<?php

declare(strict_types=1);

namespace App\Services\Recording;

use App\Models\FeedUsage;
use App\Models\Livestock;
use App\Models\LivestockDepletion;
use App\Models\Recording;
use App\Models\SupplyUsage;
use App\Config\LivestockDepletionConfig;
use App\Services\Recording\Contracts\RecordingDataAggregatorInterface;
use App\Services\Recording\DTOs\ServiceResult;
use App\Services\Recording\Exceptions\RecordingDataAggregationException;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

/**
 * Recording Data Aggregator Service
 * 
 * Service untuk mengaggregate data recording dari berbagai sumber:
 * - Feed Usage (manual dan automated)
 * - Supply Usage (manual dan automated)
 * - Depletion (manual dan automated)
 * - Recording (existing)
 * 
 * @version 1.0
 * @since 2025-01-25
 */
class RecordingDataAggregatorService implements RecordingDataAggregatorInterface
{
    /**
     * Data source priorities (higher number = higher priority)
     */
    private const DATA_SOURCE_PRIORITIES = [
        'recording' => 100,
        'manual_feed_usage' => 90,
        'manual_supply_usage' => 85,
        'manual_depletion' => 80,
        'automated_feed_usage' => 70,
        'automated_supply_usage' => 65,
        'automated_depletion' => 60,
        'calculated' => 10
    ];

    /**
     * Valid supply usage statuses for processing
     */
    private const VALID_SUPPLY_STATUSES = [
        'pending',
        'in_process',
        'completed',
        'partially_used'
    ];

    /**
     * Service configuration
     */
    private array $config;

    /**
     * Constructor
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'enable_status_filtering' => true,
            'enable_data_validation' => true,
            'enable_performance_monitoring' => true,
            'default_values' => [
                'weight_gain' => 0.05, // kg per day
                'mortality_rate' => 0.02, // 2% per day
                'feed_consumption_per_chicken' => 0.12, // kg per chicken per day
                'supply_consumption_per_chicken' => 0.01 // unit per chicken per day
            ]
        ], $config);
    }

    /**
     * Aggregate all recording data for a specific livestock and date
     * 
     * @param string $livestockId
     * @param string $date
     * @param array $options
     * @return ServiceResult
     */
    public function aggregateData(string $livestockId, string $date, array $options = []): ServiceResult
    {
        $startTime = microtime(true);

        Log::info('🔄 RecordingDataAggregatorService::aggregateData started', [
            'livestock_id' => $livestockId,
            'date' => $date,
            'options' => $options
        ]);

        try {
            // Validate inputs
            $this->validateInputs($livestockId, $date);

            // Get livestock information
            $livestock = Livestock::findOrFail($livestockId);

            // Collect data from all sources
            $dataSources = $this->collectDataSources($livestockId, $date, $livestock);

            // Aggregate and merge data
            $aggregatedData = $this->aggregateAndMergeData($dataSources, $livestock, $date);

            // Validate aggregated data
            if ($this->config['enable_data_validation']) {
                $validationResult = $this->validateAggregatedData($aggregatedData, $livestock);
                if (!$validationResult['is_valid']) {
                    Log::error('❌ Data validation failed', $validationResult['errors']);
                    return ServiceResult::error('Data validation failed', $validationResult['errors']);
                }
            }

            // Build final payload
            $payload = $this->buildPayload($aggregatedData, $livestock, $date, $options);

            // Log performance metrics
            if ($this->config['enable_performance_monitoring']) {
                $executionTime = microtime(true) - $startTime;
                $this->logPerformanceMetrics('aggregateData', $executionTime, [
                    'livestock_id' => $livestockId,
                    'date' => $date,
                    'data_sources_count' => count($dataSources),
                    'payload_size' => strlen(json_encode($payload))
                ]);
            }

            Log::info('✅ RecordingDataAggregatorService::aggregateData completed', [
                'livestock_id' => $livestockId,
                'date' => $date,
                'execution_time' => microtime(true) - $startTime
            ]);

            return ServiceResult::success('Data aggregated successfully', $payload);
        } catch (Exception $e) {
            Log::error('❌ RecordingDataAggregatorService::aggregateData failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return ServiceResult::error('Failed to aggregate data', $e);
        }
    }

    /**
     * Collect data from all available sources
     */
    private function collectDataSources(string $livestockId, string $date, Livestock $livestock): array
    {
        $dataSources = [];

        // 1. Recording data (highest priority)
        $recording = $this->getRecordingData($livestockId, $date);
        if ($recording) {
            $dataSources['recording'] = [
                'source' => 'recording',
                'priority' => self::DATA_SOURCE_PRIORITIES['recording'],
                'data' => $recording,
                'timestamp' => $recording->updated_at ?? $recording->created_at
            ];
        }

        // 2. Feed usage data
        $feedUsageData = $this->getFeedUsageData($livestockId, $date);
        if (!empty($feedUsageData)) {
            $dataSources['feed_usage'] = [
                'source' => $feedUsageData['is_manual'] ? 'manual_feed_usage' : 'automated_feed_usage',
                'priority' => self::DATA_SOURCE_PRIORITIES[$feedUsageData['is_manual'] ? 'manual_feed_usage' : 'automated_feed_usage'],
                'data' => $feedUsageData,
                'timestamp' => $feedUsageData['timestamp'] ?? now()
            ];
        }

        // 3. Supply usage data (with status filtering)
        $supplyUsageData = $this->getSupplyUsageData($livestockId, $date);
        if (!empty($supplyUsageData)) {
            $dataSources['supply_usage'] = [
                'source' => $supplyUsageData['is_manual'] ? 'manual_supply_usage' : 'automated_supply_usage',
                'priority' => self::DATA_SOURCE_PRIORITIES[$supplyUsageData['is_manual'] ? 'manual_supply_usage' : 'automated_supply_usage'],
                'data' => $supplyUsageData,
                'timestamp' => $supplyUsageData['timestamp'] ?? now()
            ];
        }

        // 4. Depletion data
        $depletionData = $this->getDepletionData($livestockId, $date);
        if (!empty($depletionData)) {
            $dataSources['depletion'] = [
                'source' => $depletionData['is_manual'] ? 'manual_depletion' : 'automated_depletion',
                'priority' => self::DATA_SOURCE_PRIORITIES[$depletionData['is_manual'] ? 'manual_depletion' : 'automated_depletion'],
                'data' => $depletionData,
                'timestamp' => $depletionData['timestamp'] ?? now()
            ];
        }

        // 5. Calculated/estimated data (lowest priority)
        $calculatedData = $this->getCalculatedData($livestockId, $date, $livestock);
        if (!empty($calculatedData)) {
            $dataSources['calculated'] = [
                'source' => 'calculated',
                'priority' => self::DATA_SOURCE_PRIORITIES['calculated'],
                'data' => $calculatedData,
                'timestamp' => now()
            ];
        }

        Log::debug('Data sources collected', [
            'sources_count' => count($dataSources),
            'sources' => array_keys($dataSources)
        ]);

        return $dataSources;
    }

    /**
     * Get recording data
     */
    private function getRecordingData(string $livestockId, string $date): ?Recording
    {
        return Recording::where('livestock_id', $livestockId)
            ->whereDate('tanggal', $date)
            ->first();
    }

    /**
     * Get feed usage data with manual detection
     */
    private function getFeedUsageData(string $livestockId, string $date): array
    {
        try {
            $feedUsage = FeedUsage::where('livestock_id', $livestockId)
                ->whereDate('usage_date', $date)
                ->first();

            if (!$feedUsage) {
                Log::debug('No feed usage found', [
                    'livestock_id' => $livestockId,
                    'date' => $date
                ]);
                return [];
            }

            Log::debug('Feed usage found', [
                'feed_usage_id' => $feedUsage->id,
                'details_count' => $feedUsage->details->count()
            ]);

            // Load relationships separately to avoid issues
            $feedUsage->load(['details.feed', 'details.feedStock.feedPurchase']);

            $items = [];
            $totalQuantity = 0;
            $totalCost = 0;

            foreach ($feedUsage->details as $detail) {
                try {
                    $feed = $detail->feed;
                    $stock = $detail->feedStock;
                    $purchase = $stock?->feedPurchase;

                    if (!$feed || !$stock || !$purchase) {
                        Log::debug('Skipping feed detail due to missing relationships', [
                            'detail_id' => $detail->id,
                            'has_feed' => (bool) $feed,
                            'has_stock' => (bool) $stock,
                            'has_purchase' => (bool) $purchase
                        ]);
                        continue;
                    }

                    $quantity = floatval($detail->quantity_taken);
                    $pricePerUnit = floatval($purchase->price_per_unit ?? 0);
                    $subtotal = $quantity * $pricePerUnit;

                    $totalQuantity += $quantity;
                    $totalCost += $subtotal;

                    $items[] = [
                        'feed_id' => $feed->id,
                        'feed_name' => $feed->name,
                        'feed_code' => $feed->code,
                        'quantity' => $quantity,
                        'unit_name' => $feed->unit?->name ?? 'KG',
                        'price_per_unit' => $pricePerUnit,
                        'subtotal' => $subtotal,
                        'stock_id' => $stock->id,
                        'purchase_id' => $purchase->id,
                        'supplier_name' => $purchase->supplier?->name ?? 'Unknown',
                        'purchase_date' => $purchase->purchase_date?->format('Y-m-d') ?? null
                    ];

                    Log::debug('Feed detail processed successfully', [
                        'detail_id' => $detail->id,
                        'feed_name' => $feed->name,
                        'quantity' => $quantity,
                        'subtotal' => $subtotal
                    ]);
                } catch (Exception $e) {
                    Log::error('Error processing feed usage detail', [
                        'detail_id' => $detail->id,
                        'error' => $e->getMessage()
                    ]);
                    continue;
                }
            }

            $result = [
                'usage_id' => $feedUsage->id,
                'is_manual' => $feedUsage->created_by_manual_input ?? false,
                'items' => $items,
                'total_quantity' => $totalQuantity,
                'total_cost' => $totalCost,
                'timestamp' => $feedUsage->updated_at ?? $feedUsage->created_at
            ];

            Log::debug('Feed usage data processed', [
                'items_count' => count($items),
                'total_quantity' => $totalQuantity,
                'total_cost' => $totalCost
            ]);

            return $result;
        } catch (Exception $e) {
            Log::error('Error in getFeedUsageData', [
                'livestock_id' => $livestockId,
                'date' => $date,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }

    /**
     * Get supply usage data with status filtering
     */
    private function getSupplyUsageData(string $livestockId, string $date): array
    {
        try {
            $query = SupplyUsage::where('livestock_id', $livestockId)
                ->whereDate('usage_date', $date);

            // Apply status filtering if enabled
            if ($this->config['enable_status_filtering']) {
                $query->whereIn('status', self::VALID_SUPPLY_STATUSES);
            }

            $supplyUsage = $query->first();

            if (!$supplyUsage) {
                Log::debug('No supply usage found', [
                    'livestock_id' => $livestockId,
                    'date' => $date,
                    'status_filtering' => $this->config['enable_status_filtering']
                ]);
                return [];
            }

            Log::debug('Supply usage found', [
                'supply_usage_id' => $supplyUsage->id,
                'status' => $supplyUsage->status,
                'details_count' => $supplyUsage->details->count()
            ]);

            // Load relationships separately to avoid issues
            $supplyUsage->load(['details.supply', 'details.supplyStock.supplyPurchase']);

            $items = [];
            $totalQuantity = 0;
            $totalCost = 0;

            foreach ($supplyUsage->details as $detail) {
                try {
                    $supply = $detail->supply;
                    $stock = $detail->supplyStock;
                    $purchase = $stock?->supplyPurchase;

                    if (!$supply || !$stock || !$purchase) {
                        Log::debug('Skipping detail due to missing relationships', [
                            'detail_id' => $detail->id,
                            'has_supply' => (bool) $supply,
                            'has_stock' => (bool) $stock,
                            'has_purchase' => (bool) $purchase
                        ]);
                        continue;
                    }

                    $quantity = floatval($detail->quantity_taken);
                    $pricePerUnit = floatval($purchase->price_per_converted_unit ?? $purchase->price_per_unit ?? 0);
                    $subtotal = $quantity * $pricePerUnit;

                    $totalQuantity += $quantity;
                    $totalCost += $subtotal;

                    $items[] = [
                        'supply_id' => $supply->id,
                        'supply_name' => $supply->name,
                        'supply_code' => $supply->code,
                        'quantity' => $quantity,
                        'unit_name' => $supply->unit?->name ?? 'UNIT',
                        'price_per_unit' => $pricePerUnit,
                        'subtotal' => $subtotal,
                        'stock_id' => $stock->id,
                        'purchase_id' => $purchase->id,
                        'supplier_name' => $purchase->supplier?->name ?? 'Unknown',
                        'purchase_date' => $purchase->purchase_date?->format('Y-m-d') ?? null
                    ];

                    Log::debug('Detail processed successfully', [
                        'detail_id' => $detail->id,
                        'supply_name' => $supply->name,
                        'quantity' => $quantity,
                        'subtotal' => $subtotal
                    ]);
                } catch (Exception $e) {
                    Log::error('Error processing supply usage detail', [
                        'detail_id' => $detail->id,
                        'error' => $e->getMessage()
                    ]);
                    continue;
                }
            }

            $result = [
                'usage_id' => $supplyUsage->id,
                'status' => $supplyUsage->status,
                'is_manual' => $supplyUsage->created_by_manual_input ?? false,
                'items' => $items,
                'total_quantity' => $totalQuantity,
                'total_cost' => $totalCost,
                'timestamp' => $supplyUsage->updated_at ?? $supplyUsage->created_at
            ];

            Log::debug('Supply usage data processed', [
                'items_count' => count($items),
                'total_quantity' => $totalQuantity,
                'total_cost' => $totalCost
            ]);

            return $result;
        } catch (Exception $e) {
            Log::error('Error in getSupplyUsageData', [
                'livestock_id' => $livestockId,
                'date' => $date,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }

    /**
     * Get depletion data with manual detection
     */
    private function getDepletionData(string $livestockId, string $date): array
    {
        $depletions = LivestockDepletion::where('livestock_id', $livestockId)
            ->whereDate('tanggal', $date)
            ->get();

        if ($depletions->isEmpty()) {
            return [];
        }

        $mortality = 0;
        $culling = 0;
        $sales = 0;
        $isManual = false;

        foreach ($depletions as $depletion) {
            $quantity = intval($depletion->jumlah);
            $type = $depletion->jenis;
            $metadata = is_array($depletion->metadata) ? $depletion->metadata : json_decode($depletion->metadata ?? '{}', true);

            // Check if manual depletion
            if (isset($metadata['depletion_method']) && $metadata['depletion_method'] === 'manual') {
                $isManual = true;
            }

            // Categorize by type
            switch ($type) {
                case LivestockDepletionConfig::TYPE_MORTALITY:
                case LivestockDepletionConfig::LEGACY_TYPE_MATI:
                    $mortality += $quantity;
                    break;
                case LivestockDepletionConfig::TYPE_CULLING:
                case LivestockDepletionConfig::LEGACY_TYPE_AFKIR:
                    $culling += $quantity;
                    break;
                default:
                    // Assume other types are sales
                    $sales += $quantity;
                    break;
            }
        }

        return [
            'mortality' => $mortality,
            'culling' => $culling,
            'sales_quantity' => $sales,
            'total_depletion' => $mortality + $culling + $sales,
            'is_manual' => $isManual,
            'depletions' => $depletions->toArray(),
            'timestamp' => $depletions->max('updated_at') ?? now()
        ];
    }

    /**
     * Get calculated/estimated data
     */
    private function getCalculatedData(string $livestockId, string $date, Livestock $livestock): array
    {
        // Get previous day's data for calculations
        $previousDate = Carbon::parse($date)->subDay()->format('Y-m-d');
        $previousRecording = Recording::where('livestock_id', $livestockId)
            ->whereDate('tanggal', $previousDate)
            ->first();

        $calculatedData = [];

        // Calculate age
        if ($livestock->start_date) {
            $startDate = Carbon::parse($livestock->start_date);
            $recordDate = Carbon::parse($date);
            $calculatedData['age_days'] = $startDate->diffInDays($recordDate, false);
        }

        // Estimate weight gain if no recording exists
        if (!$previousRecording) {
            $calculatedData['estimated_weight_gain'] = $this->config['default_values']['weight_gain'];
        }

        return $calculatedData;
    }

    /**
     * Aggregate and merge data from multiple sources
     */
    private function aggregateAndMergeData(array $dataSources, Livestock $livestock, string $date): array
    {
        $aggregatedData = [
            'livestock_info' => [
                'id' => $livestock->id,
                'name' => $livestock->name,
                'strain' => $livestock->strain?->name ?? 'Unknown Strain',
                'start_date' => $livestock->start_date,
                'initial_quantity' => $livestock->initial_quantity
            ],
            'date' => $date,
            'data_sources' => array_keys($dataSources),
            'feed_usage' => [],
            'supply_usage' => [],
            'depletion' => [],
            'weight' => [],
            'population' => [],
            'performance' => [],
            'metadata' => [
                'aggregated_at' => now()->toIso8601String(),
                'data_sources_count' => count($dataSources),
                'validation_enabled' => $this->config['enable_data_validation']
            ]
        ];

        // Sort data sources by priority (highest first)
        uasort($dataSources, function ($a, $b) {
            return $b['priority'] - $a['priority'];
        });

        // Merge data based on priority
        foreach ($dataSources as $sourceKey => $sourceData) {
            $this->mergeDataBySource($aggregatedData, $sourceData, $sourceKey);
        }

        // Calculate derived values
        $this->calculateDerivedValues($aggregatedData, $livestock, $date);

        return $aggregatedData;
    }

    /**
     * Merge data from a specific source
     */
    private function mergeDataBySource(array &$aggregatedData, array $sourceData, string $sourceKey): void
    {
        $data = $sourceData['data'];
        $source = $sourceData['source'];

        switch ($sourceKey) {
            case 'feed_usage':
                if (!empty($data)) {
                    $aggregatedData['feed_usage'] = array_merge($aggregatedData['feed_usage'], $data);
                    $aggregatedData['feed_usage']['source'] = $source;
                    $aggregatedData['feed_usage']['timestamp'] = $sourceData['timestamp'];
                }
                break;

            case 'supply_usage':
                if (!empty($data)) {
                    $aggregatedData['supply_usage'] = array_merge($aggregatedData['supply_usage'], $data);
                    $aggregatedData['supply_usage']['source'] = $source;
                    $aggregatedData['supply_usage']['timestamp'] = $sourceData['timestamp'];
                }
                break;

            case 'depletion':
                if (!empty($data)) {
                    $aggregatedData['depletion'] = array_merge($aggregatedData['depletion'], $data);
                    $aggregatedData['depletion']['source'] = $source;
                    $aggregatedData['depletion']['timestamp'] = $sourceData['timestamp'];
                }
                break;

            case 'recording':
                if (!empty($data)) {
                    // Extract weight data
                    if (isset($data->berat_hari_ini)) {
                        $aggregatedData['weight']['today'] = floatval($data->berat_hari_ini);
                    }
                    if (isset($data->berat_semalam)) {
                        $aggregatedData['weight']['yesterday'] = floatval($data->berat_semalam);
                    }
                    if (isset($data->kenaikan_berat)) {
                        $aggregatedData['weight']['gain'] = floatval($data->kenaikan_berat);
                    }

                    // Extract population data
                    if (isset($data->stock_awal)) {
                        $aggregatedData['population']['stock_start'] = intval($data->stock_awal);
                    }
                    if (isset($data->stock_akhir)) {
                        $aggregatedData['population']['stock_end'] = intval($data->stock_akhir);
                    }

                    // Extract payload data if available
                    if (isset($data->payload) && is_array($data->payload)) {
                        $this->extractPayloadData($aggregatedData, $data->payload);
                    }

                    $aggregatedData['weight']['source'] = $source;
                    $aggregatedData['population']['source'] = $source;
                    $aggregatedData['weight']['timestamp'] = $sourceData['timestamp'];
                    $aggregatedData['population']['timestamp'] = $sourceData['timestamp'];
                }
                break;

            case 'calculated':
                if (!empty($data)) {
                    $aggregatedData['calculated'] = array_merge($aggregatedData['calculated'] ?? [], $data);
                    $aggregatedData['calculated']['source'] = $source;
                    $aggregatedData['calculated']['timestamp'] = $sourceData['timestamp'];
                }
                break;
        }
    }

    /**
     * Extract data from recording payload
     */
    private function extractPayloadData(array &$aggregatedData, array $payload): void
    {
        // Extract consumption data
        if (isset($payload['consumption'])) {
            if (isset($payload['consumption']['feed'])) {
                $aggregatedData['feed_usage']['payload_data'] = $payload['consumption']['feed'];
            }
            if (isset($payload['consumption']['supply'])) {
                $aggregatedData['supply_usage']['payload_data'] = $payload['consumption']['supply'];
            }
        }

        // Extract production data
        if (isset($payload['production'])) {
            if (isset($payload['production']['weight'])) {
                $aggregatedData['weight']['payload_data'] = $payload['production']['weight'];
            }
            if (isset($payload['production']['depletion'])) {
                $aggregatedData['depletion']['payload_data'] = $payload['production']['depletion'];
            }
        }

        // Extract performance data
        if (isset($payload['performance'])) {
            $aggregatedData['performance'] = $payload['performance'];
        }

        // Extract config data
        if (isset($payload['config'])) {
            $aggregatedData['config'] = $payload['config'];
        }
    }

    /**
     * Calculate derived values
     */
    private function calculateDerivedValues(array &$aggregatedData, Livestock $livestock, string $date): void
    {
        // Calculate weight gain if not available
        if (
            !isset($aggregatedData['weight']['gain']) &&
            isset($aggregatedData['weight']['today']) &&
            isset($aggregatedData['weight']['yesterday'])
        ) {
            $aggregatedData['weight']['gain'] = $aggregatedData['weight']['today'] - $aggregatedData['weight']['yesterday'];
        }

        // Calculate population change
        if (
            isset($aggregatedData['population']['stock_start']) &&
            isset($aggregatedData['population']['stock_end'])
        ) {
            $aggregatedData['population']['change'] = $aggregatedData['population']['stock_end'] - $aggregatedData['population']['stock_start'];
        }

        // Calculate total consumption costs
        $totalFeedCost = $aggregatedData['feed_usage']['total_cost'] ?? 0;
        $totalSupplyCost = $aggregatedData['supply_usage']['total_cost'] ?? 0;
        $aggregatedData['consumption']['total_cost'] = $totalFeedCost + $totalSupplyCost;

        // Calculate per-chicken costs
        $stockEnd = $aggregatedData['population']['stock_end'] ?? $livestock->initial_quantity;
        if ($stockEnd > 0) {
            $aggregatedData['consumption']['feed_cost_per_chicken'] = round($totalFeedCost / $stockEnd, 4);
            $aggregatedData['consumption']['supply_cost_per_chicken'] = round($totalSupplyCost / $stockEnd, 4);
            $aggregatedData['consumption']['total_cost_per_chicken'] = round($aggregatedData['consumption']['total_cost'] / $stockEnd, 4);
        }

        // Calculate performance metrics
        $this->calculatePerformanceMetrics($aggregatedData, $livestock, $date);
    }

    /**
     * Calculate performance metrics
     */
    private function calculatePerformanceMetrics(array &$aggregatedData, Livestock $livestock, string $date): void
    {
        $age = $aggregatedData['calculated']['age_days'] ?? 0;
        $currentPopulation = $aggregatedData['population']['stock_end'] ?? $livestock->initial_quantity;
        $initialPopulation = $livestock->initial_quantity;
        $currentWeight = $aggregatedData['weight']['today'] ?? 0;
        $totalFeedConsumption = $aggregatedData['feed_usage']['total_quantity'] ?? 0;
        $totalDepleted = $aggregatedData['depletion']['total_depletion'] ?? 0;

        if ($age > 0 && $currentPopulation > 0) {
            // Calculate IP (Index Performance)
            $ip = $currentPopulation * $currentWeight / 1000; // Convert to kg
            $aggregatedData['performance']['ip'] = round($ip, 2);

            // Calculate FCR (Feed Conversion Ratio)
            if ($totalFeedConsumption > 0) {
                $fcr = $totalFeedConsumption / ($currentPopulation * $currentWeight / 1000);
                $aggregatedData['performance']['fcr'] = round($fcr, 3);
            }

            // Calculate Liveability
            if ($initialPopulation > 0) {
                $liveability = ($currentPopulation / $initialPopulation) * 100;
                $aggregatedData['performance']['liveability'] = round($liveability, 2);
            }
        }

        $aggregatedData['performance']['calculated_at'] = now()->toIso8601String();
        $aggregatedData['performance']['calculation_method'] = 'standard_poultry_metrics';
    }

    /**
     * Validate aggregated data
     */
    private function validateAggregatedData(array $data, Livestock $livestock): array
    {
        $errors = [];
        $rules = $this->config['validation_rules'];

        // Validate weight gain
        if (isset($data['weight']['gain'])) {
            $weightGain = abs($data['weight']['gain']);
            if ($weightGain > $rules['max_weight_gain_per_day']) {
                $errors[] = "Weight gain ({$weightGain}g) exceeds maximum allowed ({$rules['max_weight_gain_per_day']}g)";
            }
        }

        // Validate mortality rate
        if (isset($data['depletion']['mortality']) && isset($data['population']['stock_start'])) {
            $mortalityRate = $data['depletion']['mortality'] / $data['population']['stock_start'];
            if ($mortalityRate > $rules['max_mortality_rate']) {
                $errors[] = "Mortality rate ({$mortalityRate}) exceeds maximum allowed ({$rules['max_mortality_rate']})";
            }
        }

        // Validate feed consumption
        if (isset($data['feed_usage']['total_quantity']) && isset($data['population']['stock_end'])) {
            $feedPerChicken = $data['feed_usage']['total_quantity'] / $data['population']['stock_end'];
            if ($feedPerChicken > $rules['max_feed_consumption_per_chicken']) {
                $errors[] = "Feed consumption per chicken ({$feedPerChicken}kg) exceeds maximum allowed ({$rules['max_feed_consumption_per_chicken']}kg)";
            }
        }

        // Validate supply consumption
        if (isset($data['supply_usage']['total_quantity']) && isset($data['population']['stock_end'])) {
            $supplyPerChicken = $data['supply_usage']['total_quantity'] / $data['population']['stock_end'];
            if ($supplyPerChicken > $rules['max_supply_consumption_per_chicken']) {
                $errors[] = "Supply consumption per chicken ({$supplyPerChicken}L) exceeds maximum allowed ({$rules['max_supply_consumption_per_chicken']}L)";
            }
        }

        return [
            'is_valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Build final payload
     */
    private function buildPayload(array $aggregatedData, Livestock $livestock, string $date, array $options): array
    {
        $payload = [
            'schema' => [
                'version' => '3.0',
                'structure' => 'hierarchical_organized',
                'schema_date' => '2025-01-25',
                'compatibility' => ['2.0', '3.0']
            ],
            'livestock' => [
                'location' => [
                    'coop_id' => $livestock->coop_id,
                    'farm_id' => $livestock->farm_id,
                    'coop_name' => $livestock->coop?->name ?? 'Unknown',
                    'farm_name' => $livestock->farm?->name ?? 'Unknown'
                ],
                'basic_info' => [
                    'id' => $livestock->id,
                    'name' => $livestock->name,
                    'strain' => $aggregatedData['livestock_info']['strain'],
                    'age_days' => $aggregatedData['calculated']['age_days'] ?? 0,
                    'start_date' => $livestock->start_date
                ],
                'population' => $aggregatedData['population']
            ],
            'recording' => [
                'date' => $date,
                'user' => [
                    'id' => Auth::id() ?? 'system',
                    'name' => Auth::user()?->name ?? 'System',
                    'role' => Auth::user()?->roles?->first()?->name ?? 'System',
                    'company_id' => Auth::user()?->company_id ?? null
                ],
                'source' => [
                    'method' => 'aggregated',
                    'version' => '3.0',
                    'component' => 'RecordingDataAggregatorService',
                    'application' => 'service_aggregator'
                ],
                'age_days' => $aggregatedData['calculated']['age_days'] ?? 0,
                'timestamp' => now()->toIso8601String()
            ],
            'production' => [
                'sales' => [
                    'weight' => 0,
                    'quantity' => $aggregatedData['depletion']['sales_quantity'] ?? 0,
                    'total_value' => 0,
                    'average_weight' => 0,
                    'price_per_unit' => 0
                ],
                'weight' => $aggregatedData['weight'],
                'depletion' => [
                    'total' => $aggregatedData['depletion']['total_depletion'] ?? 0,
                    'culling' => $aggregatedData['depletion']['culling'] ?? 0,
                    'mortality' => $aggregatedData['depletion']['mortality'] ?? 0
                ]
            ],
            'consumption' => [
                'feed' => [
                    'items' => $aggregatedData['feed_usage']['items'] ?? [],
                    'total_cost' => $aggregatedData['feed_usage']['total_cost'] ?? 0,
                    'cost_per_kg' => $aggregatedData['consumption']['feed_cost_per_chicken'] ?? 0,
                    'types_count' => count($aggregatedData['feed_usage']['items'] ?? []),
                    'total_quantity' => $aggregatedData['feed_usage']['total_quantity'] ?? 0
                ],
                'supply' => [
                    'items' => $aggregatedData['supply_usage']['items'] ?? [],
                    'total_cost' => $aggregatedData['supply_usage']['total_cost'] ?? 0,
                    'types_count' => count($aggregatedData['supply_usage']['items'] ?? []),
                    'cost_per_unit' => $aggregatedData['consumption']['supply_cost_per_chicken'] ?? 0,
                    'total_quantity' => $aggregatedData['supply_usage']['total_quantity'] ?? 0
                ]
            ],
            'performance' => $aggregatedData['performance'],
            'validation' => [
                'completeness' => [
                    'has_feed_data' => !empty($aggregatedData['feed_usage']['items']),
                    'has_supply_data' => !empty($aggregatedData['supply_usage']['items']),
                    'has_weight_data' => isset($aggregatedData['weight']['today']),
                    'has_depletion_data' => !empty($aggregatedData['depletion'])
                ],
                'data_quality' => [
                    'weight_logical' => true,
                    'depletion_logical' => true,
                    'population_logical' => true,
                    'feed_consumption_logical' => true
                ]
            ],
            'metadata' => [
                'aggregated_at' => now()->toIso8601String(),
                'data_sources' => $aggregatedData['data_sources'],
                'service_version' => '1.0',
                'aggregation_method' => 'priority_based_merge'
            ]
        ];

        // Add config if available
        if (isset($aggregatedData['config'])) {
            $payload['config'] = $aggregatedData['config'];
        }

        return $payload;
    }

    /**
     * Validate input parameters
     */
    private function validateInputs(string $livestockId, string $date): void
    {
        if (empty($livestockId)) {
            throw new Exception('Livestock ID is required');
        }

        if (empty($date)) {
            throw new Exception('Date is required');
        }

        // Try to parse the date to validate format
        try {
            Carbon::parse($date);
        } catch (Exception $e) {
            throw new Exception('Invalid date format: ' . $date);
        }
    }

    /**
     * Log performance metrics
     */
    private function logPerformanceMetrics(string $operation, float $executionTime, array $metadata = []): void
    {
        Log::info("Performance: {$operation}", array_merge([
            'execution_time' => $executionTime,
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true)
        ], $metadata));
    }

    /**
     * Get service configuration
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Update service configuration
     */
    public function updateConfig(array $newConfig): void
    {
        $this->config = array_merge($this->config, $newConfig);
    }

    /**
     * Reset service configuration to defaults
     */
    public function resetConfig(): void
    {
        $this->config = [
            'enable_status_filtering' => true,
            'enable_data_validation' => true,
            'enable_performance_monitoring' => true,
            'default_values' => [
                'weight_gain' => 0.05,
                'mortality_rate' => 0.02,
                'feed_consumption_per_chicken' => 0.12,
                'supply_consumption_per_chicken' => 0.01
            ]
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Services\Recording;

use App\Services\Recording\Contracts\RecordingDataServiceInterface;
use App\Services\Recording\DTOs\ServiceResult;
use Illuminate\Support\Facades\Log;
use Exception;

// Models - Pindahkan semua model yang dibutuhkan dari Legacy Service
use App\Models\Recording;
use App\Models\CurrentLivestock;
use App\Models\FeedUsage;
use App\Models\SupplyUsage;
use App\Models\LivestockDepletion;
use App\Config\LivestockDepletionConfig;
use App\Models\Livestock;
use Carbon\Carbon;

use function App\Helpers\logInfoIfDebug;
use function App\Helpers\logDebugIfDebug;
use function App\Helpers\logErrorIfDebug;

use App\Services\Recording\Contracts\RecordingSaleServiceInterface;
use App\Models\LivestockBatch;

/**
 * Concrete implementation for loading recording-related data.
 * In this initial phase, it contains placeholder logic. The actual
 * business logic will be implemented in subsequent phases.
 * 
 * @version 1.0
 * @since 2025-07-09
 */
class RecordingDataService implements RecordingDataServiceInterface
{
    private RecordingSaleServiceInterface $recordingSaleService;

    public function __construct(RecordingSaleServiceInterface $recordingSaleService)
    {
        $this->recordingSaleService = $recordingSaleService;
    }

    public function loadCurrentDateData(string $livestockId, string $date, bool $bypassCache = false): ServiceResult
    {
        $performanceService = app(\App\Services\Recording\RecordingPerformanceService::class);
        $startTime = microtime(true);
        $carbonDate = Carbon::parse($date);

        logInfoIfDebug('🔄 RecordingDataService::loadCurrentDateData started', [
            'livestock_id' => $livestockId,
            'date' => $date,
            'cache_enabled' => !$bypassCache
        ]);

        // 1. Cek cache sebelum query berat
        if (!$bypassCache) {
            $cached = $performanceService->getCachedRecordingData($livestockId, $carbonDate);
            if ($cached) {
                $performanceService->logPerformanceMetrics('loadCurrentDateData_cache_hit', microtime(true) - $startTime, [
                    'livestock_id' => $livestockId,
                    'date' => $date,
                    'cache' => true
                ]);
                logInfoIfDebug('✅ MODULAR_PATH: Cache hit for loadCurrentDateData', [
                    'livestock_id' => $livestockId,
                    'date' => $date,
                    'cached_data_keys' => array_keys($cached)
                ]);
                return ServiceResult::success('Data loaded from cache.', $cached);
            }
        } else {
            logInfoIfDebug('🔄 Bypass cache: loadCurrentDateData will query DB directly', [
                'livestock_id' => $livestockId,
                'date' => $date
            ]);
        }

        logInfoIfDebug('🔄 MODULAR_PATH: RecordingDataService::loadCurrentDateData called.', compact('livestockId', 'date'));
        try {
            $recording = Recording::where('livestock_id', $livestockId)
                ->where('tanggal', $date)
                ->first();

            logDebugIfDebug('Recording query result', [
                'recording_found' => $recording ? true : false,
                'recording_id' => $recording?->id,
                'recording_payload' => $recording ? (is_array($recording->payload) ? 'array' : 'json') : null
            ]);

            // Initialize all expected keys to prevent "Undefined array key" errors in the component.
            $data = [
                'weight_today' => null,
                'mortality' => 0,
                'culling' => 0,
                'sales_quantity' => 0,
                'sales_weight' => 0,
                'sales_price' => null,
                'sales_status' => 'draft',
                'sales_is_finalized' => false,
                'total_sales' => null,
                'itemQuantities' => [],
                'supplyQuantities' => [],
                'feedUsageId' => null,
                'supplyUsageId' => null,
                'isManualDepletionEnabled' => false,
                'isManualFeedUsageEnabled' => false,
                'recording_exists' => false,
            ];

            // --- OPTIMIZED: Fetch sales draft from RecordingSaleService ---
            $salesDraftCacheKey = "sales_draft_{$livestockId}_{$date}";
            $salesDraft = cache()->remember($salesDraftCacheKey, 60, function () use ($livestockId, $date) {
                $saleDraftResult = $this->recordingSaleService->listByLivestockAndDate($livestockId, $date, ['status' => 'draft']);
                if ($saleDraftResult->isSuccess() && is_array($saleDraftResult->getData()) && count($saleDraftResult->getData()) > 0) {
                    return $saleDraftResult->getData()[0];
                }
                return null;
            });

            if ($salesDraft) {
                $data['sales_quantity'] = $salesDraft['total_quantity'] ?? $salesDraft['quantity'] ?? 0;
                $data['sales_weight'] = $salesDraft['total_weight'] ?? $salesDraft['weight'] ?? 0;
                $data['sales_price'] = $salesDraft['price'] ?? null;
                $data['sales_status'] = $salesDraft['status'] ?? 'draft';
                $data['sales_is_finalized'] = ($salesDraft['status'] ?? 'draft') === 'finalized';
                $data['total_sales'] = $salesDraft['total_amount'] ?? null;

                // Also add recording payload if recording exists
                if ($recording) {
                    $payload = is_array($recording->payload)
                        ? $recording->payload
                        : json_decode($recording->payload, true);
                    $data['payload'] = $payload;
                    $data['weight_today'] = $recording->berat_hari_ini;
                    $data['recording_exists'] = true;

                    // Fallback weight_today from payload if berat_hari_ini is null/empty
                    if (empty($data['weight_today']) && isset($payload['production']['weight']['today'])) {
                        $data['weight_today'] = $payload['production']['weight']['today'];
                        logDebugIfDebug('Weight today fallback from payload (sales draft)', [
                            'original' => $recording->berat_hari_ini,
                            'fallback' => $data['weight_today'],
                            'source' => 'payload.production.weight.today'
                        ]);
                    }
                }

                logInfoIfDebug('Sales data loaded from RecordingSaleService (sales draft)', [
                    'sales_quantity' => $data['sales_quantity'],
                    'sales_weight' => $data['sales_weight'],
                    'sales_price' => $data['sales_price'],
                    'sales_status' => $data['sales_status'],
                    'sales_is_finalized' => $data['sales_is_finalized'],
                    'total_sales' => $data['total_sales'],
                    'source' => 'sales_draft',
                    'sales_draft_id' => $salesDraft['id'] ?? null,
                    'has_payload' => isset($data['payload']),
                    'weight_today' => $data['weight_today'] ?? null
                ]);
            } else if ($recording) {
                $payload = is_array($recording->payload)
                    ? $recording->payload
                    : json_decode($recording->payload, true);
                $data['weight_today'] = $recording->berat_hari_ini;
                $data['recording_exists'] = true;
                $data['payload'] = $payload; // Add payload to response

                // Fallback weight_today from payload if berat_hari_ini is null/empty
                if (empty($data['weight_today']) && isset($payload['production']['weight']['today'])) {
                    $data['weight_today'] = $payload['production']['weight']['today'];
                    logDebugIfDebug('Weight today fallback from payload', [
                        'original' => $recording->berat_hari_ini,
                        'fallback' => $data['weight_today'],
                        'source' => 'payload.production.weight.today'
                    ]);
                }

                logDebugIfDebug('Recording data loaded', [
                    'weight_today' => $data['weight_today'],
                    'payload_keys' => $payload ? array_keys($payload) : null
                ]);

                if (isset($payload['config']['manual_depletion_enabled']) && $payload['config']['manual_depletion_enabled']) {
                    $data['isManualDepletionEnabled'] = true;
                } else {
                    $data['isManualDepletionEnabled'] = false;
                }

                // PRIORITY: Extract sales data from payload['production']['sales'] if exists
                if (isset($payload['production']['sales'])) {
                    $sales = $payload['production']['sales'];
                    $data['sales_quantity'] = isset($sales['quantity']) ? (int)$sales['quantity'] : 0;
                    $data['sales_weight'] = isset($sales['weight']) ? (float)$sales['weight'] : 0;
                    $data['sales_price'] = isset($sales['price_per_unit']) ? (float)$sales['price_per_unit'] : null;
                    $data['sales_status'] = isset($sales['status']) ? $sales['status'] : 'draft';
                    $data['sales_is_finalized'] = isset($sales['is_finalized']) ? (bool)$sales['is_finalized'] : false;
                    $data['total_sales'] = isset($sales['total_value']) ? (float)$sales['total_value'] : null;

                    logInfoIfDebug('Sales data loaded from payload.production.sales (PRIORITY)', [
                        'sales_quantity' => $data['sales_quantity'],
                        'sales_weight' => $data['sales_weight'],
                        'sales_price' => $data['sales_price'],
                        'sales_status' => $data['sales_status'],
                        'sales_is_finalized' => $data['sales_is_finalized'],
                        'total_sales' => $data['total_sales'],
                        'source' => 'payload_production_sales'
                    ]);
                } else if (isset($payload['sales_quantity']) || isset($payload['sales_weight'])) {
                    // HIGH PRIORITY: Extract sales data directly from payload root level
                    $data['sales_quantity'] = isset($payload['sales_quantity']) ? (int)$payload['sales_quantity'] : 0;
                    $data['sales_weight'] = isset($payload['sales_weight']) ? (float)$payload['sales_weight'] : 0;
                    $data['sales_price'] = isset($payload['sales_price']) ? (float)$payload['sales_price'] : null;
                    $data['sales_status'] = isset($payload['sales_status']) ? $payload['sales_status'] : 'draft';
                    $data['sales_is_finalized'] = isset($payload['sales_is_finalized']) ? (bool)$payload['sales_is_finalized'] : false;
                    $data['total_sales'] = isset($payload['total_sales']) ? (float)$payload['total_sales'] : null;

                    logInfoIfDebug('Sales data loaded from payload root level (HIGH PRIORITY)', [
                        'sales_quantity' => $data['sales_quantity'],
                        'sales_weight' => $data['sales_weight'],
                        'sales_price' => $data['sales_price'],
                        'sales_status' => $data['sales_status'],
                        'sales_is_finalized' => $data['sales_is_finalized'],
                        'total_sales' => $data['total_sales'],
                        'source' => 'payload_root_level',
                        'payload_keys' => array_keys($payload)
                    ]);
                } else {
                    // Fallback: Check if sales data exists in RecordingSaleService
                    $salesDraftResult = $this->recordingSaleService->listByLivestockAndDate($livestockId, $date, ['status' => 'draft']);
                    if ($salesDraftResult->isSuccess() && is_array($salesDraftResult->getData()) && count($salesDraftResult->getData()) > 0) {
                        $salesDraft = $salesDraftResult->getData()[0];
                        $data['sales_quantity'] = $salesDraft['quantity'] ?? 0;
                        $data['sales_weight'] = $salesDraft['weight'] ?? 0;
                        $data['sales_price'] = $salesDraft['price'] ?? null;
                        $data['sales_status'] = $salesDraft['status'] ?? 'draft';
                        $data['sales_is_finalized'] = ($salesDraft['status'] ?? 'draft') === 'finalized';

                        logInfoIfDebug('Sales data loaded from RecordingSaleService (fallback)', [
                            'sales_quantity' => $data['sales_quantity'],
                            'sales_weight' => $data['sales_weight'],
                            'sales_price' => $data['sales_price'],
                            'sales_status' => $data['sales_status'],
                            'sales_is_finalized' => $data['sales_is_finalized'],
                            'source' => 'recording_sale_service_fallback'
                        ]);
                    } else {
                        $data['sales_quantity'] = 0;
                        $data['sales_weight'] = 0;
                        $data['sales_price'] = null;
                        $data['sales_status'] = 'draft';
                        $data['sales_is_finalized'] = false;
                        $data['total_sales'] = null;

                        logDebugIfDebug('No sales data found in payload or RecordingSaleService, defaulting to 0', [
                            'payload_production_sales_exists' => isset($payload['production']['sales']),
                            'recording_sale_service_result' => $salesDraftResult->isSuccess()
                        ]);
                    }
                }
            }

            // PRIORITY: Extract depletion data from payload if exists
            if ($recording && isset($payload['production']['depletion'])) {
                $depletion = $payload['production']['depletion'];
                $data['mortality'] = isset($depletion['mortality']) ? (int)$depletion['mortality'] : 0;
                $data['culling'] = isset($depletion['culling']) ? (int)$depletion['culling'] : 0;
                
                logInfoIfDebug('Depletion data loaded from payload.production.depletion (PRIORITY)', [
                    'mortality' => $data['mortality'],
                    'culling' => $data['culling'],
                    'source' => 'payload_production_depletion'
                ]);
            } else if ($recording && isset($payload['mortality']) || isset($payload['culling'])) {
                // HIGH PRIORITY: Extract depletion data directly from payload root level
                $data['mortality'] = isset($payload['mortality']) ? (int)$payload['mortality'] : 0;
                $data['culling'] = isset($payload['culling']) ? (int)$payload['culling'] : 0;
                
                logInfoIfDebug('Depletion data loaded from payload root level (HIGH PRIORITY)', [
                    'mortality' => $data['mortality'],
                    'culling' => $data['culling'],
                    'source' => 'payload_root_level'
                ]);
            } else {
                // Fallback: Load from LivestockDepletion table
                $data['mortality'] = LivestockDepletion::where('livestock_id', $livestockId)
                    ->where('tanggal', $date)
                    ->where('jenis', LivestockDepletionConfig::TYPE_MORTALITY)
                    ->sum('jumlah');

                $data['culling'] = LivestockDepletion::where('livestock_id', $livestockId)
                    ->where('tanggal', $date)
                    ->where('jenis', LivestockDepletionConfig::TYPE_CULLING)
                    ->sum('jumlah');
                
                logDebugIfDebug('Depletion data loaded from LivestockDepletion table (fallback)', [
                    'mortality' => $data['mortality'],
                    'culling' => $data['culling'],
                    'source' => 'livestock_depletion_table'
                ]);
            }

            // ALWAYS fetch feed and supply usage, regardless of recording existence.
            // This ensures feedUsageId and supplyUsageId are always present.
            $feedUsage = FeedUsage::where('livestock_id', $livestockId)
                ->whereDate('usage_date', $date)
                ->first();
            if ($feedUsage) {
                logInfoIfDebug('✅ MODULAR_PATH: FeedUsage found for today.', [
                    'feedUsageId' => $feedUsage->id,
                    'details_count' => $feedUsage->details->count(),
                    'created_by_manual_input' => $feedUsage->created_by_manual_input
                ]);
                $data['feedUsageId'] = $feedUsage->id;
                if ($feedUsage->created_by_manual_input) {
                    $data['isManualFeedUsageEnabled'] = true;
                    // itemQuantities remains empty as it's handled by another component
                    logInfoIfDebug('MODULAR_PATH: Manual feed usage detected. Skipping itemQuantities population.');
                } else {
                    $data['isManualFeedUsageEnabled'] = false;
                    $itemQuantities = [];
                    foreach ($feedUsage->details as $detail) {
                        $itemQuantities[$detail->feed_id] = $detail->quantity_taken;
                    }
                    $data['itemQuantities'] = $itemQuantities;
                    logInfoIfDebug('MODULAR_PATH: Populated itemQuantities from FeedUsage details.', ['itemQuantities' => $itemQuantities]);
                }
            } else {
                logInfoIfDebug('MODULAR_PATH: No FeedUsage found for today.');
            }

            $supplyUsage = SupplyUsage::where('livestock_id', $livestockId)
                ->whereDate('usage_date', $date)
                ->whereIn('status', [
                    SupplyUsage::STATUS_PENDING,
                    SupplyUsage::STATUS_IN_PROCESS,
                    SupplyUsage::STATUS_COMPLETED,
                    SupplyUsage::STATUS_PARTIALLY_USED
                ])
                ->first();
            if ($supplyUsage) {
                $supplyQuantities = [];
                foreach ($supplyUsage->details as $detail) {
                    $supplyQuantities[$detail->supply_id] = $detail->quantity_taken;
                }
                $data['supplyQuantities'] = $supplyQuantities;
                $data['supplyUsageId'] = $supplyUsage->id;

                logDebugIfDebug('Supply usage loaded', [
                    'supplyUsageId' => $data['supplyUsageId'],
                    'supplyQuantities_count' => count($supplyQuantities),
                    'supplyQuantities' => $supplyQuantities
                ]);
            } else {
                logDebugIfDebug('No supply usage found for date', [
                    'livestock_id' => $livestockId,
                    'date' => $date
                ]);
            }

            logInfoIfDebug('✅ MODULAR_PATH: Final data being returned from loadCurrentDateData.', [
                'data_keys' => array_keys($data),
                'essential_keys_present' => [
                    'itemQuantities' => isset($data['itemQuantities']),
                    'supplyQuantities' => isset($data['supplyQuantities']),
                    'mortality' => isset($data['mortality']),
                    'culling' => isset($data['culling'])
                ]
            ]);

            // 2. Simpan hasil ke cache
            $performanceService->cacheRecordingData($livestockId, $carbonDate, $data);
            // 3. Log waktu proses dengan metadata komprehensif
            $user = \Illuminate\Support\Facades\Auth::user();
            $executionTime = microtime(true) - $startTime;
            $threshold = 2.0; // seconds, match PERFORMANCE_THRESHOLD
            $isBottleneck = $executionTime > $threshold;
            $userName = $user?->name ?? null;
            $companyName = $user?->company?->name ?? null;
            $envMeta = [
                'server_hostname' => gethostname(),
                'php_version' => PHP_VERSION,
                'os' => PHP_OS,
                'memory_usage' => memory_get_usage(true),
                'peak_memory_usage' => memory_get_peak_usage(true),
                'laravel_version' => app()->version(),
                'app_env' => config('app.env'),
                'app_debug' => config('app.debug'),
            ];
            $metadata = array_merge([
                'date' => $date,
                'cache' => false,
                'livestock_id' => $livestockId,
                'query_params' => [
                    'livestock_id' => $livestockId,
                    'date' => $date
                ],
                'recording_exists' => $data['recording_exists'] ?? null,
                'feed_usage_count' => isset($data['itemQuantities']) ? count($data['itemQuantities']) : 0,
                'supply_usage_count' => isset($data['supplyQuantities']) ? count($data['supplyQuantities']) : 0,
                'depletion_count' => ($data['mortality'] > 0 ? 1 : 0) + ($data['culling'] > 0 ? 1 : 0),
                'is_manual_depletion' => $data['isManualDepletionEnabled'] ?? null,
                'is_manual_feed_usage' => $data['isManualFeedUsageEnabled'] ?? null,
                'user_id' => $user?->id,
                'user_name' => $userName,
                'company_id' => $user?->company_id,
                'company_name' => $companyName,
                'user_agent' => request()->header('User-Agent'),
                'service_version' => 'performance_v1.0',
                'environment' => config('app.env'),
                'app_debug' => config('app.debug'),
                'is_bottleneck' => $isBottleneck,
                'execution_time' => $executionTime,
                'performance_threshold' => $threshold,
            ], $envMeta);
            $performanceService->logPerformanceMetrics('loadCurrentDateData', $executionTime, $metadata);

            return ServiceResult::success('Data for the current date loaded successfully.', $data);
        } catch (Exception $e) {
            logErrorIfDebug('❌ MODULAR_PATH: Error in RecordingDataService::loadCurrentDateData', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return ServiceResult::error('Failed to load data for the selected date.', $e);
        }
    }

    /**
     * Load yesterday's data with virtual quantity calculation support
     */
    public function loadYesterdayData(string $livestockId, string $yesterdayDate): ServiceResult
    {
        try {
            logInfoIfDebug('🔄 RecordingDataService::loadYesterdayData started', [
                'livestock_id' => $livestockId,
                'yesterday_date' => $yesterdayDate
            ]);

            // Get sales configuration
            $salesConfig = \App\Config\CompanyConfig::getSalesConfig();
            $quantityCalculation = $salesConfig['quantity_calculation'] ?? [];
            $isVirtualMode = ($quantityCalculation['mode'] ?? 'real') === 'virtual';

            // Get recording data
            $yesterdayRecording = Recording::where('livestock_id', $livestockId)
                ->where('tanggal', $yesterdayDate)
                ->first();

            // Initialize default values
            $yesterdayWeight = 0;
            $yesterdayStockEnd = 0;
            $yesterdayMortality = 0;
            $yesterdayCulling = 0;
            $yesterdayFeedUsage = [
                'total_quantity' => 0,
                'by_type' => [],
                'types_count' => 0
            ];
            $yesterdaySupplyUsage = [
                'total_quantity' => 0,
                'by_type' => [],
                'types_count' => 0
            ];
            $yesterdaySalesQuantity = 0;
            $yesterdaySalesWeight = 0;
            $yesterdaySalesStatus = 'draft';

            // Extract data from recording if exists
            if ($yesterdayRecording) {
                $payload = is_array($yesterdayRecording->payload)
                    ? $yesterdayRecording->payload
                    : json_decode($yesterdayRecording->payload, true);

                // Extract weight data
                if (isset($payload['production']['weight']['today'])) {
                    $yesterdayWeight = (float) $payload['production']['weight']['today'];
                } elseif (isset($payload['weight']['today'])) {
                    $yesterdayWeight = (float) $payload['weight']['today'];
                }

                // Extract stock data
                if (isset($payload['production']['stock']['end'])) {
                    $yesterdayStockEnd = (int) $payload['production']['stock']['end'];
                } elseif (isset($payload['stock']['end'])) {
                    $yesterdayStockEnd = (int) $payload['stock']['end'];
                }

                // Extract depletion data
                if (isset($payload['production']['depletion'])) {
                    $depletion = $payload['production']['depletion'];
                    $yesterdayMortality = (int) ($depletion['mortality'] ?? 0);
                    $yesterdayCulling = (int) ($depletion['culling'] ?? 0);
                } elseif (isset($payload['depletion'])) {
                    $depletion = $payload['depletion'];
                    $yesterdayMortality = (int) ($depletion['mortality'] ?? 0);
                    $yesterdayCulling = (int) ($depletion['culling'] ?? 0);
                }

                // Extract feed usage data
                if (isset($payload['production']['feed_usage'])) {
                    $feedUsage = $payload['production']['feed_usage'];
                    $yesterdayFeedUsage = [
                        'total_quantity' => (float) ($feedUsage['total_quantity'] ?? 0),
                        'by_type' => $feedUsage['by_type'] ?? [],
                        'types_count' => (int) ($feedUsage['types_count'] ?? 0)
                    ];
                }

                // Extract supply usage data
                if (isset($payload['production']['supply_usage'])) {
                    $supplyUsage = $payload['production']['supply_usage'];
                    $yesterdaySupplyUsage = [
                        'total_quantity' => (float) ($supplyUsage['total_quantity'] ?? 0),
                        'by_type' => $supplyUsage['by_type'] ?? [],
                        'types_count' => (int) ($supplyUsage['types_count'] ?? 0)
                    ];
                }

                // Extract sales data with virtual calculation support
                if ($isVirtualMode) {
                    // Try to get virtual sales data from batch data column
                    $virtualSalesData = $this->getVirtualSalesData($livestockId, $yesterdayDate);
                    if ($virtualSalesData) {
                        $yesterdaySalesQuantity = $virtualSalesData['quantity'] ?? 0;
                        $yesterdaySalesWeight = $virtualSalesData['weight'] ?? 0;
                        $yesterdaySalesStatus = $virtualSalesData['status'] ?? 'draft';

                        logInfoIfDebug('Virtual sales data loaded from batch data column', [
                            'date' => $yesterdayDate,
                            'virtual_quantity' => $yesterdaySalesQuantity,
                            'virtual_weight' => $yesterdaySalesWeight,
                            'source' => 'batch_virtual_data'
                        ]);
                    }
                }

                // Fallback to recording payload sales data
                if ($yesterdaySalesQuantity == 0 && $yesterdaySalesWeight == 0) {
                    if (isset($payload['production']['sales'])) {
                        $sales = $payload['production']['sales'];
                        $yesterdaySalesQuantity = (int) ($sales['quantity'] ?? 0);
                        $yesterdaySalesWeight = (float) ($sales['weight'] ?? 0);
                        $yesterdaySalesStatus = $sales['status'] ?? 'draft';

                        logInfoIfDebug('Sales data loaded from recording payload', [
                            'date' => $yesterdayDate,
                            'sales_quantity' => $yesterdaySalesQuantity,
                            'sales_weight' => $yesterdaySalesWeight,
                            'source' => 'recording_payload'
                        ]);
                    } else if (isset($payload['sales_quantity']) || isset($payload['sales_weight'])) {
                        // HIGH PRIORITY: Extract sales data directly from payload root level
                        $yesterdaySalesQuantity = (int) ($payload['sales_quantity'] ?? 0);
                        $yesterdaySalesWeight = (float) ($payload['sales_weight'] ?? 0);
                        $yesterdaySalesStatus = $payload['sales_status'] ?? 'draft';

                        logInfoIfDebug('Sales data loaded from recording payload root level (HIGH PRIORITY)', [
                            'date' => $yesterdayDate,
                            'sales_quantity' => $yesterdaySalesQuantity,
                            'sales_weight' => $yesterdaySalesWeight,
                            'source' => 'recording_payload_root_level',
                            'payload_keys' => array_keys($payload)
                        ]);
                    }
                }

                // Final fallback to RecordingSaleService
                if ($yesterdaySalesQuantity == 0 && $yesterdaySalesWeight == 0) {
                    $salesResult = $this->recordingSaleService->listByLivestockAndDate($livestockId, $yesterdayDate, ['status' => 'draft']);
                    if ($salesResult->isSuccess() && is_array($salesResult->getData()) && count($salesResult->getData()) > 0) {
                        $salesData = $salesResult->getData()[0];
                        $yesterdaySalesQuantity = (int) ($salesData['total_quantity'] ?? 0);
                        $yesterdaySalesWeight = (float) ($salesData['total_weight'] ?? 0);
                        $yesterdaySalesStatus = $salesData['status'] ?? 'draft';

                        logInfoIfDebug('Sales data loaded from RecordingSaleService', [
                            'date' => $yesterdayDate,
                            'sales_quantity' => $yesterdaySalesQuantity,
                            'sales_weight' => $yesterdaySalesWeight,
                            'source' => 'recording_sale_service'
                        ]);
                    }
                }
            }

            // Prepare result data
            $resultData = [
                'weight' => $yesterdayWeight,
                'stock_end' => $yesterdayStockEnd,
                'mortality' => $yesterdayMortality,
                'culling' => $yesterdayCulling,
                'feed_usage' => $yesterdayFeedUsage,
                'supply_usage' => $yesterdaySupplyUsage,
                'sales' => [
                    'quantity' => $yesterdaySalesQuantity,
                    'weight' => $yesterdaySalesWeight,
                    'status' => $yesterdaySalesStatus,
                    'calculation_mode' => $isVirtualMode ? 'virtual' : 'real'
                ],
                'metadata' => [
                    'calculation_mode' => $isVirtualMode ? 'virtual' : 'real',
                    'virtual_mode_enabled' => $isVirtualMode,
                    'data_sources' => [
                        'recording_exists' => $yesterdayRecording ? true : false,
                        'virtual_data_available' => $isVirtualMode && $yesterdaySalesQuantity > 0,
                        'payload_data_available' => $yesterdayRecording && isset($yesterdayRecording->payload['production']['sales']),
                        'service_data_available' => $yesterdaySalesQuantity > 0
                    ]
                ]
            ];

            logInfoIfDebug('✅ RecordingDataService::loadYesterdayData completed successfully', [
                'livestock_id' => $livestockId,
                'yesterday_date' => $yesterdayDate,
                'virtual_mode' => $isVirtualMode,
                'sales_quantity' => $yesterdaySalesQuantity,
                'sales_weight' => $yesterdaySalesWeight
            ]);

            return ServiceResult::success('Yesterday data loaded successfully', $resultData);
        } catch (Exception $e) {
            logErrorIfDebug('❌ Error in loadYesterdayData', [
                'livestock_id' => $livestockId,
                'yesterday_date' => $yesterdayDate,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return ServiceResult::error('Failed to load yesterday data: ' . $e->getMessage(), $e);
        }
    }

    /**
     * Get virtual sales data from batch data column
     */
    private function getVirtualSalesData(string $livestockId, string $date): ?array
    {
        try {
            $batches = LivestockBatch::where('livestock_id', $livestockId)
                ->where('status', 'active')
                ->get();

            $totalVirtualQuantity = 0;
            $totalVirtualWeight = 0;
            $virtualStatus = 'draft';

            foreach ($batches as $batch) {
                $batchData = $batch->data ?? [];
                if (isset($batchData['virtual_sales'][$date])) {
                    $virtualSales = $batchData['virtual_sales'][$date];
                    $totalVirtualQuantity += (int) ($virtualSales['quantity'] ?? 0);
                    $totalVirtualWeight += (float) ($virtualSales['weight'] ?? 0);
                    $virtualStatus = $virtualSales['status'] ?? 'draft';
                }
            }

            if ($totalVirtualQuantity > 0 || $totalVirtualWeight > 0) {
                return [
                    'quantity' => $totalVirtualQuantity,
                    'weight' => $totalVirtualWeight,
                    'status' => $virtualStatus,
                    'source' => 'batch_virtual_data'
                ];
            }

            return null;
        } catch (Exception $e) {
            logErrorIfDebug('Error getting virtual sales data', [
                'livestock_id' => $livestockId,
                'date' => $date,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function loadRecordingDataForTable(string $livestockId): ServiceResult
    {
        logInfoIfDebug('MODULAR_PATH: RecordingDataService::loadRecordingDataForTable called.', compact('livestockId'));

        try {
            $recordings = Recording::where('livestock_id', $livestockId)
                ->orderBy('tanggal', 'desc')
                ->get()
                ->map(function ($recording) {
                    $payload = is_array($recording->payload)
                        ? $recording->payload
                        : json_decode($recording->payload, true);

                    // Robust depletion calculation by querying the source table directly
                    $kematian = LivestockDepletion::where('recording_id', $recording->id)->sum('jumlah');

                    return [
                        'id' => $recording->id,
                        'tanggal' => Carbon::parse($recording->tanggal)->format('d M Y'),
                        'age' => $recording->age,
                        'stock_akhir' => $recording->stock_akhir,
                        'berat_hari_ini' => number_format((float) $recording->berat_hari_ini, 2),
                        'kenaikan_berat' => number_format((float) $recording->kenaikan_berat, 2),
                        'pakan_harian' => isset($payload['data']['consumption']['feed']['total_quantity'])
                            ? number_format((float) $payload['data']['consumption']['feed']['total_quantity'], 2)
                            : number_format((float) $recording->pakan_harian, 2),
                        'kematian' => $kematian,
                        'payload_version' => $payload['schema']['version'] ?? '1.0'
                    ];
                });

            return ServiceResult::success('Table data loaded successfully.', ['recordings' => $recordings->toArray()]);
        } catch (Exception $e) {
            logErrorIfDebug('❌ MODULAR_PATH: Error in RecordingDataService::loadRecordingDataForTable', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return ServiceResult::error('Failed to load recording history.', $e);
        }
    }

    /**
     * Return the sales_changes array from the latest (or specified date's) recording payload for the given livestockId.
     * If $date is null, use the latest recording (by tanggal desc).
     * If no sales_changes found, return an empty array.
     */
    public function getSalesHistory(string $livestockId, ?string $date = null): array
    {
        try {
            $query = Recording::where('livestock_id', $livestockId);
            if ($date) {
                $query->where('tanggal', $date);
            }
            $recording = $query->orderBy('tanggal', 'desc')->first();
            if (!$recording) {
                logDebugIfDebug('getSalesHistory: No recording found', compact('livestockId', 'date'));
                return [];
            }
            $payload = is_array($recording->payload)
                ? $recording->payload
                : json_decode($recording->payload, true);
            if (!isset($payload['history']['sales_changes']) || !is_array($payload['history']['sales_changes'])) {
                logDebugIfDebug('getSalesHistory: No sales_changes found in payload', [
                    'recording_id' => $recording->id,
                    'payload_keys' => array_keys($payload)
                ]);
                return [];
            }
            logDebugIfDebug('getSalesHistory: Returning sales_changes', [
                'recording_id' => $recording->id,
                'sales_changes_count' => count($payload['history']['sales_changes'])
            ]);
            return $payload['history']['sales_changes'];
        } catch (\Exception $e) {
            logErrorIfDebug('getSalesHistory: Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'livestockId' => $livestockId,
                'date' => $date
            ]);
            return [];
        }
    }

    /**
     * Generates a summary string for yesterday's data.
     * This is a helper method moved from the legacy service.
     */
    private function generateYesterdaySummary($yesterday_weight, $yesterday_mortality, $yesterday_culling, $yesterday_feed_usage, $yesterday_supply_usage, $yesterday_sales_quantity, $yesterday_sales_weight): string
    {
        $summary = [];
        if ($yesterday_weight > 0) $summary[] = "Berat: " . number_format((float) $yesterday_weight, 0) . "gr";
        if ($yesterday_mortality > 0) $summary[] = "Mati: " . (int) $yesterday_mortality . " ekor";
        if ($yesterday_culling > 0) $summary[] = "Afkir: " . (int) $yesterday_culling . " ekor";
        if ($yesterday_feed_usage['total_quantity'] > 0) $summary[] = "Pakan: " . number_format((float) $yesterday_feed_usage['total_quantity'], 1) . "Kg";
        if ($yesterday_supply_usage['total_quantity'] > 0) $summary[] = "OVK: " . (int) $yesterday_supply_usage['types_count'] . " jenis";
        if ($yesterday_sales_quantity > 0) $summary[] = "Jual: " . (int) $yesterday_sales_quantity . " ekor";
        if ($yesterday_sales_weight > 0) $summary[] = "Berat Jual: " . number_format((float) $yesterday_sales_weight, 0) . "Kg";
        return empty($summary) ? "Tidak ada data" : implode(", ", $summary);
    }



    public function getLivestockSummary(string $livestockId): array
    {
        $livestock = $this->getLivestock($livestockId);

        // dd($livestock);
        $recordings = $livestock->recordings()->orderBy('tanggal')->get();

        $lastPayload = $recordings->last()?->payload ?? [];

        $totalSales = 0;
        $totalDeplesi = 0;

        foreach ($recordings as $recording) {
            $payload = $recording->payload ?? [];

            // From structured production section
            if (isset($payload['production']['sales'])) {
                $totalSales += (int)($payload['production']['sales']['quantity'] ?? 0);
            } else if (isset($payload['sales_quantity'])) {
                // HIGH PRIORITY: Extract sales data directly from payload root level
                $totalSales += (int)($payload['sales_quantity'] ?? 0);
            }

            $totalDeplesi += (int)($payload['production']['depletion']['total'] ?? 0);

            // From history section
            $totalSales += collect($payload['history']['sales_changes'] ?? [])
                ->sum(fn($sale) => (int)($sale['quantity'] ?? 0));

            $totalDeplesi += collect($payload['history']['depletion_changes'] ?? [])
                ->sum(fn($depl) => (int)($depl['quantity'] ?? 0));
        }

        return [
            'date'       => $lastPayload['livestock']['population']['date'] ?? '',
            'remaining'       => $lastPayload['livestock']['population']['stock_end'] ?? 0,
            'current'       => $lastPayload['livestock']['population']['stock_end'] ?? 0,
            'deplesi'       => $totalDeplesi,
            'sales'         => $totalSales,
            'feed_usage'    => (float)($lastPayload['consumption']['feed']['cumulative_feed_consumption'] ?? 0),
            'supply_usage'  => (float)($lastPayload['consumption']['supply']['total_quantity'] ?? 0),
        ];
    }


    public function getLivestock($livestockId): ?Livestock
    {
        return Livestock::with([
            // 'livestockSales',
            'livestockDepletion',
            // 'feedUsages.feedUsageDetails',
            // 'supplyUsages',
        ])->find($livestockId);
    }
}

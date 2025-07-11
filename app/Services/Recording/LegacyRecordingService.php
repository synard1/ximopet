<?php

namespace App\Services\Recording;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Exception;

// Models
use App\Models\Recording;
use App\Models\CurrentLivestock;
use App\Models\Feed;
use App\Models\LivestockDepletion;
use App\Models\FeedUsage;
use App\Models\FeedUsageDetail;
use App\Models\Livestock;
use App\Models\Supply;
use App\Models\SupplyUsage;
use App\Models\SupplyUsageDetail;
use App\Models\SupplyStock;
use App\Models\FeedStock;
use App\Models\Unit;

// Config
use App\Config\LivestockDepletionConfig;

// Services
use App\Services\FeedUsageService;
use App\Services\Livestock\LivestockCostService;

/**
 * Service to encapsulate legacy recording logic from Records.php
 * This service acts as an intermediate step in the refactoring process,
 * allowing the legacy code to be isolated before being fully replaced.
 * 
 * @version 1.0
 * @since 2025-07-09
 */
class LegacyRecordingService
{
    // TODO: Inject dependencies instead of using app()
    // private LivestockCostService $livestockCostService;

    // public function __construct(LivestockCostService $livestockCostService)
    // {
    //     $this->livestockCostService = $livestockCostService;
    // }

    public function handleSave(array $componentData)
    {
        // Extract component data into local variables for easier access
        extract($componentData);

        Log::info('🔄 LegacyRecordingService: handleSave started.', [
            'livestock_id' => $livestockId,
            'date' => $date,
        ]);

        try {
            DB::beginTransaction();

            // --- Prepare feed usage data ---
            $usages = $this->prepareFeedUsageData($itemQuantities ?? [], $livestockId ?? '');
            if (!is_array($usages)) $usages = [];

            // --- Prepare supply usage data ---
            $supplyUsages = $this->prepareSupplyUsageData($supplyQuantities ?? [], $livestockId ?? '');
            if (!is_array($supplyUsages)) $supplyUsages = [];

            // Validate livestock and data structure
            $ternak = CurrentLivestock::with(['livestock.coop', 'livestock.farm'])->where('livestock_id', $livestockId)->first();
            if (!$ternak || !$ternak->livestock) {
                throw new Exception("Livestock record not found or invalid");
            }

            $livestockStartDate = Carbon::parse($ternak->livestock->start_date);
            $recordDate = Carbon::parse($date);
            if ($recordDate->lt($livestockStartDate)) {
                throw new Exception("Recording date cannot be earlier than livestock start date ({$livestockStartDate->format('Y-m-d')})");
            }

            // Get population history
            $recordingService = app(RecordingService::class);
            $populationHistory = $recordingService->getPopulationHistory($livestockId, $recordDate);

            // Calculate age and stock values
            $age = $livestockStartDate->diffInDays($recordDate);
            $previousRecording = Recording::where('livestock_id', $livestockId)->whereDate('tanggal', $recordDate->copy()->subDay())->first();
            $stockAwalHariIni = $previousRecording ? $previousRecording->stock_akhir : $ternak->livestock->initial_quantity;
            $totalDeplesiHariIni = (int)($mortality ?? 0) + (int)($culling ?? 0) + (int)($sales_quantity ?? 0);
            $stockAkhirHariIni = $stockAwalHariIni - $totalDeplesiHariIni;

            // Build payload
            $weightHistory = $this->getWeightHistory($livestockId, $recordDate);
            $feedHistory = $this->getFeedConsumptionHistory($livestockId, $recordDate);
            $outflowHistory = $this->getDetailedOutflowHistory($livestockId, $date);
            $performanceMetrics = $this->calculatePerformanceMetrics($age, $stockAkhirHariIni, $ternak->livestock->initial_quantity, $weight_today, $feedHistory['cumulative_feed_consumption'], $outflowHistory['total']);

            $detailedPayload = $this->buildStructuredPayload(
                $ternak,
                $age,
                $stockAwalHariIni,
                $stockAkhirHariIni,
                (float)($weight_today ?? 0),
                (float)($weight_yesterday ?? 0),
                (float)($weight_gain ?? 0),
                $performanceMetrics,
                $weightHistory,
                $feedHistory,
                $populationHistory,
                $outflowHistory,
                $usages,
                $supplyUsages,
                (int)($mortality ?? 0),
                (int)($culling ?? 0),
                (int)($sales_quantity ?? 0),
                (float)($sales_weight ?? 0),
                (float)($sales_price ?? 0),
                (float)($total_sales ?? 0),
                (bool)($isManualDepletionEnabled ?? false),
                (bool)($isManualFeedUsageEnabled ?? false),
                $recordingMethod,
                $livestockConfig,
                $date
            );

            // Save recording data
            $recordingInput = [
                'livestock_id' => $livestockId,
                'tanggal' => $date,
                'age' => $age,
                'stock_awal' => $stockAwalHariIni,
                'stock_akhir' => $stockAkhirHariIni,
                'berat_hari_ini' => $weight_today,
                'berat_semalam' => $weight_yesterday,
                'kenaikan_berat' => $weight_gain,
                'pakan_jenis' => is_array($usages) ? implode(', ', array_column($usages, 'feed_name')) : '',
                'pakan_harian' => is_array($usages) ? array_sum(array_column($usages, 'quantity')) : 0,
                'feed_id' => is_array($usages) ? implode(', ', array_column($usages, 'feed_id')) : '',
                'payload' => $detailedPayload,
            ];
            $recording = $this->saveOrUpdateRecording($recordingInput);

            // Process feed usage
            if (!empty($usages)) {
                $this->saveFeedUsageWithTrackingFallback($usages, $recording->id, $date, $livestockId, $feedUsageId ?? null);
            }

            // Process supply usage
            if (!empty($supplyUsages)) {
                $this->saveSupplyUsageWithTracking($supplyUsages, $recording->id, $date, $livestockId, $supplyUsageId ?? null);
            }

            // Process depletions
            $depletionsToProcess = [
                LivestockDepletionConfig::TYPE_MORTALITY => $mortality,
                LivestockDepletionConfig::TYPE_CULLING => $culling,
            ];

            foreach ($depletionsToProcess as $depletionType => $quantity) {
                if ($quantity > 0) {
                    $this->storeDeplesiWithDetailsFallback($depletionType, (int) $quantity, $recording->id, $date, $livestockId);
                }
            }

            // Calculate costs
            $costService = app(LivestockCostService::class);
            $costService->calculateForDate($livestockId, $date);

            DB::commit();

            Log::info('🎉 LegacyRecordingService: handleSave completed successfully');
            return ['success' => true, 'message' => 'Data berhasil disimpan (Legacy).'];
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("❌ LegacyRecordingService: Error in handleSave", [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return ['success' => false, 'message' => 'Terjadi kesalahan saat menyimpan data (Legacy): ' . $e->getMessage()];
        }
    }

    private function prepareFeedUsageData(array $itemQuantities, string $livestockId)
    {
        return collect($itemQuantities)
            ->filter(fn($qty) => $qty > 0)
            ->map(function ($qty, $itemId) use ($livestockId) {
                $feed = Feed::with('unit')->find($itemId);
                $unitInfo = $this->getDetailedUnitInfo($feed, $qty);
                $stockInfo = $this->getStockDetails($itemId, $livestockId);
                return [
                    'feed_id' => $itemId,
                    'quantity' => (float) $qty,
                    'feed_name' => $feed ? $feed->name : 'Unknown Feed',
                    'feed_code' => $feed ? $feed->code : 'Unknown Code',
                    'unit_id' => $unitInfo['smallest_unit_id'],
                    'unit_name' => $unitInfo['smallest_unit_name'],
                    'original_unit_id' => $unitInfo['original_unit_id'],
                    'original_unit_name' => $unitInfo['original_unit_name'],
                    'consumption_unit_id' => $unitInfo['consumption_unit_id'],
                    'consumption_unit_name' => $unitInfo['consumption_unit_name'],
                    'conversion_factor' => $unitInfo['conversion_factor'],
                    'converted_quantity' => $unitInfo['converted_quantity'],
                    'available_stocks' => $stockInfo['available_stocks'],
                    'stock_origins' => $stockInfo['stock_origins'],
                    'stock_purchase_dates' => $stockInfo['stock_purchase_dates'],
                    'stock_prices' => $stockInfo['stock_prices'],
                    'category' => $feed ? ($feed->category->name ?? 'Uncategorized') : 'Unknown',
                    'timestamp' => now()->toIso8601String(),
                ];
            })
            ->values()
            ->toArray();
    }

    private function prepareSupplyUsageData(array $supplyQuantities, string $livestockId)
    {
        return collect($supplyQuantities)
            ->map(function ($quantity, $supplyId) use ($livestockId) {
                if (empty($quantity) || $quantity <= 0) return null;
                $supply = Supply::with('unit')->find($supplyId);
                if (!$supply) return null;
                $unitInfo = $this->getDetailedSupplyUnitInfo($supply, floatval($quantity));
                $stockInfo = $this->getSupplyStockDetails($supplyId, $livestockId);
                return [
                    'supply_id' => $supplyId,
                    'quantity' => (float) $quantity,
                    'supply_name' => $supply->name,
                    'supply_code' => $supply->code,
                    'notes' => '',
                    'unit_id' => $unitInfo['smallest_unit_id'],
                    'unit_name' => $unitInfo['smallest_unit_name'],
                    'original_unit_id' => $unitInfo['original_unit_id'],
                    'original_unit_name' => $unitInfo['original_unit_name'],
                    'consumption_unit_id' => $unitInfo['consumption_unit_id'],
                    'consumption_unit_name' => $unitInfo['consumption_unit_name'],
                    'conversion_factor' => $unitInfo['conversion_factor'],
                    'converted_quantity' => $unitInfo['converted_quantity'],
                    'available_stocks' => $stockInfo['available_stocks'],
                    'stock_origins' => $stockInfo['stock_origins'],
                    'stock_purchase_dates' => $stockInfo['stock_purchase_dates'],
                    'stock_prices' => $stockInfo['stock_prices'],
                    'category' => $supply->supplyCategory->name ?? 'Uncategorized',
                    'timestamp' => now()->toIso8601String(),
                ];
            })
            ->filter()
            ->values()
            ->toArray();
    }

    private function saveFeedUsageWithTrackingFallback(array $newUsages, string $recordingId, string $date, string $livestockId, ?string $feedUsageId)
    {
        if ($feedUsageId) {
            $usage = FeedUsage::findOrFail($feedUsageId);
            if ($this->hasUsageChanged($usage, $newUsages)) {
                $this->updateFeedUsageWithTrackingFallback($newUsages, $feedUsageId, $recordingId);
            }
        } else {
            $usage = FeedUsage::create([
                'usage_date' => $date,
                'livestock_id' => $livestockId,
                'recording_id' => $recordingId,
                'total_quantity' => array_sum(array_column($newUsages, 'quantity')),
                'metadata' => ['created_at' => now()->toIso8601String(), 'created_by' => Auth::id()],
                'created_by' => Auth::id(),
            ]);
            app(FeedUsageService::class)->processWithMetadata($usage, $newUsages);
            Log::info("✅ New feed usage created with ID {$usage->id}");
        }
    }

    private function updateFeedUsageWithTrackingFallback(array $newUsages, string $feedUsageId, string $recordingId): void
    {
        $usage = FeedUsage::findOrFail($feedUsageId);
        $oldDetails = $usage->details;
        foreach ($oldDetails as $detail) {
            $stock = FeedStock::find($detail->feed_stock_id);
            if ($stock) {
                $stock->quantity_used = max(0, $stock->quantity_used - $detail->quantity_taken);
                $stock->save();
            }
            $detail->delete();
        }

        $usage->update([
            'recording_id' => $recordingId,
            'total_quantity' => array_sum(array_column($newUsages, 'quantity')),
            'metadata' => array_merge($usage->metadata ?? [], ['updated_at' => now()->toIso8601String(), 'updated_by' => Auth::id()]),
            'updated_by' => Auth::id(),
        ]);

        app(FeedUsageService::class)->processWithMetadata($usage, $newUsages);
        Log::info("✅ Feed usage update complete for usage ID {$usage->id}");
    }

    private function hasUsageChanged(FeedUsage $usage, array $newUsages): bool
    {
        $existingDetails = $usage->details()
            ->select('feed_id', DB::raw('SUM(quantity_taken) as total'))
            ->groupBy('feed_id')->get()->keyBy('feed_id');
        foreach ($newUsages as $row) {
            $feedId = $row['feed_id'];
            $qty = (float) $row['quantity'];
            if (!isset($existingDetails[$feedId]) || (float) $existingDetails[$feedId]->total !== $qty) return true;
        }
        if (count($existingDetails) !== count($newUsages)) return true;
        return false;
    }

    private function saveSupplyUsageWithTracking(array $supplyUsages, string $recordingId, string $date, string $livestockId, ?string $supplyUsageId)
    {
        if ($supplyUsageId) {
            $usage = SupplyUsage::findOrFail($supplyUsageId);
            if ($this->hasSupplyUsageChanged($usage, $supplyUsages)) {
                $this->updateSupplyUsageWithTracking($supplyUsages, $supplyUsageId, $recordingId);
            }
        } else {
            $livestock = Livestock::find($livestockId);
            $earliestStockDate = SupplyStock::where('farm_id', $livestock->farm_id)->min('date');
            if ($earliestStockDate && $date < $earliestStockDate) {
                throw new Exception("Supply usage date must be after the earliest supply stock entry date ({$earliestStockDate})");
            }
            $usage = SupplyUsage::create([
                'usage_date' => $date,
                'livestock_id' => $livestockId,
                'total_quantity' => array_sum(array_column($supplyUsages, 'quantity')),
                'created_by' => Auth::id(),
            ]);
        }
        foreach ($supplyUsages as $usageData) {
            $this->processSupplyUsageDetail($usage, $usageData, $livestockId);
        }
    }

    private function updateSupplyUsageWithTracking(array $newUsages, string $supplyUsageId, string $recordingId): void
    {
        $usage = SupplyUsage::findOrFail($supplyUsageId);
        $oldDetails = $usage->details;
        foreach ($oldDetails as $detail) {
            $stock = SupplyStock::find($detail->supply_stock_id);
            if ($stock) {
                $stock->quantity_used = max(0, $stock->quantity_used - $detail->quantity_taken);
                $stock->save();
            }
            $detail->delete();
        }

        $usage->update([
            'recording_id' => $recordingId,
            'total_quantity' => array_sum(array_column($newUsages, 'quantity')),
            'updated_by' => Auth::id(),
        ]);

        foreach ($newUsages as $usageData) {
            $this->processSupplyUsageDetail($usage, $usageData, $usage->livestock_id);
        }
        Log::info("✅ Supply usage update complete for usage ID {$usage->id}");
    }

    private function hasSupplyUsageChanged(SupplyUsage $usage, array $newSupplyUsages): bool
    {
        $existingDetails = $usage->details()
            ->select('supply_id', DB::raw('SUM(quantity_taken) as total'))
            ->groupBy('supply_id')->get()->keyBy('supply_id');
        foreach ($newSupplyUsages as $row) {
            $supplyId = $row['supply_id'];
            $qty = (float) $row['quantity'];
            if (!isset($existingDetails[$supplyId]) || (float) $existingDetails[$supplyId]->total !== $qty) return true;
        }
        if (count($existingDetails) !== count($newSupplyUsages)) return true;
        return false;
    }

    private function processSupplyUsageDetail($usage, $usageData, $livestockId)
    {
        $livestock = Livestock::find($livestockId);
        $quantityNeeded = $usageData['quantity'];
        $availableStocks = SupplyStock::where('farm_id', $livestock->farm_id)
            ->where('supply_id', $usageData['supply_id'])
            ->whereRaw('(quantity_in - quantity_used - quantity_mutated) > 0')
            ->orderBy('date')->orderBy('created_at')->get();

        foreach ($availableStocks as $stock) {
            if ($quantityNeeded <= 0) break;
            $availableInStock = $stock->quantity_in - $stock->quantity_used - $stock->quantity_mutated;
            $quantityToTake = min($quantityNeeded, $availableInStock);

            if ($quantityToTake > 0) {
                SupplyUsageDetail::create([
                    'supply_usage_id' => $usage->id,
                    'supply_id' => $usageData['supply_id'],
                    'supply_stock_id' => $stock->id,
                    'quantity_taken' => $quantityToTake,
                    'created_by' => Auth::id(),
                ]);
                $stock->quantity_used += $quantityToTake;
                $stock->save();
                $quantityNeeded -= $quantityToTake;
            }
        }
    }

    private function storeDeplesiWithDetailsFallback($jenis, $jumlah, $recordingId, $date, $livestockId)
    {
        $normalizedType = LivestockDepletionConfig::normalize($jenis);
        $livestock = Livestock::find($livestockId);
        $age = $livestock ? Carbon::parse($date)->diffInDays(Carbon::parse($livestock->start_date)) : null;

        $oldJumlah = LivestockDepletion::where('livestock_id', $livestockId)
            ->where('tanggal', $date)->where('jenis', $normalizedType)->sum('jumlah');
        $delta = $jumlah - $oldJumlah;

        $deplesi = LivestockDepletion::updateOrCreate(
            ['livestock_id' => $livestockId, 'tanggal' => $date, 'jenis' => $normalizedType],
            [
                'jumlah' => $jumlah,
                'recording_id' => $recordingId,
                'method' => 'traditional',
                'metadata' => [
                    'livestock_name' => $livestock->name ?? 'Unknown',
                    'age_days' => $age,
                    'updated_at' => now()->toIso8601String(),
                    'updated_by' => Auth::id(),
                    'depletion_method' => 'traditional',
                    'delta_calculation' => ['old_value' => $oldJumlah, 'new_value' => $jumlah, 'delta' => $delta],
                    'depletion_config' => ['original_type' => $jenis, 'normalized_type' => $normalizedType]
                ],
                'data' => ['delta_info' => ['old_value' => $oldJumlah, 'new_value' => $jumlah, 'delta' => $delta]],
                'created_by' => Auth::id(),
                'updated_by' => Auth::id()
            ]
        );
        return $deplesi;
    }

    // ... All other helper methods like getDetailedUnitInfo, getStockDetails, etc. go here
    // Making them public to be callable from the service context.

    public function getDetailedOutflowHistory($livestockId, $date)
    {
        $recordings = Recording::where('livestock_id', $livestockId)->where('tanggal', '!=', $date)->get();
        $totalMortality = 0;
        $totalCulling = 0;
        $totalSales = 0;
        foreach ($recordings as $recording) {
            $payload = $recording->payload ?? [];
            $totalMortality += $payload['mortality'] ?? 0;
            $totalCulling += $payload['culling'] ?? 0;
            $totalSales += $payload['sales_quantity'] ?? 0;
        }
        return [
            'mortality' => $totalMortality,
            'culling' => $totalCulling,
            'sales' => $totalSales,
            'total' => $totalMortality + $totalCulling + $totalSales,
        ];
    }

    public function getWeightHistory($livestockId, $currentDate)
    {
        $recordings = Recording::where('livestock_id', $livestockId)
            ->where('tanggal', '<', $currentDate->format('Y-m-d'))
            ->whereNotNull('berat_hari_ini')->orderBy('tanggal')->get();
        $lastWeight = 0;
        if ($recordings->isNotEmpty()) {
            $lastWeight = $recordings->last()->berat_hari_ini;
        }
        return ['latest_weight' => $lastWeight];
    }

    public function getFeedConsumptionHistory($livestockId, $currentDate)
    {
        $totalConsumption = FeedUsage::where('livestock_id', $livestockId)
            ->where('usage_date', '<', $currentDate->format('Y-m-d'))
            ->sum('total_quantity');
        return ['cumulative_feed_consumption' => $totalConsumption];
    }

    public function calculatePerformanceMetrics($age, $currentPopulation, $initialPopulation, $currentWeight, $totalFeedConsumption, $totalDepleted)
    {
        $liveability = $initialPopulation > 0 ? ($currentPopulation / $initialPopulation) * 100 : 0;
        $fcr = 0;
        if ($currentWeight > 0 && $currentPopulation > 0) {
            $totalWeight = $currentWeight * $currentPopulation;
            $fcr = $totalFeedConsumption > 0 ? $totalFeedConsumption / $totalWeight : 0;
        }
        $ip = 0;
        if ($age > 0 && $fcr > 0) {
            $ip = ($liveability * $currentWeight * 100) / ($age * $fcr);
        }
        return ['liveability' => round($liveability, 2), 'fcr' => round($fcr, 3), 'ip' => round($ip, 2)];
    }

    public function saveOrUpdateRecording($data)
    {
        $livestock = Livestock::find($data['livestock_id']);
        if (!$livestock) throw new Exception("Livestock not found");

        $enhancedMetadata = [
            'version' => '2.0',
            'recorded_at' => now()->toIso8601String(),
            'recorded_by' => ['id' => Auth::id(), 'name' => Auth::user()->name ?? 'Unknown'],
        ];
        $fullPayload = array_merge($data['payload'] ?? [], $enhancedMetadata);

        $recording = Recording::updateOrCreate(
            ['livestock_id' => $data['livestock_id'], 'tanggal' => $data['tanggal']],
            array_merge($data, ['payload' => $fullPayload, 'created_by' => Auth::id(), 'updated_by' => Auth::id()])
        );
        return $recording;
    }

    /**
     * Build structured payload with organized sections for future-proof data storage
     * (Ekstrak dari Records.php, parameterisasi semua property yang sebelumnya $this->)
     */
    public function buildStructuredPayload(
        $ternak,
        int $age,
        int $stockAwal,
        int $stockAkhir,
        float $weightToday,
        float $weightYesterday,
        float $weightGain,
        array $performanceMetrics,
        array $weightHistory,
        array $feedHistory,
        array $populationHistory,
        array $outflowHistory,
        array $usages,
        array $supplyUsages,
        int $mortality = 0,
        int $culling = 0,
        int $sales_quantity = 0,
        float $sales_weight = 0,
        float $sales_price = 0,
        float $total_sales = 0,
        bool $isManualDepletionEnabled = false,
        bool $isManualFeedUsageEnabled = false,
        $recordingMethod = 'total',
        $livestockConfig = [],
        $date = null
    ): array {
        $totalFeedUsage = array_sum(array_column($usages, 'quantity'));
        $feedCost = array_sum(array_map(function ($usage) {
            $qty = $usage['quantity'] ?? 0;
            $price = $usage['stock_prices']['average_price'] ?? 0;
            return $qty * $price;
        }, $usages));
        $totalSupplyUsage = array_sum(array_column($supplyUsages, 'quantity'));
        $supplyCost = array_sum(array_map(function ($usage) {
            $qty = $usage['quantity'] ?? 0;
            $price = $usage['stock_prices']['average_price'] ?? 0;
            return $qty * $price;
        }, $supplyUsages));
        return [
            'schema' => [
                'version' => '3.0',
                'schema_date' => '2025-01-23',
                'compatibility' => ['2.0', '3.0'],
                'structure' => 'hierarchical_organized'
            ],
            'recording' => [
                'timestamp' => now()->toIso8601String(),
                'date' => $date,
                'age_days' => $age,
                'user' => [
                    'id' => Auth::id(),
                    'name' => Auth::user()->name ?? 'Unknown User',
                    'role' => Auth::user()->roles->first()->name ?? 'Unknown Role',
                    'company_id' => Auth::user()->company_id ?? null,
                ],
                'source' => [
                    'application' => 'livewire_records',
                    'component' => 'Records',
                    'method' => 'save',
                    'version' => '3.0'
                ]
            ],
            'livestock' => [
                'basic_info' => [
                    'id' => $ternak->livestock->id,
                    'name' => $ternak->livestock->name,
                    'strain' => $ternak->livestock->strain ?? 'Unknown Strain',
                    'start_date' => $ternak->livestock->start_date,
                    'age_days' => $age
                ],
                'location' => [
                    'farm_id' => $ternak->livestock->farm_id,
                    'farm_name' => $ternak->livestock->farm->name ?? 'Unknown Farm',
                    'coop_id' => $ternak->livestock->coop_id,
                    'coop_name' => $ternak->livestock->coop->name ?? 'Unknown Coop'
                ],
                'population' => [
                    'initial' => $ternak->livestock->initial_quantity,
                    'stock_start' => $stockAwal,
                    'stock_end' => $stockAkhir,
                    'change' => $stockAkhir - $stockAwal
                ]
            ],
            'production' => [
                'weight' => [
                    'yesterday' => $weightYesterday,
                    'today' => $weightToday,
                    'gain' => $weightGain,
                    'unit' => 'grams'
                ],
                'depletion' => [
                    'mortality' => (int)($mortality ?? 0),
                    'culling' => (int)($culling ?? 0),
                    'total' => (int)($mortality ?? 0) + (int)($culling ?? 0)
                ],
                'sales' => [
                    'quantity' => (int)($sales_quantity ?? 0),
                    'weight' => (float)($sales_weight ?? 0),
                    'price_per_unit' => (float)($sales_price ?? 0),
                    'total_value' => (float)($total_sales ?? 0),
                    'average_weight' => $sales_quantity > 0 ? $sales_weight / $sales_quantity : 0
                ]
            ],
            'consumption' => [
                'feed' => [
                    'total_quantity' => $totalFeedUsage,
                    'total_cost' => $feedCost,
                    'items' => $usages,
                    'types_count' => count($usages),
                    'cost_per_kg' => $totalFeedUsage > 0 ? $feedCost / $totalFeedUsage : 0
                ],
                'supply' => [
                    'total_quantity' => $totalSupplyUsage,
                    'total_cost' => $supplyCost,
                    'items' => $supplyUsages,
                    'types_count' => count($supplyUsages),
                    'cost_per_unit' => $totalSupplyUsage > 0 ? $supplyCost / $totalSupplyUsage : 0
                ]
            ],
            'performance' => array_merge($performanceMetrics, [
                'calculated_at' => now()->toIso8601String(),
                'calculation_method' => 'standard_poultry_metrics'
            ]),
            'history' => [
                'weight' => $weightHistory,
                'feed' => $feedHistory,
                'population' => $populationHistory,
                'outflow' => $outflowHistory
            ],
            'environment' => [
                'climate' => [
                    'temperature' => null,
                    'humidity' => null,
                    'pressure' => null
                ],
                'housing' => [
                    'lighting' => null,
                    'ventilation' => null,
                    'density' => null
                ],
                'water' => [
                    'consumption' => null,
                    'quality' => null,
                    'temperature' => null
                ]
            ],
            'config' => [
                'manual_depletion_enabled' => $isManualDepletionEnabled,
                'manual_feed_usage_enabled' => $isManualFeedUsageEnabled,
                'recording_method' => $recordingMethod ?? 'total',
                'livestock_config' => $livestockConfig
            ],
            'validation' => [
                'data_quality' => [
                    'weight_logical' => $weightToday >= 0 && $weightGain >= -100,
                    'population_logical' => $stockAkhir >= 0 && $stockAwal >= $stockAkhir,
                    'feed_consumption_logical' => $totalFeedUsage >= 0,
                    'depletion_logical' => ($mortality ?? 0) >= 0 && ($culling ?? 0) >= 0
                ],
                'completeness' => [
                    'has_weight_data' => $weightToday > 0,
                    'has_feed_data' => $totalFeedUsage > 0,
                    'has_depletion_data' => ($mortality ?? 0) > 0 || ($culling ?? 0) > 0,
                    'has_supply_data' => $totalSupplyUsage > 0
                ]
            ]
        ];
    }

    public function getDetailedUnitInfo($feed, $quantity)
    {
        $result = [
            'smallest_unit_id' => null,
            'smallest_unit_name' => 'Unknown',
            'original_unit_id' => null,
            'original_unit_name' => 'Unknown',
            'consumption_unit_id' => null,
            'consumption_unit_name' => 'Unknown',
            'conversion_factor' => 1,
            'converted_quantity' => $quantity,
        ];
        if (!$feed) return $result;
        if (isset($feed->payload['conversion_units']) && is_array($feed->payload['conversion_units'])) {
            $conversionUnits = collect($feed->payload['conversion_units']);
            $smallestUnit = $conversionUnits->firstWhere('is_smallest', true);
            if ($smallestUnit) {
                $result['smallest_unit_id'] = $smallestUnit['unit_id'];
                $unit = Unit::find($smallestUnit['unit_id']);
                $result['smallest_unit_name'] = $unit ? $unit->name : 'Unknown';
                $result['conversion_factor'] = floatval($smallestUnit['value'] ?? 1);
            }
            $originalUnit = $conversionUnits->firstWhere('is_default_purchase', true);
            if ($originalUnit) {
                $result['original_unit_id'] = $originalUnit['unit_id'];
                $unit = Unit::find($originalUnit['unit_id']);
                $result['original_unit_name'] = $unit ? $unit->name : 'Unknown';
            }
            $consumptionUnit = $conversionUnits->firstWhere('is_default_mutation', true) ?? $smallestUnit;
            if ($consumptionUnit) {
                $result['consumption_unit_id'] = $consumptionUnit['unit_id'];
                $unit = Unit::find($consumptionUnit['unit_id']);
                $result['consumption_unit_name'] = $unit ? $unit->name : 'Unknown';
                if ($smallestUnit && $consumptionUnit) {
                    $smallestValue = floatval($smallestUnit['value'] ?? 1);
                    $consumptionValue = floatval($consumptionUnit['value'] ?? 1);
                    if ($smallestValue > 0 && $consumptionValue > 0) {
                        $result['converted_quantity'] = ($quantity * $consumptionValue) / $smallestValue;
                    }
                }
            }
        } else if ($feed->unit) {
            $result['smallest_unit_id'] = $feed->unit->id;
            $result['smallest_unit_name'] = $feed->unit->name;
            $result['original_unit_id'] = $feed->unit->id;
            $result['original_unit_name'] = $feed->unit->name;
            $result['consumption_unit_id'] = $feed->unit->id;
            $result['consumption_unit_name'] = $feed->unit->name;
        }
        return $result;
    }

    public function getStockDetails($feedId, $livestockId)
    {
        $result = [
            'available_stocks' => [],
            'stock_origins' => [],
            'stock_purchase_dates' => [],
            'stock_prices' => [
                'min_price' => 0,
                'max_price' => 0,
                'average_price' => 0,
            ],
        ];
        $stocks = FeedStock::where('feed_id', $feedId)->where('livestock_id', $livestockId)
            ->whereRaw('(quantity_in - quantity_used - quantity_mutated) > 0')->get();
        if ($stocks->isEmpty()) return $result;

        $prices = [];
        $origins = [];
        $purchaseDates = [];
        foreach ($stocks as $stock) {
            if ($stock->feedPurchase) {
                $prices[] = $stock->feedPurchase->price_per_converted_unit ?? ($stock->feedPurchase->price_per_unit ?? 0);
            }
            // Dummy logic for origins and purchaseDates (implement as needed)
        }
        if (!empty($prices)) {
            $result['stock_prices'] = [
                'min_price' => min($prices),
                'max_price' => max($prices),
                'average_price' => array_sum($prices) / count($prices),
            ];
        }
        // Dummy: assign empty for now
        $result['stock_origins'] = $origins;
        $result['stock_purchase_dates'] = $purchaseDates;
        return $result;
    }

    public function getDetailedSupplyUnitInfo($supply, $quantity)
    {
        $result = [
            'smallest_unit_id' => null,
            'smallest_unit_name' => 'Unknown',
            'original_unit_id' => null,
            'original_unit_name' => 'Unknown',
            'consumption_unit_id' => null,
            'consumption_unit_name' => 'Unknown',
            'conversion_factor' => 1,
            'converted_quantity' => $quantity,
        ];
        if (!$supply) return $result;
        if (isset($supply->data['conversion_units']) && is_array($supply->data['conversion_units'])) {
            $conversionUnits = collect($supply->data['conversion_units']);
            $smallestUnit = $conversionUnits->firstWhere('is_smallest', true);
            if ($smallestUnit) {
                $result['smallest_unit_id'] = $smallestUnit['unit_id'];
                $unit = Unit::find($smallestUnit['unit_id']);
                $result['smallest_unit_name'] = $unit ? $unit->name : 'Unknown';
                $result['conversion_factor'] = floatval($smallestUnit['value'] ?? 1);
            }
            $originalUnit = $conversionUnits->firstWhere('is_default_purchase', true);
            if ($originalUnit) {
                $result['original_unit_id'] = $originalUnit['unit_id'];
                $unit = Unit::find($originalUnit['unit_id']);
                $result['original_unit_name'] = $unit ? $unit->name : 'Unknown';
            }
            $consumptionUnit = $conversionUnits->firstWhere('is_default_mutation', true) ?? $smallestUnit;
            if ($consumptionUnit) {
                $result['consumption_unit_id'] = $consumptionUnit['unit_id'];
                $unit = Unit::find($consumptionUnit['unit_id']);
                $result['consumption_unit_name'] = $unit ? $unit->name : 'Unknown';
                if ($smallestUnit && $consumptionUnit) {
                    $smallestValue = floatval($smallestUnit['value'] ?? 1);
                    $consumptionValue = floatval($consumptionUnit['value'] ?? 1);
                    if ($smallestValue > 0 && $consumptionValue > 0) {
                        $result['converted_quantity'] = ($quantity * $consumptionValue) / $smallestValue;
                    }
                }
            }
        } else if ($supply->unit) {
            $result['smallest_unit_id'] = $supply->unit->id;
            $result['smallest_unit_name'] = $supply->unit->name;
            $result['original_unit_id'] = $supply->unit->id;
            $result['original_unit_name'] = $supply->unit->name;
            $result['consumption_unit_id'] = $supply->unit->id;
            $result['consumption_unit_name'] = $supply->unit->name;
        }
        return $result;
    }

    public function getSupplyStockDetails($supplyId, $livestockId)
    {
        $result = [
            'available_stocks' => [],
            'stock_origins' => [],
            'stock_purchase_dates' => [],
            'stock_prices' => [
                'min_price' => 0,
                'max_price' => 0,
                'average_price' => 0,
            ],
        ];
        $livestock = Livestock::find($livestockId);
        if (!$livestock) return $result;

        $stocks = SupplyStock::where('supply_id', $supplyId)->where('farm_id', $livestock->farm_id)
            ->whereRaw('(quantity_in - quantity_used - quantity_mutated) > 0')->get();
        if ($stocks->isEmpty()) return $result;

        $prices = [];
        $origins = [];
        $purchaseDates = [];
        foreach ($stocks as $stock) {
            if ($stock->supplyPurchase) {
                $prices[] = $stock->supplyPurchase->price_per_converted_unit ?? ($stock->supplyPurchase->price_per_unit ?? 0);
            }
            // Dummy logic for origins and purchaseDates (implement as needed)
        }
        if (!empty($prices)) {
            $result['stock_prices'] = [
                'min_price' => min($prices),
                'max_price' => max($prices),
                'average_price' => array_sum($prices) / count($prices),
            ];
        }
        // Dummy: assign empty for now
        $result['stock_origins'] = $origins;
        $result['stock_purchase_dates'] = $purchaseDates;
        return $result;
    }


    /**
     * Load all recording related data for a specific date (legacy path).
     * This method mirrors logic from Records::updatedDateFallback but returns
     * structured data instead of directly mutating component state.
     */
    public function loadDateDataFallback(string $livestockId, string $date): array
    {
        try {
            // Get yesterday's date
            $yesterdayDate = Carbon::parse($date)->subDay()->format('Y-m-d');

            // Fetch all data in parallel
            $recordingData = Recording::where('livestock_id', $livestockId)->whereDate('tanggal', $date)->first();
            $feedUsage = FeedUsage::where('livestock_id', $livestockId)->whereDate('usage_date', $date)->first();
            $supplyUsage = SupplyUsage::where('livestock_id', $livestockId)->whereDate('usage_date', $date)->first();
            $deplesiData = LivestockDepletion::where('livestock_id', $livestockId)->whereDate('tanggal', $date)->get();
            $yesterdayData = $this->loadYesterdayDataFallback($livestockId, $yesterdayDate);

            // Process data
            $itemQuantities = $feedUsage ? $feedUsage->details->pluck('quantity_taken', 'feed_id') : [];
            $supplyQuantities = $supplyUsage ? $supplyUsage->details->pluck('quantity_taken', 'supply_id') : [];

            $mortalityTypes = [LivestockDepletionConfig::LEGACY_TYPE_MATI, LivestockDepletionConfig::TYPE_MORTALITY];
            $cullingTypes = [LivestockDepletionConfig::LEGACY_TYPE_AFKIR, LivestockDepletionConfig::TYPE_CULLING];

            $mortality = $deplesiData->whereIn('jenis', $mortalityTypes)->sum('jumlah');
            $culling = $deplesiData->whereIn('jenis', $cullingTypes)->sum('jumlah');

            $startDate = Livestock::find($livestockId)->start_date;
            $age = $startDate ? Carbon::parse($startDate)->diffInDays(Carbon::parse($date)) : null;

            $weightYesterday = $yesterdayData['weight'] ?? 0;
            $weightToday = $recordingData->berat_hari_ini ?? null;

            return [
                'feedUsageId' => $feedUsage->id ?? null,
                'supplyUsageId' => $supplyUsage->id ?? null,
                'itemQuantities' => $itemQuantities,
                'supplyQuantities' => $supplyQuantities,
                'deplesiData' => $deplesiData,
                'mortality' => $mortality,
                'culling' => $culling,
                'total_deplesi' => $mortality + $culling,
                'weight_yesterday' => $weightYesterday,
                'weight_today' => $weightToday,
                'weight_gain' => $weightToday && $weightYesterday ? $weightToday - $weightYesterday : null,
                'sales_quantity' => $recordingData->sales_quantity ?? null,
                'sales_weight' => $recordingData->sales_weight ?? null,
                'sales_price' => $recordingData->sales_price ?? null,
                'total_sales' => $recordingData->total_sales ?? null,
                'isEditing' => !is_null($recordingData),
                'yesterday_data' => $yesterdayData,
                'age' => $age,
            ];
        } catch (Exception $e) {
            Log::error('❌ LegacyRecordingService: Error in loadDateDataFallback', ['error' => $e->getMessage()]);
            return ['error' => 'Gagal memuat data tanggal.'];
        }
    }

    /**
     * Load yesterday's data for better information display (legacy path).
     * This method mirrors logic from Records::loadYesterdayDataFallback.
     */
    public function loadYesterdayDataFallback(string $livestockId, string $yesterdayDate): ?array
    {
        Log::info('🔄 LegacyRecordingService: loadYesterdayDataFallback started.', [
            'livestock_id' => $livestockId,
            'yesterday_date' => $yesterdayDate,
        ]);
        try {
            // --- Fetch Yesterday's Recording Data ---
            $yesterdayRecording = Recording::where('livestock_id', $livestockId)
                ->whereDate('tanggal', $yesterdayDate)
                ->first();

            // --- Fetch Yesterday's Depletion Data (with config normalization) ---
            $yesterdayDeplesi = LivestockDepletion::where('livestock_id', $livestockId)
                ->whereDate('tanggal', $yesterdayDate)
                ->get()
                ->map(function ($item) {
                    $item->normalized_type = LivestockDepletionConfig::normalize($item->jenis);
                    $item->display_name = LivestockDepletionConfig::getDisplayName($item->jenis, true);
                    $item->category = LivestockDepletionConfig::getCategory($item->normalized_type);
                    return $item;
                });

            // --- Fetch Yesterday's Feed Usage Data ---
            $yesterdayFeedUsage = FeedUsageDetail::whereHas('feedUsage', function ($query) use ($livestockId, $yesterdayDate) {
                $query->where('livestock_id', $livestockId)
                    ->whereDate('usage_date', $yesterdayDate);
            })
                ->with(['feedStock.feed.unit'])
                ->get();

            // --- Fetch Yesterday's Supply Usage Data ---
            $yesterdaySupplyUsage = SupplyUsageDetail::whereHas('supplyUsage', function ($query) use ($livestockId, $yesterdayDate) {
                $query->where('livestock_id', $livestockId)
                    ->whereDate('usage_date', $yesterdayDate);
            })
                ->with(['supplyStock.supply.unit'])
                ->get();

            // --- Process and Store Yesterday's Data ---
            $yesterday_weight = $yesterdayRecording->berat_hari_ini ?? 0;
            $yesterday_stock_end = $yesterdayRecording->stock_akhir ?? 0;

            $mortalityTypes = [LivestockDepletionConfig::LEGACY_TYPE_MATI, LivestockDepletionConfig::TYPE_MORTALITY];
            $cullingTypes = [LivestockDepletionConfig::LEGACY_TYPE_AFKIR, LivestockDepletionConfig::TYPE_CULLING];

            $yesterday_mortality = $yesterdayDeplesi->filter(fn($item) => in_array($item->jenis, $mortalityTypes) || in_array($item->normalized_type, [LivestockDepletionConfig::TYPE_MORTALITY]))->sum('jumlah');
            $yesterday_culling = $yesterdayDeplesi->filter(fn($item) => in_array($item->jenis, $cullingTypes) || in_array($item->normalized_type, [LivestockDepletionConfig::TYPE_CULLING]))->sum('jumlah');

            $feedUsageByType = $yesterdayFeedUsage->groupBy('feedStock.feed.name')
                ->map(fn($group) => [
                    'name' => $group->first()->feedStock->feed->name ?? 'Unknown',
                    'total_quantity' => $group->sum('quantity_taken'),
                    'unit' => $group->first()->feedStock->feed->unit->name ?? 'Kg'
                ])->values();

            $yesterday_feed_usage = [
                'total_quantity' => $yesterdayFeedUsage->sum('quantity_taken'),
                'by_type' => $feedUsageByType->toArray(),
                'types_count' => $feedUsageByType->count()
            ];

            $supplyUsageByType = $yesterdaySupplyUsage->groupBy('supplyStock.supply.name')
                ->map(fn($group) => [
                    'name' => $group->first()->supplyStock->supply->name ?? 'Unknown',
                    'total_quantity' => $group->sum('quantity_taken'),
                    'unit' => $group->first()->supplyStock->supply->unit->name ?? 'Unit'
                ])->values();

            $yesterday_supply_usage = [
                'total_quantity' => $yesterdaySupplyUsage->sum('quantity_taken'),
                'by_type' => $supplyUsageByType->toArray(),
                'types_count' => $supplyUsageByType->count()
            ];

            $yesterdayManualDepletion = $yesterdayDeplesi->contains(function ($item) {
                $metadata = is_array($item->metadata) ? $item->metadata : json_decode($item->metadata ?? '{}', true);
                return isset($metadata['depletion_method']) && $metadata['depletion_method'] === 'manual';
            });

            $summary = $this->generateYesterdaySummary($yesterday_weight, $yesterday_mortality, $yesterday_culling, $yesterday_feed_usage, $yesterday_supply_usage);

            return [
                'date' => $yesterdayDate,
                'formatted_date' => Carbon::parse($yesterdayDate)->format('d/m/Y'),
                'day_name' => Carbon::parse($yesterdayDate)->locale('id')->dayName,
                'weight' => $yesterday_weight,
                'stock_end' => $yesterday_stock_end,
                'mortality' => $yesterday_mortality,
                'culling' => $yesterday_culling,
                'total_depletion' => $yesterday_mortality + $yesterday_culling,
                'feed_usage' => $yesterday_feed_usage,
                'supply_usage' => $yesterday_supply_usage,
                'has_data' => $yesterdayRecording || $yesterdayDeplesi->isNotEmpty() || $yesterdayFeedUsage->isNotEmpty() || $yesterdaySupplyUsage->isNotEmpty(),
                'summary' => $summary,
                'is_manual_depletion' => $yesterdayManualDepletion,
                'depletion_method' => $yesterdayManualDepletion ? 'manual' : 'recording'
            ];
        } catch (Exception $e) {
            Log::error("❌ LegacyRecordingService: Error in loadYesterdayDataFallback", [
                'livestock_id' => $livestockId,
                'yesterday_date' => $yesterdayDate,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Generate a summary of yesterday's activities. Helper for loadYesterdayDataFallback.
     */
    private function generateYesterdaySummary($yesterday_weight, $yesterday_mortality, $yesterday_culling, $yesterday_feed_usage, $yesterday_supply_usage): string
    {
        $summary = [];
        if ($yesterday_weight > 0) $summary[] = "Berat: " . number_format($yesterday_weight, 0) . "gr";
        if ($yesterday_mortality > 0) $summary[] = "Mati: " . $yesterday_mortality . " ekor";
        if ($yesterday_culling > 0) $summary[] = "Afkir: " . $yesterday_culling . " ekor";
        if ($yesterday_feed_usage['total_quantity'] > 0) $summary[] = "Pakan: " . number_format($yesterday_feed_usage['total_quantity'], 1) . "kg";
        if ($yesterday_supply_usage['total_quantity'] > 0) $summary[] = "OVK: " . $yesterday_supply_usage['types_count'] . " jenis";
        return empty($summary) ? "Tidak ada data" : implode(", ", $summary);
    }

    /**
     * Load recording data for table display with optimized queries.
     * This replaces the N+1 query version from Records.php.
     */
    public function loadRecordingDataForTable(string $livestockId): array
    {
        $ternak = CurrentLivestock::with('livestock')->where('livestock_id', $livestockId)->first();
        if (!$ternak || !$ternak->livestock || !$ternak->livestock->start_date) {
            return [];
        }

        $startDate = Carbon::parse($ternak->livestock->start_date);
        $today = Carbon::today();

        // --- OPTIMIZATION: Fetch all data outside the loop ---
        $allDeplesi = LivestockDepletion::where('livestock_id', $livestockId)
            ->whereBetween('tanggal', [$startDate, $today])
            ->get()
            ->groupBy(fn($item) => Carbon::parse($item->tanggal)->format('Y-m-d'));

        $allPakanUsage = FeedUsageDetail::whereHas('feedUsage', function ($query) use ($livestockId, $startDate, $today) {
            $query->where('livestock_id', $livestockId)
                ->whereBetween('usage_date', [$startDate, $today]);
        })
            ->with('feedUsage', 'feedStock.feed')
            ->get()
            ->groupBy(fn($item) => Carbon::parse($item->feedUsage->usage_date)->format('Y-m-d'));

        $standarData = [];
        $livestockData = json_decode(json_encode($ternak->livestock->data), true);
        if (is_array($livestockData) && isset($livestockData[0]['livestock_breed_standard'])) {
            $standarData = $livestockData[0]['livestock_breed_standard'];
        }

        // --- Process data day by day ---
        $records = collect();
        $currentDate = $startDate->copy();
        $stockAwal = $ternak->livestock->initial_quantity;
        $totalPakanUsage = 0;

        while ($currentDate <= $today) {
            $dateStr = $currentDate->format('Y-m-d');
            $age = $startDate->diffInDays($currentDate);

            $deplesiForDay = $allDeplesi->get($dateStr, collect());
            $mortality = $deplesiForDay->whereIn('jenis', [LivestockDepletionConfig::LEGACY_TYPE_MATI, LivestockDepletionConfig::TYPE_MORTALITY])->sum('jumlah');
            $culling = $deplesiForDay->whereIn('jenis', [LivestockDepletionConfig::LEGACY_TYPE_AFKIR, LivestockDepletionConfig::TYPE_CULLING])->sum('jumlah');
            $totalDeplesi = $mortality + $culling;

            $pakanUsageForDay = $allPakanUsage->get($dateStr, collect());
            $pakanHarian = $pakanUsageForDay->sum('quantity_taken');
            $totalPakanUsage += $pakanHarian;

            $record = [
                'tanggal' => $dateStr,
                'age' => $age,
                'fcr_target' => $standarData['data'][$age]['fcr']['target'] ?? 0,
                'stock_awal' => $stockAwal,
                'mati' => $mortality,
                'afkir' => $culling,
                'total_deplesi' => $totalDeplesi,
                'deplesi_percentage' => $stockAwal > 0 ? round(($totalDeplesi / $stockAwal) * 100, 2) : 0,
                'stock_akhir' => $stockAwal - $totalDeplesi,
                'pakan_jenis' => $pakanUsageForDay->pluck('feedStock.feed.name')->first() ?? '-',
                'pakan_harian' => $pakanHarian,
                'pakan_total' => $totalPakanUsage,
            ];

            $records->push($record);
            $stockAwal = $record['stock_akhir'];
            $currentDate->addDay();
        }

        return $records->toArray();
    }

    // public function loadDateDataFallback(string $livestockId, string $date): array
    // {
    //     Log::warning('🚨 DEPRECATED: LegacyRecordingService::loadDateDataFallback was called. This logic has been migrated to RecordingDataService.');
    //     return []; // Return empty array to prevent errors
    // }

    // public function loadYesterdayDataFallback(string $livestockId, string $yesterdayDate): ?array
    // {
    //     Log::warning('🚨 DEPRECATED: LegacyRecordingService::loadYesterdayDataFallback was called. This logic has been migrated to RecordingDataService.');
    //     return null; // Return null as per original signature
    // }

    // private function generateYesterdaySummary($yesterday_weight, $yesterday_mortality, $yesterday_culling, $yesterday_feed_usage, $yesterday_supply_usage): string
    // {
    //     Log::warning('🚨 DEPRECATED: LegacyRecordingService::generateYesterdaySummary was called. This helper has been migrated to RecordingDataService.');
    //     return "";
    // }

    // public function loadRecordingDataForTable(string $livestockId): array
    // {
    //     Log::warning('🚨 DEPRECATED: LegacyRecordingService::loadRecordingDataForTable was called. This logic has been migrated to RecordingDataService.');
    //     return []; // Return empty array
    // }
}

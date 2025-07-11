<?php

namespace App\Services\Recording;

use App\Services\Recording\Contracts\RecordingPersistenceServiceInterface;
use App\Services\Recording\DTOs\RecordingDTO;
use App\Services\Recording\DTOs\ServiceResult;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

// All required models and services
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
use App\Config\LivestockDepletionConfig;
use App\Services\FeedUsageService;
use App\Services\Livestock\LivestockCostService;
use App\Services\Recording\RecordingService;

// Import logging helper functions
use function App\Helpers\logInfoIfDebug;
use function App\Helpers\logDebugIfDebug;
use function App\Helpers\logWarningIfDebug;
use function App\Helpers\logErrorIfDebug;


class RecordingPersistenceService implements RecordingPersistenceServiceInterface
{
    private FeedUsageService $feedUsageService;
    private LivestockCostService $livestockCostService;
    private RecordingService $recordingService;

    public function __construct(
        FeedUsageService $feedUsageService,
        LivestockCostService $livestockCostService,
        RecordingService $recordingService
    ) {
        $this->feedUsageService = $feedUsageService;
        $this->livestockCostService = $livestockCostService;
        $this->recordingService = $recordingService;
    }

    public function saveRecording(RecordingDTO $recordingDTO): ServiceResult
    {
        logInfoIfDebug('🔄 MODULAR_PATH: RecordingPersistenceService::saveRecording called.', [
            'livestockId' => $recordingDTO->livestockId,
            'date' => $recordingDTO->date,
            'weight_today' => $recordingDTO->weightToday,
            'mortality' => $recordingDTO->mortality,
            'culling' => $recordingDTO->culling,
            'itemQuantities_count' => count($recordingDTO->itemQuantities),
            'supplyQuantities_count' => count($recordingDTO->supplyQuantities)
        ]);

        try {
            DB::beginTransaction();

            $livestockId = $recordingDTO->livestockId;
            $date = $recordingDTO->date;

            logDebugIfDebug('Preparing feed and supply usage data', [
                'livestockId' => $livestockId,
                'date' => $date
            ]);

            $usages = $this->prepareFeedUsageData($recordingDTO->itemQuantities, $livestockId);
            $supplyUsages = $this->prepareSupplyUsageData($recordingDTO->supplyQuantities, $livestockId);

            logDebugIfDebug('Feed and supply usage data prepared', [
                'usages_count' => count($usages),
                'supplyUsages_count' => count($supplyUsages),
                'usages_sample' => array_slice($usages, 0, 2),
                'supplyUsages_sample' => array_slice($supplyUsages, 0, 2)
            ]);

            $ternak = CurrentLivestock::with(['livestock.coop', 'livestock.farm'])->where('livestock_id', $livestockId)->first();
            if (!$ternak || !$ternak->livestock) {
                throw new Exception("Livestock record not found or invalid");
            }

            logDebugIfDebug('Livestock data loaded', [
                'livestock_name' => $ternak->livestock->name,
                'farm_name' => $ternak->livestock->farm->name ?? 'Unknown',
                'coop_name' => $ternak->livestock->coop->name ?? 'Unknown'
            ]);

            $livestockStartDate = Carbon::parse($ternak->livestock->start_date);
            $recordDate = Carbon::parse($date);
            if ($recordDate->lt($livestockStartDate)) {
                throw new Exception("Recording date cannot be earlier than livestock start date ({$livestockStartDate->format('Y-m-d')})");
            }

            $populationHistory = $this->recordingService->getPopulationHistory($livestockId, $recordDate);
            $age = $livestockStartDate->diffInDays($recordDate);
            $previousRecording = Recording::where('livestock_id', $livestockId)->whereDate('tanggal', $recordDate->copy()->subDay())->first();

            $stockAwalHariIni = $previousRecording ? $previousRecording->stock_akhir : $ternak->livestock->initial_quantity;
            $totalDeplesiHariIni = (int)($recordingDTO->mortality) + (int)($recordingDTO->culling) + (int)($recordingDTO->salesQuantity);
            $stockAkhirHariIni = $stockAwalHariIni - $totalDeplesiHariIni;

            logDebugIfDebug('Stock calculations', [
                'stock_awal' => $stockAwalHariIni,
                'stock_akhir' => $stockAkhirHariIni,
                'total_deplesi' => $totalDeplesiHariIni,
                'mortality' => $recordingDTO->mortality,
                'culling' => $recordingDTO->culling,
                'sales_quantity' => $recordingDTO->salesQuantity
            ]);

            $weightHistory = $this->getWeightHistory($livestockId, $recordDate);
            $feedHistory = $this->getFeedConsumptionHistory($livestockId, $recordDate);
            $outflowHistory = $this->getDetailedOutflowHistory($livestockId, $date);
            $performanceMetrics = $this->calculatePerformanceMetrics($age, $stockAkhirHariIni, $ternak->livestock->initial_quantity, (float)$recordingDTO->weightToday, $feedHistory['cumulative_feed_consumption'], $outflowHistory['total']);

            $weight_yesterday = $previousRecording->berat_hari_ini ?? 0;
            $weight_gain = (float)$recordingDTO->weightToday - (float)$weight_yesterday;

            logDebugIfDebug('Weight calculations', [
                'weight_yesterday' => $weight_yesterday,
                'weight_today' => $recordingDTO->weightToday,
                'weight_gain' => $weight_gain
            ]);

            $detailedPayload = $this->buildStructuredPayload(
                $ternak,
                $age,
                $stockAwalHariIni,
                $stockAkhirHariIni,
                (float)($recordingDTO->weightToday),
                (float)$weight_yesterday,
                $weight_gain,
                $performanceMetrics,
                $weightHistory,
                $feedHistory,
                $populationHistory,
                $outflowHistory,
                $usages,
                $supplyUsages,
                (int)$recordingDTO->mortality,
                (int)$recordingDTO->culling,
                (int)$recordingDTO->salesQuantity,
                (float)($recordingDTO->salesPrice ?? 0),
                0,
                (float)($recordingDTO->totalSales ?? 0),
                $recordingDTO->isManualDepletionEnabled,
                $recordingDTO->isManualFeedUsageEnabled,
                $recordingDTO->recordingMethod,
                $recordingDTO->livestockConfig,
                $date
            );

            logDebugIfDebug('Structured payload built', [
                'payload_keys' => array_keys($detailedPayload),
                'payload_size' => strlen(json_encode($detailedPayload))
            ]);

            $recordingInput = [
                'livestock_id' => $livestockId,
                'tanggal' => $date,
                'age' => $age,
                'stock_awal' => $stockAwalHariIni,
                'stock_akhir' => $stockAkhirHariIni,
                'berat_hari_ini' => $recordingDTO->weightToday,
                'berat_semalam' => $weight_yesterday,
                'kenaikan_berat' => $weight_gain,
                'pakan_jenis' => is_array($usages) ? implode(', ', array_column($usages, 'feed_name')) : '',
                'pakan_harian' => is_array($usages) ? array_sum(array_column($usages, 'quantity')) : 0,
                'feed_id' => is_array($usages) ? implode(', ', array_column($usages, 'feed_id')) : '',
                'payload' => $detailedPayload,
            ];

            logDebugIfDebug('Recording input prepared', [
                'recording_input_keys' => array_keys($recordingInput),
                'pakan_harian' => $recordingInput['pakan_harian'],
                'pakan_jenis' => $recordingInput['pakan_jenis']
            ]);

            $recording = $this->saveOrUpdateRecording($recordingInput);

            logInfoIfDebug('✅ Recording saved/updated', [
                'recording_id' => $recording->id,
                'livestock_id' => $recording->livestock_id,
                'tanggal' => $recording->tanggal,
                'berat_hari_ini' => $recording->berat_hari_ini,
                'stock_akhir' => $recording->stock_akhir
            ]);

            // Save feed usage if there are items
            if (!empty($usages)) {
                logDebugIfDebug('Saving feed usage', [
                    'usages_count' => count($usages),
                    'feedUsageId' => $recordingDTO->feedUsageId
                ]);
                $this->saveFeedUsageWithTracking($usages, $recording->id, $date, $livestockId, $recordingDTO->feedUsageId);
            } else {
                logDebugIfDebug('No feed usage to save', ['usages_empty' => empty($usages)]);
            }

            // Save supply usage if there are items
            if (!empty($supplyUsages)) {
                logDebugIfDebug('Saving supply usage', [
                    'supplyUsages_count' => count($supplyUsages),
                    'supplyUsageId' => $recordingDTO->supplyUsageId
                ]);
                $this->saveSupplyUsageWithTracking($supplyUsages, $recording->id, $date, $livestockId, $recordingDTO->supplyUsageId);
            } else {
                logDebugIfDebug('No supply usage to save', ['supplyUsages_empty' => empty($supplyUsages)]);
            }

            // Save depletions if there are any
            $depletionsToProcess = [
                LivestockDepletionConfig::TYPE_MORTALITY => $recordingDTO->mortality,
                LivestockDepletionConfig::TYPE_CULLING => $recordingDTO->culling
            ];

            foreach ($depletionsToProcess as $type => $qty) {
                if ($qty > 0) {
                    logDebugIfDebug('Saving depletion', [
                        'type' => $type,
                        'quantity' => $qty,
                        'recording_id' => $recording->id
                    ]);
                    $this->storeDeplesiWithDetails($type, (int) $qty, $recording->id, $date, $livestockId);
                }
            }

            // Calculate livestock cost
            logDebugIfDebug('Calculating livestock cost', [
                'livestock_id' => $livestockId,
                'date' => $date
            ]);
            $this->livestockCostService->calculateForDate($livestockId, $date);

            DB::commit();

            // Clear cache after successful save
            logDebugIfDebug('Clearing cache after successful save', [
                'livestock_id' => $livestockId,
                'date' => $date
            ]);
            $performanceService = app(\App\Services\Recording\RecordingPerformanceService::class);
            $performanceService->clearRecordingCache($livestockId);

            // Validate that data was actually saved
            logDebugIfDebug('Validating saved data', [
                'livestock_id' => $livestockId,
                'date' => $date
            ]);
            $this->validateSavedData($livestockId, $date, $recordingDTO);

            logInfoIfDebug('✅ MODULAR_PATH: Recording saved successfully', [
                'recording_id' => $recording->id,
                'livestock_id' => $livestockId,
                'date' => $date,
                'total_feed_quantity' => array_sum(array_column($usages, 'quantity')),
                'total_supply_quantity' => array_sum(array_column($supplyUsages, 'quantity')),
                'mortality' => $recordingDTO->mortality,
                'culling' => $recordingDTO->culling
            ]);

            return ServiceResult::success('Data berhasil disimpan (Modular).', [
                'recording_id' => $recording->id,
                'feed_usage_id' => $recordingDTO->feedUsageId,
                'supply_usage_id' => $recordingDTO->supplyUsageId,
                'saved_data' => [
                    'weight_today' => $recordingDTO->weightToday,
                    'mortality' => $recordingDTO->mortality,
                    'culling' => $recordingDTO->culling,
                    'feed_quantity' => array_sum(array_column($usages, 'quantity')),
                    'supply_quantity' => array_sum(array_column($supplyUsages, 'quantity'))
                ]
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            logErrorIfDebug("❌ MODULAR_PATH: Error in RecordingPersistenceService::saveRecording", [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => substr($e->getTraceAsString(), 0, 2000)
            ]);
            return ServiceResult::error('Gagal menyimpan data (Modular): ' . $e->getMessage(), $e);
        }
    }

    private function prepareFeedUsageData(array $itemQuantities, string $livestockId): array
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

    private function prepareSupplyUsageData(array $supplyQuantities, string $livestockId): array
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

    private function saveFeedUsageWithTracking(array $newUsages, string $recordingId, string $date, string $livestockId, ?string $feedUsageId): void
    {
        logDebugIfDebug('🔄 saveFeedUsageWithTracking called', [
            'recording_id' => $recordingId,
            'date' => $date,
            'livestock_id' => $livestockId,
            'feedUsageId' => $feedUsageId,
            'newUsages_count' => count($newUsages),
            'total_quantity' => array_sum(array_column($newUsages, 'quantity'))
        ]);

        if ($feedUsageId) {
            $usage = FeedUsage::findOrFail($feedUsageId);
            logDebugIfDebug('📝 Found existing feed usage', [
                'usage_id' => $usage->id,
                'existing_total_quantity' => $usage->total_quantity,
                'new_total_quantity' => array_sum(array_column($newUsages, 'quantity'))
            ]);

            if ($this->hasUsageChanged($usage, $newUsages)) {
                logDebugIfDebug('🔄 Feed usage has changed, updating', [
                    'usage_id' => $usage->id
                ]);
                $this->updateFeedUsageWithTracking($newUsages, $feedUsageId, $recordingId);
            } else {
                logDebugIfDebug('✅ Feed usage unchanged, skipping update', [
                    'usage_id' => $usage->id
                ]);
            }
        } else {
            logDebugIfDebug('🆕 Creating new feed usage', [
                'date' => $date,
                'livestock_id' => $livestockId,
                'recording_id' => $recordingId,
                'total_quantity' => array_sum(array_column($newUsages, 'quantity'))
            ]);

            $usage = FeedUsage::create([
                'usage_date' => $date,
                'livestock_id' => $livestockId,
                'recording_id' => $recordingId,
                'total_quantity' => array_sum(array_column($newUsages, 'quantity')),
                'metadata' => ['created_at' => now()->toIso8601String(), 'created_by' => Auth::id()],
                'created_by' => Auth::id(),
            ]);

            logInfoIfDebug('✅ New feed usage created', [
                'usage_id' => $usage->id,
                'total_quantity' => $usage->total_quantity,
                'date' => $usage->usage_date
            ]);

            $this->feedUsageService->processWithMetadata($usage, $newUsages);
        }
    }

    private function updateFeedUsageWithTracking(array $newUsages, string $feedUsageId, string $recordingId): void
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

        $this->feedUsageService->processWithMetadata($usage, $newUsages);
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

    private function saveSupplyUsageWithTracking(array $supplyUsages, string $recordingId, string $date, string $livestockId, ?string $supplyUsageId): void
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

    private function processSupplyUsageDetail($usage, $usageData, $livestockId): void
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

    private function storeDeplesiWithDetails($jenis, $jumlah, $recordingId, $date, $livestockId): LivestockDepletion
    {
        logDebugIfDebug('🔄 storeDeplesiWithDetails called', [
            'jenis' => $jenis,
            'jumlah' => $jumlah,
            'recording_id' => $recordingId,
            'date' => $date,
            'livestock_id' => $livestockId
        ]);

        $normalizedType = LivestockDepletionConfig::normalize($jenis);
        $livestock = Livestock::find($livestockId);
        $age = $livestock ? Carbon::parse($date)->diffInDays(Carbon::parse($livestock->start_date)) : null;

        $oldJumlah = LivestockDepletion::where('livestock_id', $livestockId)
            ->where('tanggal', $date)->where('jenis', $normalizedType)->sum('jumlah');
        $delta = $jumlah - $oldJumlah;

        logDebugIfDebug('Depletion calculation', [
            'normalized_type' => $normalizedType,
            'old_jumlah' => $oldJumlah,
            'new_jumlah' => $jumlah,
            'delta' => $delta,
            'age_days' => $age
        ]);

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

        logInfoIfDebug('✅ Depletion saved/updated', [
            'depletion_id' => $deplesi->id,
            'jenis' => $deplesi->jenis,
            'jumlah' => $deplesi->jumlah,
            'recording_id' => $deplesi->recording_id,
            'was_created' => $deplesi->wasRecentlyCreated,
            'was_updated' => !$deplesi->wasRecentlyCreated
        ]);

        return $deplesi;
    }

    public function getDetailedOutflowHistory($livestockId, $date): array
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

    public function getWeightHistory($livestockId, $currentDate): array
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

    public function getFeedConsumptionHistory($livestockId, $currentDate): array
    {
        $totalConsumption = FeedUsage::where('livestock_id', $livestockId)
            ->where('usage_date', '<', $currentDate->format('Y-m-d'))
            ->sum('total_quantity');
        return ['cumulative_feed_consumption' => $totalConsumption];
    }

    public function calculatePerformanceMetrics($age, $currentPopulation, $initialPopulation, $currentWeight, $totalFeedConsumption, $totalDepleted): array
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

    public function saveOrUpdateRecording($data): Recording
    {
        logDebugIfDebug('🔄 saveOrUpdateRecording called', [
            'livestock_id' => $data['livestock_id'],
            'tanggal' => $data['tanggal'],
            'berat_hari_ini' => $data['berat_hari_ini'] ?? null,
            'stock_akhir' => $data['stock_akhir'] ?? null
        ]);

        $livestock = Livestock::find($data['livestock_id']);
        if (!$livestock) {
            throw new Exception("Livestock not found");
        }

        $enhancedMetadata = [
            'version' => '2.0',
            'recorded_at' => now()->toIso8601String(),
            'recorded_by' => ['id' => Auth::id(), 'name' => Auth::user()->name ?? 'Unknown'],
        ];
        $fullPayload = array_merge($data['payload'] ?? [], $enhancedMetadata);

        // Check if recording already exists
        $existingRecording = Recording::where('livestock_id', $data['livestock_id'])
            ->where('tanggal', $data['tanggal'])
            ->first();

        if ($existingRecording) {
            logDebugIfDebug('📝 Updating existing recording', [
                'recording_id' => $existingRecording->id,
                'old_berat_hari_ini' => $existingRecording->berat_hari_ini,
                'new_berat_hari_ini' => $data['berat_hari_ini'],
                'old_stock_akhir' => $existingRecording->stock_akhir,
                'new_stock_akhir' => $data['stock_akhir']
            ]);
        } else {
            logDebugIfDebug('🆕 Creating new recording', [
                'livestock_id' => $data['livestock_id'],
                'tanggal' => $data['tanggal']
            ]);
        }

        $recording = Recording::updateOrCreate(
            ['livestock_id' => $data['livestock_id'], 'tanggal' => $data['tanggal']],
            array_merge($data, ['payload' => $fullPayload, 'created_by' => Auth::id(), 'updated_by' => Auth::id()])
        );

        logInfoIfDebug('✅ Recording saved/updated successfully', [
            'recording_id' => $recording->id,
            'livestock_id' => $recording->livestock_id,
            'tanggal' => $recording->tanggal,
            'berat_hari_ini' => $recording->berat_hari_ini,
            'stock_akhir' => $recording->stock_akhir,
            'was_created' => $recording->wasRecentlyCreated,
            'was_updated' => !$recording->wasRecentlyCreated
        ]);

        return $recording;
    }

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

    public function getDetailedUnitInfo($feed, $quantity): array
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

    public function getStockDetails($feedId, $livestockId): array
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

    public function getDetailedSupplyUnitInfo($supply, $quantity): array
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

    public function getSupplyStockDetails($supplyId, $livestockId): array
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

    private function validateSavedData($livestockId, $date, $recordingDTO)
    {
        logDebugIfDebug('🔄 validateSavedData called', [
            'livestock_id' => $livestockId,
            'date' => $date
        ]);

        // Check if recording exists in database
        $recording = Recording::where('livestock_id', $livestockId)
            ->where('tanggal', $date)
            ->first();

        if (!$recording) {
            logErrorIfDebug('❌ Validation failed: Recording not found after save', [
                'livestock_id' => $livestockId,
                'date' => $date
            ]);
            throw new Exception("Recording not found after successful save for validation.");
        }

        logInfoIfDebug('✅ Recording found in database', [
            'recording_id' => $recording->id,
            'berat_hari_ini' => $recording->berat_hari_ini,
            'stock_akhir' => $recording->stock_akhir,
            'pakan_harian' => $recording->pakan_harian
        ]);

        // Check if feed usage exists
        $feedUsage = FeedUsage::where('livestock_id', $livestockId)
            ->where('usage_date', $date)
            ->first();

        if ($feedUsage) {
            logInfoIfDebug('✅ Feed usage found in database', [
                'feed_usage_id' => $feedUsage->id,
                'total_quantity' => $feedUsage->total_quantity,
                'details_count' => $feedUsage->details->count()
            ]);
        } else {
            logWarningIfDebug('⚠️ Feed usage not found in database', [
                'livestock_id' => $livestockId,
                'date' => $date
            ]);
        }

        // Check if depletion exists
        $depletions = LivestockDepletion::where('livestock_id', $livestockId)
            ->where('tanggal', $date)
            ->get();

        if ($depletions->isNotEmpty()) {
            logInfoIfDebug('✅ Depletions found in database', [
                'depletions_count' => $depletions->count(),
                'mortality' => $depletions->where('jenis', 'mortality')->sum('jumlah'),
                'culling' => $depletions->where('jenis', 'culling')->sum('jumlah')
            ]);
        } else {
            logWarningIfDebug('⚠️ No depletions found in database', [
                'livestock_id' => $livestockId,
                'date' => $date
            ]);
        }

        // Validate data consistency
        $expectedWeight = $recordingDTO->weightToday;
        $expectedMortality = $recordingDTO->mortality;
        $expectedCulling = $recordingDTO->culling;

        $actualWeight = $recording->berat_hari_ini;
        $actualMortality = $depletions->where('jenis', 'mortality')->sum('jumlah');
        $actualCulling = $depletions->where('jenis', 'culling')->sum('jumlah');

        $validationResults = [
            'weight_match' => $expectedWeight == $actualWeight,
            'mortality_match' => $expectedMortality == $actualMortality,
            'culling_match' => $expectedCulling == $actualCulling
        ];

        logInfoIfDebug('✅ Data validation results', [
            'validation_results' => $validationResults,
            'expected' => [
                'weight' => $expectedWeight,
                'mortality' => $expectedMortality,
                'culling' => $expectedCulling
            ],
            'actual' => [
                'weight' => $actualWeight,
                'mortality' => $actualMortality,
                'culling' => $actualCulling
            ]
        ]);

        // Check if any validation failed
        if (in_array(false, $validationResults)) {
            logErrorIfDebug('❌ Data validation failed', [
                'validation_results' => $validationResults
            ]);
            throw new Exception("Data validation failed after save.");
        }

        logInfoIfDebug('✅ All validations passed', [
            'livestock_id' => $livestockId,
            'date' => $date
        ]);
    }
}

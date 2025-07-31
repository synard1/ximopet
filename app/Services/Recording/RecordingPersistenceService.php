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
use App\Models\Company;
use App\Models\LivestockBatch;
use App\Models\RecordingSale;
use App\Models\RecordingSaleItem;

use App\Config\LivestockDepletionConfig;
use App\Services\FeedUsageService;
use App\Services\Livestock\LivestockCostService;
use App\Services\Recording\RecordingService;
use App\Services\Livestock\LivestockMutationService;
use App\Config\CompanyConfig;

// Import logging helper functions
use function App\Helpers\logInfoIfDebug;
use function App\Helpers\logDebugIfDebug;
use function App\Helpers\logWarningIfDebug;
use function App\Helpers\logErrorIfDebug;

use App\Services\Recording\RecordingFeedInsightBuilder;
use App\Services\Recording\Contracts\RecordingSaleServiceInterface;

class RecordingPersistenceService implements RecordingPersistenceServiceInterface
{
    private FeedUsageService $feedUsageService;
    private LivestockCostService $livestockCostService;
    private RecordingService $recordingService;
    private RecordingSaleServiceInterface $recordingSaleService;

    public function __construct(
        FeedUsageService $feedUsageService,
        LivestockCostService $livestockCostService,
        RecordingService $recordingService,
        RecordingSaleServiceInterface $recordingSaleService
    ) {
        $this->feedUsageService = $feedUsageService;
        $this->livestockCostService = $livestockCostService;
        $this->recordingService = $recordingService;
        $this->recordingSaleService = $recordingSaleService;
    }

    /**
     * Save recording with virtual quantity calculation support
     */
    public function saveRecording(RecordingDTO $recordingDTO): ServiceResult
    {
        $startTime = microtime(true);
        $timeout = 30; // 30 seconds timeout

        try {
            logInfoIfDebug('🔄 RecordingPersistenceService::saveRecording started', [
                'livestock_id' => $recordingDTO->livestockId,
                'date' => $recordingDTO->date,
                'sales_quantity' => $recordingDTO->salesQuantity,
                'sales_weight' => $recordingDTO->salesWeight
            ]);

            // Get company configuration
            $companyConfig = $this->getCompanyConfig();
            $isVirtualMode = $companyConfig['virtual_mode'] ?? true;
            $isTwoStage = $companyConfig['two_stage_mode'] ?? true;

            // Get livestock and company data
            logInfoIfDebug('🔄 Getting livestock and company data', [
                'livestock_id' => $recordingDTO->livestockId
            ]);

            $livestock = Livestock::with(['farm'])->find($recordingDTO->livestockId);
            if (!$livestock) {
                return ServiceResult::error('Livestock not found: ' . $recordingDTO->livestockId);
            }

            logInfoIfDebug('✅ Livestock found', [
                'livestock_id' => $recordingDTO->livestockId,
                'livestock_name' => $livestock->name,
                'farm_id' => $livestock->farm_id,
                'company_id' => $livestock->company_id
            ]);

            // Get company directly from livestock
            $company = Company::find($livestock->company_id);
            if (!$company) {
                return ServiceResult::error('Company not found for livestock: ' . $recordingDTO->livestockId);
            }

            logInfoIfDebug('✅ Company found via livestock company_id', [
                'company_id' => $livestock->company_id,
                'company_name' => $company->name
            ]);

            logInfoIfDebug('✅ Company found', [
                'livestock_id' => $recordingDTO->livestockId,
                'farm_id' => $livestock->farm_id,
                'company_id' => $livestock->company_id,
                'company_name' => $company->name
            ]);

            // Save or update recording first
            logInfoIfDebug('🔄 Saving/updating recording', [
                'livestock_id' => $recordingDTO->livestockId,
                'date' => $recordingDTO->date
            ]);

            $recordingData = [
                'livestock_id' => $recordingDTO->livestockId,
                'tanggal' => $recordingDTO->date,
                'berat_hari_ini' => $recordingDTO->weightToday,
                'stock_akhir' => $recordingDTO->weightToday ?: 0, // Ensure not null
                'payload' => [
                    'livestock_id' => $recordingDTO->livestockId,
                    'date' => $recordingDTO->date,
                    'mortality' => $recordingDTO->mortality,
                    'culling' => $recordingDTO->culling,
                    'weight_today' => $recordingDTO->weightToday,
                    'sales_quantity' => $recordingDTO->salesQuantity,
                    'sales_weight' => $recordingDTO->salesWeight,
                    'sales_price' => $recordingDTO->salesPrice,
                    'total_sales' => $recordingDTO->totalSales,
                    'itemQuantities' => $recordingDTO->itemQuantities,
                    'supplyQuantities' => $recordingDTO->supplyQuantities,
                    'livestockConfig' => $recordingDTO->livestockConfig,
                    'isManualDepletionEnabled' => $recordingDTO->isManualDepletionEnabled,
                    'isManualFeedUsageEnabled' => $recordingDTO->isManualFeedUsageEnabled,
                    'recordingMethod' => $recordingDTO->recordingMethod,
                    'validationStatus' => $recordingDTO->validationStatus,
                    'validatedData' => $recordingDTO->validatedData,
                ]
            ];

            logDebugIfDebug('🔄 Calculating stock_akhir', [
                'livestock_id' => $recordingDTO->livestockId,
                'date' => $recordingDTO->date,
                'weight_today' => $recordingDTO->weightToday,
                'calculated_stock_akhir' => $recordingData['stock_akhir']
            ]);

            $recording = $this->saveOrUpdateRecording($recordingData);
            $recordingId = $recording->id;

            // Process sales data (now uses UPDATE logic instead of DELETE/CREATE)
            $salesResult = $this->processSalesData($recordingDTO, $isVirtualMode, $isTwoStage, $livestock, $recordingId);

            // Process other data types
            $this->processDepletionData($recordingDTO, $isVirtualMode, $isTwoStage);
            $this->processFeedUsageData($recordingDTO, $recordingId);
            $this->processSupplyUsageData($recordingDTO, $recordingId);

            // Clear caches
            $salesDraftCacheKey = "sales_draft_{$recordingDTO->livestockId}_{$recordingDTO->date}";
            $recordingDataCacheKey = "recording_data_{$recordingDTO->livestockId}_{$recordingDTO->date}";
            cache()->forget($salesDraftCacheKey);
            cache()->forget($recordingDataCacheKey);

            // No need to delete sales draft records anymore since we use UPDATE logic
            logDebugIfDebug('Skipping sales draft cleanup - using UPDATE logic', [
                'virtual_mode' => $isVirtualMode,
                'sales_quantity' => $recordingDTO->salesQuantity,
                'sales_weight' => $recordingDTO->salesWeight,
                'reason' => 'update_logic_no_cleanup_needed'
            ]);

            logInfoIfDebug('🔄 Cleared related caches', [
                'sales_draft_cache_key' => $salesDraftCacheKey,
                'recording_data_cache_key' => $recordingDataCacheKey,
                'livestock_id' => $recordingDTO->livestockId,
                'date' => $recordingDTO->date
            ]);

            logInfoIfDebug('✅ RecordingPersistenceService::saveRecording completed successfully', [
                'livestock_id' => $recordingDTO->livestockId,
                'date' => $recordingDTO->date,
                'recording_id' => $recording->id,
                'virtual_mode' => $isVirtualMode,
                'two_stage_mode' => $isTwoStage,
                'sales_processed' => $recordingDTO->salesQuantity > 0,
                'depletion_processed' => ($recordingDTO->mortality > 0 || $recordingDTO->culling > 0),
                'feed_processed' => !empty($recordingDTO->itemQuantities),
                'supply_processed' => !empty($recordingDTO->supplyQuantities),
                'execution_time_seconds' => round(microtime(true) - $startTime, 2)
            ]);

            return ServiceResult::success('Recording saved successfully', [
                'recording_id' => $recording->id,
                'virtual_mode' => $isVirtualMode,
                'two_stage_mode' => $isTwoStage,
                'execution_time' => round(microtime(true) - $startTime, 2)
            ]);
        } catch (Exception $e) {
            $executionTime = microtime(true) - $startTime;
            logErrorIfDebug('❌ Error in saveRecording', [
                'livestock_id' => $recordingDTO->livestockId,
                'date' => $recordingDTO->date,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'execution_time_seconds' => round($executionTime, 2)
            ]);

            // Check if it's a timeout
            if ($executionTime > $timeout) {
                return ServiceResult::error('Save operation timed out after ' . $timeout . ' seconds');
            }

            return ServiceResult::error('Failed to save recording: ' . $e->getMessage(), $e);
        }
    }

    /**
     * Process sales data with virtual calculation support
     */
    private function processSalesData(RecordingDTO $recordingDTO, bool $isVirtualMode, bool $isTwoStage, Livestock $livestock, string $recordingId): ServiceResult
    {
        try {
            // Skip sales processing if no sales data
            if ($recordingDTO->salesQuantity <= 0 && $recordingDTO->salesWeight <= 0) {
                logDebugIfDebug('No sales data to process', [
                    'sales_quantity' => $recordingDTO->salesQuantity,
                    'sales_weight' => $recordingDTO->salesWeight
                ]);
                return ServiceResult::success('No sales data to process');
            }

            // Check if sales recording is enabled
            $salesConfig = \App\Config\CompanyConfig::getSalesConfig();
            if (!($salesConfig['enabled'] ?? true)) {
                logDebugIfDebug('Sales recording is disabled', ['sales_config' => $salesConfig]);
                return ServiceResult::success('Sales recording is disabled');
            }

            // Check if existing sales record exists for this livestock and date
            $existingSalesRecord = \App\Models\RecordingSale::where('livestock_id', $recordingDTO->livestockId)
                ->where('date', $recordingDTO->date)
                ->where('status', 'draft')
                ->first();

            if ($existingSalesRecord) {
                // UPDATE existing sales record
                logInfoIfDebug('🔄 Updating existing sales record', [
                    'existing_sales_id' => $existingSalesRecord->id,
                    'recording_id' => $recordingId,
                    'old_quantity' => $existingSalesRecord->total_quantity,
                    'new_quantity' => $recordingDTO->salesQuantity,
                    'old_weight' => $existingSalesRecord->total_weight,
                    'new_weight' => $recordingDTO->salesWeight,
                    'virtual_mode' => $isVirtualMode
                ]);

                // Update the existing record
                $updateData = [
                    'recording_id' => $recordingId,
                    'quantity' => $recordingDTO->salesQuantity,
                    'total_quantity' => $recordingDTO->salesQuantity,
                    'weight' => $recordingDTO->salesWeight,
                    'total_weight' => $recordingDTO->salesWeight,
                    'price' => $recordingDTO->salesPrice,
                    'total_amount' => $recordingDTO->totalSales,
                    'metadata' => array_merge($existingSalesRecord->metadata ?? [], [
                        'virtual_mode' => $isVirtualMode,
                        'two_stage_mode' => $isTwoStage,
                        'calculation_mode' => $isVirtualMode ? 'virtual' : 'real',
                        'updated_at' => now()->toIso8601String(),
                        'update_source' => 'RecordingPersistenceService::processSalesData'
                    ])
                ];

                logDebugIfDebug('🔄 Updating sales record with data', [
                    'sales_id' => $existingSalesRecord->id,
                    'update_data' => $updateData
                ]);

                $existingSalesRecord->update($updateData);

                // Refresh the model to get updated values
                $existingSalesRecord->refresh();

                // Store virtual data in LivestockBatch if in virtual mode
                if ($isVirtualMode) {
                    $this->storeVirtualSalesDataInBatches($recordingDTO->livestockId, $recordingDTO->date, $recordingDTO->salesQuantity, $recordingDTO->salesWeight);
                }

                logInfoIfDebug('✅ Sales record updated successfully', [
                    'sales_id' => $existingSalesRecord->id,
                    'virtual_mode' => $isVirtualMode,
                    'two_stage_mode' => $isTwoStage,
                    'sales_quantity' => $recordingDTO->salesQuantity,
                    'sales_weight' => $recordingDTO->salesWeight,
                    'sales_status' => $existingSalesRecord->status,
                    'updated_quantity' => $existingSalesRecord->quantity,
                    'updated_total_quantity' => $existingSalesRecord->total_quantity,
                    'updated_weight' => $existingSalesRecord->weight,
                    'updated_total_weight' => $existingSalesRecord->total_weight
                ]);

                return ServiceResult::success('Sales data updated successfully', [
                    'header_id' => $existingSalesRecord->id,
                    'action' => 'updated',
                    'previous_quantity' => $existingSalesRecord->getOriginal('total_quantity'),
                    'previous_weight' => $existingSalesRecord->getOriginal('total_weight')
                ]);
            } else {
                // CREATE new sales record only if none exists
                logInfoIfDebug('🔄 Creating new sales record (no existing record found)', [
                    'recording_id' => $recordingId,
                    'sales_quantity' => $recordingDTO->salesQuantity,
                    'sales_weight' => $recordingDTO->salesWeight,
                    'virtual_mode' => $isVirtualMode
                ]);

                // Prepare sales data for new record
                $salesData = [
                    'company_id' => $livestock->company_id,
                    'livestock_id' => $recordingDTO->livestockId,
                    'recording_id' => $recordingId,
                    'date' => $recordingDTO->date,
                    'quantity' => $recordingDTO->salesQuantity,
                    'total_quantity' => $recordingDTO->salesQuantity,
                    'weight' => $recordingDTO->salesWeight,
                    'total_weight' => $recordingDTO->salesWeight,
                    'price' => $recordingDTO->salesPrice,
                    'total_amount' => $recordingDTO->totalSales,
                    'status' => $isVirtualMode ? 'draft' : ($isTwoStage ? 'draft' : 'finalized'),
                    'metadata' => [
                        'virtual_mode' => $isVirtualMode,
                        'two_stage_mode' => $isTwoStage,
                        'calculation_mode' => $isVirtualMode ? 'virtual' : 'real',
                        'created_at' => now()->toIso8601String(),
                        'create_source' => 'RecordingPersistenceService::processSalesData'
                    ]
                ];

                $salesResult = $this->recordingSaleService->create($salesData);
                if (!$salesResult->isSuccess()) {
                    logErrorIfDebug('Failed to create sales record', [
                        'error' => $salesResult->getMessage(),
                        'sales_data' => $salesData
                    ]);
                    return ServiceResult::error('Failed to create sales record: ' . $salesResult->getMessage());
                }

                logInfoIfDebug('✅ New sales record created successfully', [
                    'virtual_mode' => $isVirtualMode,
                    'two_stage_mode' => $isTwoStage,
                    'sales_quantity' => $recordingDTO->salesQuantity,
                    'sales_weight' => $recordingDTO->salesWeight,
                    'sales_status' => $salesData['status']
                ]);

                return ServiceResult::success('Sales data created successfully', $salesResult->getData());
            }
        } catch (Exception $e) {
            logErrorIfDebug('Error processing sales data', [
                'livestock_id' => $recordingDTO->livestockId,
                'date' => $recordingDTO->date,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return ServiceResult::error('Failed to process sales data: ' . $e->getMessage(), $e);
        }
    }

    /**
     * Process depletion data with virtual calculation support
     */
    private function processDepletionData(RecordingDTO $recordingDTO, bool $isVirtualMode, bool $isTwoStage): ServiceResult
    {
        try {
            $totalDepletion = ($recordingDTO->mortality ?? 0) + ($recordingDTO->culling ?? 0);

            if ($totalDepletion <= 0) {
                logDebugIfDebug('No depletion data to process', [
                    'mortality' => $recordingDTO->mortality,
                    'culling' => $recordingDTO->culling
                ]);
                return ServiceResult::success('No depletion data to process');
            }

            // Get company configuration
            $companyConfig = $this->getCompanyConfig();

            // Process mortality
            if ($recordingDTO->mortality > 0) {
                $mortalityResult = $this->processDepletionType(
                    $recordingDTO->livestockId,
                    $recordingDTO->date,
                    $recordingDTO->mortality,
                    'mortality',
                    $isVirtualMode,
                    $isTwoStage,
                    $companyConfig
                );

                if (!$mortalityResult->isSuccess()) {
                    return $mortalityResult;
                }
            }

            // Process culling
            if ($recordingDTO->culling > 0) {
                $cullingResult = $this->processDepletionType(
                    $recordingDTO->livestockId,
                    $recordingDTO->date,
                    $recordingDTO->culling,
                    'culling',
                    $isVirtualMode,
                    $isTwoStage,
                    $companyConfig
                );

                if (!$cullingResult->isSuccess()) {
                    return $cullingResult;
                }
            }

            logInfoIfDebug('Depletion data processed successfully', [
                'virtual_mode' => $isVirtualMode,
                'two_stage_mode' => $isTwoStage,
                'mortality' => $recordingDTO->mortality,
                'culling' => $recordingDTO->culling,
                'total_depletion' => $totalDepletion
            ]);

            return ServiceResult::success('Depletion data processed successfully');
        } catch (Exception $e) {
            logErrorIfDebug('Error processing depletion data', [
                'livestock_id' => $recordingDTO->livestockId,
                'date' => $recordingDTO->date,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return ServiceResult::error('Failed to process depletion data: ' . $e->getMessage(), $e);
        }
    }

    /**
     * Process specific depletion type with virtual calculation support
     */
    private function processDepletionType(
        string $livestockId,
        string $date,
        int $quantity,
        string $depletionType,
        bool $isVirtualMode,
        bool $isTwoStage,
        array $companyConfig
    ): ServiceResult {
        try {
            // Get depletion method
            $depletionMethod = $this->getDepletionMethod($companyConfig, $depletionType);

            if ($isVirtualMode) {
                // Store virtual depletion data
                $virtualResult = $this->storeVirtualDepletionData($livestockId, $date, $quantity, $depletionType);
                if (!$virtualResult->isSuccess()) {
                    return $virtualResult;
                }
            } else {
                // Process real depletion
                if ($depletionMethod === 'fifo') {
                    $allocationResult = $this->allocateDepletionToBatches($livestockId, $date, $quantity, $depletionType);
                    if (!$allocationResult['success']) {
                        return ServiceResult::error($allocationResult['message']);
                    }
                } else {
                    // Manual depletion - store directly
                    $this->storeDeplesiWithDetails($depletionType, $quantity, null, $date, $livestockId);
                }
            }

            return ServiceResult::success("{$depletionType} data processed successfully");
        } catch (Exception $e) {
            logErrorIfDebug("Error processing {$depletionType} data", [
                'livestock_id' => $livestockId,
                'date' => $date,
                'quantity' => $quantity,
                'error' => $e->getMessage()
            ]);
            return ServiceResult::error("Failed to process {$depletionType} data: " . $e->getMessage(), $e);
        }
    }

    /**
     * Store virtual depletion data in batch data column
     */
    private function storeVirtualDepletionData(string $livestockId, string $date, int $quantity, string $depletionType): ServiceResult
    {
        try {
            $batches = LivestockBatch::where('livestock_id', $livestockId)
                ->where('status', 'active')
                ->get();

            if ($batches->isEmpty()) {
                return ServiceResult::error('No active batches found for virtual depletion storage');
            }

            // Distribute depletion across batches (simple equal distribution for now)
            $quantityPerBatch = (int) ($quantity / $batches->count());
            $remainingQuantity = $quantity % $batches->count();

            foreach ($batches as $index => $batch) {
                $batchQuantity = $quantityPerBatch + ($index < $remainingQuantity ? 1 : 0);

                if ($batchQuantity <= 0) {
                    continue;
                }

                $existingData = $batch->data ?? [];

                // Initialize virtual data structure if not exists
                if (!isset($existingData['virtual_depletion'])) {
                    $existingData['virtual_depletion'] = [];
                }

                // Store virtual depletion data
                $existingData['virtual_depletion'][$date] = [
                    'mortality' => $depletionType === 'mortality' ? $batchQuantity : 0,
                    'culling' => $depletionType === 'culling' ? $batchQuantity : 0,
                    'date' => $date,
                    'status' => 'draft',
                    'metadata' => [
                        'calculation_method' => 'equal_distribution',
                        'calculation_timestamp' => now()->toIso8601String(),
                        'original_quantity' => $quantity,
                        'batch_quantity' => $batchQuantity,
                        'depletion_type' => $depletionType,
                    ]
                ];

                // Update batch data
                $batch->update([
                    'data' => $existingData,
                    'updated_at' => now()
                ]);
            }

            logInfoIfDebug('Virtual depletion data stored successfully', [
                'livestock_id' => $livestockId,
                'date' => $date,
                'depletion_type' => $depletionType,
                'quantity' => $quantity,
                'batches_count' => $batches->count()
            ]);

            return ServiceResult::success('Virtual depletion data stored successfully');
        } catch (Exception $e) {
            logErrorIfDebug('Error storing virtual depletion data', [
                'livestock_id' => $livestockId,
                'date' => $date,
                'depletion_type' => $depletionType,
                'quantity' => $quantity,
                'error' => $e->getMessage()
            ]);
            return ServiceResult::error('Failed to store virtual depletion data: ' . $e->getMessage(), $e);
        }
    }

    /**
     * Process feed usage data
     */
    private function processFeedUsageData(RecordingDTO $recordingDTO, ?string $recordingId = null): ServiceResult
    {
        try {
            if (empty($recordingDTO->itemQuantities)) {
                logDebugIfDebug('No feed usage data to process');
                return ServiceResult::success('No feed usage data to process');
            }

            // Prepare feed usage data
            $feedUsageData = $this->prepareFeedUsageData($recordingDTO->itemQuantities, $recordingDTO->livestockId);

            // Save feed usage
            $this->saveFeedUsageWithTracking(
                $feedUsageData,
                $recordingId,
                $recordingDTO->date,
                $recordingDTO->livestockId,
                $recordingDTO->feedUsageId,
                $recordingDTO->withHistoryFeedUsageDetail
            );

            logInfoIfDebug('Feed usage data processed successfully', [
                'feed_types_count' => count($feedUsageData)
            ]);

            return ServiceResult::success('Feed usage data processed successfully');
        } catch (Exception $e) {
            logErrorIfDebug('Error processing feed usage data', [
                'livestock_id' => $recordingDTO->livestockId,
                'date' => $recordingDTO->date,
                'error' => $e->getMessage()
            ]);
            return ServiceResult::error('Failed to process feed usage data: ' . $e->getMessage(), $e);
        }
    }

    /**
     * Process supply usage data
     */
    private function processSupplyUsageData(RecordingDTO $recordingDTO, ?string $recordingId = null): ServiceResult
    {
        try {
            if (empty($recordingDTO->supplyQuantities)) {
                logDebugIfDebug('No supply usage data to process');
                return ServiceResult::success('No supply usage data to process');
            }

            // Prepare supply usage data
            $supplyUsageData = $this->prepareSupplyUsageData($recordingDTO->supplyQuantities, $recordingDTO->livestockId);

            // Save supply usage
            $this->saveSupplyUsageWithTracking(
                $supplyUsageData,
                $recordingId,
                $recordingDTO->date,
                $recordingDTO->livestockId,
                $recordingDTO->supplyUsageId
            );

            logInfoIfDebug('Supply usage data processed successfully', [
                'supply_types_count' => count($supplyUsageData)
            ]);

            return ServiceResult::success('Supply usage data processed successfully');
        } catch (Exception $e) {
            logErrorIfDebug('Error processing supply usage data', [
                'livestock_id' => $recordingDTO->livestockId,
                'date' => $recordingDTO->date,
                'error' => $e->getMessage()
            ]);
            return ServiceResult::error('Failed to process supply usage data: ' . $e->getMessage(), $e);
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
                // dd($stockInfo);
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
                    // 'category' => $feed ? ($feed->category->name ?? 'Uncategorized') : 'Unknown',
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
                    // 'category' => $supply->supplyCategory->name ?? 'Uncategorized',
                    'timestamp' => now()->toIso8601String(),
                ];
            })
            ->filter()
            ->values()
            ->toArray();
    }

    private function saveFeedUsageWithTracking(array $newUsages, ?string $recordingId, string $date, string $livestockId, ?string $feedUsageId, bool $withHistoryFeedUsageDetail = false): void
    {
        logDebugIfDebug('🔄 saveFeedUsageWithTracking called', [
            'recording_id' => $recordingId,
            'date' => $date,
            'livestock_id' => $livestockId,
            'feedUsageId' => $feedUsageId,
            'newUsages_count' => count($newUsages),
            'total_quantity' => array_sum(array_column($newUsages, 'quantity')),
            'withHistoryFeedUsageDetail' => $withHistoryFeedUsageDetail
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
                $this->updateFeedUsageWithTracking($newUsages, $feedUsageId, $recordingId, $withHistoryFeedUsageDetail);
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

            // dd($newUsages, $usage);

            $this->feedUsageService->processWithMetadata($usage, $newUsages);
        }
    }

    private function updateFeedUsageWithTracking(array $newUsages, string $feedUsageId, string $recordingId, bool $withHistoryFeedUsageDetail = false): void
    {
        $usage = FeedUsage::findOrFail($feedUsageId);
        if ($withHistoryFeedUsageDetail) {
            $oldDetails = $usage->details;
            foreach ($oldDetails as $detail) {
                $stock = FeedStock::find($detail->feed_stock_id);
                if ($stock) {
                    $stock->quantity_used = max(0, $stock->quantity_used - $detail->quantity_taken);
                    $stock->save();
                }
                $detail->delete();
            }
            logInfoIfDebug("[FeedUsageDetail] Deleted and recreated all details for usage ID {$usage->id} (withHistoryFeedUsageDetail=true)");
            $usage->update([
                'recording_id' => $recordingId,
                'total_quantity' => array_sum(array_column($newUsages, 'quantity')),
                'metadata' => array_merge($usage->metadata ?? [], ['updated_at' => now()->toIso8601String(), 'updated_by' => Auth::id()]),
                'updated_by' => Auth::id(),
            ]);
            $this->feedUsageService->processWithMetadata($usage, $newUsages);
        } else {
            // Update existing details in-place (no delete/recreate)
            logInfoIfDebug("[FeedUsageDetail] Updating details in-place for usage ID {$usage->id} (withHistoryFeedUsageDetail=false)");
            // Map new usages by feed_id for easy lookup
            $newUsagesByFeedId = collect($newUsages)->keyBy('feed_id');
            foreach ($usage->details as $detail) {
                $feedId = $detail->feed_id;
                if ($newUsagesByFeedId->has($feedId)) {
                    $newQty = $newUsagesByFeedId[$feedId]['quantity'];
                    // Update quantity_taken if changed
                    if ($detail->quantity_taken != $newQty) {
                        // Adjust stock
                        $stock = FeedStock::find($detail->feed_stock_id);
                        if ($stock) {
                            $stock->quantity_used = max(0, $stock->quantity_used - $detail->quantity_taken + $newQty);
                            $stock->save();
                        }
                        $detail->quantity_taken = $newQty;
                        $detail->updated_by = Auth::id();
                        $detail->save();
                    }
                }
            }
            // Add new details for any new feed_id not present before
            $existingFeedIds = $usage->details->pluck('feed_id')->all();
            foreach ($newUsages as $row) {
                if (!in_array($row['feed_id'], $existingFeedIds)) {
                    // Find a stock to use (simplified: pick first available)
                    $stock = FeedStock::where('livestock_id', $usage->livestock_id)->where('feed_id', $row['feed_id'])->first();
                    if ($stock) {
                        FeedUsageDetail::create([
                            'feed_usage_id' => $usage->id,
                            'feed_stock_id' => $stock->id,
                            'feed_id' => $row['feed_id'],
                            'quantity_taken' => $row['quantity'],
                            'created_by' => Auth::id(),
                            'updated_by' => Auth::id(),
                        ]);
                    }
                }
            }
            $usage->update([
                'recording_id' => $recordingId,
                'total_quantity' => array_sum(array_column($newUsages, 'quantity')),
                'metadata' => array_merge($usage->metadata ?? [], ['updated_at' => now()->toIso8601String(), 'updated_by' => Auth::id()]),
                'updated_by' => Auth::id(),
            ]);
        }
        logInfoIfDebug("✅ Feed usage update complete for usage ID {$usage->id}");
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

            // Check if usage has valid status for processing
            $validStatuses = [
                SupplyUsage::STATUS_PENDING,
                SupplyUsage::STATUS_IN_PROCESS,
                SupplyUsage::STATUS_COMPLETED,
                SupplyUsage::STATUS_PARTIALLY_USED
            ];

            if (!in_array($usage->status, $validStatuses)) {
                logInfoIfDebug("⏭️ Skipping supply usage processing - invalid status", [
                    'usage_id' => $usage->id,
                    'status' => $usage->status,
                    'valid_statuses' => $validStatuses
                ]);
                return;
            }

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
                'status' => SupplyUsage::STATUS_DRAFT, // Default to draft status
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
        logInfoIfDebug("✅ Supply usage update complete for usage ID {$usage->id}");
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

        // Fix age calculation: calculate livestock age relative to depletion date
        $age = null;
        if ($livestock && $livestock->start_date) {
            $livestockStartDate = Carbon::parse($livestock->start_date);
            $depletionDate = Carbon::parse($date);
            // Calculate age: how old is the livestock on depletion date
            $age = $livestockStartDate->diffInDays($depletionDate, false);

            logDebugIfDebug('Livestock age calculation', [
                'livestock_id' => $livestockId,
                'livestock_start_date' => $livestockStartDate->format('Y-m-d'),
                'depletion_date' => $depletionDate->format('Y-m-d'),
                'calculated_age_days' => $age,
                'age_calculation' => 'depletion_date - livestock_start_date'
            ]);
        }

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

        // Get company config for depletion method
        $companyConfig = $this->getCompanyConfig();
        $depletionMethod = $this->getDepletionMethod($companyConfig, $normalizedType);

        logDebugIfDebug('Depletion method configuration', [
            'company_config_available' => !empty($companyConfig),
            'depletion_method' => $depletionMethod,
            'normalized_type' => $normalizedType
        ]);

        // Initialize batch breakdown data
        $batchBreakdown = [];
        $batchAllocationResult = null;

        // Process batch allocation if FIFO method is enabled
        if ($depletionMethod === 'fifo' && $delta != 0) {
            logDebugIfDebug('🔄 Processing FIFO batch allocation', [
                'livestock_id' => $livestockId,
                'delta' => $delta,
                'date' => $date,
                'operation' => $delta > 0 ? 'increment' : 'decrement'
            ]);

            try {
                // For negative delta (decrement), we need to handle differently
                if ($delta < 0) {
                    // Handle decrement case - we need to find and update existing batch allocations
                    $batchBreakdown = $this->handleDecrementDepletion($livestockId, $date, abs($delta), $normalizedType);
                    // Create allocation result for decrement case
                    $batchAllocationResult = [
                        'batches' => $batchBreakdown,
                        'success' => !empty($batchBreakdown),
                        'allocation_date' => $date,
                        'allocation_method' => $depletionMethod,
                        'allocated_quantity' => array_sum(array_column($batchBreakdown, 'quantity')),
                        'remaining_quantity' => 0,
                        'requested_quantity' => $delta
                    ];
                } else {
                    // Handle increment case - normal allocation
                    $batchAllocationResult = $this->allocateDepletionToBatches($livestockId, $date, $delta, $normalizedType);
                    $batchBreakdown = $batchAllocationResult['batches'] ?? [];
                }

                // Validate batch allocation result
                if (empty($batchBreakdown)) {
                    $errorMessage = $this->generateBatchAllocationError($livestockId, $delta, $normalizedType);
                    logErrorIfDebug('❌ Batch allocation validation failed', [
                        'livestock_id' => $livestockId,
                        'delta' => $delta,
                        'depletion_type' => $normalizedType,
                        'error_message' => $errorMessage
                    ]);

                    // Don't throw exception, let the process continue with empty batch breakdown
                    // Error details will be added in the data/metadata preparation
                    logWarningIfDebug('⚠️ Continuing with empty batch breakdown', [
                        'livestock_id' => $livestockId,
                        'delta' => $delta,
                        'depletion_type' => $normalizedType
                    ]);
                }

                logInfoIfDebug('✅ FIFO batch allocation completed', [
                    'batches_count' => count($batchBreakdown),
                    'total_allocated' => array_sum(array_column($batchBreakdown, 'quantity')),
                    'batch_details' => $batchBreakdown,
                    'operation' => $delta > 0 ? 'increment' : 'decrement'
                ]);

                // Update batch quantities in database
                $this->updateBatchDepletionQuantities($batchBreakdown);
            } catch (Exception $e) {
                logErrorIfDebug('❌ FIFO batch allocation failed', [
                    'error' => $e->getMessage(),
                    'livestock_id' => $livestockId,
                    'delta' => $delta
                ]);

                // Don't re-throw exception, let the process continue with empty batch breakdown
                // Error details will be added in the data/metadata preparation
                $batchBreakdown = [];
                $batchAllocationResult = [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'batches' => []
                ];

                logWarningIfDebug('⚠️ Continuing with empty batch breakdown after exception', [
                    'livestock_id' => $livestockId,
                    'delta' => $delta,
                    'error' => $e->getMessage()
                ]);
            }
        } else {
            logDebugIfDebug('⏭️ Skipping batch allocation', [
                'reason' => $depletionMethod !== 'fifo' ? 'method_not_fifo' : 'no_delta',
                'depletion_method' => $depletionMethod,
                'delta' => $delta
            ]);
        }

        // Prepare metadata with batch information
        $metadata = [
            'livestock_name' => $livestock->name ?? 'Unknown',
            'age_days' => $age,
            'updated_at' => now()->toIso8601String(),
            'updated_by' => Auth::id(),
            'depletion_method' => $depletionMethod,
            'delta_calculation' => ['old_value' => $oldJumlah, 'new_value' => $jumlah, 'delta' => $delta],
            'depletion_config' => ['original_type' => $jenis, 'normalized_type' => $normalizedType],
            'batch_allocation' => [
                'method' => $depletionMethod,
                'batches_count' => count($batchBreakdown),
                'total_allocated' => array_sum(array_column($batchBreakdown, 'quantity')),
                'batches' => $batchBreakdown,
                'allocation_success' => !empty($batchBreakdown)
            ]
        ];

        // Prepare data field with detailed batch breakdown
        $data = [
            'delta_info' => ['old_value' => $oldJumlah, 'new_value' => $jumlah, 'delta' => $delta],
            'batch_breakdown' => $batchBreakdown,
            'allocation_result' => $batchAllocationResult
        ];

        // Ensure allocation_result has consistent structure for successful cases
        if (!empty($batchBreakdown) && $depletionMethod === 'fifo' && $delta != 0) {
            // Ensure allocation_result is an array
            if (!is_array($data['allocation_result'])) {
                $data['allocation_result'] = [];
            }

            // If allocation_result doesn't have complete structure, enhance it
            if (!isset($data['allocation_result']['allocation_date'])) {
                $data['allocation_result'] = array_merge($data['allocation_result'], [
                    'allocation_date' => $date,
                    'allocation_method' => $depletionMethod,
                    'allocated_quantity' => array_sum(array_column($batchBreakdown, 'quantity')),
                    'remaining_quantity' => 0,
                    'requested_quantity' => $delta
                ]);
            }
        }

        // If batch allocation failed, add detailed error information
        if (empty($batchBreakdown) && $depletionMethod === 'fifo' && $delta != 0) {
            // Get available batches for error reporting
            $availableBatches = $livestock->batches()->where('status', 'active')->get();
            $totalAvailable = $availableBatches->sum('quantity_available');

            // Determine error message
            $errorMessage = 'No available batches found';
            if (isset($batchAllocationResult['error'])) {
                $errorMessage = $batchAllocationResult['error'];
            }

            $data['allocation_result'] = [
                'success' => false,
                'error' => $errorMessage,
                'error_details' => [
                    'requested_quantity' => $delta,
                    'available_batches_count' => $availableBatches->count(),
                    'total_available_quantity' => $totalAvailable,
                    'available_batches' => $availableBatches->map(function ($batch) {
                        return [
                            'batch_id' => $batch->id,
                            'batch_name' => $batch->name,
                            'quantity_available' => $batch->quantity_available,
                            'initial_quantity' => $batch->initial_quantity,
                            'start_date' => $batch->start_date
                        ];
                    })->toArray()
                ],
                'batches' => [],
                'allocation_date' => $date,
                'allocation_method' => $depletionMethod,
                'allocated_quantity' => 0,
                'remaining_quantity' => $delta,
                'requested_quantity' => $delta
            ];

            $metadata['batch_allocation']['error'] = $errorMessage;
            $metadata['batch_allocation']['error_details'] = [
                'requested_quantity' => $delta,
                'available_batches_count' => $availableBatches->count(),
                'total_available_quantity' => $totalAvailable
            ];
        }

        // Ensure allocation_result is always an array for consistency
        if (!is_array($data['allocation_result'])) {
            $data['allocation_result'] = [];
        }

        $deplesi = LivestockDepletion::updateOrCreate(
            ['livestock_id' => $livestockId, 'tanggal' => $date, 'jenis' => $normalizedType],
            [
                'jumlah' => $jumlah,
                'recording_id' => $recordingId,
                'method' => $depletionMethod,
                'metadata' => $metadata,
                'data' => $data,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id()
            ]
        );

        logInfoIfDebug('✅ Depletion saved/updated', [
            'depletion_id' => $deplesi->id,
            'jenis' => $deplesi->jenis,
            'jumlah' => $deplesi->jumlah,
            'method' => $deplesi->method,
            'recording_id' => $deplesi->recording_id,
            'was_created' => $deplesi->wasRecentlyCreated,
            'was_updated' => !$deplesi->wasRecentlyCreated,
            'batches_allocated' => count($batchBreakdown)
        ]);

        return $deplesi;
    }

    /**
     * Get company configuration for depletion settings
     */
    private function getCompanyConfig(): array
    {
        try {
            $user = Auth::user();
            if (!$user || !$user->company) {
                logWarningIfDebug('⚠️ No user or company found, using default config', [
                    'user_id' => $user?->id ?? 'null',
                    'company_id' => $user?->company_id ?? 'null'
                ]);
                return [];
            }

            $config = $user->company->config ?? [];
            logDebugIfDebug('Company config loaded', [
                'company_id' => $user->company_id,
                'config_keys' => array_keys($config),
                'has_livestock_config' => isset($config['livestock']),
                'has_purchasing_config' => isset($config['purchasing'])
            ]);

            return $config;
        } catch (Exception $e) {
            logErrorIfDebug('❌ Error loading company config', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Determine depletion method based on company configuration
     */
    private function getDepletionMethod(array $companyConfig, string $depletionType): string
    {
        // Check purchasing config first (livestock purchase batch settings)
        $purchasingConfig = $companyConfig['purchasing'] ?? [];
        $livestockPurchaseConfig = $purchasingConfig['livestock_purchase'] ?? [];
        $batchSettings = $livestockPurchaseConfig['batch_settings'] ?? [];
        $multipleBatchesConfig = $batchSettings['allow_multiple_batches'] ?? [];

        if (isset($multipleBatchesConfig['depletion_method'])) {
            $method = $multipleBatchesConfig['depletion_method'];
            logDebugIfDebug('Depletion method from purchasing config', [
                'method' => $method,
                'config_path' => 'purchasing.livestock_purchase.batch_settings.allow_multiple_batches.depletion_method'
            ]);
            return $method;
        }

        // Check livestock config as fallback
        $livestockConfig = $companyConfig['livestock'] ?? [];
        $depletionTrackingConfig = $livestockConfig['depletion_tracking'] ?? [];
        $typesConfig = $depletionTrackingConfig['types'] ?? [];
        $typeConfig = $typesConfig[$depletionType] ?? [];

        if (isset($typeConfig['batch_attribution'])) {
            $method = $typeConfig['batch_attribution'] === 'auto' ? 'fifo' : 'manual';
            logDebugIfDebug('Depletion method from livestock config', [
                'method' => $method,
                'batch_attribution' => $typeConfig['batch_attribution'],
                'config_path' => "livestock.depletion_tracking.types.{$depletionType}.batch_attribution"
            ]);
            return $method;
        }

        // Default to traditional if no config found
        logWarningIfDebug('⚠️ No depletion method config found, using traditional', [
            'company_config_keys' => array_keys($companyConfig),
            'depletion_type' => $depletionType
        ]);
        return 'traditional';
    }

    /**
     * Allocate depletion to batches using FIFO method
     */
    private function allocateDepletionToBatches(string $livestockId, string $date, int $quantity, string $depletionType): array
    {
        logDebugIfDebug('🔄 allocateDepletionToBatches called', [
            'livestock_id' => $livestockId,
            'date' => $date,
            'quantity' => $quantity,
            'depletion_type' => $depletionType
        ]);

        try {
            // Get livestock with batches
            $livestock = Livestock::with(['batches' => function ($query) {
                $query->where('status', 'active')
                    ->where('quantity_available', '>', 0) // Using quantity_available instead of raw calculation
                    ->orderBy('start_date', 'asc')
                    ->orderBy('id', 'asc');
            }])->find($livestockId);

            if (!$livestock) {
                throw new Exception("Livestock not found: {$livestockId}");
            }

            $availableBatches = $livestock->batches;
            logDebugIfDebug('Available batches found', [
                'batches_count' => $availableBatches->count(),
                'total_available' => $availableBatches->sum(function ($batch) {
                    return $batch->getQuantityAvailable();
                })
            ]);

            if ($availableBatches->isEmpty()) {
                logWarningIfDebug('⚠️ No available batches found for depletion allocation', [
                    'livestock_id' => $livestockId,
                    'quantity' => $quantity
                ]);
                return [
                    'success' => false,
                    'error' => 'No available batches found',
                    'batches' => []
                ];
            }

            $remainingQuantity = $quantity;
            $allocatedBatches = [];
            $mutationDate = Carbon::parse($date);

            foreach ($availableBatches as $batch) {
                if ($remainingQuantity <= 0) break;

                $availableInBatch = $batch->getQuantityAvailable();

                if ($availableInBatch <= 0) continue;

                $quantityToAllocate = min($remainingQuantity, $availableInBatch);

                // Fix age calculation: calculate how old the batch is relative to mutation date
                $batchAge = 0;
                if ($batch->start_date) {
                    $batchStartDate = Carbon::parse($batch->start_date);
                    // Calculate age: how many days old is the batch on mutation date
                    $batchAge = $batchStartDate->diffInDays($mutationDate, false);

                    logDebugIfDebug('Age calculation details', [
                        'batch_id' => $batch->id,
                        'batch_start_date' => $batchStartDate->format('Y-m-d'),
                        'mutation_date' => $mutationDate->format('Y-m-d'),
                        'calculated_age_days' => $batchAge,
                        'age_calculation' => 'mutation_date - batch_start_date'
                    ]);
                }

                $allocatedBatches[] = [
                    'batch_id' => $batch->id,
                    'batch_name' => $batch->name,
                    'start_date' => $batch->start_date,
                    'age_days' => $batchAge,
                    'initial_quantity' => $batch->initial_quantity,
                    'current_available' => $availableInBatch,
                    'quantity' => $quantityToAllocate,
                    'remaining_after_allocation' => $availableInBatch - $quantityToAllocate
                ];

                $remainingQuantity -= $quantityToAllocate;

                logDebugIfDebug('Batch allocated', [
                    'batch_id' => $batch->id,
                    'batch_name' => $batch->name,
                    'quantity_allocated' => $quantityToAllocate,
                    'remaining_quantity' => $remainingQuantity,
                    'batch_age_days' => $batchAge
                ]);
            }

            if ($remainingQuantity > 0) {
                logWarningIfDebug('⚠️ Not enough batch capacity for full depletion', [
                    'requested_quantity' => $quantity,
                    'allocated_quantity' => $quantity - $remainingQuantity,
                    'remaining_quantity' => $remainingQuantity,
                    'batches_used' => count($allocatedBatches)
                ]);
            }

            $result = [
                'success' => true,
                'requested_quantity' => $quantity,
                'allocated_quantity' => $quantity - $remainingQuantity,
                'remaining_quantity' => $remainingQuantity,
                'batches' => $allocatedBatches,
                'allocation_method' => 'fifo',
                'allocation_date' => $date
            ];

            logInfoIfDebug('✅ Batch allocation completed', [
                'total_allocated' => $result['allocated_quantity'],
                'batches_used' => count($allocatedBatches),
                'allocation_method' => 'fifo',
                'age_range' => [
                    'min_age' => min(array_column($allocatedBatches, 'age_days')),
                    'max_age' => max(array_column($allocatedBatches, 'age_days'))
                ]
            ]);

            return $result;
        } catch (Exception $e) {
            logErrorIfDebug('❌ Error in allocateDepletionToBatches', [
                'error' => $e->getMessage(),
                'livestock_id' => $livestockId,
                'quantity' => $quantity
            ]);
            throw $e;
        }
    }

    /**
     * Generate detailed error message for batch allocation failures
     */
    private function generateBatchAllocationError(string $livestockId, int $delta, string $depletionType): string
    {
        $livestock = Livestock::with(['batches' => function ($query) {
            $query->where('status', 'active');
        }])->find($livestockId);

        if (!$livestock) {
            return "Livestock tidak ditemukan untuk alokasi batch.";
        }

        $totalBatches = $livestock->batches->count();
        $availableBatches = $livestock->batches->where('quantity_available', '>', 0)->count();
        $totalAvailable = $livestock->getTotalAvailableQuantity();
        $operation = $delta > 0 ? 'menambah' : 'mengurangi';
        $depletionTypeLabel = $depletionType === 'mortality' ? 'kematian' : ($depletionType === 'culling' ? 'afkir' : $depletionType);

        if ($totalBatches === 0) {
            return "Tidak ada batch aktif untuk ternak '{$livestock->name}'. Silakan periksa data batch atau hubungi administrator.";
        }

        if ($availableBatches === 0) {
            return "Semua batch untuk ternak '{$livestock->name}' sudah habis (quantity_available = 0). Tidak dapat {$operation} {$depletionTypeLabel} sebanyak " . abs($delta) . " ekor.";
        }

        if ($delta > 0 && $totalAvailable < $delta) {
            return "Stok tersedia ({$totalAvailable} ekor) tidak cukup untuk {$operation} {$depletionTypeLabel} sebanyak {$delta} ekor. Silakan periksa data batch atau kurangi jumlah {$depletionTypeLabel}.";
        }

        return "Gagal mengalokasi {$depletionTypeLabel} ke batch. Total batch: {$totalBatches}, Batch tersedia: {$availableBatches}, Stok tersedia: {$totalAvailable}, Jumlah yang diminta: " . abs($delta) . " ekor.";
    }

    /**
     * Handle decrement depletion (when delta is negative)
     * This method handles the case when depletion quantity is reduced
     */
    private function handleDecrementDepletion(string $livestockId, string $date, int $decrementAmount, string $depletionType): array
    {
        logDebugIfDebug('🔄 handleDecrementDepletion called', [
            'livestock_id' => $livestockId,
            'date' => $date,
            'decrement_amount' => $decrementAmount,
            'depletion_type' => $depletionType
        ]);

        try {
            // Get livestock with batches
            $livestock = Livestock::with(['batches' => function ($query) {
                $query->where('status', 'active')
                    ->where('quantity_depletion', '>', 0) // Only batches with existing depletion
                    ->orderBy('start_date', 'desc') // Reverse FIFO for decrement (newest first)
                    ->orderBy('id', 'desc');
            }])->find($livestockId);

            if (!$livestock) {
                throw new Exception("Livestock not found: {$livestockId}");
            }

            $batchesWithDepletion = $livestock->batches;
            logDebugIfDebug('Batches with existing depletion found', [
                'batches_count' => $batchesWithDepletion->count(),
                'total_existing_depletion' => $batchesWithDepletion->sum('quantity_depletion')
            ]);

            if ($batchesWithDepletion->isEmpty()) {
                logWarningIfDebug('⚠️ No batches with existing depletion found for decrement', [
                    'livestock_id' => $livestockId,
                    'decrement_amount' => $decrementAmount
                ]);
                return [];
            }

            $remainingDecrement = $decrementAmount;
            $decrementBreakdown = [];

            foreach ($batchesWithDepletion as $batch) {
                if ($remainingDecrement <= 0) break;

                $currentDepletion = $batch->quantity_depletion;
                if ($currentDepletion <= 0) continue;

                $quantityToDecrement = min($remainingDecrement, $currentDepletion);

                // Fix: calculate age_days manually (replace getAgeDays)
                $age_days = null;
                if ($batch->start_date) {
                    $age_days = Carbon::parse($date)->diffInDays(Carbon::parse($batch->start_date));
                }
                $decrementBreakdown[] = [
                    'age_days' => $age_days,
                    'batch_id' => $batch->id,
                    'batch_name' => $batch->name,
                    'start_date' => $batch->start_date,
                    'initial_quantity' => $batch->initial_quantity,
                    'current_available' => $batch->getQuantityAvailable(),
                    'current_depletion' => $currentDepletion,
                    'quantity' => -$quantityToDecrement, // Negative for decrement
                    'remaining_after_decrement' => $currentDepletion - $quantityToDecrement,
                    'remaining_after_allocation' => $batch->getQuantityAvailable() // For consistency with create
                ];

                $remainingDecrement -= $quantityToDecrement;

                logDebugIfDebug('Batch decrement calculated', [
                    'batch_id' => $batch->id,
                    'batch_name' => $batch->name,
                    'current_depletion' => $currentDepletion,
                    'quantity_to_decrement' => $quantityToDecrement,
                    'remaining_decrement' => $remainingDecrement
                ]);
            }

            if ($remainingDecrement > 0) {
                logWarningIfDebug('⚠️ Not enough existing depletion to decrement', [
                    'requested_decrement' => $decrementAmount,
                    'actual_decrement' => $decrementAmount - $remainingDecrement,
                    'remaining_decrement' => $remainingDecrement,
                    'batches_used' => count($decrementBreakdown)
                ]);
            }

            logInfoIfDebug('✅ Decrement breakdown completed', [
                'total_decrement' => $decrementAmount - $remainingDecrement,
                'batches_affected' => count($decrementBreakdown),
                'decrement_method' => 'reverse_fifo'
            ]);

            return $decrementBreakdown;
        } catch (Exception $e) {
            logErrorIfDebug('❌ Error in handleDecrementDepletion', [
                'error' => $e->getMessage(),
                'livestock_id' => $livestockId,
                'decrement_amount' => $decrementAmount
            ]);
            throw $e;
        }
    }

    /**
     * Update batch depletion quantities in database
     */
    private function updateBatchDepletionQuantities(array $batchBreakdown): void
    {
        if (empty($batchBreakdown)) {
            logDebugIfDebug('⏭️ No batch breakdown to update', []);
            return;
        }

        logDebugIfDebug('🔄 Updating batch depletion quantities', [
            'batches_count' => count($batchBreakdown)
        ]);

        try {
            foreach ($batchBreakdown as $batchData) {
                $batchId = $batchData['batch_id'];
                $quantityToAdd = $batchData['quantity'];

                $batch = \App\Models\LivestockBatch::find($batchId);
                if (!$batch) {
                    logWarningIfDebug('⚠️ Batch not found for update', [
                        'batch_id' => $batchId,
                        'quantity_to_add' => $quantityToAdd
                    ]);
                    continue;
                }

                $oldQuantityDepletion = $batch->quantity_depletion;
                $batch->quantity_depletion += $quantityToAdd;

                // Auto-calculate quantity_available will be handled by model observer
                $batch->save();

                // Force recalculate to ensure accuracy
                $batch->recalculateQuantityAvailable();

                $operation = $quantityToAdd > 0 ? 'increment' : 'decrement';
                $operationAmount = abs($quantityToAdd);

                logDebugIfDebug('Batch depletion quantity updated', [
                    'batch_id' => $batchId,
                    'batch_name' => $batch->name,
                    'old_quantity_depletion' => $oldQuantityDepletion,
                    'new_quantity_depletion' => $batch->quantity_depletion,
                    'quantity_change' => $quantityToAdd,
                    'operation' => $operation,
                    'operation_amount' => $operationAmount,
                    'quantity_available' => $batch->getQuantityAvailable(),
                    'availability_percentage' => $batch->getAvailabilityPercentage(),
                    'availability_status' => $batch->getAvailabilityStatus()
                ]);
            }

            logInfoIfDebug('✅ All batch depletion quantities updated', [
                'batches_updated' => count($batchBreakdown)
            ]);
        } catch (Exception $e) {
            logErrorIfDebug('❌ Error updating batch depletion quantities', [
                'error' => $e->getMessage(),
                'batches_count' => count($batchBreakdown)
            ]);
            throw $e;
        }
    }

    public function getDetailedOutflowHistory($livestockId, $date): array
    {
        $recordings = Recording::where('livestock_id', $livestockId)->where('tanggal', '!=', $date)->get();
        $totalMortality = 0;
        $totalCulling = 0;
        $totalSales = 0;
        $totalSalesWeight = 0;
        $totalSalesValue = 0;
        $salesHistory = [];

        foreach ($recordings as $recording) {
            $payload = $recording->payload ?? [];
            $totalMortality += $payload['mortality'] ?? 0;
            $totalCulling += $payload['culling'] ?? 0;
            $totalSales += $payload['sales_quantity'] ?? 0;
            $totalSalesWeight += $payload['sales_weight'] ?? 0;
            $totalSalesValue += $payload['total_sales'] ?? 0;

            // Collect sales history data
            if (isset($payload['production']['sales']) && $payload['production']['sales']['quantity'] > 0) {
                $salesHistory[] = [
                    'date' => $recording->tanggal,
                    'quantity' => $payload['production']['sales']['quantity'] ?? 0,
                    'weight' => $payload['production']['sales']['weight'] ?? 0,
                    'price_per_unit' => $payload['production']['sales']['price_per_unit'] ?? 0,
                    'total_value' => $payload['production']['sales']['total_value'] ?? 0,
                    'is_finalized' => $payload['production']['sales']['is_finalized'] ?? false,
                    'recording_id' => $recording->id,
                ];
            }
        }

        return [
            'mortality' => $totalMortality,
            'culling' => $totalCulling,
            'sales' => [
                'total_quantity' => $totalSales,
                'total_weight' => $totalSalesWeight,
                'total_value' => $totalSalesValue,
                'history' => $salesHistory,
                'count' => count($salesHistory)
            ],
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

    /**
     * Compare only business data (feed, supply, depletion, sales) between new and previous payloads.
     * Ignore changes in recorded_at, recorded_by, and ignore duplicate history entries with same value.
     */
    protected function isBusinessDataChanged(array $fullPayload, array $previousPayload): bool
    {
        // Compare main business sections
        $fields = [
            'consumption.feed.items',
            'consumption.supply.items',
            'production.depletion',
            'production.sales',
        ];
        foreach ($fields as $field) {
            $new = data_get($fullPayload, $field);
            $old = data_get($previousPayload, $field);
            if ($new !== $old) {
                return true;
            }
        }
        return false;
    }

    public function saveOrUpdateRecording($data): Recording
    {
        logDebugIfDebug('🔄 saveOrUpdateRecording called', [
            'livestock_id' => $data['livestock_id'] ?? 'NOT_SET',
            'tanggal' => $data['tanggal'] ?? 'NOT_SET',
            'berat_hari_ini' => $data['berat_hari_ini'] ?? null,
            'stock_akhir' => $data['stock_akhir'] ?? null,
            'data_keys' => array_keys($data)
        ]);

        if (!isset($data['livestock_id']) || empty($data['livestock_id'])) {
            throw new Exception("livestock_id is required but not provided in data");
        }

        $livestock = Livestock::find($data['livestock_id']);
        if (!$livestock) {
            throw new Exception("Livestock not found with ID: " . $data['livestock_id']);
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

        $previousPayload = $existingRecording ? ($existingRecording->payload ?? []) : [];

        // 1. CEK PERUBAHAN DATA BISNIS SEBELUM UPDATE HISTORY
        if ($existingRecording && $previousPayload) {
            if (!$this->isBusinessDataChanged($fullPayload, $previousPayload)) {
                logInfoIfDebug('⏭️ No business data changes detected, skipping update.');
                return $existingRecording;
            }
        }

        // 2. Hanya update history jika memang ada perubahan data bisnis
        logDebugIfDebug('🔄 Building history for recording', [
            'livestock_id' => $data['livestock_id'],
            'tanggal' => $data['tanggal'],
            'has_previous_payload' => !empty($previousPayload)
        ]);

        $fullPayload['history'] = $this->buildHistory($fullPayload, $previousPayload);

        logDebugIfDebug('✅ History built successfully', [
            'history_keys' => array_keys($fullPayload['history'] ?? [])
        ]);

        // ...lanjutkan proses simpan seperti biasa
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

        // Persiapkan JSON untuk masing-masing bagian
        $dataOperasional = $this->generateOperasionalJson($fullPayload, $previousPayload);
        $dataAudit = $this->generateAuditJson($fullPayload);
        $dataGabungan = [
            'operational' => $dataOperasional,
            'audit' => $dataAudit,
        ];

        logDebugIfDebug('🔄 Executing database updateOrCreate', [
            'livestock_id' => $data['livestock_id'],
            'tanggal' => $data['tanggal']
        ]);

        $recording = Recording::updateOrCreate(
            ['livestock_id' => $data['livestock_id'], 'tanggal' => $data['tanggal']],
            array_merge($data, ['payload' => $fullPayload, 'created_by' => Auth::id(), 'updated_by' => Auth::id(), 'data_operational' => $dataOperasional, 'data_audit' => $dataAudit, 'data' => $dataGabungan])
        );

        logDebugIfDebug('✅ Database updateOrCreate completed', [
            'recording_id' => $recording->id,
            'was_created' => $recording->wasRecentlyCreated
        ]);

        // Tandai cost Livestock sebagai invalid hanya jika update existing recording
        if (!$recording->wasRecentlyCreated) {
            \App\Services\Livestock\LivestockCostService::markCostInvalid($data['livestock_id']);
        }

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
        $date = null,
        $validationStatus = [],
        $validatedData = [],
        bool $isSalesFinalized = false
    ): array {
        logInfoIfDebug('🔄 MODULAR_PATH: RecordingPersistenceService::buildStructuredPayload called.', [
            'livestockId' => $ternak->livestock->id,
            'date' => $date,
            'weight_today' => $weightToday,
            'mortality' => $mortality,
            'culling' => $culling,
            'itemQuantities_count' => count($usages),
            'supplyQuantities_count' => count($supplyUsages)
        ]);

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

        // Only use cumulative_feed_consumption in consumption.feed, never in history.feed
        $cumulativeFeedConsumption = $feedHistory['cumulative_feed_consumption'] ?? 0;
        unset($feedHistory['cumulative_feed_consumption']);
        $cumulativeSupplyConsumption = 0; // Not used in history
        logDebugIfDebug('StructuredPayload: cumulative_feed_consumption only in consumption.feed', [
            'cumulative_feed_consumption' => $cumulativeFeedConsumption
        ]);

        // Helper: convert empty array to empty object for history keys
        $normalizeHistory = function ($arr) {
            if (is_array($arr) && empty($arr)) {
                logDebugIfDebug('history.* normalized from [] to {}');
                return (object)[];
            }
            return $arr;
        };

        $payload = [
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
                    'average_weight' => $sales_quantity > 0 ? $sales_weight / $sales_quantity : 0,
                    'is_finalized' => $isSalesFinalized,
                    'status' => $isSalesFinalized ? 'finalized' : 'draft',
                    'batch_allocation' => [
                        'method' => 'fifo',
                        'status' => $isSalesFinalized ? 'allocated' : 'preview',
                        'breakdown' => [] // Will be populated by RecordingSaleService
                    ],
                    'metadata' => [
                        'created_at' => now()->toIso8601String(),
                        'finalized_at' => $isSalesFinalized ? now()->toIso8601String() : null,
                        'source' => 'RecordingPersistenceService'
                    ]
                ]
            ],
            'consumption' => [
                'feed' => [
                    'total_quantity' => $totalFeedUsage,
                    'total_cost' => $feedCost,
                    'items' => $usages,
                    'types_count' => count($usages),
                    'cost_per_kg' => $totalFeedUsage > 0 ? $feedCost / $totalFeedUsage : 0,
                    'cumulative_feed_consumption' => $cumulativeFeedConsumption
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
        // Normalize all empty arrays in history to objects recursively
        $payload['history'] = $this->normalizeEmptyArraysToObjects($payload['history']);
        // Inject validation_status if provided
        if (!empty($validationStatus)) {
            $payload['validation_status'] = $validationStatus;
        }
        // Inject validated_data if provided
        if (!empty($validatedData)) {
            $payload['validated_data'] = $validatedData;
            logDebugIfDebug('Injected validated_data into payload', [
                'validated_data_keys' => array_keys($validatedData)
            ]);
        }
        return $payload;
    }

    // Pastikan generateOperasionalJson tidak pernah inject cumulative_feed_consumption ke feed/history dan array kosong jadi object
    protected function generateOperasionalJson(array $payload, array $previousPayload): array
    {
        // Always update history to the latest robust version before generating operational data
        $payload['history'] = $this->buildHistory($payload, $previousPayload);

        $feedHistory = $payload['history']['feed'] ?? [];
        $feedHistoryArr = (array)$feedHistory;
        if (isset($feedHistoryArr['cumulative_feed_consumption'])) {
            unset($feedHistoryArr['cumulative_feed_consumption']);
            logWarningIfDebug('generateOperasionalJson: cumulative_feed_consumption dihapus dari history.feed');
        }
        // Normalize recursively
        $feedHistoryArr = $this->normalizeEmptyArraysToObjects($feedHistoryArr);
        return [
            'production' => $payload['production'] ?? [],
            'consumption' => $payload['consumption'] ?? [],
            'recording' => $payload['recording'] ?? [],
            'history' => $payload['history'] ?? [],
            // 'feed' => $feedHistoryArr,
            'performance' => $payload['performance'] ?? [],
            'environment' => $payload['environment'] ?? [],
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
        if (isset($feed->data['conversion_units']) && is_array($feed->data['conversion_units'])) {
            $conversionUnits = collect($feed->data['conversion_units']);
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

        // Get stocks with available quantity > 0
        $stocks = FeedStock::where('feed_id', $feedId)
            ->where('livestock_id', $livestockId)
            ->whereRaw('(quantity_in - quantity_used - quantity_mutated - COALESCE(quantity_reserved, 0)) > 0')
            ->with([
                'feedPurchase.supplier',
                'feedPurchase.expedition',
                'feedPurchase.feedPurchaseItems.unit',
                'feedPurchase.feedPurchaseItems.convertedUnit'
            ])
            ->get();

        if ($stocks->isEmpty()) {
            logDebugIfDebug('📊 No available stocks found', [
                'feed_id' => $feedId,
                'livestock_id' => $livestockId
            ]);
            return $result;
        }

        $prices = [];
        $origins = [];
        $purchaseDates = [];
        $availableStocks = [];

        foreach ($stocks as $stock) {
            // dd($stock->feedPurchase);
            $availableQuantity = $stock->quantity_in - $stock->quantity_used - $stock->quantity_mutated - ($stock->quantity_reserved ?? 0);

            if ($availableQuantity > 0) {
                $stockData = [
                    'id' => $stock->id,
                    'quantity_available' => $availableQuantity,
                    'quantity_in' => $stock->quantity_in,
                    'quantity_used' => $stock->quantity_used,
                    'quantity_mutated' => $stock->quantity_mutated,
                    'quantity_reserved' => $stock->quantity_reserved ?? 0,
                    'purchase_date' => $stock->feedPurchase->date ?? 'Unknown',
                    'created_at' => $stock->created_at,
                    'updated_at' => $stock->updated_at,
                ];

                // Add purchase information if available
                if ($stock->feedPurchase) {
                    $stockData['purchase_id'] = $stock->feedPurchase->id;
                    $stockData['purchase_date'] = $stock->feedPurchase->date;
                    $stockData['invoice_number'] = $stock->feedPurchase->invoice_number;
                    $stockData['do_number'] = $stock->feedPurchase->do_number;
                    $stockData['supplier_name'] = optional($stock->feedPurchase->supplier)->name ?? 'Unknown';
                    $stockData['expedition_name'] = optional($stock->feedPurchase->expedition)->name ?? null;

                    // Get price information from FeedPurchaseItem if available
                    $feedPurchaseItem = $stock->feedPurchase->feedPurchaseItems()
                        ->where('feed_id', $feedId)
                        ->first();

                    if ($feedPurchaseItem) {
                        $stockData['price_per_unit'] = $feedPurchaseItem->price_per_unit;
                        $stockData['price_per_converted_unit'] = $feedPurchaseItem->price_per_converted_unit;
                        $stockData['unit_name'] = optional($feedPurchaseItem->unit)->name ?? 'Unknown';
                        $stockData['converted_unit_name'] = optional($feedPurchaseItem->convertedUnit)->name ?? 'Unknown';
                        $stockData['purchase_date'] = optional($feedPurchaseItem->feedPurchase)->date ?? 'Unknown';

                        $prices[] = $feedPurchaseItem->price_per_converted_unit ?? ($feedPurchaseItem->price_per_unit ?? 0);
                    }

                    $purchaseDates[] = $stock->feedPurchase->date;
                    $origins[] = optional($stock->feedPurchase->supplier)->name ?? 'Unknown';
                }

                $availableStocks[] = $stockData;
            }
        }

        // Update result with collected data
        $result['available_stocks'] = $availableStocks;
        $result['stock_origins'] = array_unique($origins);
        $result['stock_purchase_dates'] = array_unique($purchaseDates);

        if (!empty($prices)) {
            $result['stock_prices'] = [
                'min_price' => min($prices),
                'max_price' => max($prices),
                'average_price' => array_sum($prices) / count($prices),
            ];
        }

        logDebugIfDebug('📊 Stock details collected', [
            'feed_id' => $feedId,
            'livestock_id' => $livestockId,
            'available_stocks_count' => count($availableStocks),
            'total_available_quantity' => array_sum(array_column($availableStocks, 'quantity_available')),
            'stock_breakdown' => array_map(function ($stock) {
                return [
                    'id' => $stock['id'],
                    'quantity_available' => $stock['quantity_available'],
                    'quantity_in' => $stock['quantity_in'],
                    'quantity_used' => $stock['quantity_used'],
                    'quantity_mutated' => $stock['quantity_mutated'],
                    'quantity_reserved' => $stock['quantity_reserved'],
                ];
            }, $availableStocks)
        ]);

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
        if (!$livestock) {
            logDebugIfDebug('📊 Livestock not found for supply stock details', [
                'supply_id' => $supplyId,
                'livestock_id' => $livestockId
            ]);
            return $result;
        }

        $stocks = SupplyStock::where('supply_id', $supplyId)
            ->where('farm_id', $livestock->farm_id)
            ->whereRaw('(quantity_in - quantity_used - quantity_mutated) > 0')
            ->with(['supplyPurchase'])
            ->get();

        if ($stocks->isEmpty()) {
            logDebugIfDebug('📊 No available supply stocks found', [
                'supply_id' => $supplyId,
                'farm_id' => $livestock->farm_id
            ]);
            return $result;
        }

        $prices = [];
        $origins = [];
        $purchaseDates = [];
        $availableStocks = [];

        foreach ($stocks as $stock) {
            $availableQuantity = $stock->quantity_in - $stock->quantity_used - $stock->quantity_mutated;

            if ($availableQuantity > 0) {
                $stockData = [
                    'id' => $stock->id,
                    'quantity_available' => $availableQuantity,
                    'quantity_in' => $stock->quantity_in,
                    'quantity_used' => $stock->quantity_used,
                    'quantity_mutated' => $stock->quantity_mutated,
                    'created_at' => $stock->created_at,
                    'updated_at' => $stock->updated_at,
                ];

                // Add purchase information if available
                if ($stock->supplyPurchase) {
                    $stockData['purchase_id'] = $stock->supplyPurchase->id;
                    $stockData['purchase_date'] = $stock->supplyPurchase->purchase_date;
                    $stockData['price_per_unit'] = $stock->supplyPurchase->price_per_unit;
                    $stockData['price_per_converted_unit'] = $stock->supplyPurchase->price_per_converted_unit;
                    $stockData['supplier_name'] = $stock->supplyPurchase->supplier_name;

                    $prices[] = $stock->supplyPurchase->price_per_converted_unit ?? ($stock->supplyPurchase->price_per_unit ?? 0);
                    $purchaseDates[] = $stock->supplyPurchase->purchase_date;
                    $origins[] = $stock->supplyPurchase->supplier_name ?? 'Unknown';
                }

                $availableStocks[] = $stockData;
            }
        }

        // Update result with collected data
        $result['available_stocks'] = $availableStocks;
        $result['stock_origins'] = array_unique($origins);
        $result['stock_purchase_dates'] = array_unique($purchaseDates);

        if (!empty($prices)) {
            $result['stock_prices'] = [
                'min_price' => min($prices),
                'max_price' => max($prices),
                'average_price' => array_sum($prices) / count($prices),
            ];
        }

        logDebugIfDebug('📊 Supply stock details collected', [
            'supply_id' => $supplyId,
            'livestock_id' => $livestockId,
            'available_stocks_count' => count($availableStocks),
            'total_available_quantity' => array_sum(array_column($availableStocks, 'quantity_available')),
            'stock_breakdown' => array_map(function ($stock) {
                return [
                    'id' => $stock['id'],
                    'quantity_available' => $stock['quantity_available'],
                    'quantity_in' => $stock['quantity_in'],
                    'quantity_used' => $stock['quantity_used'],
                    'quantity_mutated' => $stock['quantity_mutated'],
                ];
            }, $availableStocks)
        ]);

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

    protected function generateAuditJson(array $payload): array
    {
        return [
            'validation' => $payload['validation'] ?? [],
            'config' => $payload['config'] ?? [],
        ];
    }

    // Helper: recursively convert all empty arrays to empty objects in a given array
    private function normalizeEmptyArraysToObjects($data)
    {
        if (is_array($data)) {
            if (empty($data)) {
                logDebugIfDebug('normalizeEmptyArraysToObjects: normalized [] to {}');
                return (object)[];
            }
            foreach ($data as $k => $v) {
                $data[$k] = $this->normalizeEmptyArraysToObjects($v);
            }
        }
        return $data;
    }

    /**
     * Build history of last 5 changes for feed, supply, depletion, and sales from payloads.
     * Robust: use (date, quantity) as change comparator for feed and depletion. Only add if unique or value changed for the same date.
     * Date is always stored as datetime for robustness.
     */
    protected function buildHistory(array $newPayload, array $previousPayload = []): array
    {
        $userId = data_get($newPayload, 'recorded_by.id');
        $feedItems = data_get($newPayload, 'consumption.feed.items', []);
        $prevFeed = $previousPayload['history']['feed_changes'] ?? [];
        $supplyItems = data_get($newPayload, 'consumption.supply.items', []);
        $prevSupply = $previousPayload['history']['supply_changes'] ?? [];
        $prevSales = $previousPayload['history']['sales_changes'] ?? [];

        // --- FEED HISTORY ---
        // Use only date (Y-m-d) and quantity as key, but keep all unique changes for a date (append-only, non-destructive)
        $feedHistory = $prevFeed;
        foreach ($feedItems as $item) {
            $dateRaw = $item['timestamp'] ?? $item['date'] ?? data_get($newPayload, 'recording.date');
            $dateKey = substr($dateRaw, 0, 10);
            $quantity = is_numeric($item['quantity']) ? (string)$item['quantity'] : $item['quantity'];
            $newEntry = [
                'id' => $item['feed_id'] ?? null,
                'date' => $dateRaw,
                'total_quantity' => $quantity,
                'created_by' => $userId,
                'notes' => $item['notes'] ?? '',
                // Add feed_stock_id and unit_id if available
                'feed_stock_id' => $item['feed_stock_id'] ?? (isset($item['available_stocks'][0]['id']) ? $item['available_stocks'][0]['id'] : null),
                'unit_id' => $item['unit_id'] ?? null,
            ];
            // Only add if not already present (date+quantity)
            $exists = false;
            foreach ($feedHistory as $entry) {
                if (substr($entry['date'], 0, 10) === $dateKey && (string)$entry['total_quantity'] === $quantity) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $feedHistory[] = $newEntry;
            }
        }
        usort($feedHistory, function ($a, $b) {
            return strtotime($b['date']) <=> strtotime($a['date']);
        });
        $feedChanges = array_slice($feedHistory, 0, 5);

        // --- SUPPLY HISTORY ---
        $supplyHistory = $prevSupply;
        foreach ($supplyItems as $item) {
            $dateRaw = $item['timestamp'] ?? $item['date'] ?? data_get($newPayload, 'recording.date');
            $dateKey = substr($dateRaw, 0, 10);
            $quantity = is_numeric($item['quantity']) ? (string)$item['quantity'] : $item['quantity'];
            $id = $item['id'] ?? null;
            $newEntry = [
                'id' => $id,
                'date' => $dateRaw,
                'total_quantity' => $quantity,
                'created_by' => $userId,
                'notes' => $item['notes'] ?? '',
            ];
            $exists = false;
            foreach ($supplyHistory as $entry) {
                if (substr($entry['date'], 0, 10) === $dateKey && (string)$entry['total_quantity'] === $quantity && $entry['id'] == $id) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $supplyHistory[] = $newEntry;
            }
        }
        usort($supplyHistory, function ($a, $b) {
            return strtotime($b['date']) <=> strtotime($a['date']);
        });
        $supplyChanges = array_slice($supplyHistory, 0, 5);

        // --- DEPLETION HISTORY ---
        $depletionItems = [];
        foreach (['mortality', 'culling'] as $type) {
            $jumlah = data_get($newPayload, "production.depletion.$type", 0);
            if ($jumlah > 0) {
                $dateRaw = data_get($newPayload, 'recording.timestamp') ?? data_get($newPayload, 'recording.date');
                $dateKey = substr($dateRaw, 0, 10);
                // Try to get batch id for this depletion type (for future multi-batch support)
                $batchId = data_get($newPayload, "production.depletion.{$type}_batch_id", null);
                $depletionItems[] = [
                    'jenis' => $type,
                    'jumlah' => $jumlah,
                    'date' => $dateRaw,
                    'date_key' => $dateKey,
                    'livestock_batch_id' => $batchId,
                ];
            }
        }
        $prevDepletion = $previousPayload['history']['depletion_changes'] ?? [];
        $depletionHistory = $prevDepletion;
        foreach ($depletionItems as $item) {
            $dateKey = $item['date_key'];
            $jumlah = is_numeric($item['jumlah']) ? (string)$item['jumlah'] : $item['jumlah'];
            $jenis = $item['jenis'];
            $dateRaw = $item['date'];
            $newEntry = [
                'id' => $jenis . '-' . $dateRaw,
                'date' => $dateRaw,
                'jenis' => $jenis,
                'jumlah' => $jumlah,
                'created_by' => $userId,
                'livestock_batch_id' => $item['livestock_batch_id'] ?? null,
            ];
            $exists = false;
            foreach ($depletionHistory as $entry) {
                if (substr($entry['date'], 0, 10) === $dateKey && (string)$entry['jumlah'] === $jumlah && $entry['jenis'] === $jenis) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $depletionHistory[] = $newEntry;
            }
        }
        usort($depletionHistory, function ($a, $b) {
            return strtotime($b['date']) <=> strtotime($a['date']);
        });
        $depletionChanges = array_slice($depletionHistory, 0, 5);

        // --- SALES HISTORY ---
        $salesItems = [];
        $salesQuantity = data_get($newPayload, 'production.sales.quantity', 0);
        if ($salesQuantity > 0) {
            $dateRaw = data_get($newPayload, 'recording.timestamp') ?? data_get($newPayload, 'recording.date');
            $dateKey = substr($dateRaw, 0, 10);
            $id = data_get($newPayload, 'production.sales.id') ?? null;
            $customer = data_get($newPayload, 'production.sales.customer_name') ?? 'Unknown';
            $salesWeight = data_get($newPayload, 'production.sales.weight', 0);
            $salesItems[] = [
                'id' => $id,
                'date' => $dateRaw,
                'date_key' => $dateKey,
                'quantity' => (string)$salesQuantity,
                'weight' => (string)$salesWeight,
                'customer_name' => $customer,
                'created_by' => $userId,
            ];
        }
        $prevSales = $previousPayload['history']['sales_changes'] ?? [];
        $salesHistory = $prevSales;
        foreach ($salesItems as $item) {
            $dateKey = $item['date_key'];
            $quantity = $item['quantity'];
            $id = $item['id'];
            $customer = $item['customer_name'];
            $dateRaw = $item['date'];
            $weight = $item['weight'];
            $newEntry = [
                'id' => $id,
                'date' => $dateRaw,
                'quantity' => $quantity,
                'weight' => $weight,
                'customer_name' => $customer,
                'created_by' => $userId,
            ];
            $exists = false;
            foreach ($salesHistory as $entry) {
                if (substr($entry['date'], 0, 10) === $dateKey && (string)$entry['quantity'] === $quantity && $entry['id'] === $id && $entry['customer_name'] === $customer) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $salesHistory[] = $newEntry;
            }
        }
        usort($salesHistory, function ($a, $b) {
            return strtotime($b['date']) <=> strtotime($a['date']);
        });
        $salesChanges = array_slice($salesHistory, 0, 5);

        $prevWeight = $previousPayload['history']['weight_changes'] ?? [];
        $weightHistory = $prevWeight;
        $weightToday = data_get($newPayload, 'production.weight.today', null);
        $dateRaw = data_get($newPayload, 'recording.timestamp') ?? data_get($newPayload, 'recording.date');
        $dateKey = substr($dateRaw, 0, 10);
        if ($weightToday !== null) {
            $newEntry = [
                'date' => $dateRaw,
                'weight_today' => (string)$weightToday,
                'created_by' => $userId,
            ];
            $exists = false;
            foreach ($weightHistory as $entry) {
                if (substr($entry['date'], 0, 10) === $dateKey && (string)$entry['weight_today'] === (string)$weightToday) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $weightHistory[] = $newEntry;
            }
        }
        usort($weightHistory, function ($a, $b) {
            return strtotime($b['date']) <=> strtotime($a['date']);
        });
        $weightChanges = array_slice($weightHistory, 0, 5);

        return [
            'feed_changes' => $feedChanges,
            'supply_changes' => $supplyChanges,
            'depletion_changes' => $depletionChanges,
            'sales_changes' => $salesChanges,
            'weight_changes' => $weightChanges,
        ];
    }

    /**
     * Store virtual sales data in LivestockBatch data column
     */
    private function storeVirtualSalesDataInBatches(string $livestockId, string $date, int $quantity, float $weight): void
    {
        try {
            $batches = \App\Models\LivestockBatch::where('livestock_id', $livestockId)
                ->where('status', 'active')
                ->get();

            if ($batches->isEmpty()) {
                logWarningIfDebug('No active batches found for virtual data storage', [
                    'livestock_id' => $livestockId,
                    'date' => $date
                ]);
                return;
            }

            // Simple distribution: put all data in the first active batch
            $batch = $batches->first();
            $existingData = $batch->data ?? [];

            // Initialize virtual data structure if not exists
            if (!isset($existingData['virtual_sales'])) {
                $existingData['virtual_sales'] = [];
            }

            // Store virtual sales data
            $existingData['virtual_sales'][$date] = [
                'quantity' => $quantity,
                'weight' => $weight,
                'date' => $date,
                'status' => 'draft',
                'metadata' => [
                    'calculation_method' => 'recording_persistence_service',
                    'calculation_timestamp' => now()->toIso8601String(),
                    'price_per_unit' => 0,
                    'amount' => 0,
                    'allocation_order' => 0,
                    'source' => 'RecordingPersistenceService::processSalesData'
                ]
            ];

            // Update batch data
            $batch->update([
                'data' => $existingData,
                'updated_at' => now()
            ]);

            logInfoIfDebug('✅ Virtual sales data stored in batch', [
                'batch_id' => $batch->id,
                'batch_name' => $batch->name,
                'livestock_id' => $livestockId,
                'date' => $date,
                'virtual_quantity' => $quantity,
                'virtual_weight' => $weight
            ]);
        } catch (Exception $e) {
            logErrorIfDebug('Failed to store virtual sales data in batches', [
                'livestock_id' => $livestockId,
                'date' => $date,
                'quantity' => $quantity,
                'weight' => $weight,
                'error' => $e->getMessage()
            ]);
        }
    }
}

<?php

namespace App\Services\Livestock;

use App\Models\Livestock;
use App\Models\LivestockBatch;
use App\Models\LivestockDepletion;
use App\Models\CurrentLivestock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use Carbon\Carbon;
use App\Config\CompanyConfig;

/**
 * Livestock Batch Depletion Service
 * 
 * Comprehensive service for handling livestock batch depletion with multiple methods:
 * - FIFO (First In First Out) - default
 * - LIFO (Last In First Out) 
 * - Manual (User selected batch)
 * 
 * Features:
 * - Robust error handling and validation
 * - Automatic batch selection based on depletion method
 * - Real-time quantity calculations
 * - Audit trail and logging
 * - Transaction safety
 * - Future-proof extensible design
 */
class BatchDepletionService
{
    /**
     * Supported depletion methods
     */
    const DEPLETION_METHODS = [
        'fifo' => 'First In First Out',
        'lifo' => 'Last In First Out',
        'manual' => 'Manual Selection'
    ];

    /**
     * Depletion types
     */
    const DEPLETION_TYPES = [
        'mortality' => 'Kematian',
        'sales' => 'Penjualan',
        'mutation' => 'Mutasi',
        'culling' => 'Afkir',
        'other' => 'Lainnya'
    ];

    /**
     * Process livestock depletion with automatic batch selection
     *
     * @param array $depletionData
     * @return array
     * @throws Exception
     */
    public function processDepletion(array $depletionData): array
    {
        // Validate input data
        $this->validateDepletionData($depletionData);

        // Get livestock and configuration
        $livestock = Livestock::findOrFail($depletionData['livestock_id']);
        $config = $livestock->getRecordingMethodConfig();
        $depletionMethod = $depletionData['depletion_method'] ?? $config['batch_settings']['depletion_method'] ?? 'fifo';

        // Determine processing method
        $recordingValidation = $livestock->validateBatchRecording();

        if (!$recordingValidation['valid']) {
            throw new Exception("Batch recording validation failed: " . $recordingValidation['message']);
        }

        // Process based on recording method and depletion method
        if ($recordingValidation['method'] === 'batch') {
            if ($depletionMethod === 'manual') {
                return $this->processManualBatchDepletion($livestock, $depletionData);
            } else {
                return $this->processBatchDepletion($livestock, $depletionData, $depletionMethod);
            }
        } else {
            return $this->processTotalDepletion($livestock, $depletionData);
        }
    }

    /**
     * Process batch-based depletion with FIFO method
     *
     * @param Livestock $livestock
     * @param array $depletionData
     * @param string $depletionMethod
     * @return array
     */
    public function processBatchDepletion(Livestock $livestock, array $depletionData, string $depletionMethod = 'fifo'): array
    {
        return DB::transaction(function () use ($livestock, $depletionData, $depletionMethod) {
            $results = [];
            $remainingQuantity = $depletionData['quantity'];
            $processedBatches = [];

            Log::info('🎯 Starting batch depletion process', [
                'livestock_id' => $livestock->id,
                'livestock_name' => $livestock->name,
                'total_quantity' => $depletionData['quantity'],
                'depletion_method' => $depletionMethod,
                'depletion_type' => $depletionData['type']
            ]);

            // Get available batches based on depletion method
            $availableBatches = $this->getAvailableBatchesForDepletion($livestock, $depletionMethod);

            if ($availableBatches->isEmpty()) {
                throw new Exception("No available batches found for depletion");
            }

            // Process depletion across batches
            foreach ($availableBatches as $batch) {
                if ($remainingQuantity <= 0) {
                    break;
                }

                $batchResult = $this->depleteBatch($batch, $remainingQuantity, $depletionData);

                if ($batchResult['depleted_quantity'] > 0) {
                    $processedBatches[] = $batchResult;
                    $remainingQuantity -= $batchResult['depleted_quantity'];

                    Log::info('✅ Batch depleted', [
                        'batch_id' => $batch->id,
                        'batch_name' => $batch->name,
                        'depleted_quantity' => $batchResult['depleted_quantity'],
                        'remaining_in_batch' => $batchResult['remaining_quantity'],
                        'remaining_to_process' => $remainingQuantity
                    ]);
                }
            }

            // Validate that all quantity was processed
            if ($remainingQuantity > 0) {
                throw new Exception("Insufficient quantity in available batches. Remaining: {$remainingQuantity}");
            }

            // Update livestock totals
            $this->updateLivestockTotals($livestock);

            $results = [
                'success' => true,
                'livestock_id' => $livestock->id,
                'total_depleted' => $depletionData['quantity'],
                'processed_batches' => $processedBatches,
                'depletion_method' => $depletionMethod,
                'message' => 'Batch depletion completed successfully'
            ];

            Log::info('🎉 Batch depletion process completed', $results);

            return $results;
        });
    }

    /**
     * Deplete specific quantity from a batch
     *
     * @param LivestockBatch $batch
     * @param int $requestedQuantity
     * @param array $depletionData
     * @return array
     */
    private function depleteBatch(LivestockBatch $batch, int $requestedQuantity, array $depletionData): array
    {
        // Calculate available quantity in this batch
        $availableQuantity = $batch->initial_quantity - $batch->quantity_depletion - $batch->quantity_sales - $batch->quantity_mutated;

        // Determine how much to deplete from this batch
        $depletionQuantity = min($requestedQuantity, $availableQuantity);

        if ($depletionQuantity <= 0) {
            return [
                'batch_id' => $batch->id,
                'depleted_quantity' => 0,
                'remaining_quantity' => $availableQuantity,
                'reason' => 'No available quantity in batch'
            ];
        }

        // Create depletion record
        $depletion = LivestockDepletion::create([
            'livestock_id' => $batch->livestock_id,
            'tanggal' => $depletionData['date'] ?? now(),
            'jumlah' => $depletionQuantity,
            'jenis' => $depletionData['type'],
            'data' => [
                'batch_id' => $batch->id,
                'batch_name' => $batch->name,
                'batch_start_date' => $batch->start_date,
                'depletion_method' => $depletionData['depletion_method'] ?? 'fifo',
                'original_request' => $requestedQuantity,
                'available_in_batch' => $availableQuantity,
                'manual_batch_note' => $depletionData['manual_batch_note'] ?? null,
                'is_edit_replacement' => $depletionData['is_edit_replacement'] ?? false,
                'reason' => $depletionData['reason'] ?? null
            ],
            'metadata' => [
                'processed_at' => now()->toISOString(),
                'processed_by' => auth()->id(),
                'processing_method' => 'batch_depletion_service',
                'edit_mode' => $depletionData['is_edit_replacement'] ?? false,
                'batch_metadata' => [
                    'age_days' => $batch->start_date ? now()->diffInDays($batch->start_date) : null,
                    'initial_quantity' => $batch->initial_quantity,
                    'previous_depletion' => $batch->quantity_depletion,
                    'previous_sales' => $batch->quantity_sales,
                    'previous_mutated' => $batch->quantity_mutated
                ]
            ],
            'created_by' => auth()->id()
        ]);

        // Update batch quantities
        $this->updateBatchQuantities($batch, $depletionData['type'], $depletionQuantity);

        return [
            'batch_id' => $batch->id,
            'batch_name' => $batch->name,
            'depleted_quantity' => $depletionQuantity,
            'remaining_quantity' => $availableQuantity - $depletionQuantity,
            'depletion_record_id' => $depletion->id,
            'age_days' => $batch->start_date ? now()->diffInDays($batch->start_date) : null
        ];
    }

    /**
     * Update batch quantities based on depletion type
     *
     * @param LivestockBatch $batch
     * @param string $depletionType
     * @param int $quantity
     * @return void
     */
    private function updateBatchQuantities(LivestockBatch $batch, string $depletionType, int $quantity): void
    {
        switch ($depletionType) {
            case 'mortality':
            case 'culling':
            case 'other':
                $batch->quantity_depletion += $quantity;
                break;
            case 'sales':
                $batch->quantity_sales += $quantity;
                break;
            case 'mutation':
                $batch->quantity_mutated += $quantity;
                break;
            default:
                $batch->quantity_depletion += $quantity;
        }

        $batch->save();
    }

    /**
     * Process total-based depletion (for single batch livestock)
     *
     * @param Livestock $livestock
     * @param array $depletionData
     * @return array
     */
    public function processTotalDepletion(Livestock $livestock, array $depletionData): array
    {
        return DB::transaction(function () use ($livestock, $depletionData) {
            Log::info('📊 Processing total depletion', [
                'livestock_id' => $livestock->id,
                'quantity' => $depletionData['quantity'],
                'type' => $depletionData['type']
            ]);

            // Create depletion record
            $depletion = LivestockDepletion::create([
                'livestock_id' => $livestock->id,
                'tanggal' => $depletionData['date'] ?? now(),
                'jumlah' => $depletionData['quantity'],
                'jenis' => $depletionData['type'],
                'data' => [
                    'processing_method' => 'total',
                    'reason' => 'Single batch or total recording method'
                ],
                'metadata' => [
                    'processed_at' => now()->toISOString(),
                    'processed_by' => auth()->id(),
                    'processing_method' => 'total_depletion_service'
                ],
                'created_by' => auth()->id()
            ]);

            // Update livestock totals
            $this->updateLivestockTotals($livestock);

            return [
                'success' => true,
                'livestock_id' => $livestock->id,
                'total_depleted' => $depletionData['quantity'],
                'depletion_record_id' => $depletion->id,
                'processing_method' => 'total',
                'message' => 'Total depletion completed successfully'
            ];
        });
    }

    /**
     * Get available batches for depletion based on method
     *
     * @param Livestock $livestock
     * @param string $method
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAvailableBatchesForDepletion(Livestock $livestock, string $method = 'fifo'): \Illuminate\Database\Eloquent\Collection
    {
        $query = $livestock->batches()
            ->where('status', 'active')
            ->whereRaw('(initial_quantity - quantity_depletion - quantity_sales - quantity_mutated) > 0');

        switch ($method) {
            case 'fifo':
                return $query->orderBy('start_date', 'asc')->get();
            case 'lifo':
                return $query->orderBy('start_date', 'desc')->get();
            case 'manual':
                return $query->orderBy('start_date', 'asc')->get();
            default:
                return $query->orderBy('start_date', 'asc')->get();
        }
    }

    /**
     * Update livestock total quantities
     *
     * @param Livestock $livestock
     * @return void
     */
    private function updateLivestockTotals(Livestock $livestock): void
    {
        // Calculate total depletion from all records
        $totalDepletion = LivestockDepletion::where('livestock_id', $livestock->id)->sum('jumlah');

        // Update livestock
        $livestock->update([
            'quantity_depletion' => $totalDepletion,
            'updated_by' => auth()->id()
        ]);

        // Update CurrentLivestock
        $this->updateCurrentLivestock($livestock);
    }

    /**
     * Update CurrentLivestock with real-time calculation
     *
     * @param Livestock $livestock
     * @return void
     */
    private function updateCurrentLivestock(Livestock $livestock): void
    {
        $currentLivestock = CurrentLivestock::where('livestock_id', $livestock->id)->first();

        if (!$currentLivestock) {
            Log::warning('CurrentLivestock not found', ['livestock_id' => $livestock->id]);
            return;
        }

        // Calculate real-time quantity
        $calculatedQuantity = $livestock->initial_quantity
            - ($livestock->quantity_depletion ?? 0)
            - ($livestock->quantity_sales ?? 0)
            - ($livestock->quantity_mutated ?? 0);

        $calculatedQuantity = max(0, $calculatedQuantity);

        $currentLivestock->update([
            'quantity' => $calculatedQuantity,
            'metadata' => array_merge($currentLivestock->metadata ?? [], [
                'last_updated' => now()->toISOString(),
                'updated_by' => auth()->id(),
                'calculation_source' => 'batch_depletion_service',
                'formula_breakdown' => [
                    'initial_quantity' => $livestock->initial_quantity,
                    'quantity_depletion' => $livestock->quantity_depletion ?? 0,
                    'quantity_sales' => $livestock->quantity_sales ?? 0,
                    'quantity_mutated' => $livestock->quantity_mutated ?? 0,
                    'calculated_quantity' => $calculatedQuantity
                ]
            ]),
            'updated_by' => auth()->id()
        ]);
    }

    /**
     * Validate depletion data
     *
     * @param array $data
     * @return void
     * @throws Exception
     */
    private function validateDepletionData(array $data): void
    {
        // Basic required fields for all depletion types
        $basicRequired = ['livestock_id', 'type'];

        foreach ($basicRequired as $field) {
            if (!isset($data[$field])) {
                throw new Exception("Required field '{$field}' is missing");
            }
        }

        if (!in_array($data['type'], array_keys(self::DEPLETION_TYPES))) {
            throw new Exception("Invalid depletion type: {$data['type']}");
        }

        // Validate livestock exists
        if (!Livestock::find($data['livestock_id'])) {
            throw new Exception("Livestock not found: {$data['livestock_id']}");
        }

        // For manual depletion method, validate manual_batches instead of quantity
        if (isset($data['depletion_method']) && $data['depletion_method'] === 'manual') {
            if (!isset($data['manual_batches']) || !is_array($data['manual_batches'])) {
                throw new Exception("Manual depletion method requires 'manual_batches' array");
            }

            if (empty($data['manual_batches'])) {
                throw new Exception("Manual depletion method requires at least one batch selection");
            }

            // Validate each manual batch
            foreach ($data['manual_batches'] as $index => $batchData) {
                try {
                    $this->validateManualBatchData($batchData);
                } catch (Exception $e) {
                    throw new Exception("Manual batch #{$index}: " . $e->getMessage());
                }
            }
        } else {
            // For non-manual methods, quantity is required
            if (!isset($data['quantity'])) {
                throw new Exception("Required field 'quantity' is missing");
            }

            if ($data['quantity'] <= 0) {
                throw new Exception("Quantity must be greater than 0");
            }
        }
    }

    /**
     * Validate manual batch data structure
     *
     * @param array $batchData
     * @return void
     * @throws Exception
     */
    private function validateManualBatchData(array $batchData): void
    {
        $required = ['batch_id', 'quantity'];

        foreach ($required as $field) {
            if (!isset($batchData[$field])) {
                throw new Exception("Manual batch data missing required field: {$field}");
            }
        }

        if ($batchData['quantity'] <= 0) {
            throw new Exception("Manual batch quantity must be greater than 0");
        }

        // Check if batch exists and is active
        $batch = LivestockBatch::find($batchData['batch_id']);
        if (!$batch) {
            throw new Exception("Batch not found: {$batchData['batch_id']}");
        }

        if ($batch->status !== 'active') {
            throw new Exception("Batch {$batch->name} is not active (status: {$batch->status})");
        }
    }

    /**
     * Get depletion summary for livestock
     *
     * @param string $livestockId
     * @return array
     */
    public function getDepletionSummary(string $livestockId): array
    {
        $livestock = Livestock::findOrFail($livestockId);
        $depletions = LivestockDepletion::where('livestock_id', $livestockId)->get();

        return [
            'livestock_id' => $livestockId,
            'livestock_name' => $livestock->name,
            'total_depletion' => $depletions->sum('jumlah'),
            'depletion_by_type' => $depletions->groupBy('jenis')->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'total_quantity' => $group->sum('jumlah')
                ];
            }),
            'depletion_by_batch' => $depletions->where('data.batch_id', '!=', null)
                ->groupBy('data.batch_id')
                ->map(function ($group) {
                    return [
                        'batch_name' => $group->first()->data['batch_name'] ?? 'Unknown',
                        'count' => $group->count(),
                        'total_quantity' => $group->sum('jumlah')
                    ];
                }),
            'recent_depletions' => $depletions->sortByDesc('created_at')->take(10)->values()
        ];
    }

    /**
     * Preview depletion without executing
     *
     * @param array $depletionData
     * @return array
     */
    public function previewDepletion(array $depletionData): array
    {
        $this->validateDepletionData($depletionData);

        $livestock = Livestock::findOrFail($depletionData['livestock_id']);
        $config = $livestock->getRecordingMethodConfig();
        $depletionMethod = $config['batch_settings']['depletion_method'] ?? 'fifo';

        $recordingValidation = $livestock->validateBatchRecording();

        if ($recordingValidation['method'] === 'batch') {
            $availableBatches = $this->getAvailableBatchesForDepletion($livestock, $depletionMethod);

            $preview = [];
            $remainingQuantity = $depletionData['quantity'];

            foreach ($availableBatches as $batch) {
                if ($remainingQuantity <= 0) break;

                $availableInBatch = $batch->initial_quantity - $batch->quantity_depletion - $batch->quantity_sales - $batch->quantity_mutated;
                $willDeplete = min($remainingQuantity, $availableInBatch);

                if ($willDeplete > 0) {
                    $preview[] = [
                        'batch_id' => $batch->id,
                        'batch_name' => $batch->name,
                        'batch_age_days' => $batch->start_date ? now()->diffInDays($batch->start_date) : null,
                        'available_quantity' => $availableInBatch,
                        'will_deplete' => $willDeplete,
                        'remaining_after' => $availableInBatch - $willDeplete
                    ];
                    $remainingQuantity -= $willDeplete;
                }
            }

            return [
                'method' => 'batch',
                'depletion_method' => $depletionMethod,
                'total_quantity' => $depletionData['quantity'],
                'can_fulfill' => $remainingQuantity === 0,
                'batches_affected' => $preview,
                'shortfall' => $remainingQuantity > 0 ? $remainingQuantity : 0
            ];
        } else {
            return [
                'method' => 'total',
                'total_quantity' => $depletionData['quantity'],
                'can_fulfill' => true,
                'message' => 'Will process as total depletion'
            ];
        }
    }

    /**
     * Get supported depletion methods
     *
     * @return array
     */
    public static function getSupportedMethods(): array
    {
        return self::DEPLETION_METHODS;
    }

    /**
     * Get supported depletion types
     *
     * @return array
     */
    public static function getSupportedTypes(): array
    {
        return self::DEPLETION_TYPES;
    }

    // ========================================================================
    // ENHANCED FEATURES - Future Proof Extensions
    // ========================================================================

    /**
     * Reverse/Rollback a depletion transaction
     *
     * @param string $depletionId
     * @param array $reason
     * @return array
     * @throws Exception
     */
    public function reverseDepletion(string $depletionId, array $reason = []): array
    {
        return DB::transaction(function () use ($depletionId, $reason) {
            $depletion = LivestockDepletion::findOrFail($depletionId);

            Log::info('🔄 Starting depletion reversal', [
                'depletion_id' => $depletionId,
                'livestock_id' => $depletion->livestock_id,
                'original_quantity' => $depletion->jumlah,
                'reason' => $reason
            ]);

            // Get batch info if it exists
            $batchId = $depletion->data['batch_id'] ?? null;
            $batch = $batchId ? LivestockBatch::find($batchId) : null;

            // Reverse batch quantities if batch exists
            if ($batch) {
                $this->reverseBatchQuantities($batch, $depletion->jenis, $depletion->jumlah);
            }

            // Mark depletion as reversed
            $depletion->update([
                'metadata' => array_merge($depletion->metadata ?? [], [
                    'reversed_at' => now()->toISOString(),
                    'reversed_by' => auth()->id(),
                    'reversal_reason' => $reason,
                    'original_status' => 'active'
                ]),
                'updated_by' => auth()->id()
            ]);

            // Soft delete the depletion record
            $depletion->delete();

            // Update livestock totals
            $livestock = Livestock::findOrFail($depletion->livestock_id);
            $this->updateLivestockTotals($livestock);

            $result = [
                'success' => true,
                'depletion_id' => $depletionId,
                'livestock_id' => $depletion->livestock_id,
                'reversed_quantity' => $depletion->jumlah,
                'batch_affected' => $batch ? $batch->name : null,
                'message' => 'Depletion reversed successfully'
            ];

            Log::info('✅ Depletion reversal completed', $result);
            return $result;
        });
    }

    /**
     * Reverse batch quantities for depletion reversal
     *
     * @param LivestockBatch $batch
     * @param string $depletionType
     * @param int $quantity
     * @return void
     */
    private function reverseBatchQuantities(LivestockBatch $batch, string $depletionType, int $quantity): void
    {
        switch ($depletionType) {
            case 'mortality':
            case 'culling':
            case 'other':
                $batch->quantity_depletion = max(0, $batch->quantity_depletion - $quantity);
                break;
            case 'sales':
                $batch->quantity_sales = max(0, $batch->quantity_sales - $quantity);
                break;
            case 'mutation':
                $batch->quantity_mutated = max(0, $batch->quantity_mutated - $quantity);
                break;
            default:
                $batch->quantity_depletion = max(0, $batch->quantity_depletion - $quantity);
        }

        $batch->save();
    }

    /**
     * Bulk process multiple depletions
     *
     * @param array $depletionBatch
     * @return array
     */
    public function processBulkDepletion(array $depletionBatch): array
    {
        $results = [];
        $successCount = 0;
        $errorCount = 0;

        Log::info('📦 Starting bulk depletion process', [
            'total_records' => count($depletionBatch)
        ]);

        foreach ($depletionBatch as $index => $depletionData) {
            try {
                $result = $this->processDepletion($depletionData);
                $results[] = [
                    'index' => $index,
                    'status' => 'success',
                    'result' => $result
                ];
                $successCount++;
            } catch (Exception $e) {
                $results[] = [
                    'index' => $index,
                    'status' => 'error',
                    'error' => $e->getMessage(),
                    'data' => $depletionData
                ];
                $errorCount++;

                Log::error('❌ Bulk depletion item failed', [
                    'index' => $index,
                    'error' => $e->getMessage(),
                    'data' => $depletionData
                ]);
            }
        }

        $summary = [
            'total_processed' => count($depletionBatch),
            'success_count' => $successCount,
            'error_count' => $errorCount,
            'success_rate' => $successCount > 0 ? ($successCount / count($depletionBatch)) * 100 : 0,
            'results' => $results
        ];

        Log::info('📊 Bulk depletion process completed', $summary);
        return $summary;
    }

    /**
     * Get batch utilization analytics
     *
     * @param string $livestockId
     * @return array
     */
    public function getBatchUtilizationAnalytics(string $livestockId): array
    {
        $livestock = Livestock::findOrFail($livestockId);
        $batches = $livestock->batches()->get();

        $analytics = [
            'livestock_id' => $livestockId,
            'livestock_name' => $livestock->name,
            'total_batches' => $batches->count(),
            'active_batches' => $batches->where('status', 'active')->count(),
            'batch_utilization' => []
        ];

        foreach ($batches as $batch) {
            $totalUsed = $batch->quantity_depletion + $batch->quantity_sales + $batch->quantity_mutated;
            $utilizationRate = $batch->initial_quantity > 0 ? ($totalUsed / $batch->initial_quantity) * 100 : 0;
            $remainingQuantity = $batch->initial_quantity - $totalUsed;

            $analytics['batch_utilization'][] = [
                'batch_id' => $batch->id,
                'batch_name' => $batch->name,
                'start_date' => $batch->start_date,
                'age_days' => $batch->start_date ? now()->diffInDays($batch->start_date) : null,
                'initial_quantity' => $batch->initial_quantity,
                'quantity_depletion' => $batch->quantity_depletion,
                'quantity_sales' => $batch->quantity_sales,
                'quantity_mutated' => $batch->quantity_mutated,
                'total_used' => $totalUsed,
                'remaining_quantity' => $remainingQuantity,
                'utilization_rate' => round($utilizationRate, 2),
                'status' => $batch->status
            ];
        }

        // Sort by utilization rate descending
        usort($analytics['batch_utilization'], function ($a, $b) {
            return $b['utilization_rate'] <=> $a['utilization_rate'];
        });

        return $analytics;
    }

    /**
     * Validate batch availability before processing
     *
     * @param string $livestockId
     * @param int $requiredQuantity
     * @param string $depletionMethod
     * @return array
     */
    public function validateBatchAvailability(string $livestockId, int $requiredQuantity, string $depletionMethod = 'fifo'): array
    {
        $livestock = Livestock::findOrFail($livestockId);
        $availableBatches = $this->getAvailableBatchesForDepletion($livestock, $depletionMethod);

        $totalAvailable = $availableBatches->sum(function ($batch) {
            return $batch->initial_quantity - $batch->quantity_depletion - $batch->quantity_sales - $batch->quantity_mutated;
        });

        $validation = [
            'livestock_id' => $livestockId,
            'required_quantity' => $requiredQuantity,
            'total_available' => $totalAvailable,
            'can_fulfill' => $totalAvailable >= $requiredQuantity,
            'shortfall' => max(0, $requiredQuantity - $totalAvailable),
            'depletion_method' => $depletionMethod,
            'available_batches_count' => $availableBatches->count(),
            'batches_detail' => $availableBatches->map(function ($batch) {
                $available = $batch->initial_quantity - $batch->quantity_depletion - $batch->quantity_sales - $batch->quantity_mutated;
                return [
                    'batch_id' => $batch->id,
                    'batch_name' => $batch->name,
                    'available_quantity' => $available,
                    'age_days' => $batch->start_date ? now()->diffInDays($batch->start_date) : null
                ];
            })->values()
        ];

        return $validation;
    }

    /**
     * Get performance metrics for depletion operations
     *
     * @param string $livestockId
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    public function getDepletionPerformanceMetrics(string $livestockId, Carbon $startDate, Carbon $endDate): array
    {
        $startDate = $startDate ?? now()->subMonth();
        $endDate = $endDate ?? now();

        $depletions = LivestockDepletion::where('livestock_id', $livestockId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $batchDepletions = $depletions->whereNotNull('data.batch_id');
        $totalDepletions = $depletions->whereNull('data.batch_id');

        return [
            'livestock_id' => $livestockId,
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'days' => $startDate->diffInDays($endDate)
            ],
            'summary' => [
                'total_depletion_records' => $depletions->count(),
                'total_quantity_depleted' => $depletions->sum('jumlah'),
                'batch_method_count' => $batchDepletions->count(),
                'total_method_count' => $totalDepletions->count(),
                'average_depletion_per_day' => $depletions->count() / max(1, $startDate->diffInDays($endDate))
            ],
            'by_type' => $depletions->groupBy('jenis')->map(function ($group, $type) {
                return [
                    'count' => $group->count(),
                    'total_quantity' => $group->sum('jumlah'),
                    'average_quantity' => round($group->avg('jumlah'), 2)
                ];
            }),
            'by_method' => [
                'batch' => [
                    'count' => $batchDepletions->count(),
                    'total_quantity' => $batchDepletions->sum('jumlah')
                ],
                'total' => [
                    'count' => $totalDepletions->count(),
                    'total_quantity' => $totalDepletions->sum('jumlah')
                ]
            ]
        ];
    }

    /**
     * Export depletion data for reporting
     *
     * @param string $livestockId
     * @param array $options
     * @return array
     */
    public function exportDepletionData(string $livestockId, array $options = []): array
    {
        $livestock = Livestock::findOrFail($livestockId);
        $query = LivestockDepletion::where('livestock_id', $livestockId);

        // Apply filters if provided
        if (isset($options['start_date'])) {
            $query->where('tanggal', '>=', $options['start_date']);
        }
        if (isset($options['end_date'])) {
            $query->where('tanggal', '<=', $options['end_date']);
        }
        if (isset($options['type'])) {
            $query->where('jenis', $options['type']);
        }

        $depletions = $query->with(['livestock'])
            ->orderBy('tanggal', 'desc')
            ->get();

        return [
            'livestock' => [
                'id' => $livestock->id,
                'name' => $livestock->name,
                'initial_quantity' => $livestock->initial_quantity,
                'current_quantity_depletion' => $livestock->quantity_depletion
            ],
            'export_info' => [
                'exported_at' => now()->toISOString(),
                'exported_by' => auth()->user()->name ?? 'System',
                'total_records' => $depletions->count(),
                'filters_applied' => $options
            ],
            'depletions' => $depletions->map(function ($depletion) {
                return [
                    'id' => $depletion->id,
                    'date' => $depletion->tanggal,
                    'quantity' => $depletion->jumlah,
                    'type' => $depletion->jenis,
                    'batch_name' => $depletion->data['batch_name'] ?? 'N/A',
                    'batch_age_days' => isset($depletion->data['batch_start_date'])
                        ? Carbon::parse($depletion->data['batch_start_date'])->diffInDays($depletion->tanggal)
                        : null,
                    'processing_method' => $depletion->metadata['processing_method'] ?? 'unknown',
                    'created_at' => $depletion->created_at,
                    'created_by' => $depletion->created_by
                ];
            })->values()
        ];
    }

    /**
     * Get configuration recommendations based on livestock data
     *
     * @param string $livestockId
     * @return array
     */
    public function getConfigurationRecommendations(string $livestockId): array
    {
        $livestock = Livestock::findOrFail($livestockId);
        $batchCount = $livestock->getActiveBatchesCount();
        $depletions = LivestockDepletion::where('livestock_id', $livestockId)->get();

        $recommendations = [
            'livestock_id' => $livestockId,
            'current_batch_count' => $batchCount,
            'current_config' => $livestock->getRecordingMethodConfig(),
            'recommendations' => []
        ];

        // Recommendation logic
        if ($batchCount <= 1) {
            $recommendations['recommendations'][] = [
                'type' => 'recording_method',
                'recommendation' => 'total',
                'reason' => 'Single batch detected - total recording is more efficient',
                'priority' => 'high'
            ];
        } elseif ($batchCount > 3) {
            $recommendations['recommendations'][] = [
                'type' => 'recording_method',
                'recommendation' => 'batch',
                'reason' => 'Multiple batches detected - batch recording provides better tracking',
                'priority' => 'high'
            ];

            $recommendations['recommendations'][] = [
                'type' => 'depletion_method',
                'recommendation' => 'fifo',
                'reason' => 'FIFO method recommended for better inventory rotation',
                'priority' => 'medium'
            ];
        }

        // Performance recommendations
        if ($depletions->count() > 100) {
            $recommendations['recommendations'][] = [
                'type' => 'performance',
                'recommendation' => 'Enable batch indexing for better query performance',
                'reason' => 'High volume of depletion records detected',
                'priority' => 'medium'
            ];
        }

        return $recommendations;
    }

    /**
     * Process manual batch depletion where user selects specific batches
     *
     * @param Livestock $livestock
     * @param array $depletionData
     * @return array
     * @throws Exception
     */
    public function processManualBatchDepletion(Livestock $livestock, array $depletionData): array
    {
        return DB::transaction(function () use ($livestock, $depletionData) {
            // Validate manual batch input
            if (!isset($depletionData['manual_batches']) || !is_array($depletionData['manual_batches'])) {
                throw new Exception("Manual batch selection requires 'manual_batches' array with batch specifications");
            }

            // Filter only batches with quantity > 0
            $validBatches = array_filter($depletionData['manual_batches'], function ($batch) {
                return isset($batch['quantity']) && is_numeric($batch['quantity']) && $batch['quantity'] > 0;
            });
            if (empty($validBatches)) {
                throw new Exception("Minimal satu batch dengan quantity > 0 harus dipilih untuk depletion manual");
            }

            $processedBatches = [];
            $totalProcessed = 0;
            $isEditMode = isset($depletionData['is_editing']) && $depletionData['is_editing'] === true;
            $existingDepletionIds = $depletionData['existing_depletion_ids'] ?? [];

            Log::info('🎯 Starting manual batch depletion process', [
                'livestock_id' => $livestock->id,
                'livestock_name' => $livestock->name,
                'manual_batches' => count($validBatches),
                'depletion_type' => $depletionData['type'],
                'mode' => $isEditMode ? 'UPDATE' : 'CREATE',
                'existing_depletion_ids' => $existingDepletionIds
            ]);

            // Process each valid batch
            foreach ($validBatches as $manualBatch) {
                $this->validateManualBatchData($manualBatch);

                $batch = LivestockBatch::findOrFail($manualBatch['batch_id']);

                // Verify batch belongs to livestock
                if ($batch->livestock_id !== $livestock->id) {
                    throw new Exception("Batch {$batch->id} does not belong to livestock {$livestock->id}");
                }

                // Check batch availability (after potential reversals)
                $availableQuantity = $batch->initial_quantity - $batch->quantity_depletion - $batch->quantity_sales - $batch->quantity_mutated;

                if ($availableQuantity < $manualBatch['quantity']) {
                    throw new Exception("Insufficient quantity in batch {$batch->name}. Available: {$availableQuantity}, Requested: {$manualBatch['quantity']}");
                }

                // Process this batch
                $batchDepletionData = array_merge($depletionData, [
                    'quantity' => $manualBatch['quantity'],
                    'depletion_method' => 'manual',
                    'manual_batch_note' => $manualBatch['note'] ?? null,
                    'is_edit_replacement' => $isEditMode
                ]);

                $batchResult = $this->depleteBatch($batch, $manualBatch['quantity'], $batchDepletionData);

                if ($batchResult['depleted_quantity'] > 0) {
                    $processedBatches[] = array_merge($batchResult, [
                        'user_selected' => true,
                        'manual_note' => $manualBatch['note'] ?? null
                    ]);
                    $totalProcessed += $batchResult['depleted_quantity'];

                    Log::info('✅ Manual batch depleted', [
                        'batch_id' => $batch->id,
                        'batch_name' => $batch->name,
                        'depleted_quantity' => $batchResult['depleted_quantity'],
                        'remaining_in_batch' => $batchResult['remaining_quantity'],
                        'user_note' => $manualBatch['note'] ?? null
                    ]);
                }
            }

            // Update livestock totals
            $this->updateLivestockTotals($livestock);

            $results = [
                'success' => true,
                'livestock_id' => $livestock->id,
                'total_depleted' => $totalProcessed,
                'processed_batches' => $processedBatches,
                'depletion_method' => 'manual',
                'manual_selection' => true,
                'edit_mode' => $isEditMode,
                'update_strategy' => $isEditMode ? 'DELETE_AND_CREATE' : 'CREATE_NEW',
                'replaced_depletions' => $isEditMode ? count($existingDepletionIds) : 0,
                'message' => $isEditMode
                    ? 'Manual batch depletion updated successfully (deleted old, created new)'
                    : 'Manual batch depletion completed successfully'
            ];

            Log::info('🎉 Manual batch depletion process completed', $results);

            return $results;
        });
    }

    /**
     * Get available batches for manual selection
     *
     * @param string $livestockId
     * @return array
     */
    public function getAvailableBatchesForManualSelection(string $livestockId): array
    {
        $livestock = Livestock::findOrFail($livestockId);
        $batches = $livestock->batches()
            ->where('status', 'active')
            ->whereRaw('(initial_quantity - quantity_depletion - quantity_sales - quantity_mutated) > 0')
            ->orderBy('start_date', 'asc')
            ->get();

        return [
            'livestock_id' => $livestockId,
            'livestock_name' => $livestock->name,
            'total_batches' => $batches->count(),
            'batches' => $batches->map(function ($batch) {
                $availableQuantity = $batch->initial_quantity - $batch->quantity_depletion - $batch->quantity_sales - $batch->quantity_mutated;

                return [
                    'batch_id' => $batch->id,
                    'batch_name' => $batch->name,
                    'start_date' => $batch->start_date,
                    'age_days' => $batch->start_date ? now()->diffInDays($batch->start_date) : null,
                    'initial_quantity' => $batch->initial_quantity,
                    'used_quantity' => [
                        'depletion' => $batch->quantity_depletion,
                        'sales' => $batch->quantity_sales,
                        'mutated' => $batch->quantity_mutated,
                        'total' => $batch->quantity_depletion + $batch->quantity_sales + $batch->quantity_mutated
                    ],
                    'available_quantity' => $availableQuantity,
                    'utilization_rate' => $batch->initial_quantity > 0 ? round((($batch->quantity_depletion + $batch->quantity_sales + $batch->quantity_mutated) / $batch->initial_quantity) * 100, 2) : 0,
                    'status' => $batch->status
                ];
            })->values()
        ];
    }

    /**
     * Preview manual batch depletion
     *
     * @param array $depletionData
     * @return array
     */
    public function previewManualBatchDepletion(array $depletionData): array
    {
        if (!isset($depletionData['manual_batches']) || !is_array($depletionData['manual_batches'])) {
            throw new Exception("Manual batch selection requires 'manual_batches' array");
        }

        $livestock = Livestock::findOrFail($depletionData['livestock_id']);
        $preview = [];
        $totalQuantity = 0;
        $canFulfill = true;
        $errors = [];

        foreach ($depletionData['manual_batches'] as $index => $manualBatch) {
            try {
                $this->validateManualBatchData($manualBatch);

                $batch = LivestockBatch::findOrFail($manualBatch['batch_id']);

                // Verify batch belongs to livestock
                if ($batch->livestock_id !== $livestock->id) {
                    throw new Exception("Batch does not belong to this livestock");
                }

                $availableQuantity = $batch->initial_quantity - $batch->quantity_depletion - $batch->quantity_sales - $batch->quantity_mutated;
                $requestedQuantity = $manualBatch['quantity'];
                $batchCanFulfill = $availableQuantity >= $requestedQuantity;

                if (!$batchCanFulfill) {
                    $canFulfill = false;
                }

                $preview[] = [
                    'batch_id' => $batch->id,
                    'batch_name' => $batch->name,
                    'batch_age_days' => $batch->start_date ? now()->diffInDays($batch->start_date) : null,
                    'available_quantity' => $availableQuantity,
                    'requested_quantity' => $requestedQuantity,
                    'can_fulfill' => $batchCanFulfill,
                    'shortfall' => max(0, $requestedQuantity - $availableQuantity),
                    'note' => $manualBatch['note'] ?? null
                ];

                $totalQuantity += $requestedQuantity;
            } catch (Exception $e) {
                $canFulfill = false;
                $errors[] = [
                    'batch_index' => $index,
                    'batch_id' => $manualBatch['batch_id'] ?? 'unknown',
                    'error' => $e->getMessage()
                ];
            }
        }

        return [
            'method' => 'manual',
            'livestock_id' => $livestock->id,
            'livestock_name' => $livestock->name,
            'total_quantity' => $totalQuantity,
            'can_fulfill' => $canFulfill,
            'batches_count' => count($depletionData['manual_batches']),
            'batches_preview' => $preview,
            'errors' => $errors,
            'validation_passed' => empty($errors)
        ];
    }

    /**
     * Reverse depletion quantities for edit mode
     * 
     * @param LivestockDepletion $depletion
     * @return void
     */
    private function reverseDepletionQuantities(LivestockDepletion $depletion): void
    {
        try {
            $data = $depletion->data ?? [];
            $batchId = $data['batch_id'] ?? null;

            if ($batchId) {
                $batch = LivestockBatch::find($batchId);
                if ($batch) {
                    // Reverse the batch quantities
                    $this->reverseBatchQuantities($batch, $depletion->jenis, $depletion->jumlah);

                    Log::info('🔄 Reversed batch quantities', [
                        'batch_id' => $batchId,
                        'batch_name' => $batch->name,
                        'quantity_reversed' => $depletion->jumlah,
                        'depletion_type' => $depletion->jenis
                    ]);
                }
            }

            // Reverse livestock totals
            $livestock = Livestock::find($depletion->livestock_id);
            if ($livestock) {
                // Reverse the livestock depletion quantity
                $depletionType = $depletion->jenis;
                $quantity = $depletion->jumlah;

                if ($depletionType === 'mortality') {
                    $livestock->quantity_depletion = max(0, $livestock->quantity_depletion - $quantity);
                } elseif ($depletionType === 'sales') {
                    $livestock->quantity_sales = max(0, $livestock->quantity_sales - $quantity);
                } elseif ($depletionType === 'culling') {
                    $livestock->quantity_culling = max(0, $livestock->quantity_culling - $quantity);
                }

                $livestock->save();

                Log::info('🔄 Reversed livestock quantities', [
                    'livestock_id' => $livestock->id,
                    'depletion_type' => $depletionType,
                    'quantity_reversed' => $quantity
                ]);
            }
        } catch (Exception $e) {
            Log::error('❌ Error reversing depletion quantities', [
                'depletion_id' => $depletion->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Update existing depletions in place
     *
     * @param array $existingDepletionIds
     * @param array $depletionData
     * @param Livestock $livestock
     * @return void
     */
    private function updateExistingDepletions(array $existingDepletionIds, array $depletionData, Livestock $livestock): void
    {
        try {
            $manualBatches = $depletionData['manual_batches'] ?? [];
            $batchIndex = 0;

            foreach ($existingDepletionIds as $depletionId) {
                $existingDepletion = LivestockDepletion::find($depletionId);
                if (!$existingDepletion) {
                    Log::warning('⚠️ Existing depletion not found for update', ['depletion_id' => $depletionId]);
                    continue;
                }

                // Get corresponding batch data (if available)
                $batchData = $manualBatches[$batchIndex] ?? null;
                if (!$batchData) {
                    Log::warning('⚠️ No batch data for existing depletion', [
                        'depletion_id' => $depletionId,
                        'batch_index' => $batchIndex
                    ]);
                    $batchIndex++;
                    continue;
                }

                // Find the batch
                $batch = LivestockBatch::find($batchData['batch_id']);
                if (!$batch) {
                    Log::error('❌ Batch not found for update', ['batch_id' => $batchData['batch_id']]);
                    $batchIndex++;
                    continue;
                }

                // Update the depletion record
                $existingDepletion->update([
                    'tanggal' => $depletionData['date'] ?? $existingDepletion->tanggal,
                    'jumlah' => $batchData['quantity'],
                    'jenis' => $depletionData['type'] ?? $existingDepletion->jenis,
                    'data' => [
                        'batch_id' => $batch->id,
                        'batch_name' => $batch->name,
                        'batch_start_date' => $batch->start_date,
                        'depletion_method' => 'manual',
                        'original_request' => $batchData['quantity'],
                        'available_in_batch' => $batch->initial_quantity - $batch->quantity_depletion - $batch->quantity_sales - $batch->quantity_mutated,
                        'manual_batch_note' => $batchData['note'] ?? null,
                        'is_edit_replacement' => false, // This is an update, not replacement
                        'reason' => $depletionData['reason'] ?? null,
                        'updated_at' => now()->toISOString()
                    ],
                    'metadata' => array_merge($existingDepletion->metadata ?? [], [
                        'updated_at' => now()->toISOString(),
                        'updated_by' => auth()->id(),
                        'processing_method' => 'batch_depletion_service_update',
                        'edit_mode' => true,
                        'update_strategy' => 'UPDATE_EXISTING',
                        'batch_metadata' => [
                            'age_days' => $batch->start_date ? now()->diffInDays($batch->start_date) : null,
                            'initial_quantity' => $batch->initial_quantity,
                            'previous_depletion' => $batch->quantity_depletion,
                            'previous_sales' => $batch->quantity_sales,
                            'previous_mutated' => $batch->quantity_mutated
                        ]
                    ]),
                    'updated_by' => auth()->id()
                ]);

                // Update batch quantities
                $this->updateBatchQuantities($batch, $depletionData['type'], $batchData['quantity']);

                Log::info('✅ Updated existing depletion record', [
                    'depletion_id' => $depletionId,
                    'batch_id' => $batch->id,
                    'batch_name' => $batch->name,
                    'new_quantity' => $batchData['quantity'],
                    'depletion_type' => $depletionData['type']
                ]);

                $batchIndex++;
            }

            // Update livestock totals after all updates
            $this->updateLivestockTotals($livestock);
        } catch (Exception $e) {
            Log::error('❌ Error updating existing depletions', [
                'error' => $e->getMessage(),
                'livestock_id' => $livestock->id,
                'existing_depletion_ids' => $existingDepletionIds
            ]);
            throw $e;
        }
    }

    /**
     * Build processed batches from update
     *
     * @param array $manualBatches
     * @return array
     */
    private function buildProcessedBatchesFromUpdate(array $manualBatches): array
    {
        $processedBatches = [];

        foreach ($manualBatches as $batchData) {
            try {
                $batch = LivestockBatch::find($batchData['batch_id']);
                if (!$batch) {
                    Log::warning('⚠️ Batch not found for processed batches', ['batch_id' => $batchData['batch_id']]);
                    continue;
                }

                $availableQuantity = $batch->initial_quantity - $batch->quantity_depletion - $batch->quantity_sales - $batch->quantity_mutated;

                $processedBatches[] = [
                    'batch_id' => $batch->id,
                    'batch_name' => $batch->name,
                    'depleted_quantity' => $batchData['quantity'],
                    'remaining_quantity' => $availableQuantity,
                    'depletion_record_id' => null, // Will be set by the calling method if needed
                    'age_days' => $batch->start_date ? now()->diffInDays($batch->start_date) : null,
                    'user_selected' => true,
                    'manual_note' => $batchData['note'] ?? null,
                    'update_operation' => true
                ];
            } catch (Exception $e) {
                Log::error('❌ Error building processed batch from update', [
                    'batch_data' => $batchData,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $processedBatches;
    }
}

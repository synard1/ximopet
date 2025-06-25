<?php

namespace App\Services\Livestock;

use App\Models\Livestock;
use App\Models\LivestockBatch;
use App\Models\LivestockMutation;
use App\Models\LivestockMutationItem;
use App\Models\CurrentLivestock;
use App\Config\CompanyConfig;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Exception;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Livestock Mutation Service
 * 
 * Comprehensive service for handling livestock mutations with multiple methods:
 * - Manual (User selected batch and destination)
 * - FIFO (First In First Out) - future implementation
 * - LIFO (Last In First Out) - future implementation
 * 
 * Features:
 * - Robust error handling and validation
 * - Configurable mutation strategies
 * - Real-time quantity calculations
 * - Audit trail and logging
 * - Transaction safety
 * - Future-proof extensible design
 * - Support for both internal and external mutations
 */
class LivestockMutationService
{
    /**
     * Supported mutation methods
     */
    const MUTATION_METHODS = [
        'manual' => 'Manual Selection',
        'fifo' => 'First In First Out',
        'lifo' => 'Last In First Out'
    ];

    /**
     * Mutation types
     */
    const MUTATION_TYPES = [
        'internal' => 'Internal Transfer',
        'external' => 'External Transfer',
        'farm_transfer' => 'Farm Transfer',
        'location_transfer' => 'Location Transfer',
        'emergency_transfer' => 'Emergency Transfer'
    ];

    /**
     * Mutation directions
     */
    const MUTATION_DIRECTIONS = [
        'in' => 'Mutation In (Incoming)',
        'out' => 'Mutation Out (Outgoing)'
    ];

    /**
     * Process livestock mutation
     *
     * @param array $mutationData
     * @return array
     * @throws Exception
     */
    public function processMutation(array $mutationData): array
    {
        // Validate input data
        $this->validateMutationData($mutationData);

        // Get livestock and configuration
        $sourceLivestock = Livestock::findOrFail($mutationData['source_livestock_id']);
        $config = $this->getMutationConfig();
        $mutationMethod = $mutationData['mutation_method'] ?? $config['default_method'] ?? 'manual';

        // Determine processing method
        $recordingValidation = $sourceLivestock->validateBatchRecording();

        if (!$recordingValidation['valid']) {
            throw new Exception("Batch recording validation failed: " . $recordingValidation['message']);
        }

        // Process based on recording method and mutation method
        if ($recordingValidation['method'] === 'batch') {
            if ($mutationMethod === 'manual') {
                return $this->processManualBatchMutation($sourceLivestock, $mutationData);
            } elseif ($mutationMethod === 'fifo') {
                return $this->processFifoMutation($sourceLivestock, $mutationData);
            } else {
                return $this->processBatchMutation($sourceLivestock, $mutationData, $mutationMethod);
            }
        } else {
            return $this->processTotalMutation($sourceLivestock, $mutationData);
        }
    }

    /**
     * Process manual batch mutation
     *
     * @param Livestock $sourceLivestock
     * @param array $mutationData
     * @return array
     */
    public function processManualBatchMutation(Livestock $sourceLivestock, array $mutationData): array
    {
        return DB::transaction(function () use ($sourceLivestock, $mutationData) {
            // Validate manual batch input
            if (!isset($mutationData['manual_batches']) || !is_array($mutationData['manual_batches']) || empty($mutationData['manual_batches'])) {
                throw new Exception("Minimal satu batch harus dipilih untuk mutasi manual");
            }

            $processedBatches = [];
            $totalProcessed = 0;
            $isEditMode = isset($mutationData['is_editing']) && $mutationData['is_editing'] === true;
            $existingMutationIds = $mutationData['existing_mutation_ids'] ?? [];
            $config = CompanyConfig::getManualMutationHistorySettings();
            $historyEnabled = $config['history_enabled'] ?? false;

            Log::info('🔄 Starting manual batch mutation process', [
                'source_livestock_id' => $sourceLivestock->id,
                'source_livestock_name' => $sourceLivestock->name,
                'manual_batches' => count($mutationData['manual_batches']),
                'mutation_type' => $mutationData['type'],
                'mutation_direction' => $mutationData['direction'],
                'mode' => $isEditMode ? 'UPDATE' : 'CREATE',
                'existing_mutation_ids' => $existingMutationIds
            ]);

            if ($isEditMode && !empty($existingMutationIds) && !$historyEnabled) {
                // UPDATE_EXISTING: update header & items in-place
                $mutation = $this->updateExistingMutations($existingMutationIds, $mutationData, $sourceLivestock);
                if (!$mutation) {
                    return [
                        'success' => false,
                        'message' => 'Mutation record not found for update',
                        'edit_mode' => true,
                        'update_strategy' => 'UPDATE_EXISTING',
                    ];
                }
                foreach ($mutation->items as $item) {
                    $batch = LivestockBatch::find($item->batch_id);
                    $processedBatches[] = [
                        'batch_id' => $item->batch_id,
                        'batch_name' => $batch->name ?? 'Unknown',
                        'mutated_quantity' => $item->quantity,
                        'remaining_quantity' => $batch ? ($batch->initial_quantity - $batch->quantity_depletion - $batch->quantity_sales - $batch->quantity_mutated) : 0,
                        'mutation_item_id' => $item->id,
                        'age_days' => $batch && $batch->start_date ? now()->diffInDays($batch->start_date) : null,
                        'user_selected' => true,
                        'manual_note' => $item->keterangan ?? null
                    ];
                    $totalProcessed += $item->quantity;
                }
                $results = [
                    'success' => true,
                    'mutation_id' => $mutation->id,
                    'source_livestock_id' => $sourceLivestock->id,
                    'total_mutated' => $totalProcessed,
                    'processed_batches' => $processedBatches,
                    'mutation_method' => 'manual',
                    'mutation_type' => $mutationData['type'],
                    'mutation_direction' => $mutationData['direction'],
                    'manual_selection' => true,
                    'edit_mode' => true,
                    'update_strategy' => 'UPDATE_EXISTING',
                    'replaced_mutations' => count($existingMutationIds),
                    'items_count' => count($processedBatches),
                    'message' => $this->buildSuccessMessage(true, $mutationData['direction'])
                ];
                Log::info('🎉 Manual batch mutation process completed (update existing)', $results);

                // Cleanup header kosong (jumlah=0, tanpa detail)
                $this->cleanupEmptyMutationHeaders($sourceLivestock->id, $mutationData['date'] ?? now(), $mutationData['type'], $mutationData['direction']);

                return $results;
            }

            // Handle edit mode (delete & create)
            if ($isEditMode && !empty($existingMutationIds) && $historyEnabled) {
                $this->deleteExistingMutations($existingMutationIds);
            }

            // Create main mutation record (header)
            $mutation = $this->createMutationHeader($sourceLivestock, $mutationData, $isEditMode);

            // Process each manually specified batch as mutation items
            foreach ($mutationData['manual_batches'] as $manualBatch) {
                $this->validateManualBatchData($manualBatch);

                $batch = LivestockBatch::findOrFail($manualBatch['batch_id']);

                // Verify batch belongs to source livestock
                if ($batch->livestock_id !== $sourceLivestock->id) {
                    throw new Exception("Batch {$batch->id} does not belong to source livestock {$sourceLivestock->id}");
                }

                // Check batch availability
                $availableQuantity = $this->calculateBatchAvailableQuantity($batch);

                if ($availableQuantity < $manualBatch['quantity']) {
                    throw new Exception("Insufficient quantity in batch {$batch->name}. Available: {$availableQuantity}, Requested: {$manualBatch['quantity']}");
                }

                // Create mutation item for this batch
                $mutationItem = $this->createMutationItem($mutation, $batch, $manualBatch);

                // Update batch quantities
                $this->updateBatchQuantities($batch, $mutationData['direction'], $manualBatch['quantity']);

                $processedBatches[] = [
                    'batch_id' => $batch->id,
                    'batch_name' => $batch->name,
                    'mutated_quantity' => $manualBatch['quantity'],
                    'remaining_quantity' => $availableQuantity - $manualBatch['quantity'],
                    'mutation_item_id' => $mutationItem->id,
                    'age_days' => $batch->start_date ? now()->diffInDays($batch->start_date) : null,
                    'user_selected' => true,
                    'manual_note' => $manualBatch['note'] ?? null
                ];

                $totalProcessed += $manualBatch['quantity'];

                Log::info('✅ Manual batch mutated', [
                    'batch_id' => $batch->id,
                    'batch_name' => $batch->name,
                    'mutated_quantity' => $manualBatch['quantity'],
                    'remaining_in_batch' => $availableQuantity - $manualBatch['quantity'],
                    'user_note' => $manualBatch['note'] ?? null,
                    'mutation_item_id' => $mutationItem->id
                ]);
            }

            // Update mutation header with calculated total
            $mutation->update(['jumlah' => $totalProcessed]);

            // Update livestock totals
            $this->updateLivestockTotals($sourceLivestock);

            // Handle destination (coop or livestock) if specified
            if ($mutationData['direction'] === 'out') {
                // Jika destination_coop_id ada dan destination_livestock_id belum ada, buat livestock & batch tujuan
                if (isset($mutationData['destination_coop_id']) && empty($mutationData['destination_livestock_id'])) {
                    $coopId = $mutationData['destination_coop_id'];
                    $tanggal = $mutationData['date'] ?? now();
                    $farmId = \App\Models\Coop::findOrFail($coopId)->farm_id;

                    // Calculate aggregated data from source batches for destination livestock
                    $aggregatedData = $this->calculateAggregatedDataForDestination($mutationData['manual_batches']);

                    // 1. Buat Livestock tujuan jika belum ada dengan data yang benar
                    $destinationLivestock = $this->createLivestockIfNotExists($farmId, $coopId, $tanggal, null, $aggregatedData);
                    $mutationData['destination_livestock_id'] = $destinationLivestock->id;

                    // 2. Untuk setiap batch sumber, buat batch tujuan baru
                    foreach ($mutationData['manual_batches'] as $manualBatch) {
                        $sourceBatch = \App\Models\LivestockBatch::find($manualBatch['batch_id']);
                        $this->createBatchForLivestock($destinationLivestock, $sourceBatch, $manualBatch['quantity'], $mutation->id);
                    }

                    // 3. Update CurrentLivestock setelah semua batch dibuat
                    $this->updateCurrentLivestockSafe($destinationLivestock, $farmId, $coopId, $destinationLivestock->company_id);

                    // 3.1. [PERBAIKAN] Sync destination livestock totals dari batch yang baru dibuat
                    $this->syncDestinationLivestockTotals($destinationLivestock);

                    // 4. Update data Coop (quantity, weight, status, livestock_id)
                    $totalQuantity = \App\Models\LivestockBatch::where([
                        'livestock_id' => $destinationLivestock->id,
                        'farm_id' => $farmId,
                        'coop_id' => $coopId,
                        'status' => 'active'
                    ])->sum('initial_quantity');
                    $totalWeight = \App\Models\LivestockBatch::where([
                        'livestock_id' => $destinationLivestock->id,
                        'farm_id' => $farmId,
                        'coop_id' => $coopId,
                        'status' => 'active'
                    ])->sum('weight_total');
                    $coop = \App\Models\Coop::find($coopId);
                    if ($coop) {
                        $coop->update([
                            'quantity' => $totalQuantity,
                            'weight' => $totalWeight,
                            'status' => $totalQuantity > 0 ? 'in_use' : 'active',
                            'livestock_id' => $destinationLivestock->id,
                        ]);
                        Log::info('✅ Updated Coop after livestock mutation', [
                            'coop_id' => $coopId,
                            'quantity' => $totalQuantity,
                            'weight' => $totalWeight,
                            'status' => $totalQuantity > 0 ? 'in_use' : 'active',
                            'livestock_id' => $destinationLivestock->id
                        ]);
                    }

                    // 5. [PERBAIKAN] Update mutation header dengan destination_livestock_id yang baru dibuat
                    $mutation->update([
                        'destination_livestock_id' => $destinationLivestock->id,
                        'updated_by' => auth()->id(),
                    ]);

                    Log::info('🆕 Created/Found destination livestock & batches for mutation', [
                        'destination_livestock_id' => $destinationLivestock->id,
                        'coop_id' => $coopId,
                        'farm_id' => $farmId,
                        'tanggal' => $tanggal,
                        'batch_count' => count($mutationData['manual_batches']),
                        'aggregated_data' => $aggregatedData,
                        'mutation_updated' => true
                    ]);
                }
                if (isset($mutationData['destination_coop_id'])) {
                    $this->handleDestinationCoop($mutationData['destination_coop_id'], $totalProcessed, $mutationData, $mutation);
                } elseif (isset($mutationData['destination_livestock_id'])) {
                    $this->handleDestinationLivestock($mutationData['destination_livestock_id'], $totalProcessed, $mutationData, $mutation);
                }
            }

            $results = [
                'success' => true,
                'mutation_id' => $mutation->id,
                'source_livestock_id' => $sourceLivestock->id,
                'total_mutated' => $totalProcessed,
                'processed_batches' => $processedBatches,
                'mutation_method' => 'manual',
                'mutation_type' => $mutationData['type'],
                'mutation_direction' => $mutationData['direction'],
                'manual_selection' => true,
                'edit_mode' => $isEditMode,
                'update_strategy' => $isEditMode ? $this->getUpdateStrategy() : 'CREATE_NEW',
                'replaced_mutations' => $isEditMode ? count($existingMutationIds) : 0,
                'items_count' => count($processedBatches),
                'message' => $this->buildSuccessMessage($isEditMode, $mutationData['direction'])
            ];

            Log::info('🎉 Manual batch mutation process completed', $results);

            // Cleanup header kosong (jumlah=0, tanpa detail)
            $this->cleanupEmptyMutationHeaders($sourceLivestock->id, $mutationData['date'] ?? now(), $mutationData['type'], $mutationData['direction']);

            return $results;
        });
    }

    /**
     * Create mutation header record
     *
     * @param Livestock $sourceLivestock
     * @param array $mutationData
     * @param bool $isEditMode
     * @return LivestockMutation
     */
    private function createMutationHeader(Livestock $sourceLivestock, array $mutationData, bool $isEditMode): LivestockMutation
    {
        // Cek header existing sebelum create
        $tanggal = $mutationData['date'] ?? now();
        $jenis = $mutationData['type'];
        $direction = $mutationData['direction'];
        $sourceColumn = Schema::hasColumn('livestock_mutations', 'source_livestock_id') ? 'source_livestock_id' : 'from_livestock_id';
        $query = LivestockMutation::where($sourceColumn, $sourceLivestock->id)
            ->whereDate('tanggal', $tanggal)
            ->where('jenis', $jenis)
            ->where('direction', $direction)
            ->whereNull('deleted_at');
        $existing = $query->first();
        if ($existing) {
            // Update header existing (reset jumlah, update metadata)
            $existing->jumlah = 0;
            $existing->keterangan = $mutationData['reason'] ?? null;
            $existing->data = [
                'mutation_method' => $mutationData['mutation_method'] ?? 'manual',
                'reason' => $mutationData['reason'] ?? null,
                'notes' => $mutationData['notes'] ?? null,
                'is_edit_replacement' => $isEditMode,
                'destination_info' => $this->buildDestinationInfo($mutationData),
                'batch_count' => isset($mutationData['fifo_batches'])
                    ? count($mutationData['fifo_batches'])
                    : (isset($mutationData['manual_batches']) ? count($mutationData['manual_batches']) : 0),
            ];
            $existing->metadata = array_merge($existing->metadata ?? [], [
                'processed_at' => now()->toISOString(),
                'processed_by' => auth()->id(),
                'processing_method' => 'livestock_mutation_service_v2',
                'edit_mode' => $isEditMode,
                'service_version' => '2.0',
                'uses_items' => true,
                'updated_at' => now()->toISOString(),
                'updated_by' => auth()->id(),
            ]);
            $existing->save();
            Log::info('♻️ Using existing mutation header (no duplicate created)', [
                'mutation_id' => $existing->id,
                'source_livestock_id' => $sourceLivestock->id,
                'mutation_type' => $jenis,
                'direction' => $direction,
                'edit_mode' => $isEditMode
            ]);
            return $existing;
        }

        $headerData = [
            'source_livestock_id' => $sourceLivestock->id,
            'destination_livestock_id' => $mutationData['destination_livestock_id'] ?? null,
            'tanggal' => $mutationData['date'] ?? now(),
            'jumlah' => 0, // Will be calculated from items
            'jenis' => $mutationData['type'],
            'direction' => $mutationData['direction'],
            'keterangan' => $mutationData['reason'] ?? null,
            'data' => [
                'mutation_method' => $mutationData['mutation_method'] ?? 'manual',
                'reason' => $mutationData['reason'] ?? null,
                'notes' => $mutationData['notes'] ?? null,
                'is_edit_replacement' => $isEditMode,
                'destination_info' => $this->buildDestinationInfo($mutationData),
                'batch_count' => isset($mutationData['fifo_batches'])
                    ? count($mutationData['fifo_batches'])
                    : (isset($mutationData['manual_batches']) ? count($mutationData['manual_batches']) : 0),
            ],
            'metadata' => [
                'processed_at' => now()->toISOString(),
                'processed_by' => auth()->id(),
                'processing_method' => 'livestock_mutation_service_v2',
                'edit_mode' => $isEditMode,
                'service_version' => '2.0',
                'uses_items' => true,
            ],
            'created_by' => auth()->id()
        ];

        // Handle legacy column names
        if (!Schema::hasColumn('livestock_mutations', 'source_livestock_id')) {
            $headerData['from_livestock_id'] = $headerData['source_livestock_id'];
            unset($headerData['source_livestock_id']);
        }

        if (isset($headerData['destination_livestock_id']) && !Schema::hasColumn('livestock_mutations', 'destination_livestock_id')) {
            $headerData['to_livestock_id'] = $headerData['destination_livestock_id'];
            unset($headerData['destination_livestock_id']);
        }

        if (Schema::hasColumn('livestock_mutations', 'destination_coop_id')) {
            $headerData['destination_coop_id'] = $mutationData['destination_coop_id'] ?? null;
        }

        $mutation = LivestockMutation::create($headerData);

        Log::info('✅ Created mutation header', [
            'mutation_id' => $mutation->id,
            'source_livestock_id' => $sourceLivestock->id,
            'mutation_type' => $mutationData['type'],
            'direction' => $mutationData['direction'],
            'edit_mode' => $isEditMode
        ]);

        return $mutation;
    }

    /**
     * Create mutation item for a specific batch
     *
     * @param LivestockMutation $mutation
     * @param LivestockBatch $batch
     * @param array $manualBatch
     * @return LivestockMutationItem
     */
    private function createMutationItem(LivestockMutation $mutation, LivestockBatch $batch, array $manualBatch): LivestockMutationItem
    {
        $itemData = [
            'livestock_mutation_id' => $mutation->id,
            'batch_id' => $batch->id,
            'quantity' => $manualBatch['quantity'],
            'weight' => $manualBatch['weight'] ?? null,
            'keterangan' => $manualBatch['note'] ?? null,
            'payload' => [
                'batch_info' => [
                    'batch_id' => $batch->id,
                    'batch_name' => $batch->name,
                    'batch_start_date' => $batch->start_date,
                    'age_days' => $batch->start_date ? now()->diffInDays($batch->start_date) : null,
                    'initial_quantity' => $batch->initial_quantity,
                    'available_before_mutation' => $this->calculateBatchAvailableQuantity($batch),
                ],
                'mutation_info' => [
                    'requested_quantity' => $manualBatch['quantity'],
                    'user_note' => $manualBatch['note'] ?? null,
                    'processed_at' => now()->toISOString(),
                    'processed_by' => auth()->id(),
                ]
            ],
            'created_by' => auth()->id()
        ];

        return LivestockMutationItem::create($itemData);
    }

    /**
     * Build destination info for mutation data
     *
     * @param array $mutationData
     * @return array|null
     */
    private function buildDestinationInfo(array $mutationData): ?array
    {
        $destinationInfo = [];

        if (isset($mutationData['destination_coop_id'])) {
            try {
                $coop = \App\Models\Coop::find($mutationData['destination_coop_id']);
                if ($coop) {
                    $destinationInfo['coop'] = [
                        'id' => $coop->id,
                        'name' => $coop->name,
                        'farm_id' => $coop->farm_id,
                        'farm_name' => $coop->farm->name ?? 'Unknown',
                        'capacity' => $coop->capacity,
                    ];
                }
            } catch (Exception $e) {
                Log::warning('Could not load destination coop info', [
                    'coop_id' => $mutationData['destination_coop_id'],
                    'error' => $e->getMessage()
                ]);
            }
        }

        if (isset($mutationData['destination_livestock_id'])) {
            try {
                $livestock = Livestock::find($mutationData['destination_livestock_id']);
                if ($livestock) {
                    $destinationInfo['livestock'] = [
                        'id' => $livestock->id,
                        'name' => $livestock->name,
                        'farm_id' => $livestock->farm_id,
                        'farm_name' => $livestock->farm->name ?? 'Unknown',
                        'coop_id' => $livestock->coop_id,
                        'coop_name' => $livestock->coop->name ?? 'Unknown',
                    ];
                }
            } catch (Exception $e) {
                Log::warning('Could not load destination livestock info', [
                    'livestock_id' => $mutationData['destination_livestock_id'],
                    'error' => $e->getMessage()
                ]);
            }
        }

        return empty($destinationInfo) ? null : $destinationInfo;
    }

    /**
     * Calculate available quantity in a batch
     *
     * @param LivestockBatch $batch
     * @return int
     */
    private function calculateBatchAvailableQuantity(LivestockBatch $batch): int
    {
        return $batch->initial_quantity - $batch->quantity_depletion - $batch->quantity_sales - $batch->quantity_mutated;
    }

    /**
     * Update batch quantities based on mutation direction
     *
     * @param LivestockBatch $batch
     * @param string $direction
     * @param int $quantity
     * @return void
     */
    private function updateBatchQuantities(LivestockBatch $batch, string $direction, int $quantity): void
    {
        if ($direction === 'out') {
            $batch->quantity_mutated += $quantity;
        }
        // For 'in' direction, we don't update the source batch as it's incoming

        $batch->save();

        Log::info('📊 Updated batch quantities', [
            'batch_id' => $batch->id,
            'direction' => $direction,
            'quantity_change' => $quantity,
            'new_quantity_mutated' => $batch->quantity_mutated
        ]);
    }

    /**
     * Update livestock total quantities
     *
     * @param Livestock $livestock
     * @return void
     */
    private function updateLivestockTotals(Livestock $livestock): void
    {
        // Get correct column names for legacy support
        $sourceColumn = Schema::hasColumn('livestock_mutations', 'source_livestock_id') ? 'source_livestock_id' : 'from_livestock_id';
        $destinationColumn = Schema::hasColumn('livestock_mutations', 'destination_livestock_id') ? 'destination_livestock_id' : 'to_livestock_id';

        // Calculate total mutations from all records
        $totalMutationOut = LivestockMutation::where($sourceColumn, $livestock->id)
            ->where('direction', 'out')
            ->sum('jumlah');

        $totalMutationIn = LivestockMutation::where($destinationColumn, $livestock->id)
            ->where('direction', 'in')
            ->sum('jumlah');

        // Update livestock
        $livestock->update([
            'quantity_mutated_out' => $totalMutationOut,
            'quantity_mutated_in' => $totalMutationIn,
            'updated_by' => auth()->id()
        ]);

        Log::info('🔄 Updated livestock mutation totals', [
            'livestock_id' => $livestock->id,
            'total_mutation_out' => $totalMutationOut,
            'total_mutation_in' => $totalMutationIn,
            'source_column' => $sourceColumn,
            'destination_column' => $destinationColumn
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

        // Calculate real-time quantity including mutations
        $calculatedQuantity = $livestock->initial_quantity
            - ($livestock->quantity_depletion ?? 0)
            - ($livestock->quantity_sales ?? 0)
            - ($livestock->quantity_mutated_out ?? 0)
            + ($livestock->quantity_mutated_in ?? 0);

        $oldQuantity = $currentLivestock->quantity;
        $currentLivestock->update([
            'quantity' => max(0, $calculatedQuantity),
            'updated_by' => auth()->id()
        ]);

        Log::info('🔄 Updated CurrentLivestock quantity', [
            'livestock_id' => $livestock->id,
            'livestock_name' => $livestock->name,
            'old_quantity' => $oldQuantity,
            'new_quantity' => $currentLivestock->quantity,
            'change' => $currentLivestock->quantity - $oldQuantity,
            'formula' => sprintf(
                '%d - %d - %d - %d + %d = %d',
                $livestock->initial_quantity,
                $livestock->quantity_depletion ?? 0,
                $livestock->quantity_sales ?? 0,
                $livestock->quantity_mutated_out ?? 0,
                $livestock->quantity_mutated_in ?? 0,
                $currentLivestock->quantity
            )
        ]);
    }

    /**
     * Handle destination coop updates
     *
     * @param string $destinationCoopId
     * @param int $quantity
     * @param array $mutationData
     * @param LivestockMutation $mutation
     * @return void
     */
    private function handleDestinationCoop(string $destinationCoopId, int $quantity, array $mutationData, LivestockMutation $mutation): void
    {
        try {
            $destinationCoop = \App\Models\Coop::findOrFail($destinationCoopId);

            // Create mutation record with coop destination info
            LivestockMutation::create([
                'source_livestock_id' => $mutationData['source_livestock_id'],
                'destination_livestock_id' => null, // No specific livestock, just coop
                'tanggal' => $mutationData['date'] ?? now(),
                'jumlah' => $quantity,
                'jenis' => $mutationData['type'],
                'direction' => 'out',
                'data' => [
                    'mutation_method' => $mutationData['mutation_method'] ?? 'manual',
                    'reason' => $mutationData['reason'] ?? null,
                    'destination_coop_id' => $destinationCoopId,
                    'destination_coop_name' => $destinationCoop->name,
                    'destination_farm_id' => $destinationCoop->farm_id,
                    'destination_farm_name' => $destinationCoop->farm->name ?? 'Unknown Farm',
                    'notes' => $mutationData['notes'] ?? null
                ],
                'metadata' => [
                    'processed_at' => now()->toISOString(),
                    'processed_by' => auth()->id(),
                    'processing_method' => 'livestock_mutation_service_coop_destination',
                    'destination_type' => 'coop',
                    'destination_coop_capacity' => $destinationCoop->capacity
                ],
                'created_by' => auth()->id()
            ]);

            Log::info('✅ Created coop destination mutation record', [
                'destination_coop_id' => $destinationCoopId,
                'destination_coop_name' => $destinationCoop->name,
                'destination_farm' => $destinationCoop->farm->name ?? 'Unknown',
                'quantity' => $quantity,
                'source_livestock_id' => $mutationData['source_livestock_id']
            ]);
        } catch (Exception $e) {
            Log::error('❌ Error handling destination coop', [
                'destination_coop_id' => $destinationCoopId,
                'quantity' => $quantity,
                'error' => $e->getMessage()
            ]);

            throw new Exception("Failed to handle destination coop: " . $e->getMessage());
        }
    }

    /**
     * Handle destination livestock for outgoing mutations
     *
     * @param string $destinationLivestockId
     * @param int $quantity
     * @param array $mutationData
     * @param LivestockMutation $mutation
     * @return void
     */
    private function handleDestinationLivestock(string $destinationLivestockId, int $quantity, array $mutationData, LivestockMutation $mutation): void
    {
        $destinationLivestock = Livestock::find($destinationLivestockId);
        if (!$destinationLivestock) {
            Log::warning('Destination livestock not found', ['destination_livestock_id' => $destinationLivestockId]);
            return;
        }

        // Create incoming mutation record for destination
        LivestockMutation::create([
            'source_livestock_id' => $mutationData['source_livestock_id'],
            'destination_livestock_id' => $destinationLivestockId,
            'tanggal' => $mutationData['date'] ?? now(),
            'jumlah' => $quantity,
            'jenis' => $mutationData['type'],
            'direction' => 'in',
            'data' => [
                'mutation_method' => $mutationData['mutation_method'] ?? 'manual',
                'reason' => $mutationData['reason'] ?? null,
                'source_info' => [
                    'livestock_id' => $mutationData['source_livestock_id'],
                    'livestock_name' => Livestock::find($mutationData['source_livestock_id'])->name ?? 'Unknown'
                ]
            ],
            'metadata' => [
                'processed_at' => now()->toISOString(),
                'processed_by' => auth()->id(),
                'processing_method' => 'livestock_mutation_service_destination',
                'is_destination_record' => true
            ],
            'created_by' => auth()->id()
        ]);

        // Update destination livestock totals
        $this->updateLivestockTotals($destinationLivestock);

        Log::info('📥 Created destination mutation record', [
            'destination_livestock_id' => $destinationLivestockId,
            'quantity' => $quantity,
            'source_livestock_id' => $mutationData['source_livestock_id']
        ]);
    }

    /**
     * Handle edit mode operations
     *
     * @param array $existingMutationIds
     * @param array $mutationData
     * @return void
     */
    private function handleEditMode(array $existingMutationIds, array $mutationData): void
    {
        $config = CompanyConfig::getManualMutationHistorySettings();
        $historyEnabled = $config['history_enabled'] ?? false;

        Log::info('🔄 Edit mode: Processing existing mutations', [
            'existing_ids' => $existingMutationIds,
            'history_enabled' => $historyEnabled,
            'strategy' => $historyEnabled ? 'DELETE_AND_CREATE' : 'UPDATE_EXISTING'
        ]);

        if ($historyEnabled) {
            // Delete old records and create new ones
            $this->deleteExistingMutations($existingMutationIds);
        }
    }

    /**
     * Delete existing mutations (for history enabled mode)
     *
     * @param array $existingMutationIds
     * @return void
     */
    private function deleteExistingMutations(array $existingMutationIds): void
    {
        foreach ($existingMutationIds as $mutationId) {
            try {
                $existingMutation = LivestockMutation::find($mutationId);
                if ($existingMutation) {
                    // Reverse the quantities before deleting
                    $this->reverseMutationQuantities($existingMutation);

                    Log::info('🔄 Reversed and deleted existing mutation', [
                        'mutation_id' => $mutationId,
                        'quantity' => $existingMutation->jumlah,
                        'type' => $existingMutation->jenis,
                        'direction' => $existingMutation->direction
                    ]);

                    $existingMutation->delete();
                }
            } catch (Exception $e) {
                Log::error('❌ Error reversing existing mutation', [
                    'mutation_id' => $mutationId,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Update existing mutations (for history disabled mode)
     *
     * @param array $existingMutationIds
     * @param array $mutationData
     * @return void
     */
    private function updateExistingMutations(array $existingMutationIds, array $mutationData, Livestock $sourceLivestock): ?LivestockMutation
    {
        $mutation = LivestockMutation::find($existingMutationIds[0]);
        if (!$mutation) {
            Log::warning('⚠️ updateExistingMutations: mutation not found for IDs', ['ids' => $existingMutationIds]);
            return null;
        }

        // Mapping batch_id => item lama
        $oldItems = $mutation->items()->get()->keyBy('batch_id');
        // Mapping batch_id => batch baru dari input
        $newBatches = collect($mutationData['manual_batches'])->keyBy('batch_id');

        $totalProcessed = 0;
        $updatedItems = [];

        // 1. Update or create items for all batches in input
        foreach ($newBatches as $batchId => $manualBatch) {
            $this->validateManualBatchData($manualBatch);
            $batch = LivestockBatch::findOrFail($batchId);
            if ($oldItems->has($batchId)) {
                // Update existing item
                $item = $oldItems[$batchId];
                // Reverse old quantity first
                if ($mutation->direction === 'out') {
                    $batch->quantity_mutated = max(0, $batch->quantity_mutated - $item->quantity);
                }
                // Update item fields
                $item->quantity = $manualBatch['quantity'];
                $item->keterangan = $manualBatch['note'] ?? null;
                $item->payload = [
                    'batch_info' => [
                        'batch_id' => $batch->id,
                        'batch_name' => $batch->name,
                        'batch_start_date' => $batch->start_date,
                        'age_days' => $batch->start_date ? now()->diffInDays($batch->start_date) : null,
                        'initial_quantity' => $batch->initial_quantity,
                        'available_before_mutation' => $this->calculateBatchAvailableQuantity($batch),
                    ],
                    'mutation_info' => [
                        'requested_quantity' => $manualBatch['quantity'],
                        'user_note' => $manualBatch['note'] ?? null,
                        'processed_at' => now()->toISOString(),
                        'processed_by' => auth()->id(),
                    ]
                ];
                $item->save();
                // Update batch quantity
                $this->updateBatchQuantities($batch, $mutationData['direction'], $manualBatch['quantity']);
                $updatedItems[] = $item;
            } else {
                // Create new item
                $item = $this->createMutationItem($mutation, $batch, $manualBatch);
                $this->updateBatchQuantities($batch, $mutationData['direction'], $manualBatch['quantity']);
                $updatedItems[] = $item;
            }
            $totalProcessed += $manualBatch['quantity'];
        }

        // 2. Delete items that are not in new input (and reverse quantity)
        foreach ($oldItems as $batchId => $item) {
            if (!$newBatches->has($batchId)) {
                $batch = LivestockBatch::find($batchId);
                if ($batch && $mutation->direction === 'out') {
                    $batch->quantity_mutated = max(0, $batch->quantity_mutated - $item->quantity);
                    $batch->save();
                }
                $item->delete();
            }
        }

        // 3. Update header fields
        $mutation->tanggal = $mutationData['date'] ?? $mutation->tanggal;
        $mutation->jenis = $mutationData['type'] ?? $mutation->jenis;
        $mutation->direction = $mutationData['direction'] ?? $mutation->direction;
        $mutation->destination_livestock_id = $mutationData['destination_livestock_id'] ?? null;
        if (Schema::hasColumn('livestock_mutations', 'destination_coop_id')) {
            $mutation->destination_coop_id = $mutationData['destination_coop_id'] ?? null;
        }
        $mutation->keterangan = $mutationData['reason'] ?? null;
        $mutation->data = [
            'mutation_method' => $mutationData['mutation_method'] ?? 'manual',
            'reason' => $mutationData['reason'] ?? null,
            'notes' => $mutationData['notes'] ?? null,
            'is_edit_replacement' => true,
            'destination_info' => $this->buildDestinationInfo($mutationData),
            'batch_count' => count($updatedItems),
        ];
        $mutation->metadata = array_merge($mutation->metadata ?? [], [
            'updated_at' => now()->toISOString(),
            'updated_by' => auth()->id(),
            'edit_mode' => true,
            'update_strategy' => 'UPDATE_EXISTING',
        ]);
        $mutation->jumlah = $totalProcessed;
        $mutation->save();
        $this->updateLivestockTotals($sourceLivestock);
        Log::info('✅ Updated existing mutation header and items (granular)', [
            'mutation_id' => $mutation->id,
            'total_quantity' => $totalProcessed,
            'batch_count' => count($updatedItems),
            'mutation_method' => $mutationData['mutation_method'] ?? 'manual'
        ]);
        return $mutation;
    }

    /**
     * Get mutation configuration
     *
     * @return array
     */
    private function getMutationConfig(): array
    {
        return CompanyConfig::getManualMutationConfig();
    }

    /**
     * Get update strategy based on configuration
     *
     * @return string
     */
    private function getUpdateStrategy(): string
    {
        $config = CompanyConfig::getManualMutationHistorySettings();
        return ($config['history_enabled'] ?? false) ? 'DELETE_AND_CREATE' : 'UPDATE_EXISTING';
    }

    /**
     * Build success message based on mode and direction
     *
     * @param bool $isEditMode
     * @param string $direction
     * @return string
     */
    private function buildSuccessMessage(bool $isEditMode, string $direction): string
    {
        if ($isEditMode) {
            $strategy = $this->getUpdateStrategy();
            return $strategy === 'UPDATE_EXISTING'
                ? "Mutasi {$direction} berhasil diperbarui (record existing di-update)"
                : "Mutasi {$direction} berhasil diperbarui (record lama dihapus, record baru dibuat)";
        }

        return "Manual batch mutation {$direction} berhasil diproses";
    }

    /**
     * Validate mutation data
     *
     * @param array $data
     * @throws Exception
     */
    private function validateMutationData(array $data): void
    {
        $required = ['source_livestock_id', 'type', 'direction'];

        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new Exception("Field '{$field}' is required for mutation processing");
            }
        }

        if (!in_array($data['direction'], ['in', 'out'])) {
            throw new Exception("Invalid mutation direction. Must be 'in' or 'out'");
        }

        if (!in_array($data['type'], array_keys(self::MUTATION_TYPES))) {
            throw new Exception("Invalid mutation type: " . $data['type']);
        }

        // For outgoing mutations, require either destination_livestock_id OR destination_coop_id
        if ($data['direction'] === 'out') {
            if (!isset($data['destination_livestock_id']) && !isset($data['destination_coop_id'])) {
                throw new Exception("Either destination livestock ID or destination coop ID is required for outgoing mutations");
            }
        }
    }

    /**
     * Validate manual batch data
     *
     * @param array $batchData
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

        if (!is_numeric($batchData['quantity']) || $batchData['quantity'] <= 0) {
            throw new Exception("Batch quantity must be a positive number");
        }
    }

    /**
     * Process batch-based mutation with FIFO/LIFO method (future implementation)
     *
     * @param Livestock $livestock
     * @param array $mutationData
     * @param string $mutationMethod
     * @return array
     */
    public function processBatchMutation(Livestock $livestock, array $mutationData, string $mutationMethod = 'fifo'): array
    {
        // Future implementation for FIFO/LIFO mutations
        throw new Exception("Batch mutation with {$mutationMethod} method is not yet implemented");
    }

    /**
     * Process total-based mutation (for single batch livestock)
     *
     * @param Livestock $livestock
     * @param array $mutationData
     * @return array
     */
    public function processTotalMutation(Livestock $livestock, array $mutationData): array
    {
        // Future implementation for total mutations
        throw new Exception("Total mutation method is not yet implemented");
    }

    /**
     * Get available batches for manual selection
     *
     * @param string $livestockId
     * @return array
     */
    public function getAvailableBatchesForMutation(string $livestockId): array
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
                $availableQuantity = $this->calculateBatchAvailableQuantity($batch);

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
                    'utilization_rate' => $batch->initial_quantity > 0
                        ? round((($batch->quantity_depletion + $batch->quantity_sales + $batch->quantity_mutated) / $batch->initial_quantity) * 100, 2)
                        : 0,
                    'status' => $batch->status
                ];
            })->values()
        ];
    }

    /**
     * Preview manual batch mutation
     *
     * @param array $mutationData
     * @return array
     */
    public function previewManualBatchMutation(array $mutationData): array
    {
        if (!isset($mutationData['manual_batches']) || !is_array($mutationData['manual_batches'])) {
            throw new Exception("Manual batch selection requires 'manual_batches' array");
        }

        $livestock = Livestock::findOrFail($mutationData['source_livestock_id']);
        $preview = [];
        $totalQuantity = 0;
        $canFulfill = true;
        $errors = [];

        foreach ($mutationData['manual_batches'] as $index => $manualBatch) {
            try {
                $this->validateManualBatchData($manualBatch);

                $batch = LivestockBatch::findOrFail($manualBatch['batch_id']);

                // Verify batch belongs to livestock
                if ($batch->livestock_id !== $livestock->id) {
                    throw new Exception("Batch does not belong to this livestock");
                }

                $availableQuantity = $this->calculateBatchAvailableQuantity($batch);
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
            'batches_count' => count($mutationData['manual_batches']),
            'batches_preview' => $preview,
            'errors' => $errors,
            'validation_passed' => empty($errors)
        ];
    }

    /**
     * Get supported mutation methods
     *
     * @return array
     */
    public static function getSupportedMethods(): array
    {
        return self::MUTATION_METHODS;
    }

    /**
     * Get supported mutation types
     *
     * @return array
     */
    public static function getSupportedTypes(): array
    {
        return self::MUTATION_TYPES;
    }

    /**
     * Get supported mutation directions
     *
     * @return array
     */
    public static function getSupportedDirections(): array
    {
        return self::MUTATION_DIRECTIONS;
    }

    /**
     * Hapus header mutation dengan jumlah=0 dan tanpa detail (mutation_items)
     */
    private function cleanupEmptyMutationHeaders($sourceLivestockId, $tanggal, $jenis, $direction): void
    {
        $sourceColumn = Schema::hasColumn('livestock_mutations', 'source_livestock_id') ? 'source_livestock_id' : 'from_livestock_id';
        $headers = \App\Models\LivestockMutation::where($sourceColumn, $sourceLivestockId)
            ->whereDate('tanggal', $tanggal)
            ->where('jenis', $jenis)
            ->where('direction', $direction)
            ->where('jumlah', 0)
            ->whereNull('deleted_at')
            ->get();
        foreach ($headers as $header) {
            if ($header->items()->count() === 0) {
                $header->forceDelete();
                Log::info('🧹 Deleted empty mutation header (jumlah=0, no detail)', [
                    'mutation_id' => $header->id,
                    'source_livestock_id' => $sourceLivestockId,
                    'tanggal' => $tanggal,
                    'jenis' => $jenis,
                    'direction' => $direction
                ]);
            }
        }
    }

    /**
     * Create livestock and batch in destination coop if not exists (Production Ready)
     * @param string $farmId
     * @param string $coopId
     * @param string|\Carbon\Carbon $tanggal
     * @param string|null $name
     * @param array $additional
     * @return array
     */
    public function createLivestockAndBatchIfNotExists($farmId, $coopId, $tanggal, $name = null, $additional = []): array
    {
        return DB::transaction(function () use ($farmId, $coopId, $tanggal, $name, $additional) {
            Log::info('🔄 Starting createLivestockAndBatchIfNotExists (Production)', [
                'farm_id' => $farmId,
                'coop_id' => $coopId,
                'tanggal' => $tanggal,
                'name' => $name,
                'additional_keys' => array_keys($additional)
            ]);

            // Input validation
            if (empty($farmId) || empty($coopId)) {
                throw new \Exception('Farm ID and Coop ID are required');
            }

            // Convert tanggal to Carbon
            if (is_string($tanggal)) {
                $tanggal = \Carbon\Carbon::parse($tanggal);
            }

            // Load models with validation
            $farm = \App\Models\Farm::findOrFail($farmId);
            $coop = \App\Models\Coop::findOrFail($coopId);

            // Business validation
            if ($coop->farm_id !== $farm->id) {
                throw new \Exception("Coop {$coopId} does not belong to farm {$farmId}");
            }

            // Get company for multi-tenant support
            $companyId = $farm->company_id ?? auth()->user()->company_id ?? null;
            if (!$companyId) {
                throw new \Exception('Company ID not found for multi-tenant support');
            }

            // Find or create Livestock
            $livestock = \App\Models\Livestock::where([
                'farm_id' => $farmId,
                'coop_id' => $coopId,
                'company_id' => $companyId,
            ])->first();

            $livestockName = $name ?? $this->generateLivestockName($farm, $coop, $tanggal);

            if (!$livestock) {
                // Validate livestock capacity
                $currentLivestockCount = \App\Models\Livestock::where([
                    'farm_id' => $farmId,
                    'coop_id' => $coopId,
                    'status' => 'active'
                ])->count();

                $maxLivestockPerCoop = 5; // Business rule
                if ($currentLivestockCount >= $maxLivestockPerCoop) {
                    throw new \Exception("Maximum livestock per coop ({$maxLivestockPerCoop}) reached");
                }

                $livestock = \App\Models\Livestock::create([
                    'name' => $livestockName,
                    'farm_id' => $farmId,
                    'coop_id' => $coopId,
                    'company_id' => $companyId,
                    'initial_quantity' => $additional['initial_quantity'] ?? 0,
                    'initial_weight' => $additional['initial_weight'] ?? 0,
                    'price' => $additional['price'] ?? 0,
                    'livestock_breed_id' => $additional['strain_id'] ?? null,
                    'breed' => $additional['strain_name'] ?? null,
                    'livestock_strain_standard_id' => $additional['strain_standard_id'] ?? null,
                    'start_date' => $tanggal,
                    'status' => 'active',
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);

                Log::info('✅ Created new Livestock', [
                    'livestock_id' => $livestock->id,
                    'name' => $livestockName,
                    'company_id' => $companyId
                ]);
            } else {
                Log::info('✅ Using existing Livestock', [
                    'livestock_id' => $livestock->id,
                    'name' => $livestock->name
                ]);
            }

            // Find or create LivestockBatch
            $batchName = $this->generateBatchName($livestock, $tanggal, $additional);
            $batch = \App\Models\LivestockBatch::where([
                'livestock_id' => $livestock->id,
                'farm_id' => $farmId,
                'coop_id' => $coopId,
                'name' => $batchName,
                'start_date' => $tanggal->toDateString(),
            ])->first();

            if (!$batch) {
                // Get source batch data for comprehensive reference
                $sourceBatch = null;
                if (isset($additional['source_batch_id'])) {
                    $sourceBatch = \App\Models\LivestockBatch::with(['livestock', 'livestock.farm', 'livestock.coop'])
                        ->find($additional['source_batch_id']);

                    if ($sourceBatch) {
                        Log::info('📋 Found source batch for comprehensive reference', [
                            'source_batch_id' => $sourceBatch->id,
                            'source_batch_name' => $sourceBatch->name,
                            'source_details' => [
                                'initial_weight' => $sourceBatch->initial_weight,
                                'weight_per_unit' => $sourceBatch->weight_per_unit,
                                'weight_total' => $sourceBatch->weight_total,
                                'strain_id' => $sourceBatch->livestock_strain_id,
                                'strain_name' => $sourceBatch->livestock_strain_name,
                                'price_per_unit' => $sourceBatch->price_per_unit,
                                'price_total' => $sourceBatch->price_total
                            ]
                        ]);
                    }
                }

                // Prepare comprehensive batch data
                $batchData = [
                    'livestock_id' => $livestock->id,
                    'farm_id' => $farmId,
                    'coop_id' => $coopId,
                    'company_id' => $companyId,
                    'name' => $batchName,
                    'start_date' => $tanggal,
                    'source_type' => $additional['source_type'] ?? 'mutation',
                    'source_id' => $additional['mutation_id'] ?? null,
                    'initial_quantity' => $additional['initial_quantity'] ?? 0,
                    'status' => 'active',
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ];

                // Fill strain data with comprehensive fallback
                $this->fillStrainData($batchData, $additional, $sourceBatch, $livestock);

                // Fill weight data with comprehensive calculation
                $this->fillWeightData($batchData, $additional, $sourceBatch);

                // Fill price data with business logic
                $this->fillPriceData($batchData, $additional, $sourceBatch, $livestock);

                // Validate all required fields (with reference)
                $this->validateAndFixBatchData($batchData);

                // Final business validation
                $this->validateBusinessRules($batchData, $coop);

                $batch = \App\Models\LivestockBatch::create($batchData);

                Log::info('✅ Created new destination batch (Production)', [
                    'batch_id' => $batch->id,
                    'batch_name' => $batch->name,
                    'comprehensive_data' => [
                        'initial_weight' => $batch->initial_weight,
                        'weight_per_unit' => $batch->weight_per_unit,
                        'weight_total' => $batch->weight_total,
                        'strain_id' => $batch->livestock_strain_id,
                        'strain_name' => $batch->livestock_strain_name,
                        'price_per_unit' => $batch->price_per_unit,
                        'price_total' => $batch->price_total,
                        'company_id' => $batch->company_id
                    ]
                ]);

                // Update CurrentLivestock within same transaction
                $this->updateCurrentLivestockSafe($livestock, $farmId, $coopId, $companyId);

                // --- [PERBAIKAN] Sync initial_quantity Livestock dengan total batch aktif ---
                $totalInitialQuantity = \App\Models\LivestockBatch::where([
                    'livestock_id' => $livestock->id,
                    'status' => 'active'
                ])->sum('initial_quantity');
                $livestock->update([
                    'initial_quantity' => $totalInitialQuantity,
                    'updated_by' => auth()->id(),
                ]);
                Log::info('🔄 Synced Livestock initial_quantity after batch creation', [
                    'livestock_id' => $livestock->id,
                    'new_initial_quantity' => $totalInitialQuantity
                ]);
            } else {
                Log::info('✅ Using existing batch', [
                    'batch_id' => $batch->id,
                    'batch_name' => $batch->name
                ]);
            }

            return [
                'livestock' => $livestock,
                'batch' => $batch
            ];
        });
    }

    /**
     * Generate livestock name with business logic
     */
    private function generateLivestockName($farm, $coop, $tanggal): string
    {
        return 'PR-' . ($farm->code ?? $farm->name) . '-' . ($coop->code ?? $coop->name) . '-' . $tanggal->format('dmY');
    }

    /**
     * Generate batch name with uniqueness check
     */
    private function generateBatchName($livestock, $tanggal, $additional): string
    {
        $baseName = $livestock->name . '-' . $tanggal->format('dmY');

        // Check for existing batches and increment
        $existingCount = \App\Models\LivestockBatch::where([
            'livestock_id' => $livestock->id,
            'farm_id' => $livestock->farm_id,
            'coop_id' => $livestock->coop_id,
        ])->where('name', 'like', $baseName . '%')->count();

        return $baseName . '-' . sprintf('%03d', $existingCount + 1);
    }

    /**
     * Fill strain data with comprehensive fallback
     */
    private function fillStrainData(array &$batchData, array $additional, $sourceBatch, $livestock): void
    {
        $batchData['livestock_strain_id'] = $additional['strain_id'] ??
            ($sourceBatch->livestock_strain_id ?? $livestock->livestock_breed_id ?? null);
        $batchData['livestock_strain_name'] = $additional['strain_name'] ??
            ($sourceBatch->livestock_strain_name ?? $livestock->breed ?? 'Default Strain');
        $batchData['livestock_strain_standard_id'] = $additional['strain_standard_id'] ??
            ($sourceBatch->livestock_strain_standard_id ?? $livestock->livestock_strain_standard_id ?? null);
    }

    /**
     * Fill weight data with comprehensive calculation
     */
    private function fillWeightData(array &$batchData, array $additional, $sourceBatch): void
    {
        $initialWeight = $additional['initial_weight'] ??
            ($sourceBatch->initial_weight ?? $sourceBatch->weight ?? 0);

        $batchData['initial_weight'] = $initialWeight;
        $batchData['weight'] = $initialWeight;
        $batchData['weight_per_unit'] = $additional['weight_per_unit'] ??
            ($sourceBatch->weight_per_unit ?? $initialWeight);
        $batchData['weight_total'] = $additional['weight_total'] ??
            ($sourceBatch->weight_total ?? ($initialWeight * ($additional['initial_quantity'] ?? 0)));
        $batchData['weight_type'] = $additional['weight_type'] ??
            ($sourceBatch->weight_type ?? 'per_unit');
        $batchData['weight_value'] = $additional['weight_value'] ??
            ($sourceBatch->weight_value ?? $batchData['weight_total']);
    }

    /**
     * Fill price data with business logic (enhanced dummy function)
     */
    private function fillPriceData(array &$batchData, array $additional, $sourceBatch, $livestock): void
    {
        $pricePerUnit = $additional['price_per_unit'] ??
            ($sourceBatch->price_per_unit ?? $livestock->price ?? 0);
        $priceTotal = $additional['price_total'] ??
            ($sourceBatch->price_total ?? ($pricePerUnit * ($additional['initial_quantity'] ?? 0)));
        $priceValue = $additional['price_value'] ??
            ($sourceBatch->price_value ?? $priceTotal);

        $batchData['price_per_unit'] = $pricePerUnit;
        $batchData['price_total'] = $priceTotal;
        $batchData['price_value'] = $priceValue;
        $batchData['price_type'] = $additional['price_type'] ??
            ($sourceBatch->price_type ?? 'per_unit');

        Log::info('💰 Calculated comprehensive batch price data', [
            'price_per_unit' => $pricePerUnit,
            'price_total' => $priceTotal,
            'price_value' => $priceValue,
            'calculation_method' => 'enhanced_business_logic'
        ]);
    }

    /**
     * Validate and fix batch data (with reference parameter)
     */
    private function validateAndFixBatchData(array &$batchData): void
    {
        $requiredFields = [
            'livestock_strain_id',
            'livestock_strain_name',
            'initial_weight',
            'weight_per_unit',
            'weight_total',
            'price_per_unit',
            'price_total',
            'price_value',
            'weight_type',
            'price_type'
        ];

        $fixedFields = [];
        foreach ($requiredFields as $field) {
            if (!isset($batchData[$field]) || $batchData[$field] === null || $batchData[$field] === '') {
                $fixedFields[] = $field;

                // Set appropriate defaults
                if ($field === 'livestock_strain_name') {
                    $batchData[$field] = 'Default Strain';
                } elseif ($field === 'livestock_strain_id') {
                    $batchData[$field] = null; // Allow null for strain_id
                } elseif (in_array($field, ['weight_type', 'price_type'])) {
                    $batchData[$field] = 'per_unit';
                } else {
                    $batchData[$field] = 0;
                }
            }
        }

        if (!empty($fixedFields)) {
            Log::warning('⚠️ Fixed empty batch fields with defaults', [
                'fixed_fields' => $fixedFields,
                'batch_data_sample' => array_intersect_key($batchData, array_flip($fixedFields))
            ]);
        }
    }

    /**
     * Validate business rules
     */
    private function validateBusinessRules(array $batchData, $coop): void
    {
        // Capacity validation
        $currentQuantityInCoop = \App\Models\LivestockBatch::where([
            'farm_id' => $coop->farm_id,
            'coop_id' => $coop->id,
            'status' => 'active'
        ])->sum('initial_quantity');

        $newTotalQuantity = $currentQuantityInCoop + ($batchData['initial_quantity'] ?? 0);

        if ($newTotalQuantity > $coop->capacity) {
            throw new \Exception("Adding batch would exceed coop capacity. Current: {$currentQuantityInCoop}, Adding: {$batchData['initial_quantity']}, Capacity: {$coop->capacity}");
        }

        // Name uniqueness validation
        $existingBatch = \App\Models\LivestockBatch::where([
            'farm_id' => $batchData['farm_id'],
            'coop_id' => $batchData['coop_id'],
            'name' => $batchData['name']
        ])->first();

        if ($existingBatch) {
            throw new \Exception("Batch name '{$batchData['name']}' already exists in this coop");
        }
    }

    /**
     * Update CurrentLivestock safely within transaction
     */
    private function updateCurrentLivestockSafe($livestock, $farmId, $coopId, $companyId): void
    {
        try {
            // Calculate totals from all active batches
            $allBatches = \App\Models\LivestockBatch::where([
                'livestock_id' => $livestock->id,
                'farm_id' => $farmId,
                'coop_id' => $coopId,
                'status' => 'active'
            ])->get();

            $totalQuantity = $allBatches->sum('initial_quantity');
            $totalWeight = $allBatches->sum('weight_total');
            $avgWeight = $totalQuantity > 0 ? $totalWeight / $totalQuantity : 0;

            // Update or create CurrentLivestock
            $currentLivestock = \App\Models\CurrentLivestock::updateOrCreate(
                [
                    'farm_id' => $farmId,
                    'coop_id' => $coopId,
                    'livestock_id' => $livestock->id,
                ],
                [
                    'company_id' => $companyId,
                    'quantity' => $totalQuantity,
                    'weight_total' => $totalWeight,
                    'weight_avg' => $avgWeight,
                    'age' => 0,
                    'status' => 'active',
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]
            );

            Log::info('✅ Updated CurrentLivestock safely (Production)', [
                'current_livestock_id' => $currentLivestock->id,
                'total_quantity' => $totalQuantity,
                'total_weight' => $totalWeight,
                'avg_weight' => $avgWeight,
                'batch_count' => $allBatches->count(),
                'company_id' => $companyId
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Failed to update CurrentLivestock safely', [
                'error' => $e->getMessage(),
                'livestock_id' => $livestock->id,
                'farm_id' => $farmId,
                'coop_id' => $coopId
            ]);
            throw new \Exception("Failed to update CurrentLivestock: " . $e->getMessage());
        }
    }

    /**
     * Create livestock in destination coop if not exists, return Livestock instance only
     */
    public function createLivestockIfNotExists($farmId, $coopId, $tanggal, $name = null, $additional = []): \App\Models\Livestock
    {
        $farm = \App\Models\Farm::findOrFail($farmId);
        $coop = \App\Models\Coop::findOrFail($coopId);
        $companyId = $farm->company_id ?? auth()->user()->company_id ?? null;
        $livestock = \App\Models\Livestock::where([
            'farm_id' => $farmId,
            'coop_id' => $coopId,
            'company_id' => $companyId,
        ])->first();
        if (!$livestock) {
            $livestockName = $name ?? 'PR-' . ($farm->code ?? $farm->name) . '-' . ($coop->code ?? $coop->name) . '-' . (is_string($tanggal) ? $tanggal : $tanggal->format('dmY'));
            $livestock = \App\Models\Livestock::create([
                'name' => $livestockName,
                'farm_id' => $farmId,
                'coop_id' => $coopId,
                'company_id' => $companyId,
                'initial_quantity' => $additional['initial_quantity'] ?? 0,
                'initial_weight' => $additional['initial_weight'] ?? 0,
                'price' => $additional['price'] ?? 0,
                'livestock_breed_id' => $additional['strain_id'] ?? null,
                'breed' => $additional['strain_name'] ?? null,
                'livestock_strain_standard_id' => $additional['strain_standard_id'] ?? null,
                'start_date' => $tanggal,
                'status' => 'active',
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);
            Log::info('✅ Created new Livestock (destination)', [
                'livestock_id' => $livestock->id,
                'name' => $livestock->name,
                'company_id' => $companyId
            ]);
        } else {
            Log::info('✅ Using existing Livestock (destination)', [
                'livestock_id' => $livestock->id,
                'name' => $livestock->name
            ]);
        }
        return $livestock;
    }

    /**
     * Create batch for destination livestock, copying data from source batch
     */
    public function createBatchForLivestock($livestock, $sourceBatch, $quantity, $mutationId = null): \App\Models\LivestockBatch
    {
        $batchName = $sourceBatch->name . '-MUT-' . now()->format('His');

        // Calculate proportional weight and price based on transferred quantity
        $sourceQuantity = $sourceBatch->initial_quantity ?? 1;
        $quantityRatio = $quantity / $sourceQuantity;

        // Calculate proportional values
        $proportionalWeightTotal = ($sourceBatch->weight_total ?? 0) * $quantityRatio;
        $proportionalPriceTotal = ($sourceBatch->price_total ?? 0) * $quantityRatio;

        $batchData = [
            'livestock_id' => $livestock->id,
            'farm_id' => $livestock->farm_id,
            'coop_id' => $livestock->coop_id,
            'company_id' => $livestock->company_id,
            'name' => $batchName,
            'start_date' => now(),
            'source_type' => 'mutation',
            'source_id' => $mutationId,
            'initial_quantity' => $quantity,
            'status' => 'active',
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
            'livestock_strain_id' => $sourceBatch->livestock_strain_id,
            'livestock_strain_name' => $sourceBatch->livestock_strain_name,
            'livestock_strain_standard_id' => $sourceBatch->livestock_strain_standard_id,
            'initial_weight' => $sourceBatch->initial_weight ?? 0,
            'weight' => $sourceBatch->weight ?? 0,
            'weight_per_unit' => $sourceBatch->weight_per_unit ?? 0,
            'weight_total' => $proportionalWeightTotal,
            'weight_type' => $sourceBatch->weight_type ?? 'per_unit',
            'weight_value' => $proportionalWeightTotal,
            'price_per_unit' => $sourceBatch->price_per_unit ?? 0,
            'price_total' => $proportionalPriceTotal,
            'price_value' => $proportionalPriceTotal,
            'price_type' => $sourceBatch->price_type ?? 'per_unit',
        ];

        $batch = \App\Models\LivestockBatch::create($batchData);

        Log::info('✅ Created batch for destination livestock with proportional calculations', [
            'batch_id' => $batch->id,
            'batch_name' => $batch->name,
            'livestock_id' => $livestock->id,
            'quantity' => $quantity,
            'source_quantity' => $sourceQuantity,
            'quantity_ratio' => $quantityRatio,
            'proportional_weight_total' => $proportionalWeightTotal,
            'proportional_price_total' => $proportionalPriceTotal,
            'weight_per_unit' => $batch->weight_per_unit,
            'price_per_unit' => $batch->price_per_unit
        ]);

        return $batch;
    }

    /**
     * Calculate aggregated data from source batches for destination livestock
     *
     * @param array $manualBatches
     * @return array
     */
    private function calculateAggregatedDataForDestination(array $manualBatches): array
    {
        $totalQuantity = 0;
        $totalWeight = 0;
        $totalPrice = 0;
        $strainData = [];
        $sourceBatches = [];

        foreach ($manualBatches as $manualBatch) {
            $sourceBatch = \App\Models\LivestockBatch::find($manualBatch['batch_id']);
            if ($sourceBatch) {
                $sourceBatches[] = $sourceBatch;
                $quantity = $manualBatch['quantity'];

                // Aggregate quantities
                $totalQuantity += $quantity;

                // Calculate proportional weight based on quantity
                $batchWeightPerUnit = $sourceBatch->weight_per_unit ?? 0;
                $batchWeight = $batchWeightPerUnit * $quantity;
                $totalWeight += $batchWeight;

                // Calculate proportional price based on quantity
                $batchPricePerUnit = $sourceBatch->price_per_unit ?? 0;
                $batchPrice = $batchPricePerUnit * $quantity;
                $totalPrice += $batchPrice;

                // Collect strain data (use first available strain info)
                if (empty($strainData) && $sourceBatch->livestock_strain_id) {
                    $strainData = [
                        'strain_id' => $sourceBatch->livestock_strain_id,
                        'strain_name' => $sourceBatch->livestock_strain_name,
                        'strain_standard_id' => $sourceBatch->livestock_strain_standard_id,
                    ];
                }
            }
        }

        // Calculate averages
        $avgWeightPerUnit = $totalQuantity > 0 ? $totalWeight / $totalQuantity : 0;
        $avgPricePerUnit = $totalQuantity > 0 ? $totalPrice / $totalQuantity : 0;

        $aggregatedData = [
            'initial_quantity' => $totalQuantity,
            'initial_weight' => $avgWeightPerUnit,
            'price' => $avgPricePerUnit,
            'weight_per_unit' => $avgWeightPerUnit,
            'weight_total' => $totalWeight,
            'price_per_unit' => $avgPricePerUnit,
            'price_total' => $totalPrice,
            'source_type' => 'mutation',
            'source_batch_count' => count($sourceBatches),
        ];

        // Add strain data if available
        if (!empty($strainData)) {
            $aggregatedData = array_merge($aggregatedData, $strainData);
        }

        Log::info('📊 Calculated aggregated data for destination livestock', [
            'total_quantity' => $totalQuantity,
            'total_weight' => $totalWeight,
            'total_price' => $totalPrice,
            'avg_weight_per_unit' => $avgWeightPerUnit,
            'avg_price_per_unit' => $avgPricePerUnit,
            'source_batch_count' => count($sourceBatches),
            'strain_data' => $strainData
        ]);

        return $aggregatedData;
    }

    /**
     * Sync destination livestock totals from its batches
     *
     * @param \App\Models\Livestock $livestock
     * @return void
     */
    private function syncDestinationLivestockTotals(\App\Models\Livestock $livestock): void
    {
        // Calculate totals from all active batches
        $activeBatches = \App\Models\LivestockBatch::where([
            'livestock_id' => $livestock->id,
            'status' => 'active'
        ])->get();

        $totalQuantity = $activeBatches->sum('initial_quantity');
        $totalWeightTotal = $activeBatches->sum('weight_total');
        $totalPriceTotal = $activeBatches->sum('price_total');

        // Calculate averages
        $avgWeightPerUnit = $totalQuantity > 0 ? $totalWeightTotal / $totalQuantity : 0;
        $avgPricePerUnit = $totalQuantity > 0 ? $totalPriceTotal / $totalQuantity : 0;

        // Update livestock with calculated totals
        $livestock->update([
            'initial_quantity' => $totalQuantity,
            'initial_weight' => $avgWeightPerUnit,
            'price' => $avgPricePerUnit,
            'updated_by' => auth()->id(),
        ]);

        Log::info('🔄 Synced destination livestock totals from batches', [
            'livestock_id' => $livestock->id,
            'livestock_name' => $livestock->name,
            'batch_count' => $activeBatches->count(),
            'total_quantity' => $totalQuantity,
            'total_weight_total' => $totalWeightTotal,
            'total_price_total' => $totalPriceTotal,
            'avg_weight_per_unit' => $avgWeightPerUnit,
            'avg_price_per_unit' => $avgPricePerUnit,
            'before_sync' => [
                'initial_quantity' => $livestock->getOriginal('initial_quantity'),
                'initial_weight' => $livestock->getOriginal('initial_weight'),
                'price' => $livestock->getOriginal('price'),
            ],
            'after_sync' => [
                'initial_quantity' => $totalQuantity,
                'initial_weight' => $avgWeightPerUnit,
                'price' => $avgPricePerUnit,
            ]
        ]);
    }

    /**
     * Public cleanup method to update all related data after mutation deletion
     */
    public function cleanupAfterMutationDelete(LivestockMutation $mutation): void
    {
        Log::debug('[CLEANUP] Start cleanupAfterMutationDelete', [
            'mutation_id' => $mutation->id,
            'user_id' => auth()->id(),
            'user_name' => auth()->user()->name ?? 'system',
        ]);

        // Cek config history_enabled
        $historySettings = CompanyConfig::getManualMutationHistorySettings();
        $useForceDelete = !($historySettings['history_enabled'] ?? false);
        Log::info('[CLEANUP] Delete mode', ['force_delete' => $useForceDelete]);

        // 1. Hapus semua LivestockBatch dengan source_type = 'mutation' dan source_id = mutation id
        $batches = \App\Models\LivestockBatch::where('source_type', 'mutation')
            ->where('source_id', $mutation->id)
            ->get();
        Log::debug('[CLEANUP] Found batches to delete', [
            'mutation_id' => $mutation->id,
            'batch_ids' => $batches->pluck('id')->toArray(),
            'count' => $batches->count(),
        ]);
        foreach ($batches as $batch) {
            $livestock = $batch->livestock;
            Log::debug('[CLEANUP] Deleting batch', [
                'batch_id' => $batch->id,
                'livestock_id' => $batch->livestock_id,
                'mutation_id' => $mutation->id,
            ]);
            if ($useForceDelete) {
                $batch->forceDelete();
            } else {
                $batch->delete();
            }
            Log::info('🗑️ LivestockBatch deleted after mutation deletion', [
                'batch_id' => $batch->id,
                'livestock_id' => $batch->livestock_id,
                'mutation_id' => $mutation->id,
            ]);
            $this->logLivestockDeletionDocumentation($batch, 'LivestockBatch');

            // Setelah batch dihapus, cek apakah livestock terkait perlu dihapus
            if ($livestock) {
                $hasMutations = $livestock->allMutations()->count() > 0;
                $activeBatchCount = $livestock->batches()->where('status', 'active')->count();
                Log::debug('[CLEANUP] Livestock status after batch delete', [
                    'livestock_id' => $livestock->id,
                    'has_mutations' => $hasMutations,
                    'active_batch_count' => $activeBatchCount,
                ]);
                if (!$hasMutations && $activeBatchCount === 0) {
                    // Hapus referensi di tabel coops terlebih dahulu untuk menghindari foreign key constraint
                    $coops = \App\Models\Coop::where('livestock_id', $livestock->id)->get();
                    foreach ($coops as $coop) {
                        Log::debug('[CLEANUP] Removing livestock reference from coop', [
                            'coop_id' => $coop->id,
                            'coop_name' => $coop->name,
                            'livestock_id' => $livestock->id,
                        ]);
                        $coop->update([
                            'livestock_id' => null,
                            'quantity' => 0,
                            'weight' => 0,
                            'status' => 'active',
                            'updated_by' => auth()->id(),
                        ]);
                        Log::info('🔄 Removed livestock reference from coop', [
                            'coop_id' => $coop->id,
                            'livestock_id' => $livestock->id,
                        ]);
                    }

                    $current = $livestock->currentLivestock;
                    if ($current) {
                        Log::debug('[CLEANUP] Deleting CurrentLivestock', [
                            'livestock_id' => $livestock->id,
                            'current_livestock_id' => $current->id,
                        ]);
                        if ($useForceDelete) {
                            $current->forceDelete();
                        } else {
                            $current->delete();
                        }
                        Log::info('🗑️ CurrentLivestock deleted after all mutations and batches removed', [
                            'livestock_id' => $livestock->id,
                            'current_livestock_id' => $current->id,
                        ]);
                        $this->logLivestockDeletionDocumentation($livestock, 'CurrentLivestock');
                    }
                    Log::debug('[CLEANUP] Deleting Livestock', [
                        'livestock_id' => $livestock->id,
                        'livestock_name' => $livestock->name,
                    ]);
                    if ($useForceDelete) {
                        $livestock->forceDelete();
                    } else {
                        $livestock->delete();
                    }
                    Log::info('🗑️ Livestock deleted after all mutations and batches removed', [
                        'livestock_id' => $livestock->id,
                        'livestock_name' => $livestock->name,
                    ]);
                    $this->logLivestockDeletionDocumentation($livestock, 'Livestock');
                }
            }
        }

        // 2. Lanjutkan dengan logic existing untuk sourceLivestock dan destinationLivestock
        foreach (['sourceLivestock', 'destinationLivestock'] as $rel) {
            $livestock = $mutation->$rel;
            if ($livestock) {
                Log::debug('[CLEANUP] Update totals for livestock', [
                    'livestock_id' => $livestock->id,
                    'livestock_name' => $livestock->name,
                ]);
                $this->updateLivestockTotals($livestock);
                $this->updateCurrentLivestock($livestock);

                $hasMutations = $livestock->allMutations()->count() > 0;
                $activeBatchCount = $livestock->batches()->where('status', 'active')->count();
                Log::debug('[CLEANUP] Livestock status after update', [
                    'livestock_id' => $livestock->id,
                    'has_mutations' => $hasMutations,
                    'active_batch_count' => $activeBatchCount,
                ]);
                if (!$hasMutations && $activeBatchCount === 0) {
                    // Hapus referensi di tabel coops terlebih dahulu untuk menghindari foreign key constraint
                    $coops = \App\Models\Coop::where('livestock_id', $livestock->id)->get();
                    foreach ($coops as $coop) {
                        Log::debug('[CLEANUP] Removing livestock reference from coop (post-update)', [
                            'coop_id' => $coop->id,
                            'coop_name' => $coop->name,
                            'livestock_id' => $livestock->id,
                        ]);
                        $coop->update([
                            'livestock_id' => null,
                            'quantity' => 0,
                            'weight' => 0,
                            'status' => 'active',
                            'updated_by' => auth()->id(),
                        ]);
                        Log::info('🔄 Removed livestock reference from coop (post-update)', [
                            'coop_id' => $coop->id,
                            'livestock_id' => $livestock->id,
                        ]);
                    }

                    $current = $livestock->currentLivestock;
                    if ($current) {
                        Log::debug('[CLEANUP] Deleting CurrentLivestock (post-update)', [
                            'livestock_id' => $livestock->id,
                            'current_livestock_id' => $current->id,
                        ]);
                        if ($useForceDelete) {
                            $current->forceDelete();
                        } else {
                            $current->delete();
                        }
                        Log::info('🗑️ CurrentLivestock deleted after all mutations and batches removed', [
                            'livestock_id' => $livestock->id,
                            'current_livestock_id' => $current->id,
                        ]);
                        $this->logLivestockDeletionDocumentation($livestock, 'CurrentLivestock');
                    }
                    Log::debug('[CLEANUP] Deleting Livestock (post-update)', [
                        'livestock_id' => $livestock->id,
                        'livestock_name' => $livestock->name,
                    ]);
                    if ($useForceDelete) {
                        $livestock->forceDelete();
                    } else {
                        $livestock->delete();
                    }
                    Log::info('🗑️ Livestock deleted after all mutations and batches removed', [
                        'livestock_id' => $livestock->id,
                        'livestock_name' => $livestock->name,
                    ]);
                    $this->logLivestockDeletionDocumentation($livestock, 'Livestock');
                }
            }
        }
        Log::debug('[CLEANUP] cleanupAfterMutationDelete complete', [
            'mutation_id' => $mutation->id,
        ]);
    }

    /**
     * Dokumentasi penghapusan Livestock/CurrentLivestock
     */
    protected function logLivestockDeletionDocumentation($livestock, $type = 'Livestock')
    {
        $logPath = base_path('docs/debugging/delete-livestock-mutation-log.md');
        $logEntry = "\n## [" . now()->format('Y-m-d H:i:s') . "] Deleted {$type}: {$livestock->id}\n";
        $logEntry .= "- Name: {$livestock->name}\n";
        $logEntry .= "- Dijalankan oleh user: " . (auth()->user()->name ?? 'system') . " (ID: " . (auth()->id() ?? '-') . ")\n";
        $logEntry .= "- Status: SUCCESS\n";
        file_put_contents($logPath, $logEntry, FILE_APPEND);
    }

    /**
     * Reverse mutation quantities for edit mode (now public for deletion use)
     *
     * @param LivestockMutation $mutation
     * @return void
     */
    public function reverseMutationQuantities(LivestockMutation $mutation): void
    {
        try {
            // Eager load items and batches for efficiency
            $mutation->loadMissing(['items.batch']);
            $handledBatchIds = [];

            // Multi-batch support (modern, recommended)
            foreach ($mutation->items as $item) {
                $batch = $item->batch;
                if ($batch && $mutation->direction === 'out') {
                    $old = $batch->quantity_mutated;
                    $batch->quantity_mutated = max(0, $batch->quantity_mutated - $item->quantity);
                    $batch->save();
                    Log::info('🔄 Reversed batch mutation quantities (multi-batch)', [
                        'batch_id' => $batch->id,
                        'old_quantity_mutated' => $old,
                        'new_quantity_mutated' => $batch->quantity_mutated,
                        'quantity_reversed' => $item->quantity,
                        'direction' => $mutation->direction,
                        'mutation_id' => $mutation->id,
                    ]);
                    $handledBatchIds[$batch->id] = true;
                }
            }

            // Legacy/single-batch support (if mutation->data['batch_id'] exists and not already handled)
            $data = $mutation->data ?? [];
            $batchId = $data['batch_id'] ?? null;
            if ($batchId && empty($handledBatchIds[$batchId])) {
                $batch = LivestockBatch::find($batchId);
                if ($batch && $mutation->direction === 'out') {
                    $old = $batch->quantity_mutated;
                    $batch->quantity_mutated = max(0, $batch->quantity_mutated - $mutation->jumlah);
                    $batch->save();
                    Log::info('🔄 Reversed batch mutation quantities (legacy)', [
                        'batch_id' => $batchId,
                        'old_quantity_mutated' => $old,
                        'new_quantity_mutated' => $batch->quantity_mutated,
                        'quantity_reversed' => $mutation->jumlah,
                        'direction' => $mutation->direction,
                        'mutation_id' => $mutation->id,
                    ]);
                }
            }

            // Reverse livestock totals (existing logic)
            $sourceLivestock = Livestock::find($mutation->source_livestock_id);
            if ($sourceLivestock && $mutation->direction === 'out') {
                $sourceLivestock->quantity_mutated_out = max(0, $sourceLivestock->quantity_mutated_out - $mutation->jumlah);
                $sourceLivestock->save();
            }
            $destinationLivestock = Livestock::find($mutation->destination_livestock_id);
            if ($destinationLivestock && $mutation->direction === 'in') {
                $destinationLivestock->quantity_mutated_in = max(0, $destinationLivestock->quantity_mutated_in - $mutation->jumlah);
                $destinationLivestock->save();
            }
        } catch (\Exception $e) {
            Log::error('❌ Error reversing mutation quantities', [
                'mutation_id' => $mutation->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Process FIFO (First In First Out) mutation
     * Automatically selects oldest batches first
     *
     * @param Livestock $sourceLivestock
     * @param array $mutationData
     * @return array
     * @throws Exception
     */
    public function processFifoMutation(Livestock $sourceLivestock, array $mutationData): array
    {
        // Guard: Cegah mutasi ke diri sendiri (livestock atau kandang sama)
        $isSameLivestock = isset($mutationData['destination_livestock_id']) && $sourceLivestock->id === $mutationData['destination_livestock_id'];
        $isSameCoop = isset($mutationData['destination_coop_id']) && $sourceLivestock->coop_id === $mutationData['destination_coop_id'];
        if ($isSameLivestock || $isSameCoop) {
            Log::warning('🚫 Mutasi FIFO ke diri sendiri dicegah di service', [
                'sourceLivestockId' => $sourceLivestock->id,
                'destinationLivestockId' => $mutationData['destination_livestock_id'] ?? null,
                'sourceCoopId' => $sourceLivestock->coop_id,
                'destinationCoopId' => $mutationData['destination_coop_id'] ?? null
            ]);
            throw new Exception('Mutasi ke ternak atau kandang yang sama tidak diperbolehkan.');
        }

        Log::info('🔄 Starting FIFO mutation process', [
            'source_livestock_id' => $sourceLivestock->id,
            'source_livestock_name' => $sourceLivestock->name,
            'requested_quantity' => $mutationData['quantity'] ?? 0,
            'user_id' => auth()->id()
        ]);

        // Validate mutation data
        $this->validateMutationData($mutationData);

        // Get FIFO batch selection
        $fifoBatches = $this->getFifoBatchSelection($sourceLivestock, $mutationData['quantity']);

        if (empty($fifoBatches['selected_batches'])) {
            throw new Exception("Tidak ada batch yang tersedia untuk mutasi FIFO. Kuantitas yang diminta: {$mutationData['quantity']}");
        }

        // Check if can fulfill total quantity
        if (!$fifoBatches['can_fulfill']) {
            throw new Exception("Kuantitas batch tidak mencukupi. Tersedia: {$fifoBatches['total_available']}, Diminta: {$mutationData['quantity']}");
        }

        DB::beginTransaction();
        try {
            // Check for existing mutations (edit mode)
            $existingMutationIds = $mutationData['existing_mutation_ids'] ?? [];
            $isEditMode = !empty($existingMutationIds);

            // Always reverse mutation quantities for all existing mutations before update (edit mode)
            if ($isEditMode) {
                foreach ($existingMutationIds as $mutationId) {
                    $existingMutation = \App\Models\LivestockMutation::find($mutationId);
                    if ($existingMutation) {
                        $this->reverseMutationQuantities($existingMutation);
                        \Log::info('🔄 FIFO edit mode: reversed mutation quantities before update', [
                            'mutation_id' => $mutationId,
                            'jumlah' => $existingMutation->jumlah
                        ]);
                    }
                }
                // Rollback destination batches before creating new ones
                $this->reverseDestinationLivestockBatches($existingMutationIds);
                $this->handleEditMode($existingMutationIds, $mutationData);

                // --- IMPROVEMENT: Always sync source & destination livestock after rollback ---
                $sourceLivestock->refresh();
                $this->updateLivestockTotals($sourceLivestock);
                $this->updateCurrentLivestock($sourceLivestock);
                if (isset($mutationData['destination_livestock_id'])) {
                    $destinationLivestock = \App\Models\Livestock::find($mutationData['destination_livestock_id']);
                    if ($destinationLivestock) {
                        $this->updateLivestockTotals($destinationLivestock);
                        $this->updateCurrentLivestock($destinationLivestock);
                    }
                }
            }

            // Create mutation header
            $mutation = $this->createMutationHeader($sourceLivestock, $mutationData, $isEditMode);

            // Create mutation items for each FIFO batch
            $createdItems = [];
            foreach ($fifoBatches['selected_batches'] as $fifoBatch) {
                $mutationItem = $this->createFifoMutationItem($mutation, $fifoBatch);
                $createdItems[] = $mutationItem;

                // Update batch quantities
                $this->updateBatchQuantities($fifoBatch['batch'], 'out', $fifoBatch['quantity_to_mutate']);
            }

            // Handle destination
            if (isset($mutationData['destination_coop_id'])) {
                // Handle destination coop for FIFO mutation
                $this->handleFifoDestinationCoop($mutationData['destination_coop_id'], $fifoBatches['total_quantity'], $mutationData, $mutation, $fifoBatches['selected_batches']);
            } elseif (isset($mutationData['destination_livestock_id'])) {
                // Handle destination livestock for FIFO mutation
                $this->handleFifoDestinationLivestock($mutationData['destination_livestock_id'], $fifoBatches['total_quantity'], $mutationData, $mutation, $fifoBatches['selected_batches']);
            }

            // Update mutation header with final totals
            $mutation->jumlah = $fifoBatches['total_quantity'];
            $mutation->save();

            // --- IMPROVEMENT: Always sync source & destination livestock after mutation ---
            $sourceLivestock->refresh();
            $this->updateLivestockTotals($sourceLivestock);
            $this->updateCurrentLivestock($sourceLivestock);
            if (isset($mutationData['destination_livestock_id'])) {
                $destinationLivestock = \App\Models\Livestock::find($mutationData['destination_livestock_id']);
                if ($destinationLivestock) {
                    $this->updateLivestockTotals($destinationLivestock);
                    $this->updateCurrentLivestock($destinationLivestock);
                }
            }

            DB::commit();

            $successMessage = $this->buildSuccessMessage($isEditMode, $mutationData['direction']);

            Log::info('✅ FIFO mutation completed successfully', [
                'mutation_id' => $mutation->id,
                'source_livestock_id' => $sourceLivestock->id,
                'total_quantity' => $fifoBatches['total_quantity'],
                'batches_used' => count($fifoBatches['selected_batches']),
                'is_edit_mode' => $isEditMode
            ]);

            return [
                'success' => true,
                'message' => $successMessage,
                'mutation_id' => $mutation->id,
                'method' => 'fifo',
                'total_quantity' => $fifoBatches['total_quantity'],
                'batches_used' => count($fifoBatches['selected_batches']),
                'fifo_batches' => $fifoBatches['selected_batches'],
                'is_edit_mode' => $isEditMode
            ];
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('❌ FIFO mutation failed', [
                'error' => $e->getMessage(),
                'source_livestock_id' => $sourceLivestock->id,
                'mutation_data' => $mutationData
            ]);
            throw $e;
        }
    }

    /**
     * Get FIFO batch selection for mutation
     *
     * @param Livestock $livestock
     * @param int $requestedQuantity
     * @return array
     */
    private function getFifoBatchSelection(Livestock $livestock, int $requestedQuantity): array
    {
        // Get available batches ordered by start_date (oldest first - FIFO)
        $availableBatches = $livestock->batches()
            ->where('status', 'active')
            ->whereRaw('(initial_quantity - quantity_depletion - quantity_sales - quantity_mutated) > 0')
            ->orderBy('start_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $selectedBatches = [];
        $remainingQuantity = $requestedQuantity;
        $totalAvailable = 0;

        foreach ($availableBatches as $batch) {
            $availableQuantity = $this->calculateBatchAvailableQuantity($batch);
            $totalAvailable += $availableQuantity;

            if ($remainingQuantity <= 0) {
                break;
            }

            $quantityToMutate = min($availableQuantity, $remainingQuantity);

            $selectedBatches[] = [
                'batch' => $batch,
                'batch_id' => $batch->id,
                'batch_name' => $batch->name,
                'start_date' => $batch->start_date,
                'age_days' => $batch->start_date ? now()->diffInDays($batch->start_date) : null,
                'available_quantity' => $availableQuantity,
                'quantity_to_mutate' => $quantityToMutate,
                'remaining_after_mutation' => $availableQuantity - $quantityToMutate
            ];

            $remainingQuantity -= $quantityToMutate;
        }

        $totalQuantity = $requestedQuantity - $remainingQuantity;
        $canFulfill = $remainingQuantity === 0;

        return [
            'selected_batches' => $selectedBatches,
            'total_quantity' => $totalQuantity,
            'total_available' => $totalAvailable,
            'can_fulfill' => $canFulfill,
            'shortfall' => $remainingQuantity,
            'batches_count' => count($selectedBatches)
        ];
    }

    /**
     * Create FIFO mutation item
     *
     * @param LivestockMutation $mutation
     * @param array $fifoBatch
     * @return LivestockMutationItem
     */
    private function createFifoMutationItem(LivestockMutation $mutation, array $fifoBatch): LivestockMutationItem
    {
        $batch = $fifoBatch['batch'];
        $quantity = $fifoBatch['quantity_to_mutate'];

        $mutationItem = LivestockMutationItem::create([
            'id' => Str::uuid(),
            'livestock_mutation_id' => $mutation->id,
            'batch_id' => $batch->id,
            'quantity' => $quantity,
            'weight' => $this->calculateBatchWeight($batch, $quantity),
            'payload' => [
                'method' => 'fifo',
                'batch_age_days' => $fifoBatch['age_days'],
                'batch_start_date' => $batch->start_date,
                'selection_order' => 'oldest_first',
                'available_before_mutation' => $fifoBatch['available_quantity'],
                'remaining_after_mutation' => $fifoBatch['remaining_after_mutation']
            ],
            'created_by' => auth()->id()
        ]);

        Log::info('📝 FIFO mutation item created', [
            'mutation_item_id' => $mutationItem->id,
            'batch_id' => $batch->id,
            'batch_name' => $batch->name,
            'quantity' => $quantity,
            'age_days' => $fifoBatch['age_days']
        ]);

        return $mutationItem;
    }

    /**
     * Calculate batch weight for mutation
     *
     * @param LivestockBatch $batch
     * @param int $quantity
     * @return float
     */
    private function calculateBatchWeight(LivestockBatch $batch, int $quantity): float
    {
        if ($batch->initial_weight && $batch->initial_quantity > 0) {
            $weightPerUnit = $batch->initial_weight / $batch->initial_quantity;
            return round($weightPerUnit * $quantity, 2);
        }
        return 0;
    }

    /**
     * Preview FIFO batch mutation
     *
     * @param array $mutationData
     * @return array
     */
    public function previewFifoBatchMutation(array $mutationData): array
    {
        if (!isset($mutationData['quantity']) || !is_numeric($mutationData['quantity']) || $mutationData['quantity'] <= 0) {
            throw new Exception("Quantity is required and must be a positive number for FIFO mutation");
        }

        $sourceLivestock = Livestock::findOrFail($mutationData['source_livestock_id']);
        $fifoBatches = $this->getFifoBatchSelection($sourceLivestock, $mutationData['quantity']);

        $preview = [
            'method' => 'fifo',
            'livestock_id' => $sourceLivestock->id,
            'livestock_name' => $sourceLivestock->name,
            'requested_quantity' => $mutationData['quantity'],
            'total_quantity' => $fifoBatches['total_quantity'],
            'can_fulfill' => $fifoBatches['can_fulfill'],
            'shortfall' => $fifoBatches['shortfall'],
            'total_available' => $fifoBatches['total_available'],
            'batches_count' => $fifoBatches['batches_count'],
            'batches_preview' => array_map(function ($fifoBatch) {
                return [
                    'batch_id' => $fifoBatch['batch_id'],
                    'batch_name' => $fifoBatch['batch_name'],
                    'start_date' => $fifoBatch['start_date'],
                    'age_days' => $fifoBatch['age_days'],
                    'available_quantity' => $fifoBatch['available_quantity'],
                    'quantity_to_mutate' => $fifoBatch['quantity_to_mutate'],
                    'remaining_after_mutation' => $fifoBatch['remaining_after_mutation'],
                    'utilization_rate' => $fifoBatch['batch']->initial_quantity > 0
                        ? round((($fifoBatch['batch']->quantity_depletion + $fifoBatch['batch']->quantity_sales + $fifoBatch['batch']->quantity_mutated + $fifoBatch['quantity_to_mutate']) / $fifoBatch['batch']->initial_quantity) * 100, 2)
                        : 0
                ];
            }, $fifoBatches['selected_batches']),
            'fifo_order' => 'oldest_first',
            'validation_passed' => $fifoBatches['can_fulfill']
        ];

        Log::info('👁️ FIFO mutation preview generated', [
            'source_livestock_id' => $sourceLivestock->id,
            'requested_quantity' => $mutationData['quantity'],
            'can_fulfill' => $fifoBatches['can_fulfill'],
            'batches_count' => $fifoBatches['batches_count']
        ]);

        return $preview;
    }

    /**
     * Handle destination coop for FIFO mutation
     *
     * @param string $destinationCoopId
     * @param int $totalQuantity
     * @param array $mutationData
     * @param LivestockMutation $mutation
     * @param array $fifoBatches
     * @return void
     */
    private function handleFifoDestinationCoop(string $destinationCoopId, int $totalQuantity, array $mutationData, LivestockMutation $mutation, array $fifoBatches): void
    {
        $coop = \App\Models\Coop::findOrFail($destinationCoopId);
        $farmId = $coop->farm_id;
        $tanggal = $mutationData['date'] ?? now();

        // Calculate aggregated data from FIFO batches
        $aggregatedData = $this->calculateAggregatedDataFromFifoBatches($fifoBatches);

        // Create destination livestock if not exists
        $destinationLivestock = $this->createLivestockIfNotExists($farmId, $destinationCoopId, $tanggal, null, $aggregatedData);

        // Update mutation with destination livestock
        $mutation->update([
            'destination_livestock_id' => $destinationLivestock->id,
            'updated_by' => auth()->id(),
        ]);

        // Create batches for destination livestock based on FIFO selection
        foreach ($fifoBatches as $fifoBatch) {
            $sourceBatch = $fifoBatch['batch'];
            $this->createBatchForLivestock($destinationLivestock, $sourceBatch, $fifoBatch['quantity_to_mutate'], $mutation->id);
        }

        // Update current livestock and coop
        $this->updateCurrentLivestockSafe($destinationLivestock, $farmId, $destinationCoopId, $destinationLivestock->company_id);
        $this->syncDestinationLivestockTotals($destinationLivestock);

        // Update coop data
        $totalQuantity = \App\Models\LivestockBatch::where([
            'livestock_id' => $destinationLivestock->id,
            'farm_id' => $farmId,
            'coop_id' => $destinationCoopId,
            'status' => 'active'
        ])->sum('initial_quantity');

        $totalWeight = \App\Models\LivestockBatch::where([
            'livestock_id' => $destinationLivestock->id,
            'farm_id' => $farmId,
            'coop_id' => $destinationCoopId,
            'status' => 'active'
        ])->sum('weight_total');

        $coop->update([
            'quantity' => $totalQuantity,
            'weight' => $totalWeight,
            'status' => $totalQuantity > 0 ? 'in_use' : 'active',
            'livestock_id' => $destinationLivestock->id,
        ]);

        Log::info('✅ FIFO destination coop handled', [
            'coop_id' => $destinationCoopId,
            'destination_livestock_id' => $destinationLivestock->id,
            'total_quantity' => $totalQuantity,
            'fifo_batches_count' => count($fifoBatches)
        ]);
    }

    /**
     * Handle destination livestock for FIFO mutation
     *
     * @param string $destinationLivestockId
     * @param int $totalQuantity
     * @param array $mutationData
     * @param LivestockMutation $mutation
     * @param array $fifoBatches
     * @return void
     */
    private function handleFifoDestinationLivestock(string $destinationLivestockId, int $totalQuantity, array $mutationData, LivestockMutation $mutation, array $fifoBatches): void
    {
        $destinationLivestock = Livestock::findOrFail($destinationLivestockId);

        // Update mutation with destination livestock
        $mutation->update([
            'destination_livestock_id' => $destinationLivestockId,
            'updated_by' => auth()->id(),
        ]);

        // Create batches for destination livestock based on FIFO selection
        foreach ($fifoBatches as $fifoBatch) {
            $sourceBatch = $fifoBatch['batch'];
            $this->createBatchForLivestock($destinationLivestock, $sourceBatch, $fifoBatch['quantity_to_mutate'], $mutation->id);
        }

        // Update destination livestock totals
        $this->updateLivestockTotals($destinationLivestock);
        $this->updateCurrentLivestock($destinationLivestock);
        $this->syncDestinationLivestockTotals($destinationLivestock);

        Log::info('✅ FIFO destination livestock handled', [
            'destination_livestock_id' => $destinationLivestockId,
            'destination_livestock_name' => $destinationLivestock->name,
            'total_quantity' => $totalQuantity,
            'fifo_batches_count' => count($fifoBatches)
        ]);
    }

    /**
     * Calculate aggregated data from FIFO batches
     *
     * @param array $fifoBatches
     * @return array
     */
    private function calculateAggregatedDataFromFifoBatches(array $fifoBatches): array
    {
        $totalQuantity = 0;
        $totalWeight = 0;
        $totalPrice = 0;
        $strainIds = [];
        $strainNames = [];

        foreach ($fifoBatches as $fifoBatch) {
            $batch = $fifoBatch['batch'];
            $quantity = $fifoBatch['quantity_to_mutate'];

            $totalQuantity += $quantity;

            // Calculate weight proportionally
            if ($batch->initial_weight && $batch->initial_quantity > 0) {
                $weightPerUnit = $batch->initial_weight / $batch->initial_quantity;
                $totalWeight += $weightPerUnit * $quantity;
            }

            // Calculate price proportionally
            if ($batch->price && $batch->initial_quantity > 0) {
                $pricePerUnit = $batch->price / $batch->initial_quantity;
                $totalPrice += $pricePerUnit * $quantity;
            }

            // Collect strain information
            if ($batch->strain_id) {
                $strainIds[] = $batch->strain_id;
                $strainNames[] = $batch->strain_name ?? 'Unknown Strain';
            }
        }

        return [
            'initial_quantity' => $totalQuantity,
            'initial_weight' => round($totalWeight, 2),
            'price' => round($totalPrice, 2),
            'strain_id' => !empty($strainIds) ? $strainIds[0] : null, // Use first strain
            'strain_name' => !empty($strainNames) ? $strainNames[0] : null,
            'source_batch_count' => count($fifoBatches),
            'fifo_method' => true
        ];
    }

    /**
     * Rollback destination batches for all existing mutations before edit
     * @param array $existingMutationIds
     */
    private function reverseDestinationLivestockBatches(array $existingMutationIds): void
    {
        foreach ($existingMutationIds as $mutationId) {
            $mutation = \App\Models\LivestockMutation::find($mutationId);
            if (!$mutation || !$mutation->destination_livestock_id) continue;
            $destinationLivestockId = $mutation->destination_livestock_id;
            $batches = \App\Models\LivestockBatch::where('livestock_id', $destinationLivestockId)
                ->where('source_type', 'mutation')
                ->where('source_id', $mutationId)
                ->get();
            foreach ($batches as $batch) {
                $batch->delete();
                \Log::info('🗑️ Deleted destination batch from previous mutation (edit rollback)', [
                    'batch_id' => $batch->id,
                    'livestock_id' => $destinationLivestockId,
                    'mutation_id' => $mutationId
                ]);
            }
            // Sync destination livestock totals after batch deletion
            $destinationLivestock = \App\Models\Livestock::find($destinationLivestockId);
            if ($destinationLivestock) {
                $this->syncDestinationLivestockTotals($destinationLivestock);
                \Log::info('🔄 Synced destination livestock totals after batch rollback', [
                    'livestock_id' => $destinationLivestockId
                ]);
            }
        }
    }
}

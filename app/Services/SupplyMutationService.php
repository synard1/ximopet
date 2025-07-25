<?php

namespace App\Services;

use App\Models\SupplyMutation;
use App\Models\SupplyMutationItem;
use App\Models\SupplyStock;
use App\Models\CurrentSupply;
use App\Models\Farm;
use App\Models\Coop;
use App\Models\Livestock;
use App\Models\Supply;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;
use App\Services\Recording\UnitConversionService;

class SupplyMutationService
{
    /**
     * Process supply mutation with flexible destination handling
     *
     * @param array $mutationData
     * @param array $items
     * @param string|null $mutationId
     * @param bool $withHistory
     * @return SupplyMutation
     */
    public function processSupplyMutation(array $mutationData, array $items, ?string $mutationId = null, bool $withHistory = false): SupplyMutation
    {
        return DB::transaction(function () use ($mutationData, $items, $mutationId, $withHistory) {
            Log::info('Starting supply mutation process', [
                'mutationId' => $mutationId,
                'withHistory' => $withHistory,
                'destination_type' => $this->getDestinationType($mutationData),
                'items_count' => count($items)
            ]);

            $isUpdate = !empty($mutationId);

            // Validate destination configuration
            $this->validateDestinationConfiguration($mutationData);

            // Find or create mutation
            $mutation = $isUpdate
                ? SupplyMutation::findOrFail($mutationId)
                : new SupplyMutation();

            // Fill mutation data
            $mutation->fill([
                'id' => $mutationId ?? Str::uuid(),
                'company_id' => \Illuminate\Support\Facades\Auth::user()->company_id,
                'date' => $mutationData['date'],
                'from_farm_id' => $mutationData['source_farm_id'],
                'to_farm_id' => $mutationData['destination_farm_id'],
                'from_livestock_id' => $mutationData['source_livestock_id'] ?? null,
                'to_livestock_id' => $mutationData['destination_livestock_id'] ?? null,
                'from_coop_id' => $mutationData['source_coop_id'] ?? null,
                'to_coop_id' => $mutationData['destination_coop_id'] ?? null,
                'status' => $mutationData['status'] ?? 'draft',
                'created_by' => \Illuminate\Support\Facades\Auth::id(),
                'updated_by' => \Illuminate\Support\Facades\Auth::id(),
            ]);

            if (!$isUpdate) {
                $mutation->save();
                Log::info('New supply mutation created', ['mutationId' => $mutation->id]);
            } else {
                $mutation->update();
                Log::info('Existing supply mutation updated', ['mutationId' => $mutation->id]);

                // Handle old mutation items based on history preference
                if ($withHistory) {
                    // Keep old items for audit trail
                    Log::info('Keeping old mutation items for audit trail');
                } else {
                    // Delete old items and create new ones
                    $mutation->supplyMutationDetails()->delete();
                    Log::info('Deleted old mutation items for replacement');
                }
            }

            // Process mutation items
            $this->processMutationItems($mutation, $items, $mutationData);

            // Handle destination based on type
            $this->handleDestination($mutation, $mutationData);

            Log::info('Supply mutation process completed', [
                'mutationId' => $mutation->id,
                'destination_type' => $mutation->destination_type,
                'items_processed' => $mutation->supplyMutationDetails()->count()
            ]);

            return $mutation;
        });
    }

    /**
     * Create supply mutation detail and items after master mutation is created in mutations table
     * @param \App\Models\Mutation $mutation
     * @param array $items
     * @param array $mutationData
     * @param bool $withHistory
     * @return SupplyMutation|null
     */
    public static function createSupplyMutation(\App\Models\Mutation $mutation, array $items, array $mutationData = [], bool $withHistory = false)
    {
        try {
            \Log::info('🔄 Starting SupplyMutation creation', [
                'mutationId' => $mutation->id,
                'items_count' => count($items),
                'withHistory' => $withHistory
            ]);

            // 1. Buat supply_mutation (detail)
            $supplyMutationData = [
                'id' => \Str::uuid(),
                'mutation_id' => $mutation->id,
                'company_id' => $mutation->company_id ?? (auth()->user()->company_id ?? null),
                'date' => $mutation->date,
                'from_farm_id' => $mutation->from_farm_id,
                'to_farm_id' => $mutation->to_farm_id,
                'from_livestock_id' => $mutation->from_livestock_id,
                'to_livestock_id' => $mutation->to_livestock_id,
                'from_coop_id' => $mutation->from_coop_id,
                'to_coop_id' => $mutation->to_coop_id,
                'status' => $mutationData['status'] ?? 'draft',
                'notes' => $mutation->notes,
                'created_by' => $mutation->created_by,
                'updated_by' => $mutation->updated_by,
            ];

            \Log::info('🔄 Creating SupplyMutation record', [
                'supplyMutationData' => $supplyMutationData
            ]);

            $supplyMutation = \App\Models\SupplyMutation::create($supplyMutationData);

            if (!$supplyMutation) {
                throw new \Exception('Failed to create SupplyMutation record');
            }

            \Log::info('✅ SupplyMutation created successfully', [
                'supplyMutationId' => $supplyMutation->id,
                'mutationId' => $mutation->id
            ]);

            // 2. Buat supply_mutation_items (detail items)
            $createdItems = [];
            foreach ($items as $index => $item) {
                try {
                    \Log::info('🔄 Processing item', [
                        'index' => $index,
                        'item_id' => $item['item_id'],
                        'quantity' => $item['quantity'],
                        'unit_id' => $item['unit_id']
                    ]);

                    // Cari supply stock yang sesuai untuk item ini
                    $supplyStock = \App\Models\SupplyStock::where('farm_id', $mutation->from_farm_id)
                        ->where('supply_id', $item['item_id'])
                        ->whereRaw('(quantity_in - quantity_used - quantity_mutated) > 0')
                        ->orderBy('date')
                        ->orderBy('created_at')
                        ->first();

                    if (!$supplyStock) {
                        throw new \Exception("Supply stock tidak ditemukan untuk supply ID: {$item['item_id']} di farm: {$mutation->from_farm_id}");
                    }

                    \Log::info('✅ Supply stock found', [
                        'supplyStockId' => $supplyStock->id,
                        'available_quantity' => $supplyStock->quantity_in - $supplyStock->quantity_used - $supplyStock->quantity_mutated
                    ]);

                    // Ambil supply dan data konversi
                    $supply = \App\Models\Supply::findOrFail($item['item_id']);
                    // PATCH: gunakan data['conversion_units']
                    $conversion = \App\Services\Recording\UnitConversionService::getConvertedQuantityAndUnitId('supply', $item['item_id'], $item['unit_id'], $item['quantity']);
                    $converted_quantity = $conversion['converted_quantity'];
                    $converted_unit_id = $conversion['converted_unit_id'];

                    $itemData = [
                        'id' => \Str::uuid(),
                        'supply_mutation_id' => $supplyMutation->id,
                        'supply_stock_id' => $supplyStock->id,
                        'supply_id' => $item['item_id'],
                        'quantity' => $item['quantity'],
                        'unit_id' => $item['unit_id'],
                        'converted_quantity' => $converted_quantity,
                        'converted_unit_id' => $converted_unit_id,
                        'created_by' => $mutation->created_by,
                    ];

                    \Log::info('🔄 Creating SupplyMutationItem', [
                        'itemData' => $itemData
                    ]);

                    $supplyMutationItem = \App\Models\SupplyMutationItem::create($itemData);

                    if (!$supplyMutationItem) {
                        throw new \Exception("Failed to create SupplyMutationItem for item index: {$index}");
                    }

                    $createdItems[] = $supplyMutationItem;

                    \Log::info('✅ SupplyMutationItem created successfully', [
                        'itemId' => $supplyMutationItem->id,
                        'supplyMutationId' => $supplyMutation->id,
                        'index' => $index
                    ]);
                } catch (\Exception $itemError) {
                    \Log::error('❌ Failed to create SupplyMutationItem', [
                        'index' => $index,
                        'item' => $item,
                        'error' => $itemError->getMessage()
                    ]);
                    throw $itemError;
                }
            }

            \Log::info('✅ All SupplyMutationItems created successfully', [
                'supplyMutationId' => $supplyMutation->id,
                'total_items_created' => count($createdItems)
            ]);

            // 3. Validasi final - pastikan semua data ter-create
            $finalValidation = \App\Models\SupplyMutation::with('supplyMutationDetails')
                ->find($supplyMutation->id);

            if (!$finalValidation) {
                throw new \Exception('Final validation failed: SupplyMutation not found after creation');
            }

            if ($finalValidation->supplyMutationDetails->count() !== count($items)) {
                throw new \Exception(
                    "Item count mismatch. Expected: " . count($items) .
                        ", Created: " . $finalValidation->supplyMutationDetails->count()
                );
            }

            \Log::info('✅ SupplyMutation creation completed successfully', [
                'supplyMutationId' => $supplyMutation->id,
                'mutationId' => $mutation->id,
                'items_created' => $finalValidation->supplyMutationDetails->count()
            ]);

            return $supplyMutation;
        } catch (\Exception $e) {
            \Log::error('❌ SupplyMutation creation failed', [
                'mutationId' => $mutation->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            // Re-throw exception untuk handling di MutationService
            throw new \Exception(
                "SupplyMutation creation failed: " . $e->getMessage() .
                    " | File: " . basename($e->getFile()) .
                    " | Line: " . $e->getLine()
            );
        }
    }

    /**
     * Get destination type from mutation data
     */
    private function getDestinationType(array $mutationData): string
    {
        if (isset($mutationData['destination_coop_id']) && $mutationData['destination_coop_id']) {
            return 'coop';
        }
        if (isset($mutationData['destination_livestock_id']) && $mutationData['destination_livestock_id']) {
            return 'livestock';
        }
        return 'unknown';
    }

    /**
     * Validate destination configuration
     */
    private function validateDestinationConfiguration(array $mutationData): void
    {
        $hasCoopDestination = isset($mutationData['destination_coop_id']) && $mutationData['destination_coop_id'];
        $hasLivestockDestination = isset($mutationData['destination_livestock_id']) && $mutationData['destination_livestock_id'];

        if ($hasCoopDestination && $hasLivestockDestination) {
            throw new Exception('Cannot specify both coop and livestock destination simultaneously');
        }

        if (!$hasCoopDestination && !$hasLivestockDestination) {
            throw new Exception('Must specify either coop or livestock destination');
        }

        // Validate destination exists
        if ($hasCoopDestination) {
            $coop = Coop::find($mutationData['destination_coop_id']);
            if (!$coop) {
                throw new Exception('Destination coop not found');
            }
        }

        if ($hasLivestockDestination) {
            $livestock = Livestock::find($mutationData['destination_livestock_id']);
            if (!$livestock) {
                throw new Exception('Destination livestock not found');
            }
        }
    }

    /**
     * Process mutation items
     */
    private function processMutationItems(SupplyMutation $mutation, array $items, array $mutationData): void
    {
        foreach ($items as $item) {
            $supplyId = $item['item_id'];
            $unitId = $item['unit_id'];
            $inputQty = $item['quantity'];

            // === REFACTOR: Gunakan UnitConversionService untuk konversi ===
            $conversion = UnitConversionService::getConvertedQuantityAndUnitId('supply', $supplyId, $unitId, $inputQty);
            $requiredQty = $conversion['converted_quantity'];
            $converted_unit_id = $conversion['converted_unit_id'];

            // Get available stocks using FIFO
            $stocks = SupplyStock::where('farm_id', $mutationData['source_farm_id'])
                ->where('supply_id', $supplyId)
                ->whereRaw('(quantity_in - quantity_used - quantity_mutated) > 0')
                ->orderBy('date')
                ->orderBy('created_at')
                ->lockForUpdate()
                ->get();

            $totalAvailable = $stocks->sum(fn($s) => $s->quantity_in - $s->quantity_used - $s->quantity_mutated);

            if ($totalAvailable < $requiredQty) {
                throw new Exception("Insufficient stock for supply: {$supply->name}. Required: {$requiredQty}, Available: {$totalAvailable}");
            }

            // Process FIFO stock allocation
            $remainingQty = $requiredQty;
            foreach ($stocks as $stock) {
                if ($remainingQty <= 0) break;

                $available = $stock->quantity_in - $stock->quantity_used - $stock->quantity_mutated;
                $takeQty = min($available, $remainingQty);

                // Update source stock
                $stock->quantity_mutated += $takeQty;
                $stock->save();

                // Create mutation item
                SupplyMutationItem::create([
                    'supply_mutation_id' => $mutation->id,
                    'supply_stock_id' => $stock->id,
                    'supply_id' => $supplyId,
                    'quantity' => $takeQty,
                    'unit_id' => $unitId,
                    'converted_quantity' => $requiredQty, // hasil konversi
                    'converted_unit_id' => $converted_unit_id,
                    'unit_price' => $stock->amount / $stock->quantity_in,
                    'total_value' => ($stock->amount / $stock->quantity_in) * $takeQty,
                    'created_by' => auth()->id(),
                ]);

                $remainingQty -= $takeQty;
            }

            // Update CurrentSupply for source
            $this->updateCurrentSupply($mutationData['source_farm_id'], $supplyId);
        }
    }

    /**
     * Handle destination based on type
     */
    private function handleDestination(SupplyMutation $mutation, array $mutationData): void
    {
        $destinationType = $this->getDestinationType($mutationData);

        if ($destinationType === 'coop') {
            $this->handleCoopDestination($mutation, $mutationData);
        } elseif ($destinationType === 'livestock') {
            $this->handleLivestockDestination($mutation, $mutationData);
        }
    }

    /**
     * Handle coop destination
     */
    private function handleCoopDestination(SupplyMutation $mutation, array $mutationData): void
    {
        $coopId = $mutationData['destination_coop_id'];
        $coop = Coop::findOrFail($coopId);
        $farmId = $coop->farm_id;

        Log::info('Processing coop destination', [
            'coop_id' => $coopId,
            'coop_name' => $coop->name,
            'farm_id' => $farmId
        ]);

        // Create destination stocks for each mutation item
        foreach ($mutation->supplyMutationDetails as $item) {
            $supplyStock = SupplyStock::find($item->supply_stock_id);

            // Create new stock at destination
            SupplyStock::create([
                'id' => Str::uuid(),
                'farm_id' => $farmId,
                'coop_id' => $coopId,
                'supply_id' => $item->supply_id,
                'supply_purchase_id' => $supplyStock->supply_purchase_id,
                'date' => $mutation->date,
                'source_type' => 'mutation',
                'source_id' => $mutation->id,
                'quantity_in' => $item->quantity,
                'quantity_used' => 0,
                'quantity_mutated' => 0,
                'amount' => $item->total_value,
                'created_by' => auth()->id(),
            ]);
        }

        // Update CurrentSupply for destination
        $this->updateCurrentSupplyForCoop($coopId, $mutation->supplyMutationDetails);

        Log::info('Coop destination handled successfully', [
            'coop_id' => $coopId,
            'items_processed' => $mutation->supplyMutationDetails->count()
        ]);
    }

    /**
     * Handle livestock destination
     */
    private function handleLivestockDestination(SupplyMutation $mutation, array $mutationData): void
    {
        $livestockId = $mutationData['destination_livestock_id'];
        $livestock = Livestock::findOrFail($livestockId);
        $farmId = $livestock->farm_id;
        $coopId = $livestock->coop_id;

        Log::info('Processing livestock destination', [
            'livestock_id' => $livestockId,
            'livestock_name' => $livestock->name,
            'farm_id' => $farmId,
            'coop_id' => $coopId
        ]);

        // Create destination stocks for each mutation item
        foreach ($mutation->supplyMutationDetails as $item) {
            $supplyStock = SupplyStock::find($item->supply_stock_id);

            // Create new stock at destination
            SupplyStock::create([
                'id' => Str::uuid(),
                'farm_id' => $farmId,
                'coop_id' => $coopId,
                'livestock_id' => $livestockId,
                'supply_id' => $item->supply_id,
                'supply_purchase_id' => $supplyStock->supply_purchase_id,
                'date' => $mutation->date,
                'source_type' => 'mutation',
                'source_id' => $mutation->id,
                'quantity_in' => $item->quantity,
                'quantity_used' => 0,
                'quantity_mutated' => 0,
                'amount' => $item->total_value,
                'created_by' => auth()->id(),
            ]);
        }

        // Update CurrentSupply for destination
        $this->updateCurrentSupplyForLivestock($livestockId, $mutation->supplyMutationDetails);

        Log::info('Livestock destination handled successfully', [
            'livestock_id' => $livestockId,
            'items_processed' => $mutation->supplyMutationDetails->count()
        ]);
    }

    /**
     * Update CurrentSupply for farm
     */
    private function updateCurrentSupply(string $farmId, string $supplyId): void
    {
        $totalQuantity = SupplyStock::where('farm_id', $farmId)
            ->where('supply_id', $supplyId)
            ->selectRaw('COALESCE(SUM(quantity_in - quantity_used - quantity_mutated), 0) as total')
            ->value('total');

        CurrentSupply::updateOrCreate(
            ['farm_id' => $farmId, 'item_id' => $supplyId, 'type' => 'supply'],
            [
                'quantity' => $totalQuantity,
                'updated_by' => auth()->id(),
            ]
        );
    }

    /**
     * Update CurrentSupply for coop
     */
    private function updateCurrentSupplyForCoop(string $coopId, $mutationItems): void
    {
        foreach ($mutationItems as $item) {
            $totalQuantity = SupplyStock::where('coop_id', $coopId)
                ->where('supply_id', $item->supply_id)
                ->selectRaw('COALESCE(SUM(quantity_in - quantity_used - quantity_mutated), 0) as total')
                ->value('total');

            CurrentSupply::updateOrCreate(
                ['coop_id' => $coopId, 'item_id' => $item->supply_id, 'type' => 'supply'],
                [
                    'quantity' => $totalQuantity,
                    'updated_by' => auth()->id(),
                ]
            );
        }
    }

    /**
     * Update CurrentSupply for livestock
     */
    private function updateCurrentSupplyForLivestock(string $livestockId, $mutationItems): void
    {
        foreach ($mutationItems as $item) {
            $totalQuantity = SupplyStock::where('livestock_id', $livestockId)
                ->where('supply_id', $item->supply_id)
                ->selectRaw('COALESCE(SUM(quantity_in - quantity_used - quantity_mutated), 0) as total')
                ->value('total');

            CurrentSupply::updateOrCreate(
                ['livestock_id' => $livestockId, 'item_id' => $item->supply_id, 'type' => 'supply'],
                [
                    'quantity' => $totalQuantity,
                    'updated_by' => auth()->id(),
                ]
            );
        }
    }

    /**
     * Approve supply mutation
     */
    public function approveMutation(SupplyMutation $mutation, ?string $notes = null): void
    {
        if (!$mutation->canBeApproved()) {
            throw new Exception('Mutation cannot be approved in current status');
        }

        $mutation->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        Log::info('Supply mutation approved', [
            'mutation_id' => $mutation->id,
            'approved_by' => auth()->id(),
            'notes' => $notes
        ]);
    }

    /**
     * Reject supply mutation
     */
    public function rejectMutation(SupplyMutation $mutation, string $reason): void
    {
        if (!$mutation->canBeRejected()) {
            throw new Exception('Mutation cannot be rejected in current status');
        }

        $mutation->update([
            'status' => 'rejected',
            'rejected_by' => auth()->id(),
            'rejected_at' => now(),
            'rejected_reason' => $reason,
        ]);

        Log::info('Supply mutation rejected', [
            'mutation_id' => $mutation->id,
            'rejected_by' => auth()->id(),
            'reason' => $reason
        ]);
    }

    /**
     * Verify supply mutation
     */
    public function verifyMutation(SupplyMutation $mutation, ?string $notes = null): void
    {
        if (!$mutation->canBeVerified()) {
            throw new Exception('Mutation cannot be verified in current status');
        }

        $config = config('supply_mutation.workflow.verification');

        // Check if verification notes are required
        if ($config['verification_notes_required'] && empty($notes)) {
            throw new Exception('Catatan verifikasi wajib diisi');
        }

        $updateData = [
            'verified_by' => auth()->id(),
            'verified_at' => now(),
        ];

        // Only update status to 'verified' if not already completed
        if ($mutation->status !== 'completed') {
            $updateData['status'] = 'verified';
        }

        $mutation->update($updateData);

        Log::info('Supply mutation verified', [
            'mutation_id' => $mutation->id,
            'verified_by' => auth()->id(),
            'notes' => $notes,
            'previous_status' => $mutation->getOriginal('status'),
            'new_status' => $mutation->status
        ]);
    }

    /**
     * Complete verified mutation (bypass approval)
     */
    public function completeVerifiedMutation(SupplyMutation $mutation, ?string $notes = null): void
    {
        // Check if mutation can bypass approval (verification not required)
        if (!$mutation->canBypassApproval()) {
            throw new Exception('Mutation cannot bypass approval');
        }

        $mutation->update([
            'status' => 'completed',
            'updated_by' => auth()->id(),
            'updated_at' => now(),
        ]);

        Log::info('Supply mutation completed (bypass approval)', [
            'mutation_id' => $mutation->id,
            'completed_by' => auth()->id(),
            'notes' => $notes,
            'verification_status' => $mutation->isVerified() ? 'verified' : 'not_verified'
        ]);
    }
}

<?php

namespace App\Services;

use App\Models\SupplyStock;
use App\Models\CurrentSupply;
use App\Models\Supply;
use App\Models\Farm;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SupplyStockManagementService
{
    protected $metadataService;

    public function __construct(SupplyMetadataService $metadataService)
    {
        $this->metadataService = $metadataService;
    }

    /**
     * Create supply stock entry for purchase
     */
    public function createPurchaseStock(array $data): array
    {
        DB::beginTransaction();

        try {
            // Validate required data
            $this->validatePurchaseData($data);

            // Build metadata
            $metadata = $this->metadataService->buildPurchaseMetadata($data);

            // Create stock entry
            $stockEntry = SupplyStock::create([
                'farm_id' => $data['farm_id'],
                'coop_id' => $data['coop_id'] ?? null,
                'supply_id' => $data['supply_id'],
                'supply_purchase_id' => $data['purchase_id'],
                'date' => $data['purchase_date'] ?? now(),
                'source_type' => 'purchase',
                'source_id' => $data['purchase_id'],
                'quantity_in' => $data['quantity'],
                'quantity_used' => 0,
                'quantity_mutated' => 0,
                'quantity_reserved' => 0,
                'quantity_available' => $data['quantity'],
                'metadata' => $metadata,
                'created_by' => \Illuminate\Support\Facades\Auth::id()
            ]);

            // Update CurrentSupply
            $this->updateCurrentSupply($data['farm_id'], $data['supply_id'], $data['unit_id']);

            DB::commit();

            Log::info('Supply purchase stock created', [
                'stock_id' => $stockEntry->id,
                'farm_id' => $data['farm_id'],
                'supply_id' => $data['supply_id'],
                'quantity' => $data['quantity']
            ]);

            return [
                'success' => true,
                'stock_entry' => $stockEntry,
                'message' => 'Supply purchase stock created successfully'
            ];
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Supply purchase stock creation failed: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Create supply stock entry for inbound mutation
     */
    public function createInboundMutationStock(array $data): array
    {
        DB::beginTransaction();

        try {
            // Validate required data
            $this->validateInboundMutationData($data);

            // supply_purchase_id is required for integrity, must be provided (from source stock)
            if (empty($data['supply_purchase_id'])) {
                throw new \Exception('supply_purchase_id is required for inbound mutation stock. Please provide the source stock\'s supply_purchase_id.');
            }

            // Build metadata
            $metadata = $this->metadataService->buildInboundMutationMetadata($data);

            // Create stock entry
            $stockEntry = SupplyStock::create([
                'farm_id' => $data['to_farm_id'],
                'coop_id' => $data['to_coop_id'] ?? null,
                'supply_id' => $data['supply_id'],
                'supply_purchase_id' => $data['supply_purchase_id'],
                'date' => $data['mutation_date'] ?? now(),
                'source_type' => 'mutation',
                'source_id' => $data['mutation_id'],
                'quantity_in' => $data['quantity'],
                'quantity_used' => 0,
                'quantity_mutated' => 0,
                'quantity_reserved' => 0,
                'quantity_available' => $data['quantity'],
                'metadata' => $metadata,
                'created_by' => \Illuminate\Support\Facades\Auth::id()
            ]);

            // Update CurrentSupply
            $this->updateCurrentSupply($data['to_farm_id'], $data['supply_id'], $data['unit_id']);

            DB::commit();

            Log::info('Supply inbound mutation stock created', [
                'stock_id' => $stockEntry->id,
                'from_farm_id' => $data['from_farm_id'],
                'to_farm_id' => $data['to_farm_id'],
                'supply_id' => $data['supply_id'],
                'quantity' => $data['quantity']
            ]);

            return [
                'success' => true,
                'stock_entry' => $stockEntry,
                'message' => 'Supply inbound mutation stock created successfully'
            ];
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Supply inbound mutation stock creation failed: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Update stock quantity (usage, mutation, reservation)
     */
    public function updateStockQuantity(string $stockId, string $action, float $quantity, array $additionalData = []): array
    {
        DB::beginTransaction();

        try {
            $stock = SupplyStock::findOrFail($stockId);

            // Validate quantity
            if ($quantity <= 0) {
                throw new \Exception('Quantity must be greater than 0');
            }

            // Check available quantity
            if ($stock->quantity_available < $quantity) {
                throw new \Exception("Insufficient available quantity. Available: {$stock->quantity_available}, Requested: {$quantity}");
            }

            // Update quantity based on action
            switch ($action) {
                case 'usage':
                    $stock->quantity_used += $quantity;
                    break;
                case 'mutation':
                    $stock->quantity_mutated += $quantity;
                    break;
                case 'reservation':
                    $stock->quantity_reserved += $quantity;
                    break;
                default:
                    throw new \Exception("Invalid action: {$action}");
            }

            // Update available quantity
            $stock->updateAvailableQuantity();

            // Update metadata with history
            $metadata = $stock->metadata;
            $metadata = $this->metadataService->addHistoryEntry($metadata, "quantity_{$action}", [
                'quantity' => $quantity,
                'usage_id' => $additionalData['usage_id'] ?? null,
                'mutation_id' => $additionalData['mutation_id'] ?? null,
                'reservation_id' => $additionalData['reservation_id'] ?? null,
                'notes' => $additionalData['notes'] ?? null
            ]);

            $stock->metadata = $metadata;
            $stock->save();

            // Update CurrentSupply
            $this->updateCurrentSupply($stock->farm_id, $stock->supply_id, $stock->unit_id);

            DB::commit();

            Log::info("Supply stock quantity updated", [
                'stock_id' => $stockId,
                'action' => $action,
                'quantity' => $quantity,
                'new_available' => $stock->quantity_available
            ]);

            return [
                'success' => true,
                'stock' => $stock,
                'message' => "Stock quantity updated successfully for action: {$action}"
            ];
        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Supply stock quantity update failed: " . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get stock summary for supply
     */
    public function getStockSummary(string $farmId, string $supplyId): array
    {
        try {
            $stocks = SupplyStock::where('farm_id', $farmId)
                ->where('supply_id', $supplyId)
                ->whereNull('deleted_at')
                ->get();

            $summary = [
                'total_records' => $stocks->count(),
                'total_quantity_in' => $stocks->sum('quantity_in'),
                'total_quantity_used' => $stocks->sum('quantity_used'),
                'total_quantity_mutated' => $stocks->sum('quantity_mutated'),
                'total_quantity_reserved' => $stocks->sum('quantity_reserved'),
                'total_quantity_available' => $stocks->sum('quantity_available'),
                'breakdown' => [
                    'purchase_records' => $stocks->where('source_type', 'purchase')->count(),
                    'mutation_records' => $stocks->where('source_type', 'mutation')->count(),
                ],
                'metadata_summaries' => []
            ];

            // Get metadata summaries for each stock
            foreach ($stocks as $stock) {
                $summary['metadata_summaries'][] = [
                    'stock_id' => $stock->id,
                    'source_type' => $stock->source_type,
                    'batch_code' => $stock->metadata['batch_code'] ?? 'N/A',
                    'quantity_available' => $stock->quantity_available,
                    'created_date' => $stock->date,
                    'last_activity' => end($stock->metadata['history'])['date'] ?? 'N/A'
                ];
            }

            return [
                'success' => true,
                'summary' => $summary
            ];
        } catch (\Exception $e) {
            Log::error("Supply stock summary failed: " . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get FIFO stock for usage
     */
    public function getFifoStock(string $farmId, string $supplyId, float $requiredQuantity): array
    {
        try {
            $availableStocks = SupplyStock::where('farm_id', $farmId)
                ->where('supply_id', $supplyId)
                ->whereNull('deleted_at')
                ->where('quantity_available', '>', 0)
                ->orderBy('date', 'asc') // FIFO order
                ->orderBy('id', 'asc')
                ->get();

            if ($availableStocks->isEmpty()) {
                return [
                    'success' => false,
                    'error' => 'No available stock found'
                ];
            }

            $allocatedStocks = [];
            $remainingQuantity = $requiredQuantity;

            foreach ($availableStocks as $stock) {
                if ($remainingQuantity <= 0) break;

                $allocatedQuantity = min($stock->quantity_available, $remainingQuantity);

                $allocatedStocks[] = [
                    'stock_id' => $stock->id,
                    'batch_code' => $stock->metadata['batch_code'] ?? 'N/A',
                    'available_quantity' => $stock->quantity_available,
                    'allocated_quantity' => $allocatedQuantity,
                    'source_type' => $stock->source_type,
                    'date' => $stock->date
                ];

                $remainingQuantity -= $allocatedQuantity;
            }

            if ($remainingQuantity > 0) {
                return [
                    'success' => false,
                    'error' => "Insufficient stock. Required: {$requiredQuantity}, Available: " . ($requiredQuantity - $remainingQuantity)
                ];
            }

            return [
                'success' => true,
                'allocated_stocks' => $allocatedStocks,
                'total_allocated' => $requiredQuantity - $remainingQuantity
            ];
        } catch (\Exception $e) {
            Log::error("FIFO stock allocation failed: " . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Update CurrentSupply based on stock totals
     */
    private function updateCurrentSupply(string $farmId, string $supplyId, string $unitId): void
    {
        $totalAvailable = SupplyStock::where('farm_id', $farmId)
            ->where('supply_id', $supplyId)
            ->whereNull('deleted_at')
            ->sum('quantity_available');

        CurrentSupply::updateOrCreate(
            [
                'farm_id' => $farmId,
                'item_id' => $supplyId,
                'type' => 'supply'
            ],
            [
                'unit_id' => $unitId,
                'quantity' => $totalAvailable,
                'updated_at' => now()
            ]
        );
    }

    /**
     * Validate purchase data
     */
    private function validatePurchaseData(array $data): void
    {
        $required = ['farm_id', 'supply_id', 'quantity', 'purchase_id'];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new \Exception("Required field missing: {$field}");
            }
        }

        if ($data['quantity'] <= 0) {
            throw new \Exception('Quantity must be greater than 0');
        }
    }

    /**
     * Validate inbound mutation data
     */
    private function validateInboundMutationData(array $data): void
    {
        $required = ['from_farm_id', 'to_farm_id', 'supply_id', 'quantity', 'mutation_id'];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new \Exception("Required field missing: {$field}");
            }
        }

        if ($data['quantity'] <= 0) {
            throw new \Exception('Quantity must be greater than 0');
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Services\Recording;

use App\Models\RecordingSale;
use App\Models\RecordingSaleItem;
use App\Models\LivestockBatch;
use App\Services\Recording\Contracts\RecordingSaleServiceInterface;
use App\Services\Recording\DTOs\ServiceResult;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Exception;
use Illuminate\Support\Carbon;
use App\Models\Livestock;
use App\Models\LivestockSales;
use App\Models\LivestockSalesItem;
use Illuminate\Support\Facades\Auth;

class RecordingSaleService implements RecordingSaleServiceInterface
{
    // Feature flag for using new header-item pattern
    private bool $useHeaderItemPattern = true;

    /**
     * Create a new recording sale using header-item pattern with virtual calculation support
     */
    public function createWithBatches(array $saleData, array $batchAllocations): ServiceResult
    {
        $header = null;
        $createdItems = [];
        $updatedBatches = [];

        try {
            return DB::transaction(function () use ($saleData, $batchAllocations, &$header, &$createdItems, &$updatedBatches) {
                // Check if virtual calculation is enabled
                $salesConfig = \App\Config\CompanyConfig::getSalesConfig();
                $quantityCalculation = $salesConfig['quantity_calculation'] ?? [];
                $isVirtualMode = ($quantityCalculation['mode'] ?? 'real') === 'virtual';
                $isTwoStage = ($salesConfig['recording_method']['type'] ?? 'direct') === 'two_stage';

                // Validate input data
                $validationResult = $this->validateBatchAllocationData($saleData, $batchAllocations);
                if (!$validationResult['valid']) {
                    throw new Exception($validationResult['message']);
                }

                // Calculate totals from batch allocations
                $totalQuantity = array_sum(array_column($batchAllocations, 'quantity'));
                $totalWeight = array_sum(array_column($batchAllocations, 'weight'));
                $totalAmount = array_sum(array_column($batchAllocations, 'amount'));

                // Validate totals match sale data
                if ($totalQuantity != $saleData['quantity']) {
                    throw new Exception("Total quantity mismatch: expected {$saleData['quantity']}, got {$totalQuantity}");
                }

                if (abs($totalWeight - $saleData['weight']) > 0.01) {
                    throw new Exception("Total weight mismatch: expected {$saleData['weight']}, got {$totalWeight}");
                }

                // Determine status based on virtual mode and two-stage configuration
                $defaultStatus = 'draft';
                if ($isVirtualMode) {
                    $defaultStatus = 'draft'; // Virtual mode always starts as draft
                } elseif ($isTwoStage) {
                    $defaultStatus = 'draft'; // Two-stage starts as draft
                } else {
                    $defaultStatus = 'finalized'; // Direct mode can be finalized immediately
                }

                // Create header (RecordingSale with is_header = true)
                $header = RecordingSale::create([
                    'company_id' => $saleData['company_id'],
                    'livestock_id' => $saleData['livestock_id'],
                    'recording_id' => $saleData['recording_id'],
                    'date' => $saleData['date'],
                    'livestock_batch_id' => null, // Header record has no specific batch
                    'quantity' => $totalQuantity, // Set to total quantity
                    'weight' => $totalWeight, // Set to total weight
                    'price' => $saleData['price'] ?? 0, // Average price for reference
                    'total_quantity' => $totalQuantity,
                    'total_weight' => $totalWeight,
                    'total_amount' => $totalAmount,
                    'is_header' => true, // Mark as header record
                    'batch_count' => count($batchAllocations),
                    'status' => $saleData['status'] ?? $defaultStatus,
                    'metadata' => [
                        'source' => 'RecordingSaleService',
                        'allocation_method' => 'fifo',
                        'virtual_mode' => $isVirtualMode,
                        'two_stage_mode' => $isTwoStage,
                        'calculation_mode' => $quantityCalculation['mode'] ?? 'real',
                        'created_at' => now()->toIso8601String(),
                        'validation' => [
                            'total_quantity' => $totalQuantity,
                            'total_weight' => $totalWeight,
                            'total_amount' => $totalAmount,
                            'batch_count' => count($batchAllocations),
                        ],
                    ],
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);

                // Validate header was created
                if (!$header || !$header->id) {
                    throw new Exception('Failed to create sale header');
                }

                // Create items and update batch quantities (only if not virtual mode)
                foreach ($batchAllocations as $index => $allocation) {
                    // Validate allocation data
                    if (empty($allocation['batch_id']) || $allocation['quantity'] <= 0) {
                        throw new Exception("Invalid allocation data at index {$index}");
                    }

                    // Create sale item
                    $item = RecordingSaleItem::create([
                        'recording_sale_id' => $header->id,
                        'livestock_id' => $saleData['livestock_id'],
                        'livestock_batch_id' => $allocation['batch_id'],
                        'quantity' => $allocation['quantity'],
                        'weight' => $allocation['weight'],
                        'price_per_unit' => $allocation['price_per_unit'],
                        'amount' => $allocation['amount'],
                        'metadata' => [
                            'allocation_order' => $allocation['order'] ?? $index,
                            'weight_per_unit' => $allocation['quantity'] > 0 ? $allocation['weight'] / $allocation['quantity'] : 0,
                            'virtual_mode' => $isVirtualMode,
                            'created_at' => now()->toIso8601String(),
                        ],
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ]);

                    // Validate item was created
                    if (!$item || !$item->id) {
                        throw new Exception("Failed to create sale item for batch {$allocation['batch_id']}");
                    }

                    $createdItems[] = $item;

                    // Update batch quantities only if not in virtual mode
                    if (!$isVirtualMode) {
                        $batch = LivestockBatch::find($allocation['batch_id']);
                        if (!$batch) {
                            throw new Exception("Batch not found: {$allocation['batch_id']}");
                        }

                        // Validate batch has enough quantity
                        if ($batch->quantity_available < $allocation['quantity']) {
                            throw new Exception("Insufficient batch quantity: batch {$batch->id} has {$batch->quantity_available}, need {$allocation['quantity']}");
                        }

                        $batch->quantity_available -= $allocation['quantity'];
                        $batch->quantity_sales += $allocation['quantity'];
                        $batch->save();

                        $updatedBatches[] = $batch;
                    } else {
                        // Store virtual data in batch data column
                        $this->storeVirtualSaleData($allocation['batch_id'], $saleData['date'], $allocation);
                    }
                }

                // Validate all items were created
                if (count($createdItems) !== count($batchAllocations)) {
                    throw new Exception("Item creation mismatch: expected " . count($batchAllocations) . ", created " . count($createdItems));
                }

                // Final validation: check header totals match items
                $actualTotalQuantity = array_sum(array_column($createdItems, 'quantity'));
                $actualTotalWeight = array_sum(array_column($createdItems, 'weight'));

                if ($actualTotalQuantity !== $totalQuantity) {
                    throw new Exception("Final quantity validation failed: header shows {$totalQuantity}, items total {$actualTotalQuantity}");
                }

                if (abs($actualTotalWeight - $totalWeight) > 0.01) {
                    throw new Exception("Final weight validation failed: header shows {$totalWeight}, items total {$actualTotalWeight}");
                }

                // Final update header to ensure data consistency
                $header->update([
                    'quantity' => $actualTotalQuantity,
                    'weight' => $actualTotalWeight,
                    'total_quantity' => $actualTotalQuantity,
                    'total_weight' => $actualTotalWeight,
                    'total_amount' => array_sum(array_column($createdItems, 'amount')),
                    'batch_count' => count($createdItems),
                    'metadata' => array_merge($header->metadata ?? [], [
                        'final_validation' => [
                            'completed_at' => now()->toIso8601String(),
                            'items_count' => count($createdItems),
                            'actual_total_quantity' => $actualTotalQuantity,
                            'actual_total_weight' => $actualTotalWeight,
                            'validation_passed' => true,
                            'virtual_mode' => $isVirtualMode,
                            'real_stock_updated' => !$isVirtualMode,
                        ],
                    ]),
                    'updated_by' => Auth::id(),
                ]);

                Log::info('RecordingSaleService::createWithBatches completed successfully', [
                    'header_id' => $header->id,
                    'items_count' => count($createdItems),
                    'batches_updated' => count($updatedBatches),
                    'total_quantity' => $actualTotalQuantity,
                    'total_weight' => $actualTotalWeight,
                    'total_amount' => array_sum(array_column($createdItems, 'amount')),
                    'batch_count' => count($createdItems),
                    'virtual_mode' => $isVirtualMode,
                    'two_stage_mode' => $isTwoStage,
                ]);

                return ServiceResult::success('Sale with batches created successfully', $header->toArray());
            });
        } catch (Exception $e) {
            // Rollback if transaction fails
            if ($header && $header->id) {
                $this->rollbackSaleCreation($header->id, $createdItems, $updatedBatches);
            }

            Log::error('RecordingSaleService::createWithBatches failed', [
                'error' => $e->getMessage(),
                'saleData' => $saleData,
                'batchAllocations' => $batchAllocations,
                'trace' => $e->getTraceAsString(),
            ]);
            return ServiceResult::error('Failed to create sale with batches: ' . $e->getMessage());
        }
    }

    /**
     * Store virtual sale data in batch data column
     */
    private function storeVirtualSaleData(string $batchId, string $date, array $allocation): void
    {
        try {
            $batch = LivestockBatch::find($batchId);
            if (!$batch) {
                Log::error('Batch not found for virtual data storage', ['batch_id' => $batchId]);
                return;
            }

            $existingData = $batch->data ?? [];

            // Initialize virtual data structure if not exists
            if (!isset($existingData['virtual_sales'])) {
                $existingData['virtual_sales'] = [];
            }

            // Store virtual sales data
            $existingData['virtual_sales'][$date] = [
                'quantity' => $allocation['quantity'],
                'weight' => $allocation['weight'],
                'date' => $date,
                'status' => 'draft',
                'metadata' => [
                    'calculation_method' => 'fifo_allocation',
                    'calculation_timestamp' => now()->toIso8601String(),
                    'price_per_unit' => $allocation['price_per_unit'] ?? 0,
                    'amount' => $allocation['amount'] ?? 0,
                    'allocation_order' => $allocation['order'] ?? 0,
                ]
            ];

            // Update batch data
            $batch->update([
                'data' => $existingData,
                'updated_at' => now()
            ]);

            Log::debug('Virtual sale data stored in batch', [
                'batch_id' => $batch->id,
                'batch_name' => $batch->name,
                'date' => $date,
                'virtual_quantity' => $allocation['quantity'],
                'virtual_weight' => $allocation['weight']
            ]);
        } catch (Exception $e) {
            Log::error('Failed to store virtual sale data', [
                'batch_id' => $batchId,
                'date' => $date,
                'allocation' => $allocation,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Rollback sale creation if error occurs
     */
    private function rollbackSaleCreation(string $headerId, array $createdItems, array $updatedBatches): void
    {
        try {
            Log::warning('RecordingSaleService::rollbackSaleCreation - Starting rollback', [
                'header_id' => $headerId,
                'items_count' => count($createdItems),
                'batches_count' => count($updatedBatches),
            ]);

            // Rollback batch updates
            foreach ($updatedBatches as $batch) {
                try {
                    $batch->refresh(); // Get latest data
                    $batch->quantity_available += $batch->quantity_sales;
                    $batch->quantity_sales = 0;
                    $batch->save();

                    Log::info('RecordingSaleService::rollbackSaleCreation - Batch rolled back', [
                        'batch_id' => $batch->id,
                        'quantity_available' => $batch->quantity_available,
                    ]);
                } catch (Exception $e) {
                    Log::error('RecordingSaleService::rollbackSaleCreation - Failed to rollback batch', [
                        'batch_id' => $batch->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Delete created items
            foreach ($createdItems as $item) {
                try {
                    $item->delete();
                    Log::info('RecordingSaleService::rollbackSaleCreation - Item deleted', [
                        'item_id' => $item->id,
                    ]);
                } catch (Exception $e) {
                    Log::error('RecordingSaleService::rollbackSaleCreation - Failed to delete item', [
                        'item_id' => $item->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Delete header
            try {
                $header = RecordingSale::find($headerId);
                if ($header) {
                    $header->delete();
                    Log::info('RecordingSaleService::rollbackSaleCreation - Header deleted', [
                        'header_id' => $headerId,
                    ]);
                }
            } catch (Exception $e) {
                Log::error('RecordingSaleService::rollbackSaleCreation - Failed to delete header', [
                    'header_id' => $headerId,
                    'error' => $e->getMessage(),
                ]);
            }

            Log::info('RecordingSaleService::rollbackSaleCreation - Rollback completed');
        } catch (Exception $e) {
            Log::error('RecordingSaleService::rollbackSaleCreation - Rollback failed', [
                'error' => $e->getMessage(),
                'header_id' => $headerId,
            ]);
        }
    }

    /**
     * Legacy create method - routes to appropriate implementation
     */
    public function create(array $data): ServiceResult
    {
        if ($this->useHeaderItemPattern) {
            return $this->createWithHeaderItem($data);
        } else {
            return $this->createLegacy($data);
        }
    }

    /**
     * Create using new header-item pattern
     */
    private function createWithHeaderItem(array $data): ServiceResult
    {
        try {
            // Auto-allocate to batches using FIFO
            $livestockId = $data['livestock_id'];
            $quantity = $data['quantity'];
            $weight = $data['weight'] ?? 0;
            $pricePerUnit = $data['price'] ?? 0;

            $batchAllocations = $this->allocateToBatchesFIFO($livestockId, $quantity, $weight, $pricePerUnit);

            if (empty($batchAllocations)) {
                return ServiceResult::error('No available batches for allocation');
            }

            return $this->createWithBatches($data, $batchAllocations);
        } catch (Exception $e) {
            Log::error('RecordingSaleService::createWithHeaderItem failed', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            return ServiceResult::error('Failed to create sale: ' . $e->getMessage());
        }
    }

    /**
     * Legacy create method for backward compatibility
     */
    private function createLegacy(array $data): ServiceResult
    {
        try {
            // Create single record with is_header = false
            $data['is_header'] = false;
            $data['batch_count'] = 1;

            $sale = RecordingSale::create($data);
            return ServiceResult::success('Legacy sale created successfully', $sale->toArray());
        } catch (Exception $e) {
            Log::error('RecordingSaleService::createLegacy failed', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            return ServiceResult::error('Failed to create legacy sale: ' . $e->getMessage());
        }
    }

    /**
     * Update method - routes to appropriate implementation
     */
    public function update(string $id, array $data): ServiceResult
    {
        try {
            $sale = RecordingSale::findOrFail($id);

            if ($sale->isHeader()) {
                return $this->updateHeaderItem($sale, $data);
            } else {
                return $this->updateLegacy($sale, $data);
            }
        } catch (Exception $e) {
            Log::error('RecordingSaleService::update failed', [
                'error' => $e->getMessage(),
                'id' => $id,
                'data' => $data,
            ]);
            return ServiceResult::error('Failed to update sale: ' . $e->getMessage());
        }
    }

    /**
     * Update using header-item pattern
     */
    private function updateHeaderItem(RecordingSale $sale, array $data): ServiceResult
    {
        try {
            return DB::transaction(function () use ($sale, $data) {
                // Validate sale exists and is header
                if (!$sale->isHeader()) {
                    throw new Exception('Sale is not a header record');
                }

                // Store original data for rollback
                $originalData = [
                    'status' => $sale->status,
                    'metadata' => $sale->metadata,
                    'total_quantity' => $sale->total_quantity,
                    'total_weight' => $sale->total_weight,
                    'total_amount' => $sale->total_amount,
                ];

                // Update header with validation
                $updateData = [
                    'status' => $data['status'] ?? $sale->status,
                    'metadata' => array_merge($sale->metadata ?? [], [
                        'updated_at' => now()->toIso8601String(),
                        'updated_by_service' => 'RecordingSaleService',
                        'previous_data' => $originalData,
                    ]),
                    'updated_by' => Auth::id(),
                ];

                // If quantity/weight changed, validate and recalculate
                if (isset($data['quantity']) || isset($data['weight'])) {
                    $newQuantity = $data['quantity'] ?? $sale->total_quantity;
                    $newWeight = $data['weight'] ?? $sale->total_weight;

                    // Validate new values
                    if ($newQuantity <= 0) {
                        throw new Exception('Invalid quantity: must be greater than 0');
                    }

                    if ($newWeight < 0) {
                        throw new Exception('Invalid weight: must be non-negative');
                    }

                    // Update totals
                    $updateData['total_quantity'] = $newQuantity;
                    $updateData['total_weight'] = $newWeight;
                    $updateData['total_amount'] = $newQuantity * ($data['price'] ?? $sale->price ?? 0);
                }

                // Update the sale
                $sale->update($updateData);

                // Validate update was successful
                if (!$sale->wasChanged()) {
                    Log::warning('RecordingSaleService::updateHeaderItem - No changes detected', [
                        'sale_id' => $sale->id,
                        'data' => $data,
                    ]);
                }

                // Recalculate totals from items if needed
                $items = $sale->items;
                if ($items->isNotEmpty()) {
                    $calculatedTotalQuantity = $items->sum('quantity');
                    $calculatedTotalWeight = $items->sum('weight');
                    $calculatedTotalAmount = $items->sum('amount');

                    // Validate calculated totals match header
                    if ($calculatedTotalQuantity != $sale->total_quantity) {
                        Log::warning('RecordingSaleService::updateHeaderItem - Quantity mismatch detected', [
                            'sale_id' => $sale->id,
                            'header_quantity' => $sale->total_quantity,
                            'items_quantity' => $calculatedTotalQuantity,
                        ]);
                    }

                    if (abs($calculatedTotalWeight - $sale->total_weight) > 0.01) {
                        Log::warning('RecordingSaleService::updateHeaderItem - Weight mismatch detected', [
                            'sale_id' => $sale->id,
                            'header_weight' => $sale->total_weight,
                            'items_weight' => $calculatedTotalWeight,
                        ]);
                    }
                }

                Log::info('RecordingSaleService::updateHeaderItem completed successfully', [
                    'sale_id' => $sale->id,
                    'changes' => $sale->getChanges(),
                    'items_count' => $items->count(),
                ]);

                return ServiceResult::success('Header item sale updated successfully', $sale->toArray());
            });
        } catch (Exception $e) {
            Log::error('RecordingSaleService::updateHeaderItem failed', [
                'error' => $e->getMessage(),
                'sale_id' => $sale->id,
                'data' => $data,
                'trace' => $e->getTraceAsString(),
            ]);
            return ServiceResult::error('Failed to update header-item sale: ' . $e->getMessage());
        }
    }

    /**
     * Legacy update method
     */
    private function updateLegacy(RecordingSale $sale, array $data): ServiceResult
    {
        try {
            $sale->update($data);
            return ServiceResult::success('Legacy sale updated successfully', $sale->toArray());
        } catch (Exception $e) {
            Log::error('RecordingSaleService::updateLegacy failed', [
                'error' => $e->getMessage(),
                'sale_id' => $sale->id,
                'data' => $data,
            ]);
            return ServiceResult::error('Failed to update legacy sale: ' . $e->getMessage());
        }
    }

    /**
     * List sales by livestock and date - routes to appropriate implementation
     */
    public function listByLivestockAndDate(string $livestockId, string $date, array $filters = []): ServiceResult
    {
        try {
            $query = RecordingSale::with(['items.batch', 'livestockBatch'])
                ->where('livestock_id', $livestockId)
                ->where('date', $date);

            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            $sales = $query->get();

            $result = $sales->map(function ($sale) {
                return [
                    'id' => $sale->id,
                    'livestock_id' => $sale->livestock_id,
                    'recording_id' => $sale->recording_id,
                    'date' => $sale->date->format('Y-m-d'),
                    'total_quantity' => $sale->getTotalQuantity(),
                    'total_weight' => $sale->getTotalWeight(),
                    'total_amount' => $sale->getTotalAmount(),
                    'status' => $sale->status,
                    'is_header' => $sale->isHeader(),
                    'batch_count' => $sale->batch_count,
                    'batch_breakdown' => $sale->getBatchBreakdown(),
                    'metadata' => $sale->metadata,
                ];
            })->toArray();

            return ServiceResult::success('Sales data retrieved successfully', $result);
        } catch (Exception $e) {
            Log::error('RecordingSaleService::listByLivestockAndDate failed', [
                'error' => $e->getMessage(),
                'livestock_id' => $livestockId,
                'date' => $date,
            ]);
            return ServiceResult::error('Failed to list sales: ' . $e->getMessage());
        }
    }

    /**
     * Allocate sale quantity to batches using FIFO
     */
    private function allocateToBatchesFIFO(string $livestockId, int $quantity, float $weight, float $pricePerUnit): array
    {
        Log::info('🔄 allocateToBatchesFIFO called', [
            'livestock_id' => $livestockId,
            'quantity' => $quantity,
            'weight' => $weight,
            'price_per_unit' => $pricePerUnit
        ]);

        $batches = LivestockBatch::where('livestock_id', $livestockId)
            ->where('quantity_available', '>', 0)
            ->where('status', 'active')
            ->orderBy('start_date', 'asc') // FIFO
            ->get();

        Log::info('📊 Available batches found', [
            'livestock_id' => $livestockId,
            'batches_count' => $batches->count(),
            'total_available_quantity' => $batches->sum('quantity_available'),
            'batch_details' => $batches->map(function ($batch) {
                return [
                    'id' => $batch->id,
                    'name' => $batch->name,
                    'quantity_available' => $batch->quantity_available,
                    'status' => $batch->status
                ];
            })->toArray()
        ]);

        $remaining = $quantity;
        $remainingWeight = $weight;
        $allocations = [];
        $order = 0;

        foreach ($batches as $batch) {
            if ($remaining <= 0) break;

            $allocateQty = min($batch->quantity_available, $remaining);
            $allocateWeight = 0;

            // Calculate weight allocation
            if ($remainingWeight > 0) {
                if ($batch->weight_per_unit) {
                    $allocateWeight = $batch->weight_per_unit * $allocateQty;
                } else {
                    // Proportional weight allocation
                    $allocateWeight = ($allocateQty / $quantity) * $weight;
                }
                $allocateWeight = min($allocateWeight, $remainingWeight);
            }

            $allocations[] = [
                'batch_id' => $batch->id,
                'quantity' => $allocateQty,
                'weight' => round($allocateWeight, 2),
                'price_per_unit' => $pricePerUnit,
                'amount' => round($allocateQty * $pricePerUnit, 2),
                'order' => $order++,
            ];

            $remaining -= $allocateQty;
            $remainingWeight -= $allocateWeight;
        }

        if ($remaining > 0) {
            Log::error('❌ Insufficient batch quantity', [
                'livestock_id' => $livestockId,
                'requested_quantity' => $quantity,
                'available_quantity' => $quantity - $remaining,
                'remaining' => $remaining
            ]);
            throw new Exception("Insufficient batch quantity. Need {$quantity}, available: " . ($quantity - $remaining));
        }

        Log::info('✅ Batch allocation completed successfully', [
            'livestock_id' => $livestockId,
            'allocations_count' => count($allocations),
            'total_allocated_quantity' => array_sum(array_column($allocations, 'quantity')),
            'allocations' => $allocations
        ]);

        return $allocations;
    }

    /**
     * Preview batch allocation without updating quantities
     */
    public function previewBatchAllocation(string $livestockId, int $quantity, float $weight, string $date): ServiceResult
    {
        try {
            $batches = LivestockBatch::where('livestock_id', $livestockId)
                ->where('quantity_available', '>', 0)
                ->where('status', 'active')
                ->where('start_date', '<=', $date)
                ->orderBy('start_date', 'asc')
                ->get();

            $remaining = $quantity;
            $remainingWeight = $weight;
            $preview = [];

            foreach ($batches as $batch) {
                if ($remaining <= 0) break;

                $canTake = min($batch->quantity_available, $remaining);
                $weightToTake = 0;

                if ($remainingWeight > 0) {
                    if ($batch->weight_per_unit) {
                        $weightToTake = $batch->weight_per_unit * $canTake;
                    } else {
                        $weightToTake = ($canTake / $quantity) * $weight;
                    }
                    $weightToTake = min($weightToTake, $remainingWeight);
                }

                $preview[] = [
                    'batch_id' => $batch->id,
                    'batch_name' => $batch->name,
                    'start_date' => $batch->start_date,
                    'available_quantity' => $batch->quantity_available,
                    'allocated_quantity' => $canTake,
                    'allocated_weight' => round($weightToTake, 2),
                    'remaining_after' => $batch->quantity_available - $canTake,
                ];

                $remaining -= $canTake;
                $remainingWeight -= $weightToTake;
            }

            $result = [
                'can_fulfill' => $remaining <= 0,
                'requested_quantity' => $quantity,
                'requested_weight' => $weight,
                'remaining_quantity' => $remaining,
                'batch_breakdown' => $preview,
                'total_batches_needed' => count($preview),
            ];

            return ServiceResult::success('Batch allocation preview generated successfully', $result);
        } catch (Exception $e) {
            Log::error('RecordingSaleService::previewBatchAllocation failed', [
                'error' => $e->getMessage(),
                'livestock_id' => $livestockId,
                'quantity' => $quantity,
            ]);
            return ServiceResult::error('Failed to preview batch allocation: ' . $e->getMessage());
        }
    }

    /**
     * Helper: Allocate sale quantity to batches (FIFO) - Legacy method for compatibility
     * Returns: [breakdown, batch_id]
     */
    private function allocateSaleToBatches(string $livestockId, float $quantity, float $weight = null, float $price = null): array
    {
        $batches = LivestockBatch::where('livestock_id', $livestockId)
            ->where('quantity_available', '>', 0)
            ->orderBy('start_date')
            ->lockForUpdate()
            ->get();
        $remaining = $quantity;
        $breakdown = [];
        $mainBatchId = null;
        foreach ($batches as $batch) {
            if ($remaining <= 0) break;
            $qty = min($batch->quantity_available, $remaining);
            if ($mainBatchId === null) $mainBatchId = $batch->id;
            $avgWeight = $batch->weight_per_unit ?? null;
            $breakdown[] = [
                'batch_id' => $batch->id,
                'quantity' => $qty,
                'weight' => $avgWeight ? round($qty * $avgWeight, 2) : null,
                'price_per_unit' => $price,
            ];
            $batch->quantity_available -= $qty;
            $batch->save();
            $remaining -= $qty;
        }
        if ($remaining > 0) {
            throw new \Exception('Stok batch tidak cukup untuk penjualan.');
        }
        return [$breakdown, $mainBatchId];
    }

    /**
     * Preview batch allocation for sale (draft mode - tidak mengubah stok) - Legacy method
     * Returns: [breakdown, batch_id, preview_data]
     */
    private function previewSaleBatchAllocation(string $livestockId, int $quantity, float $weight, string $date): array
    {
        try {
            // Get active batches ordered by FIFO (start_date ASC)
            $activeBatches = LivestockBatch::where('livestock_id', $livestockId)
                ->where('status', 'active')
                ->where('quantity_available', '>', 0)
                ->orderBy('start_date', 'asc')
                ->get();

            if ($activeBatches->isEmpty()) {
                throw new \Exception('Tidak ada batch aktif untuk livestock ini');
            }

            $remainingQuantity = $quantity;
            $remainingWeight = $weight;
            $batchBreakdown = [];
            $primaryBatchId = null;

            foreach ($activeBatches as $batch) {
                if ($remainingQuantity <= 0) break;

                $batchAvailableQuantity = $batch->quantity_available;

                // Calculate available weight based on weight_per_unit
                $batchAvailableWeight = null;
                if ($batch->weight_per_unit && $batchAvailableQuantity > 0) {
                    $batchAvailableWeight = $batch->weight_per_unit * $batchAvailableQuantity;
                }

                // Calculate how much we can take from this batch
                $quantityToTake = min($remainingQuantity, $batchAvailableQuantity);

                // Calculate weight to take based on weight_per_unit or proportional to total weight
                $weightToTake = 0;
                if ($remainingWeight > 0) {
                    if ($batch->weight_per_unit) {
                        // Use weight_per_unit for accurate calculation
                        $weightToTake = $batch->weight_per_unit * $quantityToTake;
                    } else {
                        // Proportional calculation if no weight_per_unit
                        $weightToTake = $remainingWeight * ($quantityToTake / $remainingQuantity);
                    }
                }

                if ($quantityToTake > 0) {
                    // Update batch quantities
                    $batch->update([
                        'quantity_available' => $batchAvailableQuantity - $quantityToTake,
                        'quantity_sales' => $batch->quantity_sales + $quantityToTake,
                        'updated_by' => Auth::id(),
                    ]);

                    $batchBreakdown[] = [
                        'batch_id' => $batch->id,
                        'batch_name' => $batch->name ?? $batch->number ?? 'Batch-' . $batch->id,
                        'quantity' => $quantityToTake,
                        'weight' => round($weightToTake, 2),
                        'price_per_unit' => $batch->price_per_unit ?? 0,
                        'allocated_at' => now()->toISOString(),
                    ];

                    if ($primaryBatchId === null) {
                        $primaryBatchId = $batch->id;
                    }

                    $remainingQuantity -= $quantityToTake;
                    $remainingWeight -= $weightToTake;
                }
            }

            if ($remainingQuantity > 0) {
                throw new \Exception("Stok tidak mencukupi. Kekurangan: {$remainingQuantity} ekor");
            }

            return [
                'breakdown' => $batchBreakdown,
                'primary_batch_id' => $primaryBatchId,
                'preview_data' => [
                    'total_quantity' => $quantity,
                    'total_weight' => round($weight, 2),
                    'batch_count' => count($batchBreakdown),
                    'allocation_date' => $date,
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Error previewing sale batch allocation', [
                'livestock_id' => $livestockId,
                'quantity' => $quantity,
                'weight' => $weight,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Finalize sale with actual batch allocation (mengubah stok)
     * Returns: [breakdown, batch_id]
     */
    private function finalizeSaleBatchAllocation(string $livestockId, int $quantity, float $weight, string $date): array
    {
        return DB::transaction(function () use ($livestockId, $quantity, $weight, $date) {
            // Get active batches ordered by FIFO (start_date ASC)
            $activeBatches = LivestockBatch::where('livestock_id', $livestockId)
                ->where('status', 'active')
                ->where('quantity_available', '>', 0)
                ->orderBy('start_date', 'asc')
                ->lockForUpdate()
                ->get();

            if ($activeBatches->isEmpty()) {
                throw new \Exception('Tidak ada batch aktif untuk livestock ini');
            }

            $remainingQuantity = $quantity;
            $remainingWeight = $weight;
            $batchBreakdown = [];
            $primaryBatchId = null;

            foreach ($activeBatches as $batch) {
                if ($remainingQuantity <= 0) break;

                $batchAvailableQuantity = $batch->quantity_available;

                // Calculate available weight based on weight_per_unit
                $batchAvailableWeight = null;
                if ($batch->weight_per_unit && $batchAvailableQuantity > 0) {
                    $batchAvailableWeight = $batch->weight_per_unit * $batchAvailableQuantity;
                }

                // Calculate how much we can take from this batch
                $quantityToTake = min($remainingQuantity, $batchAvailableQuantity);

                // Calculate weight to take based on weight_per_unit or proportional to total weight
                $weightToTake = 0;
                if ($remainingWeight > 0) {
                    if ($batch->weight_per_unit) {
                        // Use weight_per_unit for accurate calculation
                        $weightToTake = $batch->weight_per_unit * $quantityToTake;
                    } else {
                        // Proportional calculation if no weight_per_unit
                        $weightToTake = $remainingWeight * ($quantityToTake / $remainingQuantity);
                    }
                }

                if ($quantityToTake > 0) {
                    // Update batch quantities
                    $batch->update([
                        'quantity_available' => $batchAvailableQuantity - $quantityToTake,
                        'quantity_sales' => $batch->quantity_sales + $quantityToTake,
                        'updated_by' => Auth::id(),
                    ]);

                    $batchBreakdown[] = [
                        'batch_id' => $batch->id,
                        'batch_name' => $batch->name ?? $batch->number ?? 'Batch-' . $batch->id,
                        'quantity' => $quantityToTake,
                        'weight' => round($weightToTake, 2),
                        'price_per_unit' => $batch->price_per_unit ?? 0,
                        'allocated_at' => now()->toISOString(),
                    ];

                    if ($primaryBatchId === null) {
                        $primaryBatchId = $batch->id;
                    }

                    $remainingQuantity -= $quantityToTake;
                    $remainingWeight -= $weightToTake;
                }
            }

            if ($remainingQuantity > 0) {
                throw new \Exception("Stok tidak mencukupi. Kekurangan: {$remainingQuantity} ekor");
            }

            return [
                'breakdown' => $batchBreakdown,
                'primary_batch_id' => $primaryBatchId,
            ];
        });
    }

    /**
     * Finalize recording sale (move to LivestockSales and update batch quantities)
     */
    public function finalize(string $id): ServiceResult
    {
        try {
            $sale = RecordingSale::find($id);
            if (!$sale) {
                return ServiceResult::error('Recording sale not found.');
            }

            if ($sale->status === 'finalized') {
                return ServiceResult::success('Recording sale already finalized.', $sale->toArray());
            }

            // Finalize batch allocation (mengubah stok)
            $finalizedAllocation = $this->finalizeSaleBatchAllocation(
                $sale->livestock_id,
                $sale->quantity,
                $sale->weight,
                $sale->date
            );

            // Create LivestockSales record
            $livestockSale = LivestockSales::create([
                'tanggal' => $sale->date,
                'customer_name' => $sale->data['customer_name'] ?? 'Unknown',
                'customer_id' => $sale->data['customer_id'] ?? null,
                'created_by' => Auth::id() ?? 1,
                'updated_by' => Auth::id() ?? 1,
            ]);

            // Create LivestockSalesItem record
            $livestockSaleItem = LivestockSalesItem::create([
                'livestock_sales_id' => $livestockSale->id,
                'livestock_id' => $sale->livestock_id,
                'tanggal' => $sale->date,
                'quantity' => $sale->quantity,
                'weight' => $sale->weight,
                'berat_total' => $sale->weight,
                'harga_satuan' => $sale->price,
                'created_by' => Auth::id() ?? 1,
                'updated_by' => Auth::id() ?? 1,
            ]);

            // Update recording sale status
            $sale->update([
                'status' => 'finalized',
                'data' => array_merge($sale->data, [
                    'batch_breakdown' => $finalizedAllocation['breakdown'],
                    'allocation_status' => 'finalized',
                    'livestock_sale_id' => $livestockSale->id,
                    'livestock_sale_item_id' => $livestockSaleItem->id,
                    'finalized_at' => now()->toISOString(),
                ]),
                'updated_by' => Auth::id() ?? 1,
            ]);

            Log::info('Recording sale finalized successfully', [
                'sale_id' => $sale->id,
                'livestock_sale_id' => $livestockSale->id,
                'batch_count' => count($finalizedAllocation['breakdown']),
            ]);

            return ServiceResult::success('Recording sale finalized successfully.', [
                'recording_sale' => $sale->toArray(),
                'livestock_sale' => $livestockSale->toArray(),
                'batch_breakdown' => $finalizedAllocation['breakdown'],
            ]);
        } catch (\Exception $e) {
            Log::error('Error finalizing recording sale', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return ServiceResult::error('Failed to finalize recording sale: ' . $e->getMessage());
        }
    }

    /**
     * Get batch allocation preview for sale
     */
    public function getBatchAllocationPreview(string $livestockId, int $quantity, float $weight, string $date): ServiceResult
    {
        try {
            $preview = $this->previewSaleBatchAllocation($livestockId, $quantity, $weight, $date);

            return ServiceResult::success('Batch allocation preview generated.', $preview);
        } catch (\Exception $e) {
            return ServiceResult::error('Failed to generate batch allocation preview: ' . $e->getMessage());
        }
    }

    /**
     * Validate sale data
     */
    private function validateSaleData(array $data): ServiceResult
    {
        try {
            $required = ['livestock_id', 'date', 'quantity'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return ServiceResult::error("Field '$field' is required.");
                }
            }

            // Validate livestock exists
            $livestock = Livestock::find($data['livestock_id']);
            if (!$livestock) {
                return ServiceResult::error('Livestock not found.');
            }

            // Get sales configuration from CompanyConfig
            $salesConfig = \App\Config\CompanyConfig::getSalesConfig();
            $validationRules = $salesConfig['validation_rules'] ?? [];

            // Validate quantity limits
            $minQuantity = $validationRules['min_quantity'] ?? 1;
            $maxQuantity = $validationRules['max_quantity_per_sale'] ?? 10000;

            if ($data['quantity'] < $minQuantity) {
                return ServiceResult::error("Quantity must be at least {$minQuantity}.");
            }

            if ($data['quantity'] > $maxQuantity) {
                return ServiceResult::error("Quantity cannot exceed {$maxQuantity}.");
            }

            // Validate weight if provided
            if (isset($data['weight']) && $data['weight'] > 0) {
                $weightValidation = $validationRules['weight_validation'] ?? [];

                if ($weightValidation['enabled'] ?? true) {
                    $minWeightPerUnit = $weightValidation['min_weight_per_unit'] ?? 0.1;
                    $maxWeightPerUnit = $weightValidation['max_weight_per_unit'] ?? 10.0;
                    $allowZeroWeight = $weightValidation['allow_zero_weight'] ?? true;

                    $weightPerUnit = $data['weight'] / $data['quantity'];

                    if (!$allowZeroWeight && $weightPerUnit <= 0) {
                        return ServiceResult::error('Weight per unit must be greater than 0.');
                    }

                    if ($weightPerUnit < $minWeightPerUnit) {
                        return ServiceResult::error("Weight per unit must be at least {$minWeightPerUnit} kg.");
                    }

                    if ($weightPerUnit > $maxWeightPerUnit) {
                        return ServiceResult::error("Weight per unit cannot exceed {$maxWeightPerUnit} kg.");
                    }
                }
            }

            // Validate price if provided
            if (isset($data['price']) && $data['price'] > 0) {
                $priceValidation = $validationRules['price_validation'] ?? [];

                if ($priceValidation['enabled'] ?? false) {
                    $minPrice = $priceValidation['min_price_per_unit'] ?? 0;
                    $maxPrice = $priceValidation['max_price_per_unit'] ?? 1000000;
                    $requirePositivePrice = $priceValidation['require_positive_price'] ?? false;

                    if ($requirePositivePrice && $data['price'] <= 0) {
                        return ServiceResult::error('Price must be greater than 0.');
                    }

                    if ($data['price'] < $minPrice) {
                        return ServiceResult::error("Price must be at least {$minPrice}.");
                    }

                    if ($data['price'] > $maxPrice) {
                        return ServiceResult::error("Price cannot exceed {$maxPrice}.");
                    }
                }
            }

            return ServiceResult::success('Validation passed.');
        } catch (\Exception $e) {
            return ServiceResult::error('Validation error: ' . $e->getMessage());
        }
    }

    public function delete(string $id): ServiceResult
    {
        try {
            $sale = RecordingSale::find($id);
            if (!$sale) {
                return ServiceResult::error('Recording sale not found.');
            }

            // Only allow deletion of draft sales
            if ($sale->status === 'finalized') {
                return ServiceResult::error('Cannot delete finalized recording sale.');
            }

            $sale->delete();

            Log::info('Recording sale deleted', [
                'sale_id' => $id,
                'livestock_id' => $sale->livestock_id,
            ]);

            return ServiceResult::success('Recording sale deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Error deleting recording sale', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return ServiceResult::error('Failed to delete recording sale: ' . $e->getMessage());
        }
    }

    public function getById(string $id): ServiceResult
    {
        try {
            $sale = RecordingSale::findOrFail($id);
            return ServiceResult::success('Recording sale found.', $sale->toArray());
        } catch (Exception $e) {
            return ServiceResult::error('Recording sale not found: ' . $e->getMessage());
        }
    }

    public function listByCompanyAndPeriod(string $companyId, string $startDate, string $endDate, array $filters = []): ServiceResult
    {
        try {
            $query = RecordingSale::where('company_id', $companyId)
                ->whereBetween('date', [$startDate, $endDate]);
            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }
            if (isset($filters['livestock_id'])) {
                $query->where('livestock_id', $filters['livestock_id']);
            }
            $sales = $query->get();
            return ServiceResult::success('Recording sales listed.', $sales->all());
        } catch (Exception $e) {
            return ServiceResult::error('Failed to list recording sales: ' . $e->getMessage());
        }
    }

    /**
     * Validate batch allocation against FIFO settings
     */
    private function validateBatchAllocation(string $livestockId, int $quantity, float $weight, string $date): ServiceResult
    {
        try {
            // Get sales configuration
            $salesConfig = \App\Config\CompanyConfig::getSalesConfig();
            $fifoSettings = $salesConfig['batch_allocation']['fifo_settings'] ?? [];

            // Get available batches
            $batches = LivestockBatch::where('livestock_id', $livestockId)
                ->where('status', 'active')
                ->where('quantity_available', '>', 0)
                ->orderBy('start_date', 'asc')
                ->get();

            if ($batches->isEmpty()) {
                return ServiceResult::error('No active batches available for allocation.');
            }

            // Validate minimum age requirement
            $minAgeDays = $fifoSettings['min_age_days'] ?? 30;
            $maxAgeDays = $fifoSettings['max_age_days'] ?? null;
            $minWeightKg = $fifoSettings['min_weight_kg'] ?? 1.0;
            $maxWeightKg = $fifoSettings['max_weight_kg'] ?? 10.0;

            $saleDate = Carbon::parse($date);
            $eligibleBatches = [];

            foreach ($batches as $batch) {
                $batchStartDate = Carbon::parse($batch->start_date);
                $ageDays = $batchStartDate->diffInDays($saleDate);

                // Check age requirements
                if ($ageDays < $minAgeDays) {
                    continue; // Skip batches that are too young
                }

                if ($maxAgeDays !== null && $ageDays > $maxAgeDays) {
                    continue; // Skip batches that are too old
                }

                // Check weight requirements if weight_per_unit is available
                if ($batch->weight_per_unit) {
                    $batchWeightPerUnit = $batch->weight_per_unit;

                    if ($batchWeightPerUnit < $minWeightKg) {
                        continue; // Skip batches with insufficient weight per unit
                    }

                    if ($batchWeightPerUnit > $maxWeightKg) {
                        continue; // Skip batches with excessive weight per unit
                    }
                }

                $eligibleBatches[] = $batch;
            }

            if (empty($eligibleBatches)) {
                $ageMessage = $minAgeDays > 0 ? " (minimum age: {$minAgeDays} days)" : "";
                $weightMessage = $minWeightKg > 0 ? " (minimum weight: {$minWeightKg} kg)" : "";
                return ServiceResult::error("No eligible batches found for allocation{$ageMessage}{$weightMessage}.");
            }

            // Check if we have enough quantity across eligible batches
            $totalAvailableQuantity = collect($eligibleBatches)->sum('quantity_available');

            if ($totalAvailableQuantity < $quantity) {
                return ServiceResult::error("Insufficient quantity in eligible batches. Available: {$totalAvailableQuantity}, Required: {$quantity}.");
            }

            return ServiceResult::success('Batch allocation validation passed.');
        } catch (Exception $e) {
            return ServiceResult::error('Batch allocation validation failed: ' . $e->getMessage());
        }
    }

    /**
     * Validate batch allocation data before processing
     */
    private function validateBatchAllocationData(array $saleData, array $batchAllocations): array
    {
        // Validate sale data
        if (empty($saleData['company_id']) || empty($saleData['livestock_id']) || empty($saleData['recording_id'])) {
            return ['valid' => false, 'message' => 'Missing required sale data fields'];
        }

        if (empty($saleData['quantity']) || $saleData['quantity'] <= 0) {
            return ['valid' => false, 'message' => 'Invalid quantity in sale data'];
        }

        // Validate batch allocations
        if (empty($batchAllocations)) {
            return ['valid' => false, 'message' => 'No batch allocations provided'];
        }

        $totalQuantity = 0;
        $totalWeight = 0;

        foreach ($batchAllocations as $index => $allocation) {
            if (empty($allocation['batch_id'])) {
                return ['valid' => false, 'message' => "Missing batch_id at index {$index}"];
            }

            if (empty($allocation['quantity']) || $allocation['quantity'] <= 0) {
                return ['valid' => false, 'message' => "Invalid quantity at index {$index}"];
            }

            if (!isset($allocation['weight']) || $allocation['weight'] < 0) {
                return ['valid' => false, 'message' => "Invalid weight at index {$index}"];
            }

            $totalQuantity += $allocation['quantity'];
            $totalWeight += $allocation['weight'];
        }

        // Validate totals match
        if ($totalQuantity !== $saleData['quantity']) {
            return ['valid' => false, 'message' => "Quantity mismatch: sale data shows {$saleData['quantity']}, allocations total {$totalQuantity}"];
        }

        if (abs($totalWeight - $saleData['weight']) > 0.01) {
            return ['valid' => false, 'message' => "Weight mismatch: sale data shows {$saleData['weight']}, allocations total {$totalWeight}"];
        }

        return ['valid' => true, 'message' => 'Validation passed'];
    }

    /**
     * Validate sale data (interface method)
     */
    public function validate(array $data): ServiceResult
    {
        return $this->validateSaleData($data);
    }
}

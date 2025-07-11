<?php

namespace App\Services;

use App\Models\SupplyStock;
use App\Models\SupplyUsage;
use App\Models\SupplyUsageDetail;
use App\Models\CurrentSupply;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class SupplyUsageService
{
    public function process(SupplyUsage $usage, array $usages)
    {

        // dd($usages);
        foreach ($usages as $usageRow) {
            $supplyId = $usageRow['supply_id'];
            $requiredQty = $usageRow['quantity'];

            // Ambil stok FIFO (stok yang masih ada dan belum dipakai/mutasi penuh)
            $stocks = SupplyStock::where('livestock_id', $usage->livestock_id)
                ->where('supply_id', $supplyId)
                ->whereRaw('(quantity_in - quantity_used - quantity_mutated) > 0')
                ->orderBy('date')
                ->orderBy('created_at')
                ->lockForUpdate()
                ->get();

            foreach ($stocks as $stock) {
                if ($requiredQty <= 0) break;

                $available = $stock->quantity_in - $stock->quantity_used - $stock->quantity_mutated;
                $usedQty = min($requiredQty, $available);

                // dd($usedQty);

                // Update stok
                // $stock->used += $usedQty;
                $stock->quantity_used += $usedQty;
                $stock->save();

                // Simpan detail pemakaian
                SupplyUsageDetail::create([
                    'supply_usage_id' => $usage->id,
                    'supply_stock_id' => $stock->id,
                    'supply_id' => $supplyId,
                    'quantity_taken' => $usedQty,
                    'created_by' => Auth::id(),
                ]);

                $requiredQty -= $usedQty;
            }

            if ($requiredQty > 0) {
                throw new \Exception("Stok pakan tidak cukup untuk supply ID: $supplyId");
            }
        }
    }

    /**
     * Process supply usage with enhanced metadata tracking
     * 
     * @param SupplyUsage $usage The supply usage record
     * @param array $items Array of supply usage items with metadata
     * @return array Processing results
     */
    public function processWithMetadata(SupplyUsage $usage, array $items)
    {
        DB::beginTransaction();
        try {
            $processedSupplys = [];
            $detailsCount = 0;

            // dd($items);

            foreach ($items as $item) {
                $supplyId = $item['supply_id'];
                // GUNAKAN converted_quantity sebagai field utama
                $requiredQty = isset($item['converted_quantity']) ? floatval($item['converted_quantity']) : 0;
                if (!isset($item['converted_quantity'])) {
                    Log::error('SupplyUsageService: Missing converted_quantity in item', $item);
                }
                if ($requiredQty <= 0) {
                    continue;
                }

                // Get available stocks using FIFO
                $stocks = SupplyStock::where('livestock_id', $usage->livestock_id)
                    ->where('supply_id', $supplyId)
                    ->whereRaw('(quantity_in - quantity_used - quantity_mutated) > 0')
                    ->orderBy('date')
                    ->orderBy('created_at')
                    ->lockForUpdate()
                    ->get();

                $remainingQty = $requiredQty;
                $stocksUsed = [];

                foreach ($stocks as $stock) {
                    if ($remainingQty <= 0) break;

                    $available = $stock->quantity_in - $stock->quantity_used - $stock->quantity_mutated;
                    $qtyToTake = min($available, $remainingQty);

                    // Update stock usage
                    $stock->quantity_used += $qtyToTake;

                    // Update CurrentSupply quantity instead of using $stock->available
                    $currentSupply = $stock->currentSupply;
                    if ($currentSupply) {
                        $currentSupply->quantity -= $qtyToTake;
                        $currentSupply->save();
                    }

                    $stock->save();

                    // Create usage detail with enhanced metadata
                    $detail = SupplyUsageDetail::create([
                        'supply_usage_id' => $usage->id,
                        'supply_stock_id' => $stock->id,
                        'supply_id' => $supplyId,
                        'quantity_taken' => $item['quantity_taken'] ?? $qtyToTake, // ASLI: input user
                        'unit_id' => $item['unit_id'] ?? null,
                        'converted_unit_id' => $item['converted_unit_id'] ?? $item['unit_id'] ?? null,
                        'converted_quantity' => $item['converted_quantity'] ?? $qtyToTake, // HASIL KONVERSI
                        'price_per_unit' => $item['price_per_unit'] ?? null,
                        'price_per_converted_unit' => $item['price_per_converted_unit'] ?? null,
                        'total_price' => $item['total_price'] ?? null,
                        'notes' => $item['notes'] ?? null,
                        'batch_number' => $item['batch_number'] ?? ($stock->batch_number ?? null),
                        'expiry_date' => $item['expiry_date'] ?? ($stock->expiry_date ?? null),
                        'metadata' => [
                            'original_quantity_input' => $item['quantity_taken'] ?? null, // tetap simpan input user
                            'converted_quantity' => $item['converted_quantity'] ?? $qtyToTake, // hasil konversi
                            'supply_info' => [
                                'id' => $supplyId,
                                'name' => $item['supply_name'] ?? 'Unknown',
                                'code' => $item['supply_code'] ?? 'Unknown',
                            ],
                            'stock_info' => [
                                'id' => $stock->id,
                                'purchase_date' => $stock->date,
                                'original_quantity' => $stock->quantity_in,
                                'remaining_before' => $available,
                                'remaining_after' => $available - $qtyToTake,
                            ],
                            'unit_info' => [
                                'original_unit' => [
                                    'id' => $item['original_unit_id'] ?? null,
                                    'name' => $item['original_unit_name'] ?? 'Unknown',
                                ],
                                'smallest_unit' => [
                                    'id' => $item['unit_id'] ?? null,
                                    'name' => $item['unit_name'] ?? 'Unknown',
                                ],
                                'conversion_factor' => $item['conversion_factor'] ?? 1,
                            ],
                            'purchase_info' => [
                                'batch_id' => $stock->supplyPurchase->batch->id ?? null,
                                'batch_number' => $stock->supplyPurchase->batch->invoice_number ?? null,
                                'supplier' => $stock->supplyPurchase->batch->supplier->name ?? 'Unknown',
                            ],
                            'current_supply_info' => [
                                'id' => $currentSupply->id ?? null,
                                'previous_quantity' => $currentSupply->quantity ?? null,
                                'quantity_reduced' => $qtyToTake,
                            ],
                            'created_at' => now()->toIso8601String(),
                            'created_by' => Auth::id(),
                            'created_by_name' => (Auth::user() ? Auth::user()->name : 'Unknown User'),
                        ],
                        'created_by' => Auth::id(),
                    ]);
                    // Log error jika field penting kosong
                    if (empty($item['unit_id']) || empty($item['converted_quantity'])) {
                        Log::error('SupplyUsageService: Missing unit_id or converted_quantity in detail', [
                            'item' => $item,
                            'detail_id' => $detail->id,
                        ]);
                    }

                    // dd($detail);

                    $stocksUsed[] = [
                        'stock_id' => $stock->id,
                        'quantity_taken' => $qtyToTake,
                        'detail_id' => $detail->id,
                    ];

                    $remainingQty -= $qtyToTake;
                    $detailsCount++;
                }

                if ($remainingQty > 0) {
                    throw new \Exception("Insufficient stock for supply {$item['supply_name']} (ID: $supplyId). Still needed: $remainingQty");
                }

                $processedSupplys[] = [
                    'supply_id' => $supplyId,
                    'supply_name' => $item['supply_name'] ?? 'Unknown',
                    'quantity_required' => $requiredQty,
                    'quantity_fulfilled' => $requiredQty - $remainingQty,
                    'stocks_used' => $stocksUsed,
                ];
            }

            // Update usage totals
            $totalQty = collect($processedSupplys)->sum('quantity_fulfilled');
            $usage->update([
                'total_quantity' => $totalQty,
                'metadata' => array_merge($usage->metadata ?? [], [
                    'processed_supplys' => $processedSupplys,
                    'total_details' => $detailsCount,
                    'processed_at' => now()->toIso8601String(),
                    'processed_by' => Auth::id(),
                    'processed_by_name' => (Auth::user() ? Auth::user()->name : 'Unknown User'),
                ]),
            ]);

            DB::commit();

            Log::info("Supply usage processed successfully", [
                'usage_id' => $usage->id,
                'total_quantity' => $totalQty,
                'details_count' => $detailsCount,
                'supplys_count' => count($processedSupplys),
            ]);

            // Ensure these keys are returned
            return [
                'success' => true,
                'usage_id' => $usage->id,
                'details_count' => $detailsCount,
                'supplys_processed' => $processedSupplys,
                'total_quantity' => $totalQty,
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error("Error processing supply usage", [
                'usage_id' => $usage->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            throw $e;
        }
    }

    /**
     * Submit usage for approval
     * 
     * @param SupplyUsage $usage
     * @return array
     */
    public function submitForApproval(SupplyUsage $usage): array
    {
        try {
            DB::beginTransaction();

            if (!$usage->canBeSubmitted()) {
                throw new \Exception("Usage cannot be submitted. Current status: {$usage->status}");
            }

            // Validate stock availability before submitting
            $this->validateStockAvailability($usage);

            // Submit the usage
            $usage->submit();

            // Reserve stock for this usage
            $this->reserveStock($usage);

            DB::commit();

            Log::info("Supply usage submitted for approval", [
                'usage_id' => $usage->id,
                'status' => $usage->status,
                'submitted_by' => Auth::id(),
            ]);

            return [
                'success' => true,
                'message' => 'Usage submitted for approval successfully',
                'usage_id' => $usage->id,
                'status' => $usage->status,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error submitting usage for approval", [
                'usage_id' => $usage->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Approve usage
     * 
     * @param SupplyUsage $usage
     * @return array
     */
    public function approveUsage(SupplyUsage $usage): array
    {
        try {
            DB::beginTransaction();

            if (!$usage->canBeApproved()) {
                throw new \Exception("Usage cannot be approved. Current status: {$usage->status}");
            }

            // Approve the usage
            $usage->approve();

            // Start processing stock reduction
            $this->startStockProcessing($usage);

            DB::commit();

            Log::info("Supply usage approved", [
                'usage_id' => $usage->id,
                'status' => $usage->status,
                'approved_by' => Auth::id(),
            ]);

            return [
                'success' => true,
                'message' => 'Usage approved successfully',
                'usage_id' => $usage->id,
                'status' => $usage->status,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error approving usage", [
                'usage_id' => $usage->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Complete usage
     * 
     * @param SupplyUsage $usage
     * @return array
     */
    public function completeUsage(SupplyUsage $usage): array
    {
        try {
            DB::beginTransaction();

            if (!$usage->isInProcess()) {
                throw new \Exception("Usage cannot be completed. Current status: {$usage->status}");
            }

            // Complete the usage
            $usage->complete();

            // Finalize stock reduction
            $this->finalizeStockReduction($usage);

            DB::commit();

            Log::info("Supply usage completed", [
                'usage_id' => $usage->id,
                'status' => $usage->status,
                'completed_by' => Auth::id(),
            ]);

            return [
                'success' => true,
                'message' => 'Usage completed successfully',
                'usage_id' => $usage->id,
                'status' => $usage->status,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error completing usage", [
                'usage_id' => $usage->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Cancel usage
     * 
     * @param SupplyUsage $usage
     * @param string|null $reason
     * @return array
     */
    public function cancelUsage(SupplyUsage $usage, ?string $reason = null): array
    {
        try {
            DB::beginTransaction();

            if (!$usage->canBeCancelled()) {
                throw new \Exception("Usage cannot be cancelled. Current status: {$usage->status}");
            }

            // Cancel the usage
            $usage->cancel();

            // Restore stock if it was reserved or reduced
            $this->restoreStock($usage);

            // Add cancellation reason to notes
            if ($reason) {
                $usage->update([
                    'notes' => $usage->notes . "\nCancellation Reason: " . $reason
                ]);
            }

            DB::commit();

            Log::info("Supply usage cancelled", [
                'usage_id' => $usage->id,
                'status' => $usage->status,
                'reason' => $reason,
                'cancelled_by' => Auth::id(),
            ]);

            return [
                'success' => true,
                'message' => 'Usage cancelled successfully',
                'usage_id' => $usage->id,
                'status' => $usage->status,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error cancelling usage", [
                'usage_id' => $usage->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Reject usage
     * 
     * @param SupplyUsage $usage
     * @param string $reason
     * @return array
     */
    public function rejectUsage(SupplyUsage $usage, string $reason): array
    {
        try {
            DB::beginTransaction();

            if (!$usage->canBeApproved()) {
                throw new \Exception("Usage cannot be rejected. Current status: {$usage->status}");
            }

            // Reject the usage
            $usage->reject($reason);

            // Restore stock if it was reserved
            $this->restoreStock($usage);

            DB::commit();

            Log::info("Supply usage rejected", [
                'usage_id' => $usage->id,
                'status' => $usage->status,
                'reason' => $reason,
                'rejected_by' => Auth::id(),
            ]);

            return [
                'success' => true,
                'message' => 'Usage rejected successfully',
                'usage_id' => $usage->id,
                'status' => $usage->status,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error rejecting usage", [
                'usage_id' => $usage->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Restore cancelled usage
     * 
     * @param SupplyUsage $usage
     * @return array
     */
    public function restoreUsage(SupplyUsage $usage): array
    {
        try {
            DB::beginTransaction();

            if (!$usage->canBeRestored()) {
                throw new \Exception("Usage cannot be restored. Current status: {$usage->status}");
            }

            // Restore the usage
            $usage->restore();

            // Reserve stock again
            $this->reserveStock($usage);

            DB::commit();

            Log::info("Supply usage restored", [
                'usage_id' => $usage->id,
                'status' => $usage->status,
                'restored_by' => Auth::id(),
            ]);

            return [
                'success' => true,
                'message' => 'Usage restored successfully',
                'usage_id' => $usage->id,
                'status' => $usage->status,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error restoring usage", [
                'usage_id' => $usage->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Validate stock availability
     * 
     * @param SupplyUsage $usage
     * @throws \Exception
     */
    private function validateStockAvailability(SupplyUsage $usage): void
    {
        foreach ($usage->details as $detail) {
            $availableStock = $this->getAvailableStockFromSupplyStock($detail->supply_stock_id);

            if ($availableStock < $detail->converted_quantity) {
                throw new \Exception("Insufficient stock for supply {$detail->supply->name}. Available: {$availableStock}, Required: {$detail->converted_quantity}");
            }
        }
    }

    /**
     * Reserve stock for usage
     * 
     * @param SupplyUsage $usage
     */
    private function reserveStock(SupplyUsage $usage): void
    {
        foreach ($usage->details as $detail) {
            // Mark stock as reserved (could be implemented with a reserved_quantity field)
            Log::info("Stock reserved for usage", [
                'supply_stock_id' => $detail->supply_stock_id,
                'quantity_reserved' => $detail->converted_quantity,
            ]);
        }
    }

    /**
     * Start stock processing
     * 
     * @param SupplyUsage $usage
     */
    private function startStockProcessing(SupplyUsage $usage): void
    {
        foreach ($usage->details as $detail) {
            // Start reducing stock
            $stock = SupplyStock::find($detail->supply_stock_id);
            if ($stock) {
                $stock->quantity_used += $detail->converted_quantity;
                $stock->save();
            }
        }
    }

    /**
     * Finalize stock reduction
     * 
     * @param SupplyUsage $usage
     */
    private function finalizeStockReduction(SupplyUsage $usage): void
    {
        foreach ($usage->details as $detail) {
            // Update CurrentSupply
            $currentSupply = CurrentSupply::where('farm_id', $usage->farm_id)
                ->where('item_id', $detail->supply_id)
                ->where('type', 'Supply')
                ->first();

            if ($currentSupply) {
                $currentSupply->quantity -= $detail->converted_quantity;
                $currentSupply->save();
            }
        }
    }

    /**
     * Restore stock
     * 
     * @param SupplyUsage $usage
     */
    private function restoreStock(SupplyUsage $usage): void
    {
        foreach ($usage->details as $detail) {
            // Restore SupplyStock
            $stock = SupplyStock::find($detail->supply_stock_id);
            if ($stock) {
                $stock->quantity_used = max(0, $stock->quantity_used - $detail->converted_quantity);
                $stock->save();
            }

            // Restore CurrentSupply
            $currentSupply = CurrentSupply::where('farm_id', $usage->farm_id)
                ->where('item_id', $detail->supply_id)
                ->where('type', 'Supply')
                ->first();

            if ($currentSupply) {
                $currentSupply->quantity += $detail->converted_quantity;
                $currentSupply->save();
            }
        }
    }

    /**
     * Get available stock from supply stock
     * 
     * @param string $supplyStockId
     * @return float
     */
    private function getAvailableStockFromSupplyStock(string $supplyStockId): float
    {
        $supplyStock = SupplyStock::find($supplyStockId);

        if (!$supplyStock) {
            return 0;
        }

        return $supplyStock->quantity_in - $supplyStock->quantity_used - $supplyStock->quantity_mutated;
    }
}

// class SupplyUsageService
// {
//     public function create(array $data)
//     {
//         return DB::transaction(function () use ($data) {
//             $usage = SupplyUsage::create([
//                 'id' => Str::uuid(),
//                 'usage_date' => $data['date'],
//                 'livestock_id' => $data['livestock_id'],
//                 'total_quantity' => 0,
//                 'created_by' => auth()->id(),
//             ]);

//             foreach ($data['usages'] as $usageRow) {
//                 $supplyId = $usageRow['supply_id'];
//                 $requiredQty = $usageRow['quantity'];

//                 $stocks = SupplyStock::where('livestock_id', $data['livestock_id'])
//                     ->where('supply_id', $supplyId)
//                     ->whereRaw('(quantity_in - quantity_used - quantity_mutated) > 0')
//                     ->orderBy('date')
//                     ->orderBy('created_at')
//                     ->lockForUpdate()
//                     ->get();

//                 foreach ($stocks as $stock) {
//                     if ($requiredQty <= 0) break;

//                     $available = $stock->quantity_in - $stock->quantity_used - $stock->quantity_mutated;
//                     $usedQty = min($requiredQty, $available);

//                     $stock->used += $usedQty;
//                     $stock->quantity_used += $usedQty;
//                     $stock->save();

//                     SupplyUsageDetail::create([
//                         'supply_usage_id' => $usage->id,
//                         'supply_stock_id' => $stock->id,
//                         'supply_id' => $stock->supply_id,
//                         'quantity_taken' => $usedQty,
//                         'created_by' => auth()->id(),
//                     ]);

//                     $requiredQty -= $usedQty;
//                 }

//                 if ($requiredQty > 0) {
//                     throw new \Exception("Stok pakan tidak cukup untuk supply ID: $supplyId");
//                 }
//             }

//             return $usage;
//         });
//     }
// }

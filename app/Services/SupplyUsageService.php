<?php

namespace App\Services;

use App\Models\SupplyStock;
use App\Models\SupplyUsage;
use App\Models\SupplyUsageDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

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
                    'created_by' => auth()->id(),
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
                $requiredQty = floatval($item['quantity']);

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
                        'quantity_taken' => $qtyToTake,
                        'metadata' => [
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
                            'created_by' => auth()->id(),
                            'created_by_name' => auth()->user()->name ?? 'Unknown User',
                        ],
                        'created_by' => auth()->id(),
                    ]);

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
                    'processed_by' => auth()->id(),
                    'processed_by_name' => auth()->user()->name ?? 'Unknown User',
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

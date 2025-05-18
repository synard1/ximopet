<?php

namespace App\Services;

use App\Models\FeedStock;
use App\Models\FeedUsage;
use App\Models\FeedUsageDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class FeedUsageService
{
    public function process(FeedUsage $usage, array $usages)
    {

        // dd($usages);
        foreach ($usages as $usageRow) {
            $feedId = $usageRow['feed_id'];
            $requiredQty = $usageRow['quantity'];

            // Ambil stok FIFO (stok yang masih ada dan belum dipakai/mutasi penuh)
            $stocks = FeedStock::where('livestock_id', $usage->livestock_id)
                ->where('feed_id', $feedId)
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
                FeedUsageDetail::create([
                    'feed_usage_id' => $usage->id,
                    'feed_stock_id' => $stock->id,
                    'feed_id' => $feedId,
                    'quantity_taken' => $usedQty,
                    'created_by' => auth()->id(),
                ]);

                $requiredQty -= $usedQty;
            }

            if ($requiredQty > 0) {
                throw new \Exception("Stok pakan tidak cukup untuk feed ID: $feedId");
            }
        }
    }

    /**
     * Process feed usage with enhanced metadata tracking
     * 
     * @param FeedUsage $usage The feed usage record
     * @param array $items Array of feed usage items with metadata
     * @return array Processing results
     */
    public function processWithMetadata(FeedUsage $usage, array $items)
    {
        DB::beginTransaction();
        try {
            $processedFeeds = [];
            $detailsCount = 0;

            // dd($items);

            foreach ($items as $item) {
                $feedId = $item['feed_id'];
                $requiredQty = floatval($item['quantity']);

                if ($requiredQty <= 0) {
                    continue;
                }

                // Get available stocks using FIFO
                $stocks = FeedStock::where('livestock_id', $usage->livestock_id)
                    ->where('feed_id', $feedId)
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
                    $detail = FeedUsageDetail::create([
                        'feed_usage_id' => $usage->id,
                        'feed_stock_id' => $stock->id,
                        'feed_id' => $feedId,
                        'quantity_taken' => $qtyToTake,
                        'metadata' => [
                            'feed_info' => [
                                'id' => $feedId,
                                'name' => $item['feed_name'] ?? 'Unknown',
                                'code' => $item['feed_code'] ?? 'Unknown',
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
                                'batch_id' => $stock->feedPurchase->batch->id ?? null,
                                'batch_number' => $stock->feedPurchase->batch->invoice_number ?? null,
                                'supplier' => $stock->feedPurchase->batch->supplier->name ?? 'Unknown',
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
                    throw new \Exception("Insufficient stock for feed {$item['feed_name']} (ID: $feedId). Still needed: $remainingQty");
                }

                $processedFeeds[] = [
                    'feed_id' => $feedId,
                    'feed_name' => $item['feed_name'] ?? 'Unknown',
                    'quantity_required' => $requiredQty,
                    'quantity_fulfilled' => $requiredQty - $remainingQty,
                    'stocks_used' => $stocksUsed,
                ];
            }

            // Update usage totals
            $totalQty = collect($processedFeeds)->sum('quantity_fulfilled');
            $usage->update([
                'total_quantity' => $totalQty,
                'metadata' => array_merge($usage->metadata ?? [], [
                    'processed_feeds' => $processedFeeds,
                    'total_details' => $detailsCount,
                    'processed_at' => now()->toIso8601String(),
                    'processed_by' => auth()->id(),
                    'processed_by_name' => auth()->user()->name ?? 'Unknown User',
                ]),
            ]);

            DB::commit();

            Log::info("Feed usage processed successfully", [
                'usage_id' => $usage->id,
                'total_quantity' => $totalQty,
                'details_count' => $detailsCount,
                'feeds_count' => count($processedFeeds),
            ]);

            // Ensure these keys are returned
            return [
                'success' => true,
                'usage_id' => $usage->id,
                'details_count' => $detailsCount,
                'feeds_processed' => $processedFeeds,
                'total_quantity' => $totalQty,
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error("Error processing feed usage", [
                'usage_id' => $usage->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            throw $e;
        }
    }
}

// class FeedUsageService
// {
//     public function create(array $data)
//     {
//         return DB::transaction(function () use ($data) {
//             $usage = FeedUsage::create([
//                 'id' => Str::uuid(),
//                 'usage_date' => $data['date'],
//                 'livestock_id' => $data['livestock_id'],
//                 'total_quantity' => 0,
//                 'created_by' => auth()->id(),
//             ]);

//             foreach ($data['usages'] as $usageRow) {
//                 $feedId = $usageRow['feed_id'];
//                 $requiredQty = $usageRow['quantity'];

//                 $stocks = FeedStock::where('livestock_id', $data['livestock_id'])
//                     ->where('feed_id', $feedId)
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

//                     FeedUsageDetail::create([
//                         'feed_usage_id' => $usage->id,
//                         'feed_stock_id' => $stock->id,
//                         'feed_id' => $stock->feed_id,
//                         'quantity_taken' => $usedQty,
//                         'created_by' => auth()->id(),
//                     ]);

//                     $requiredQty -= $usedQty;
//                 }

//                 if ($requiredQty > 0) {
//                     throw new \Exception("Stok pakan tidak cukup untuk feed ID: $feedId");
//                 }
//             }

//             return $usage;
//         });
//     }
// }

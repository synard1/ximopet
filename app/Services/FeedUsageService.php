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

                // Update stok
                $stock->quantity_used += $usedQty;
                $stock->save();

                // Update CurrentFeed (CurrentSupply) jika ada
                if (method_exists($stock, 'currentFeed')) {
                    $currentFeed = $stock->currentFeed;
                    if ($currentFeed) {
                        // Hitung ulang sisa quantity dari semua stock terkait feed & livestock
                        $totalRemaining = FeedStock::where('livestock_id', $usage->livestock_id)
                            ->where('feed_id', $feedId)
                            ->sum(DB::raw('quantity_in - quantity_used - quantity_mutated'));
                        $currentFeed->quantity = $totalRemaining;
                        $currentFeed->save();
                    }
                }

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

            // Add comprehensive validation and logging
            Log::info("FeedUsageService::processWithMetadata called", [
                'usage_id' => $usage->id,
                'items_count' => count($items),
                'items_type' => gettype($items),
                'items_sample' => array_slice($items, 0, 2), // Log first 2 items for debugging
            ]);

            // Validate items structure
            if (!is_array($items)) {
                throw new \Exception("Items parameter must be an array, got: " . gettype($items));
            }

            foreach ($items as $index => $item) {
                // Validate each item
                if (!is_array($item)) {
                    Log::error("Invalid item structure at index {$index}", [
                        'item_type' => gettype($item),
                        'item_value' => $item,
                        'usage_id' => $usage->id
                    ]);
                    throw new \Exception("Item at index {$index} must be an array, got: " . gettype($item));
                }

                // Validate required fields
                if (!isset($item['feed_id'])) {
                    Log::error("Missing feed_id in item at index {$index}", [
                        'item' => $item,
                        'usage_id' => $usage->id
                    ]);
                    throw new \Exception("Missing feed_id in item at index {$index}");
                }

                if (!isset($item['quantity'])) {
                    Log::error("Missing quantity in item at index {$index}", [
                        'item' => $item,
                        'usage_id' => $usage->id
                    ]);
                    throw new \Exception("Missing quantity in item at index {$index}");
                }

                $feedId = $item['feed_id'];
                $requiredQty = floatval($item['quantity']);

                Log::info("Processing feed item", [
                    'index' => $index,
                    'feed_id' => $feedId,
                    'quantity' => $requiredQty,
                    'item_data' => $item
                ]);

                if ($requiredQty <= 0) {
                    Log::info("Skipping item with zero or negative quantity", [
                        'feed_id' => $feedId,
                        'quantity' => $requiredQty
                    ]);
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

                Log::info("Found available stocks", [
                    'feed_id' => $feedId,
                    'stocks_count' => $stocks->count(),
                    'livestock_id' => $usage->livestock_id
                ]);

                $remainingQty = $requiredQty;
                $stocksUsed = [];

                foreach ($stocks as $stock) {
                    if ($remainingQty <= 0) break;

                    $available = $stock->quantity_in - $stock->quantity_used - $stock->quantity_mutated;
                    $qtyToTake = min($available, $remainingQty);

                    // Update stock usage
                    $stock->quantity_used += $qtyToTake;

                    // Update CurrentFeed (CurrentSupply) jika ada
                    if (method_exists($stock, 'currentFeed')) {
                        $currentFeed = $stock->currentFeed;
                        if ($currentFeed) {
                            $currentFeed->quantity -= $qtyToTake;
                            $currentFeed->save();
                        }
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
                            'current_feed_info' => [
                                'id' => $currentFeed->id ?? null,
                                'previous_quantity' => $currentFeed->quantity ?? null,
                                'quantity_reduced' => $qtyToTake,
                            ],
                            'created_at' => now()->toIso8601String(),
                            'created_by' => auth()->id(),
                            'created_by_name' => auth()->user()->name ?? 'Unknown User',
                        ],
                        'created_by' => auth()->id(),
                    ]);

                    $stocksUsed[] = [
                        'stock_id' => $stock->id,
                        'quantity_taken' => $qtyToTake,
                        'detail_id' => $detail->id,
                    ];

                    $remainingQty -= $qtyToTake;
                    $detailsCount++;
                }

                // Setelah semua stock diambil, update CurrentFeed (CurrentSupply) dengan quantity terakhir
                // (jumlah sisa dari semua stock feed & livestock terkait)
                if (isset($currentFeed) && $currentFeed) {
                    $totalRemaining = FeedStock::where('livestock_id', $usage->livestock_id)
                        ->where('feed_id', $feedId)
                        ->sum(DB::raw('quantity_in - quantity_used - quantity_mutated'));
                    $currentFeed->quantity = $totalRemaining;
                    $currentFeed->save();
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
                'items_count' => count($items),
                'items_sample' => array_slice($items, 0, 2),
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

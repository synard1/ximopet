<?php

namespace App\Services;

use App\Models\FeedStock;
use App\Models\Mutation;
use App\Models\MutationItem;
use App\Models\FeedPurchase;
use App\Models\FeedUsage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\DataAuditTrail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class FeedDataIntegrityService
{
    protected $logs = [];
    protected $version = '1.1.0';

    public function previewInvalidFeedData()
    {
        try {
            $this->logs = [];
            $this->logs[] = [
                'type' => 'info',
                'message' => 'FeedDataIntegrityService version ' . $this->version . ' - ' . now(),
                'data' => [],
            ];

            // Find invalid feed stocks
            $invalidStocks = FeedStock::where(function ($query) {
                $query->where('source_type', 'mutation')
                    ->where(function ($q) {
                        $q->whereNotExists(function ($subQuery) {
                            $subQuery->select(DB::raw(1))
                                ->from('mutations')
                                ->whereColumn('mutations.id', 'feed_stocks.source_id')
                                ->whereNull('mutations.deleted_at');
                        })
                            ->orWhereNotExists(function ($subQuery) {
                                $subQuery->select(DB::raw(1))
                                    ->from('mutation_items')
                                    ->whereColumn('mutation_items.mutation_id', 'feed_stocks.source_id')
                                    ->where('mutation_items.item_type', 'feed')
                                    ->whereNull('mutation_items.deleted_at');
                            });
                    });
            })
                ->orWhere(function ($query) {
                    $query->where('source_type', 'purchase')
                        ->whereNotExists(function ($subQuery) {
                            $subQuery->select(DB::raw(1))
                                ->from('feed_purchases')
                                ->whereColumn('feed_purchases.id', 'feed_stocks.source_id')
                                ->whereNull('feed_purchases.deleted_at');
                        });
                })
                ->get();

            foreach ($invalidStocks as $stock) {
                $reasons = [];

                if ($stock->source_type === 'mutation') {
                    // Check mutation existence
                    $mutationExists = DB::table('mutations')
                        ->where('id', $stock->source_id)
                        ->whereNull('deleted_at')
                        ->exists();

                    if (!$mutationExists) {
                        $reasons[] = "Mutation record with ID {$stock->source_id} does not exist or has been deleted";
                    }

                    // Check mutation item existence
                    $mutationItemExists = DB::table('mutation_items')
                        ->where('mutation_id', $stock->source_id)
                        ->where('item_type', 'feed')
                        ->whereNull('deleted_at')
                        ->exists();

                    if (!$mutationItemExists) {
                        $reasons[] = "No mutation items found for mutation ID {$stock->source_id}";
                    }
                } elseif ($stock->source_type === 'purchase') {
                    // Check purchase existence
                    $purchaseExists = DB::table('feed_purchases')
                        ->where('id', $stock->source_id)
                        ->whereNull('deleted_at')
                        ->exists();

                    if (!$purchaseExists) {
                        $reasons[] = "Feed purchase record with ID {$stock->source_id} does not exist or has been deleted";
                    }
                }

                $this->logs[] = [
                    'type' => 'invalid_stock',
                    'message' => "Found invalid feed stock: ID {$stock->id}, Source: {$stock->source_type}, Source ID: {$stock->source_id}",
                    'data' => $stock->toArray(),
                    'reasons' => $reasons
                ];
            }

            // --- Cek purchase tanpa stock ---
            $purchasesWithoutStock = FeedPurchase::whereNull('deleted_at')
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('feed_stocks')
                        ->whereColumn('feed_stocks.source_id', 'feed_purchases.id')
                        ->where('feed_stocks.source_type', 'purchase')
                        ->whereNull('feed_stocks.deleted_at');
                })
                ->get();
            foreach ($purchasesWithoutStock as $purchase) {
                $this->logs[] = [
                    'type' => 'missing_stock',
                    'message' => "Purchase record with ID {$purchase->id} does not have a corresponding feed stock.",
                    'data' => $purchase->toArray(),
                    'reasons' => ["No feed stock found for this purchase record."]
                ];
            }

            // --- Cek mutation tanpa stock ---
            $mutationsWithoutStock = Mutation::whereNull('deleted_at')
                ->where('type', 'feed')
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('feed_stocks')
                        ->whereColumn('feed_stocks.source_id', 'mutations.id')
                        ->where('feed_stocks.source_type', 'mutation')
                        ->whereNull('feed_stocks.deleted_at');
                })
                ->get();
            foreach ($mutationsWithoutStock as $mutation) {
                $this->logs[] = [
                    'type' => 'missing_stock',
                    'message' => "Mutation record with ID {$mutation->id} does not have a corresponding feed stock.",
                    'data' => $mutation->toArray(),
                    'reasons' => ["No feed stock found for this mutation record."]
                ];
            }

            // --- Cek quantity mismatch antara feed stock dan purchase ---
            $stocksWithPurchase = FeedStock::where('source_type', 'purchase')
                ->whereNull('deleted_at')
                ->get();
            foreach ($stocksWithPurchase as $stock) {
                $purchase = FeedPurchase::where('id', $stock->source_id)->whereNull('deleted_at')->first();
                if ($purchase && $stock->quantity_in != $purchase->quantity) {
                    $this->logs[] = [
                        'type' => 'quantity_mismatch',
                        'message' => "Quantity mismatch: Stock ID {$stock->id} (quantity_in: {$stock->quantity_in}) vs Purchase ID {$purchase->id} (quantity: {$purchase->quantity})",
                        'data' => [
                            'stock' => $stock->toArray(),
                            'purchase' => $purchase->toArray(),
                        ],
                        'reasons' => [
                            "Stock quantity_in ({$stock->quantity_in}) does not match purchase quantity ({$purchase->quantity})"
                        ]
                    ];
                }
            }

            // --- Cek conversion mismatch pada feed purchase ---
            $purchases = FeedPurchase::whereNull('deleted_at')->get();
            foreach ($purchases as $purchase) {
                $expectedConverted = $purchase->calculateConvertedQuantity();
                if ((float) $purchase->converted_quantity !== (float) $expectedConverted) {
                    $this->logs[] = [
                        'type' => 'conversion_mismatch',
                        'message' => "Conversion mismatch: Purchase ID {$purchase->id} (converted_quantity: {$purchase->converted_quantity}) vs Expected ({$expectedConverted})",
                        'data' => $purchase->toArray(),
                        'reasons' => [
                            "Converted quantity ({$purchase->converted_quantity}) does not match calculated value ({$expectedConverted})"
                        ]
                    ];
                }
            }

            // --- Validasi FeedStock source_type=mutation: cek quantity_in ---
            $mutationStocks = FeedStock::where('source_type', 'mutation')->whereNull('deleted_at')->get();
            foreach ($mutationStocks as $stock) {
                $mutationItems = \App\Models\MutationItem::where('mutation_id', $stock->source_id)
                    ->where('item_type', 'feed')
                    // ->where('stock_id', $stock->id)
                    ->whereNull('deleted_at')
                    ->get();
                // dd($mutationItems);
                $totalMutationQty = $mutationItems->sum('quantity');
                if ((float)$stock->quantity_in !== (float)$totalMutationQty) {
                    $this->logs[] = [
                        'type' => 'mutation_quantity_mismatch',
                        'message' => "Quantity mismatch: Stock ID {$stock->id} (quantity_in: {$stock->quantity_in}) vs Total MutationItem ({$totalMutationQty})",
                        'data' => [
                            'stock' => $stock->toArray(),
                            'mutation_items' => $mutationItems->toArray(),
                        ],
                        'reasons' => [
                            "Stock quantity_in ({$stock->quantity_in}) does not match total mutation item quantity ({$totalMutationQty})"
                        ]
                    ];
                }
            }
            // --- Validasi MutationItem: stock_id harus ada di feed_stocks ---
            $mutationItems = \App\Models\MutationItem::where('item_type', 'feed')->whereNull('deleted_at')->get();
            foreach ($mutationItems as $item) {
                $stockExists = FeedStock::where('id', $item->stock_id)->whereNull('deleted_at')->exists();
                if (!$stockExists) {
                    $this->logs[] = [
                        'type' => 'mutation_item_invalid_stock',
                        'message' => "MutationItem ID {$item->id} refers to non-existent or deleted stock_id {$item->stock_id}",
                        'data' => $item->toArray(),
                        'reasons' => [
                            "MutationItem stock_id ({$item->stock_id}) does not exist in feed_stocks or has been deleted."
                        ]
                    ];
                }
            }

            // --- VALIDASI LANJUTAN ---
            // 1. Validasi tanggal mutation
            $mutations = \App\Models\Mutation::whereNull('deleted_at')->get();
            foreach ($mutations as $mutation) {
                if (!$mutation->date || !strtotime($mutation->date)) {
                    $this->logs[] = [
                        'type' => 'mutation_invalid_date',
                        'message' => "Mutation ID {$mutation->id} has invalid or missing date.",
                        'data' => $mutation->toArray(),
                        'reasons' => ["Tanggal mutation tidak valid atau kosong."]
                    ];
                }
            }
            // 2. Validasi tanggal purchase batch
            $batches = \App\Models\FeedPurchaseBatch::whereNull('deleted_at')->get();
            foreach ($batches as $batch) {
                if (!$batch->date || !strtotime($batch->date)) {
                    $this->logs[] = [
                        'type' => 'purchase_batch_invalid_date',
                        'message' => "Purchase Batch ID {$batch->id} has invalid or missing date.",
                        'data' => $batch->toArray(),
                        'reasons' => ["Tanggal batch purchase tidak valid atau kosong."]
                    ];
                }
            }
            // 3. Validasi unit conversion pada setiap FeedPurchase
            $purchases = \App\Models\FeedPurchase::whereNull('deleted_at')->get();
            foreach ($purchases as $purchase) {
                if ($purchase->unit_id && $purchase->converted_unit) {
                    $conversion = \App\Models\UnitConversion::where('unit_id', $purchase->unit_id)
                        ->where('conversion_unit_id', $purchase->converted_unit)
                        ->where('status', 'active')
                        ->first();
                    if (!$conversion) {
                        $this->logs[] = [
                            'type' => 'unit_conversion_invalid',
                            'message' => "FeedPurchase ID {$purchase->id} has invalid or missing unit conversion.",
                            'data' => $purchase->toArray(),
                            'reasons' => ["Unit conversion tidak ditemukan atau tidak aktif untuk unit_id {$purchase->unit_id} ke {$purchase->converted_unit}."]
                        ];
                    }
                }
            }
            // 4. Validasi harga batch
            foreach ($purchases as $purchase) {
                $batch = $purchase->batch;
                if ($batch && isset($batch->payload['price_per_unit'])) {
                    $expectedPrice = $batch->payload['price_per_unit'];
                    if ((float)$purchase->price_per_unit !== (float)$expectedPrice) {
                        $this->logs[] = [
                            'type' => 'price_mismatch',
                            'message' => "FeedPurchase ID {$purchase->id} price_per_unit ({$purchase->price_per_unit}) does not match batch price ({$expectedPrice})",
                            'data' => $purchase->toArray(),
                            'reasons' => ["Harga per unit tidak sesuai dengan harga batch."]
                        ];
                    }
                }
            }
            // 5. Validasi farm aktif
            $farms = \App\Models\Farm::all()->keyBy('id');
            foreach ($purchases as $purchase) {
                $farm = $farms[$purchase->livestok->farm_id] ?? null;
                if (!$farm || (isset($farm->status) && strtolower($farm->status) !== 'active')) {
                    $this->logs[] = [
                        'type' => 'farm_inactive',
                        'message' => "FeedPurchase ID {$purchase->id} mengarah ke farm tidak aktif atau tidak ditemukan.",
                        'data' => $purchase->toArray(),
                        'reasons' => ["Farm tidak aktif atau tidak ditemukan."]
                    ];
                }
            }

            return [
                'success' => true,
                'logs' => $this->logs,
                'invalid_stocks_count' => $invalidStocks->count()
            ];
        } catch (\Exception $e) {
            Log::error('Error in FeedDataIntegrityService preview: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'logs' => $this->logs
            ];
        }
    }

    public function checkAndFixInvalidFeedData()
    {
        DB::beginTransaction();
        try {
            $this->logs = [];

            // Find invalid feed stocks
            $invalidStocks = FeedStock::where(function ($query) {
                $query->where('source_type', 'mutation')
                    ->where(function ($q) {
                        $q->whereNotExists(function ($subQuery) {
                            $subQuery->select(DB::raw(1))
                                ->from('mutations')
                                ->whereColumn('mutations.id', 'feed_stocks.source_id')
                                ->whereNull('mutations.deleted_at');
                        })
                            ->orWhereNotExists(function ($subQuery) {
                                $subQuery->select(DB::raw(1))
                                    ->from('mutation_items')
                                    ->whereColumn('mutation_items.mutation_id', 'feed_stocks.source_id')
                                    ->where('mutation_items.item_type', 'feed')
                                    ->whereNull('mutation_items.deleted_at');
                            });
                    });
            })
                ->orWhere(function ($query) {
                    $query->where('source_type', 'purchase')
                        ->whereNotExists(function ($subQuery) {
                            $subQuery->select(DB::raw(1))
                                ->from('feed_purchases')
                                ->whereColumn('feed_purchases.id', 'feed_stocks.source_id')
                                ->whereNull('feed_purchases.deleted_at');
                        });
                })
                ->get();

            foreach ($invalidStocks as $stock) {
                $reasons = [];

                if ($stock->source_type === 'mutation') {
                    // Check mutation existence
                    $mutationExists = DB::table('mutations')
                        ->where('id', $stock->source_id)
                        ->whereNull('deleted_at')
                        ->exists();

                    if (!$mutationExists) {
                        $reasons[] = "Mutation record with ID {$stock->source_id} does not exist or has been deleted";
                    }

                    // Check mutation item existence
                    $mutationItemExists = DB::table('mutation_items')
                        ->where('mutation_id', $stock->source_id)
                        ->where('item_type', 'feed')
                        ->whereNull('deleted_at')
                        ->exists();

                    if (!$mutationItemExists) {
                        $reasons[] = "No mutation items found for mutation ID {$stock->source_id}";
                    }
                } elseif ($stock->source_type === 'purchase') {
                    // Check purchase existence
                    $purchaseExists = DB::table('feed_purchases')
                        ->where('id', $stock->source_id)
                        ->whereNull('deleted_at')
                        ->exists();

                    if (!$purchaseExists) {
                        $reasons[] = "Feed purchase record with ID {$stock->source_id} does not exist or has been deleted";
                    }
                }

                $this->logs[] = [
                    'type' => 'invalid_stock',
                    'message' => "Found invalid feed stock: ID {$stock->id}, Source: {$stock->source_type}, Source ID: {$stock->source_id}",
                    'data' => $stock->toArray(),
                    'reasons' => $reasons
                ];

                // Delete invalid stock
                $stock->delete();
                $this->logs[] = [
                    'type' => 'deleted_stock',
                    'message' => "Deleted invalid feed stock: ID {$stock->id}",
                    'data' => $stock->toArray()
                ];

                // Recalculate stock for the farm
                $this->recalculateFarmStock($stock->farm_id, $stock->feed_id);
            }

            DB::commit();
            return [
                'success' => true,
                'logs' => $this->logs,
                'deleted_stocks_count' => $invalidStocks->count()
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in FeedDataIntegrityService: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'logs' => $this->logs
            ];
        }
    }

    protected function recalculateFarmStock($farmId, $feedId)
    {
        // Get all purchases for this feed and farm
        $purchases = FeedPurchase::where('farm_id', $farmId)
            ->where('feed_id', $feedId)
            ->sum('quantity');

        // Get all usages for this feed and farm
        $usages = DB::table('feed_usage_details')
            ->join('feed_usages', 'feed_usage_details.feed_usage_id', '=', 'feed_usages.id')
            ->where('feed_usages.livestock_id', $farmId)
            ->where('feed_usage_details.feed_id', $feedId)
            // ->where('feed_usages.status', 'approved')
            ->sum('feed_usage_details.quantity_taken');

        // Get all valid mutations (incoming)
        $incomingMutations = FeedStock::where('farm_id', $farmId)
            ->where('feed_id', $feedId)
            ->where('source_type', 'mutation')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('mutations')
                    ->whereColumn('mutations.id', 'feed_stocks.source_id');
                // ->where('mutations.status', 'approved');
            })
            ->sum('quantity_in');

        // Get all valid mutations (outgoing)
        $outgoingMutations = FeedStock::where('farm_id', $farmId)
            ->where('feed_id', $feedId)
            ->where('source_type', 'mutation')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('mutations')
                    ->whereColumn('mutations.id', 'feed_stocks.source_id');
                // ->where('mutations.status', 'approved');
            })
            ->sum('quantity_mutated');

        // Calculate current stock
        $currentStock = $purchases + $incomingMutations - $usages - $outgoingMutations;

        // Update current feed record
        $currentFeed = \App\Models\CurrentFeed::updateOrCreate(
            [
                'farm_id' => $farmId,
                'item_id' => $feedId,
                'type' => 'feed'
            ],
            [
                'quantity' => $currentStock,
                'updated_by' => auth()->id()
            ]
        );

        $this->logs[] = [
            'type' => 'recalculation',
            'message' => "Recalculated stock for Farm ID: {$farmId}, Feed ID: {$feedId}",
            'data' => [
                'purchases' => $purchases,
                'usages' => $usages,
                'incoming_mutations' => $incomingMutations,
                'outgoing_mutations' => $outgoingMutations,
                'current_stock' => $currentStock
            ]
        ];
    }

    /**
     * Restore related record (purchase/mutation) if soft deleted
     */
    public function restoreRelatedRecord($type, $sourceId)
    {
        if ($type === 'purchase') {
            $purchase = FeedPurchase::withTrashed()->find($sourceId);
            if ($purchase && $purchase->deleted_at) {
                $purchase->restore();
                $this->logs[] = [
                    'type' => 'restore',
                    'message' => "Restored feed purchase with ID $sourceId",
                    'data' => $purchase->toArray(),
                ];
                return true;
            }
        } elseif ($type === 'mutation') {
            $mutation = Mutation::withTrashed()->find($sourceId);
            if ($mutation && $mutation->deleted_at) {
                $mutation->restore();
                $this->logs[] = [
                    'type' => 'restore',
                    'message' => "Restored mutation with ID $sourceId",
                    'data' => $mutation->toArray(),
                ];
                // Restore mutation items as well
                MutationItem::withTrashed()->where('mutation_id', $sourceId)->restore();
                return true;
            }
        }
        return false;
    }

    /**
     * Check if related record can be restored (soft deleted)
     */
    public function canRestore($type, $sourceId)
    {
        if ($type === 'purchase') {
            $purchase = FeedPurchase::withTrashed()->find($sourceId);
            return $purchase && $purchase->deleted_at;
        } elseif ($type === 'mutation') {
            $mutation = Mutation::withTrashed()->find($sourceId);
            return $mutation && $mutation->deleted_at;
        }
        return false;
    }

    /**
     * Restore missing feed stock for purchase or mutation
     */
    public function restoreMissingStock($type, $sourceId)
    {
        if ($type === 'purchase') {
            $purchase = FeedPurchase::where('id', $sourceId)->whereNull('deleted_at')->first();
            if ($purchase) {
                // Ambil tanggal dari batch
                $batchDate = $purchase->batch ? $purchase->batch->date : now();
                // Cek apakah stock sudah ada (double check)
                $existing = FeedStock::where('source_type', 'purchase')
                    ->where('source_id', $purchase->id)
                    ->whereNull('deleted_at')
                    ->first();
                if (!$existing) {
                    $stock = FeedStock::create([
                        'farm_id' => $purchase->farm_id,
                        'feed_id' => $purchase->feed_id,
                        'feed_purchase_id' => $purchase->id,
                        'date' => $batchDate,
                        'source_type' => 'purchase',
                        'source_id' => $purchase->id,
                        'quantity_in' => $purchase->quantity,
                        'quantity_used' => 0,
                        'quantity_mutated' => 0,
                        'created_by' => $purchase->created_by,
                    ]);
                    $this->logs[] = [
                        'type' => 'restore_stock',
                        'message' => "Restored feed stock for purchase ID $sourceId",
                        'data' => $stock->toArray(),
                    ];
                    return true;
                }
            }
        } elseif ($type === 'mutation') {
            $mutation = Mutation::where('id', $sourceId)->whereNull('deleted_at')->first();
            if ($mutation) {
                $existing = FeedStock::where('source_type', 'mutation')
                    ->where('source_id', $mutation->id)
                    ->whereNull('deleted_at')
                    ->first();
                if (!$existing) {
                    $stock = FeedStock::create([
                        'farm_id' => $mutation->farm_id,
                        'feed_id' => $mutation->feed_id,
                        'date' => $mutation->date ?? now(),
                        'source_type' => 'mutation',
                        'source_id' => $mutation->id,
                        'quantity_in' => $mutation->quantity ?? 0,
                        'quantity_used' => 0,
                        'quantity_mutated' => 0,
                        'created_by' => $mutation->created_by,
                    ]);
                    $this->logs[] = [
                        'type' => 'restore_stock',
                        'message' => "Restored feed stock for mutation ID $sourceId",
                        'data' => $stock->toArray(),
                    ];
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Fix all quantity mismatch between feed stock and purchase
     */
    public function fixQuantityMismatchStocks()
    {
        $fixedCount = 0;
        $stocksWithPurchase = FeedStock::where('source_type', 'purchase')
            ->whereNull('deleted_at')
            ->get();
        foreach ($stocksWithPurchase as $stock) {
            $purchase = FeedPurchase::where('id', $stock->source_id)->whereNull('deleted_at')->first();
            if ($purchase && $stock->quantity_in != $purchase->quantity) {
                $old = $stock->quantity_in;
                $before = $stock->toArray();
                $stock->quantity_in = $purchase->quantity;
                $stock->save();
                $after = $stock->toArray();
                $this->logAudit($stock, 'fix_quantity_mismatch', $before, $after);
                $this->logs[] = [
                    'type' => 'fix_quantity_mismatch',
                    'message' => "Fixed quantity_in for Stock ID {$stock->id} from $old to {$purchase->quantity}",
                    'data' => [
                        'id' => $stock->id,
                        'model_type' => get_class($stock),
                        'stock' => $stock->toArray(),
                        'purchase' => $purchase->toArray(),
                    ],
                ];
                $fixedCount++;
            }
        }
        return [

            'success' => true,
            'logs' => $this->logs,
            'fixed_count' => $fixedCount
        ];
    }

    /**
     * Fix all conversion mismatch on feed purchases
     */
    public function fixConversionMismatchPurchases()
    {
        $fixedCount = 0;
        $purchases = \App\Models\FeedPurchase::whereNull('deleted_at')->get();
        foreach ($purchases as $purchase) {
            $expectedConverted = $purchase->calculateConvertedQuantity();
            if ((float) $purchase->converted_quantity !== (float) $expectedConverted) {
                $old = $purchase->converted_quantity;
                $before = $purchase->toArray();
                $purchase->converted_quantity = $expectedConverted;
                $purchase->save();
                $after = $purchase->toArray();
                $this->logAudit($purchase, 'fix_conversion_mismatch', $before, $after);
                $this->logs[] = [
                    'type' => 'fix_conversion_mismatch',
                    'message' => "Fixed converted_quantity for Purchase ID {$purchase->id} from $old to $expectedConverted",
                    'data' => [
                        'id' => $purchase->id,
                        'model_type' => get_class($purchase),
                        'purchase' => $purchase->toArray(),
                    ],
                ];
                $fixedCount++;
            }
        }
        return [
            'success' => true,
            'logs' => $this->logs,
            'fixed_count' => $fixedCount
        ];
    }

    /**
     * Fix all mutation quantity mismatch on feed stocks
     */
    public function fixMutationQuantityMismatchStocks()
    {
        $fixedCount = 0;
        $mutationStocks = FeedStock::where('source_type', 'mutation')->whereNull('deleted_at')->get();
        foreach ($mutationStocks as $stock) {
            $mutationItems = \App\Models\MutationItem::where('mutation_id', $stock->source_id)
                ->where('item_type', 'feed')
                ->whereNull('deleted_at')
                ->get();
            $totalMutationQty = $mutationItems->sum('quantity');
            if ((float)$stock->quantity_in !== (float)$totalMutationQty) {
                $old = $stock->quantity_in;
                $before = $stock->toArray();
                $stock->quantity_in = $totalMutationQty;
                $stock->save();
                $after = $stock->toArray();
                $this->logAudit($stock, 'fix_mutation_quantity_mismatch', $before, $after);
                $this->logs[] = [
                    'type' => 'fix_mutation_quantity_mismatch',
                    'message' => "Fixed quantity_in for Stock ID {$stock->id} from $old to $totalMutationQty",
                    'data' => [
                        'id' => $stock->id,
                        'model_type' => get_class($stock),
                        'stock' => $stock->toArray(),
                        'mutation_items' => $mutationItems->toArray(),
                    ],
                ];
                $fixedCount++;
            }
        }
        return [
            'success' => true,
            'logs' => $this->logs,
            'fixed_count' => $fixedCount
        ];
    }

    /**
     * Get preview of changes before fixing
     */
    public function previewChanges()
    {
        $previewData = [];

        // Preview quantity mismatch fixes
        $stocksWithPurchase = FeedStock::where('source_type', 'purchase')
            ->whereNull('deleted_at')
            ->get();
        foreach ($stocksWithPurchase as $stock) {
            $purchase = FeedPurchase::where('id', $stock->source_id)->whereNull('deleted_at')->first();
            if ($purchase && $stock->quantity_in != $purchase->quantity) {
                $previewData[] = [
                    'type' => 'quantity_mismatch',
                    'id' => $stock->id,
                    'before' => [
                        'quantity_in' => $stock->quantity_in,
                        'purchase_quantity' => $purchase->quantity,
                    ],
                    'after' => [
                        'quantity_in' => $purchase->quantity,
                        'purchase_quantity' => $purchase->quantity,
                    ],
                    'message' => "Stock ID {$stock->id}: quantity_in will be updated from {$stock->quantity_in} to {$purchase->quantity}"
                ];
            }
        }

        // Preview conversion mismatch fixes
        $purchases = FeedPurchase::whereNull('deleted_at')->get();
        foreach ($purchases as $purchase) {
            $expectedConverted = $purchase->calculateConvertedQuantity();
            if ((float) $purchase->converted_quantity !== (float) $expectedConverted) {
                $previewData[] = [
                    'type' => 'conversion_mismatch',
                    'id' => $purchase->id,
                    'before' => [
                        'converted_quantity' => $purchase->converted_quantity,
                        'expected' => $expectedConverted,
                    ],
                    'after' => [
                        'converted_quantity' => $expectedConverted,
                        'expected' => $expectedConverted,
                    ],
                    'message' => "Purchase ID {$purchase->id}: converted_quantity will be updated from {$purchase->converted_quantity} to {$expectedConverted}"
                ];
            }
        }

        // Preview mutation quantity mismatch fixes
        $mutationStocks = FeedStock::where('source_type', 'mutation')->whereNull('deleted_at')->get();
        foreach ($mutationStocks as $stock) {
            $mutationItems = MutationItem::where('mutation_id', $stock->source_id)
                ->where('item_type', 'feed')
                ->whereNull('deleted_at')
                ->get();
            $totalMutationQty = $mutationItems->sum('quantity');
            if ((float)$stock->quantity_in !== (float)$totalMutationQty) {
                $previewData[] = [
                    'type' => 'mutation_quantity_mismatch',
                    'id' => $stock->id,
                    'before' => [
                        'quantity_in' => $stock->quantity_in,
                        'total_mutation_qty' => $totalMutationQty,
                    ],
                    'after' => [
                        'quantity_in' => $totalMutationQty,
                        'total_mutation_qty' => $totalMutationQty,
                    ],
                    'message' => "Stock ID {$stock->id}: quantity_in will be updated from {$stock->quantity_in} to {$totalMutationQty}"
                ];
            }
        }

        return [
            'success' => true,
            'preview_data' => $previewData,
            'total_changes' => count($previewData)
        ];
    }

    /**
     * Simpan audit trail setiap kali ada perubahan data
     */
    protected function logAudit($model, $action, $before, $after, $rollbackToId = null)
    {
        DataAuditTrail::create([
            'id' => Str::uuid(),
            'model_type' => get_class($model),
            'model_id' => $model->id,
            'action' => $action,
            'before_data' => $before,
            'after_data' => $after,
            'user_id' => auth()->id(),
            'rollback_to_id' => $rollbackToId,
        ]);
    }

    /**
     * Rollback ke versi sebelumnya berdasarkan audit trail
     */
    public function rollback($auditId)
    {
        $audit = DataAuditTrail::findOrFail($auditId);
        $modelClass = $audit->model_type;
        $model = $modelClass::findOrFail($audit->model_id);
        $before = $audit->before_data;
        $after = $model->toArray();
        $model->fill($before);
        $model->save();
        // Simpan audit rollback
        $this->logAudit($model, 'rollback', $after, $before, $auditId);
        $this->logs[] = [
            'type' => 'rollback',
            'message' => "Rollback data {$audit->model_type} ID {$audit->model_id} ke versi sebelumnya.",
            'data' => $before,
        ];
        return true;
    }

    public function getLogs()
    {
        return $this->logs;
    }

    /**
     * Backup seluruh data utama ke file storage/app/feed-backups/
     */
    public function backupToStorage($type = 'manual', $description = null)
    {
        $dir = 'feed-backups';
        if (!Storage::exists($dir)) {
            Storage::makeDirectory($dir);
        }
        $timestamp = now()->format('Ymd-His');
        $user = Auth::user()?->name ?? 'system';
        $filename = "{$dir}/feed-backup_{$timestamp}_{$user}_{$type}.json";
        $data = [
            'meta' => [
                'created_at' => now()->toDateTimeString(),
                'user' => $user,
                'type' => $type,
                'description' => $description,
            ],
            'feed_stocks' => \App\Models\FeedStock::withTrashed()->get()->toArray(),
            'feed_purchases' => \App\Models\FeedPurchase::withTrashed()->get()->toArray(),
            'mutations' => \App\Models\Mutation::withTrashed()->get()->toArray(),
            'mutation_items' => \App\Models\MutationItem::withTrashed()->get()->toArray(),
        ];
        Storage::put($filename, json_encode($data, JSON_PRETTY_PRINT));
        return $filename;
    }

    /**
     * Restore seluruh data utama dari file backup JSON di storage/app/feed-backups/
     */
    public function restoreFromBackup($filename)
    {
        $dir = 'feed-backups';
        $path = "$dir/$filename";
        if (!Storage::exists($path)) {
            throw new \Exception("Backup file not found: $filename");
        }
        $json = Storage::get($path);
        $data = json_decode($json, true);
        // Restore: hapus semua data lama, lalu insert dari backup
        DB::transaction(function () use ($data) {
            \App\Models\FeedStock::truncate();
            \App\Models\FeedPurchase::truncate();
            \App\Models\Mutation::truncate();
            \App\Models\MutationItem::truncate();
            \App\Models\FeedStock::insert($data['feed_stocks'] ?? []);
            \App\Models\FeedPurchase::insert($data['feed_purchases'] ?? []);
            \App\Models\Mutation::insert($data['mutations'] ?? []);
            \App\Models\MutationItem::insert($data['mutation_items'] ?? []);
        });
        return true;
    }
}

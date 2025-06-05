<?php

namespace App\Services;

use App\Models\SupplyStock;
use App\Models\Mutation;
use App\Models\MutationItem;
use App\Models\SupplyPurchase;
use App\Models\SupplyUsage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\DataAuditTrail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class SupplyDataIntegrityService
{
    protected $logs = [];
    protected $version = '1.1.0';

    public function previewInvalidSupplyData()
    {
        try {
            $this->logs = [];
            $this->logs[] = [
                'type' => 'info',
                'message' => 'SupplyDataIntegrityService version ' . $this->version . ' - ' . now(),
                'data' => [],
            ];

            // Find invalid supply stocks
            $invalidStocks = SupplyStock::where(function ($query) {
                $query->where('source_type', 'mutation')
                    ->where(function ($q) {
                        $q->whereNotExists(function ($subQuery) {
                            $subQuery->select(DB::raw(1))
                                ->from('mutations')
                                ->whereColumn('mutations.id', 'supply_stocks.source_id')
                                ->whereNull('mutations.deleted_at');
                        })
                            ->orWhereNotExists(function ($subQuery) {
                                $subQuery->select(DB::raw(1))
                                    ->from('mutation_items')
                                    ->whereColumn('mutation_items.mutation_id', 'supply_stocks.source_id')
                                    ->where('mutation_items.item_type', 'supply')
                                    ->whereNull('mutation_items.deleted_at');
                            });
                    });
            })
                ->orWhere(function ($query) {
                    $query->where('source_type', 'purchase')
                        ->whereNotExists(function ($subQuery) {
                            $subQuery->select(DB::raw(1))
                                ->from('supply_purchases')
                                ->whereColumn('supply_purchases.id', 'supply_stocks.source_id')
                                ->whereNull('supply_purchases.deleted_at');
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
                        ->where('item_type', 'supply')
                        ->whereNull('deleted_at')
                        ->exists();

                    if (!$mutationItemExists) {
                        $reasons[] = "No mutation items found for mutation ID {$stock->source_id}";
                    }
                } elseif ($stock->source_type === 'purchase') {
                    // Check purchase existence
                    $purchaseExists = DB::table('supply_purchases')
                        ->where('id', $stock->source_id)
                        ->whereNull('deleted_at')
                        ->exists();

                    if (!$purchaseExists) {
                        $reasons[] = "Supply purchase record with ID {$stock->source_id} does not exist or has been deleted";
                    }
                }

                $this->logs[] = [
                    'type' => 'invalid_stock',
                    'message' => "Found invalid supply stock: ID {$stock->id}, Source: {$stock->source_type}, Source ID: {$stock->source_id}",
                    'data' => $stock->toArray(),
                    'reasons' => $reasons
                ];
            }

            // --- Cek purchase tanpa stock ---
            $purchasesWithoutStock = SupplyPurchase::whereNull('deleted_at')
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('supply_stocks')
                        ->whereColumn('supply_stocks.source_id', 'supply_purchases.id')
                        ->where('supply_stocks.source_type', 'purchase')
                        ->whereNull('supply_stocks.deleted_at');
                })
                ->get();
            foreach ($purchasesWithoutStock as $purchase) {
                $this->logs[] = [
                    'type' => 'missing_stock',
                    'message' => "Purchase record with ID {$purchase->id} does not have a corresponding supply stock.",
                    'data' => $purchase->toArray(),
                    'reasons' => ["No supply stock found for this purchase record."]
                ];
            }

            // --- Cek mutation tanpa stock ---
            $mutationsWithoutStock = Mutation::whereNull('deleted_at')
                ->where('type', 'supply')
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('supply_stocks')
                        ->whereColumn('supply_stocks.source_id', 'mutations.id')
                        ->where('supply_stocks.source_type', 'mutation')
                        ->whereNull('supply_stocks.deleted_at');
                })
                ->get();
            foreach ($mutationsWithoutStock as $mutation) {
                $this->logs[] = [
                    'type' => 'missing_stock',
                    'message' => "Mutation record with ID {$mutation->id} does not have a corresponding supply stock.",
                    'data' => $mutation->toArray(),
                    'reasons' => ["No supply stock found for this mutation record."]
                ];
            }

            // --- Cek quantity mismatch antara supply stock dan purchase ---
            $stocksWithPurchase = SupplyStock::where('source_type', 'purchase')
                ->whereNull('deleted_at')
                ->get();
            foreach ($stocksWithPurchase as $stock) {
                $purchase = SupplyPurchase::where('id', $stock->source_id)->whereNull('deleted_at')->first();
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

            // --- Cek conversion mismatch pada supply purchase ---
            $purchases = SupplyPurchase::whereNull('deleted_at')->get();
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

            // --- Validasi SupplyStock source_type=mutation: cek quantity_in ---
            $mutationStocks = SupplyStock::where('source_type', 'mutation')->whereNull('deleted_at')->get();
            foreach ($mutationStocks as $stock) {
                $mutationItems = \App\Models\MutationItem::where('mutation_id', $stock->source_id)
                    ->where('item_type', 'supply')
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
            // --- Validasi MutationItem: stock_id harus ada di supply_stocks ---
            $mutationItems = \App\Models\MutationItem::where('item_type', 'supply')->whereNull('deleted_at')->get();
            foreach ($mutationItems as $item) {
                $stockExists = SupplyStock::where('id', $item->stock_id)->whereNull('deleted_at')->exists();
                if (!$stockExists) {
                    $this->logs[] = [
                        'type' => 'mutation_item_invalid_stock',
                        'message' => "MutationItem ID {$item->id} refers to non-existent or deleted stock_id {$item->stock_id}",
                        'data' => $item->toArray(),
                        'reasons' => [
                            "MutationItem stock_id ({$item->stock_id}) does not exist in supply_stocks or has been deleted."
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
            $batches = \App\Models\SupplyPurchaseBatch::whereNull('deleted_at')->get();
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
            // 3. Validasi unit conversion pada setiap SupplyPurchase
            $purchases = \App\Models\SupplyPurchase::whereNull('deleted_at')->get();
            foreach ($purchases as $purchase) {
                if ($purchase->unit_id && $purchase->converted_unit) {
                    $conversion = \App\Models\UnitConversion::where('unit_id', $purchase->unit_id)
                        ->where('conversion_unit_id', $purchase->converted_unit)
                        ->where('status', 'active')
                        ->first();
                    if (!$conversion) {
                        $this->logs[] = [
                            'type' => 'unit_conversion_invalid',
                            'message' => "SupplyPurchase ID {$purchase->id} has invalid or missing unit conversion.",
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
                            'message' => "SupplyPurchase ID {$purchase->id} price_per_unit ({$purchase->price_per_unit}) does not match batch price ({$expectedPrice})",
                            'data' => $purchase->toArray(),
                            'reasons' => ["Harga per unit tidak sesuai dengan harga batch."]
                        ];
                    }
                }
            }
            // 5. Validasi farm aktif
            $farms = \App\Models\Farm::all()->keyBy('id');
            foreach ($purchases as $purchase) {
                $farm = $farms[$purchase->farm_id] ?? null;
                if (!$farm || (isset($farm->status) && strtolower($farm->status) !== 'active')) {
                    $this->logs[] = [
                        'type' => 'farm_inactive',
                        'message' => "SupplyPurchase ID {$purchase->id} mengarah ke farm tidak aktif atau tidak ditemukan.",
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
            Log::error('Error in SupplyDataIntegrityService preview: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'logs' => $this->logs
            ];
        }
    }

    public function checkAndFixInvalidSupplyData()
    {
        DB::beginTransaction();
        try {
            $this->logs = [];

            // Find invalid supply stocks
            $invalidStocks = SupplyStock::where(function ($query) {
                $query->where('source_type', 'mutation')
                    ->where(function ($q) {
                        $q->whereNotExists(function ($subQuery) {
                            $subQuery->select(DB::raw(1))
                                ->from('mutations')
                                ->whereColumn('mutations.id', 'supply_stocks.source_id')
                                ->whereNull('mutations.deleted_at');
                        })
                            ->orWhereNotExists(function ($subQuery) {
                                $subQuery->select(DB::raw(1))
                                    ->from('mutation_items')
                                    ->whereColumn('mutation_items.mutation_id', 'supply_stocks.source_id')
                                    ->where('mutation_items.item_type', 'supply')
                                    ->whereNull('mutation_items.deleted_at');
                            });
                    });
            })
                ->orWhere(function ($query) {
                    $query->where('source_type', 'purchase')
                        ->whereNotExists(function ($subQuery) {
                            $subQuery->select(DB::raw(1))
                                ->from('supply_purchases')
                                ->whereColumn('supply_purchases.id', 'supply_stocks.source_id')
                                ->whereNull('supply_purchases.deleted_at');
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
                        ->where('item_type', 'supply')
                        ->whereNull('deleted_at')
                        ->exists();

                    if (!$mutationItemExists) {
                        $reasons[] = "No mutation items found for mutation ID {$stock->source_id}";
                    }
                } elseif ($stock->source_type === 'purchase') {
                    // Check purchase existence
                    $purchaseExists = DB::table('supply_purchases')
                        ->where('id', $stock->source_id)
                        ->whereNull('deleted_at')
                        ->exists();

                    if (!$purchaseExists) {
                        $reasons[] = "Supply purchase record with ID {$stock->source_id} does not exist or has been deleted";
                    }
                }

                $this->logs[] = [
                    'type' => 'invalid_stock',
                    'message' => "Found invalid supply stock: ID {$stock->id}, Source: {$stock->source_type}, Source ID: {$stock->source_id}",
                    'data' => $stock->toArray(),
                    'reasons' => $reasons
                ];

                // Delete invalid stock
                $stock->delete();
                $this->logs[] = [
                    'type' => 'deleted_stock',
                    'message' => "Deleted invalid supply stock: ID {$stock->id}",
                    'data' => $stock->toArray()
                ];

                // Recalculate stock for the farm
                $this->recalculateFarmStock($stock->farm_id, $stock->supply_id);
            }

            DB::commit();
            return [
                'success' => true,
                'logs' => $this->logs,
                'deleted_stocks_count' => $invalidStocks->count()
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in SupplyDataIntegrityService: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'logs' => $this->logs
            ];
        }
    }

    protected function recalculateFarmStock($farmId, $supplyId)
    {
        // Get all purchases for this supply and farm
        $purchases = SupplyPurchase::where('farm_id', $farmId)
            ->where('supply_id', $supplyId)
            ->sum('quantity');

        // Get all usages for this supply and farm
        $usages = DB::table('supply_usage_details')
            ->join('supply_usages', 'supply_usage_details.supply_usage_id', '=', 'supply_usages.id')
            ->where('supply_usages.livestock_id', $farmId)
            ->where('supply_usage_details.supply_id', $supplyId)
            // ->where('supply_usages.status', 'approved')
            ->sum('supply_usage_details.quantity_taken');

        // Get all valid mutations (incoming)
        $incomingMutations = SupplyStock::where('farm_id', $farmId)
            ->where('supply_id', $supplyId)
            ->where('source_type', 'mutation')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('mutations')
                    ->whereColumn('mutations.id', 'supply_stocks.source_id');
                // ->where('mutations.status', 'approved');
            })
            ->sum('quantity_in');

        // Get all valid mutations (outgoing)
        $outgoingMutations = SupplyStock::where('farm_id', $farmId)
            ->where('supply_id', $supplyId)
            ->where('source_type', 'mutation')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('mutations')
                    ->whereColumn('mutations.id', 'supply_stocks.source_id');
                // ->where('mutations.status', 'approved');
            })
            ->sum('quantity_mutated');

        // Calculate current stock
        $currentStock = $purchases + $incomingMutations - $usages - $outgoingMutations;

        // Update current supply record
        $currentSupply = \App\Models\CurrentSupply::updateOrCreate(
            [
                'farm_id' => $farmId,
                'item_id' => $supplyId,
                'type' => 'supply'
            ],
            [
                'quantity' => $currentStock,
                'updated_by' => auth()->id()
            ]
        );

        $this->logs[] = [
            'type' => 'recalculation',
            'message' => "Recalculated stock for Farm ID: {$farmId}, Supply ID: {$supplyId}",
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
            $purchase = SupplyPurchase::withTrashed()->find($sourceId);
            if ($purchase && $purchase->deleted_at) {
                $purchase->restore();
                $this->logs[] = [
                    'type' => 'restore',
                    'message' => "Restored supply purchase with ID $sourceId",
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
            $purchase = SupplyPurchase::withTrashed()->find($sourceId);
            return $purchase && $purchase->deleted_at;
        } elseif ($type === 'mutation') {
            $mutation = Mutation::withTrashed()->find($sourceId);
            return $mutation && $mutation->deleted_at;
        }
        return false;
    }

    /**
     * Restore missing supply stock for purchase or mutation
     */
    public function restoreMissingStock($type, $sourceId)
    {
        if ($type === 'purchase') {
            $purchase = SupplyPurchase::where('id', $sourceId)->whereNull('deleted_at')->first();
            if ($purchase) {
                // Ambil tanggal dari batch
                $batchDate = $purchase->batch ? $purchase->batch->date : now();
                // Cek apakah stock sudah ada (double check)
                $existing = SupplyStock::where('source_type', 'purchase')
                    ->where('source_id', $purchase->id)
                    ->whereNull('deleted_at')
                    ->first();
                if (!$existing) {
                    $stock = SupplyStock::create([
                        'farm_id' => $purchase->farm_id,
                        'supply_id' => $purchase->supply_id,
                        'supply_purchase_id' => $purchase->id,
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
                        'message' => "Restored supply stock for purchase ID $sourceId",
                        'data' => $stock->toArray(),
                    ];
                    return true;
                }
            }
        } elseif ($type === 'mutation') {
            $mutation = Mutation::where('id', $sourceId)->whereNull('deleted_at')->first();
            if ($mutation) {
                $existing = SupplyStock::where('source_type', 'mutation')
                    ->where('source_id', $mutation->id)
                    ->whereNull('deleted_at')
                    ->first();
                if (!$existing) {
                    $stock = SupplyStock::create([
                        'farm_id' => $mutation->farm_id,
                        'supply_id' => $mutation->supply_id,
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
                        'message' => "Restored supply stock for mutation ID $sourceId",
                        'data' => $stock->toArray(),
                    ];
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Fix all quantity mismatch between supply stock and purchase
     */
    public function fixQuantityMismatchStocks()
    {
        $fixedCount = 0;
        $stocksWithPurchase = SupplyStock::where('source_type', 'purchase')
            ->whereNull('deleted_at')
            ->get();
        foreach ($stocksWithPurchase as $stock) {
            $purchase = SupplyPurchase::where('id', $stock->source_id)->whereNull('deleted_at')->first();
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
     * Fix all conversion mismatch on supply purchases
     */
    public function fixConversionMismatchPurchases()
    {
        $fixedCount = 0;
        $purchases = \App\Models\SupplyPurchase::whereNull('deleted_at')->get();
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
     * Fix all mutation quantity mismatch on supply stocks
     */
    public function fixMutationQuantityMismatchStocks()
    {
        $fixedCount = 0;
        $mutationStocks = SupplyStock::where('source_type', 'mutation')->whereNull('deleted_at')->get();
        foreach ($mutationStocks as $stock) {
            $mutationItems = \App\Models\MutationItem::where('mutation_id', $stock->source_id)
                ->where('item_type', 'supply')
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
        $stocksWithPurchase = SupplyStock::where('source_type', 'purchase')
            ->whereNull('deleted_at')
            ->get();
        foreach ($stocksWithPurchase as $stock) {
            $purchase = SupplyPurchase::where('id', $stock->source_id)->whereNull('deleted_at')->first();
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
        $purchases = SupplyPurchase::whereNull('deleted_at')->get();
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
        $mutationStocks = SupplyStock::where('source_type', 'mutation')->whereNull('deleted_at')->get();
        foreach ($mutationStocks as $stock) {
            $mutationItems = MutationItem::where('mutation_id', $stock->source_id)
                ->where('item_type', 'supply')
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
     * Backup seluruh data utama ke file storage/app/supply-backups/
     */
    public function backupToStorage($type = 'manual', $description = null)
    {
        $dir = 'supply-backups';
        if (!Storage::exists($dir)) {
            Storage::makeDirectory($dir);
        }
        $timestamp = now()->format('Ymd-His');
        $user = Auth::user()?->name ?? 'system';
        $filename = "{$dir}/supply-backup_{$timestamp}_{$user}_{$type}.json";
        $data = [
            'meta' => [
                'created_at' => now()->toDateTimeString(),
                'user' => $user,
                'type' => $type,
                'description' => $description,
            ],
            'supply_stocks' => \App\Models\SupplyStock::withTrashed()->get()->toArray(),
            'supply_purchases' => \App\Models\SupplyPurchase::withTrashed()->get()->toArray(),
            'mutations' => \App\Models\Mutation::withTrashed()->get()->toArray(),
            'mutation_items' => \App\Models\MutationItem::withTrashed()->get()->toArray(),
        ];
        Storage::put($filename, json_encode($data, JSON_PRETTY_PRINT));
        return $filename;
    }

    /**
     * Restore seluruh data utama dari file backup JSON di storage/app/supply-backups/
     */
    public function restoreFromBackup($filename)
    {
        $dir = 'supply-backups';
        $path = "$dir/$filename";
        if (!Storage::exists($path)) {
            throw new \Exception("Backup file not found: $filename");
        }
        $json = Storage::get($path);
        $data = json_decode($json, true);
        // Restore: hapus semua data lama, lalu insert dari backup
        DB::transaction(function () use ($data) {
            \App\Models\SupplyStock::truncate();
            \App\Models\SupplyPurchase::truncate();
            \App\Models\Mutation::truncate();
            \App\Models\MutationItem::truncate();
            \App\Models\SupplyStock::insert($data['supply_stocks'] ?? []);
            \App\Models\SupplyPurchase::insert($data['supply_purchases'] ?? []);
            \App\Models\Mutation::insert($data['mutations'] ?? []);
            \App\Models\MutationItem::insert($data['mutation_items'] ?? []);
        });
        return true;
    }
}

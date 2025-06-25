<?php

namespace App\Services;

use App\Models\Livestock;
use App\Models\Mutation;
use App\Models\MutationItem;
use App\Models\LivestockPurchase;
use App\Models\LivestockUsage;
use App\Models\CurrentLivestock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\DataAuditTrail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Models\LivestockBatch;
use App\Models\LivestockPurchaseItem;

/**
 * LivestockDataIntegrityService
 * 
 * Service untuk mengecek dan memperbaiki integritas data livestock
 * termasuk relasi dengan CurrentLivestock
 * 
 * @version 2.0.0
 * @author System
 * @since 2025-01-19
 */
class LivestockDataIntegrityService
{
    protected $logs = [];
    protected $version = '2.0.0';

    public function previewInvalidLivestockData()
    {
        try {
            $this->logs = [];
            $this->logs[] = [
                'type' => 'info',
                'message' => 'LivestockDataIntegrityService version ' . $this->version . ' - ' . now(),
                'data' => [],
            ];

            // Find invalid livestock batches
            $invalidBatches = LivestockBatch::where(function ($query) {
                $query->where('source_type', 'purchase')
                    ->whereNotExists(function ($subQuery) {
                        $subQuery->select(DB::raw(1))
                            ->from('livestock_purchases')
                            ->whereColumn('livestock_purchases.id', 'livestock_batches.source_id')
                            ->whereNull('livestock_purchases.deleted_at');
                    });
            })
                ->orWhere(function ($query) {
                    $query->where('source_type', 'mutation')
                        ->whereNotExists(function ($subQuery) {
                            $subQuery->select(DB::raw(1))
                                ->from('mutations')
                                ->whereColumn('mutations.id', 'livestock_batches.source_id')
                                ->whereNull('mutations.deleted_at');
                        });
                })
                ->get();

            foreach ($invalidBatches as $batch) {
                $reasons = [];

                if ($batch->source_type === 'purchase') {
                    $purchaseExists = DB::table('livestock_purchases')
                        ->where('id', $batch->source_id)
                        ->whereNull('deleted_at')
                        ->exists();

                    if (!$purchaseExists) {
                        $reasons[] = "Livestock purchase record with ID {$batch->source_id} does not exist or has been deleted";
                    }
                } elseif ($batch->source_type === 'mutation') {
                    $mutationExists = DB::table('mutations')
                        ->where('id', $batch->source_id)
                        ->whereNull('deleted_at')
                        ->exists();

                    if (!$mutationExists) {
                        $reasons[] = "Mutation record with ID {$batch->source_id} does not exist or has been deleted";
                    }
                }

                $this->logs[] = [
                    'type' => 'invalid_batch',
                    'message' => "Found invalid livestock batch: ID {$batch->id}, Source: {$batch->source_type}, Source ID: {$batch->source_id}",
                    'data' => $batch->toArray(),
                    'reasons' => $reasons
                ];
            }

            // Check for purchases without batches
            $purchasesWithoutBatch = DB::table('livestock_purchase_items')
                ->join('livestock_purchases', 'livestock_purchase_items.livestock_purchase_id', '=', 'livestock_purchases.id')
                ->whereNull('livestock_purchases.deleted_at')
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('livestock_batches')
                        ->whereColumn('livestock_batches.livestock_purchase_item_id', 'livestock_purchase_items.id')
                        ->whereNull('livestock_batches.deleted_at');
                })
                ->get();

            foreach ($purchasesWithoutBatch as $purchaseItem) {
                $this->logs[] = [
                    'type' => 'missing_batch',
                    'message' => "Purchase item with ID {$purchaseItem->id} does not have a corresponding livestock batch.",
                    'data' => (array) $purchaseItem,
                    'reasons' => ["No livestock batch found for this purchase item."]
                ];
            }

            // Check for batches with empty source_type or source_id
            $batchesWithEmptySource = LivestockBatch::where(function ($query) {
                $query->whereNull('source_type')
                    ->orWhereNull('source_id')
                    ->orWhere('source_type', '');
            })->get();

            foreach ($batchesWithEmptySource as $batch) {
                $this->logs[] = [
                    'type' => 'invalid_batch_source',
                    'message' => "Livestock batch ID {$batch->id} has empty or invalid source information.",
                    'data' => $batch->toArray(),
                    'reasons' => [
                        "Source type is " . ($batch->source_type ?: 'empty'),
                        "Source ID is " . ($batch->source_id ?: 'empty')
                    ]
                ];
            }

            // Check for batches with invalid source_type
            $batchesWithInvalidSourceType = LivestockBatch::whereNotNull('source_type')
                ->whereNotIn('source_type', ['purchase', 'mutation'])
                ->get();

            foreach ($batchesWithInvalidSourceType as $batch) {
                $this->logs[] = [
                    'type' => 'invalid_source_type',
                    'message' => "Livestock batch ID {$batch->id} has invalid source_type: {$batch->source_type}",
                    'data' => $batch->toArray(),
                    'reasons' => ["Source type '{$batch->source_type}' is not valid. Expected values are 'purchase' or 'mutation'."]
                ];
            }

            // Check for batches with mismatched source_id and livestock_purchase_item_id
            $batchesWithMismatchedIds = LivestockBatch::whereNotNull('source_id')
                ->whereNotNull('livestock_purchase_item_id')
                ->where('source_type', 'purchase')
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('livestock_purchase_items')
                        ->whereColumn('livestock_purchase_items.id', 'livestock_batches.livestock_purchase_item_id')
                        ->whereColumn('livestock_purchase_items.livestock_purchase_id', 'livestock_batches.source_id');
                })
                ->get();

            foreach ($batchesWithMismatchedIds as $batch) {
                $this->logs[] = [
                    'type' => 'mismatched_source_ids',
                    'message' => "Livestock batch ID {$batch->id} has mismatched source_id and livestock_purchase_item_id",
                    'data' => $batch->toArray(),
                    'reasons' => [
                        "Source ID ({$batch->source_id}) does not match the livestock_purchase_id associated with livestock_purchase_item_id ({$batch->livestock_purchase_item_id})"
                    ]
                ];
            }

            // Check for mutations without batches
            $mutationsWithoutBatch = Mutation::whereNull('deleted_at')
                ->where('type', 'livestock')
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('livestock_batches')
                        ->whereColumn('livestock_batches.source_id', 'mutations.id')
                        ->where('livestock_batches.source_type', 'mutation')
                        ->whereNull('livestock_batches.deleted_at');
                })
                ->get();

            foreach ($mutationsWithoutBatch as $mutation) {
                $this->logs[] = [
                    'type' => 'missing_batch',
                    'message' => "Mutation record with ID {$mutation->id} does not have a corresponding livestock batch.",
                    'data' => $mutation->toArray(),
                    'reasons' => ["No livestock batch found for this mutation record."]
                ];
            }

            // Check for price data integrity (new columns added to livestock_batches)
            $this->checkPriceDataIntegrity();

            // Check for quantity mismatches
            $batchesWithPurchase = LivestockBatch::where('source_type', 'purchase')
                ->whereNull('deleted_at')
                ->get();

            foreach ($batchesWithPurchase as $batch) {
                $purchase = LivestockPurchase::where('id', $batch->source_id)->whereNull('deleted_at')->first();
                if ($purchase && $batch->quantity != $purchase->quantity) {
                    $this->logs[] = [
                        'type' => 'quantity_mismatch',
                        'message' => "Quantity mismatch: Batch ID {$batch->id} (quantity: {$batch->quantity}) vs Purchase ID {$purchase->id} (quantity: {$purchase->quantity})",
                        'data' => [
                            'batch' => $batch->toArray(),
                            'purchase' => $purchase->toArray(),
                        ],
                        'reasons' => [
                            "Batch quantity ({$batch->quantity}) does not match purchase quantity ({$purchase->quantity})"
                        ]
                    ];
                }
            }

            // Check for missing CurrentLivestock records
            $livestocksWithoutCurrent = Livestock::whereDoesntHave('currentLivestock')
                ->whereNull('deleted_at')
                ->get();

            foreach ($livestocksWithoutCurrent as $livestock) {
                $this->logs[] = [
                    'type' => 'missing_current_livestock',
                    'message' => "Livestock ID {$livestock->id} does not have a corresponding CurrentLivestock record.",
                    'data' => $livestock->toArray(),
                    'reasons' => ["No CurrentLivestock record found for this livestock."]
                ];
            }

            // Check for orphaned CurrentLivestock records
            $orphanedCurrentLivestock = CurrentLivestock::whereDoesntHave('livestock')
                ->get();

            foreach ($orphanedCurrentLivestock as $current) {
                $this->logs[] = [
                    'type' => 'orphaned_current_livestock',
                    'message' => "CurrentLivestock ID {$current->id} references non-existent livestock ID {$current->livestock_id}.",
                    'data' => $current->toArray(),
                    'reasons' => ["CurrentLivestock record references deleted or non-existent livestock."]
                ];
            }

            return [
                'success' => true,
                'logs' => $this->logs,
                'invalid_batches_count' => $invalidBatches->count(),
                'invalid_stocks_count' => $invalidBatches->count(),
                'missing_current_livestock_count' => $livestocksWithoutCurrent->count()
            ];
        } catch (\Exception $e) {
            Log::error('Error in LivestockDataIntegrityService preview: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'logs' => $this->logs,
                'invalid_batches_count' => 0,
                'invalid_stocks_count' => 0,
                'missing_current_livestock_count' => 0
            ];
        }
    }

    public function checkAndFixInvalidLivestockData()
    {
        DB::beginTransaction();
        try {
            $this->logs = [];

            // Fix batches with empty source information
            $this->fixEmptySourceBatches();

            // Find and fix invalid batches
            $invalidBatches = LivestockBatch::where(function ($query) {
                $query->where('source_type', 'purchase')
                    ->whereNotExists(function ($subQuery) {
                        $subQuery->select(DB::raw(1))
                            ->from('livestock_purchases')
                            ->whereColumn('livestock_purchases.id', 'livestock_batches.source_id')
                            ->whereNull('livestock_purchases.deleted_at');
                    });
            })
                ->orWhere(function ($query) {
                    $query->where('source_type', 'mutation')
                        ->whereNotExists(function ($subQuery) {
                            $subQuery->select(DB::raw(1))
                                ->from('mutations')
                                ->whereColumn('mutations.id', 'livestock_batches.source_id')
                                ->whereNull('mutations.deleted_at');
                        });
                })
                ->get();

            foreach ($invalidBatches as $batch) {
                $this->logs[] = [
                    'type' => 'invalid_batch',
                    'message' => "Found invalid livestock batch: ID {$batch->id}, Source: {$batch->source_type}, Source ID: {$batch->source_id}",
                    'data' => $batch->toArray()
                ];

                // Delete invalid batch
                $batch->delete();
                $this->logs[] = [
                    'type' => 'deleted_batch',
                    'message' => "Deleted invalid livestock batch: ID {$batch->id}",
                    'data' => $batch->toArray()
                ];

                // Recalculate livestock totals
                $this->recalculateLivestockTotals($batch->livestock_id);
            }

            DB::commit();
            return [
                'success' => true,
                'logs' => $this->logs,
                'deleted_batches_count' => $invalidBatches->count()
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in LivestockDataIntegrityService: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'logs' => $this->logs
            ];
        }
    }

    /**
     * Fix batches with empty source_type or source_id
     */
    public function fixEmptySourceBatches()
    {
        $batchesWithEmptySource = LivestockBatch::where(function ($query) {
            $query->whereNull('source_type')
                ->orWhereNull('source_id')
                ->orWhere('source_type', '');
        })->get();

        foreach ($batchesWithEmptySource as $batch) {
            $fixed = false;
            // Try from purchase item
            if ($batch->livestock_purchase_item_id) {
                $purchaseItem = DB::table('livestock_purchase_items')
                    ->join('livestock_purchases', 'livestock_purchase_items.livestock_purchase_id', '=', 'livestock_purchases.id')
                    ->where('livestock_purchase_items.id', $batch->livestock_purchase_item_id)
                    ->whereNull('livestock_purchases.deleted_at')
                    ->first();
                if ($purchaseItem) {
                    $batch->source_type = 'purchase';
                    $batch->source_id = $purchaseItem->livestock_purchase_id;
                    $batch->save();
                    $fixed = true;
                }
            }
            // Try from mutation if batch has mutation_id
            if (!$fixed && isset($batch->mutation_id) && $batch->mutation_id) {
                $mutation = Mutation::where('id', $batch->mutation_id)->whereNull('deleted_at')->first();
                if ($mutation) {
                    $batch->source_type = 'mutation';
                    $batch->source_id = $mutation->id;
                    $batch->save();
                    $fixed = true;
                }
            }
            // If cannot fix, log for manual fix
            if (!$fixed) {
                $this->logs[] = [
                    'type' => 'cannot_fix_empty_source',
                    'message' => "Batch ID {$batch->id} tidak bisa diperbaiki otomatis. Silakan cek data sumber (purchase/mutation) di admin.",
                    'data' => $batch->toArray(),
                ];
            }
        }
    }

    protected function recalculateLivestockTotals($livestockId)
    {
        $livestock = Livestock::findOrFail($livestockId);

        // Get all valid batches for this livestock
        $batches = LivestockBatch::where('livestock_id', $livestockId)
            ->whereNull('deleted_at')
            ->get();

        $totalQuantity = $batches->sum('quantity');
        $totalWeight = $batches->sum(function ($batch) {
            return $batch->quantity * $batch->weight;
        });
        $avgWeight = $totalQuantity > 0 ? $totalWeight / $totalQuantity : 0;

        // Update livestock record
        $livestock->update([
            'quantity' => $totalQuantity,
            'weight' => $avgWeight,
            'updated_by' => auth()->id()
        ]);

        // Update or create CurrentLivestock record
        $currentLivestock = CurrentLivestock::updateOrCreate(
            [
                'livestock_id' => $livestockId,
            ],
            [
                'farm_id' => $livestock->farm_id,
                'coop_id' => $livestock->coop_id,
                'quantity' => $totalQuantity,
                'weight_total' => $totalWeight,
                'weight_avg' => $avgWeight,
                'status' => 'active',
                'updated_by' => auth()->id(),
            ]
        );

        $this->logs[] = [
            'type' => 'recalculation',
            'message' => "Recalculated totals for Livestock ID: {$livestockId}",
            'data' => [
                'total_quantity' => $totalQuantity,
                'total_weight' => $totalWeight,
                'avg_weight' => $avgWeight,
                'current_livestock_updated' => true,
                'current_livestock_id' => $currentLivestock->id
            ]
        ];
    }

    /**
     * Restore related record (purchase/mutation) if soft deleted
     */
    public function restoreRelatedRecord($type, $sourceId)
    {
        if ($type === 'purchase') {
            $purchase = LivestockPurchase::withTrashed()->find($sourceId);
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
            $mutation = Mutation::withTrashed()->where('type', 'livestock')->find($sourceId);
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
            $purchase = LivestockPurchase::withTrashed()->find($sourceId);
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
            $purchase = LivestockPurchase::where('id', $sourceId)->whereNull('deleted_at')->first();
            if ($purchase) {
                // Ambil tanggal dari batch
                $batchDate = $purchase->batch ? $purchase->batch->date : now();
                // Cek apakah stock sudah ada (double check)
                $existing = Livestock::where('source_type', 'purchase')
                    ->where('source_id', $purchase->id)
                    ->whereNull('deleted_at')
                    ->first();
                if (!$existing) {
                    $stock = Livestock::create([
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
                $existing = Livestock::where('source_type', 'mutation')
                    ->where('source_id', $mutation->id)
                    ->whereNull('deleted_at')
                    ->first();
                if (!$existing) {
                    $stock = Livestock::create([
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
        $batchesWithPurchase = LivestockBatch::where('source_type', 'purchase')
            ->whereNull('deleted_at')
            ->get();

        foreach ($batchesWithPurchase as $batch) {
            $purchase = LivestockPurchase::where('id', $batch->source_id)->whereNull('deleted_at')->first();
            if ($purchase && $batch->quantity != $purchase->quantity) {
                $old = $batch->quantity;
                $before = $batch->toArray();
                $batch->quantity = $purchase->quantity;
                $batch->save();
                $after = $batch->toArray();
                $this->logAudit($batch, 'fix_quantity_mismatch', $before, $after);
                $this->logs[] = [
                    'type' => 'fix_quantity_mismatch',
                    'message' => "Fixed quantity for Batch ID {$batch->id} from $old to {$purchase->quantity}",
                    'data' => [
                        'id' => $batch->id,
                        'model_type' => get_class($batch),
                        'batch' => $batch->toArray(),
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
        $purchases = \App\Models\LivestockPurchase::whereNull('deleted_at')->get();
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
        $batchesWithMutation = LivestockBatch::where('source_type', 'mutation')
            ->whereNull('deleted_at')
            ->get();

        foreach ($batchesWithMutation as $batch) {
            $mutation = Mutation::where('id', $batch->source_id)->whereNull('deleted_at')->first();
            if ($mutation && $batch->quantity != $mutation->quantity) {
                $old = $batch->quantity;
                $before = $batch->toArray();
                $batch->quantity = $mutation->quantity;
                $batch->save();
                $after = $batch->toArray();
                $this->logAudit($batch, 'fix_mutation_quantity_mismatch', $before, $after);
                $this->logs[] = [
                    'type' => 'fix_mutation_quantity_mismatch',
                    'message' => "Fixed quantity for Batch ID {$batch->id} from $old to {$mutation->quantity}",
                    'data' => [
                        'id' => $batch->id,
                        'model_type' => get_class($batch),
                        'batch' => $batch->toArray(),
                        'mutation' => $mutation->toArray(),
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
        $batchesWithPurchase = LivestockBatch::where('source_type', 'purchase')
            ->whereNull('deleted_at')
            ->get();

        foreach ($batchesWithPurchase as $batch) {
            $purchase = LivestockPurchase::where('id', $batch->source_id)->whereNull('deleted_at')->first();
            if ($purchase && $batch->quantity != $purchase->quantity) {
                $previewData[] = [
                    'type' => 'quantity_mismatch',
                    'id' => $batch->id,
                    'before' => [
                        'quantity' => $batch->quantity,
                        'purchase_quantity' => $purchase->quantity,
                    ],
                    'after' => [
                        'quantity' => $purchase->quantity,
                        'purchase_quantity' => $purchase->quantity,
                    ],
                    'message' => "Batch ID {$batch->id}: quantity will be updated from {$batch->quantity} to {$purchase->quantity}"
                ];
            }
        }

        // Preview empty source_type/source_id fixes
        $batchesWithEmptySource = LivestockBatch::where(function ($query) {
            $query->whereNull('source_type')
                ->orWhereNull('source_id')
                ->orWhere('source_type', '');
        })->get();
        foreach ($batchesWithEmptySource as $batch) {
            $previewData[] = [
                'type' => 'empty_source',
                'id' => $batch->id,
                'before' => [
                    'source_type' => $batch->source_type,
                    'source_id' => $batch->source_id,
                    'batch_number' => $batch->batch_number ?? null,
                    'livestock_id' => $batch->livestock_id,
                    'livestock_name' => optional($batch->livestock)->name ?? null,
                    'mutation_id' => $batch->mutation_id ?? null,
                    'livestock_purchase_item_id' => $batch->livestock_purchase_item_id ?? null,
                    'created_at' => $batch->created_at,
                    'updated_at' => $batch->updated_at,
                ],
                'after' => [
                    'source_type' => 'purchase/mutation (auto or manual fix)',
                    'source_id' => 'auto/manual',
                ],
                'message' => "Batch ID {$batch->id}: source_type/source_id is empty and will be attempted to fix from purchase item or mutation.",
                'details' => [
                    'batch_number' => $batch->batch_number ?? null,
                    'livestock_id' => $batch->livestock_id,
                    'livestock_name' => optional($batch->livestock)->name ?? null,
                    'mutation_id' => $batch->mutation_id ?? null,
                    'livestock_purchase_item_id' => $batch->livestock_purchase_item_id ?? null,
                    'created_at' => $batch->created_at,
                    'updated_at' => $batch->updated_at,
                ]
            ];
        }

        // Preview conversion mismatch fixes
        $purchases = LivestockPurchase::whereNull('deleted_at')->get();
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
        $batchesWithMutation = LivestockBatch::where('source_type', 'mutation')
            ->whereNull('deleted_at')
            ->get();

        foreach ($batchesWithMutation as $batch) {
            $mutation = Mutation::where('id', $batch->source_id)->whereNull('deleted_at')->first();
            if ($mutation && $batch->quantity != $mutation->quantity) {
                $previewData[] = [
                    'type' => 'mutation_quantity_mismatch',
                    'id' => $batch->id,
                    'before' => [
                        'quantity' => $batch->quantity,
                        'mutation_quantity' => $mutation->quantity,
                    ],
                    'after' => [
                        'quantity' => $mutation->quantity,
                        'mutation_quantity' => $mutation->quantity,
                    ],
                    'message' => "Batch ID {$batch->id}: quantity will be updated from {$batch->quantity} to {$mutation->quantity}"
                ];
            }
        }

        // Preview missing CurrentLivestock fixes
        $livestocksWithoutCurrent = Livestock::whereDoesntHave('currentLivestock')
            ->whereNull('deleted_at')
            ->get();

        foreach ($livestocksWithoutCurrent as $livestock) {
            $totalQuantity = LivestockBatch::where('livestock_id', $livestock->id)
                ->whereNull('deleted_at')
                ->sum('initial_quantity') ?? 0;

            $previewData[] = [
                'type' => 'missing_current_livestock',
                'id' => $livestock->id,
                'before' => [
                    'current_livestock_exists' => false,
                    'livestock_name' => $livestock->name ?? 'Unknown',
                ],
                'after' => [
                    'current_livestock_exists' => true,
                    'calculated_quantity' => $totalQuantity,
                ],
                'message' => "Livestock ID {$livestock->id}: CurrentLivestock record will be created with quantity {$totalQuantity}"
            ];
        }

        // Preview orphaned CurrentLivestock removal
        $orphanedCurrentLivestock = CurrentLivestock::whereDoesntHave('livestock')->get();

        foreach ($orphanedCurrentLivestock as $current) {
            $previewData[] = [
                'type' => 'orphaned_current_livestock',
                'id' => $current->id,
                'before' => [
                    'livestock_id' => $current->livestock_id,
                    'quantity' => $current->quantity,
                    'exists' => true,
                ],
                'after' => [
                    'exists' => false,
                    'action' => 'deleted',
                ],
                'message' => "CurrentLivestock ID {$current->id}: orphaned record will be removed"
            ];
        }

        return [
            'success' => true,
            'preview_data' => $previewData,
            'total_changes' => count($previewData)
        ];
    }

    /**
     * Preview changes specifically for CurrentLivestock records
     */
    public function previewCurrentLivestockChanges()
    {
        $preview = [];

        try {
            Log::info('Starting CurrentLivestock changes preview');

            // Preview missing CurrentLivestock records that would be created
            $livestocksWithoutCurrent = Livestock::whereDoesntHave('currentLivestock')
                ->whereNull('deleted_at')
                ->with(['farm', 'coop'])
                ->get();

            Log::info('Found livestock without CurrentLivestock for preview', ['count' => $livestocksWithoutCurrent->count()]);

            foreach ($livestocksWithoutCurrent as $livestock) {
                // Calculate what would be the values - menggunakan logika yang sama seperti fix
                $batches = LivestockBatch::where('livestock_id', $livestock->id)
                    ->whereNull('deleted_at')
                    ->get();

                $totalQuantity = $batches->sum('initial_quantity') ?? 0;
                $totalWeightSum = $batches->sum(function ($batch) {
                    return ($batch->initial_quantity ?? 0) * ($batch->weight ?? 0);
                }) ?? 0;
                $avgWeight = $totalQuantity > 0 ? $totalWeightSum / $totalQuantity : 0;

                $preview[] = [
                    'type' => 'create_current_livestock',
                    'action' => 'CREATE',
                    'model' => 'CurrentLivestock',
                    'livestock_id' => $livestock->id,
                    'livestock_name' => $livestock->name ?? 'Unknown',
                    'farm_name' => $livestock->farm->name ?? 'Unknown',
                    'coop_name' => $livestock->coop->name ?? 'Unknown',
                    'changes' => [
                        'before' => [
                            'status' => 'missing',
                            'quantity' => 0,
                            'weight_total' => 0,
                            'weight_avg' => 0
                        ],
                        'after' => [
                            'status' => 'active',
                            'quantity' => $totalQuantity,
                            'weight_total' => round($totalWeightSum, 2),
                            'weight_avg' => round($avgWeight, 2)
                        ]
                    ],
                    'description' => "Will create CurrentLivestock record with calculated totals from {$batches->count()} batches",
                    'impact' => 'low' // Creating missing data
                ];
            }

            // dd($preview);

            // Preview orphaned CurrentLivestock records that would be removed
            $orphanedCurrentLivestock = CurrentLivestock::whereDoesntHave('livestock')->get();

            foreach ($orphanedCurrentLivestock as $current) {
                $preview[] = [
                    'type' => 'remove_orphaned_current_livestock',
                    'action' => 'DELETE',
                    'model' => 'CurrentLivestock',
                    'current_livestock_id' => $current->id,
                    'livestock_id' => $current->livestock_id,
                    'changes' => [
                        'before' => [
                            'status' => $current->status ?? 'unknown',
                            'quantity' => $current->quantity ?? 0,
                            'weight_total' => $current->weight_total ?? 0,
                            'weight_avg' => $current->weight_avg ?? 0
                        ],
                        'after' => [
                            'status' => 'deleted',
                            'quantity' => 0,
                            'weight_total' => 0,
                            'weight_avg' => 0
                        ]
                    ],
                    'description' => "Will remove orphaned CurrentLivestock (references non-existent livestock ID: {$current->livestock_id})",
                    'impact' => 'medium' // Removing orphaned data
                ];
            }

            // dd($preview);

            return [
                'success' => true,
                'preview' => $preview,
                'summary' => [
                    'missing_current_livestock' => $livestocksWithoutCurrent->count(),
                    'orphaned_current_livestock' => $orphanedCurrentLivestock->count(),
                    'total_changes' => count($preview)
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Error previewing CurrentLivestock changes: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'preview' => [],
                'summary' => []
            ];
        }
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
            'feed_stocks' => \App\Models\Livestock::withTrashed()->get()->toArray(),
            'feed_purchases' => \App\Models\LivestockPurchase::withTrashed()->get()->toArray(),
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
            \App\Models\Livestock::truncate();
            \App\Models\LivestockPurchase::truncate();
            \App\Models\Mutation::truncate();
            \App\Models\MutationItem::truncate();
            \App\Models\Livestock::insert($data['feed_stocks'] ?? []);
            \App\Models\LivestockPurchase::insert($data['feed_purchases'] ?? []);
            \App\Models\Mutation::insert($data['mutations'] ?? []);
            \App\Models\MutationItem::insert($data['mutation_items'] ?? []);
        });
        return true;
    }

    /**
     * Check for weight and price mismatches between purchase items and batches
     */
    public function checkPurchaseItemBatchMismatches()
    {
        $mismatches = [];

        // Get all purchase items with their batches using Eloquent
        $purchaseItems = LivestockPurchaseItem::with('batch')
            ->whereHas('batch')
            ->whereNull('deleted_at')
            ->get();

        foreach ($purchaseItems as $item) {
            $mismatchFound = false;
            $mismatchDetails = [];

            // Check weight calculations
            $expectedWeightPerUnit = $item->weight_type === 'per_unit'
                ? $item->weight_value
                : ($item->weight_value / $item->quantity);

            $expectedWeightTotal = $item->weight_type === 'total'
                ? $item->weight_value
                : ($item->weight_value * $item->quantity);

            if ((float)$item->weight_per_unit !== (float)$expectedWeightPerUnit) {
                $mismatchFound = true;
                $mismatchDetails['weight_per_unit'] = [
                    'expected' => $expectedWeightPerUnit,
                    'actual' => $item->weight_per_unit
                ];
            }

            if ((float)$item->weight_total !== (float)$expectedWeightTotal) {
                $mismatchFound = true;
                $mismatchDetails['weight_total'] = [
                    'expected' => $expectedWeightTotal,
                    'actual' => $item->weight_total
                ];
            }

            // Check price calculations
            $expectedPricePerUnit = $item->price_type === 'per_unit'
                ? $item->price_value
                : ($item->price_value / $item->quantity);

            $expectedPriceTotal = $item->price_type === 'total'
                ? $item->price_value
                : ($item->price_value * $item->quantity);

            if ((float)$item->price_per_unit !== (float)$expectedPricePerUnit) {
                $mismatchFound = true;
                $mismatchDetails['price_per_unit'] = [
                    'expected' => $expectedPricePerUnit,
                    'actual' => $item->price_per_unit
                ];
            }

            if ((float)$item->price_total !== (float)$expectedPriceTotal) {
                $mismatchFound = true;
                $mismatchDetails['price_total'] = [
                    'expected' => $expectedPriceTotal,
                    'actual' => $item->price_total
                ];
            }

            if ($mismatchFound) {
                $mismatches[] = [
                    'type' => 'purchase_item_batch_mismatch',
                    'purchase_item_id' => $item->id,
                    'batch_id' => $item->batch->id,
                    'details' => $mismatchDetails,
                    'message' => "Mismatch found in calculations for Purchase Item ID {$item->id} and Batch ID {$item->batch->id}"
                ];
            }
        }

        return $mismatches;
    }

    /**
     * Fix weight and price mismatches between purchase items and batches
     */
    public function fixPurchaseItemBatchMismatches()
    {
        $fixedCount = 0;
        $mismatches = $this->checkPurchaseItemBatchMismatches();

        foreach ($mismatches as $mismatch) {
            $purchaseItem = LivestockPurchaseItem::find($mismatch['purchase_item_id']);
            $batch = LivestockBatch::find($mismatch['batch_id']);

            if ($purchaseItem && $batch) {
                // Calculate correct values
                $weightPerUnit = $purchaseItem->weight_type === 'per_unit'
                    ? $purchaseItem->weight_value
                    : ($purchaseItem->weight_value / $purchaseItem->quantity);

                $weightTotal = $purchaseItem->weight_type === 'total'
                    ? $purchaseItem->weight_value
                    : ($purchaseItem->weight_value * $purchaseItem->quantity);

                $pricePerUnit = $purchaseItem->price_type === 'per_unit'
                    ? $purchaseItem->price_value
                    : ($purchaseItem->price_value / $purchaseItem->quantity);

                $priceTotal = $purchaseItem->price_type === 'total'
                    ? $purchaseItem->price_value
                    : ($purchaseItem->price_value * $purchaseItem->quantity);

                // Update purchase item
                $purchaseItem->update([
                    'weight_per_unit' => $weightPerUnit,
                    'weight_total' => $weightTotal,
                    'price_per_unit' => $pricePerUnit,
                    'price_total' => $priceTotal,
                    'updated_by' => auth()->id()
                ]);

                // Update batch
                $batch->update([
                    'weight' => $weightPerUnit,
                    'weight_total' => $weightTotal,
                    'price' => $pricePerUnit,
                    'price_total' => $priceTotal,
                    'updated_by' => auth()->id()
                ]);

                $fixedCount++;
                $this->logs[] = [
                    'type' => 'fix_purchase_item_batch_mismatch',
                    'message' => "Fixed calculations for Purchase Item ID {$purchaseItem->id} and Batch ID {$batch->id}",
                    'data' => [
                        'purchase_item' => $purchaseItem->toArray(),
                        'batch' => $batch->toArray()
                    ]
                ];
            }
        }

        return [
            'success' => true,
            'fixed_count' => $fixedCount,
            'logs' => $this->logs
        ];
    }

    /**
     * Preview changes for weight and price mismatches
     */
    public function previewPurchaseItemBatchChanges()
    {
        $previewData = [];
        $mismatches = $this->checkPurchaseItemBatchMismatches();

        foreach ($mismatches as $mismatch) {
            $purchaseItem = LivestockPurchaseItem::find($mismatch['purchase_item_id']);
            $batch = LivestockBatch::find($mismatch['batch_id']);

            if ($purchaseItem && $batch) {
                $previewData[] = [
                    'type' => 'purchase_item_batch_mismatch',
                    'id' => $purchaseItem->id,
                    'before' => [
                        'weight_per_unit' => $purchaseItem->weight_per_unit,
                        'weight_total' => $purchaseItem->weight_total,
                        'price_per_unit' => $purchaseItem->price_per_unit,
                        'price_total' => $purchaseItem->price_total,
                        'batch_weight' => $batch->weight,
                        'batch_weight_total' => $batch->weight_total,
                        'batch_price' => $batch->price,
                        'batch_price_total' => $batch->price_total
                    ],
                    'after' => [
                        'weight_per_unit' => $purchaseItem->weight_type === 'per_unit'
                            ? $purchaseItem->weight_value
                            : ($purchaseItem->weight_value / $purchaseItem->quantity),
                        'weight_total' => $purchaseItem->weight_type === 'total'
                            ? $purchaseItem->weight_value
                            : ($purchaseItem->weight_value * $purchaseItem->quantity),
                        'price_per_unit' => $purchaseItem->price_type === 'per_unit'
                            ? $purchaseItem->price_value
                            : ($purchaseItem->price_value / $purchaseItem->quantity),
                        'price_total' => $purchaseItem->price_type === 'total'
                            ? $purchaseItem->price_value
                            : ($purchaseItem->price_value * $purchaseItem->quantity)
                    ],
                    'message' => "Purchase Item ID {$purchaseItem->id} and Batch ID {$batch->id} will be updated with corrected calculations"
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
     * Fix missing CurrentLivestock records
     */
    /**
     * Check price data integrity for livestock batches
     * Validates that price columns have correct values and relationships
     */
    protected function checkPriceDataIntegrity()
    {
        try {
            // Check for batches with missing price data when purchase items have price data
            $batchesWithMissingPrice = DB::table('livestock_batches as lb')
                ->join('livestock_purchase_items as lpi', 'lb.livestock_purchase_item_id', '=', 'lpi.id')
                ->where('lb.source_type', 'purchase')
                ->whereNull('lb.deleted_at')
                ->whereNull('lpi.deleted_at')
                ->where(function ($query) {
                    $query->where('lb.price_total', 0)
                        ->orWhereNull('lb.price_total')
                        ->orWhere('lb.price_per_unit', 0)
                        ->orWhereNull('lb.price_per_unit');
                })
                ->where(function ($query) {
                    $query->where('lpi.price_total', '>', 0)
                        ->where('lpi.price_per_unit', '>', 0);
                })
                ->select('lb.*', 'lpi.price_total as item_price_total', 'lpi.price_per_unit as item_price_per_unit', 'lpi.price_value as item_price_value', 'lpi.price_type as item_price_type')
                ->get();

            foreach ($batchesWithMissingPrice as $batch) {
                $this->logs[] = [
                    'type' => 'price_data_missing',
                    'message' => "Livestock batch ID {$batch->id} missing price data while purchase item has valid price data.",
                    'data' => [
                        'batch_id' => $batch->id,
                        'batch_price_total' => $batch->price_total,
                        'batch_price_per_unit' => $batch->price_per_unit,
                        'item_price_total' => $batch->item_price_total,
                        'item_price_per_unit' => $batch->item_price_per_unit,
                        'item_price_value' => $batch->item_price_value,
                        'item_price_type' => $batch->item_price_type,
                    ],
                    'reasons' => [
                        "Batch price_total is " . ($batch->price_total ?: '0 or null'),
                        "Batch price_per_unit is " . ($batch->price_per_unit ?: '0 or null'),
                        "But purchase item has valid price data: total={$batch->item_price_total}, per_unit={$batch->item_price_per_unit}"
                    ]
                ];
            }

            // Check for price calculation mismatches between batch and purchase item
            $batchesWithPriceMismatch = DB::table('livestock_batches as lb')
                ->join('livestock_purchase_items as lpi', 'lb.livestock_purchase_item_id', '=', 'lpi.id')
                ->where('lb.source_type', 'purchase')
                ->whereNull('lb.deleted_at')
                ->whereNull('lpi.deleted_at')
                ->where('lb.price_total', '>', 0)
                ->where('lpi.price_total', '>', 0)
                ->whereRaw('ABS(lb.price_total - lpi.price_total) > 0.01')
                ->select('lb.*', 'lpi.price_total as item_price_total', 'lpi.price_per_unit as item_price_per_unit')
                ->get();

            foreach ($batchesWithPriceMismatch as $batch) {
                $priceDifference = abs($batch->price_total - $batch->item_price_total);
                $this->logs[] = [
                    'type' => 'price_calculation_mismatch',
                    'message' => "Livestock batch ID {$batch->id} has price mismatch with purchase item.",
                    'data' => [
                        'batch_id' => $batch->id,
                        'batch_price_total' => $batch->price_total,
                        'item_price_total' => $batch->item_price_total,
                        'difference' => $priceDifference,
                    ],
                    'reasons' => [
                        "Batch price_total: {$batch->price_total}",
                        "Purchase item price_total: {$batch->item_price_total}",
                        "Difference: {$priceDifference}"
                    ]
                ];
            }

            // Check for livestock with incorrect price aggregation
            $livestockWithPriceIssues = DB::table('livestocks as l')
                ->join('livestock_purchase_items as lpi', 'l.id', '=', 'lpi.livestock_id')
                ->whereNull('l.deleted_at')
                ->whereNull('lpi.deleted_at')
                ->where('lpi.price_total', '>', 0)
                ->where(function ($query) {
                    $query->where('l.price', 0)
                        ->orWhereNull('l.price');
                })
                ->select('l.id as livestock_id', 'l.name as livestock_name', 'l.price as livestock_price')
                ->distinct()
                ->get();

            foreach ($livestockWithPriceIssues as $livestock) {
                // Calculate expected price from purchase items
                $expectedPrice = DB::table('livestock_purchase_items')
                    ->where('livestock_id', $livestock->livestock_id)
                    ->whereNull('deleted_at')
                    ->selectRaw('
                        SUM(quantity) as total_quantity,
                        SUM(price_total) as total_price_value,
                        CASE 
                            WHEN SUM(quantity) > 0 THEN SUM(price_total) / SUM(quantity)
                            ELSE 0 
                        END as expected_avg_price
                    ')
                    ->first();

                $this->logs[] = [
                    'type' => 'livestock_price_aggregation_issue',
                    'message' => "Livestock ID {$livestock->livestock_id} has incorrect price aggregation.",
                    'data' => [
                        'livestock_id' => $livestock->livestock_id,
                        'livestock_name' => $livestock->livestock_name,
                        'current_price' => $livestock->livestock_price,
                        'expected_price' => $expectedPrice->expected_avg_price ?? 0,
                        'total_quantity' => $expectedPrice->total_quantity ?? 0,
                        'total_price_value' => $expectedPrice->total_price_value ?? 0,
                    ],
                    'reasons' => [
                        "Current livestock price: " . ($livestock->livestock_price ?: '0 or null'),
                        "Expected price based on purchase items: " . ($expectedPrice->expected_avg_price ?? 0),
                        "Total purchase items quantity: " . ($expectedPrice->total_quantity ?? 0),
                        "Total purchase items price value: " . ($expectedPrice->total_price_value ?? 0)
                    ]
                ];
            }
        } catch (\Exception $e) {
            $this->logs[] = [
                'type' => 'error',
                'message' => 'Error checking price data integrity: ' . $e->getMessage(),
                'data' => [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ],
                'reasons' => ['Exception occurred during price integrity check']
            ];

            Log::error('Error in checkPriceDataIntegrity', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    public function fixMissingCurrentLivestock()
    {
        $fixedCount = 0;
        $removedCount = 0;

        try {
            Log::info('Starting CurrentLivestock integrity fix');

            // Find livestock without CurrentLivestock - menggunakan query yang sama seperti di preview
            $livestocksWithoutCurrent = Livestock::whereDoesntHave('currentLivestock')
                ->whereNull('deleted_at')
                ->get();

            Log::info('Found livestock without CurrentLivestock', ['count' => $livestocksWithoutCurrent->count()]);

            foreach ($livestocksWithoutCurrent as $livestock) {
                Log::info('Processing livestock without CurrentLivestock', ['livestock_id' => $livestock->id]);

                // Calculate totals from batches - menggunakan sum dengan callback yang benar
                $batches = LivestockBatch::where('livestock_id', $livestock->id)
                    ->whereNull('deleted_at')
                    ->get();

                $totalQuantity = $batches->sum('initial_quantity') ?? 0;
                $totalWeightSum = $batches->sum(function ($batch) {
                    return ($batch->initial_quantity ?? 0) * ($batch->weight ?? 0);
                }) ?? 0;
                $avgWeight = $totalQuantity > 0 ? $totalWeightSum / $totalQuantity : 0;

                Log::info('Calculated totals for livestock', [
                    'livestock_id' => $livestock->id,
                    'total_quantity' => $totalQuantity,
                    'total_weight_sum' => $totalWeightSum,
                    'avg_weight' => $avgWeight,
                    'batch_count' => $batches->count()
                ]);

                // Create CurrentLivestock record
                $currentLivestock = CurrentLivestock::create([
                    'livestock_id' => $livestock->id,
                    'farm_id' => $livestock->farm_id,
                    'coop_id' => $livestock->coop_id,
                    'quantity' => $totalQuantity,
                    'weight_total' => $totalWeightSum,
                    'weight_avg' => $avgWeight,
                    'status' => 'active',
                    'created_by' => auth()->id() ?? 1, // Fallback ke user ID 1 jika tidak ada auth
                    'updated_by' => auth()->id() ?? 1,
                ]);

                Log::info('Created CurrentLivestock record', ['current_livestock_id' => $currentLivestock->id]);

                $this->logs[] = [
                    'type' => 'fix_missing_current_livestock',
                    'message' => "Created missing CurrentLivestock for Livestock ID {$livestock->id} with quantity {$totalQuantity}",
                    'data' => [
                        'id' => $currentLivestock->id,
                        'model_type' => get_class($currentLivestock),
                        'livestock_id' => $livestock->id,
                        'livestock_name' => $livestock->name ?? 'Unknown',
                        'current_livestock' => $currentLivestock->toArray(),
                        'calculated_totals' => [
                            'quantity' => $totalQuantity,
                            'weight_total' => $totalWeightSum,
                            'weight_avg' => $avgWeight,
                            'batch_count' => $batches->count()
                        ]
                    ],
                ];
                $fixedCount++;
            }

            // Remove orphaned CurrentLivestock records
            $orphanedCurrentLivestock = CurrentLivestock::whereDoesntHave('livestock')->get();

            Log::info('Found orphaned CurrentLivestock records', ['count' => $orphanedCurrentLivestock->count()]);

            foreach ($orphanedCurrentLivestock as $current) {
                $before = $current->toArray();
                $current->delete();

                Log::info('Removed orphaned CurrentLivestock', ['current_livestock_id' => $current->id]);

                $this->logs[] = [
                    'type' => 'remove_orphaned_current_livestock',
                    'message' => "Removed orphaned CurrentLivestock ID {$current->id} (referenced non-existent livestock ID {$current->livestock_id})",
                    'data' => [
                        'id' => $current->id,
                        'model_type' => get_class($current),
                        'removed_data' => $before,
                    ],
                ];
                $removedCount++;
            }

            Log::info('CurrentLivestock fix completed', [
                'fixed_count' => $fixedCount,
                'removed_count' => $removedCount
            ]);

            return [
                'success' => true,
                'logs' => $this->logs,
                'fixed_count' => $fixedCount,
                'removed_count' => $removedCount
            ];
        } catch (\Exception $e) {
            Log::error('Error fixing missing CurrentLivestock: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'logs' => $this->logs,
                'fixed_count' => $fixedCount,
                'removed_count' => $removedCount
            ];
        }
    }

    /**
     * Fix price data integrity issues
     */
    public function fixPriceDataIntegrity()
    {
        $fixedCount = 0;

        try {
            DB::beginTransaction();

            // Fix batches with missing price data
            $batchesWithMissingPrice = DB::table('livestock_batches as lb')
                ->join('livestock_purchase_items as lpi', 'lb.livestock_purchase_item_id', '=', 'lpi.id')
                ->where('lb.source_type', 'purchase')
                ->whereNull('lb.deleted_at')
                ->whereNull('lpi.deleted_at')
                ->where(function ($query) {
                    $query->where('lb.price_total', 0)
                        ->orWhereNull('lb.price_total')
                        ->orWhere('lb.price_per_unit', 0)
                        ->orWhereNull('lb.price_per_unit');
                })
                ->where(function ($query) {
                    $query->where('lpi.price_total', '>', 0)
                        ->where('lpi.price_per_unit', '>', 0);
                })
                ->select('lb.id as batch_id', 'lpi.price_total', 'lpi.price_per_unit', 'lpi.price_value', 'lpi.price_type')
                ->get();

            foreach ($batchesWithMissingPrice as $batch) {
                $updated = DB::table('livestock_batches')
                    ->where('id', $batch->batch_id)
                    ->update([
                        'price_total' => $batch->price_total,
                        'price_per_unit' => $batch->price_per_unit,
                        'price_value' => $batch->price_value,
                        'price_type' => $batch->price_type,
                        'updated_at' => now(),
                        'updated_by' => auth()->id() ?? 1
                    ]);

                if ($updated) {
                    $fixedCount++;
                    $this->logs[] = [
                        'type' => 'price_data_fixed',
                        'message' => "Fixed missing price data for livestock batch ID {$batch->batch_id}",
                        'data' => [
                            'batch_id' => $batch->batch_id,
                            'price_total' => $batch->price_total,
                            'price_per_unit' => $batch->price_per_unit,
                        ]
                    ];
                }
            }

            // Fix price calculation mismatches
            $batchesWithPriceMismatch = DB::table('livestock_batches as lb')
                ->join('livestock_purchase_items as lpi', 'lb.livestock_purchase_item_id', '=', 'lpi.id')
                ->where('lb.source_type', 'purchase')
                ->whereNull('lb.deleted_at')
                ->whereNull('lpi.deleted_at')
                ->where('lb.price_total', '>', 0)
                ->where('lpi.price_total', '>', 0)
                ->whereRaw('ABS(lb.price_total - lpi.price_total) > 0.01')
                ->select('lb.id as batch_id', 'lpi.price_total', 'lpi.price_per_unit', 'lpi.price_value', 'lpi.price_type')
                ->get();

            foreach ($batchesWithPriceMismatch as $batch) {
                $updated = DB::table('livestock_batches')
                    ->where('id', $batch->batch_id)
                    ->update([
                        'price_total' => $batch->price_total,
                        'price_per_unit' => $batch->price_per_unit,
                        'price_value' => $batch->price_value,
                        'price_type' => $batch->price_type,
                        'updated_at' => now(),
                        'updated_by' => auth()->id() ?? 1
                    ]);

                if ($updated) {
                    $fixedCount++;
                    $this->logs[] = [
                        'type' => 'price_mismatch_fixed',
                        'message' => "Fixed price mismatch for livestock batch ID {$batch->batch_id}",
                        'data' => [
                            'batch_id' => $batch->batch_id,
                            'corrected_price_total' => $batch->price_total,
                            'corrected_price_per_unit' => $batch->price_per_unit,
                        ]
                    ];
                }
            }

            // Fix livestock price aggregation issues
            $livestockWithPriceIssues = DB::table('livestocks as l')
                ->join('livestock_purchase_items as lpi', 'l.id', '=', 'lpi.livestock_id')
                ->whereNull('l.deleted_at')
                ->whereNull('lpi.deleted_at')
                ->where('lpi.price_total', '>', 0)
                ->where(function ($query) {
                    $query->where('l.price', 0)
                        ->orWhereNull('l.price');
                })
                ->select('l.id as livestock_id')
                ->distinct()
                ->get();

            foreach ($livestockWithPriceIssues as $livestock) {
                // Calculate correct price from purchase items
                $correctPrice = DB::table('livestock_purchase_items')
                    ->where('livestock_id', $livestock->livestock_id)
                    ->whereNull('deleted_at')
                    ->selectRaw('
                        SUM(quantity) as total_quantity,
                        SUM(price_total) as total_price_value,
                        CASE 
                            WHEN SUM(quantity) > 0 THEN SUM(price_total) / SUM(quantity)
                            ELSE 0 
                        END as correct_avg_price
                    ')
                    ->first();

                if ($correctPrice && $correctPrice->correct_avg_price > 0) {
                    $updated = DB::table('livestocks')
                        ->where('id', $livestock->livestock_id)
                        ->update([
                            'price' => $correctPrice->correct_avg_price,
                            'updated_at' => now(),
                            'updated_by' => auth()->id() ?? 1
                        ]);

                    if ($updated) {
                        $fixedCount++;
                        $this->logs[] = [
                            'type' => 'livestock_price_fixed',
                            'message' => "Fixed price aggregation for livestock ID {$livestock->livestock_id}",
                            'data' => [
                                'livestock_id' => $livestock->livestock_id,
                                'corrected_price' => $correctPrice->correct_avg_price,
                                'total_quantity' => $correctPrice->total_quantity,
                                'total_price_value' => $correctPrice->total_price_value,
                            ]
                        ];
                    }
                }
            }

            DB::commit();

            $this->logs[] = [
                'type' => 'success',
                'message' => "Successfully fixed {$fixedCount} price data integrity issues",
                'data' => ['fixed_count' => $fixedCount]
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            $this->logs[] = [
                'type' => 'error',
                'message' => 'Error fixing price data integrity: ' . $e->getMessage(),
                'data' => [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ];

            Log::error('Error in fixPriceDataIntegrity', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return [
            'success' => true,
            'logs' => $this->logs,
            'fixed_count' => $fixedCount
        ];
    }
}

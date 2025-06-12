<?php

namespace App\Services;

use App\Models\SupplyStock;
use App\Models\Mutation;
use App\Models\MutationItem;
use App\Models\SupplyPurchase;
use App\Models\SupplyUsage;
use App\Models\CurrentSupply;
use App\Models\Supply;
use App\Models\SupplyPurchaseBatch;
use App\Models\Farm;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\DataAuditTrail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class SupplyDataIntegrityService
{
    protected $logs = [];
    protected $version = '2.0.0'; // Updated version for refactor
    protected $checkCategories = [
        'stock_integrity',
        'current_supply_integrity',
        'purchase_integrity',
        'mutation_integrity',
        'usage_integrity',
        'status_integrity',
        'master_data_integrity',
        'relationship_integrity'
    ];

    public function previewInvalidSupplyData($categories = null)
    {
        try {
            $this->logs = [];
            $this->logs[] = [
                'type' => 'info',
                'message' => 'SupplyDataIntegrityService version ' . $this->version . ' - ' . now(),
                'data' => [
                    'available_categories' => $this->checkCategories,
                    'selected_categories' => $categories ?? $this->checkCategories
                ],
            ];

            $categoriesToCheck = $categories ?? $this->checkCategories;

            // Run integrity checks based on selected categories
            foreach ($categoriesToCheck as $category) {
                $this->runIntegrityCheck($category);
            }

            return [
                'success' => true,
                'logs' => $this->logs,
                'invalid_stocks_count' => $this->countInvalidItems()
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

    /**
     * Run specific integrity check by category
     */
    protected function runIntegrityCheck($category)
    {
        switch ($category) {
            case 'stock_integrity':
                $this->checkStockIntegrity();
                break;
            case 'current_supply_integrity':
                $this->checkCurrentSupplyIntegrity();
                break;
            case 'purchase_integrity':
                $this->checkPurchaseIntegrity();
                break;
            case 'mutation_integrity':
                $this->checkMutationIntegrity();
                break;
            case 'usage_integrity':
                $this->checkUsageIntegrity();
                break;
            case 'status_integrity':
                $this->checkStatusIntegrity();
                break;
            case 'master_data_integrity':
                $this->checkMasterDataIntegrity();
                break;
            case 'relationship_integrity':
                $this->checkRelationshipIntegrity();
                break;
        }
    }

    /**
     * Check CurrentSupply integrity vs SupplyStock
     */
    protected function checkCurrentSupplyIntegrity()
    {
        $currentSupplies = CurrentSupply::where('type', 'supply')->get();

        foreach ($currentSupplies as $currentSupply) {
            // Calculate actual stock from SupplyStock
            $actualStock = $this->calculateActualStock($currentSupply->farm_id, $currentSupply->item_id);

            // Check if CurrentSupply quantity matches calculated stock
            if (abs($currentSupply->quantity - $actualStock) > 0.001) {
                $this->logs[] = [
                    'type' => 'current_supply_mismatch',
                    'message' => "CurrentSupply mismatch: Farm {$currentSupply->farm_id}, Supply {$currentSupply->item_id} - CurrentSupply: {$currentSupply->quantity}, Calculated: {$actualStock}",
                    'data' => [
                        'current_supply' => $currentSupply->toArray(),
                        'calculated_stock' => $actualStock,
                        'difference' => $currentSupply->quantity - $actualStock,
                        'farm_id' => $currentSupply->farm_id,
                        'supply_id' => $currentSupply->item_id,
                        'id' => $currentSupply->id,
                        'model_type' => get_class($currentSupply)
                    ],
                    'reasons' => [
                        "CurrentSupply quantity ({$currentSupply->quantity}) does not match calculated stock ({$actualStock})"
                    ]
                ];
            }
        }

        // Check for missing CurrentSupply records
        $this->checkMissingCurrentSupplyRecords();

        // Check for orphaned CurrentSupply records
        $this->checkOrphanedCurrentSupplyRecords();
    }

    /**
     * Calculate actual stock from SupplyStock records
     */
    protected function calculateActualStock($farmId, $supplyId)
    {
        // Get all purchases that have arrived (status-based calculation)
        $purchaseStock = SupplyStock::join('supply_purchases', 'supply_stocks.source_id', '=', 'supply_purchases.id')
            ->join('supply_purchase_batches', 'supply_purchases.supply_purchase_batch_id', '=', 'supply_purchase_batches.id')
            ->where('supply_stocks.source_type', 'purchase')
            ->where('supply_stocks.farm_id', $farmId)
            ->where('supply_stocks.supply_id', $supplyId)
            ->where('supply_purchase_batches.status', 'arrived') // Only count arrived purchases
            ->whereNull('supply_stocks.deleted_at')
            ->sum('supply_stocks.quantity_in');

        // Get incoming mutations
        $incomingMutations = SupplyStock::where('source_type', 'mutation')
            ->where('farm_id', $farmId)
            ->where('supply_id', $supplyId)
            ->whereNull('deleted_at')
            ->sum('quantity_in');

        // Get used quantities
        $usedQuantities = SupplyStock::where('farm_id', $farmId)
            ->where('supply_id', $supplyId)
            ->whereNull('deleted_at')
            ->sum('quantity_used');

        // Get mutated quantities (outgoing)
        $mutatedQuantities = SupplyStock::where('farm_id', $farmId)
            ->where('supply_id', $supplyId)
            ->whereNull('deleted_at')
            ->sum('quantity_mutated');

        return $purchaseStock + $incomingMutations - $usedQuantities - $mutatedQuantities;
    }

    /**
     * Check for missing CurrentSupply records
     */
    protected function checkMissingCurrentSupplyRecords()
    {
        // Find farms and supplies that have stock but no CurrentSupply record
        $stocksWithoutCurrentSupply = DB::table('supply_stocks')
            ->select('farm_id', 'supply_id')
            ->whereNull('deleted_at')
            ->groupBy('farm_id', 'supply_id')
            ->get();

        foreach ($stocksWithoutCurrentSupply as $stock) {
            $currentSupply = CurrentSupply::where('farm_id', $stock->farm_id)
                ->where('item_id', $stock->supply_id)
                ->where('type', 'supply')
                ->first();

            if (!$currentSupply) {
                $calculatedStock = $this->calculateActualStock($stock->farm_id, $stock->supply_id);

                $this->logs[] = [
                    'type' => 'missing_current_supply',
                    'message' => "Missing CurrentSupply record for Farm {$stock->farm_id}, Supply {$stock->supply_id} (Calculated stock: {$calculatedStock})",
                    'data' => [
                        'farm_id' => $stock->farm_id,
                        'supply_id' => $stock->supply_id,
                        'calculated_stock' => $calculatedStock,
                        'type' => 'supply'
                    ],
                    'reasons' => [
                        "No CurrentSupply record found but SupplyStock records exist"
                    ]
                ];
            }
        }
    }

    /**
     * Check for orphaned CurrentSupply records
     */
    protected function checkOrphanedCurrentSupplyRecords()
    {
        $orphanedCurrentSupplies = CurrentSupply::where('type', 'supply')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('supply_stocks')
                    ->whereColumn('supply_stocks.farm_id', 'current_supplies.farm_id')
                    ->whereColumn('supply_stocks.supply_id', 'current_supplies.item_id')
                    ->whereNull('supply_stocks.deleted_at');
            })
            ->get();

        foreach ($orphanedCurrentSupplies as $orphaned) {
            $this->logs[] = [
                'type' => 'orphaned_current_supply',
                'message' => "Orphaned CurrentSupply record: Farm {$orphaned->farm_id}, Supply {$orphaned->item_id} (No corresponding SupplyStock)",
                'data' => [
                    'current_supply' => $orphaned->toArray(),
                    'id' => $orphaned->id,
                    'model_type' => get_class($orphaned)
                ],
                'reasons' => [
                    "CurrentSupply record exists but no SupplyStock records found"
                ]
            ];
        }
    }

    /**
     * Check stock integrity - refactored from existing functionality
     */
    protected function checkStockIntegrity()
    {
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
                $mutationExists = DB::table('mutations')
                    ->where('id', $stock->source_id)
                    ->whereNull('deleted_at')
                    ->exists();

                if (!$mutationExists) {
                    $reasons[] = "Mutation record with ID {$stock->source_id} does not exist or has been deleted";
                }

                $mutationItemExists = DB::table('mutation_items')
                    ->where('mutation_id', $stock->source_id)
                    ->where('item_type', 'supply')
                    ->whereNull('deleted_at')
                    ->exists();

                if (!$mutationItemExists) {
                    $reasons[] = "No mutation items found for mutation ID {$stock->source_id}";
                }
            } elseif ($stock->source_type === 'purchase') {
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

        // Check for missing stocks
        $this->checkMissingStocks();
    }

    /**
     * Check for missing stock records
     */
    protected function checkMissingStocks()
    {
        // Check purchases without stock (only for arrived batches)
        $purchasesWithoutStock = SupplyPurchase::join('supply_purchase_batches', 'supply_purchases.supply_purchase_batch_id', '=', 'supply_purchase_batches.id')
            ->where('supply_purchase_batches.status', 'arrived')
            ->whereNull('supply_purchases.deleted_at')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('supply_stocks')
                    ->whereColumn('supply_stocks.source_id', 'supply_purchases.id')
                    ->where('supply_stocks.source_type', 'purchase')
                    ->whereNull('supply_stocks.deleted_at');
            })
            ->get(['supply_purchases.*']);

        foreach ($purchasesWithoutStock as $purchase) {
            $this->logs[] = [
                'type' => 'missing_stock',
                'message' => "Arrived purchase record with ID {$purchase->id} does not have a corresponding supply stock.",
                'data' => $purchase->toArray(),
                'reasons' => ["No supply stock found for this arrived purchase record."]
            ];
        }

        // Check mutations without stock
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
    }

    /**
     * Check purchase integrity
     */
    protected function checkPurchaseIntegrity()
    {
        // Check quantity mismatch between stock and purchase
        $stocksWithPurchase = SupplyStock::where('source_type', 'purchase')
            ->whereNull('deleted_at')
            ->get();

        foreach ($stocksWithPurchase as $stock) {
            $purchase = SupplyPurchase::where('id', $stock->source_id)->whereNull('deleted_at')->first();
            if ($purchase && $stock->quantity_in != $purchase->converted_quantity) {
                $this->logs[] = [
                    'type' => 'quantity_mismatch',
                    'message' => "Quantity mismatch: Stock ID {$stock->id} (quantity_in: {$stock->quantity_in}) vs Purchase ID {$purchase->id} (converted_quantity: {$purchase->converted_quantity})",
                    'data' => [
                        'stock' => $stock->toArray(),
                        'purchase' => $purchase->toArray(),
                        'id' => $stock->id,
                        'model_type' => get_class($stock)
                    ],
                    'reasons' => [
                        "Stock quantity_in ({$stock->quantity_in}) does not match purchase converted_quantity ({$purchase->converted_quantity})"
                    ]
                ];
            }
        }

        // Check conversion mismatch
        $purchases = SupplyPurchase::whereNull('deleted_at')->get();
        foreach ($purchases as $purchase) {
            if (method_exists($purchase, 'calculateConvertedQuantity')) {
                $expectedConverted = $purchase->calculateConvertedQuantity();
                if (abs((float) $purchase->converted_quantity - (float) $expectedConverted) > 0.001) {
                    $this->logs[] = [
                        'type' => 'conversion_mismatch',
                        'message' => "Conversion mismatch: Purchase ID {$purchase->id} (converted_quantity: {$purchase->converted_quantity}) vs Expected ({$expectedConverted})",
                        'data' => [
                            'purchase' => $purchase->toArray(),
                            'id' => $purchase->id,
                            'model_type' => get_class($purchase)
                        ],
                        'reasons' => [
                            "Converted quantity ({$purchase->converted_quantity}) does not match calculated value ({$expectedConverted})"
                        ]
                    ];
                }
            }
        }
    }

    /**
     * Check mutation integrity
     */
    protected function checkMutationIntegrity()
    {
        $mutationStocks = SupplyStock::where('source_type', 'mutation')->whereNull('deleted_at')->get();
        foreach ($mutationStocks as $stock) {
            $mutationItems = MutationItem::where('mutation_id', $stock->source_id)
                ->where('item_type', 'supply')
                ->whereNull('deleted_at')
                ->get();

            $totalMutationQty = $mutationItems->sum('quantity');
            if (abs((float)$stock->quantity_in - (float)$totalMutationQty) > 0.001) {
                $this->logs[] = [
                    'type' => 'mutation_quantity_mismatch',
                    'message' => "Quantity mismatch: Stock ID {$stock->id} (quantity_in: {$stock->quantity_in}) vs Total MutationItem ({$totalMutationQty})",
                    'data' => [
                        'stock' => $stock->toArray(),
                        'mutation_items' => $mutationItems->toArray(),
                        'id' => $stock->id,
                        'model_type' => get_class($stock)
                    ],
                    'reasons' => [
                        "Stock quantity_in ({$stock->quantity_in}) does not match total mutation item quantity ({$totalMutationQty})"
                    ]
                ];
            }
        }
    }

    /**
     * Check usage integrity
     */
    protected function checkUsageIntegrity()
    {
        // This can be expanded based on SupplyUsage model structure
        $this->logs[] = [
            'type' => 'info',
            'message' => 'Usage integrity check - placeholder for future implementation',
            'data' => []
        ];
    }

    /**
     * Check status integrity
     */
    protected function checkStatusIntegrity()
    {
        // Check SupplyPurchaseBatch status consistency
        $batches = SupplyPurchaseBatch::whereNull('deleted_at')->get();
        foreach ($batches as $batch) {
            // Check if batch has status 'arrived' but no stocks
            if ($batch->status === 'arrived') {
                $hasStocks = SupplyStock::where('source_type', 'purchase')
                    ->whereExists(function ($query) use ($batch) {
                        $query->select(DB::raw(1))
                            ->from('supply_purchases')
                            ->whereColumn('supply_purchases.id', 'supply_stocks.source_id')
                            ->where('supply_purchases.supply_purchase_batch_id', $batch->id);
                    })
                    ->exists();

                if (!$hasStocks) {
                    $this->logs[] = [
                        'type' => 'status_integrity_issue',
                        'message' => "Batch ID {$batch->id} has status 'arrived' but no corresponding stock records",
                        'data' => [
                            'batch' => $batch->toArray(),
                            'id' => $batch->id,
                            'model_type' => get_class($batch)
                        ],
                        'reasons' => [
                            "Batch status is 'arrived' but no SupplyStock records found"
                        ]
                    ];
                }
            }
        }
    }

    /**
     * Check master data integrity
     */
    protected function checkMasterDataIntegrity()
    {
        // Check if referenced supplies exist
        $stocksWithInvalidSupply = SupplyStock::whereNotExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('supplies')
                ->whereColumn('supplies.id', 'supply_stocks.supply_id')
                ->whereNull('supplies.deleted_at');
        })->get();

        foreach ($stocksWithInvalidSupply as $stock) {
            $this->logs[] = [
                'type' => 'master_data_issue',
                'message' => "Stock ID {$stock->id} references non-existent supply ID {$stock->supply_id}",
                'data' => $stock->toArray(),
                'reasons' => [
                    "Referenced supply does not exist or has been deleted"
                ]
            ];
        }

        // Check if referenced farms exist
        $stocksWithInvalidFarm = SupplyStock::whereNotExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('farms')
                ->whereColumn('farms.id', 'supply_stocks.farm_id')
                ->whereNull('farms.deleted_at');
        })->get();

        foreach ($stocksWithInvalidFarm as $stock) {
            $this->logs[] = [
                'type' => 'master_data_issue',
                'message' => "Stock ID {$stock->id} references non-existent farm ID {$stock->farm_id}",
                'data' => $stock->toArray(),
                'reasons' => [
                    "Referenced farm does not exist or has been deleted"
                ]
            ];
        }
    }

    /**
     * Check relationship integrity
     */
    protected function checkRelationshipIntegrity()
    {
        // Check MutationItem stock_id references
        $mutationItems = MutationItem::where('item_type', 'supply')->whereNull('deleted_at')->get();
        foreach ($mutationItems as $item) {
            if ($item->stock_id) {
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
        }
    }

    /**
     * Count invalid items from logs
     */
    protected function countInvalidItems()
    {
        $invalidTypes = [
            'invalid_stock',
            'current_supply_mismatch',
            'missing_stock',
            'quantity_mismatch',
            'conversion_mismatch',
            'mutation_quantity_mismatch',
            'missing_current_supply',
            'orphaned_current_supply',
            'status_integrity_issue',
            'master_data_issue',
            'mutation_item_invalid_stock'
        ];

        return collect($this->logs)->whereIn('type', $invalidTypes)->count();
    }

    /**
     * Fix CurrentSupply mismatch
     */
    public function fixCurrentSupplyMismatch()
    {
        $fixedCount = 0;
        $currentSupplies = CurrentSupply::where('type', 'supply')->get();

        foreach ($currentSupplies as $currentSupply) {
            $actualStock = $this->calculateActualStock($currentSupply->farm_id, $currentSupply->item_id);

            if (abs($currentSupply->quantity - $actualStock) > 0.001) {
                $before = $currentSupply->toArray();
                $currentSupply->quantity = $actualStock;
                $currentSupply->updated_by = auth()->id();
                $currentSupply->save();
                $after = $currentSupply->toArray();

                $this->logAudit($currentSupply, 'fix_current_supply_mismatch', $before, $after);

                $this->logs[] = [
                    'type' => 'fix_current_supply_mismatch',
                    'message' => "Fixed CurrentSupply for Farm {$currentSupply->farm_id}, Supply {$currentSupply->item_id} from {$before['quantity']} to {$actualStock}",
                    'data' => [
                        'id' => $currentSupply->id,
                        'model_type' => get_class($currentSupply),
                        'before_quantity' => $before['quantity'],
                        'after_quantity' => $actualStock
                    ]
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
     * Create missing CurrentSupply records
     */
    public function createMissingCurrentSupplyRecords()
    {
        $createdCount = 0;

        $stocksWithoutCurrentSupply = DB::table('supply_stocks')
            ->select('farm_id', 'supply_id')
            ->whereNull('deleted_at')
            ->groupBy('farm_id', 'supply_id')
            ->get();

        foreach ($stocksWithoutCurrentSupply as $stock) {
            $currentSupply = CurrentSupply::where('farm_id', $stock->farm_id)
                ->where('item_id', $stock->supply_id)
                ->where('type', 'supply')
                ->first();

            if (!$currentSupply) {
                $calculatedStock = $this->calculateActualStock($stock->farm_id, $stock->supply_id);
                $supply = Supply::find($stock->supply_id);
                $farm = Farm::find($stock->farm_id);

                if ($supply && $farm) {
                    $newCurrentSupply = CurrentSupply::create([
                        'farm_id' => $stock->farm_id,
                        'coop_id' => $farm->coop_id,
                        'item_id' => $stock->supply_id,
                        'unit_id' => $supply->data['unit_id'] ?? null,
                        'type' => 'supply',
                        'quantity' => $calculatedStock,
                        'status' => 'active',
                        'created_by' => auth()->id(),
                        'updated_by' => auth()->id()
                    ]);

                    $this->logs[] = [
                        'type' => 'create_missing_current_supply',
                        'message' => "Created missing CurrentSupply for Farm {$stock->farm_id}, Supply {$stock->supply_id} with quantity {$calculatedStock}",
                        'data' => [
                            'id' => $newCurrentSupply->id,
                            'model_type' => get_class($newCurrentSupply),
                            'created_record' => $newCurrentSupply->toArray()
                        ]
                    ];
                    $createdCount++;
                }
            }
        }

        return [
            'success' => true,
            'logs' => $this->logs,
            'created_count' => $createdCount
        ];
    }

    // Existing methods preserved for backward compatibility
    public function checkAndFixInvalidSupplyData()
    {
        DB::beginTransaction();
        try {
            $this->logs = [];

            // Run all integrity checks
            $this->runIntegrityCheck('stock_integrity');

            // Delete invalid stocks and recalculate
            $invalidStocks = collect($this->logs)
                ->where('type', 'invalid_stock')
                ->pluck('data');

            $deletedCount = 0;
            foreach ($invalidStocks as $stockData) {
                $stock = SupplyStock::find($stockData['id']);
                if ($stock) {
                    $stock->delete();
                    $this->recalculateFarmStock($stock->farm_id, $stock->supply_id);
                    $deletedCount++;
                }
            }

            DB::commit();
            return [
                'success' => true,
                'logs' => $this->logs,
                'deleted_stocks_count' => $deletedCount
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
        $actualStock = $this->calculateActualStock($farmId, $supplyId);

        $currentSupply = CurrentSupply::updateOrCreate(
            [
                'farm_id' => $farmId,
                'item_id' => $supplyId,
                'type' => 'supply'
            ],
            [
                'quantity' => $actualStock,
                'updated_by' => auth()->id()
            ]
        );

        $this->logs[] = [
            'type' => 'recalculation',
            'message' => "Recalculated stock for Farm ID: {$farmId}, Supply ID: {$supplyId}",
            'data' => [
                'farm_id' => $farmId,
                'supply_id' => $supplyId,
                'calculated_stock' => $actualStock
            ]
        ];
    }

    public function fixQuantityMismatchStocks()
    {
        $fixedCount = 0;
        $stocksWithPurchase = SupplyStock::where('source_type', 'purchase')
            ->whereNull('deleted_at')
            ->get();

        foreach ($stocksWithPurchase as $stock) {
            $purchase = SupplyPurchase::where('id', $stock->source_id)->whereNull('deleted_at')->first();
            if ($purchase && $stock->quantity_in != $purchase->converted_quantity) {
                $old = $stock->quantity_in;
                $before = $stock->toArray();
                $stock->quantity_in = $purchase->converted_quantity;
                $stock->save();
                $after = $stock->toArray();

                $this->logAudit($stock, 'fix_quantity_mismatch', $before, $after);

                $this->logs[] = [
                    'type' => 'fix_quantity_mismatch',
                    'message' => "Fixed quantity_in for Stock ID {$stock->id} from $old to {$purchase->converted_quantity}",
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

    public function fixConversionMismatchPurchases()
    {
        $fixedCount = 0;
        $purchases = SupplyPurchase::whereNull('deleted_at')->get();

        foreach ($purchases as $purchase) {
            if (method_exists($purchase, 'calculateConvertedQuantity')) {
                $expectedConverted = $purchase->calculateConvertedQuantity();
                if (abs((float) $purchase->converted_quantity - (float) $expectedConverted) > 0.001) {
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
        }

        return [
            'success' => true,
            'logs' => $this->logs,
            'fixed_count' => $fixedCount
        ];
    }

    public function fixMutationQuantityMismatchStocks()
    {
        $fixedCount = 0;
        $mutationStocks = SupplyStock::where('source_type', 'mutation')->whereNull('deleted_at')->get();

        foreach ($mutationStocks as $stock) {
            $mutationItems = MutationItem::where('mutation_id', $stock->source_id)
                ->where('item_type', 'supply')
                ->whereNull('deleted_at')
                ->get();

            $totalMutationQty = $mutationItems->sum('quantity');
            if (abs((float)$stock->quantity_in - (float)$totalMutationQty) > 0.001) {
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

    public function previewChanges()
    {
        $previewData = [];

        // Preview current supply mismatch fixes
        $currentSupplies = CurrentSupply::where('type', 'supply')->get();
        foreach ($currentSupplies as $currentSupply) {
            $actualStock = $this->calculateActualStock($currentSupply->farm_id, $currentSupply->item_id);
            if (abs($currentSupply->quantity - $actualStock) > 0.001) {
                $previewData[] = [
                    'type' => 'current_supply_mismatch',
                    'id' => $currentSupply->id,
                    'before' => [
                        'quantity' => $currentSupply->quantity,
                        'calculated_stock' => $actualStock,
                    ],
                    'after' => [
                        'quantity' => $actualStock,
                        'calculated_stock' => $actualStock,
                    ],
                    'message' => "CurrentSupply ID {$currentSupply->id}: quantity will be updated from {$currentSupply->quantity} to {$actualStock}"
                ];
            }
        }

        // Preview quantity mismatch fixes
        $stocksWithPurchase = SupplyStock::where('source_type', 'purchase')
            ->whereNull('deleted_at')
            ->get();
        foreach ($stocksWithPurchase as $stock) {
            $purchase = SupplyPurchase::where('id', $stock->source_id)->whereNull('deleted_at')->first();
            if ($purchase && $stock->quantity_in != $purchase->converted_quantity) {
                $previewData[] = [
                    'type' => 'quantity_mismatch',
                    'id' => $stock->id,
                    'before' => [
                        'quantity_in' => $stock->quantity_in,
                        'purchase_converted_quantity' => $purchase->converted_quantity,
                    ],
                    'after' => [
                        'quantity_in' => $purchase->converted_quantity,
                        'purchase_converted_quantity' => $purchase->converted_quantity,
                    ],
                    'message' => "Stock ID {$stock->id}: quantity_in will be updated from {$stock->quantity_in} to {$purchase->converted_quantity}"
                ];
            }
        }

        // Preview other changes...

        return [
            'success' => true,
            'preview_data' => $previewData,
            'total_changes' => count($previewData)
        ];
    }

    // Restore and utility methods
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
                MutationItem::withTrashed()->where('mutation_id', $sourceId)->restore();
                return true;
            }
        }
        return false;
    }

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

    public function restoreMissingStock($type, $sourceId)
    {
        if ($type === 'purchase') {
            $purchase = SupplyPurchase::where('id', $sourceId)->whereNull('deleted_at')->first();
            if ($purchase) {
                $batch = $purchase->batch;
                $batchDate = $batch ? $batch->date : now();

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
                        'quantity_in' => $purchase->converted_quantity,
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
        }
        return false;
    }

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

    public function rollback($auditId)
    {
        $audit = DataAuditTrail::findOrFail($auditId);
        $modelClass = $audit->model_type;
        $model = $modelClass::findOrFail($audit->model_id);
        $before = $audit->before_data;
        $after = $model->toArray();
        $model->fill($before);
        $model->save();

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
            'supply_stocks' => SupplyStock::withTrashed()->get()->toArray(),
            'supply_purchases' => SupplyPurchase::withTrashed()->get()->toArray(),
            'mutations' => Mutation::withTrashed()->get()->toArray(),
            'mutation_items' => MutationItem::withTrashed()->get()->toArray(),
            'current_supplies' => CurrentSupply::all()->toArray(),
        ];
        Storage::put($filename, json_encode($data, JSON_PRETTY_PRINT));
        return $filename;
    }

    public function restoreFromBackup($filename)
    {
        $dir = 'supply-backups';
        $path = "$dir/$filename";
        if (!Storage::exists($path)) {
            throw new \Exception("Backup file not found: $filename");
        }
        $json = Storage::get($path);
        $data = json_decode($json, true);

        DB::transaction(function () use ($data) {
            SupplyStock::truncate();
            SupplyPurchase::truncate();
            Mutation::truncate();
            MutationItem::truncate();
            CurrentSupply::where('type', 'supply')->delete();

            if (!empty($data['supply_stocks'])) {
                SupplyStock::insert($data['supply_stocks']);
            }
            if (!empty($data['supply_purchases'])) {
                SupplyPurchase::insert($data['supply_purchases']);
            }
            if (!empty($data['mutations'])) {
                Mutation::insert($data['mutations']);
            }
            if (!empty($data['mutation_items'])) {
                MutationItem::insert($data['mutation_items']);
            }
            if (!empty($data['current_supplies'])) {
                CurrentSupply::insert($data['current_supplies']);
            }
        });
        return true;
    }
}

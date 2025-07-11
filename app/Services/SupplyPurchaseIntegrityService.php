<?php

namespace App\Services;

use App\Models\SupplyPurchaseBatch;
use App\Models\SupplyPurchase;
use App\Models\SupplyStock;
use App\Models\CurrentSupply;
use App\Models\Farm;
use App\Models\Supply;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use function App\Helpers\logInfoIfDebug;
use function App\Helpers\logDebugIfDebug;
use function App\Helpers\logWarningIfDebug;
use function App\Helpers\logErrorIfDebug;

class SupplyPurchaseIntegrityService
{
    /**
     * Validate data integrity before allowing status change to 'arrived'
     * 
     * @param SupplyPurchaseBatch $batch
     * @return array
     */
    public function validateArrivalIntegrity(SupplyPurchaseBatch $batch): array
    {
        try {
            logDebugIfDebug('SupplyPurchaseIntegrityService: Starting arrival integrity validation', [
                'batch_id' => $batch->id,
                'current_status' => $batch->status
            ]);

            // 1. Check if all SupplyPurchases exist
            $purchaseValidation = $this->validateSupplyPurchases($batch);
            if (!$purchaseValidation['valid']) {
                return $purchaseValidation;
            }

            // 2. Validate SupplyStock creation/update for each purchase
            $stockValidation = $this->validateSupplyStocks($batch);
            if (!$stockValidation['valid']) {
                return $stockValidation;
            }

            // 3. Validate CurrentSupply calculations
            $currentSupplyValidation = $this->validateCurrentSupplyBalance($batch);
            if (!$currentSupplyValidation['valid']) {
                return $currentSupplyValidation;
            }

            // 4. Cross-validate data consistency
            $consistencyValidation = $this->validateDataConsistency($batch);
            if (!$consistencyValidation['valid']) {
                return $consistencyValidation;
            }

            logInfoIfDebug('SupplyPurchaseIntegrityService: All validations passed', [
                'batch_id' => $batch->id
            ]);

            return [
                'valid' => true,
                'message' => 'All integrity checks passed',
                'batch_id' => $batch->id,
                'checks_performed' => [
                    'supply_purchases' => $purchaseValidation,
                    'supply_stocks' => $stockValidation,
                    'current_supplies' => $currentSupplyValidation,
                    'data_consistency' => $consistencyValidation
                ]
            ];
        } catch (Exception $e) {
            logErrorIfDebug('SupplyPurchaseIntegrityService: Validation failed with exception', [
                'batch_id' => $batch->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'valid' => false,
                'error' => 'Validation failed: ' . $e->getMessage(),
                'batch_id' => $batch->id,
                'error_type' => 'exception'
            ];
        }
    }

    /**
     * Validate that all SupplyPurchases exist and have correct data
     */
    private function validateSupplyPurchases(SupplyPurchaseBatch $batch): array
    {
        $purchases = $batch->supplyPurchases;

        if ($purchases->isEmpty()) {
            return [
                'valid' => false,
                'error' => 'No supply purchases found for this batch',
                'batch_id' => $batch->id,
                'error_type' => 'missing_purchases'
            ];
        }

        $errors = [];
        foreach ($purchases as $purchase) {
            // Validate required fields
            if (!$purchase->supply_id || !$purchase->farm_id) {
                $errors[] = "Purchase {$purchase->id}: Missing supply_id or farm_id";
            }

            if (!$purchase->converted_quantity || $purchase->converted_quantity <= 0) {
                $errors[] = "Purchase {$purchase->id}: Invalid converted_quantity ({$purchase->converted_quantity})";
            }

            // Validate related models exist
            if (!Supply::find($purchase->supply_id)) {
                $errors[] = "Purchase {$purchase->id}: Supply {$purchase->supply_id} not found";
            }

            if (!Farm::find($purchase->farm_id)) {
                $errors[] = "Purchase {$purchase->id}: Farm {$purchase->farm_id} not found";
            }
        }

        if (!empty($errors)) {
            return [
                'valid' => false,
                'error' => 'Supply purchase validation failed',
                'batch_id' => $batch->id,
                'error_type' => 'invalid_purchases',
                'details' => $errors
            ];
        }

        return [
            'valid' => true,
            'message' => 'All supply purchases are valid',
            'purchases_count' => $purchases->count()
        ];
    }

    /**
     * Validate SupplyStock records for the batch
     */
    private function validateSupplyStocks(SupplyPurchaseBatch $batch): array
    {
        $errors = [];
        $stockData = [];

        foreach ($batch->supplyPurchases as $purchase) {
            // Check if SupplyStock exists for this purchase
            $supplyStock = SupplyStock::where('supply_purchase_id', $purchase->id)->first();

            if (!$supplyStock) {
                $errors[] = "Purchase {$purchase->id}: No SupplyStock record found";
                continue;
            }

            // Validate SupplyStock data
            if ($supplyStock->quantity_in != $purchase->converted_quantity) {
                $errors[] = "Purchase {$purchase->id}: SupplyStock quantity_in ({$supplyStock->quantity_in}) doesn't match purchase converted_quantity ({$purchase->converted_quantity})";
            }

            if ($supplyStock->farm_id != $purchase->farm_id) {
                $errors[] = "Purchase {$purchase->id}: SupplyStock farm_id ({$supplyStock->farm_id}) doesn't match purchase farm_id ({$purchase->farm_id})";
            }

            if ($supplyStock->supply_id != $purchase->supply_id) {
                $errors[] = "Purchase {$purchase->id}: SupplyStock supply_id ({$supplyStock->supply_id}) doesn't match purchase supply_id ({$purchase->supply_id})";
            }

            $stockData[] = [
                'purchase_id' => $purchase->id,
                'supply_stock_id' => $supplyStock->id,
                'quantity_in' => $supplyStock->quantity_in,
                'quantity_used' => $supplyStock->quantity_used,
                'quantity_mutated' => $supplyStock->quantity_mutated,
                'available' => $supplyStock->quantity_in - $supplyStock->quantity_used - $supplyStock->quantity_mutated
            ];
        }

        if (!empty($errors)) {
            return [
                'valid' => false,
                'error' => 'SupplyStock validation failed',
                'batch_id' => $batch->id,
                'error_type' => 'invalid_supply_stocks',
                'details' => $errors
            ];
        }

        return [
            'valid' => true,
            'message' => 'All SupplyStock records are valid',
            'stock_data' => $stockData
        ];
    }

    /**
     * Validate CurrentSupply balance calculations
     */
    private function validateCurrentSupplyBalance(SupplyPurchaseBatch $batch): array
    {
        $errors = [];
        $currentSupplyData = [];

        // Group purchases by farm and supply for validation
        $groupedPurchases = $batch->supplyPurchases->groupBy(function ($purchase) {
            return $purchase->farm_id . '_' . $purchase->supply_id;
        });

        foreach ($groupedPurchases as $key => $purchases) {
            [$farmId, $supplyId] = explode('_', $key);

            // Calculate expected CurrentSupply quantity
            $expectedQuantity = $this->calculateExpectedCurrentSupplyQuantity($farmId, $supplyId);

            // Get actual CurrentSupply
            $currentSupply = CurrentSupply::where('farm_id', $farmId)
                ->where('item_id', $supplyId)
                ->first();

            if (!$currentSupply) {
                $errors[] = "Farm {$farmId}, Supply {$supplyId}: No CurrentSupply record found";
                continue;
            }

            if (abs($currentSupply->quantity - $expectedQuantity) > 0.01) { // Allow small floating point differences
                $errors[] = "Farm {$farmId}, Supply {$supplyId}: CurrentSupply quantity ({$currentSupply->quantity}) doesn't match expected ({$expectedQuantity})";
            }

            $currentSupplyData[] = [
                'farm_id' => $farmId,
                'supply_id' => $supplyId,
                'current_supply_id' => $currentSupply->id,
                'actual_quantity' => $currentSupply->quantity,
                'expected_quantity' => $expectedQuantity,
                'difference' => $currentSupply->quantity - $expectedQuantity
            ];
        }

        if (!empty($errors)) {
            return [
                'valid' => false,
                'error' => 'CurrentSupply validation failed',
                'batch_id' => $batch->id,
                'error_type' => 'invalid_current_supply',
                'details' => $errors
            ];
        }

        return [
            'valid' => true,
            'message' => 'All CurrentSupply calculations are correct',
            'current_supply_data' => $currentSupplyData
        ];
    }

    /**
     * Calculate expected CurrentSupply quantity based on SupplyStock records
     */
    private function calculateExpectedCurrentSupplyQuantity(string $farmId, string $supplyId): float
    {
        return SupplyStock::join('supply_purchases', 'supply_stocks.supply_purchase_id', '=', 'supply_purchases.id')
            ->join('supply_purchase_batches', 'supply_purchases.supply_purchase_batch_id', '=', 'supply_purchase_batches.id')
            ->where('supply_stocks.farm_id', $farmId)
            ->where('supply_stocks.supply_id', $supplyId)
            ->where('supply_purchase_batches.status', SupplyPurchaseBatch::STATUS_ARRIVED)
            ->whereNull('supply_stocks.deleted_at')
            ->sum(DB::raw('supply_stocks.quantity_in - supply_stocks.quantity_used - supply_stocks.quantity_mutated'));
    }

    /**
     * Validate overall data consistency
     */
    private function validateDataConsistency(SupplyPurchaseBatch $batch): array
    {
        $errors = [];

        // Check that batch status is appropriate for arrival
        if ($batch->status === SupplyPurchaseBatch::STATUS_ARRIVED) {
            // If already arrived, validate that all data is consistent
            foreach ($batch->supplyPurchases as $purchase) {
                $supplyStock = SupplyStock::where('supply_purchase_id', $purchase->id)->first();
                if (!$supplyStock) {
                    $errors[] = "Batch already marked as arrived but SupplyStock missing for purchase {$purchase->id}";
                }
            }
        }

        // Check for orphaned SupplyStock records
        $orphanedStocks = SupplyStock::whereIn('supply_purchase_id', $batch->supplyPurchases->pluck('id'))
            ->whereDoesntHave('supplyPurchase')
            ->count();

        if ($orphanedStocks > 0) {
            $errors[] = "Found {$orphanedStocks} orphaned SupplyStock records";
        }

        if (!empty($errors)) {
            return [
                'valid' => false,
                'error' => 'Data consistency validation failed',
                'batch_id' => $batch->id,
                'error_type' => 'data_inconsistency',
                'details' => $errors
            ];
        }

        return [
            'valid' => true,
            'message' => 'Data consistency checks passed'
        ];
    }

    /**
     * Rollback arrival changes if validation fails
     */
    public function rollbackArrivalChanges(SupplyPurchaseBatch $batch): array
    {
        try {
            DB::beginTransaction();

            logInfoIfDebug('SupplyPurchaseIntegrityService: Starting rollback of arrival changes', [
                'batch_id' => $batch->id
            ]);

            $rollbackActions = [];

            // 1. Remove SupplyStock records created for this batch
            foreach ($batch->supplyPurchases as $purchase) {
                $deletedStocks = SupplyStock::where('supply_purchase_id', $purchase->id)->delete();
                if ($deletedStocks > 0) {
                    $rollbackActions[] = "Deleted {$deletedStocks} SupplyStock records for purchase {$purchase->id}";
                }
            }

            // 2. Recalculate CurrentSupply for affected farm/supply combinations
            $affectedCombinations = $batch->supplyPurchases->groupBy(function ($purchase) {
                return $purchase->farm_id . '_' . $purchase->supply_id;
            });

            foreach ($affectedCombinations as $key => $purchases) {
                [$farmId, $supplyId] = explode('_', $key);

                $farm = Farm::find($farmId);
                $supply = Supply::find($supplyId);

                if ($farm && $supply) {
                    $this->recalculateCurrentSupply($farm, $supply);
                    $rollbackActions[] = "Recalculated CurrentSupply for farm {$farmId}, supply {$supplyId}";
                }
            }

            // 3. Reset batch status if it was changed
            if ($batch->status === SupplyPurchaseBatch::STATUS_ARRIVED) {
                $batch->status = SupplyPurchaseBatch::STATUS_DRAFT; // or previous status
                $batch->save();
                $rollbackActions[] = "Reset batch status to draft";
            }

            DB::commit();

            logInfoIfDebug('SupplyPurchaseIntegrityService: Rollback completed successfully', [
                'batch_id' => $batch->id,
                'actions_performed' => count($rollbackActions)
            ]);

            return [
                'success' => true,
                'message' => 'Arrival changes rolled back successfully',
                'batch_id' => $batch->id,
                'actions_performed' => $rollbackActions
            ];
        } catch (Exception $e) {
            DB::rollBack();

            logErrorIfDebug('SupplyPurchaseIntegrityService: Rollback failed', [
                'batch_id' => $batch->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => 'Rollback failed: ' . $e->getMessage(),
                'batch_id' => $batch->id
            ];
        }
    }

    /**
     * Recalculate CurrentSupply quantity
     */
    private function recalculateCurrentSupply(Farm $farm, Supply $supply): void
    {
        $totalQuantity = SupplyStock::join('supply_purchases', 'supply_stocks.supply_purchase_id', '=', 'supply_purchases.id')
            ->join('supply_purchase_batches', 'supply_purchases.supply_purchase_batch_id', '=', 'supply_purchase_batches.id')
            ->where('supply_stocks.farm_id', $farm->id)
            ->where('supply_stocks.supply_id', $supply->id)
            ->where('supply_purchase_batches.status', SupplyPurchaseBatch::STATUS_ARRIVED)
            ->whereNull('supply_stocks.deleted_at')
            ->sum(DB::raw('supply_stocks.quantity_in - supply_stocks.quantity_used - supply_stocks.quantity_mutated'));

        // Get default unit_id from supply
        $unitId = null;
        if (isset($supply->data['conversion_units']) && is_array($supply->data['conversion_units'])) {
            $units = collect($supply->data['conversion_units']);
            $defaultUnit = $units->firstWhere('is_default_purchase', true) ?? $units->first();
            if ($defaultUnit) {
                $unitId = $defaultUnit['unit_id'];
            }
        }

        // If no unit found, use a fallback
        if (!$unitId) {
            $unitId = \App\Models\Unit::first()?->id;
        }

        $currentSupply = CurrentSupply::firstOrNew([
            'farm_id' => $farm->id,
            'item_id' => $supply->id,
        ]);

        $currentSupply->quantity = $totalQuantity;
        $currentSupply->unit_id = $unitId;
        $currentSupply->type = 'supply';
        $currentSupply->status = 'active';
        $currentSupply->save();

        logDebugIfDebug('SupplyPurchaseIntegrityService: CurrentSupply recalculated', [
            'farm_id' => $farm->id,
            'supply_id' => $supply->id,
            'new_quantity' => $totalQuantity,
            'unit_id' => $unitId
        ]);
    }

    /**
     * Get detailed integrity report for a batch
     */
    public function getIntegrityReport(SupplyPurchaseBatch $batch): array
    {
        $report = [
            'batch_id' => $batch->id,
            'batch_status' => $batch->status,
            'timestamp' => now()->toISOString(),
            'summary' => [],
            'details' => []
        ];

        // Get validation results for each component
        $purchaseValidation = $this->validateSupplyPurchases($batch);
        $stockValidation = $this->validateSupplyStocks($batch);
        $currentSupplyValidation = $this->validateCurrentSupplyBalance($batch);
        $consistencyValidation = $this->validateDataConsistency($batch);

        $report['details'] = [
            'supply_purchases' => $purchaseValidation,
            'supply_stocks' => $stockValidation,
            'current_supplies' => $currentSupplyValidation,
            'data_consistency' => $consistencyValidation
        ];

        // Generate summary
        $allValid = $purchaseValidation['valid'] && $stockValidation['valid'] &&
            $currentSupplyValidation['valid'] && $consistencyValidation['valid'];

        $report['summary'] = [
            'overall_status' => $allValid ? 'VALID' : 'INVALID',
            'checks_passed' => array_sum([
                $purchaseValidation['valid'] ? 1 : 0,
                $stockValidation['valid'] ? 1 : 0,
                $currentSupplyValidation['valid'] ? 1 : 0,
                $consistencyValidation['valid'] ? 1 : 0
            ]),
            'total_checks' => 4,
            'can_proceed_to_arrived' => $allValid
        ];

        return $report;
    }
}

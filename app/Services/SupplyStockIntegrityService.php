<?php

namespace App\Services;

use App\Models\SupplyStock;
use App\Models\CurrentSupply;
use App\Models\SupplyUsageDetail;
use App\Models\SupplyMutationItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SupplyStockIntegrityService
{
    /**
     * Recalculate and sync all supply stock quantities
     */
    public function recalculateAllStockQuantities(): array
    {
        $results = [
            'processed' => 0,
            'fixed' => 0,
            'errors' => []
        ];

        DB::beginTransaction();
        try {
            $stocks = SupplyStock::with(['supplyUsageDetails', 'mutationDetails'])->get();

            foreach ($stocks as $stock) {
                try {
                    $this->recalculateStockQuantity($stock);
                    $results['processed']++;
                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'stock_id' => $stock->id,
                        'error' => $e->getMessage()
                    ];
                }
            }

            // Sync CurrentSupply
            $this->syncAllCurrentSupply();

            DB::commit();
            Log::info('Supply stock integrity check completed', $results);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Supply stock integrity check failed', ['error' => $e->getMessage()]);
            throw $e;
        }

        return $results;
    }

    /**
     * Recalculate individual stock quantity
     */
    public function recalculateStockQuantity(SupplyStock $stock): void
    {
        // Calculate actual used quantity from usage details
        $actualUsed = $stock->supplyUsageDetails()->sum('converted_quantity');

        // Calculate actual mutated quantity from mutation details
        $actualMutated = $stock->mutationDetails()->sum('converted_quantity');

        // Update stock with actual values
        $stock->update([
            'quantity_used' => $actualUsed,
            'quantity_mutated' => $actualMutated
        ]);

        // Verify calculation
        $calculatedAvailable = $stock->quantity_in - $actualUsed - $actualMutated;
        if ($calculatedAvailable < 0) {
            Log::warning('Negative available quantity detected', [
                'stock_id' => $stock->id,
                'quantity_in' => $stock->quantity_in,
                'quantity_used' => $actualUsed,
                'quantity_mutated' => $actualMutated,
                'calculated_available' => $calculatedAvailable
            ]);
        }
    }

    /**
     * Sync CurrentSupply with actual stock quantities
     */
    public function syncAllCurrentSupply(): void
    {
        $stockSummaries = SupplyStock::selectRaw('
            farm_id,
            supply_id,
            SUM(quantity_in) as total_in,
            SUM(quantity_used) as total_used,
            SUM(quantity_mutated) as total_mutated
        ')
            ->groupBy('farm_id', 'supply_id')
            ->get();

        foreach ($stockSummaries as $summary) {
            $availableQuantity = $summary->total_in - $summary->total_used - $summary->total_mutated;

            CurrentSupply::updateOrCreate(
                [
                    'farm_id' => $summary->farm_id,
                    'supply_id' => $summary->supply_id
                ],
                [
                    'quantity' => max(0, $availableQuantity),
                    'updated_at' => now()
                ]
            );
        }
    }

    /**
     * Validate stock integrity for specific supply
     */
    public function validateSupplyStockIntegrity(string $supplyId, string $farmId): array
    {
        $stocks = SupplyStock::where('supply_id', $supplyId)
            ->where('farm_id', $farmId)
            ->with(['supplyUsageDetails', 'mutationDetails'])
            ->get();

        $issues = [];
        $totalIn = 0;
        $totalUsed = 0;
        $totalMutated = 0;

        foreach ($stocks as $stock) {
            $actualUsed = $stock->supplyUsageDetails()->sum('converted_quantity');
            $actualMutated = $stock->mutationDetails()->sum('converted_quantity');

            $totalIn += $stock->quantity_in;
            $totalUsed += $actualUsed;
            $totalMutated += $actualMutated;

            // Check for discrepancies
            if ($stock->quantity_used != $actualUsed) {
                $issues[] = [
                    'type' => 'usage_mismatch',
                    'stock_id' => $stock->id,
                    'stored_used' => $stock->quantity_used,
                    'actual_used' => $actualUsed,
                    'difference' => $actualUsed - $stock->quantity_used
                ];
            }

            if ($stock->quantity_mutated != $actualMutated) {
                $issues[] = [
                    'type' => 'mutation_mismatch',
                    'stock_id' => $stock->id,
                    'stored_mutated' => $stock->quantity_mutated,
                    'actual_mutated' => $actualMutated,
                    'difference' => $actualMutated - $stock->quantity_mutated
                ];
            }
        }

        // Check CurrentSupply consistency
        $currentSupply = CurrentSupply::where('supply_id', $supplyId)
            ->where('farm_id', $farmId)
            ->first();

        $calculatedAvailable = $totalIn - $totalUsed - $totalMutated;

        if ($currentSupply && abs($currentSupply->quantity - $calculatedAvailable) > 0.01) {
            $issues[] = [
                'type' => 'current_supply_mismatch',
                'current_supply_quantity' => $currentSupply->quantity,
                'calculated_available' => $calculatedAvailable,
                'difference' => $calculatedAvailable - $currentSupply->quantity
            ];
        }

        return [
            'supply_id' => $supplyId,
            'farm_id' => $farmId,
            'total_in' => $totalIn,
            'total_used' => $totalUsed,
            'total_mutated' => $totalMutated,
            'calculated_available' => $calculatedAvailable,
            'issues' => $issues,
            'is_valid' => empty($issues)
        ];
    }

    /**
     * Fix stock quantity discrepancies
     */
    public function fixStockDiscrepancies(array $validationResult): array
    {
        if ($validationResult['is_valid']) {
            return ['message' => 'No discrepancies found'];
        }

        $fixed = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($validationResult['issues'] as $issue) {
                try {
                    switch ($issue['type']) {
                        case 'usage_mismatch':
                            SupplyStock::where('id', $issue['stock_id'])
                                ->update(['quantity_used' => $issue['actual_used']]);
                            $fixed++;
                            break;

                        case 'mutation_mismatch':
                            SupplyStock::where('id', $issue['stock_id'])
                                ->update(['quantity_mutated' => $issue['actual_mutated']]);
                            $fixed++;
                            break;

                        case 'current_supply_mismatch':
                            CurrentSupply::where('supply_id', $validationResult['supply_id'])
                                ->where('farm_id', $validationResult['farm_id'])
                                ->update(['quantity' => $issue['calculated_available']]);
                            $fixed++;
                            break;
                    }
                } catch (\Exception $e) {
                    $errors[] = [
                        'issue' => $issue,
                        'error' => $e->getMessage()
                    ];
                }
            }

            DB::commit();
            Log::info('Stock discrepancies fixed', ['fixed' => $fixed, 'errors' => $errors]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to fix stock discrepancies', ['error' => $e->getMessage()]);
            throw $e;
        }

        return [
            'fixed' => $fixed,
            'errors' => $errors
        ];
    }
}

<?php

namespace App\Services;

use App\Models\SupplyStock;
use App\Models\SupplyUsage;
use App\Models\SupplyMutation;
use App\Models\CurrentSupply;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SupplyPerformanceOptimizationService
{
    /**
     * Optimize stock queries with eager loading and caching
     */
    public function getOptimizedStockQuery(string $farmId, string $supplyId)
    {
        $cacheKey = "supply_stock_{$farmId}_{$supplyId}";

        return Cache::remember($cacheKey, 300, function () use ($farmId, $supplyId) {
            return SupplyStock::where('farm_id', $farmId)
                ->where('supply_id', $supplyId)
                ->whereRaw('(quantity_in - quantity_used - quantity_mutated) > 0')
                ->orderBy('date')
                ->orderBy('created_at')
                ->with(['supplyUsageDetails', 'mutationDetails'])
                ->get();
        });
    }

    /**
     * Batch update CurrentSupply to avoid N+1 queries
     */
    public function batchUpdateCurrentSupply(array $updates): void
    {
        if (empty($updates)) {
            return;
        }

        DB::beginTransaction();
        try {
            foreach ($updates as $update) {
                CurrentSupply::updateOrCreate(
                    [
                        'farm_id' => $update['farm_id'],
                        'supply_id' => $update['supply_id']
                    ],
                    [
                        'quantity' => $update['quantity'],
                        'updated_at' => now()
                    ]
                );
            }

            DB::commit();

            // Clear related caches
            foreach ($updates as $update) {
                $cacheKey = "supply_stock_{$update['farm_id']}_{$update['supply_id']}";
                Cache::forget($cacheKey);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Batch CurrentSupply update failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Optimize FIFO stock selection with bulk operations
     */
    public function getFifoStocksOptimized(string $farmId, string $supplyId, float $requiredQuantity): array
    {
        $stocks = $this->getOptimizedStockQuery($farmId, $supplyId);

        $selectedStocks = [];
        $remainingQuantity = $requiredQuantity;

        foreach ($stocks as $stock) {
            if ($remainingQuantity <= 0) break;

            $available = $stock->quantity_in - $stock->quantity_used - $stock->quantity_mutated;
            $takeQuantity = min($available, $remainingQuantity);

            $selectedStocks[] = [
                'stock' => $stock,
                'take_quantity' => $takeQuantity,
                'available_before' => $available
            ];

            $remainingQuantity -= $takeQuantity;
        }

        return [
            'selected_stocks' => $selectedStocks,
            'remaining_quantity' => $remainingQuantity,
            'can_fulfill' => $remainingQuantity <= 0
        ];
    }

    /**
     * Bulk stock updates to reduce database calls
     */
    public function bulkUpdateStocks(array $stockUpdates): void
    {
        if (empty($stockUpdates)) {
            return;
        }

        DB::beginTransaction();
        try {
            $currentSupplyUpdates = [];

            foreach ($stockUpdates as $update) {
                $stock = $update['stock'];
                $quantityChange = $update['quantity_change'];
                $updateType = $update['type']; // 'used' or 'mutated'

                // Update stock
                $stock->{"quantity_{$updateType}"} += $quantityChange;
                $stock->save();

                // Prepare CurrentSupply update
                $key = "{$stock->farm_id}_{$stock->supply_id}";
                if (!isset($currentSupplyUpdates[$key])) {
                    $currentSupplyUpdates[$key] = [
                        'farm_id' => $stock->farm_id,
                        'supply_id' => $stock->supply_id,
                        'quantity' => 0
                    ];
                }
                $currentSupplyUpdates[$key]['quantity'] -= $quantityChange;
            }

            // Batch update CurrentSupply
            $this->batchUpdateCurrentSupply(array_values($currentSupplyUpdates));

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk stock update failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Optimize supply usage processing with bulk operations
     */
    public function processSupplyUsageOptimized(SupplyUsage $usage, array $items): array
    {
        $stockUpdates = [];
        $usageDetails = [];
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($items as $item) {
                $supplyId = $item['supply_id'];
                $requiredQuantity = floatval($item['converted_quantity'] ?? 0);

                if ($requiredQuantity <= 0) continue;

                // Get optimized FIFO stocks
                $fifoResult = $this->getFifoStocksOptimized(
                    $usage->farm_id,
                    $supplyId,
                    $requiredQuantity
                );

                if (!$fifoResult['can_fulfill']) {
                    $errors[] = "Insufficient stock for supply ID: {$supplyId}";
                    continue;
                }

                // Prepare stock updates
                foreach ($fifoResult['selected_stocks'] as $stockData) {
                    $stock = $stockData['stock'];
                    $takeQuantity = $stockData['take_quantity'];

                    $stockUpdates[] = [
                        'stock' => $stock,
                        'quantity_change' => $takeQuantity,
                        'type' => 'used'
                    ];

                    // Prepare usage detail
                    $usageDetails[] = [
                        'supply_usage_id' => $usage->id,
                        'supply_stock_id' => $stock->id,
                        'supply_id' => $supplyId,
                        'quantity_taken' => $item['quantity_taken'] ?? $takeQuantity,
                        'unit_id' => $item['unit_id'] ?? null,
                        'converted_unit_id' => $item['converted_unit_id'] ?? $item['unit_id'] ?? null,
                        'converted_quantity' => $takeQuantity,
                        'price_per_unit' => $item['price_per_unit'] ?? null,
                        'price_per_converted_unit' => $item['price_per_converted_unit'] ?? null,
                        'total_price' => $item['total_price'] ?? null,
                        'notes' => $item['notes'] ?? null,
                        'batch_number' => $item['batch_number'] ?? ($stock->batch_number ?? null),
                        'expiry_date' => $item['expiry_date'] ?? ($stock->expiry_date ?? null),
                        'created_by' => \Illuminate\Support\Facades\Auth::id() ?? null,
                    ];
                }
            }

            if (!empty($errors)) {
                throw new \Exception(implode(', ', $errors));
            }

            // Bulk update stocks
            $this->bulkUpdateStocks($stockUpdates);

            // Bulk insert usage details
            if (!empty($usageDetails)) {
                DB::table('supply_usage_details')->insert($usageDetails);
            }

            DB::commit();

            return [
                'success' => true,
                'processed_items' => count($items),
                'stock_updates' => count($stockUpdates),
                'usage_details' => count($usageDetails)
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Optimized supply usage processing failed', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Clear all supply-related caches
     */
    public function clearSupplyCaches(string $farmId = null, string $supplyId = null): void
    {
        if ($farmId && $supplyId) {
            $cacheKey = "supply_stock_{$farmId}_{$supplyId}";
            Cache::forget($cacheKey);
        } else {
            // Clear all supply-related caches (use with caution)
            Cache::flush();
        }
    }

    /**
     * Get supply statistics with caching
     */
    public function getSupplyStatistics(string $farmId, string $supplyId = null): array
    {
        $cacheKey = "supply_stats_{$farmId}" . ($supplyId ? "_{$supplyId}" : '');

        return Cache::remember($cacheKey, 600, function () use ($farmId, $supplyId) {
            $query = SupplyStock::where('farm_id', $farmId);

            if ($supplyId) {
                $query->where('supply_id', $supplyId);
            }

            $stats = $query->selectRaw('
                supply_id,
                SUM(quantity_in) as total_in,
                SUM(quantity_used) as total_used,
                SUM(quantity_mutated) as total_mutated,
                COUNT(*) as stock_count
            ')
                ->groupBy('supply_id')
                ->get();

            return $stats->toArray();
        });
    }
}

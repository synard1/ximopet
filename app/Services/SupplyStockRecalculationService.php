<?php

namespace App\Services;

use App\Models\SupplyStock;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SupplyStockRecalculationService
{
    /**
     * Recalculate all quantity fields for a single SupplyStock
     * @param SupplyStock $stock
     * @param bool $save
     * @return SupplyStock
     */
    public static function recalculateStock(SupplyStock $stock, bool $save = true): SupplyStock
    {
        // Calculate actual used quantity from usage details
        $actualUsed = $stock->supplyUsageDetails()->sum('converted_quantity');
        // Calculate actual mutated quantity from mutation details
        $actualMutated = $stock->mutationDetails()->sum('converted_quantity');
        // Reserved: if available, otherwise 0
        $actualReserved = $stock->quantity_reserved ?? 0;
        // Calculate available
        $calculatedAvailable = $stock->quantity_in - $actualUsed - $actualMutated - $actualReserved;
        $stock->quantity_used = $actualUsed;
        $stock->quantity_mutated = $actualMutated;
        $stock->quantity_reserved = $actualReserved;
        $stock->quantity_available = $calculatedAvailable;
        if ($save) {
            $stock->save();
        }
        return $stock;
    }

    /**
     * Recalculate all stocks matching a query (closure or builder)
     * @param \Illuminate\Database\Eloquent\Builder|callable $query
     * @param bool $save
     * @return int Number of stocks processed
     */
    public static function recalculateStocks($query, bool $save = true): int
    {
        $builder = is_callable($query) ? $query(SupplyStock::query()) : $query;
        $count = 0;
        foreach ($builder->get() as $stock) {
            self::recalculateStock($stock, $save);
            $count++;
        }
        return $count;
    }

    /**
     * Recalculate all stocks (optionally by filter)
     * @param array $filter (e.g. ['farm_id' => ..., 'supply_id' => ...])
     * @param bool $save
     * @return int
     */
    public static function recalculateAll(array $filter = [], bool $save = true): int
    {
        $query = SupplyStock::query();
        foreach ($filter as $key => $val) {
            $query->where($key, $val);
        }
        return self::recalculateStocks($query, $save);
    }
}

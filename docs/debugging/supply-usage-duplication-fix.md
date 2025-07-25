# Supply Usage Duplication Fix

## Problem Description

The Supply Usage component was showing duplicate entries for the same supply type. For example, "Biocid" appeared twice in the dropdown with different supply_stock_id values:

-   `9f60a751-3065-45e5-b1cb-158a80acb9c1` - Biocid with available_stock: 275.0
-   `9f6162fb-a04d-4499-bc9d-123de8b63c50` - Biocid with available_stock: 250.0

This happened because the system was returning individual SupplyStock records instead of aggregating supplies with the same supply_id.

## Root Cause

The `loadAvailableSupplies()` method in `app/Livewire/MasterData/Supply/Usage.php` was mapping each SupplyStock record individually without grouping supplies by supply_id. This caused:

1. Multiple entries for the same supply type
2. Confusing UI with duplicate supply names
3. Inconsistent stock validation
4. Poor user experience

## Solution

### 1. Supply Aggregation

Modified `loadAvailableSupplies()` method to group supplies by supply_id and aggregate their quantities:

```php
// Group supplies by supply_id to aggregate stocks
$groupedSupplies = $suppliesWithStock->groupBy('supply_id');

$this->availableSupplies = $groupedSupplies->map(function ($supplyStocks, $supplyId) {
    // Calculate aggregated quantities
    $totalQuantityIn = $supplyStocks->sum('quantity_in');
    $totalQuantityUsed = $supplyStocks->sum('quantity_used');
    $totalQuantityMutated = $supplyStocks->sum('quantity_mutated');
    $totalAvailableStock = $totalQuantityIn - $totalQuantityUsed - $totalQuantityMutated;

    // Combine batch numbers and expiry dates
    $batchNumbers = $supplyStocks->pluck('batch_number')->filter()->unique()->values()->toArray();
    $expiryDates = $supplyStocks->pluck('expiry_date')->filter()->unique()->values()->toArray();

    return [
        'id' => $firstStock->id, // Use first stock ID as representative
        'supply_id' => $supplyId,
        'supply_name' => $supply->name ?? '',
        'available_stock' => $totalAvailableStock,
        'batch_number' => implode(', ', $batchNumbers),
        'expiry_date' => implode(', ', array_map(function($date) {
            return $date instanceof \Carbon\Carbon ? $date->format('Y-m-d') : $date;
        }, $expiryDates)),
        'stock_count' => $supplyStocks->count(),
        'stock_ids' => $supplyStocks->pluck('id')->toArray(),
    ];
})->values()->toArray();
```

### 2. Smart Stock Selection

Added `getBestAvailableStockId()` method to intelligently select the best stock when user chooses an aggregated supply:

```php
private function getBestAvailableStockId($supplyId, $requiredQuantity = 0)
{
    $availableStocks = SupplyStock::where('supply_id', $supplyId)
        ->where('farm_id', $this->farm_id)
        ->whereRaw('(quantity_in - quantity_used - quantity_mutated) > 0')
        ->orderByRaw('(quantity_in - quantity_used - quantity_mutated) DESC')
        ->get();

    // Return stock with most available quantity or one that can fulfill requirement
    foreach ($availableStocks as $stock) {
        $availableQty = $stock->quantity_in - $stock->quantity_used - $stock->quantity_mutated;
        if ($availableQty >= $requiredQuantity) {
            return $stock->id;
        }
    }

    return $availableStocks->first()->id;
}
```

### 3. Enhanced Item Selection

Updated `updatedItems()` method to handle aggregated supplies:

```php
if ($field === 'supply_stock_id' && $value) {
    $selectedSupply = collect($this->availableSupplies)->firstWhere('id', $value);

    if ($selectedSupply) {
        // Use aggregated data
        $this->items[$index]['supply_id'] = $selectedSupply['supply_id'];
        $this->items[$index]['available_stock'] = $selectedSupply['available_stock'];

        // Get best available stock ID
        $bestStockId = $this->getBestAvailableStockId($selectedSupply['supply_id']);
        if ($bestStockId) {
            $this->items[$index]['supply_stock_id'] = $bestStockId;
        }
    }
}
```

### 4. Improved Stock Validation

Enhanced `getAvailableStockFromSupplyStock()` to handle aggregated supplies:

```php
private function getAvailableStockFromSupplyStock($supplyStockId)
{
    // First check if this is an aggregated supply
    $aggregatedSupply = collect($this->availableSupplies)->firstWhere('id', $supplyStockId);
    if ($aggregatedSupply) {
        return $aggregatedSupply['available_stock'];
    }

    // Fallback to direct database query
    $supplyStock = SupplyStock::find($supplyStockId);
    return $supplyStock ? ($supplyStock->quantity_in - $supplyStock->quantity_used - $supplyStock->quantity_mutated) : 0;
}
```

## Benefits

1. **Eliminated Duplication**: Each supply type now appears only once in the dropdown
2. **Accurate Stock Display**: Shows total available stock across all batches
3. **Better UX**: Cleaner interface with no confusing duplicates
4. **Smart Allocation**: Automatically selects the best available stock
5. **Comprehensive Logging**: Added detailed logging for debugging and monitoring

## Logging

Added comprehensive logging to track the aggregation process:

-   Raw supplies before aggregation
-   Grouping information and calculations
-   Final aggregated supplies
-   Stock selection process
-   User interaction tracking

## Testing

To verify the fix:

1. Check that each supply appears only once in the dropdown
2. Verify that available stock shows total across all batches
3. Confirm that stock validation works correctly with aggregated quantities
4. Test that the system selects appropriate stock when user chooses a supply

## Files Modified

-   `app/Livewire/MasterData/Supply/Usage.php`
    -   `loadAvailableSupplies()` - Added aggregation logic
    -   `getBestAvailableStockId()` - New method for smart stock selection
    -   `updatedItems()` - Enhanced to handle aggregated supplies
    -   `getAvailableStockFromSupplyStock()` - Updated to handle aggregated case
    -   Added comprehensive logging throughout

## Migration Notes

This change is backward compatible and doesn't require database migrations. The system will automatically aggregate supplies when the component loads.

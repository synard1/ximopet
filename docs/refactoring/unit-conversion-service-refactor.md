# UnitConversionService Refactoring

## Overview

Refactored the `getConvertedQuantityAndUnitId` method in `UnitConversionService` to match the conversion logic patterns used consistently across FeedPurchases and SupplyPurchases components.

## Key Changes

### 1. Data Structure Alignment

-   **Before**: Used `payload['conversion_units']`
-   **After**: Uses `data['conversion_units']` to match actual data structure
-   **Impact**: Ensures consistency with how conversion units are stored in Feed and Supply models

### 2. Enhanced Error Handling

-   Added comprehensive validation for missing items
-   Added validation for empty conversion units
-   Added validation for invalid unit values (≤ 0)
-   Improved error logging with detailed context information

### 3. Improved Logging

-   Added debug logging for successful conversions
-   Added warning logging for edge cases
-   Added error logging with stack traces
-   Included conversion formula in debug logs for transparency

### 4. Conversion Logic Consistency

The refactored method now follows the exact same pattern used in:

#### FeedPurchases/Create.php (lines 171-205):

```php
$units = collect($feed->data['conversion_units']);
$selectedUnit = $units->firstWhere('unit_id', $item['unit_id']);
$smallestUnit = $units->firstWhere('is_smallest', true);

if (!$selectedUnit || !$smallestUnit) {
    throw new \Exception("Invalid unit conversion for feed: {$feed->name}");
}

$convertedQuantity = ($item['quantity'] * $selectedUnit['value']) / $smallestUnit['value'];
```

#### SupplyPurchases/Create.php (lines 215-245):

```php
$units = collect($supply->data['conversion_units']);
$selectedUnit = $units->firstWhere('unit_id', $item['unit_id']);
$smallestUnit = $units->firstWhere('is_smallest', true);

if (!$selectedUnit || !$smallestUnit) {
    throw new \Exception("Invalid unit conversion for supply: {$supply->name}");
}

$convertedQuantity = ($item['quantity'] * $selectedUnit['value']) / $smallestUnit['value'];
```

## Method Signature

```php
public static function getConvertedQuantityAndUnitId(
    string $type,
    $itemId,
    $unitId,
    float $quantity
): array
```

## Return Format

```php
[
    'converted_quantity' => float,
    'converted_unit_id' => int|null
]
```

## Conversion Formula

```
converted_quantity = (quantity × selected_unit_value) ÷ smallest_unit_value
```

## Error Handling Strategy

1. **Item Not Found**: Returns original quantity and unit ID
2. **No Conversion Units**: Returns original quantity and unit ID
3. **Invalid Unit Values**: Returns original quantity and unit ID
4. **Unknown Type**: Returns original quantity and unit ID
5. **Exceptions**: Returns original quantity and unit ID with error logging

## Testing

Created comprehensive test suite in `tests/Unit/Services/Recording/UnitConversionServiceTest.php` covering:

-   ✅ Basic conversion for feeds and supplies
-   ✅ Same unit conversion (no conversion needed)
-   ✅ Error handling for missing items
-   ✅ Error handling for missing conversion units
-   ✅ Error handling for invalid unit values
-   ✅ Error handling for unknown types
-   ✅ Fallback smallest unit when not marked
-   ✅ Verification against FeedPurchases logic
-   ✅ Verification against SupplyPurchases logic

## Benefits

1. **Consistency**: All conversion logic now follows the same pattern
2. **Maintainability**: Single source of truth for unit conversion
3. **Reliability**: Comprehensive error handling prevents crashes
4. **Debugging**: Detailed logging for troubleshooting
5. **Testing**: Full test coverage ensures reliability

## Migration Notes

-   No breaking changes to method signature
-   Return format remains the same
-   All existing calls will continue to work
-   Improved error handling provides better fallback behavior

## Usage Examples

```php
// Feed conversion
$result = UnitConversionService::getConvertedQuantityAndUnitId('feed', $feedId, $unitId, 100);

// Supply conversion
$result = UnitConversionService::getConvertedQuantityAndUnitId('supply', $supplyId, $unitId, 50);

// Access results
$convertedQuantity = $result['converted_quantity'];
$convertedUnitId = $result['converted_unit_id'];
```

## Logging Examples

### Successful Conversion

```
[UnitConversionService] Quantity converted successfully
{
    "type": "feed",
    "item_id": 1,
    "item_name": "Test Feed",
    "original_quantity": 2.5,
    "original_unit_id": 3,
    "selected_unit_value": 1000,
    "smallest_unit_id": 1,
    "smallest_unit_value": 1,
    "converted_quantity": 2500,
    "conversion_formula": "(2.5 * 1000) / 1"
}
```

### Error Handling

```
[UnitConversionService] Feed not found
{
    "itemId": 99999,
    "unitId": 1,
    "quantity": 100
}
```

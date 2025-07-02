# Livestock Purchase Expedition ID Foreign Key Constraint Fix

**Date:** 2025-07-01 11:49 WIB  
**Issue:** SQLSTATE[23000]: Integrity constraint violation: 1452 Cannot add or update a child row: a foreign key constraint fails (`demo51_test2`.`livestock_purchases`, CONSTRAINT `livestock_purchases_expedition_id_foreign` FOREIGN KEY (`expedition_id`) REFERENCES `partners` (`id`))

## Problem Analysis

The error occurred when trying to save a livestock purchase with an empty string `''` for `expedition_id`. The foreign key constraint requires either:

1. A valid partner ID (UUID format)
2. `NULL` value

Empty strings `''` are not allowed by the foreign key constraint.

## Root Cause

1. **Form Submission Issue:** The expedition_id field was being submitted as an empty string `''` instead of `null`
2. **Insufficient Validation:** No validation rule for expedition_id in the save method
3. **Improper Handling:** The code was using `$this->expedition_id ?? null` which doesn't handle empty strings properly

## Solution Implemented

### 1. Property Initialization

```php
// Before
public $expedition_id;

// After
public $expedition_id = null;
```

### 2. Added Validation Rule

```php
$this->validate([
    'invoice_number' => 'required|string',
    'date' => 'required|date',
    'supplier_id' => 'required|exists:partners,id',
    'farm_id' => 'required|exists:farms,id',
    'coop_id' => 'required|exists:coops,id',
    'expedition_id' => 'nullable|exists:partners,id', // Added this line
]);
```

### 3. Created Normalization Function

```php
/**
 * Normalize expedition_id to ensure it's either a valid UUID or null
 */
private function normalizeExpeditionId($expeditionId)
{
    // If it's null, return null
    if ($expeditionId === null) {
        return null;
    }

    // If it's an empty string, return null
    if ($expeditionId === '') {
        return null;
    }

    // If it's a valid UUID format, return it
    if (is_string($expeditionId) && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $expeditionId)) {
        return $expeditionId;
    }

    // If it's not a valid UUID, return null
    Log::warning('Invalid expedition_id format detected', [
        'expedition_id' => $expeditionId,
        'type' => gettype($expeditionId)
    ]);
    return null;
}
```

### 4. Updated Save Method

```php
// Normalize expedition_id before saving
$normalizedExpeditionId = $this->normalizeExpeditionId($this->expedition_id);

$purchaseData = [
    // ... other fields
    'expedition_id' => $normalizedExpeditionId,
    // ... other fields
];
```

### 5. Updated Edit Form Method

```php
$this->expedition_id = $this->normalizeExpeditionId($pembelian->expedition_id);
```

### 6. Enhanced Logging

Added comprehensive logging to track expedition_id processing:

```php
Log::info('Expedition ID debug info', [
    'raw_expedition_id' => $this->expedition_id,
    'expedition_id_type' => gettype($this->expedition_id),
    'normalized_expedition_id' => $normalizedExpeditionId,
    'normalized_type' => gettype($normalizedExpeditionId)
]);
```

## Files Modified

-   `app/Livewire/LivestockPurchase/Create.php`

## Testing

1. **Create Mode:** Test saving with no expedition selected (should save with null)
2. **Create Mode:** Test saving with valid expedition selected (should save with UUID)
3. **Edit Mode:** Test loading existing records with null expedition_id
4. **Edit Mode:** Test loading existing records with valid expedition_id

## Prevention

1. **Consistent Pattern:** Use the same expedition_id handling pattern across all purchase components
2. **Validation:** Always validate expedition_id as nullable|exists:partners,id
3. **Normalization:** Use normalization function for all expedition_id assignments
4. **Logging:** Maintain logging for debugging future issues

## Related Components

This fix follows the same pattern used in:

-   `app/Livewire/FeedPurchases/Create.php` (line 145)
-   `app/Livewire/SupplyPurchases/Create.php` (line 169)

## Impact

-   **Positive:** Fixes foreign key constraint violation
-   **Positive:** Improves data integrity
-   **Positive:** Adds comprehensive validation and logging
-   **Positive:** Makes expedition_id handling consistent across components
-   **Neutral:** No breaking changes to existing functionality

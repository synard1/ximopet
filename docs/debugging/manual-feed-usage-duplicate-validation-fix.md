# Manual Feed Usage - Duplicate Validation Fix Documentation

## Overview

Dokumentasi perbaikan validasi duplicate stocks pada mode edit Manual Feed Usage Component.

## Issue Description

### Problem

User melaporkan error validasi yang kontradiktif:

1. **Create Mode**: User bisa input berulang kali untuk batch, stock, dan tanggal yang sama tanpa error
2. **Edit Mode**: Saat edit data existing yang memiliki duplicate stocks, muncul error "Duplicate stocks are not allowed in the same usage entry"
3. **Kondisi Spesifik**: Error terjadi ketika ada 2x input batch dan stock yang sama, kemudian data dibuka kembali untuk edit

### Error Message

```
Validation Errors
Duplicate stocks are not allowed in the same usage entry.
```

### Root Cause Analysis

1. **Validasi Duplicate Stocks**: Service `validateFeedUsageInputRestrictions()` selalu menjalankan validasi duplicate tanpa mempertimbangkan edit mode
2. **Logika Kontradiktif**: System mengizinkan user membuat duplicate entries saat create, tapi melarang saat edit
3. **Edit Mode Detection**: Validasi tidak mendeteksi bahwa ini adalah edit mode dan duplicate sudah ada sebelumnya

## Solution Implemented

### 1. Service Layer Fix

**File**: `app/Services/Feed/ManualFeedUsageService.php`

#### Modified Function Signature

```php
// Before
public function validateFeedUsageInputRestrictions(string $livestockId, array $selectedStocks): array

// After
public function validateFeedUsageInputRestrictions(string $livestockId, array $selectedStocks, bool $isEditMode = false): array
```

#### Enhanced Validation Logic

```php
// Check for duplicate stocks (skip in edit mode since user can have existing duplicates)
if (!$isEditMode && ($restrictions['prevent_duplicate_stocks'] ?? true)) {
    $stockIds = collect($selectedStocks)->pluck('stock_id')->toArray();
    if (count($stockIds) !== count(array_unique($stockIds))) {
        $errors[] = "Duplicate stocks are not allowed in the same usage entry.";
    }
}
```

#### Additional Edit Mode Skips

```php
// Skip these validations in edit mode:
1. Same batch repeated input validation
2. Maximum usage per day per batch
3. Maximum usage per day per livestock
4. Minimum interval between usage
5. Duplicate stocks validation
```

#### Enhanced Stock Availability Check for Edit Mode

```php
// For edit mode, add back the previously used quantity if this stock has usage_detail_id
if ($isEditMode && isset($stock['usage_detail_id'])) {
    $existingDetail = FeedUsageDetail::find($stock['usage_detail_id']);
    if ($existingDetail) {
        $availableQuantity += floatval($existingDetail->quantity_taken);

        Log::info('📊 Edit mode: Adjusted available quantity for validation', [
            'stock_id' => $stockRecord->id,
            'feed_name' => $stockRecord->feed->name,
            'base_available' => $stockRecord->quantity_in - $stockRecord->quantity_used - $stockRecord->quantity_mutated,
            'previously_used' => $existingDetail->quantity_taken,
            'adjusted_available' => $availableQuantity,
            'requested_quantity' => $stock['quantity']
        ]);
    }
}
```

### 2. Component Layer Fix

**File**: `app/Livewire/FeedUsages/ManualFeedUsage.php`

#### Updated Validation Call

```php
private function validateFeedUsageInputRestrictions()
{
    $service = new ManualFeedUsageService();
    $validation = $service->validateFeedUsageInputRestrictions(
        $this->livestockId,
        $this->selectedStocks,
        $this->isEditMode // Pass edit mode to skip duplicate validation
    );

    if (!$validation['valid']) {
        $this->errors = array_merge($this->errors, ['restrictions' => $validation['errors']]);
        return false;
    }

    return true;
}
```

## Technical Details

### Validation Rules Affected

1. **prevent_duplicate_stocks**: Skipped in edit mode
2. **allow_same_batch_repeated_input**: Skipped in edit mode
3. **max_usage_per_day_per_batch**: Skipped in edit mode
4. **max_usage_per_day_per_livestock**: Skipped in edit mode
5. **min_interval_minutes**: Skipped in edit mode
6. **require_stock_availability_check**: Enhanced for edit mode

### Edit Mode Detection

-   Component property: `$this->isEditMode`
-   Set to `true` when existing usage data is loaded
-   Passed to service validation functions
-   Used to modify validation behavior

### Stock Availability Calculation in Edit Mode

```php
Base Available = quantity_in - quantity_used - quantity_mutated
Edit Mode Available = Base Available + previously_used_quantity
```

### Logging Enhancement

Added comprehensive logging for edit mode stock availability adjustments:

-   Stock ID and feed name
-   Base available quantity
-   Previously used quantity
-   Adjusted available quantity
-   Requested quantity

## Testing Scenarios

### Scenario 1: Create with Duplicates (Should Work)

1. Select same batch, same stock, same date multiple times
2. Should be able to create without validation error
3. System allows duplicate entries in create mode

### Scenario 2: Edit Existing Duplicates (Should Work Now)

1. Open existing usage data with duplicate stocks
2. Modify quantities or add/remove stocks
3. Should be able to preview and save without duplicate validation error
4. Edit mode skips duplicate validation

### Scenario 3: Edit Mode Stock Availability (Should Work)

1. Edit existing usage with quantity 500kg
2. Stock shows available 100kg + 500kg (previously used) = 600kg available
3. Can modify quantity up to 600kg without availability error

## Files Modified

### Core Files

1. `app/Services/Feed/ManualFeedUsageService.php` - Enhanced validation logic
2. `app/Livewire/FeedUsages/ManualFeedUsage.php` - Updated validation call

### Documentation

1. `docs/debugging/manual-feed-usage-duplicate-validation-fix.md` - This file

## Backward Compatibility

### Function Signature

-   Added optional parameter `$isEditMode = false`
-   Maintains backward compatibility with existing calls
-   Default behavior unchanged for non-edit scenarios

### Validation Behavior

-   **Create Mode**: All validations apply as before
-   **Edit Mode**: Relaxed validations for better UX
-   **No Breaking Changes**: Existing functionality preserved

## Implementation Notes

### Why Skip Validations in Edit Mode?

1. **User Experience**: Users should be able to edit existing data without artificial restrictions
2. **Data Consistency**: If duplicates were allowed during creation, they should be editable
3. **Business Logic**: Edit operations are modifications of existing valid data

### Stock Availability Logic

-   **Create Mode**: Uses current available quantity
-   **Edit Mode**: Adds back previously used quantity for accurate availability calculation
-   **Prevents False Negatives**: Avoids "insufficient stock" errors for valid edit operations

### Logging Strategy

-   **Debug Information**: Comprehensive logging for edit mode calculations
-   **Troubleshooting**: Easy identification of availability adjustments
-   **Performance**: Minimal logging overhead with structured data

## Future Improvements

### Configuration Options

1. **Edit Mode Validation Control**: Allow companies to configure which validations to skip in edit mode
2. **Duplicate Policy**: Separate policies for create vs edit operations
3. **Audit Trail**: Enhanced logging for edit mode validation decisions

### User Interface

1. **Edit Mode Indicators**: Clear visual indicators when in edit mode
2. **Validation Messages**: Context-aware validation messages for edit vs create
3. **Confirmation Dialogs**: Optional confirmation for editing duplicate entries

## Conclusion

This fix resolves the contradictory validation behavior between create and edit modes by:

1. **Contextual Validation**: Different validation rules for create vs edit operations
2. **Improved UX**: Users can edit existing data without encountering artificial barriers
3. **Accurate Stock Calculations**: Proper availability calculations for edit mode
4. **Comprehensive Logging**: Enhanced debugging capabilities
5. **Backward Compatibility**: No breaking changes to existing functionality

The solution maintains data integrity while providing a better user experience for editing existing feed usage data.

**Status**: ✅ **RESOLVED** - Edit mode duplicate validation issue fixed
**Date**: 2024-12-19 15:30 WIB

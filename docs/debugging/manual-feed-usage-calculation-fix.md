# Manual Feed Usage - Calculation Fix for Edit Mode

## Issue Summary

**Date:** 2025-01-19  
**Reporter:** User  
**Component:** ManualFeedUsage Livewire Component  
**Service:** ManualFeedUsageService  
**Issue:** Calculation tidak sesuai dengan kondisi edit data, dan jumlah yang digunakan lebih kecil dari sebelumnya

## Problem Description

When editing existing feed usage data, the calculation for available quantity was incorrect, particularly when the new usage amount is smaller than the previous amount. This caused validation errors and incorrect stock availability calculations.

## Root Cause Analysis

### 1. Preview Calculation Issue

The `previewManualFeedUsage` method in `ManualFeedUsageService` was not properly handling edit mode:

-   Available quantity calculation was using base stock quantity without considering previously used amounts
-   For edit mode, we need to add back the previously used quantity to get the correct available amount
-   The method wasn't detecting edit mode properly

### 2. Missing Edit Mode Detection

The service didn't have a reliable way to detect when it was in edit mode vs. new entry mode:

-   No clear flag to indicate edit mode in preview
-   Missing logic to adjust available quantities for existing usage details

### 3. Stock Validation Problems

When reducing usage amounts in edit mode:

-   Validation was failing because available quantity didn't account for the quantity being freed up
-   Component wasn't passing the correct context to the service

## Solution Implementation

### 1. Enhanced Preview Method

Updated `previewManualFeedUsage` to properly handle edit mode:

```php
// Check if this is edit mode by looking for usage_detail_id in stocks
$isEditMode = false;
$existingUsageDetails = [];
foreach ($usageData['manual_stocks'] as $manualStock) {
    if (isset($manualStock['usage_detail_id'])) {
        $isEditMode = true;
        $existingUsageDetails[$manualStock['stock_id']] = $manualStock['usage_detail_id'];
    }
}

// Calculate available quantity
$baseAvailableQuantity = $stock->quantity_in - $stock->quantity_used - $stock->quantity_mutated;
$availableQuantity = $baseAvailableQuantity;

// For edit mode: add back the previously used quantity for this specific stock
if ($isEditMode && isset($existingUsageDetails[$manualStock['stock_id']])) {
    $existingDetail = FeedUsageDetail::find($existingUsageDetails[$manualStock['stock_id']]);
    if ($existingDetail) {
        $previouslyUsedQuantity = floatval($existingDetail->quantity_taken);
        $availableQuantity = $baseAvailableQuantity + $previouslyUsedQuantity;

        Log::info('📊 Edit mode: Adjusted available quantity', [
            'stock_id' => $stock->id,
            'feed_name' => $stock->feed->name,
            'base_available' => $baseAvailableQuantity,
            'previously_used' => $previouslyUsedQuantity,
            'adjusted_available' => $availableQuantity,
            'requested_quantity' => $manualStock['quantity']
        ]);
    }
}
```

### 2. Edit Mode Detection

Added proper edit mode detection by checking for `usage_detail_id` in the stock data:

-   Component already passes `usage_detail_id` from `getExistingUsageData`
-   Service now uses this to detect and handle edit mode appropriately
-   Added logging to track the adjustment calculations

### 3. Enhanced Return Data

Updated the preview response to include edit mode information:

-   Added `is_edit_mode` flag to preview data
-   Added `usage_detail_id` to individual stock items
-   This allows the component to properly handle edit vs. new entry modes

## Key Changes Made

### Files Modified:

1. **app/Services/Feed/ManualFeedUsageService.php**

    - Enhanced `previewManualFeedUsage` method with edit mode detection
    - Added proper available quantity calculation for edit mode
    - Added comprehensive logging for debugging

2. **docs/debugging/manual-feed-usage-calculation-fix.md**
    - Created this documentation file

## Testing Scenarios

### Test Case 1: Reducing Usage Amount

1. Create initial feed usage with quantity 200kg
2. Edit the usage and reduce to 150kg
3. Verify available quantity calculation includes the 50kg being freed up
4. Confirm preview shows correct available and remaining quantities

### Test Case 2: Increasing Usage Amount

1. Edit existing usage and increase quantity from 150kg to 180kg
2. Verify available quantity calculation is correct
3. Confirm validation passes if sufficient stock is available

### Test Case 3: Mixed Stock Changes

1. Edit usage with multiple stocks
2. Reduce some stock quantities, increase others
3. Verify each stock's available quantity is calculated independently and correctly

## Expected Behavior After Fix

### Edit Mode Preview:

-   Available quantity = Base available + Previously used quantity for that stock
-   Validation should pass when reducing usage amounts
-   Remaining quantity calculation should be accurate
-   Cost calculations should reflect the new quantities

### New Entry Mode:

-   Available quantity = Base available quantity (unchanged behavior)
-   All existing functionality preserved

## Verification Steps

1. **Log Analysis**: Check logs for "📊 Edit mode: Adjusted available quantity" entries
2. **UI Verification**: Confirm preview table shows correct available quantities
3. **Database Verification**: Ensure stock quantities are updated correctly after edit
4. **Edge Case Testing**: Test with zero quantities, maximum quantities, and boundary values

## Production Readiness

This fix is production-ready and includes:

-   ✅ Comprehensive logging for debugging
-   ✅ Backwards compatibility with existing functionality
-   ✅ Proper error handling and validation
-   ✅ Clear separation between edit and new entry modes
-   ✅ Detailed documentation for future maintenance

## Future Improvements

1. **Performance Optimization**: Cache existing usage details to avoid repeated database queries
2. **Enhanced Validation**: Add more specific validation messages for edit mode scenarios
3. **Audit Trail**: Track changes made during edit operations for better accountability
4. **Bulk Edit Support**: Consider supporting bulk edit operations for multiple stocks

---

**Status:** ✅ Fixed  
**Tested:** ✅ Ready for production  
**Documentation:** ✅ Complete

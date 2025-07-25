# Feed Usage Modal Refactor Documentation

## Overview

This document tracks the refactoring of the feed usage modal to provide a simpler, more focused view of feed usage data without complex stock calculations.

## Changes Made

### 1. Modal Template Simplification

-   **File**: `resources/views/livewire/feed-usages/feed-usage-modal.blade.php`
-   **Changes**:
    -   Removed complex stock calculation sections
    -   Simplified to show only usage data with feed info, summary, and export options
    -   Added date range filter functionality
    -   Improved responsive design

### 2. Controller Method Updates

-   **File**: `app/Http/Controllers/FeedController.php`
-   **Changes**:
    -   Updated `getFeedUsageData()` method to return simplified data structure
    -   Removed stock calculation logic
    -   Added proper error handling and logging
    -   Enhanced data validation

### 3. JavaScript Integration

-   **File**: `resources/views/livewire/feed-usages/feed-usage-modal.blade.php`
-   **Changes**:
    -   Updated event listeners to handle new modal structure
    -   Added proper modal open/close functionality
    -   Enhanced user experience with loading states

### 4. API Route Addition

-   **File**: `routes/web.php`
-   **Changes**:
    -   Added new route for feed usage data API
    -   Route: `GET /api/feed-usage-data/{feedId}/{livestockId}`

## API Response Structure

### Feed Usage Data Response

```json
{
  "success": true,
  "data": [
    {
      "feed_id": "uuid",
      "quantity": 10.0,
      "feed_name": "SP 10",
      "feed_code": "SP10",
      "unit_id": "uuid",
      "unit_name": "KG",
      "original_unit_id": "uuid",
      "original_unit_name": "KG",
      "consumption_unit_id": "uuid",
      "consumption_unit_name": "KG",
      "conversion_factor": 1.0,
      "converted_quantity": 10.0,
      "available_stocks": [...],
      "stock_origins": [...],
      "stock_purchase_dates": [...],
      "stock_prices": {
        "min_price": 0,
        "max_price": 0,
        "average_price": 0
      },
      "category": "Uncategorized",
      "timestamp": "2025-07-16T12:20:45+07:00"
    }
  ],
  "summary": {
    "total_quantity": 100.0,
    "total_converted_quantity": 100.0,
    "usage_count": 10,
    "date_range": {
      "start": "2025-01-01",
      "end": "2025-07-16"
    }
  }
}
```

## Usage

### Opening the Modal

```javascript
// Trigger modal open
Livewire.dispatch("open-feed-usage-modal", {
    feedId: "uuid",
    livestockId: "uuid",
});
```

### API Call

```javascript
// Fetch feed usage data
fetch(`/api/feed-usage-data/${feedId}/${livestockId}`)
    .then((response) => response.json())
    .then((data) => {
        // Handle response data
    });
```

## Error Handling

### Common Error Scenarios

1. **Feed not found**: Returns 404 with error message
2. **Livestock not found**: Returns 404 with error message
3. **No usage data**: Returns empty data array with summary
4. **Database errors**: Returns 500 with error details

### Error Response Format

```json
{
    "success": false,
    "error": "Error message",
    "details": "Additional error details"
}
```

## Performance Considerations

### Optimizations Implemented

1. **Eager Loading**: Relationships loaded efficiently
2. **Query Optimization**: Minimal database queries
3. **Caching**: Consider implementing Redis caching for large datasets
4. **Pagination**: Consider adding pagination for very large datasets

### Monitoring

-   Log all API calls for performance tracking
-   Monitor query execution times
-   Track error rates and types

## Future Enhancements

### Planned Improvements

1. **Export Functionality**: Add CSV/Excel export
2. **Advanced Filtering**: Date ranges, quantity ranges
3. **Real-time Updates**: WebSocket integration
4. **Bulk Operations**: Multiple feed selection
5. **Analytics**: Usage trends and patterns

### Technical Debt

1. **Caching Layer**: Implement Redis caching
2. **API Versioning**: Add versioning for future changes
3. **Rate Limiting**: Add API rate limiting
4. **Documentation**: Add OpenAPI/Swagger documentation

## Testing

### Test Cases

1. **Valid Data**: Test with existing feed and livestock
2. **Empty Data**: Test with no usage records
3. **Invalid IDs**: Test with non-existent feed/livestock
4. **Large Datasets**: Test with many usage records
5. **Date Filtering**: Test date range functionality

### Manual Testing Steps

1. Navigate to feed usage page
2. Click on usage record to open modal
3. Verify data display correctly
4. Test date range filter
5. Test export functionality
6. Verify error handling

## Troubleshooting

### Common Issues

#### 1. Empty Available Stocks Array

**Issue**: `available_stocks` array is empty despite having stock data in database.

**Root Cause**: The `getStockDetails()` method in `RecordingPersistenceService.php` was not properly populating the available stocks array.

**Solution**: Fixed the method to:

-   Query stocks with available quantity > 0 using proper SQL calculation
-   Populate `available_stocks` array with detailed stock information
-   Include purchase details when available
-   Add comprehensive logging for debugging

**Code Changes**:

```php
// Before: Method was overwriting available_stocks with empty array
$result['available_stocks'] = [];

// After: Properly populate with stock data
$result['available_stocks'] = $availableStocks;
```

**Verification**: Check logs for "📊 Stock details collected" message with stock counts and quantities.

#### 2. Missing Purchase Information

**Issue**: Stock records missing purchase details.

**Root Cause**: Missing eager loading of purchase relationships.

**Solution**: Added `with(['feedPurchase'])` to stock queries and proper null checks.

#### 3. SQL Query Issues

**Issue**: Database errors when querying stock data.

**Root Cause**: Incorrect field references or missing relationships.

**Solution**:

-   Fixed SQL calculation for available quantity
-   Added proper COALESCE handling for nullable fields
-   Enhanced error handling and logging

#### 4. New FeedPurchase Structure Issues

**Issue**: Purchase information showing null values for batch_id, supplier, and batch_number.

**Root Cause**: The system was updated from the old batch pattern to a new header-detail pattern where:

-   `FeedPurchase` is the header (contains invoice, supplier, expedition info)
-   `FeedPurchaseItem` is the detail (contains feed-specific info like quantity, price, unit)

**Solution**: Updated the code to work with the new structure:

-   Purchase info now comes from `FeedPurchase` (invoice_number, do_number, supplier, expedition)
-   Price info now comes from `FeedPurchaseItem` (price_per_unit, price_per_converted_unit)
-   Updated eager loading to include new relationships
-   Enhanced error handling for missing relationships

**Code Changes**:

```php
// Before (old batch structure)
'batch_id' => $stock->feedPurchase->batch->id ?? null,
'batch_number' => $stock->feedPurchase->batch->invoice_number ?? null,
'supplier' => $stock->feedPurchase->batch->supplier->name ?? 'Unknown',

// After (new header-detail structure)
'purchase_id' => $stock->feedPurchase->id ?? null,
'invoice_number' => $stock->feedPurchase->invoice_number ?? null,
'do_number' => $stock->feedPurchase->do_number ?? null,
'supplier' => optional($stock->feedPurchase->supplier)->name ?? 'Unknown',
'expedition' => optional($stock->feedPurchase->expedition)->name ?? null,
'purchase_date' => $stock->feedPurchase->date ?? null,
```

**Verification**: Check that purchase information now includes valid invoice numbers, supplier names, and expedition details.

### Debug Information

#### Log Messages

-   `

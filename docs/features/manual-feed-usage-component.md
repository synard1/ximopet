# Manual Feed Usage Component Documentation

**Version**: 2.0.0  
**Date**: {{ date('Y-m-d H:i:s') }}  
**Author**: AI Assistant

## Overview

The Manual Feed Usage Component is a comprehensive Livewire-based solution for recording manual feed consumption in livestock management systems. This component follows a **livestock-first approach**, where users first select livestock and then choose from available feed stocks associated with that livestock.

## Key Features

### Core Functionality

-   **Livestock-First Workflow**: Start by selecting livestock, then view their available feed stocks
-   **Multi-Feed Stock Selection**: Select from multiple feed stocks per livestock
-   **Real-time Cost Calculation**: Automatic cost computation based on stock purchase prices
-   **3-Step Process**: Selection → Preview → Processing
-   **Comprehensive Validation**: Input restrictions and availability checks
-   **Audit Trail**: Complete logging of all feed usage activities

### Technical Features

-   **FeedStock Integration**: Works directly with FeedStock model for accurate inventory tracking
-   **Batch Information**: Displays batch details when available from feed purchases
-   **Event-Driven Architecture**: Emits events for parent component integration
-   **Company Configuration**: Respects company-specific feed tracking settings
-   **Transaction Safety**: Database transactions ensure data consistency

## Architecture

### Data Flow (Livestock-First Approach)

```
1. User selects Livestock
2. System queries FeedStock where livestock_id = selected_livestock
3. Groups stocks by feed_id for organized display
4. User selects specific stocks and quantities
5. System validates availability and restrictions
6. Creates FeedUsage with FeedUsageDetail records
7. Updates FeedStock.quantity_used
8. Logs activity and emits completion event
```

### Component Structure

```
app/
├── Livewire/FeedUsages/
│   └── ManualFeedUsage.php          # Main Livewire component
├── Services/Feed/
│   └── ManualFeedUsageService.php   # Business logic service
└── Models/
    ├── FeedStock.php                # Primary stock model
    ├── FeedUsage.php                # Usage tracking
    ├── FeedUsageDetail.php          # Detailed usage records
    └── Livestock.php                # Livestock management

resources/views/livewire/feed-usages/
└── manual-feed-usage.blade.php     # Component template
```

## Service Layer (ManualFeedUsageService)

### Primary Methods

#### `getAvailableFeedStocksForManualSelection(string $livestockId, ?string $feedId = null): array`

Retrieves available feed stocks for a specific livestock.

**Parameters:**

-   `$livestockId`: Required livestock identifier
-   `$feedId`: Optional filter for specific feed type

**Returns:**

```php
[
    'livestock_id' => 'uuid',
    'livestock_name' => 'string',
    'total_feed_types' => 'int',
    'total_stocks' => 'int',
    'feeds' => [
        [
            'feed_id' => 'uuid',
            'feed_name' => 'string',
            'feed_type' => 'string',
            'total_available' => 'float',
            'stock_count' => 'int',
            'stocks' => [
                [
                    'stock_id' => 'uuid',
                    'feed_purchase_id' => 'uuid',
                    'batch_info' => [
                        'batch_id' => 'uuid',
                        'batch_number' => 'string',
                        'production_date' => 'date',
                        'supplier' => 'string'
                    ],
                    'stock_name' => 'string',
                    'date' => 'date',
                    'source_type' => 'string',
                    'quantity_in' => 'float',
                    'quantity_used' => 'float',
                    'quantity_mutated' => 'float',
                    'available_quantity' => 'float',
                    'unit' => 'string',
                    'age_days' => 'int',
                    'cost_per_unit' => 'float',
                    'total_cost' => 'float'
                ]
            ]
        ]
    ]
]
```

#### `previewManualFeedUsage(array $usageData): array`

Generates usage preview with cost calculations and validation.

**Input Structure:**

```php
[
    'livestock_id' => 'required|uuid',
    'feed_id' => 'nullable|uuid',  // Optional filter
    'usage_date' => 'required|date',
    'usage_purpose' => 'required|string',
    'notes' => 'nullable|string',
    'manual_stocks' => [
        [
            'stock_id' => 'required|uuid',
            'quantity' => 'required|numeric|min:0.01',
            'note' => 'nullable|string'
        ]
    ]
]
```

#### `processManualFeedUsage(array $usageData): array`

Processes the feed usage within a database transaction.

**Returns:**

```php
[
    'success' => true,
    'feed_usage_id' => 'uuid',
    'livestock_id' => 'uuid',
    'livestock_name' => 'string',
    'total_quantity' => 'float',
    'total_cost' => 'float',
    'average_cost_per_unit' => 'float',
    'stocks_processed' => 'array',
    'usage_date' => 'date',
    'usage_purpose' => 'string',
    'notes' => 'string',
    'processed_at' => 'datetime'
]
```

### Validation and Restrictions

The service includes comprehensive validation:

-   **Quantity Availability**: Ensures requested quantity doesn't exceed available stock
-   **Livestock Ownership**: Verifies stocks belong to selected livestock
-   **Feed Consistency**: Validates stock belongs to specified feed (if filtered)
-   **Company Restrictions**: Enforces company-specific usage rules
-   **Date Validation**: Ensures usage date is valid
-   **Input Sanitization**: Prevents invalid or malicious input

## Livewire Component (ManualFeedUsage)

### Public Properties

```php
// Component state
public $showModal = false;
public $livestock;
public $livestockId;
public $feedFilter = null;

// Form data
public $usagePurpose = 'feeding';
public $usageDate;
public $notes = '';

// Stock data
public $availableFeeds = [];
public $selectedStocks = [];

// UI state
public $step = 1; // 1: Selection, 2: Preview, 3: Result
public $isLoading = false;
public $errors = [];
public $successMessage = '';
```

### Key Methods

#### `openModal($livestockId, $feedId = null)`

Opens the modal for specific livestock with optional feed filter.

#### `loadAvailableFeedStocks()`

Loads available feed stocks for the selected livestock.

#### `addStock($stockId)` / `removeStock($index)`

Manages stock selection for the usage.

#### `previewUsage()`

Generates preview of the planned usage.

#### `processUsage()`

Executes the feed usage processing.

### Event System

The component emits the following events:

-   `feed-usage-completed`: When usage is successfully processed
-   `show-manual-feed-usage`: To trigger modal display
-   `step-changed`: When workflow step changes

## Database Schema

### FeedStock Table Structure

```sql
CREATE TABLE feed_stocks (
    id UUID PRIMARY KEY,
    livestock_id UUID REFERENCES livestocks(id),
    feed_id UUID REFERENCES feeds(id),
    feed_purchase_id UUID REFERENCES feed_purchases(id),
    date DATE,
    source_type VARCHAR(50),
    source_id UUID,
    quantity_in DECIMAL(12,2),
    quantity_used DECIMAL(12,2) DEFAULT 0,
    quantity_mutated DECIMAL(12,2) DEFAULT 0,
    created_by BIGINT,
    updated_by BIGINT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP
);
```

### FeedUsage Table Structure

```sql
CREATE TABLE feed_usages (
    id UUID PRIMARY KEY,
    livestock_id UUID REFERENCES livestocks(id),
    recording_id UUID REFERENCES recordings(id),
    usage_date DATE,
    total_quantity DECIMAL(12,2),
    created_by BIGINT,
    updated_by BIGINT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP
);
```

### FeedUsageDetail Table Structure

```sql
CREATE TABLE feed_usage_details (
    id UUID PRIMARY KEY,
    feed_usage_id UUID REFERENCES feed_usages(id),
    feed_stock_id UUID REFERENCES feed_stocks(id),
    feed_id UUID REFERENCES feeds(id),
    quantity DECIMAL(12,2),
    cost_per_unit DECIMAL(12,2),
    total_cost DECIMAL(12,2),
    notes TEXT,
    created_by BIGINT,
    updated_by BIGINT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP
);
```

## Usage Examples

### Basic Implementation

```php
// In your Blade template
@livewire('feed-usages.manual-feed-usage')

// In your JavaScript
function showManualFeedUsageModal(livestockId, feedId = null) {
    Livewire.dispatch('show-manual-feed-usage', {
        livestock_id: livestockId,
        feed_id: feedId
    });
}

// Handle completion
window.addEventListener('feed-usage-completed', function(event) {
    console.log('Usage completed:', event.detail);
    // Update UI, show notifications, etc.
});
```

### Service Usage

```php
use App\Services\Feed\ManualFeedUsageService;

$service = new ManualFeedUsageService();

// Get available stocks for livestock
$stocks = $service->getAvailableFeedStocksForManualSelection('livestock-uuid');

// Preview usage
$preview = $service->previewManualFeedUsage([
    'livestock_id' => 'livestock-uuid',
    'usage_date' => '2024-01-15',
    'usage_purpose' => 'feeding',
    'manual_stocks' => [
        [
            'stock_id' => 'stock-uuid-1',
            'quantity' => 25.5,
            'note' => 'Morning feeding'
        ]
    ]
]);

// Process usage
$result = $service->processManualFeedUsage($usageData);
```

## Configuration

### Company Configuration

The component respects company-specific settings:

```php
// In CompanyConfig
'feed_tracking' => [
    'input_restrictions' => [
        'allow_same_day_repeated_input' => true,
        'allow_same_stock_repeated_input' => false,
        'max_usage_per_day_per_stock' => 3,
        'min_interval_minutes' => 60
    ]
]
```

## Error Handling

### Common Error Scenarios

1. **Insufficient Stock**: When requested quantity exceeds available stock
2. **Livestock Mismatch**: When stock doesn't belong to selected livestock
3. **Validation Failures**: Invalid input data or company restrictions
4. **Database Errors**: Transaction failures or constraint violations

### Error Response Format

```php
[
    'valid' => false,
    'errors' => [
        'field_name' => ['Error message 1', 'Error message 2'],
        'restrictions' => ['Company restriction message']
    ]
]
```

## Security Considerations

-   **Input Validation**: All inputs are validated and sanitized
-   **Authorization**: User permissions are checked before processing
-   **Data Integrity**: Database transactions ensure consistency
-   **Audit Trail**: All actions are logged with user information
-   **SQL Injection Prevention**: Uses Eloquent ORM for database operations

## Performance Optimizations

-   **Eager Loading**: Related models are loaded efficiently
-   **Query Optimization**: Minimal database queries with proper indexing
-   **Caching**: Company configurations are cached
-   **Lazy Loading**: UI components load data as needed

## Testing

### Unit Tests

```php
// Test service methods
public function test_get_available_feed_stocks_for_livestock()
{
    $livestock = Livestock::factory()->create();
    $service = new ManualFeedUsageService();

    $result = $service->getAvailableFeedStocksForManualSelection($livestock->id);

    $this->assertArrayHasKey('livestock_id', $result);
    $this->assertEquals($livestock->id, $result['livestock_id']);
}
```

### Integration Tests

```php
// Test component workflow
public function test_complete_feed_usage_workflow()
{
    $livestock = Livestock::factory()->create();

    Livewire::test(ManualFeedUsage::class)
        ->call('openModal', $livestock->id)
        ->assertSet('livestockId', $livestock->id)
        ->call('addStock', 'stock-id')
        ->call('previewUsage')
        ->assertSet('step', 2)
        ->call('processUsage')
        ->assertSet('step', 3);
}
```

## Migration Guide

### From Feed-First to Livestock-First

If migrating from a feed-first approach:

1. **Update Service Calls**: Change from `getAvailableFeedBatchesForManualSelection($feedId)` to `getAvailableFeedStocksForManualSelection($livestockId, $feedId)`

2. **Modify Event Handlers**: Update event data structure to include livestock_id as primary identifier

3. **Update UI Components**: Change button calls from `showModal(feedId, livestockId)` to `showModal(livestockId, feedId)`

4. **Database Migration**: Ensure FeedStock table has proper livestock_id relationships

## Troubleshooting

### Common Issues

1. **Modal Not Opening**: Check Livewire event dispatch and component mounting
2. **Stocks Not Loading**: Verify livestock has associated FeedStock records
3. **Validation Errors**: Check company configuration and input data format
4. **Cost Calculation Issues**: Ensure FeedPurchase records have proper pricing

### Debug Mode

Enable debug logging in the service:

```php
Log::info('Manual feed usage debug', [
    'livestock_id' => $livestockId,
    'available_stocks' => $stocks->count(),
    'company_restrictions' => $restrictions
]);
```

## Future Enhancements

-   **Mobile Optimization**: Responsive design improvements
-   **Bulk Operations**: Multiple livestock usage in single session
-   **Advanced Reporting**: Usage analytics and trends
-   **API Integration**: RESTful API for external system integration
-   **Notification System**: Real-time alerts for low stock levels

---

**Last Updated**: {{ date('Y-m-d H:i:s') }}  
**Version**: 2.0.0

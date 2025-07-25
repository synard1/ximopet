# Supply Usage Cost Integration

## Overview

This document describes the integration of supply usage costs into the livestock cost calculation system, specifically addressing how to calculate supply usage costs and incorporate them into livestock cost calculations even when no recording (Recording.php) exists for the livestock on the given date.

## Problem Statement

The original issue was that supply usage costs were not being calculated when no recording existed for a specific date, even though supply usage data was present in the database. Additionally, there was a secondary issue where supply usage data was not being loaded correctly in the Records.php component, causing empty supply usage sections in the payload.

## Solution Architecture

### 1. Safe Approach: Minimal Recording Creation

Instead of making `recording_id` nullable in LivestockCost (which would break data integrity and filtering logic), we implemented a **minimal recording creation** approach:

-   When supply usage exists but no recording is found, automatically create a minimal recording
-   The minimal recording contains default values and links to the livestock and date
-   This maintains foreign key constraints and data integrity
-   The minimal recording is clearly marked with metadata for identification

### 2. Fixed Supply Usage Data Loading Issue

**Problem**: Supply usage data was not being loaded correctly in Records.php, causing empty supply usage sections in the payload.

**Root Cause**: In `RecordingDataService.php`, the query for supply usage was using `where('usage_date', $date)` instead of `whereDate('usage_date', $date)`, causing date comparison issues.

**Solution**: Fixed the query to use `whereDate('usage_date', $date)` for proper date comparison.

## Implementation Details

### LivestockCostService Enhancements

```php
/**
 * Create minimal recording for supply usage when no recording exists
 * This ensures data integrity while allowing supply usage cost calculation
 */
private function createMinimalRecording($livestock, $tanggal)
{
    // Get previous day's recording for stock data
    $previousDate = Carbon::parse($tanggal)->subDay()->format('Y-m-d');
    $previousRecording = Recording::where('livestock_id', $livestock->id)
        ->whereDate('tanggal', $previousDate)
        ->first();

    // Calculate age and stock values
    $age = Carbon::parse($livestock->start_date)->diffInDays($tanggal);
    $stockAwal = $previousRecording ? $previousRecording->stock_akhir : $livestock->initial_quantity;
    $stockAkhir = $stockAwal; // No depletion in minimal recording

    // Create minimal recording with comprehensive metadata
    $recording = Recording::create([
        'tanggal' => $tanggal,
        'livestock_id' => $livestock->id,
        'age' => $age,
        'stock_awal' => $stockAwal,
        'stock_akhir' => $stockAkhir,
        'total_deplesi' => 0,
        'total_penjualan' => 0,
        'berat_semalam' => 0,
        'berat_hari_ini' => 0,
        'kenaikan_berat' => 0,
        'pakan_jenis' => '',
        'pakan_harian' => 0,
        'pakan_total' => 0,
        'payload' => [
            'mortality' => 0,
            'culling' => 0,
            'sales_quantity' => 0,
            'recording_type' => 'minimal_for_supply_usage',
            'created_by_cost_service' => true,
            'created_at' => now()->toIso8601String(),
        ],
        'data_operational' => [
            'has_feed_usage' => false,
            'has_supply_usage' => true,
            'has_depletion' => false,
            'minimal_recording' => true,
        ],
        'data_audit' => [
            'created_reason' => 'Supply usage exists but no recording available',
            'created_by_service' => 'LivestockCostService',
            'created_at' => now()->toIso8601String(),
        ],
        'data' => [
            'recording_type' => 'minimal',
            'purpose' => 'supply_usage_cost_calculation',
            'auto_generated' => true,
        ],
        'created_by' => Auth::id() ?? \App\Models\User::whereHas('roles', function ($q) {
            $q->where('name', 'SuperAdmin');
        })->first()?->id,
        'updated_by' => Auth::id() ?? \App\Models\User::whereHas('roles', function ($q) {
            $q->where('name', 'SuperAdmin');
        })->first()?->id,
    ]);

    return $recording;
}
```

### RecordingDataService Fix

```php
// Fixed supply usage query to use whereDate for proper date comparison
$supplyUsage = SupplyUsage::where('livestock_id', $livestockId)
    ->whereDate('usage_date', $date)  // Fixed: was where('usage_date', $date)
    ->first();

if ($supplyUsage) {
    $supplyQuantities = [];
    foreach ($supplyUsage->details as $detail) {
        $supplyQuantities[$detail->supply_id] = $detail->quantity_taken;
    }
    $data['supplyQuantities'] = $supplyQuantities;
    $data['supplyUsageId'] = $supplyUsage->id;
}
```

## Usage Examples

### 1. Calculate Supply Usage Costs for a Specific Date

```bash
# Calculate costs for a specific date
php artisan supply:calculate-costs --livestock-id=9f6acd12-7f57-41da-aef8-f9d0b91a7fbf --date=2025-07-02

# Force recalculation
php artisan supply:calculate-costs --livestock-id=9f6acd12-7f57-41da-aef8-f9d0b91a7fbf --date=2025-07-02 --force
```

### 2. Calculate Supply Usage Costs for a Date Range

```bash
# Calculate costs for a date range
php artisan supply:calculate-costs --livestock-id=9f6acd12-7f57-41da-aef8-f9d0b91a7fbf --start-date=2025-07-01 --end-date=2025-07-31
```

### 3. Programmatic Usage

```php
use App\Services\Supply\SupplyUsageCostService;

$service = app(SupplyUsageCostService::class);

// Calculate for a specific date
$result = $service->calculateForDate($livestockId, '2025-07-02');

// Calculate for a date range
$result = $service->calculateForRange($livestockId, '2025-07-01', '2025-07-31');
```

## Data Flow

1. **Supply Usage Creation**: Supply usage records are created with details
2. **Cost Calculation Trigger**: When calculating livestock costs
3. **Recording Check**: System checks if recording exists for the date
4. **Minimal Recording Creation**: If no recording exists but supply usage does, create minimal recording
5. **Cost Calculation**: Calculate supply usage costs using FIFO method
6. **LivestockCost Creation**: Create or update LivestockCost record with supply usage costs

## Performance Monitoring

The system includes comprehensive performance monitoring:

-   Execution time tracking
-   Memory usage monitoring
-   Cache hit/miss statistics
-   Performance bottleneck detection
-   Detailed logging for debugging

## Testing

### Unit Tests

```bash
# Run unit tests
php artisan test tests/Unit/Services/Livestock/LivestockCostServiceTest.php
```

### Integration Tests

```bash
# Run integration tests
php artisan test tests/Feature/SupplyUsageCostIntegrationTest.php
```

## Troubleshooting

### Common Issues

1. **"Column not found" errors**: Ensure all database migrations are run
2. **"created_by cannot be null"**: The system automatically uses SuperAdmin user as fallback
3. **Supply usage not appearing in payload**: Check if `RecordingDataService` is using `whereDate` instead of `where`

### Debug Commands

```bash
# Check supply usage data
php artisan tinker --execute="echo 'Supply Usage Data:'; \$usages = \App\Models\SupplyUsage::where('livestock_id', '9f6acd12-7f57-41da-aef8-f9d0b91a7fbf')->whereDate('usage_date', '2025-07-02')->get(); echo 'Count: ' . \$usages->count();"

# Check minimal recordings
php artisan tinker --execute="echo 'Minimal Recordings:'; \$recordings = \App\Models\Recording::where('livestock_id', '9f6acd12-7f57-41da-aef8-f9d0b91a7fbf')->whereJsonContains('payload->recording_type', 'minimal_for_supply_usage')->get(); echo 'Count: ' . \$recordings->count();"
```

## Future Enhancements

1. **Bulk Processing**: Add support for bulk supply usage cost calculations
2. **Advanced Caching**: Implement more sophisticated caching strategies
3. **Real-time Updates**: Add real-time cost updates when supply usage changes
4. **Cost Analytics**: Add detailed cost analytics and reporting features
5. **Multi-currency Support**: Add support for multiple currencies in cost calculations

## Conclusion

The supply usage cost integration provides a robust, safe, and efficient solution for calculating livestock costs including supply usage costs. The minimal recording approach ensures data integrity while the fixed data loading ensures accurate payload generation. The system is production-ready with comprehensive testing, monitoring, and documentation.

# Daily Report Detail/Simple Mode Feature

## Overview

Enhanced daily report feature that allows users to choose between two display modes:

-   **Simple Mode**: Aggregated data per coop (kandang)
-   **Detail Mode**: Individual batch data within each coop

## Feature Description

### Simple Mode (Per Kandang)

-   Groups all livestock batches within the same coop
-   Shows aggregated totals for each coop
-   Maintains existing behavior for backward compatibility
-   Faster processing and simpler view

### Detail Mode (Per Batch)

-   Shows individual data for each livestock batch
-   Groups by coop name but displays each batch separately
-   Provides more granular insights into batch performance
-   Headers show coop name with individual batch rows underneath

## Implementation Details

### Files Modified

#### 1. `resources/views/pages/reports/index_report_harian.blade.php`

-   Added report type selection dropdown
-   Updated JavaScript to handle new parameter
-   Enhanced form validation and reset functionality

#### 2. `app/Http/Controllers/ReportsController.php`

-   Modified `exportHarian()` method to handle both modes
-   Added `report_type` validation
-   Implemented separate logic for detail vs simple processing
-   Enhanced logging for both modes

#### 3. `resources/views/pages/reports/harian.blade.php`

-   Dynamic table headers based on report type
-   Conditional rendering for batch details
-   Proper rowspan handling for grouped coop display
-   Enhanced footer calculations

## Usage Instructions

### Accessing the Feature

1. Navigate to Reports → Daily Report
2. Select desired Farm
3. Choose report date
4. Select Report Type:
    - **Simple (Per Kandang)**: For aggregated view
    - **Detail (Per Batch)**: For individual batch analysis
5. Click "Tampilkan" to generate report

### Report Type Selection

```html
<select class="form-select" id="report_type" name="report_type" required>
    <option value="simple">Simple (Per Kandang)</option>
    <option value="detail">Detail (Per Batch)</option>
</select>
```

## Technical Implementation

### Controller Logic

```php
if ($reportType === 'detail') {
    // Process individual batches within each coop
    foreach ($livestocksByCoopNama as $coopNama => $coopLivestocks) {
        $coopData = [];
        foreach ($coopLivestocks as $livestock) {
            // Individual batch processing
            $batchData = [...];
            $coopData[] = $batchData;
        }
        $recordings[$coopNama] = $coopData;
    }
} else {
    // Aggregate data per coop (existing logic)
    foreach ($livestocksByCoopNama as $coopNama => $coopLivestocks) {
        $aggregatedData = [...];
        // Aggregate calculations
        $recordings[$coopNama] = $aggregatedData;
    }
}
```

### View Rendering

```php
@if($reportType === 'detail')
    @foreach($recordings as $coopNama => $batchesData)
        @foreach($batchesData as $index => $batch)
            <tr>
                @if($index === 0)
                <td rowspan="{{ count($batchesData) }}">{{ $coopNama }}</td>
                @endif
                <!-- Batch-specific data -->
            </tr>
        @endforeach
    @endforeach
@else
    @foreach($recordings as $coopNama => $record)
        <!-- Aggregated coop data -->
    @endforeach
@endif
```

## Data Structure

### Simple Mode Data Structure

```php
$recordings = [
    'Kandang 1' => [
        'umur' => 45,
        'stock_awal' => 10462,
        'mati' => 100,
        'total_deplesi' => 200,
        // ... aggregated values
    ]
];
```

### Detail Mode Data Structure

```php
$recordings = [
    'Kandang 1' => [
        [
            'livestock_id' => 'uuid-1',
            'livestock_name' => 'Batch A',
            'umur' => 45,
            'stock_awal' => 5714,
            // ... individual batch values
        ],
        [
            'livestock_id' => 'uuid-2',
            'livestock_name' => 'Batch B',
            'umur' => 45,
            'stock_awal' => 4748,
            // ... individual batch values
        ]
    ]
];
```

## Benefits

### Simple Mode Benefits

-   ✅ Fast processing for large datasets
-   ✅ Clean, concise view
-   ✅ Good for overall farm performance overview
-   ✅ Maintains existing user experience

### Detail Mode Benefits

-   ✅ Granular batch-level insights
-   ✅ Individual batch performance tracking
-   ✅ Better for identifying underperforming batches
-   ✅ Detailed analysis capabilities

## Validation & Error Handling

### Request Validation

```php
$request->validate([
    'farm' => 'required',
    'tanggal' => 'required|date',
    'report_type' => 'required|in:simple,detail'
]);
```

### Frontend Validation

-   All required fields must be filled
-   Report type selection is mandatory
-   Enhanced error messages for missing parameters

## Logging & Debugging

Enhanced logging for both modes:

```php
Log::info('Export Harian Report', [
    'farm_id' => $farm->id,
    'tanggal' => $tanggal->format('Y-m-d'),
    'report_type' => $reportType,
    'livestock_count' => $livestocks->count()
]);
```

Mode-specific logging:

-   Detail mode: Individual livestock processing logs
-   Simple mode: Aggregated coop calculation logs

## Performance Considerations

### Simple Mode

-   Faster query execution
-   Lower memory usage
-   Suitable for large datasets

### Detail Mode

-   More database queries per livestock
-   Higher memory usage for complex farms
-   Recommended for smaller date ranges

## Future Enhancements

1. **Export Options**: PDF/Excel export for both modes
2. **Batch Comparison**: Side-by-side batch performance comparison
3. **Historical Trends**: Batch performance over time
4. **Performance Metrics**: Additional KPIs per batch
5. **Custom Grouping**: Group by other criteria beyond coop

## Troubleshooting

### Common Issues

1. **No data showing**: Check if livestock exists for selected farm/date
2. **Performance issues**: Use Simple mode for large datasets
3. **Missing batches**: Verify livestock start_date <= report date

### Debug Steps

1. Check logs for processing details
2. Verify request parameters
3. Confirm livestock data exists
4. Review database relationships

## Version History

-   v1.0: Initial implementation with Simple/Detail modes
-   Future: Enhanced features and optimizations

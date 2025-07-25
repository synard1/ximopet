# Laporan Pemakaian Supply/OVK

## Overview

Fitur laporan pemakaian supply/OVK memungkinkan pengguna untuk melihat dan menganalisis data pemakaian supply/OVK berdasarkan periode waktu tertentu. Laporan ini mendukung filter berdasarkan farm, kandang, batch, dan jenis supply.

## Fitur Utama

### 1. Filter Laporan

-   **Farm**: Filter berdasarkan farm tertentu
-   **Kandang**: Filter berdasarkan kandang dalam farm yang dipilih
-   **Batch**: Filter berdasarkan batch ternak tertentu
-   **Jenis Supply**: Filter berdasarkan jenis supply/OVK tertentu
-   **Range Tanggal**: Filter berdasarkan periode waktu (tanggal mulai - tanggal selesai)
-   **Tipe Laporan**: Pilihan antara detail (per record) atau simple (per batch/hari)

### 2. Tipe Laporan

#### Detail Report (Per Record)

-   Menampilkan setiap record pemakaian supply secara individual
-   Informasi lengkap: tanggal, batch, kandang, jenis supply, jumlah, satuan, harga satuan, total harga, status
-   Cocok untuk analisis detail dan audit trail

#### Simple Report (Per Batch/Hari)

-   Data diagregasi per batch per hari
-   Menampilkan total quantity dan biaya per batch per hari
-   Breakdown supply per batch dalam satu kolom
-   Cocok untuk overview dan analisis trend

### 3. Export Options

-   **HTML**: Tampilan web dengan styling lengkap
-   **Excel**: File .xlsx dengan format yang rapi
-   **PDF**: File .pdf dengan layout yang dioptimalkan untuk print
-   **CSV**: File .csv untuk analisis lanjutan

## Implementasi Teknis

### 1. Service Layer

**File**: `app/Services/Report/SupplyUsageReportService.php`

#### Method Utama:

-   `generateSupplyUsageReport()`: Generate data laporan
-   `getSupplyUsageData()`: Query data dari database
-   `processDetailReport()`: Proses data untuk laporan detail
-   `processSimpleReport()`: Proses data untuk laporan simple
-   `exportSupplyUsage()`: Handle export dalam berbagai format

#### Fitur Service:

-   Validasi parameter input
-   Logging untuk debugging dan audit
-   Error handling yang komprehensif
-   Support untuk multiple export format
-   Performance optimization dengan eager loading

### 2. Controller Layer

**File**: `app/Http/Controllers/ReportsController.php`

#### Method:

-   `indexSupplyUsage()`: Tampilkan halaman index dengan filter
-   `exportSupplyUsage()`: Handle request export

### 3. View Layer

#### Index Page

**File**: `resources/views/pages/reports/index_report_supply_usage.blade.php`

-   Form filter dengan validasi client-side
-   AJAX loading untuk data laporan
-   Export buttons dengan loading state
-   Responsive design dengan Bootstrap

#### HTML Export

**File**: `resources/views/pages/reports/supply-usage.blade.php`

-   Styling modern dengan gradient header
-   Summary box dengan key metrics
-   Responsive table dengan sticky header
-   Supply breakdown untuk simple report
-   Summary per jenis supply

#### PDF Export

**File**: `resources/views/pages/reports/supply-usage-pdf.blade.php`

-   Optimized untuk PDF generation
-   Compact layout dengan font size yang sesuai
-   Page break untuk summary section
-   Print-friendly styling

### 4. Routes

```php
// Supply Usage Report
Route::get('/reports/supply-usage', [ReportsController::class, 'indexSupplyUsage'])->name('reports.supply-usage');
Route::post('/reports/supply-usage/export', [ReportsController::class, 'exportSupplyUsage'])->name('reports.supply-usage.export');
```

## Struktur Data

### Input Parameters

```php
[
    'farm_id' => 'uuid',           // Required
    'coop_id' => 'uuid',           // Optional
    'livestock_id' => 'uuid',      // Optional
    'supply_id' => 'uuid',         // Optional
    'start_date' => 'Y-m-d',       // Required
    'end_date' => 'Y-m-d',         // Required
    'report_type' => 'detail|simple', // Required
    'export_format' => 'html|excel|pdf|csv' // Optional
]
```

### Output Data Structure

```php
[
    'farm' => Farm Model,
    'startDate' => Carbon,
    'endDate' => Carbon,
    'reportType' => 'detail|simple',
    'data' => [
        // Detail Report
        [
            'usage_date' => Carbon,
            'livestock_name' => string,
            'coop_name' => string,
            'supply_name' => string,
            'quantity' => float,
            'unit' => string,
            'unit_cost' => float,
            'total_cost' => float,
            'status' => string,
            'notes' => string
        ],
        // Simple Report
        [
            'usage_date' => Carbon,
            'livestock_name' => string,
            'coop_name' => string,
            'supply_breakdown' => [
                'supply_name' => [
                    'quantity' => float,
                    'cost' => float,
                    'unit' => string
                ]
            ],
            'total_quantity' => float,
            'total_cost' => float,
            'supply_count' => int
        ]
    ],
    'totals' => [
        'total_cost' => float,
        'total_quantity' => float,
        'total_records' => int,
        'supply_types' => [
            'supply_name' => [
                'quantity' => float,
                'cost' => float,
                'unit' => string
            ]
        ]
    ],
    'summary' => [
        'period' => string,
        'total_records' => int,
        'total_cost' => float,
        'total_quantity' => float,
        'supply_types_count' => int
    ]
]
```

## Database Queries

### Main Query

```php
SupplyUsage::with(['livestock.coop', 'livestock.farm', 'details.supply', 'details.unit'])
    ->whereHas('livestock', function ($q) use ($farm) {
        $q->where('farm_id', $farm->id);
    })
    ->whereBetween('usage_date', [$startDate, $endDate])
    ->whereIn('status', ['pending', 'in_process', 'completed'])
    ->orderBy('usage_date', 'desc')
    ->get();
```

### Filter Queries

-   **Livestock Filter**: `->where('livestock_id', $params['livestock_id'])`
-   **Coop Filter**: `->whereHas('livestock', fn($q) => $q->where('coop_id', $params['coop_id']))`
-   **Supply Filter**: `->whereHas('details', fn($q) => $q->where('supply_id', $params['supply_id']))`

## Performance Considerations

### 1. Database Optimization

-   Eager loading untuk relationships
-   Proper indexing pada kolom yang sering di-filter
-   Query optimization dengan whereHas untuk nested filters

### 2. Memory Management

-   Pagination untuk data besar (future enhancement)
-   Streaming response untuk export file besar
-   Cleanup temporary files setelah export

### 3. Caching Strategy

-   Cache farm, coop, livestock data untuk dropdown
-   Cache supply list untuk filter
-   Redis cache untuk report data (future enhancement)

## Error Handling

### 1. Validation Errors

-   Parameter validation dengan custom error messages
-   Client-side validation untuk UX yang lebih baik
-   Server-side validation untuk security

### 2. Database Errors

-   Graceful handling untuk missing relationships
-   Fallback values untuk null data
-   Comprehensive logging untuk debugging

### 3. Export Errors

-   File permission checks
-   Disk space validation
-   Timeout handling untuk large exports

## Logging

### Log Levels

-   **Info**: Report generation, successful exports
-   **Warning**: Missing data, fallback scenarios
-   **Error**: Validation failures, database errors, export failures
-   **Debug**: Query details, data processing steps

### Log Context

```php
Log::info('Generating Supply Usage Report', [
    'farm_id' => $farm->id,
    'farm_name' => $farm->name,
    'start_date' => $startDate->format('Y-m-d'),
    'end_date' => $endDate->format('Y-m-d'),
    'report_type' => $reportType,
    'user_id' => Auth::id()
]);
```

## Security Considerations

### 1. Authorization

-   Farm-based access control
-   User permission validation
-   Company isolation for multi-tenant setup

### 2. Input Validation

-   SQL injection prevention
-   XSS protection
-   File upload security for exports

### 3. Data Privacy

-   Sensitive data masking
-   Audit trail for report access
-   Secure file storage for exports

## Testing

### 1. Unit Tests

-   Service method testing
-   Data processing validation
-   Export format testing

### 2. Integration Tests

-   End-to-end report generation
-   Export functionality testing
-   Error scenario handling

### 3. Performance Tests

-   Large dataset handling
-   Export file size limits
-   Memory usage optimization

## Future Enhancements

### 1. Advanced Features

-   Chart visualization
-   Trend analysis
-   Comparative reporting
-   Automated scheduling

### 2. Performance Improvements

-   Background job processing
-   Caching implementation
-   Database query optimization
-   Pagination for large datasets

### 3. User Experience

-   Real-time data updates
-   Interactive filters
-   Custom report templates
-   Mobile-responsive design

## Usage Examples

### 1. Basic Usage

```php
// Generate report
$service = new SupplyUsageReportService();
$report = $service->generateSupplyUsageReport([
    'farm_id' => 'uuid',
    'start_date' => '2024-01-01',
    'end_date' => '2024-01-31',
    'report_type' => 'detail'
]);

// Export to Excel
$response = $service->exportSupplyUsage($report, 'excel');
```

### 2. With Filters

```php
$report = $service->generateSupplyUsageReport([
    'farm_id' => 'uuid',
    'coop_id' => 'uuid',
    'livestock_id' => 'uuid',
    'supply_id' => 'uuid',
    'start_date' => '2024-01-01',
    'end_date' => '2024-01-31',
    'report_type' => 'simple'
]);
```

### 3. Web Interface

1. Navigate to `/reports/supply-usage`
2. Select farm from dropdown
3. Choose optional filters (coop, batch, supply)
4. Set date range
5. Select report type
6. Click "Tampilkan" to view report
7. Use export buttons for different formats

## Troubleshooting

### Common Issues

#### 1. No Data Displayed

-   Check if supply usage records exist for the selected period
-   Verify farm_id is correct
-   Check supply usage status (pending, in_process, completed)

#### 2. Export Failures

-   Verify disk space for file generation
-   Check file permissions in storage directory
-   Validate export format parameter

#### 3. Performance Issues

-   Check database indexes on usage_date and farm_id
-   Monitor memory usage for large datasets
-   Consider implementing pagination

### Debug Information

-   Check Laravel logs for detailed error messages
-   Use browser developer tools for AJAX errors
-   Verify database relationships and data integrity

## Dependencies

### Required Packages

-   `phpoffice/phpspreadsheet`: Excel export
-   `barryvdh/laravel-dompdf`: PDF export
-   `laravel/framework`: Core framework

### Database Tables

-   `supply_usages`: Main usage records
-   `supply_usage_details`: Detail items
-   `livestock`: Batch information
-   `coops`: Kandang information
-   `farms`: Farm information
-   `supplies`: Supply master data
-   `units`: Unit master data

## Maintenance

### Regular Tasks

-   Monitor log files for errors
-   Clean up temporary export files
-   Update supply and unit master data
-   Review and optimize database queries
-   Backup report configurations

### Updates

-   Keep dependencies updated
-   Monitor for security patches
-   Review performance metrics
-   Update documentation as needed

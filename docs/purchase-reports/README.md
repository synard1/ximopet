# Dokumentasi Laporan Pembelian

## Overview

Fitur laporan pembelian menyediakan kemampuan untuk menganalisis dan melaporkan data pembelian livestock, pakan, dan supply dengan berbagai filter dan format export.

## Struktur Files

```
app/Http/Controllers/
├── PurchaseReportsController.php    # Controller untuk laporan pembelian

resources/views/pages/reports/
├── index_report_pembelian_livestock.blade.php  # Form filter livestock
├── index_report_pembelian_pakan.blade.php      # Form filter pakan
├── index_report_pembelian_supply.blade.php     # Form filter supply
├── pembelian-livestock.blade.php               # Laporan hasil livestock
├── pembelian-pakan.blade.php                   # Laporan hasil pakan
└── pembelian-supply.blade.php                  # Laporan hasil supply

docs/purchase-reports/
├── README.md                        # Dokumentasi utama
├── controller-methods.md            # Detail method controller
├── view-structure.md               # Struktur view dan template
└── testing-guide.md                # Panduan testing
```

## Fitur Utama

### 1. Laporan Pembelian Livestock

**URL**: `/reports/purchase/livestock`
**Method**: `GET` (index), `POST` (export)

**Filter Tersedia**:

-   Periode (start_date, end_date) - **Required**
-   Farm - Optional
-   Supplier - Optional
-   Ekspedisi - Optional
-   Status - Optional (draft, confirmed, arrived, completed)

**Data yang Ditampilkan**:

-   Detail pembelian per transaksi
-   Breakdown per item livestock
-   Summary total pembelian, quantity, nilai
-   Analisis per status, farm, supplier

### 2. Laporan Pembelian Pakan

**URL**: `/reports/purchase/feed`
**Method**: `GET` (index), `POST` (export)

**Filter Tersedia**:

-   Periode (start_date, end_date) - **Required**
-   Farm - Optional
-   Livestock/Batch - Optional
-   Supplier - Optional
-   Jenis Pakan - Optional
-   Status - Optional

**Data yang Ditampilkan**:

-   Detail per batch pembelian
-   Breakdown per jenis pakan
-   Summary total batches, purchases, nilai
-   Analisis per status, supplier, jenis pakan

### 3. Laporan Pembelian Supply/OVK

**URL**: `/reports/purchase/supply`
**Method**: `GET` (index), `POST` (export)

**Filter Tersedia**:

-   Periode (start_date, end_date) - **Required**
-   Farm - Optional
-   Livestock/Batch - Optional
-   Supplier - Optional
-   Jenis Supply - Optional
-   Status - Optional

**Data yang Ditampilkan**:

-   Detail per batch pembelian
-   Breakdown per jenis supply
-   Summary total batches, purchases, nilai
-   Analisis per status, supplier, jenis supply

## Format Export

### 1. HTML (Default)

-   Preview langsung di browser
-   Styling responsive dengan CSS Grid
-   Print-friendly layout
-   Interactive elements

### 2. Excel (.xlsx)

-   Structured data dengan multiple sheets
-   Summary sheet + detail sheets
-   Formatting cells untuk currency dan numbers
-   **Status**: Will be implemented

### 3. PDF

-   Professional report layout
-   Header dengan logo dan info perusahaan
-   Page numbering dan timestamps
-   **Status**: Will be implemented

### 4. CSV

-   Raw data untuk further analysis
-   UTF-8 encoding dengan BOM
-   Comma separated values
-   **Status**: Will be implemented

## Data Structure

### Summary Data Structure

```php
[
    'period' => 'dd-MM-yyyy s.d. dd-MM-yyyy',
    'total_purchases' => int,
    'total_quantity' => int,
    'total_value' => float,
    'total_suppliers' => int,
    'total_farms' => int,
    'by_status' => [
        'status_name' => count
    ],
    'by_farm' => [
        'farm_name' => count
    ],
    'by_supplier' => [
        'supplier_name' => count
    ]
]
```

### Export Data Structure

```php
[
    'purchases/batches' => Collection,
    'summary' => array,
    'filters' => [
        'start_date' => Carbon,
        'end_date' => Carbon,
        'farm' => Model|null,
        'supplier' => Model|null,
        'expedition' => Model|null,
        'status' => string|null
    ]
]
```

## Validasi Input

### Request Validation Rules

```php
[
    'start_date' => 'required|date',
    'end_date' => 'required|date|after_or_equal:start_date',
    'farm_id' => 'nullable|exists:farms,id',
    'supplier_id' => 'nullable|exists:partners,id',
    'expedition_id' => 'nullable|exists:expeditions,id',
    'status' => 'nullable|in:draft,confirmed,arrived,completed',
    'export_format' => 'nullable|in:html,excel,pdf,csv'
]
```

### Frontend Validation

-   JavaScript date validation
-   Maximum date range checks
-   Required field highlighting
-   Loading states during export

## Logging & Monitoring

### Log Points

1. **Access Log**: User mengakses index page
2. **Export Log**: User generate laporan dengan filter
3. **Error Log**: Jika terjadi error saat generate
4. **Performance Log**: Query execution time

### Log Format

```php
Log::info('Export Purchase Report', [
    'user_id' => auth()->id(),
    'report_type' => 'livestock|feed|supply',
    'start_date' => 'Y-m-d',
    'end_date' => 'Y-m-d',
    'export_format' => 'html|excel|pdf|csv',
    'filters' => array,
    'execution_time' => float,
    'data_count' => int
]);
```

## Performance Considerations

### Database Optimization

-   Eager loading untuk relasi yang dibutuhkan
-   Index pada kolom yang sering di-filter (date, farm_id, supplier_id)
-   Query batching untuk large datasets
-   Pagination untuk export besar

### Memory Management

-   Chunk processing untuk export besar
-   Stream response untuk CSV/Excel
-   Memory limit monitoring
-   Garbage collection hints

### Caching Strategy

-   Cache supplier/farm/expedition lists
-   Cache expensive aggregation queries
-   Redis untuk session-based filters
-   CDN untuk static assets

## Security & Access Control

### Authorization

-   User harus memiliki permission `view_purchase_reports`
-   Farm-level access control jika applicable
-   Rate limiting untuk prevent abuse
-   CSRF protection pada form submission

### Data Privacy

-   Sensitive data masking jika diperlukan
-   Audit trail untuk akses laporan
-   Data retention policies
-   Export history logging

## Testing Strategy

### Unit Tests

-   Controller method validation
-   Data calculation accuracy
-   Filter logic testing
-   Export format validation

### Integration Tests

-   End-to-end form submission
-   Database query optimization
-   Export file generation
-   Error handling scenarios

### Manual Testing Scenarios

1. **Normal Data**: Complete data dengan semua field
2. **Empty Data**: Tidak ada data untuk filter
3. **Large Dataset**: Performance dengan data besar
4. **Edge Cases**: Filter combinations yang unusual
5. **Permission Tests**: Access control validation

## Deployment Checklist

-   [ ] Database migrations untuk index optimization
-   [ ] Environment variables untuk export limits
-   [ ] Queue configuration untuk background processing
-   [ ] Storage configuration untuk temporary files
-   [ ] Backup existing report routes
-   [ ] Performance monitoring setup
-   [ ] Error tracking configuration

## Future Enhancements

### Phase 2 Features

-   Scheduled/automated reports
-   Email delivery untuk reports
-   Chart/visualization integration
-   Advanced filtering (date ranges, custom periods)
-   Bulk export multiple reports

### Phase 3 Features

-   API endpoints untuk external integration
-   Real-time dashboard integration
-   Machine learning insights
-   Advanced analytics dan trending
-   Mobile-optimized views

## Support & Maintenance

### Regular Maintenance

-   Monthly performance review
-   Quarterly data cleanup
-   Yearly archive strategy
-   Index optimization review

### Monitoring Alerts

-   High memory usage during export
-   Long query execution times
-   Failed export attempts
-   Unusual access patterns

### Troubleshooting Common Issues

1. **Timeout during export**: Increase execution time atau implement queues
2. **Memory exhaustion**: Implement chunked processing
3. **Empty results**: Check filter logic dan database
4. **Permission errors**: Verify user roles dan permissions

---

**Created**: {{ date('Y-m-d') }}
**Version**: 1.0.0
**Maintainer**: Development Team
**Last Updated**: {{ date('Y-m-d H:i:s') }}

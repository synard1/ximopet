# Phase 3: Virtual Quantity Calculation Refactor

## Overview

Implementasi refactor untuk mendukung virtual quantity calculation pada sistem recording two-stage. Refactor ini memungkinkan operator menyimpan data penjualan tanpa mempengaruhi stock real, dengan data virtual disimpan di kolom `data` pada tabel `livestock_batches`.

## Business Requirements

1. **Two-Stage Recording**: Operator dapat menyimpan data penjualan dalam status draft tanpa mengurangi stock real
2. **Virtual Calculation**: Data penjualan disimpan secara virtual di kolom `data` batch
3. **Real Stock Protection**: Stock real (`quantity_available`) tidak terpengaruh sampai data difinalisasi
4. **Data Integrity**: Memastikan konsistensi antara data virtual dan real

## Files Modified

### 1. RecordingSaleService.php

**Changes:**

-   Menambahkan virtual calculation support pada `createWithBatches()`
-   Menambahkan method `storeVirtualSaleData()` untuk menyimpan data virtual
-   Mengintegrasikan dengan konfigurasi `CompanyConfig`
-   Menambahkan metadata untuk tracking virtual vs real mode

**Key Features:**

```php
// Check virtual mode configuration
$isVirtualMode = ($quantityCalculation['mode'] ?? 'real') === 'virtual';
$isTwoStage = ($salesConfig['recording_method']['type'] ?? 'direct') === 'two_stage';

// Store virtual data instead of updating real stock
if ($isVirtualMode) {
    $this->storeVirtualSaleData($allocation['batch_id'], $saleData['date'], $allocation);
} else {
    // Update real stock quantities
    $batch->quantity_available -= $allocation['quantity'];
    $batch->quantity_sales += $allocation['quantity'];
}
```

### 2. RecordingDataService.php

**Changes:**

-   Menambahkan virtual data loading pada `loadYesterdayData()`
-   Menambahkan method `getVirtualSalesData()` untuk mengambil data virtual
-   Prioritas data: Virtual > Recording Payload > RecordingSaleService

**Key Features:**

```php
// Load virtual sales data from batch data column
if ($isVirtualMode) {
    $virtualSalesData = $this->getVirtualSalesData($livestockId, $yesterdayDate);
    if ($virtualSalesData) {
        $yesterdaySalesQuantity = $virtualSalesData['quantity'] ?? 0;
        $yesterdaySalesWeight = $virtualSalesData['weight'] ?? 0;
        $yesterdaySalesStatus = $virtualSalesData['status'] ?? 'draft';
    }
}
```

### 3. RecordingPersistenceService.php

**Changes:**

-   Menambahkan virtual calculation support pada `saveRecording()`
-   Menambahkan method `processSalesData()`, `processDepletionData()`
-   Menambahkan method `storeVirtualDepletionData()` untuk depletion virtual
-   Mengintegrasikan dengan virtual mode untuk semua jenis data

**Key Features:**

```php
// Process sales with virtual support
$salesResult = $this->processSalesData($recordingDTO, $isVirtualMode, $isTwoStage, $livestock);

// Process depletion with virtual support
$depletionResult = $this->processDepletionData($recordingDTO, $isVirtualMode, $isTwoStage);

// Store virtual depletion data
if ($isVirtualMode) {
    $this->storeVirtualDepletionData($livestockId, $date, $quantity, $depletionType);
}
```

## New Artisan Commands

### 1. MigrateRealToVirtualQuantityCommand

**Purpose:** Migrasi data yang sudah dikalkulasi secara real ke virtual

**Usage:**

```bash
# Migrate all records
php artisan migrate:real-to-virtual

# Migrate specific livestock
php artisan migrate:real-to-virtual --livestock-id=123

# Migrate specific date
php artisan migrate:real-to-virtual --date=2025-01-15

# Dry run (show what would be migrated)
php artisan migrate:real-to-virtual --dry-run

# Force migration even if virtual data exists
php artisan migrate:real-to-virtual --force
```

**Features:**

-   Validasi konfigurasi virtual mode
-   Deteksi data yang perlu dimigrasi
-   Distribusi equal untuk virtual depletion
-   Backup metadata untuk tracking migrasi
-   Rollback support jika terjadi error

### 2. ValidateVirtualQuantityDataCommand

**Purpose:** Validasi konsistensi data virtual quantity

**Usage:**

```bash
# Validate all data
php artisan validate:virtual-quantity

# Validate specific livestock
php artisan validate:virtual-quantity --livestock-id=123

# Validate specific date
php artisan validate:virtual-quantity --date=2025-01-15

# Show detailed validation results
php artisan validate:virtual-quantity --detailed

# Fix inconsistencies automatically
php artisan validate:virtual-quantity --fix
```

**Features:**

-   Validasi konsistensi virtual vs real data
-   Deteksi missing virtual data
-   Validasi metadata consistency
-   Auto-fix untuk masalah umum
-   Detailed reporting

## Data Structure

### Virtual Sales Data Structure

```json
{
    "virtual_sales": {
        "2025-01-15": {
            "quantity": 100,
            "weight": 2500.5,
            "date": "2025-01-15",
            "status": "draft",
            "metadata": {
                "calculation_method": "fifo_allocation",
                "calculation_timestamp": "2025-01-15T10:30:00Z",
                "price_per_unit": 25000,
                "amount": 2500000,
                "allocation_order": 1
            }
        }
    }
}
```

### Virtual Depletion Data Structure

```json
{
    "virtual_depletion": {
        "2025-01-15": {
            "mortality": 5,
            "culling": 2,
            "date": "2025-01-15",
            "status": "draft",
            "metadata": {
                "calculation_method": "equal_distribution",
                "calculation_timestamp": "2025-01-15T10:30:00Z",
                "original_quantity": 7,
                "batch_quantity": 3,
                "depletion_type": "mortality"
            }
        }
    }
}
```

## Configuration

### CompanyConfig.php - Sales Configuration

```php
'quantity_calculation' => [
    'mode' => 'virtual', // 'virtual', 'real', 'hybrid'
    'virtual_settings' => [
        'enabled' => true,
        'description' => 'Virtual quantity calculation for recording without affecting real stock',
        'store_in_data_column' => true,
        'data_column_structure' => [
            'virtual_sales' => [
                'quantity' => 'int',
                'weight' => 'decimal',
                'date' => 'date',
                'status' => 'string',
                'metadata' => 'json'
            ],
            'virtual_depletion' => [
                'mortality' => 'int',
                'culling' => 'int',
                'date' => 'date',
                'status' => 'string',
                'metadata' => 'json'
            ]
        ],
        'calculation_method' => 'projection',
        'projection_settings' => [
            'enabled' => true,
            'base_on_historical_data' => true,
            'historical_days' => 7,
            'growth_rate_percentage' => 0,
            'seasonal_adjustment' => false,
        ],
    ],
    'real_settings' => [
        'enabled' => false,
        'description' => 'Real quantity calculation affecting actual stock',
        'immediate_stock_reduction' => true,
        'require_confirmation' => true,
    ],
    'hybrid_settings' => [
        'enabled' => false,
        'description' => 'Combination of virtual and real calculation',
        'virtual_for_recording' => true,
        'real_for_finalization' => true,
        'transition_threshold' => 'finalization',
    ],
],
```

## Workflow

### 1. Recording Process (Virtual Mode)

1. Operator input data penjualan
2. System check konfigurasi virtual mode
3. Data disimpan sebagai draft dengan status virtual
4. Virtual data disimpan di kolom `data` batch
5. Real stock (`quantity_available`) tidak terpengaruh

### 2. Finalization Process

1. Admin review data virtual
2. System finalize data virtual ke real
3. Real stock diupdate sesuai data virtual
4. Status berubah dari draft ke finalized

### 3. Data Loading Priority

1. **Virtual Data** (jika virtual mode enabled)
2. **Recording Payload** (fallback)
3. **RecordingSaleService** (final fallback)

## Benefits

1. **Stock Protection**: Real stock tidak terpengaruh sampai finalisasi
2. **Flexibility**: Operator dapat input data tanpa khawatir mengurangi stock
3. **Audit Trail**: Tracking lengkap untuk virtual vs real data
4. **Data Integrity**: Validasi dan konsistensi data
5. **Migration Support**: Tools untuk migrasi data existing

## Testing

### Test Virtual Calculation

```bash
# Test virtual quantity calculation
php artisan test:virtual-quantity 123 2025-01-15 --quantity=100 --weight=2500

# Test with different calculation methods
php artisan test:virtual-quantity 123 2025-01-15 --method=projection
php artisan test:virtual-quantity 123 2025-01-15 --method=estimate
php artisan test:virtual-quantity 123 2025-01-15 --method=forecast
```

### Validate Data

```bash
# Validate virtual data consistency
php artisan validate:virtual-quantity --detailed

# Fix inconsistencies
php artisan validate:virtual-quantity --fix
```

### Migrate Data

```bash
# Migrate real to virtual (dry run first)
php artisan migrate:real-to-virtual --dry-run

# Execute migration
php artisan migrate:real-to-virtual --force
```

## Logging

Semua operasi virtual calculation dilog dengan detail:

-   Virtual mode status
-   Data sources (virtual vs real)
-   Calculation methods
-   Migration tracking
-   Error handling

## Future Enhancements

1. **Hybrid Mode**: Kombinasi virtual dan real calculation
2. **Advanced Projection**: Machine learning untuk forecasting
3. **Batch Optimization**: Optimasi alokasi batch untuk virtual data
4. **Real-time Sync**: Real-time synchronization virtual-real data
5. **Advanced Validation**: Rule-based validation untuk virtual data

## Troubleshooting

### Common Issues

1. **Virtual data not loading**

    - Check virtual mode configuration
    - Verify batch data structure
    - Check data loading priority

2. **Migration errors**

    - Validate source data integrity
    - Check batch availability
    - Review error logs

3. **Validation failures**
    - Run detailed validation
    - Check data consistency
    - Use auto-fix option

### Debug Commands

```bash
# Check virtual mode configuration
php artisan tinker
>>> \App\Config\CompanyConfig::getSalesConfig()['quantity_calculation']

# Check virtual data in batch
php artisan tinker
>>> \App\Models\LivestockBatch::find(123)->data

# Validate specific record
php artisan validate:virtual-quantity --livestock-id=123 --detailed
```

## Conclusion

Refactor virtual quantity calculation berhasil mengimplementasikan sistem two-stage recording dengan proteksi stock real. Sistem ini memberikan fleksibilitas bagi operator sambil menjaga integritas data dan menyediakan tools untuk migrasi dan validasi.

# Supply Usage Performance Report Feature

## Overview

Fitur ini menambahkan perhitungan supply usage ke dalam performance report untuk memberikan analisis yang lebih komprehensif tentang penggunaan supply dalam operasi ternak.

## Fitur yang Ditambahkan

### 1. Daily Supply Usage Tracking

-   Menghitung penggunaan supply harian per livestock
-   Melacak quantity dan cost per jenis supply
-   Hanya menghitung supply usage dengan status yang valid (pending, in_process, completed)

### 2. Supply Usage Data Structure

```php
'supply_usage_by_type' => [
    'Biocid' => [
        'quantity' => 10.5,
        'cost' => 52500,
        'unit' => 'ml',
        'unit_cost' => 5000
    ],
    'Vitamin' => [
        'quantity' => 5.0,
        'cost' => 25000,
        'unit' => 'gram',
        'unit_cost' => 5000
    ]
],
'supply_total_cost' => 77500
```

### 3. Performance Metrics

-   **Total Supply Cost**: Total biaya supply untuk periode tertentu
-   **Supply Cost per Head**: Biaya supply per ekor ternak
-   **Supply Usage by Type**: Breakdown penggunaan per jenis supply
-   **Quantity per Head**: Jumlah supply per ekor ternak

### 4. Overall Totals Integration

Supply usage data terintegrasi ke dalam overall totals:

-   `total_supply_cost`: Total biaya supply untuk semua livestock
-   `supply_cost_per_head`: Rata-rata biaya supply per ekor
-   `supply_by_type`: Aggregasi penggunaan supply per jenis

## Method yang Ditambahkan

### 1. `getSupplyUsageData()`

```php
private function getSupplyUsageData(Livestock $livestock, Carbon $startDate, Carbon $endDate): array
```

-   Mengambil data supply usage untuk periode tertentu
-   Menghitung cost berdasarkan `price_per_unit` atau supply price
-   Hanya menghitung supply usage dengan status valid

### 2. `getSupplyUsageSummary()`

```php
public function getSupplyUsageSummary(Livestock $livestock, Carbon $startDate, Carbon $endDate): array
```

-   Menghasilkan summary supply usage untuk livestock tertentu
-   Menghitung cost per head dan quantity per head
-   Berguna untuk analisis individual livestock

## Data yang Dihasilkan

### Daily Records

Setiap record harian sekarang mencakup:

```php
[
    'supply_usage_by_type' => [...],
    'supply_total_cost' => 77500,
    // ... other fields
]
```

### Livestock Summary

Setiap livestock memiliki summary:

```php
'summary' => [
    'total_supply_cost' => 775000,
    'supply_by_type' => [...],
    // ... other summary fields
]
```

### Overall Report

Report keseluruhan mencakup:

```php
'overall_totals' => [
    'total_supply_cost' => 1550000,
    'supply_cost_per_head' => 775,
    'supply_by_type' => [...],
    // ... other totals
],
'all_supply_names' => ['Biocid', 'Vitamin', 'Antibiotik']
```

## Logging

Sistem menambahkan logging untuk supply usage:

```php
Log::info('Enhanced Performance Report generated successfully', [
    'total_supply_cost' => $overallTotals['total_supply_cost'],
    'supply_cost_per_head' => $overallTotals['supply_cost_per_head']
]);
```

## Validasi Data

### Status Filtering

Hanya supply usage dengan status berikut yang dihitung:

-   `pending`
-   `in_process`
-   `completed`

### Cost Calculation

-   Menggunakan `price_per_unit` dari detail jika tersedia
-   Fallback ke supply price jika `price_per_unit` tidak ada
-   Menghitung total cost = quantity × unit_cost

## Integrasi dengan Existing Features

### Feed Usage Integration

Supply usage terintegrasi dengan feed usage dalam perhitungan:

-   Keduanya dihitung per hari
-   Keduanya diaggregasi dalam overall totals
-   Keduanya tersedia dalam daily records

### Performance Metrics

Supply cost dapat digunakan untuk:

-   Analisis cost efficiency
-   Perbandingan dengan feed cost
-   Perhitungan total operational cost

## Usage Example

```php
$service = new PerformanceReportService($calculationService);
$report = $service->generateEnhancedPerformanceReport(
    $livestockIds,
    $startDate,
    $endDate
);

// Access supply usage data
foreach ($report['report'] as $livestockData) {
    $supplyCost = $livestockData['summary']['total_supply_cost'];
    $supplyByType = $livestockData['summary']['supply_by_type'];

    foreach ($supplyByType as $supplyName => $data) {
        echo "{$supplyName}: {$data['quantity']} {$data['unit']} - Rp {$data['cost']}\n";
    }
}

// Overall supply metrics
$overallSupplyCost = $report['overall_totals']['total_supply_cost'];
$supplyCostPerHead = $report['overall_totals']['supply_cost_per_head'];
```

## Benefits

1. **Complete Cost Analysis**: Memberikan gambaran lengkap biaya operasional
2. **Supply Efficiency Tracking**: Memantau efisiensi penggunaan supply
3. **Cost per Head Metrics**: Memudahkan perbandingan antar batch
4. **Detailed Breakdown**: Breakdown per jenis supply untuk analisis detail
5. **Integration**: Terintegrasi dengan existing performance metrics

## Future Enhancements

1. **Supply vs Feed Cost Ratio**: Perbandingan biaya supply vs feed
2. **Supply Efficiency Metrics**: Metrics khusus efisiensi supply
3. **Supply Cost Forecasting**: Prediksi biaya supply berdasarkan trend
4. **Supply Usage Alerts**: Alert untuk penggunaan supply yang tidak normal

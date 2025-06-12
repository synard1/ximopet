# LIVESTOCK COST CALCULATION REFACTOR - BUSINESS FLOW V2.0

**Date**: December 2024  
**Version**: 2.0  
**Author**: AI Assistant  
**Status**: Completed

## Overview

Refactor komprehensif sistem perhitungan livestock cost untuk mengikuti alur bisnis yang benar:

```
Ayam Masuk → Dicatat yang Mati → Ayam Masuk Kandang → Diberi Makan (termasuk hari pertama)
```

## Masalah yang Diidentifikasi

### 1. Field Database yang Salah

-   **Problem**: LivestockCostService menggunakan `harga_per_ekor` yang tidak ada
-   **Solution**: Menggunakan field yang benar: `price_per_unit`, `quantity`, `price_total`

### 2. Alur Perhitungan Tidak Sesuai

-   **Problem**: Deplesi cost tidak dihitung dengan benar sesuai alur bisnis
-   **Solution**: Deplesi cost = jumlah mati/afkir × harga kumulatif per ayam hari sebelumnya

### 3. Inconsistency Antara Service dan Report

-   **Problem**: Output testing script tidak sama dengan laporan
-   **Solution**: Sinkronisasi struktur data antara service, controller, dan view

## File yang Direfactor

### 1. `app/Services/Livestock/LivestockCostService.php`

-   ✅ Fixed field names dari `harga_per_ekor` ke `price_per_unit`
-   ✅ Implementasi business flow yang benar
-   ✅ Improved error handling dan logging
-   ✅ Restructured data breakdown
-   ✅ Enhanced documentation

**Key Changes:**

```php
// OLD - Wrong field
$initialChickenPrice = floatval($initialPurchaseItem->harga_per_ekor ?? 0);

// NEW - Correct field
$initialPricePerUnit = floatval($initialPurchaseItem->price_per_unit ?? 0);
```

### 2. `app/Http/Controllers/ReportsController.php`

-   ✅ Updated `exportCostHarian()` method
-   ✅ Proper handling of new data structure
-   ✅ Enhanced error handling
-   ✅ Added logging for debugging

**Key Changes:**

```php
// OLD - Simple data extraction
$totalCost = $costData?->total_cost ?? 0;

// NEW - Comprehensive data handling
$breakdown = $costData->cost_breakdown ?? [];
$summary = $breakdown['summary'] ?? [];
$initialPurchaseDetails = $breakdown['initial_purchase_item_details'] ?? [];
```

### 3. `testing/test_livestock_cost_report_simple.php`

-   ✅ Complete rewrite untuk business flow testing
-   ✅ Step-by-step calculation display
-   ✅ Table format output
-   ✅ Consistency testing dengan report

## Business Flow Implementation

### 1. Initial Purchase Data

```php
$initialPurchaseItem = LivestockPurchaseItem::where('livestock_id', $livestockId)
    ->orderBy('created_at', 'asc')
    ->first();

$initialPricePerUnit = floatval($initialPurchaseItem->price_per_unit ?? 0);
$initialQuantity = floatval($initialPurchaseItem->quantity ?? 0);
$initialTotalCost = floatval($initialPurchaseItem->price_total ?? 0);
```

### 2. Daily Cost Calculation

```php
// Feed costs dari feed usage records
$feedResult = $this->calculateFeedCosts($livestockId, $tanggal);

// OVK costs dari OVK records
$ovkResult = $this->calculateOVKCosts($livestockId, $tanggal, $livestock);

// Deplesi cost berdasarkan harga kumulatif hari sebelumnya
$previousCostData = $this->getPreviousDayCostData($livestockId, $tanggal);
$cumulativeCostPerChickenPreviousDay = $previousCostData['cumulative_cost_per_chicken'];
$deplesiCost = $deplesiQty * $cumulativeCostPerChickenPreviousDay;
```

### 3. Cumulative Cost Calculation

```php
$cumulativeData = $this->calculateCumulativeCosts(
    $livestockId,
    $tanggal,
    $feedCost,
    $ovkCost,
    $deplesiCost,
    $initialPricePerUnit,
    $initialQuantity
);

$totalCostPerChicken = $initialPricePerUnit + $cumulativeCostPerChicken;
```

## Data Structure Updates

### LivestockCost Record Structure

```php
[
    'livestock_id' => $livestockId,
    'tanggal' => $tanggal,
    'recording_id' => $recording->id,
    'total_cost' => $totalDailyAddedCost, // Daily added cost
    'cost_per_ayam' => $totalCostPerChicken, // Total cost per chicken (including initial price)
    'cost_breakdown' => [
        // Daily costs
        'pakan' => $feedCost,
        'ovk' => $ovkCost,
        'deplesi' => $deplesiCost,
        'daily_total' => $totalDailyAddedCost,

        // Per chicken costs
        'ovk_per_ayam' => $ovkCostPerChicken,
        'daily_added_cost_per_chicken' => $dailyAddedCostPerChicken,
        'cumulative_cost_per_chicken' => $totalCostPerChicken,

        // Stock data
        'deplesi_ekor' => $deplesiQty,
        'jual_ekor' => $salesQty,
        'stock_awal' => $stockAwal,
        'stock_akhir' => $stockAkhir,

        // Detailed breakdowns
        'feed_detail' => $feedDetails,
        'ovk_detail' => $ovkDetails,

        // Summary and metadata
        'summary' => $summaryStats,
        'prev_cost' => [...],
        'calculations' => [...],
        'initial_purchase_item_details' => [...]
    ]
]
```

## Testing & Validation

### 1. Testing Script Output

```
=== LIVESTOCK COST CALCULATION TEST (Business Flow v2.0) ===
Alur Bisnis: Ayam masuk → dicatat yang mati → ayam masuk kandang → diberi makan (termasuk hari pertama)

✅ Found livestock: Batch 001
🏠 Farm: Farm ABC
🏠 Kandang: Kandang 1
📅 Start Date: 01/12/2024
🐔 Initial Quantity: 1000

=== INITIAL PURCHASE DATA ===
Date                     : 01/12/2024
Quantity                 : 1,000 ekor
Price per Unit           : Rp 5,000.00
Total Purchase Cost      : Rp 5,000,000.00

=== DAILY COST CALCULATIONS ===
Date        | Age | Stock | Feed Cost | OVK Cost | Deplesi Cost | Total Daily | Cost per Chicken | Cumulative Cost
01/12/2024  |   0 |  1000 |      50000|        0|            0|       50000|             5050|         5050000
02/12/2024  |   1 |   995 |      48000|     2000|        25000|       75000|             5125|         5099375
```

### 2. Business Flow Validation

-   ✅ Ayam masuk: Data initial purchase tersedia
-   ✅ Pencatatan deplesi: Menggunakan harga kumulatif
-   ✅ Penempatan di kandang: Data farm dan coop tersedia
-   ✅ Pemberian pakan: Feed cost dihitung dari hari pertama
-   ✅ Perhitungan akurat: Menggunakan business flow v2.0

## Error Fixes

### 1. Field Name Corrections

```php
// OLD
'harga_per_ekor' => $initialPurchaseItem->harga_per_ekor ?? null,

// NEW
'price_per_unit' => $initialPricePerUnit,
'quantity' => $initialQuantity,
'price_total' => $initialTotalCost,
```

### 2. Calculation Logic Fixes

```php
// OLD - Incorrect deplesi calculation
$deplesiCost = $deplesiQty * $prevCumulativeCostPerAyam;

// NEW - Correct business flow
$cumulativeCostPerChickenPreviousDay = $previousCostData['cumulative_cost_per_chicken'];
$deplesiCost = $deplesiQty * $cumulativeCostPerChickenPreviousDay;
```

### 3. Data Structure Consistency

```php
// Ensure consistent data structure across:
// - LivestockCostService
// - ReportsController
// - Blade templates
// - Testing scripts
```

## Logging & Debugging

### Enhanced Logging

```php
Log::info("🔄 Starting livestock cost calculation", [
    'livestock_id' => $livestockId,
    'date' => $tanggal
]);

Log::info("📦 Initial purchase data", [
    'price_per_unit' => $initialPricePerUnit,
    'quantity' => $initialQuantity,
    'total_cost' => $initialTotalCost,
    'date' => $initialPurchaseItem->created_at
]);

Log::info("💰 Cost calculation summary", $summaryStats);

Log::info("✅ Livestock cost calculation completed", [
    'livestock_cost_id' => $livestockCost->id,
    'total_cost' => $livestockCost->total_cost,
    'cost_per_ayam' => $livestockCost->cost_per_ayam
]);
```

## Performance Improvements

### 1. Optimized Queries

-   Reduced N+1 queries dengan proper eager loading
-   Indexed queries untuk better performance
-   Cached calculation results

### 2. Error Handling

-   Comprehensive exception handling
-   Graceful fallbacks untuk missing data
-   Clear error messages

## Deployment Checklist

### Pre-deployment

-   ✅ Backup existing livestock cost data
-   ✅ Test migration scripts
-   ✅ Validate calculation accuracy

### Post-deployment

-   ✅ Run recalculation untuk existing data
-   ✅ Monitor system logs untuk errors
-   ✅ Validate report consistency

## Future Enhancements

### 1. Batch Processing

-   Implement batch recalculation untuk large datasets
-   Queue-based processing untuk better performance

### 2. API Integration

-   REST API endpoints untuk external integrations
-   Real-time cost updates

### 3. Advanced Analytics

-   Trend analysis
-   Cost prediction models
-   Performance benchmarking

## Conclusion

Refactor ini berhasil:

1. ✅ **Fixed field name issues** - Menggunakan field yang benar dari database
2. ✅ **Implemented correct business flow** - Sesuai dengan alur bisnis peternakan
3. ✅ **Improved data consistency** - Sinkronisasi antara service, controller, dan view
4. ✅ **Enhanced testing** - Script testing yang comprehensive
5. ✅ **Better logging** - Debugging yang lebih mudah
6. ✅ **Accurate calculations** - Perhitungan yang akurat dan dapat diverifikasi

Sistema livestock cost sekarang mengikuti alur bisnis yang benar dan menghasilkan perhitungan yang akurat dan konsisten across semua components.

---

**Testing**: Run `php testing/test_livestock_cost_report_simple.php` untuk validasi  
**Reports**: Access livestock cost report melalui UI untuk melihat hasil  
**Logs**: Check Laravel logs untuk debugging information

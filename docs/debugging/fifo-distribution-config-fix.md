# FIFO Distribution Configuration Fix

**Date:** 2025-01-24 14:00:00  
**Issue:** FIFO depletion tidak dapat mendistribusikan quantity penuh (shortfall 1 unit dari 15 yang diminta)  
**Location:** `app/Services/Livestock/FIFODepletionService.php`, `app/Config/CompanyConfig.php`  
**Status:** ✅ RESOLVED

## Problem Description

Dari screenshot terlihat error:

-   **Total Quantity Diminta:** 15 ekor
-   **Total Available:** 14 ekor
-   **Shortfall:** 1 unit
-   **Error:** "Cannot fulfill FIFO depletion request completely. Can only distribute 14 out of 15 requested. Shortfall: 1 units."

### Root Cause Analysis

**Primary Issues:**

1. **Metode Proporsional Bermasalah**: Menggunakan `floor()` dalam distribusi proporsional menyebabkan sisa quantity hilang
2. **Tidak Ada Remainder Handling**: Sisa pembagian tidak didistribusikan ke batch manapun
3. **Konfigurasi Default Kurang Optimal**: Default menggunakan `proportional` yang bermasalah
4. **Tidak Mengikuti Prinsip FIFO**: Batch terlama tidak diprioritaskan untuk remainder

**Technical Issues:**

```php
// Masalah pada calculateProportionalDistribution
$depletionQuantity = min(
    floor($totalQuantity * $proportion), // floor() membuang sisa
    $availableQuantity,
    $remainingToDistribute
);
```

Contoh masalah:

-   Batch A: 8892 ekor, proporsi = 52.9%, floor(15 \* 0.529) = 7 ekor
-   Batch B: 7975 ekor, proporsi = 47.1%, floor(15 \* 0.471) = 7 ekor
-   **Total terdistribusi: 14 ekor (kehilangan 1 ekor)**

## Solution Applied

### 1. **Fixed Configuration Default**

**File:** `app/Config/CompanyConfig.php`

**Before:**

```php
'quantity_distribution' => [
    'method' => 'proportional', // Bermasalah
    'allow_partial_batch_depletion' => true,
    'min_batch_remaining' => 0,
    'preserve_batch_integrity' => false
],
```

**After:**

```php
'quantity_distribution' => [
    'method' => 'sequential', // Lebih stabil dan mengikuti FIFO
    'allow_partial_batch_depletion' => true,
    'min_batch_remaining' => 0,
    'preserve_batch_integrity' => false
],
```

### 2. **Enhanced Sequential Distribution**

**File:** `app/Services/Livestock/FIFODepletionService.php`

**Improvements:**

-   Fixed percentage calculation menggunakan original quantity
-   Added null safety untuk batch properties
-   Better error handling untuk missing data
-   Improved distribution method labeling

**Before:**

```php
'percentage' => round(($depletionQuantity / ($remainingQuantity + array_sum(array_column($distribution, 'depletion_quantity')))) * 100, 2),
'distribution_method' => 'sequential'
```

**After:**

```php
'percentage' => round(($depletionQuantity / $originalQuantity) * 100, 2),
'distribution_method' => 'sequential_fifo'
```

### 3. **Added Proportional Distribution with Remainder Handling**

Created new method `calculateProportionalDistributionWithRemainder()`:

**Key Features:**

-   **FIFO Priority**: Sort batches by age (oldest first)
-   **Remainder Distribution**: Sisa quantity diberikan ke batch terlama
-   **Complete Distribution**: Memastikan semua quantity terdistribusi

```php
private function calculateProportionalDistributionWithRemainder($batches, int $totalQuantity, bool $preserveBatchIntegrity): array
{
    // Sort by age (oldest first) for FIFO principle
    usort($batchData, function($a, $b) {
        return $b['age_days'] <=> $a['age_days'];
    });

    // Calculate proportional distribution
    foreach ($batchData as $index => $data) {
        $proportion = $availableQuantity / $totalAvailable;
        $baseAllocation = floor($totalQuantity * $proportion);

        // For the oldest batch, add any remainder
        if ($index === 0) {
            $totalAllocated = 0;
            foreach ($batchData as $tempData) {
                $tempProportion = $tempData['available_quantity'] / $totalAvailable;
                $totalAllocated += floor($totalQuantity * $tempProportion);
            }
            $remainder = $totalQuantity - $totalAllocated;
            $baseAllocation += $remainder; // Sisa diberikan ke batch terlama
        }
    }
}
```

## Configuration Options Analysis

### **Sequential Method (Recommended)**

```php
'method' => 'sequential'
```

**Characteristics:**

-   ✅ **FIFO Compliant**: Mengambil dari batch terlama dulu
-   ✅ **Complete Distribution**: Selalu mendistribusikan semua quantity
-   ✅ **Simple & Reliable**: Logika sederhana, mudah diprediksi
-   ✅ **Single or Multiple Batch**: Bisa menggunakan 1 batch atau lebih sesuai kebutuhan

**Use Cases:**

-   Depletion rutin harian
-   Ketika ingin menghabiskan batch terlama dulu
-   Sistem yang membutuhkan predictable behavior

### **Proportional Method (Advanced)**

```php
'method' => 'proportional'
```

**Characteristics:**

-   ✅ **Balanced Distribution**: Distribusi merata berdasarkan proporsi
-   ✅ **FIFO Enhanced**: Remainder diberikan ke batch terlama
-   ⚠️ **Complex Logic**: Lebih kompleks, butuh testing lebih

**Use Cases:**

-   Ketika ingin distribusi merata
-   Batch sizes sangat bervariasi
-   Analisis yang membutuhkan proporsi

### **Balanced Method**

```php
'method' => 'balanced'
```

**Characteristics:**

-   ✅ **Equilibrium**: Menjaga keseimbangan antar batch
-   ✅ **Multiple Rounds**: Distribusi dalam beberapa putaran
-   ⚠️ **Performance**: Lebih lambat untuk batch banyak

**Use Cases:**

-   Ketika ingin menjaga batch tetap seimbang
-   Management yang membutuhkan batch uniformity

## Configuration Recommendations

### **For Production Use**

```php
'quantity_distribution' => [
    'method' => 'sequential', // Paling stabil dan FIFO compliant
    'allow_partial_batch_depletion' => true, // Fleksibilitas tinggi
    'min_batch_remaining' => 0, // Bisa menghabiskan batch
    'preserve_batch_integrity' => false // Tidak perlu preserve
],
```

### **For Advanced Analytics**

```php
'quantity_distribution' => [
    'method' => 'proportional', // Distribusi berdasarkan proporsi
    'allow_partial_batch_depletion' => true,
    'min_batch_remaining' => 1, // Sisakan minimal 1 untuk tracking
    'preserve_batch_integrity' => true // Maintain batch structure
],
```

### **For Batch Management**

```php
'quantity_distribution' => [
    'method' => 'balanced', // Jaga keseimbangan
    'allow_partial_batch_depletion' => true,
    'min_batch_remaining' => 5, // Sisakan minimal 5 untuk sustainability
    'preserve_batch_integrity' => false
],
```

## Testing Results

### Before Fix (Proportional with Bug)

-   ❌ **Shortfall**: 1 unit dari 15 yang diminta
-   ❌ **Error**: "Cannot fulfill FIFO depletion request completely"
-   ❌ **Distribution**: 14 terdistribusi, 1 hilang

### After Fix (Sequential)

-   ✅ **Complete Distribution**: Semua 15 unit terdistribusi
-   ✅ **FIFO Compliant**: Batch terlama diprioritaskan
-   ✅ **Flexible**: Bisa 1 batch atau multiple batch
-   ✅ **Predictable**: Behavior yang konsisten

### Proportional with Remainder (Advanced)

-   ✅ **Complete Distribution**: Semua quantity terdistribusi
-   ✅ **FIFO Enhanced**: Remainder ke batch terlama
-   ✅ **Proportional**: Distribusi berdasarkan proporsi batch
-   ✅ **Balanced**: Lebih merata antar batch

## Implementation Guide

### **Step 1: Choose Distribution Method**

```php
// In livestock configuration or CompanyConfig
'depletion_methods' => [
    'fifo' => [
        'quantity_distribution' => [
            'method' => 'sequential', // or 'proportional', 'balanced'
        ]
    ]
]
```

### **Step 2: Configure Parameters**

```php
'quantity_distribution' => [
    'method' => 'sequential',
    'allow_partial_batch_depletion' => true, // Allow partial depletion
    'min_batch_remaining' => 0, // Minimum to keep in batch
    'preserve_batch_integrity' => false // Don't force batch preservation
],
```

### **Step 3: Test with Different Scenarios**

-   **Single Batch**: Quantity <= batch size
-   **Multiple Batch**: Quantity > largest batch
-   **Odd Numbers**: Test dengan angka ganjil
-   **Edge Cases**: Test dengan quantity = total available

## Performance Considerations

### **Sequential Method**

-   ⚡ **Fast**: O(n) complexity
-   💾 **Memory Efficient**: Minimal memory usage
-   🔄 **Scalable**: Works well with many batches

### **Proportional Method**

-   ⚡ **Moderate**: O(n log n) complexity due to sorting
-   💾 **Memory Usage**: Higher due to temporary arrays
-   🔄 **Scalable**: Good for moderate batch counts

### **Balanced Method**

-   ⚡ **Slower**: O(n²) in worst case
-   💾 **Memory Intensive**: Multiple iterations
-   🔄 **Limited Scale**: Best for < 50 batches

## Related Files Modified

1. `app/Config/CompanyConfig.php`

    - Line 240: Changed default method from 'proportional' to 'sequential'

2. `app/Services/Livestock/FIFODepletionService.php`

    - Line 298-343: Enhanced calculateSequentialDistribution method
    - Line 344-420: Fixed calculateProportionalDistribution method
    - Line 421-490: Added calculateProportionalDistributionWithRemainder method

3. `docs/debugging/fifo-distribution-config-fix.md`
    - Complete documentation of changes and configuration options

---

**Resolution Time:** 60 minutes  
**Complexity:** Medium-High  
**Risk Level:** Low (fallback to sequential method)  
**Testing Required:** Manual UI testing + Multiple scenarios ✅ Completed

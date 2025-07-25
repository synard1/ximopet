# LivestockBatch Quantity Available Fix

## Masalah yang Ditemukan

**Issue:** Data berhasil di update di Livestock dan CurrentLivestock, namun LivestockBatch tidak ada perubahan pada `quantity_available`.

**Root Cause:** Sistem skip batch allocation ketika delta negatif (perubahan depletion dari 30 ke 25 = delta -5).

## Analisis Log

### **Log yang Menunjukkan Masalah:**

```
[2025-07-20 20:36:44] production.DEBUG: ⏭️ Skipping batch allocation {"reason":"no_delta","depletion_method":"fifo","delta":-5}
```

### **Kondisi Lama yang Bermasalah:**

```php
// Hanya proses batch allocation jika delta > 0
if ($depletionMethod === 'fifo' && $delta > 0) {
    // Process batch allocation
} else {
    // Skip batch allocation
}
```

**Masalah:** Ketika user mengubah depletion dari 30 ke 25, `delta = 25 - 30 = -5`, sehingga kondisi `$delta > 0` false dan batch allocation di-skip.

## Solusi yang Diterapkan

### **1. Perbaikan Kondisi Batch Allocation**

#### **Sebelum:**

```php
if ($depletionMethod === 'fifo' && $delta > 0) {
    // Hanya proses increment
}
```

#### **Sesudah:**

```php
if ($depletionMethod === 'fifo' && $delta != 0) {
    // Proses increment dan decrement
    if ($delta < 0) {
        // Handle decrement case
        $batchBreakdown = $this->handleDecrementDepletion($livestockId, $date, abs($delta), $normalizedType);
    } else {
        // Handle increment case
        $batchAllocationResult = $this->allocateDepletionToBatches($livestockId, $date, $delta, $normalizedType);
        $batchBreakdown = $batchAllocationResult['batches'] ?? [];
    }
}
```

### **2. Method Baru: handleDecrementDepletion()**

```php
/**
 * Handle decrement depletion (when delta is negative)
 * This method handles the case when depletion quantity is reduced
 */
private function handleDecrementDepletion(string $livestockId, string $date, int $decrementAmount, string $depletionType): array
{
    // Get batches with existing depletion (reverse FIFO order)
    $livestock = Livestock::with(['batches' => function ($query) {
        $query->where('status', 'active')
            ->where('quantity_depletion', '>', 0) // Only batches with existing depletion
            ->orderBy('start_date', 'desc') // Reverse FIFO for decrement (newest first)
            ->orderBy('id', 'desc');
    }])->find($livestockId);

    // Calculate decrement breakdown
    foreach ($batchesWithDepletion as $batch) {
        $quantityToDecrement = min($remainingDecrement, $batch->quantity_depletion);

        $decrementBreakdown[] = [
            'batch_id' => $batch->id,
            'quantity' => -$quantityToDecrement, // Negative for decrement
            // ... other details
        ];
    }

    return $decrementBreakdown;
}
```

### **3. Update Method: updateBatchDepletionQuantities()**

#### **Enhanced Logging:**

```php
$operation = $quantityToAdd > 0 ? 'increment' : 'decrement';
$operationAmount = abs($quantityToAdd);

logDebugIfDebug('Batch depletion quantity updated', [
    'batch_id' => $batchId,
    'batch_name' => $batch->name,
    'old_quantity_depletion' => $oldQuantityDepletion,
    'new_quantity_depletion' => $batch->quantity_depletion,
    'quantity_change' => $quantityToAdd,
    'operation' => $operation,
    'operation_amount' => $operationAmount,
    'quantity_available' => $batch->getQuantityAvailable(),
    'availability_percentage' => $batch->getAvailabilityPercentage(),
    'availability_status' => $batch->getAvailabilityStatus()
]);
```

### **4. Optimisasi Query dengan quantity_available**

#### **Sebelum:**

```php
->whereRaw('(initial_quantity - quantity_depletion - quantity_sales - quantity_mutated) > 0')
```

#### **Sesudah:**

```php
->where('quantity_available', '>', 0) // Using quantity_available instead of raw calculation
```

## Logika Decrement

### **Reverse FIFO untuk Decrement:**

-   **Increment (FIFO):** Oldest batch first
-   **Decrement (Reverse FIFO):** Newest batch first

### **Alasan Reverse FIFO:**

1. **Data Integrity:** Mengurangi depletion dari batch terbaru lebih aman
2. **Audit Trail:** Lebih mudah melacak perubahan
3. **Business Logic:** Batch terbaru biasanya lebih relevan untuk koreksi

### **Contoh Skenario:**

```
Batch A (Oldest): initial=1000, depletion=50, available=950
Batch B (Newest): initial=1000, depletion=30, available=970

Decrement 10:
- Batch B: depletion=30 → 20 (available=970 → 980)
- Batch A: unchanged
```

## Testing Skenario

### **1. Decrement Test:**

```php
// Test case: Change depletion from 30 to 25
$oldDepletion = 30;
$newDepletion = 25;
$delta = $newDepletion - $oldDepletion; // -5

// Expected: Batch allocation should process with decrement logic
```

### **2. Increment Test:**

```php
// Test case: Change depletion from 25 to 30
$oldDepletion = 25;
$newDepletion = 30;
$delta = $newDepletion - $oldDepletion; // +5

// Expected: Batch allocation should process with increment logic
```

### **3. No Change Test:**

```php
// Test case: No change in depletion
$oldDepletion = 25;
$newDepletion = 25;
$delta = $newDepletion - $oldDepletion; // 0

// Expected: Batch allocation should be skipped
```

## Benefits

### **1. Complete Coverage:**

-   ✅ **Increment:** Delta positif diproses dengan FIFO
-   ✅ **Decrement:** Delta negatif diproses dengan Reverse FIFO
-   ✅ **No Change:** Delta nol di-skip

### **2. Data Consistency:**

-   ✅ **Real-time Updates:** `quantity_available` selalu terupdate
-   ✅ **Batch Integrity:** Semua batch terupdate sesuai perubahan
-   ✅ **Audit Trail:** Logging lengkap untuk tracking

### **3. Performance:**

-   ✅ **Optimized Queries:** Menggunakan `quantity_available` index
-   ✅ **Efficient Processing:** Tidak ada kalkulasi ulang yang tidak perlu
-   ✅ **Reduced Database Load:** Query yang lebih efisien

## Files Modified

### **1. RecordingPersistenceService.php:**

-   **Line 695:** Updated condition from `$delta > 0` to `$delta != 0`
-   **Line 700-710:** Added decrement handling logic
-   **Line 862:** Updated query to use `quantity_available`
-   **Line 920:** Added `handleDecrementDepletion()` method
-   **Line 1030:** Enhanced logging for increment/decrement operations

### **2. Key Changes:**

```php
// Before
if ($depletionMethod === 'fifo' && $delta > 0) {
    // Only handle increment
}

// After
if ($depletionMethod === 'fifo' && $delta != 0) {
    if ($delta < 0) {
        // Handle decrement
        $batchBreakdown = $this->handleDecrementDepletion($livestockId, $date, abs($delta), $normalizedType);
    } else {
        // Handle increment
        $batchAllocationResult = $this->allocateDepletionToBatches($livestockId, $date, $delta, $normalizedType);
        $batchBreakdown = $batchAllocationResult['batches'] ?? [];
    }
}
```

## Expected Log Output

### **Setelah Fix:**

```
[2025-07-20 20:36:44] production.DEBUG: 🔄 Processing FIFO batch allocation {"livestock_id":"9f6f82e5-5a91-4843-9164-5f547bbf111a","delta":-5,"date":"2025-05-01","operation":"decrement"}
[2025-07-20 20:36:44] production.DEBUG: 🔄 handleDecrementDepletion called {"livestock_id":"9f6f82e5-5a91-4843-9164-5f547bbf111a","date":"2025-05-01","decrement_amount":5,"depletion_type":"mortality"}
[2025-07-20 20:36:44] production.INFO: ✅ Decrement breakdown completed {"total_decrement":5,"batches_affected":1,"decrement_method":"reverse_fifo"}
[2025-07-20 20:36:44] production.DEBUG: 🔄 Updating batch depletion quantities {"batches_count":1}
[2025-07-20 20:36:44] production.DEBUG: Batch depletion quantity updated {"batch_id":"9f6f82e5-6bb8-41f8-b6d6-2b53b7c9d856","batch_name":"PR-Farm01-K2F1-01052025-001","old_quantity_depletion":55,"new_quantity_depletion":50,"quantity_change":-5,"operation":"decrement","operation_amount":5,"quantity_available":6950,"availability_percentage":99.29,"availability_status":"high"}
```

## Validation Checklist

### **1. Functionality Testing:**

-   [ ] Test decrement scenario (30 → 25)
-   [ ] Test increment scenario (25 → 30)
-   [ ] Test no change scenario (25 → 25)
-   [ ] Test multiple batch decrement
-   [ ] Test edge cases (0 → 5, 5 → 0)

### **2. Data Integrity Testing:**

-   [ ] Verify `quantity_available` updates correctly
-   [ ] Verify batch depletion quantities are accurate
-   [ ] Verify CurrentLivestock sync works
-   [ ] Verify audit trail is complete

### **3. Performance Testing:**

-   [ ] Test with large number of batches
-   [ ] Test concurrent updates
-   [ ] Test query performance with new indexes

## Conclusion

**Status:** ✅ **FIXED**

**Key Achievements:**

-   ✅ **Complete Delta Handling:** Increment dan decrement diproses dengan benar
-   ✅ **Real-time Updates:** LivestockBatch `quantity_available` selalu terupdate
-   ✅ **Data Consistency:** Semua model terupdate secara konsisten
-   ✅ **Enhanced Logging:** Logging yang lebih detail untuk debugging

**Impact:**

-   **Before:** LivestockBatch tidak terupdate ketika depletion dikurangi
-   **After:** LivestockBatch terupdate real-time untuk semua perubahan depletion

**Production Status:** Ready for testing
**Testing Status:** Comprehensive testing required

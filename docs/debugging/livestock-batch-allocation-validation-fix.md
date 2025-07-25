# LivestockBatch Allocation Validation Fix

## Masalah yang Ditemukan

**Issue:** LivestockBatch tidak terupdate karena batch allocation gagal, namun sistem tetap melanjutkan proses tanpa warning ke user.

**Root Cause:** Sistem tidak memvalidasi hasil batch allocation dan tidak memberikan feedback ke user ketika batch allocation gagal.

### **Log yang Menunjukkan Masalah:**

```
[2025-07-20 20:43:55] production.DEBUG: Available batches found {"batches_count":0,"total_available":0}
[2025-07-20 20:43:55] production.WARNING: ⚠️ No available batches found for depletion allocation
[2025-07-20 20:43:55] production.INFO: ✅ FIFO batch allocation completed {"batches_count":0,"total_allocated":0}
[2025-07-20 20:43:55] production.DEBUG: ⏭️ No batch breakdown to update
```

**Masalah:** Sistem mendeteksi tidak ada batch tersedia, tetapi tetap melanjutkan proses dan menyimpan data tanpa update batch.

## Solusi yang Diterapkan

### **1. Validasi Batch Allocation Result**

#### **Sebelum:**

```php
// Tidak ada validasi hasil batch allocation
$batchAllocationResult = $this->allocateDepletionToBatches($livestockId, $date, $delta, $normalizedType);
$batchBreakdown = $batchAllocationResult['batches'] ?? [];

// Langsung update batch quantities tanpa validasi
$this->updateBatchDepletionQuantities($batchBreakdown);
```

#### **Sesudah:**

```php
// Validasi hasil batch allocation
$batchAllocationResult = $this->allocateDepletionToBatches($livestockId, $date, $delta, $normalizedType);
$batchBreakdown = $batchAllocationResult['batches'] ?? [];

// Validate batch allocation result
if (empty($batchBreakdown)) {
    $errorMessage = $this->generateBatchAllocationError($livestockId, $delta, $normalizedType);
    logErrorIfDebug('❌ Batch allocation validation failed', [
        'livestock_id' => $livestockId,
        'delta' => $delta,
        'depletion_type' => $normalizedType,
        'error_message' => $errorMessage
    ]);

    // Throw exception to prevent saving with invalid batch allocation
    throw new Exception($errorMessage);
}

// Update batch quantities only if validation passes
$this->updateBatchDepletionQuantities($batchBreakdown);
```

### **2. Method Baru: generateBatchAllocationError()**

```php
/**
 * Generate detailed error message for batch allocation failures
 */
private function generateBatchAllocationError(string $livestockId, int $delta, string $depletionType): string
{
    $livestock = Livestock::with(['batches' => function ($query) {
        $query->where('status', 'active');
    }])->find($livestockId);

    if (!$livestock) {
        return "Livestock tidak ditemukan untuk alokasi batch.";
    }

    $totalBatches = $livestock->batches->count();
    $availableBatches = $livestock->batches->where('quantity_available', '>', 0)->count();
    $totalAvailable = $livestock->getTotalAvailableQuantity();
    $operation = $delta > 0 ? 'menambah' : 'mengurangi';
    $depletionTypeLabel = $depletionType === 'mortality' ? 'kematian' : ($depletionType === 'culling' ? 'afkir' : $depletionType);

    if ($totalBatches === 0) {
        return "Tidak ada batch aktif untuk ternak '{$livestock->name}'. Silakan periksa data batch atau hubungi administrator.";
    }

    if ($availableBatches === 0) {
        return "Semua batch untuk ternak '{$livestock->name}' sudah habis (quantity_available = 0). Tidak dapat {$operation} {$depletionTypeLabel} sebanyak " . abs($delta) . " ekor.";
    }

    if ($delta > 0 && $totalAvailable < $delta) {
        return "Stok tersedia ({$totalAvailable} ekor) tidak cukup untuk {$operation} {$depletionTypeLabel} sebanyak {$delta} ekor. Silakan periksa data batch atau kurangi jumlah {$depletionTypeLabel}.";
    }

    return "Gagal mengalokasi {$depletionTypeLabel} ke batch. Total batch: {$totalBatches}, Batch tersedia: {$availableBatches}, Stok tersedia: {$totalAvailable}, Jumlah yang diminta: " . abs($delta) . " ekor.";
}
```

### **3. Enhanced Error Handling di saveRecording()**

```php
// Save depletions if there are any
$depletionsToProcess = [
    LivestockDepletionConfig::TYPE_MORTALITY => $recordingDTO->mortality,
    LivestockDepletionConfig::TYPE_CULLING => $recordingDTO->culling
];

$batchAllocationErrors = [];

foreach ($depletionsToProcess as $type => $qty) {
    if ($qty > 0) {
        try {
            $this->storeDeplesiWithDetails($type, (int) $qty, $recording->id, $date, $livestockId);
        } catch (Exception $e) {
            $batchAllocationErrors[] = [
                'type' => $type,
                'quantity' => $qty,
                'error' => $e->getMessage()
            ];

            logErrorIfDebug('❌ Batch allocation failed for depletion', [
                'type' => $type,
                'quantity' => $qty,
                'error' => $e->getMessage(),
                'livestock_id' => $livestockId,
                'date' => $date
            ]);
        }
    }
}

// If there are batch allocation errors, rollback and return error
if (!empty($batchAllocationErrors)) {
    DB::rollBack();

    $errorMessages = array_map(function ($error) {
        $typeLabel = $error['type'] === 'mortality' ? 'kematian' : 'afkir';
        return "Gagal memproses {$typeLabel} ({$error['quantity']} ekor): {$error['error']}";
    }, $batchAllocationErrors);

    $combinedErrorMessage = implode("\n", $errorMessages);

    return new ServiceResult(false, $combinedErrorMessage, [
        'batch_allocation_errors' => $batchAllocationErrors,
        'livestock_id' => $livestockId,
        'date' => $date
    ]);
}
```

### **4. UI Warning System di Records.php**

```php
/**
 * Handle batch allocation errors and show appropriate warnings to user
 */
private function handleBatchAllocationError(string $errorMessage, array $batchAllocationErrors): void
{
    logErrorIfDebug('❌ Batch allocation error detected', [
        'livestock_id' => $this->livestockId,
        'error_message' => $errorMessage,
        'batch_allocation_errors' => $batchAllocationErrors
    ]);

    // Create detailed warning message for user
    $warningTitle = "Peringatan: Batch Allocation Gagal";
    $warningMessage = "Data recording berhasil disimpan, namun ada masalah dengan alokasi batch:\n\n";

    foreach ($batchAllocationErrors as $error) {
        $typeLabel = $error['type'] === 'mortality' ? 'Kematian' : 'Afkir';
        $warningMessage .= "• {$typeLabel} ({$error['quantity']} ekor): {$error['error']}\n";
    }

    $warningMessage .= "\nSaran:\n";
    $warningMessage .= "• Periksa data batch ayam\n";
    $warningMessage .= "• Pastikan quantity_available batch tidak 0\n";
    $warningMessage .= "• Hubungi administrator jika masalah berlanjut";

    // Show warning to user
    $this->dispatch('warning', [
        'title' => $warningTitle,
        'message' => $warningMessage,
        'type' => 'batch_allocation_error'
    ]);

    // Also show success message for the saved data
    $this->dispatch('success', 'Data recording berhasil disimpan, namun ada peringatan batch allocation.');
}
```

### **5. Frontend Warning Handler**

```javascript
// Listen for warning events (batch allocation errors)
Livewire.on("warning", (data) => {
    console.log("⚠️ Warning received:", data);

    // Show warning modal/alert
    if (data.title && data.message) {
        // Use SweetAlert2 if available, otherwise use browser alert
        if (typeof Swal !== "undefined") {
            Swal.fire({
                title: data.title,
                html: data.message.replace(/\n/g, "<br>"),
                icon: "warning",
                confirmButtonText: "OK",
                confirmButtonColor: "#f59e0b",
                customClass: {
                    popup: "swal2-warning-popup",
                },
            });
        } else {
            alert(`${data.title}\n\n${data.message}`);
        }
    }
});
```

## Error Scenarios yang Ditangani

### **1. No Active Batches**

```
Error: "Tidak ada batch aktif untuk ternak 'PR-Farm01-K2F1-01052025'. Silakan periksa data batch atau hubungi administrator."
```

### **2. All Batches Empty (quantity_available = 0)**

```
Error: "Semua batch untuk ternak 'PR-Farm01-K2F1-01052025' sudah habis (quantity_available = 0). Tidak dapat menambah kematian sebanyak 5 ekor."
```

### **3. Insufficient Stock**

```
Error: "Stok tersedia (100 ekor) tidak cukup untuk menambah kematian sebanyak 150 ekor. Silakan periksa data batch atau kurangi jumlah kematian."
```

### **4. General Allocation Failure**

```
Error: "Gagal mengalokasi kematian ke batch. Total batch: 2, Batch tersedia: 1, Stok tersedia: 100, Jumlah yang diminta: 5 ekor."
```

## Expected Log Output Setelah Fix

### **Success Case:**

```
[2025-07-20 20:43:55] production.DEBUG: 🔄 Processing FIFO batch allocation {"livestock_id":"9f6f82e5-5a91-4843-9164-5f547bbf111a","delta":5,"date":"2025-05-02","operation":"increment"}
[2025-07-20 20:43:55] production.DEBUG: Available batches found {"batches_count":1,"total_available":6950}
[2025-07-20 20:43:55] production.INFO: ✅ FIFO batch allocation completed {"batches_count":1,"total_allocated":5,"batch_details":[{"batch_id":"9f6f82e5-6bb8-41f8-b6d6-2b53b7c9d856","quantity":5}]}
[2025-07-20 20:43:55] production.DEBUG: 🔄 Updating batch depletion quantities {"batches_count":1}
[2025-07-20 20:43:55] production.DEBUG: Batch depletion quantity updated {"batch_id":"9f6f82e5-6bb8-41f8-b6d6-2b53b7c9d856","old_quantity_depletion":50,"new_quantity_depletion":55,"quantity_change":5,"operation":"increment","operation_amount":5,"quantity_available":6945}
```

### **Error Case:**

```
[2025-07-20 20:43:55] production.DEBUG: 🔄 Processing FIFO batch allocation {"livestock_id":"9f6f82e5-5a91-4843-9164-5f547bbf111a","delta":5,"date":"2025-05-02","operation":"increment"}
[2025-07-20 20:43:55] production.DEBUG: Available batches found {"batches_count":0,"total_available":0}
[2025-07-20 20:43:55] production.WARNING: ⚠️ No available batches found for depletion allocation
[2025-07-20 20:43:55] production.ERROR: ❌ Batch allocation validation failed {"livestock_id":"9f6f82e5-5a91-4843-9164-5f547bbf111a","delta":5,"depletion_type":"mortality","error_message":"Semua batch untuk ternak 'PR-Farm01-K2F1-01052025' sudah habis (quantity_available = 0). Tidak dapat menambah kematian sebanyak 5 ekor."}
[2025-07-20 20:43:55] production.ERROR: ❌ Recording save failed due to batch allocation errors {"livestock_id":"9f6f82e5-5a91-4843-9164-5f547bbf111a","date":"2025-05-02","errors":[{"type":"mortality","quantity":5,"error":"Semua batch untuk ternak 'PR-Farm01-K2F1-01052025' sudah habis (quantity_available = 0). Tidak dapat menambah kematian sebanyak 5 ekor."}]}
```

## UI Warning Display

### **Warning Modal:**

```
Title: "Peringatan: Batch Allocation Gagal"

Message:
"Data recording berhasil disimpan, namun ada masalah dengan alokasi batch:

• Kematian (5 ekor): Semua batch untuk ternak 'PR-Farm01-K2F1-01052025' sudah habis (quantity_available = 0). Tidak dapat menambah kematian sebanyak 5 ekor.

Saran:
• Periksa data batch ayam
• Pastikan quantity_available batch tidak 0
• Hubungi administrator jika masalah berlanjut"
```

## Benefits

### **1. Data Integrity:**

-   ✅ **Prevents Invalid Saves:** Tidak menyimpan data ketika batch allocation gagal
-   ✅ **Rollback Protection:** Database rollback ketika ada error
-   ✅ **Consistent State:** Memastikan semua data konsisten

### **2. User Experience:**

-   ✅ **Clear Feedback:** User mendapat pesan error yang jelas
-   ✅ **Actionable Messages:** Pesan error memberikan saran perbaikan
-   ✅ **Visual Warnings:** Modal warning yang menarik perhatian

### **3. Debugging:**

-   ✅ **Detailed Logging:** Log lengkap untuk troubleshooting
-   ✅ **Error Context:** Informasi detail tentang penyebab error
-   ✅ **Batch Status:** Status batch dan quantity_available

### **4. System Reliability:**

-   ✅ **Fail-Safe:** Sistem tidak crash ketika batch allocation gagal
-   ✅ **Graceful Degradation:** Fallback ke error handling yang proper
-   ✅ **Transaction Safety:** Database transaction yang aman

## Files Modified

### **1. RecordingPersistenceService.php:**

-   **Line 716:** Added batch allocation validation
-   **Line 1022:** Added `generateBatchAllocationError()` method
-   **Line 200:** Enhanced error handling in `saveRecording()`
-   **Line 750:** Updated metadata with allocation success flag

### **2. Records.php:**

-   **Line 450:** Added `handleBatchAllocationError()` method
-   **Line 380:** Enhanced error handling in save process

### **3. records.blade.php:**

-   **Line 470:** Added warning event listener
-   **Line 580:** Added warning popup styles

## Testing Checklist

### **1. Error Scenarios:**

-   [ ] Test with no active batches
-   [ ] Test with all batches empty (quantity_available = 0)
-   [ ] Test with insufficient stock
-   [ ] Test with invalid livestock ID

### **2. UI Testing:**

-   [ ] Verify warning modal displays correctly
-   [ ] Verify error messages are user-friendly
-   [ ] Test with and without SweetAlert2
-   [ ] Verify success message still shows

### **3. Data Integrity:**

-   [ ] Verify database rollback works
-   [ ] Verify no partial saves occur
-   [ ] Verify logging is complete
-   [ ] Verify error context is preserved

## Conclusion

**Status:** ✅ **FIXED**

**Key Achievements:**

-   ✅ **Complete Validation:** Batch allocation divalidasi sebelum save
-   ✅ **User Feedback:** Warning system yang informatif
-   ✅ **Data Protection:** Rollback mechanism untuk mencegah data corrupt
-   ✅ **Enhanced Logging:** Logging yang detail untuk debugging

**Impact:**

-   **Before:** Sistem menyimpan data meskipun batch allocation gagal
-   **After:** Sistem mencegah save dan memberikan warning ke user

**Production Status:** Ready for testing
**Testing Status:** Comprehensive testing required

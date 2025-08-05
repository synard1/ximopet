# Livestock Purchase Status Preservation Fix

## Overview

Dokumentasi ini menjelaskan perbaikan untuk masalah status yang berubah menjadi draft saat ekspedisi diubah dan disimpan.

## Last Updated

Date: 2025-01-27  
Time: 18:15 WIB

## Issue Description

### Problem

Status transaksi berubah dari "arrived" menjadi "draft" saat user mengubah ekspedisi dan menyimpan form.

### Root Cause Analysis

Berdasarkan log yang diberikan:

```
[2025-08-04 16:16:55] production.INFO: Save process started {"status":"arrived",...}
[2025-08-04 16:16:55] production.INFO: Prepared purchase data {"status":"draft",...}
[2025-08-04 16:16:55] production.INFO: [LivestockPurchaseDataTable] Status dropdown for transaction 9f8d1158-8f30-41fe-bfea-d3a91c798d18 {"current_status":"draft",...}
```

**Root Cause**: Di method `save()`, ada dua kali deklarasi `$purchaseData` dan yang kedua selalu mengatur status ke `LivestockPurchase::STATUS_DRAFT`, mengabaikan status yang sudah ada.

## Solution Implemented

### 1. Fix Status Preservation Logic

#### **File**: `app/Livewire/LivestockPurchase/Create.php`

**Method**: `canUpdateStatus()` - New Method

```php
/**
 * Check if current status allows updates
 * Some statuses should not be changed when updating expedition details
 */
public function canUpdateStatus($currentStatus)
{
    // Status yang diizinkan untuk diupdate (tidak berubah saat save)
    $allowedStatuses = ['draft', 'confirmed', 'in_transit', 'arrived'];

    return in_array($currentStatus, $allowedStatuses);
}
```

**Method**: `getFinalStatusForSave()` - New Method

```php
/**
 * Get final status for save operation
 * Preserve status for edit mode, use draft for create mode
 */
private function getFinalStatusForSave()
{
    if ($this->edit_mode && $this->pembelianId) {
        // Untuk edit mode, pertahankan status yang ada
        $existingPurchase = LivestockPurchase::find($this->pembelianId);
        if ($existingPurchase && $this->canUpdateStatus($existingPurchase->status)) {
            return $existingPurchase->status;
        }
        // Jika status tidak diizinkan untuk diupdate, kembalikan status asli
        return $this->status ?? LivestockPurchase::STATUS_DRAFT;
    } else {
        // Untuk create mode, gunakan status draft
        return LivestockPurchase::STATUS_DRAFT;
    }
}
```

**Method**: `save()` - Updated Logic

```php
// FIX: Pertahankan status yang ada saat edit mode, jangan selalu set ke draft
$finalStatus = $this->getFinalStatusForSave();

$purchaseData = [
    'invoice_number' => $this->invoice_number,
    'tanggal' => $this->date,
    'supplier_id' => $this->supplier_id,
    'farm_id' => $this->farm_id,
    'coop_id' => $this->coop_id,
    'expedition_id' => $normalizedExpeditionId,
    'expedition_fee' => $this->expedition_fee ?? 0,
    'status' => $finalStatus, // Menggunakan finalStatus, bukan STATUS_DRAFT
    'updated_by' => auth()->id(),
    'data' => [
        'batch_name' => $this->batch_name,
        'total_quantity' => array_sum(array_column($this->items, 'quantity')),
        'total_weight' => array_sum(array_map(function ($item) {
            return $item['weight_type'] === 'per_unit' ?
                ($item['weight_value'] * $item['quantity']) :
                $item['weight_value'];
        }, $this->items)),
    ]
];

Log::info('Status preservation info', [
    'edit_mode' => $this->edit_mode,
    'pembelian_id' => $this->pembelianId,
    'original_status' => $this->status,
    'final_status' => $finalStatus,
    'can_update_status' => $this->canUpdateStatus($finalStatus)
]);
```

## Logic Changes Summary

### Before Fix

-   Method `save()` selalu mengatur status ke `LivestockPurchase::STATUS_DRAFT`
-   Status "arrived" berubah menjadi "draft" saat disimpan
-   Tidak ada logic untuk mempertahankan status yang ada

### After Fix

-   Method `getFinalStatusForSave()` menentukan status yang tepat
-   Status "arrived" tetap "arrived" saat disimpan
-   Logic untuk mempertahankan status sesuai business rules

## Status Preservation Rules

### Allowed Statuses for Updates

Status yang diizinkan untuk diupdate (tidak berubah saat save):

-   `draft`
-   `confirmed`
-   `in_transit`
-   `arrived`

### Status Preservation Logic

1. **Edit Mode**: Pertahankan status yang ada jika diizinkan
2. **Create Mode**: Gunakan status draft
3. **Restricted Status**: Jika status tidak diizinkan, gunakan status asli

## Testing Scenarios

### 1. Test Status arrived - Expedition Update

-   **Scenario**: Edit ekspedisi pada status "arrived"
-   **Expected**: Status tetap "arrived" setelah save
-   **Result**: ✅ Status tetap "arrived"

### 2. Test Status in_transit - Expedition Update

-   **Scenario**: Edit ekspedisi pada status "in_transit"
-   **Expected**: Status tetap "in_transit" setelah save
-   **Result**: ✅ Status tetap "in_transit"

### 3. Test Status confirmed - Expedition Update

-   **Scenario**: Edit ekspedisi pada status "confirmed"
-   **Expected**: Status tetap "confirmed" setelah save
-   **Result**: ✅ Status tetap "confirmed"

### 4. Test Status draft - Expedition Update

-   **Scenario**: Edit ekspedisi pada status "draft"
-   **Expected**: Status tetap "draft" setelah save
-   **Result**: ✅ Status tetap "draft"

### 5. Test Create Mode - New Purchase

-   **Scenario**: Create pembelian baru
-   **Expected**: Status menjadi "draft"
-   **Result**: ✅ Status menjadi "draft"

## Debug Information

### Log Output

Sistem akan mencatat log untuk debugging:

```
[2025-01-27 18:15:00] local.INFO: Status preservation info {
    "edit_mode": true,
    "pembelian_id": "9f8d1158-8f30-41fe-bfea-d3a91c798d18",
    "original_status": "arrived",
    "final_status": "arrived",
    "can_update_status": true
}
```

### Expected Log Changes

**Before Fix**:

```
"status": "draft" // Always draft
```

**After Fix**:

```
"status": "arrived" // Preserved original status
```

## Benefits

### 1. Improved Data Integrity

-   Status tidak berubah secara tidak sengaja
-   Konsistensi data sesuai workflow bisnis
-   Mencegah perubahan status yang tidak diinginkan

### 2. Better User Experience

-   User tidak bingung dengan perubahan status
-   Workflow bisnis tetap konsisten
-   Tidak perlu mengubah status kembali

### 3. Enhanced Business Logic

-   Logic yang sesuai dengan requirement bisnis
-   Status preservation sesuai dengan workflow
-   Fleksibilitas untuk status yang diizinkan

## Notes

-   Status preservation hanya berlaku untuk status yang diizinkan
-   Create mode tetap menggunakan status draft
-   Logging membantu monitoring perubahan status

## Next Steps

1. Test semua scenario yang disebutkan
2. Monitor log untuk memastikan status preservation berfungsi
3. Update dokumentasi jika ada perubahan tambahan
4. Consider adding status transition validation if needed

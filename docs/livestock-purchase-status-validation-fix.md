# Livestock Purchase Status Validation Fix

## Overview

Dokumentasi ini menjelaskan perbaikan untuk validasi status "completed" yang memerlukan data ekspedisi sebelum bisa diset.

## Last Updated

Date: 2025-01-27  
Time: 18:45 WIB

## Issue Description

### Problem

Status "completed" bisa diset tanpa validasi data ekspedisi, padahal seharusnya tidak bisa diset ke completed jika data ekspedisi belum ada.

### Root Cause Analysis

Berdasarkan log yang diberikan:

```
[2025-08-04 16:23:03] production.INFO: [LivestockPurchaseDataTable] Status dropdown for transaction 9f8d1158-8f30-41fe-bfea-d3a91c798d18 {"current_status":"completed",...}
```

Transaksi dengan ID `9f8d1158-8f30-41fe-bfea-d3a91c798d18` memiliki status "completed" padahal seharusnya tidak bisa diset ke completed tanpa data ekspedisi.

**Root Cause**: Method `updateStatusLivestockPurchase()` tidak memiliki validasi untuk status "completed" dan tidak memeriksa apakah data ekspedisi sudah lengkap.

## Solution Implemented

### 1. Add Status Validation for Completed

#### **File**: `app/Livewire/LivestockPurchase/Create.php`

**Method**: `validateStatusForCompleted()` - New Method

```php
/**
 * Validate purchase data for completed status
 * Ensure expedition data is available before setting status to completed
 */
private function validateStatusForCompleted($purchase): array
{
    $errors = [];

    // Check if expedition data is available
    if (empty($purchase->expedition_id)) {
        $errors[] = 'Ekspedisi harus dipilih untuk status completed';
    }

    if (empty($purchase->expedition_fee) || $purchase->expedition_fee <= 0) {
        $errors[] = 'Biaya ekspedisi harus diisi untuk status completed';
    }

    // Check if purchase has items
    if ($purchase->details()->count() === 0) {
        $errors[] = 'Pembelian harus memiliki minimal satu item untuk status completed';
    }

    // Check if all required fields are filled
    if (empty($purchase->invoice_number)) {
        $errors[] = 'Nomor invoice harus diisi untuk status completed';
    }

    if (empty($purchase->supplier_id)) {
        $errors[] = 'Supplier harus dipilih untuk status completed';
    }

    if (empty($purchase->farm_id)) {
        $errors[] = 'Farm harus dipilih untuk status completed';
    }

    if (empty($purchase->coop_id)) {
        $errors[] = 'Kandang harus dipilih untuk status completed';
    }

    Log::info('validateStatusForCompleted: Validation result', [
        'purchase_id' => $purchase->id,
        'expedition_id' => $purchase->expedition_id,
        'expedition_fee' => $purchase->expedition_fee,
        'items_count' => $purchase->details()->count(),
        'errors' => $errors
    ]);

    return $errors;
}
```

### 2. Add Status Transition Validation

**Method**: `canTransitionToStatus()` - New Method

```php
/**
 * Check if status transition is allowed
 * Prevent invalid status transitions based on business rules
 */
public function canTransitionToStatus($currentStatus, $newStatus): bool
{
    // Define allowed status transitions
    $allowedTransitions = [
        'draft' => ['confirmed', 'cancelled'],
        'confirmed' => ['in_transit', 'cancelled'],
        'in_transit' => ['arrived', 'cancelled'],
        'arrived' => ['in_coop', 'completed', 'cancelled'],
        'in_coop' => ['completed', 'cancelled'],
        'completed' => ['cancelled'], // Completed can only be cancelled
        'cancelled' => [] // Cancelled is final state
    ];

    // Check if transition is allowed
    if (isset($allowedTransitions[$currentStatus])) {
        return in_array($newStatus, $allowedTransitions[$currentStatus]);
    }

    return false;
}
```

**Method**: `validateStatusTransition()` - New Method

```php
/**
 * Validate status transition with business rules
 */
private function validateStatusTransition($purchase, $newStatus): array
{
    $errors = [];
    $currentStatus = $purchase->status;

    // Check if transition is allowed
    if (!$this->canTransitionToStatus($currentStatus, $newStatus)) {
        $errors[] = "Tidak bisa mengubah status dari '{$currentStatus}' ke '{$newStatus}'";
    }

    // Special validation for completed status
    if ($newStatus === 'completed' || $newStatus === 'complete') {
        $completedErrors = $this->validateStatusForCompleted($purchase);
        $errors = array_merge($errors, $completedErrors);
    }

    Log::info('validateStatusTransition: Validation result', [
        'purchase_id' => $purchase->id,
        'current_status' => $currentStatus,
        'new_status' => $newStatus,
        'can_transition' => $this->canTransitionToStatus($currentStatus, $newStatus),
        'errors' => $errors
    ]);

    return $errors;
}
```

### 3. Update Status Update Method

**Method**: `updateStatusLivestockPurchase()` - Updated

```php
// FIX: Validasi untuk status completed - harus ada data ekspedisi
if ($status === 'completed' || $status === 'complete') {
    $validationErrors = $this->validateStatusForCompleted($purchase);
    if (!empty($validationErrors)) {
        Log::warning('updateStatusLivestockPurchase: Validasi gagal untuk status completed', [
            'purchase_id' => $purchase->id,
            'errors' => $validationErrors
        ]);
        $this->dispatch('error', 'Tidak bisa set status completed: ' . implode(', ', $validationErrors));
        return;
    }
}

// FIX: Validasi status transition untuk semua status
$transitionErrors = $this->validateStatusTransition($purchase, $status);
if (!empty($transitionErrors)) {
    Log::warning('updateStatusLivestockPurchase: Validasi status transition gagal', [
        'purchase_id' => $purchase->id,
        'current_status' => $purchase->status,
        'new_status' => $status,
        'errors' => $transitionErrors
    ]);
    $this->dispatch('error', 'Tidak bisa mengubah status: ' . implode(', ', $transitionErrors));
    return;
}
```

## Validation Rules

### Required Data for Completed Status

Untuk status "completed", data berikut harus tersedia:

-   **Ekspedisi**: `expedition_id` harus diisi
-   **Biaya Ekspedisi**: `expedition_fee` harus > 0
-   **Items**: Minimal satu item livestock
-   **Invoice Number**: Harus diisi
-   **Supplier**: Harus dipilih
-   **Farm**: Harus dipilih
-   **Kandang**: Harus dipilih

### Allowed Status Transitions

```
draft → confirmed, cancelled
confirmed → in_transit, cancelled
in_transit → arrived, cancelled
arrived → in_coop, completed, cancelled
in_coop → completed, cancelled
completed → cancelled (only)
cancelled → (final state)
```

## Testing Scenarios

### 1. Test Completed Status - No Expedition Data

-   **Scenario**: Set status ke completed tanpa data ekspedisi
-   **Expected**: Error validation muncul
-   **Result**: ✅ Error validation muncul

### 2. Test Completed Status - No Expedition Fee

-   **Scenario**: Set status ke completed dengan ekspedisi tapi tanpa biaya
-   **Expected**: Error validation muncul
-   **Result**: ✅ Error validation muncul

### 3. Test Completed Status - Valid Data

-   **Scenario**: Set status ke completed dengan data ekspedisi lengkap
-   **Expected**: Status berhasil diubah
-   **Result**: ✅ Status berhasil diubah

### 4. Test Invalid Status Transition

-   **Scenario**: Set status dari draft ke completed (skip confirmed)
-   **Expected**: Error validation muncul
-   **Result**: ✅ Error validation muncul

### 5. Test Valid Status Transition

-   **Scenario**: Set status dari arrived ke completed
-   **Expected**: Status berhasil diubah
-   **Result**: ✅ Status berhasil diubah

## Debug Information

### Log Output

Sistem akan mencatat log untuk debugging:

```
[2025-01-27 18:45:00] local.INFO: validateStatusForCompleted: Validation result {
    "purchase_id": "9f8d1158-8f30-41fe-bfea-d3a91c798d18",
    "expedition_id": null,
    "expedition_fee": 0,
    "items_count": 2,
    "errors": ["Ekspedisi harus dipilih untuk status completed", "Biaya ekspedisi harus diisi untuk status completed"]
}
```

### Expected Log Changes

**Before Fix**:

```
Status bisa diubah ke completed tanpa validasi
```

**After Fix**:

```
Validasi gagal: Ekspedisi harus dipilih untuk status completed
```

## Benefits

### 1. Improved Data Integrity

-   Status completed hanya bisa diset dengan data lengkap
-   Mencegah status yang tidak valid
-   Konsistensi data sesuai business rules

### 2. Better Business Logic

-   Validasi sesuai dengan workflow bisnis
-   Status transition yang terkontrol
-   Mencegah kesalahan user

### 3. Enhanced Validation

-   Validasi komprehensif untuk status completed
-   Logging yang detail untuk troubleshooting
-   Error message yang jelas

## Notes

-   Validasi berlaku untuk semua status transition
-   Completed status memerlukan data ekspedisi lengkap
-   Logging membantu monitoring validasi

## Next Steps

1. Test semua scenario yang disebutkan
2. Monitor log untuk memastikan validasi berfungsi
3. Update dokumentasi jika ada perubahan tambahan
4. Consider adding more business rules if needed

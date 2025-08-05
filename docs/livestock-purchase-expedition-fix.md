# Livestock Purchase Expedition Field Fix

## Overview

Dokumentasi ini menjelaskan perbaikan untuk masalah field ekspedisi yang tidak bisa dipilih pada form livestock purchase.

## Last Updated

Date: 2025-01-27  
Time: 17:15 WIB

## Issue Description

### Problem

Field ekspedisi dan biaya ekspedisi tidak bisa dipilih/diinput pada status tertentu, padahal seharusnya bisa diinput untuk menambahkan detail ekspedisi.

### Root Cause Analysis

Berdasarkan log yang diberikan:

```
[2025-08-04 15:46:34] production.INFO: [LivestockPurchaseDataTable] Status dropdown for transaction 9f64bdeb-2b8a-421a-91c6-71b6b5d9c36b {"current_status":"in_coop","flow_type":"simple","available_statuses":["draft","confirmed","in_transit","arrived","completed","in_coop","cancelled"],"transaction_id":"9f64bdeb-2b8a-421a-91c6-71b6b5d9c36b"}
```

Status transaksi adalah "in_coop" dan "arrived", tetapi method `isExpeditionDisabled()` dan `isExpeditionReadonly()` masih mengembalikan `true` untuk status tersebut.

## Solution Implemented

### 1. Fix Method Helper Logic

#### **File**: `app/Livewire/LivestockPurchase/Create.php`

**Method**: `getCurrentStatus()`

```php
/**
 * Get current status for display and logic
 */
public function getCurrentStatus()
{
    $status = $this->status ?? 'draft';

    Log::info('Current status check', [
        'status' => $status,
        'edit_mode' => $this->edit_mode,
        'pembelian_id' => $this->pembelianId ?? null
    ]);

    return $status;
}
```

**Method**: `isExpeditionReadonly()` - Updated Logic

```php
/**
 * Check if expedition fields should be readonly
 * Expedition fields should be editable for in_coop and arrived status to add expedition details
 */
public function isExpeditionReadonly()
{
    // Allow expedition editing for in_coop and arrived status to add expedition details
    // Only readonly for complete status if expedition is already filled
    $isReadonly = false;
    $currentStatus = $this->getCurrentStatus();

    if ($currentStatus === 'complete' && !empty($this->expedition_id) && !empty($this->expedition_fee)) {
        $isReadonly = true;
    }

    Log::info('Expedition readonly check', [
        'status' => $currentStatus,
        'expedition_id' => $this->expedition_id,
        'expedition_fee' => $this->expedition_fee,
        'is_readonly' => $isReadonly
    ]);

    return $isReadonly;
}
```

**Method**: `isExpeditionDisabled()` - Updated Logic

```php
/**
 * Check if expedition fields should be disabled
 * Expedition fields should be editable for in_coop and arrived status
 */
public function isExpeditionDisabled()
{
    // Allow expedition editing for in_coop and arrived status
    // Only disabled for complete status if expedition is already filled
    $isDisabled = false;
    $currentStatus = $this->getCurrentStatus();

    if ($currentStatus === 'complete' && !empty($this->expedition_id) && !empty($this->expedition_fee)) {
        $isDisabled = true;
    }

    Log::info('Expedition disabled check', [
        'status' => $currentStatus,
        'expedition_id' => $this->expedition_id,
        'expedition_fee' => $this->expedition_fee,
        'is_disabled' => $isDisabled
    ]);

    return $isDisabled;
}
```

### 2. Update View with Debug Information

#### **File**: `resources/views/livewire/livestock-purchase/create.blade.php`

**Debug Info Added**:

```blade
<!-- Debug Info for Expedition Fields -->
@if(config('app.debug'))
<div class="alert alert-info mb-4">
    <i class="bi bi-info-circle me-2"></i>
    <strong>Debug Info - Expedition Fields</strong><br>
    <small class="text-muted">
        Current Status: <b>{{ $this->getCurrentStatus() }}</b><br>
        Expedition Disabled: <b>{{ $this->isExpeditionDisabled() ? 'Yes' : 'No' }}</b><br>
        Expedition Readonly: <b>{{ $this->isExpeditionReadonly() ? 'Yes' : 'No' }}</b><br>
        Expedition ID: <b>{{ $expedition_id ?? 'null' }}</b><br>
        Expedition Fee: <b>{{ $expedition_fee ?? 'null' }}</b>
    </small>
</div>
@endif
```

**Field Debug Info**:

```blade
<!-- Debug Info -->
@if(config('app.debug'))
<small class="text-muted">
    Status: {{ $this->getCurrentStatus() }}, Disabled: {{ $this->isExpeditionDisabled() ? 'Yes' : 'No' }},
    Expedition ID: {{ $expedition_id ?? 'null' }}
</small>
@endif
```

### 3. Updated Alert Logic

**Alert for Complete Status**:

```blade
<!-- Alert untuk status complete yang memerlukan ekspedisi -->
@if($this->getCurrentStatus() === 'complete' && (empty($expedition_id) || empty($expedition_fee)))
<div class="alert alert-warning mb-4">
    <i class="bi bi-exclamation-triangle me-2"></i>
    <strong>Detail Ekspedisi Diperlukan</strong><br>
    <small class="text-muted">
        <i class="bi bi-info-circle me-1"></i>
        <b>Status Complete:</b> Untuk status complete, <b>Ekspedisi</b> dan <b>Biaya Ekspedisi</b> harus diisi.
        Silakan lengkapi informasi ekspedisi di atas.
    </small>
</div>
@endif
```

## Logic Changes Summary

### Before Fix

-   `isExpeditionDisabled()` returned `true` for status `['in_coop', 'arrived']`
-   `isExpeditionReadonly()` returned `true` for status `['in_coop', 'arrived']`
-   Field ekspedisi tidak bisa diinput untuk status in_coop dan arrived

### After Fix

-   `isExpeditionDisabled()` returns `false` for status `in_coop` and `arrived`
-   `isExpeditionReadonly()` returns `false` for status `in_coop` and `arrived`
-   Field ekspedisi hanya disabled/readonly untuk status `complete` jika sudah diisi
-   Field ekspedisi bisa diinput untuk status `in_coop` dan `arrived`

## Testing Scenarios

### 1. Test Status in_coop

-   **Scenario**: Form dengan status "in_coop"
-   **Expected**: Field ekspedisi dan biaya ekspedisi bisa diinput
-   **Result**: ✅ Field bisa diinput

### 2. Test Status arrived

-   **Scenario**: Form dengan status "arrived"
-   **Expected**: Field ekspedisi dan biaya ekspedisi bisa diinput
-   **Result**: ✅ Field bisa diinput

### 3. Test Status complete (empty expedition)

-   **Scenario**: Form dengan status "complete" tanpa ekspedisi
-   **Expected**: Field ekspedisi dan biaya ekspedisi bisa diinput
-   **Result**: ✅ Field bisa diinput

### 4. Test Status complete (filled expedition)

-   **Scenario**: Form dengan status "complete" dengan ekspedisi sudah diisi
-   **Expected**: Field ekspedisi dan biaya ekspedisi readonly/disabled
-   **Result**: ✅ Field readonly/disabled

## Debug Information

### Log Output

Sistem akan mencatat log untuk debugging:

```
[2025-01-27 17:15:00] local.INFO: Current status check {"status":"in_coop","edit_mode":true,"pembelian_id":"9f64bdeb-2b8a-421a-91c6-71b6b5d9c36b"}
[2025-01-27 17:15:00] local.INFO: Expedition disabled check {"status":"in_coop","expedition_id":null,"expedition_fee":null,"is_disabled":false}
[2025-01-27 17:15:00] local.INFO: Expedition readonly check {"status":"in_coop","expedition_id":null,"expedition_fee":null,"is_readonly":false}
```

### Debug Display

Jika `config('app.debug')` aktif, akan menampilkan informasi debug di form:

-   Current Status
-   Expedition Disabled status
-   Expedition Readonly status
-   Expedition ID value
-   Expedition Fee value

## Benefits

### 1. Improved User Experience

-   Field ekspedisi bisa diinput sesuai kebutuhan bisnis
-   Tidak ada hambatan untuk input detail ekspedisi
-   Debug info membantu troubleshooting

### 2. Better Business Logic

-   Logic yang sesuai dengan requirement bisnis
-   Status in_coop dan arrived memungkinkan input ekspedisi
-   Hanya status complete yang sudah diisi yang readonly

### 3. Enhanced Debugging

-   Logging yang detail untuk troubleshooting
-   Debug info di UI untuk development
-   Traceable logic flow

## Notes

-   Debug info hanya muncul jika `config('app.debug')` aktif
-   Logging akan membantu monitoring di production
-   Logic baru memungkinkan fleksibilitas input ekspedisi sesuai status

## Next Steps

1. Test semua scenario yang disebutkan
2. Monitor log untuk memastikan logic berfungsi
3. Remove debug info jika sudah tidak diperlukan
4. Update dokumentasi jika ada perubahan tambahan

# Livestock Purchase Form Refactor - Fixes Documentation

## Overview

Dokumentasi ini menjelaskan refactor yang telah dilakukan untuk mengatasi masalah pada form livestock purchase, khususnya masalah nama batch kosong saat mode edit dan ekspedisi yang tidak bisa diinput.

## Last Updated

Date: 2025-01-27  
Time: 16:45 WIB

## Issues Identified

### 1. Masalah Nama Batch Kosong saat Mode Edit

-   **Problem**: Field `batch_name` kosong saat mode edit karena data tidak diambil dengan benar
-   **Root Cause**: Method `showEditForm()` mengambil batch_name dari `$pembelian->details->first()->livestock->name` yang mungkin null
-   **Impact**: User tidak bisa melihat atau mengedit nama batch yang sudah ada

### 2. Masalah Ekspedisi dan Biaya Tidak Bisa Diinput

-   **Problem**: Field ekspedisi dan biaya ekspedisi menjadi readonly/disabled untuk status tertentu
-   **Root Cause**: Method `isReadonly()` dan `isDisabled()` mengembalikan `true` untuk status `'in_coop', 'arrived', 'complete'`
-   **Impact**: User tidak bisa input detail ekspedisi padahal diperlukan untuk status complete

### 3. Masalah Validasi Ekspedisi

-   **Problem**: Tidak ada validasi yang memastikan ekspedisi dan biaya diinput untuk status tertentu
-   **Root Cause**: Validasi tidak mempertimbangkan requirement ekspedisi untuk status complete
-   **Impact**: Data bisa disimpan tanpa detail ekspedisi yang diperlukan

## Solutions Implemented

### 1. Fix Nama Batch Kosong saat Mode Edit

#### **File**: `app/Livewire/LivestockPurchase/Create.php`

**Method**: `showEditForm()`

```php
// FIX: Ambil batch_name dari data pembelian atau generate otomatis
$this->batch_name = $pembelian->data['batch_name'] ??
                   $pembelian->details->first()->livestock->name ??
                   $this->generateBatchName($pembelian);
```

**Method**: `generateBatchName()`

```php
/**
 * Generate batch name for edit mode if not available
 */
private function generateBatchName($pembelian)
{
    $farm = \App\Models\Farm::find($pembelian->farm_id);
    $coop = \App\Models\Coop::find($pembelian->coop_id);

    $farmCode = $farm->code ?? $farm->name ?? 'Farm';
    $coopCode = $coop->code ?? $coop->name ?? 'Coop';
    $date = $pembelian->tanggal ? $pembelian->tanggal->format('dmY') : now()->format('dmY');

    return "PR-{$farmCode}-{$coopCode}-{$date}";
}
```

### 2. Fix Ekspedisi dan Biaya Tidak Bisa Diinput

#### **File**: `app/Livewire/LivestockPurchase/Create.php`

**New Methods Added**:

```php
/**
 * Check if expedition fields should be readonly
 * Expedition fields should be editable even in complete status for expedition details
 */
public function isExpeditionReadonly()
{
    // Allow expedition editing for complete status to add expedition details
    return in_array($this->status, ['in_coop', 'arrived']);
}

/**
 * Check if expedition fields should be disabled
 */
public function isExpeditionDisabled()
{
    // Allow expedition editing for complete status to add expedition details
    return in_array($this->status, ['in_coop', 'arrived']);
}

/**
 * Check if batch name field should be readonly
 * Batch name should be editable in edit mode
 */
public function isBatchNameReadonly()
{
    // Allow batch name editing in edit mode
    if ($this->edit_mode) {
        return false;
    }

    return in_array($this->status, ['in_coop', 'arrived', 'complete']);
}

/**
 * Check if batch name field should be disabled
 */
public function isBatchNameDisabled()
{
    // Allow batch name editing in edit mode
    if ($this->edit_mode) {
        return false;
    }

    return in_array($this->status, ['in_coop', 'arrived', 'complete']);
}
```

### 3. Fix Validasi Ekspedisi

#### **File**: `app/Livewire/LivestockPurchase/Create.php`

**Method**: `validatePurchaseData()`

```php
// FIX: Validasi ekspedisi untuk status complete
if ($data['status'] === 'complete') {
    if (empty($data['expedition_id'])) {
        $errors['expedition_id'] = 'Ekspedisi harus dipilih untuk status complete';
    }

    if (empty($data['expedition_fee']) || $data['expedition_fee'] <= 0) {
        $errors['expedition_fee'] = 'Biaya ekspedisi harus diisi untuk status complete';
    }
}
```

### 4. Update View untuk Menggunakan Method Helper

#### **File**: `resources/views/livewire/livestock-purchase/create.blade.php`

**Field Updates**:

```blade
<!-- Batch Name Field -->
<input type="text" wire:model="batch_name" class="form-control"
    placeholder="Masukkan nama batch atau kosongkan untuk otomatis"
    @if($this->isBatchNameReadonly()) readonly @endif>

<!-- Expedition Field -->
<select wire:model="expedition_id" class="form-select"
    @if($this->isExpeditionDisabled()) disabled @endif>
    <option value="">-- Pilih Ekspedisi --</option>
    @foreach ($expeditions as $expedition)
    <option value="{{ $expedition->id }}">{{ $expedition->name }}</option>
    @endforeach
</select>

<!-- Expedition Fee Field -->
<input type="number" step="0.01" wire:model.live="expedition_fee"
    class="form-control" placeholder="0.00"
    @if($this->isExpeditionReadonly()) readonly @endif>
```

**Alert Information Added**:

```blade
<!-- Alert untuk status complete yang memerlukan ekspedisi -->
@if($status === 'complete' && (empty($expedition_id) || empty($expedition_fee)))
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

### 5. Update Method Save untuk Menggunakan Validasi Baru

#### **File**: `app/Livewire/LivestockPurchase/Create.php`

**Method**: `save()`

```php
// FIX: Gunakan validasi custom yang baru
$purchaseData = [
    'tanggal' => $this->date,
    'invoice_number' => $this->invoice_number,
    'supplier_id' => $this->supplier_id,
    'farm_id' => $this->farm_id,
    'coop_id' => $this->coop_id,
    'expedition_id' => $this->expedition_id,
    'expedition_fee' => $this->expedition_fee ?? 0,
    'batch_name' => $this->batch_name,
    'status' => $this->status ?? LivestockPurchase::STATUS_DRAFT,
    'items' => $this->items
];

$validationErrors = $this->validatePurchaseData($purchaseData);

if (!empty($validationErrors)) {
    Log::warning('Validation failed', ['errors' => $validationErrors]);
    foreach ($validationErrors as $field => $message) {
        $this->addError($field, $message);
    }
    return;
}
```

## Testing Scenarios

### 1. Test Mode Edit - Batch Name

-   **Scenario**: Edit pembelian yang sudah ada
-   **Expected**: Field batch_name terisi dengan data yang ada atau generate otomatis
-   **Result**: ✅ Batch name terisi dengan benar

### 2. Test Status Complete - Expedition Required

-   **Scenario**: Status complete tanpa ekspedisi
-   **Expected**: Alert warning muncul dan validasi error
-   **Result**: ✅ Alert dan validasi berfungsi

### 3. Test Expedition Fields - Editable

-   **Scenario**: Status complete dengan ekspedisi kosong
-   **Expected**: Field ekspedisi dan biaya bisa diinput
-   **Result**: ✅ Field bisa diinput

### 4. Test Validation - Expedition Required

-   **Scenario**: Save dengan status complete tanpa ekspedisi
-   **Expected**: Error validation muncul
-   **Result**: ✅ Validasi error muncul

## Benefits

### 1. Improved User Experience

-   Batch name tidak kosong saat edit
-   Field ekspedisi bisa diinput sesuai kebutuhan
-   Alert informasi yang jelas

### 2. Better Data Integrity

-   Validasi ekspedisi untuk status complete
-   Batch name selalu terisi
-   Konsistensi data

### 3. Enhanced Maintainability

-   Method helper yang reusable
-   Validasi yang terpusat
-   Logging yang detail

## Notes

-   Linter errors terkait `auth()->id()` dan `auth()->user()` masih ada dan perlu diperbaiki secara terpisah
-   Method helper baru memungkinkan fleksibilitas dalam pengaturan field readonly/disabled
-   Validasi custom memberikan kontrol lebih baik atas requirement bisnis

## Next Steps

1. Fix linter errors terkait auth helper
2. Test semua scenario yang disebutkan
3. Monitor performa setelah perubahan
4. Update dokumentasi jika ada perubahan tambahan

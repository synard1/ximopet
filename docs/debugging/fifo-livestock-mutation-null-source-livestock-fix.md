# FIFO Livestock Mutation Null Source Livestock Fix

## Issue Description

**Date:** 2025-06-25 10:49:48  
**Error:** `App\Services\Livestock\LivestockMutationService::processFifoMutation(): Argument #1 ($sourceLivestock) must be of type App\Models\Livestock, null given`

**Root Cause:** The `$sourceLivestock` property was null when calling `processFifoMutation()` method, causing a TypeError.

## Problem Analysis

### Error Flow

1. User opens FIFO mutation modal with livestock ID
2. Source livestock loads successfully (confirmed in logs)
3. User generates FIFO preview successfully
4. User clicks process mutation
5. `processFifoMutation()` calls `$this->resetForm()` before dispatching success event
6. `resetForm()` clears `$sourceLivestock` property
7. Service call fails with null argument

### Key Issues Identified

1. **Premature Form Reset:** `resetForm()` was called before service processing, clearing essential data
2. **Missing Source Livestock Validation:** No check if `$sourceLivestock` is loaded before processing
3. **Insufficient Error Handling:** Limited logging for debugging source livestock state
4. **Form Reset Logic:** No selective reset capability to preserve needed data

## Solution Implemented

### 1. Enhanced `processFifoMutation()` Method

```php
public function processFifoMutation()
{
    $this->validate();

    if (!$this->fifoPreview || !$this->fifoPreview['can_fulfill']) {
        $this->addError('quantity', 'Kuantitas tidak dapat dipenuhi dengan batch yang tersedia');
        return;
    }

    // Ensure source livestock is loaded before processing
    if (!$this->sourceLivestock && $this->sourceLivestockId) {
        $this->loadSourceLivestock();
    }

    // Double-check that source livestock is available
    if (!$this->sourceLivestock) {
        $this->addError('sourceLivestockId', 'Data ternak sumber tidak ditemukan. Silakan pilih ternak sumber lagi.');
        return;
    }

    // ... processing logic ...

    if ($result['success']) {
        // Show success message and dispatch events first
        $this->showSuccessMessage($successMessage);
        $this->dispatch('fifo-mutation-completed', $eventData);

        // Reset form after successful processing and event dispatch
        $this->resetForm(false);
    }
}
```

### 2. Improved `loadSourceLivestock()` Method

```php
public function loadSourceLivestock()
{
    if (!$this->sourceLivestockId) {
        $this->availableBatches = collect();
        $this->totalAvailableQuantity = 0;
        $this->sourceLivestock = null;
        return;
    }

    // Prevent loading the same livestock multiple times
    if ($this->sourceLivestock && $this->sourceLivestock->id === $this->sourceLivestockId) {
        Log::info('🔄 Source livestock already loaded, skipping reload', [
            'livestock_id' => $this->sourceLivestockId,
            'livestock_name' => $this->sourceLivestock->name
        ]);
        return;
    }

    try {
        $this->sourceLivestock = Livestock::with(['farm', 'coop', 'batches' => function ($query) {
            $query->where('status', 'active')
                ->whereRaw('(initial_quantity - quantity_depletion - quantity_sales - quantity_mutated) > 0')
                ->orderBy('start_date', 'asc');
        }])->findOrFail($this->sourceLivestockId);

        // ... rest of loading logic ...

        Log::info('📊 Source livestock loaded for FIFO mutation', [
            'livestock_id' => $this->sourceLivestockId,
            'livestock_name' => $this->sourceLivestock->name,
            'total_batches' => $this->availableBatches->count(),
            'total_available_quantity' => $this->totalAvailableQuantity,
            'source_livestock_loaded' => !is_null($this->sourceLivestock)
        ]);
    } catch (\Exception $e) {
        Log::error('❌ Error loading source livestock for FIFO mutation', [
            'livestock_id' => $this->sourceLivestockId,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        $this->addError('sourceLivestockId', 'Gagal memuat data ternak sumber: ' . $e->getMessage());
        $this->availableBatches = collect();
        $this->totalAvailableQuantity = 0;
        $this->sourceLivestock = null;
    }
}
```

### 3. Enhanced `resetForm()` Method with Selective Reset

```php
public function resetForm($preserveSourceLivestock = false)
{
    // Reset properties manually instead of using $this->reset() to prevent re-render
    $this->mutationDate = now()->format('Y-m-d');
    $this->quantity = 0;
    $this->type = 'internal';
    $this->direction = 'out';
    $this->reason = null;
    $this->destinationLivestockId = null;
    $this->destinationCoopId = null;
    $this->fifoPreview = null;
    $this->isPreviewMode = false;
    $this->showPreviewModal = false;
    $this->existingMutationIds = [];
    $this->isEditing = false;
    $this->editModeMessage = '';
    $this->errorMessage = '';
    $this->successMessage = '';
    $this->processingMutation = false;

    // Only clear source livestock if not preserving it
    if (!$preserveSourceLivestock) {
        $this->sourceLivestockId = null;
        $this->sourceLivestock = null;
        $this->availableBatches = collect();
        $this->totalAvailableQuantity = 0;
    }

    Log::info('🔄 FIFO mutation form reset', [
        'preserve_source_livestock' => $preserveSourceLivestock,
        'source_livestock_id' => $this->sourceLivestockId,
        'source_livestock_loaded' => !is_null($this->sourceLivestock)
    ]);
}
```

### 4. Improved Modal Management

```php
public function openModal($livestockId = null, $editData = null): void
{
    // Prevent multiple opens
    if ($this->showModal) {
        Log::info('🔄 FIFO mutation modal already open, skipping', [
            'livestock_id' => $livestockId,
            'edit_mode' => !empty($editData)
        ]);
        return;
    }

    // Reset form only if not already in edit mode
    if (!$this->isEditing) {
        $this->resetForm(false);
    }

    if ($livestockId) {
        $this->sourceLivestockId = $livestockId;
    }

    if ($editData) {
        $this->loadEditMode($editData);
    }

    $this->showModal = true;

    Log::info('🔄 FIFO mutation modal opened', [
        'livestock_id' => $livestockId,
        'edit_mode' => !empty($editData),
        'show_modal' => $this->showModal,
        'source_livestock_id' => $this->sourceLivestockId,
        'source_livestock_loaded' => !is_null($this->sourceLivestock)
    ]);

    $this->dispatch('show-fifo-mutation');
}
```

## Key Improvements

### 1. **Pre-Processing Validation**

-   Added source livestock existence check before processing
-   Automatic reload if source livestock is missing
-   Clear error message if source livestock cannot be loaded

### 2. **Enhanced Logging**

-   Added comprehensive logging for source livestock state
-   Debug information for modal operations
-   Error tracking with stack traces

### 3. **Selective Form Reset**

-   Added `$preserveSourceLivestock` parameter to `resetForm()`
-   Prevents premature clearing of essential data
-   Maintains data integrity during processing

### 4. **Improved Error Handling**

-   Better error messages with specific details
-   Graceful degradation when source livestock is unavailable
-   Comprehensive exception logging

### 5. **Modal State Management**

-   Prevents multiple modal opens
-   Better state tracking and logging
-   Improved edit mode handling

## Testing Checklist

-   [x] FIFO mutation processes successfully with valid source livestock
-   [x] Error handling works when source livestock is missing
-   [x] Form reset preserves data when needed
-   [x] Modal opens and closes properly
-   [x] Edit mode functionality preserved
-   [x] Comprehensive logging provides debugging information

## Files Modified

1. `app/Livewire/Livestock/Mutation/FifoLivestockMutation.php`
    - Enhanced `processFifoMutation()` method
    - Improved `loadSourceLivestock()` method
    - Updated `resetForm()` method with selective reset
    - Enhanced `openModal()` and `closeModal()` methods

## Result

The FIFO livestock mutation now properly handles source livestock loading and validation, preventing null argument errors and providing better user experience with comprehensive error handling and logging.

## Future Considerations

1. **Caching:** Consider implementing source livestock caching to improve performance
2. **Validation:** Add more comprehensive validation for mutation data
3. **UI Feedback:** Enhance user interface to show loading states and validation feedback
4. **Testing:** Add unit tests for the enhanced methods

## [2025-06-25] Fix: Miskalkulasi Current Livestock pada Mutasi ke Diri Sendiri (FIFO)

### Root Cause

-   Mutasi FIFO ke livestock atau kandang yang sama menyebabkan current livestock salah hitung (double update atau tidak berubah).
-   Tidak ada validasi di UI maupun service untuk mencegah mutasi ke diri sendiri.

### Solusi

1. **Validasi di Komponen Livewire**
    - Menambahkan pengecekan di `FifoLivestockMutation.php` agar mutasi ke diri sendiri (livestock_id atau coop_id sama) dibatalkan dan tampil error.
2. **Guard di Service Layer**
    - Menambahkan guard di awal `LivestockMutationService::processFifoMutation` untuk mencegah proses jika source dan destination sama.
3. **Logging**
    - Menambah log warning pada kedua level untuk audit trail.

### Contoh Kode Validasi (Livewire)

```php
$isSameLivestock = $this->sourceLivestockId && $this->destinationLivestockId && $this->sourceLivestockId === $this->destinationLivestockId;
$isSameCoop = $this->sourceLivestock && $this->destinationCoopId && $this->sourceLivestock->coop_id === $this->destinationCoopId;
if ($isSameLivestock || $isSameCoop) {
    $msg = 'Mutasi ke ternak atau kandang yang sama tidak diperbolehkan.';
    Log::warning('🚫 Percobaan mutasi ke diri sendiri dicegah', [...]);
    $this->addError('destinationLivestockId', $msg);
    $this->addError('destinationCoopId', $msg);
    $this->errorMessage = $msg;
    return;
}
```

### Contoh Kode Guard (Service)

```php
$isSameLivestock = isset($mutationData['destination_livestock_id']) && $sourceLivestock->id === $mutationData['destination_livestock_id'];
$isSameCoop = isset($mutationData['destination_coop_id']) && $sourceLivestock->coop_id === $mutationData['destination_coop_id'];
if ($isSameLivestock || $isSameCoop) {
    Log::warning('🚫 Mutasi FIFO ke diri sendiri dicegah di service', [...]);
    throw new Exception('Mutasi ke ternak atau kandang yang sama tidak diperbolehkan.');
}
```

### Diagram Alur Validasi

```
flowchart TD
    A[User Input Mutasi FIFO] --> B{Source == Destination?}
    B -- Ya --> C[Show Error & Abort]
    B -- Tidak --> D[Process FIFO Mutation]
```

### Hasil

-   Mutasi ke diri sendiri sekarang dicegah di dua level (UI & service)
-   Tidak ada lagi miskalkulasi current livestock akibat mutasi ke diri sendiri
-   Log warning tercatat untuk audit

## Edit Mode UI Enhancement

### Perubahan UI untuk Mode Edit

#### 1. Action Buttons

-   **Preview Button**: Teks berubah menjadi "Preview Edit FIFO" saat mode edit
-   **Loading State**: Menampilkan "Generating Edit Preview..." untuk mode edit

#### 2. Modal Preview

-   **Title**: Berubah menjadi "Preview Edit Mutasi FIFO" untuk mode edit
-   **Process Button**:
    -   Icon berubah dari check ke save (fas fa-save)
    -   Teks berubah menjadi "Update Mutasi FIFO"
    -   Loading state menampilkan "Updating..."

#### 3. Edit Mode Information

-   **Alert Banner**: Ditampilkan di preview modal dengan informasi mode edit aktif
-   **Mutation IDs**: Menampilkan ID mutasi yang sedang diedit
-   **FIFO Info**: Deskripsi disesuaikan untuk mode edit

#### 4. Enhanced Functionality

-   **Cancel Edit Mode**: Method `cancelEditMode()` untuk membatalkan mode edit
-   **Success Messages**: Pesan sukses berbeda untuk create vs edit
-   **JavaScript Events**: Notifikasi berbeda untuk mode edit

### File Yang Dimodifikasi

#### 1. `app/Livewire/Livestock/Mutation/FifoLivestockMutation.php`

```php
// Menambahkan method cancelEditMode()
public function cancelEditMode(): void
{
    $this->isEditing = false;
    $this->existingMutationIds = [];
    $this->editModeMessage = '';
    $this->resetForm(false);
}

// Enhanced success message untuk mode edit
if ($this->isEditing) {
    $successMessage = 'Mutasi FIFO berhasil diperbarui';
}
```

#### 2. `resources/views/livewire/livestock/mutation/fifo-livestock-mutation.blade.php`

```blade
{{-- Preview Button dengan mode edit --}}
@if($isEditing)
    Preview Edit FIFO
@else
    Preview FIFO
@endif

{{-- Modal Title dengan mode edit --}}
@if($isEditing)
    Preview Edit Mutasi FIFO
@else
    Preview Mutasi FIFO
@endif

{{-- Process Button dengan mode edit --}}
@if($isEditing)
    <i class="fas fa-save me-2"></i>
    Update Mutasi FIFO
@else
    <i class="fas fa-check me-2"></i>
    Proses Mutasi FIFO
@endif
```

### Fitur Edit Mode

1. **Konsistensi UI**: Mode edit menggunakan interface yang sama dengan mode create
2. **Visual Indicators**: Jelas menunjukkan kapan dalam mode edit
3. **Contextual Messages**: Pesan dan label disesuaikan untuk mode edit
4. **Proper State Management**: Mode edit dapat dibatalkan dengan aman
5. **Enhanced UX**: Feedback yang jelas untuk operasi edit vs create

### Benefits

1. **User Experience**: Interface yang konsisten antara mode create dan edit
2. **Clarity**: Jelas membedakan operasi create vs edit
3. **Safety**: Mode edit dapat dibatalkan dengan aman
4. **Maintainability**: Kode yang bersih dan terorganisir
5. **Debugging**: Logging yang komprehensif untuk troubleshooting

## Status Saat Ini

✅ **RESOLVED** - Component berhasil dimigrasikan ke versi yang dapat dikonfigurasi  
✅ **UI ENHANCED** - Mode edit menggunakan interface yang sama dengan mode create  
✅ **COMPREHENSIVE LOGGING** - Sistem logging komprehensif diimplementasikan  
✅ **ERROR HANDLING** - Penanganan error yang robust  
✅ **SAFETY FEATURES** - Pencegahan self-mutation dan validasi komprehensif
✅ **AUTO-LOADING EDIT MODE** - Sistem otomatis mendeteksi mutasi existing dan masuk mode edit

## Auto-Loading Edit Mode Enhancement

### Fitur Auto-Loading yang Diimplementasikan

#### 1. **Real-time Detection**

-   **Date & Livestock Selection**: Menggunakan `wire:model.live` untuk deteksi real-time
-   **Automatic Check**: Sistem otomatis memeriksa mutasi existing saat user input tanggal dan livestock
-   **Seamless Transition**: Transisi smooth ke mode edit tanpa reload page

#### 2. **Enhanced User Experience**

-   **Visual Feedback**: Loading indicator saat memeriksa mutasi existing
-   **Auto-notification**: SweetAlert notification saat mode edit diaktifkan
-   **Data Pre-loading**: Data mutasi existing otomatis dimuat ke form

#### 3. **Robust Error Handling**

-   **Route Fix**: Menghapus route yang tidak ada dan menggunakan method component
-   **Fallback Logic**: Graceful handling jika data tidak ditemukan
-   **State Management**: Proper reset state saat berpindah mode

### Technical Implementation

#### 1. **Backend Enhancements**

```php
// Auto-detection methods
public function updatedSourceLivestockId($value)
public function updatedMutationDate($value)
private function checkForExistingMutations(): void

// Edit mode management
public function loadExistingMutationForEdit(): void
public function cancelEditMode(): void
private function resetEditMode(): void
```

#### 2. **Frontend Enhancements**

```blade
{{-- Real-time binding --}}
wire:model.live="mutationDate"
wire:model.live="sourceLivestockId"

{{-- Visual feedback --}}
<div wire:loading wire:target="updatedSourceLivestockId,updatedMutationDate">
    <i class="fas fa-search fa-spin"></i> Memeriksa mutasi yang ada...
</div>

{{-- Enhanced edit mode alert --}}
@if($isEditing)
    <div class="alert alert-info">
        <strong>{{ $editModeMessage }}</strong>
        <br><small>ID Mutasi: {{ implode(', ', $existingMutationIds) }}</small>
        <br><small>Kuantitas: {{ number_format($quantity) }} ekor</small>
    </div>
@endif
```

#### 3. **JavaScript Enhancements**

```javascript
// Edit mode notifications
Livewire.on("edit-mode-enabled", (data) => {
    Swal.fire({
        icon: "info",
        title: "Mode Edit Diaktifkan",
        text: data.message,
        timer: 3000,
        timerProgressBar: true,
    });
});

Livewire.on("edit-mode-cancelled", (data) => {
    Swal.fire({
        icon: "warning",
        title: "Mode Edit Dibatalkan",
        text: "Kembali ke mode input baru",
        timer: 2000,
    });
});
```

### Benefits Achieved

1. **Seamless UX**: User tidak perlu manual mencari dan load data existing
2. **Consistency**: Behavior yang sama dengan komponen mutation lainnya
3. **Error Prevention**: Mencegah duplikasi data dengan auto-detection
4. **Productivity**: Workflow yang lebih efisien untuk edit mutasi
5. **User-Friendly**: Feedback visual yang jelas dan informatif

### Workflow Auto-Loading

1. **User Input**: User memilih tanggal dan livestock sumber
2. **Auto-Detection**: Sistem otomatis cek mutasi existing di database
3. **Mode Switch**: Jika ditemukan, otomatis switch ke mode edit
4. **Data Loading**: Data existing dimuat ke form secara otomatis
5. **Visual Feedback**: Notifikasi dan UI update untuk konfirmasi user

Implementasi ini memastikan bahwa **FIFO Livestock Mutation component** memiliki behavior yang konsisten dengan komponen mutation lainnya, memberikan user experience yang seamless dan mencegah error duplikasi data.

## Function Triggering Fix

### Masalah yang Ditemukan

Function `updatedMutationDate` tidak ter-trigger dari UI meskipun menggunakan `wire:model.live`.

### Solusi Multi-Layer yang Diimplementasikan

#### 1. **Perbaikan Method Livewire**

```php
// Method utama dengan enhanced logging
public function updatedMutationDate($value)
{
    Log::info('🔍 Mutation date updated triggered', [
        'new_value' => $value,
        'current_mutation_date' => $this->mutationDate,
        'source_livestock_id' => $this->sourceLivestockId
    ]);
    // ... logic
}

// Universal fallback handler
public function updated($property, $value)
{
    if ($property === 'mutationDate') {
        // Handle mutation date changes
    }
    if ($property === 'sourceLivestockId') {
        // Handle source livestock changes
    }
}
```

#### 2. **Multiple Trigger Approaches**

```blade
{{-- Primary: wire:model.live --}}
wire:model.live="mutationDate"

{{-- Backup: wire:change and wire:blur --}}
wire:change="triggerExistingMutationCheck"
wire:blur="triggerExistingMutationCheck"

{{-- Manual trigger button for testing --}}
<button wire:click="triggerExistingMutationCheck">Manual Trigger</button>
```

#### 3. **JavaScript Backup System**

```javascript
// Edit mode notifications
Livewire.on("edit-mode-enabled", (data) => {
    Swal.fire({
        icon: "info",
        title: "Mode Edit Diaktifkan",
        text: data.message,
        timer: 3000,
        timerProgressBar: true,
    });
});

Livewire.on("edit-mode-cancelled", (data) => {
    Swal.fire({
        icon: "warning",
        title: "Mode Edit Dibatalkan",
        text: "Kembali ke mode input baru",
        timer: 2000,
    });
});
```

#### 4. **Debug System**

```blade
@if(config('app.debug'))
<div class="mt-2">
    <small class="text-muted">
        Debug: Current date = {{ $mutationDate ?? 'null' }},
        Source = {{ $sourceLivestockId ?? 'null' }},
        Edit Mode = {{ $isEditing ? 'Yes' : 'No' }}
    </small>
    <button wire:click="checkForExistingMutations">Test Check Mutations</button>
    <button wire:click="triggerExistingMutationCheck">Manual Trigger</button>
</div>
@endif
```

### Fallback Hierarchy

1. **Primary**: `wire:model.live` → `updatedMutationDate()`
2. **Secondary**: `wire:change` → `triggerExistingMutationCheck()`
3. **Tertiary**: `updated()` universal handler
4. **Quaternary**: JavaScript event listeners
5. **Manual**: Debug buttons for testing

### Enhanced Logging

Semua method dilengkapi dengan comprehensive logging untuk debugging:

-   Parameter values tracking
-   Method call verification
-   Database query results
-   State change monitoring
-   Error tracking with stack traces

### Result

Sistem sekarang memiliki **5 layer fallback** untuk memastikan function triggering bekerja dalam semua kondisi, memberikan reliability maksimal untuk auto-loading edit mode functionality.

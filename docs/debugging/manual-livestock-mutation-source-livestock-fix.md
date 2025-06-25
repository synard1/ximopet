# Manual Livestock Mutation - Source Livestock Auto-Loading Fix

**Tanggal:** 23 Januari 2025  
**Versi:** 1.1  
**Status:** ✅ COMPLETED

## 📋 Masalah yang Diperbaiki

**Issue:** Sumber ternak / livestock tidak terbaca otomatis ketika livestock_id diberikan ke komponen Manual Livestock Mutation.

**Root Cause:**

1. `resetComponent()` method mengosongkan `sourceLivestockId` sebelum livestock dimuat
2. Livestock tidak tersedia dalam dropdown options jika tidak dimuat ulang
3. Kurangnya visual feedback untuk livestock yang sudah dipilih

## 🔧 Solusi yang Diimplementasikan

### 1. Enhanced Mount Method

```php
public function mount($livestockId = null)
{
    $this->initializeComponent();

    // Auto-set source livestock if provided via mount
    if ($livestockId) {
        $this->sourceLivestockId = $livestockId;
        $this->loadSourceLivestock();
        $this->checkForExistingMutations();

        Log::info('🔄 Source livestock auto-set via mount', [
            'livestock_id' => $livestockId,
            'livestock_name' => $this->sourceLivestock->name ?? 'Not loaded'
        ]);
    }
}
```

### 2. Improved OpenModal Method

```php
public function openModal($livestockId = null, $editData = null): void
{
    // Reset component but preserve livestock_id if provided
    $preservedLivestockId = $livestockId;
    $this->resetComponent();

    // Set source livestock if provided
    if ($preservedLivestockId) {
        $this->sourceLivestockId = $preservedLivestockId;
        $this->loadSourceLivestock();
        $this->checkForExistingMutations();
    }

    // ... rest of method
}
```

### 3. Enhanced LoadSourceLivestock Method

**Improvements:**

-   Added `currentLivestock` relationship loading
-   Better error handling with null assignment
-   Enhanced logging with farm, coop, and quantity info
-   Auto-add livestock to dropdown options if missing

```php
private function loadSourceLivestock(): void
{
    try {
        $this->sourceLivestock = Livestock::with(['farm', 'coop', 'batches', 'currentLivestock'])
            ->findOrFail($this->sourceLivestockId);

        // Ensure livestock is in the allLivestock array for dropdown
        $this->ensureLivestockInOptions($this->sourceLivestock);

        $this->loadAvailableBatches();

        // Enhanced logging
    } catch (Exception $e) {
        $this->errorMessage = 'Gagal memuat data ternak sumber: ' . $e->getMessage();
        $this->sourceLivestock = null;
    }
}
```

### 4. New EnsureLivestockInOptions Method

```php
private function ensureLivestockInOptions($livestock): void
{
    $livestockExists = collect($this->allLivestock)->contains('id', $livestock->id);

    if (!$livestockExists) {
        // Add livestock to options if not present
        $this->allLivestock[] = [
            'id' => $livestock->id,
            'name' => $livestock->name,
            'farm_name' => $livestock->farm->name ?? 'Unknown Farm',
            'coop_name' => $livestock->coop->name ?? 'Unknown Coop',
            'current_quantity' => $livestock->currentLivestock->quantity ?? 0,
            'display_name' => sprintf(
                '%s (%s - %s)',
                $livestock->name,
                $livestock->farm->name ?? 'Unknown Farm',
                $livestock->coop->name ?? 'Unknown Coop'
            )
        ];
    }
}
```

### 5. Enhanced UI Visual Feedback

**Added Source Livestock Info Display:**

```html
{{-- Show selected source livestock info --}} @if($sourceLivestock)
<div class="alert alert-light-success mt-3">
    <div class="d-flex align-items-center">
        <i class="ki-duotone ki-check-circle fs-2hx text-success me-4">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>
        <div>
            <h5 class="mb-1">{{ $sourceLivestock->name }}</h5>
            <p class="mb-0">
                Farm: {{ $sourceLivestock->farm->name ?? 'Unknown' }} | Kandang:
                {{ $sourceLivestock->coop->name ?? 'Unknown' }} | Saat ini: {{
                $sourceLivestock->currentLivestock->quantity ?? 0 }} ekor
            </p>
        </div>
    </div>
</div>
@endif
```

### 6. Additional Helper Methods

**setSourceLivestock() Method:**

```php
public function setSourceLivestock($livestockId): void
{
    if ($livestockId) {
        $this->sourceLivestockId = $livestockId;
        $this->loadSourceLivestock();
        $this->checkForExistingMutations();
    }
}
```

**debugComponentState() Method:**

```php
public function debugComponentState(): array
{
    return [
        'sourceLivestockId' => $this->sourceLivestockId,
        'sourceLivestock' => $this->sourceLivestock ? [...] : null,
        'allLivestock_count' => count($this->allLivestock),
        'availableBatches_count' => count($this->availableBatches),
        // ... other state info
    ];
}
```

## 🎯 Cara Penggunaan

### 1. Via Mount Parameter

```php
// Di Blade template
<livewire:livestock.mutation.manual-livestock-mutation :livestock-id="$livestock->id" />
```

### 2. Via OpenModal Method

```javascript
// Via JavaScript/Livewire event
$wire.openModal("{{ $livestock->id }}");
```

### 3. Via Direct Method Call

```php
// Di parent component
$this->emit('setSourceLivestock', $livestockId);
```

## 🔍 Debugging & Monitoring

### Enhanced Logging

```php
Log::info('🔄 Source livestock auto-set via mount', [
    'livestock_id' => $livestockId,
    'livestock_name' => $this->sourceLivestock->name ?? 'Not loaded',
    'farm_name' => $this->sourceLivestock->farm->name ?? 'Unknown',
    'coop_name' => $this->sourceLivestock->coop->name ?? 'Unknown',
    'current_quantity' => $this->sourceLivestock->currentLivestock->quantity ?? 0
]);
```

### Debug State Check

```php
// Check component state
$state = $component->debugComponentState();
```

## 🧪 Testing Scenarios

### 1. Mount dengan Livestock ID

-   [x] Component menerima livestock_id via mount
-   [x] Source livestock dimuat otomatis
-   [x] Dropdown menampilkan livestock yang dipilih
-   [x] Info livestock ditampilkan di UI

### 2. OpenModal dengan Livestock ID

-   [x] Modal dibuka dengan livestock pre-selected
-   [x] Source livestock dimuat dan ditampilkan
-   [x] Available batches dimuat otomatis

### 3. Manual Selection

-   [x] User dapat mengubah source livestock
-   [x] Dropdown berfungsi normal
-   [x] Info livestock terupdate real-time

### 4. Error Handling

-   [x] Livestock tidak ditemukan ditangani dengan baik
-   [x] Error message informatif
-   [x] Component state tetap stabil

## 📈 Benefits

### 1. User Experience

-   **Auto-loading:** Livestock langsung terpilih saat modal dibuka
-   **Visual Feedback:** Info livestock yang jelas dan informatif
-   **Error Handling:** Pesan error yang helpful

### 2. Developer Experience

-   **Multiple Integration Methods:** Mount, openModal, direct method
-   **Debug Support:** State checking dan comprehensive logging
-   **Flexible Usage:** Berbagai cara untuk set source livestock

### 3. System Reliability

-   **Robust Error Handling:** Graceful handling untuk edge cases
-   **Data Consistency:** Livestock selalu tersedia di dropdown
-   **State Management:** Proper component state management

## 🚀 Implementation Status

-   [x] Enhanced mount method with livestock_id parameter
-   [x] Improved openModal with livestock preservation
-   [x] Enhanced loadSourceLivestock with better error handling
-   [x] Added ensureLivestockInOptions method
-   [x] Enhanced UI with visual feedback
-   [x] Added helper methods (setSourceLivestock, debugComponentState)
-   [x] Comprehensive logging and debugging
-   [x] Documentation created

## 📝 Usage Examples

### Example 1: Livestock List dengan Mutation Button

```html
@foreach($livestocks as $livestock)
<tr>
    <td>{{ $livestock->name }}</td>
    <td>{{ $livestock->farm->name }}</td>
    <td>
        <button
            class="btn btn-primary"
            wire:click="$emit('openLivestockMutation', '{{ $livestock->id }}')"
        >
            Mutasi
        </button>
    </td>
</tr>
@endforeach {{-- Modal Component --}}
<livewire:livestock.mutation.manual-livestock-mutation />
```

### Example 2: Direct Component Usage

```html
{{-- Langsung dengan livestock_id --}}
<livewire:livestock.mutation.manual-livestock-mutation
    :livestock-id="$selectedLivestock->id"
/>
```

### Example 3: JavaScript Integration

```javascript
// Open modal dengan livestock pre-selected
Livewire.emit("openLivestockMutation", livestockId);

// Set source livestock directly
$wire.setSourceLivestock(livestockId);
```

## 🔮 Future Enhancements

1. **Bulk Selection:** Multiple livestock selection untuk batch mutation
2. **Quick Actions:** Shortcut buttons untuk common mutations
3. **History Integration:** Show recent mutations untuk livestock
4. **Smart Suggestions:** Suggest destination berdasarkan history

---

**Status:** ✅ COMPLETED  
**Next Action:** Testing & User Feedback Collection

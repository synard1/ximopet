# Records Form Conditional Visibility

## Tanggal: 2025-06-23

## Status: IMPLEMENTED

### Overview

Refactoring form records untuk menyesuaikan visibilitas input berdasarkan konfigurasi livestock yang telah disimpan. Jika method manual dikonfigurasi, input terkait di form records akan disembunyikan dan diganti dengan keterangan bahwa input dilakukan di menu terpisah.

### Problem Statement

Sebelumnya, form records selalu menampilkan semua input (mortality, culling, feed usage) terlepas dari konfigurasi method yang dipilih untuk livestock. Hal ini dapat menyebabkan:

1. **Duplikasi Data:** User bisa input data di form records DAN di menu manual
2. **Konfusi:** User tidak tahu harus input dimana
3. **Inkonsistensi:** Data bisa berbeda antara kedua input method

### Solution Implementation

#### 1. **Enhanced Records Component**

Modified `app/Livewire/Records.php`:

**Added Configuration Properties:**

```php
// Configuration properties
public $livestockConfig = [];
public $isManualDepletionEnabled = false;
public $isManualFeedUsageEnabled = false;
```

**Added Configuration Loading Method:**

```php
/**
 * Load livestock configuration and set visibility flags
 */
private function loadLivestockConfiguration(Livestock $livestock): void
{
    $this->livestockConfig = $livestock->getConfiguration();
    $this->isManualDepletionEnabled = $livestock->isManualDepletionEnabled();
    $this->isManualFeedUsageEnabled = $livestock->isManualFeedUsageEnabled();

    Log::info('Records - Livestock Configuration Loaded', [
        'livestock_id' => $livestock->id,
        'config' => $this->livestockConfig,
        'manual_depletion_enabled' => $this->isManualDepletionEnabled,
        'manual_feed_usage_enabled' => $this->isManualFeedUsageEnabled,
    ]);
}
```

**Updated setRecords Method:**

-   Added configuration loading after livestock is found
-   Reload configuration after auto-save for single batch
-   Enhanced logging for debugging

#### 2. **Conditional Form Visibility**

Modified `resources/views/livewire/records.blade.php`:

**Mortality & Culling Inputs:**

```php
@if(!$isManualDepletionEnabled)
    <x-input.group col="6" label="💀 Mati (Ekor)">
        <input type="number" wire:model="mortality" class="form-control" placeholder="Jumlah ayam mati">
        <!-- Yesterday data display -->
    </x-input.group>

    <x-input.group col="6" label="🛑 Afkir (Ekor)">
        <input type="number" wire:model="culling" class="form-control" placeholder="Ayam tidak layak">
        <!-- Yesterday data display -->
    </x-input.group>
@else
    <!-- Manual Depletion Notice -->
    <div class="col-12">
        <div class="alert alert-info d-flex align-items-center" role="alert">
            <i class="fas fa-info-circle me-2"></i>
            <div>
                <strong>Mode Manual Depletion Aktif:</strong>
                Data kematian dan afkir dicatat melalui menu <strong>"Manual Depletion"</strong> pada tabel livestock.
                Input otomatis di form ini dinonaktifkan untuk mencegah duplikasi data.
            </div>
        </div>
    </div>
@endif
```

**Feed Usage Table:**

```php
@if(!$isManualFeedUsageEnabled && Auth::user()->can('create feed usage'))
    <!-- Normal feed usage table -->
@elseif($isManualFeedUsageEnabled)
    <!-- Manual Feed Usage Notice -->
    <h3 class="text-lg font-semibold mb-3 text-gray-700 border-b pb-2">📦 Penggunaan Pakan</h3>
    <div class="bg-info-50 border border-info-200 rounded p-3">
        <div class="d-flex align-items-center">
            <i class="fas fa-info-circle me-2 text-info"></i>
            <div>
                <strong>Mode Manual Feed Usage Aktif:</strong><br>
                <small class="text-muted">
                    Data penggunaan pakan dicatat melalui menu <strong>"Manual Usage"</strong> pada tabel livestock.
                    Input otomatis di form ini dinonaktifkan untuk mencegah duplikasi data.
                </small>
            </div>
        </div>
    </div>
@elseif(!Auth::user()->can('create feed usage'))
    <!-- Permission denied message -->
@endif
```

### Configuration Logic

#### Depletion Methods

1. **FIFO/LIFO Configuration:**

    - ✅ Show mortality & culling inputs in records form
    - ❌ Hide Manual Depletion menu in actions

2. **Manual Configuration:**
    - ❌ Hide mortality & culling inputs in records form
    - ✅ Show Manual Depletion menu in actions
    - ℹ️ Display informative notice in records form

#### Feed Usage Methods

1. **FIFO/LIFO Configuration:**

    - ✅ Show feed usage table in records form
    - ❌ Hide Manual Usage menu in actions

2. **Manual Configuration:**

    - ❌ Hide feed usage table in records form
    - ✅ Show Manual Usage menu in actions
    - ℹ️ Display informative notice in records form

3. **Total Configuration (Single Batch):**
    - ✅ Show feed usage table in records form
    - ❌ Hide Manual Usage menu in actions

### User Experience Scenarios

#### Scenario 1: Fresh Livestock (No Configuration)

-   **Records Form:** All inputs visible (default behavior)
-   **Actions Menu:** No manual menus visible
-   **User Action:** Must configure settings first

#### Scenario 2: FIFO/LIFO Configuration

-   **Records Form:** All inputs visible and functional
-   **Actions Menu:** No manual menus visible
-   **User Action:** Use records form for all data entry

#### Scenario 3: Manual Configuration

-   **Records Form:** Manual inputs hidden with informative notices
-   **Actions Menu:** Manual menus visible and functional
-   **User Action:** Use dedicated manual menus for specific data

#### Scenario 4: Mixed Configuration

-   **Records Form:** Only non-manual inputs visible
-   **Actions Menu:** Only configured manual menus visible
-   **User Action:** Use appropriate interface for each data type

### Benefits

#### 1. **Data Integrity**

-   Prevents duplicate data entry
-   Ensures single source of truth for each data type
-   Eliminates data conflicts between different input methods

#### 2. **User Experience**

-   Clear guidance on where to input data
-   Reduces confusion about which interface to use
-   Consistent behavior across all livestock

#### 3. **System Consistency**

-   Form behavior matches configuration settings
-   Visual feedback reflects actual system state
-   Predictable interface behavior

#### 4. **Error Prevention**

-   Impossible to accidentally input data in wrong place
-   Clear notices explain why inputs are disabled
-   Guided workflow based on configuration

### Files Modified

-   `app/Livewire/Records.php`
    -   Added configuration properties
    -   Added `loadLivestockConfiguration()` method
    -   Updated `setRecords()` method
    -   Enhanced logging for debugging
-   `resources/views/livewire/records.blade.php`
    -   Added conditional visibility for mortality/culling inputs
    -   Added conditional visibility for feed usage table
    -   Added informative notices for manual modes
    -   Improved permission handling logic

### Testing Verification

1. **FIFO Configuration:** Open records → All inputs visible and functional
2. **Manual Configuration:** Open records → Manual inputs hidden with notices
3. **Mixed Configuration:** Open records → Only relevant inputs visible
4. **Configuration Changes:** Change settings → Form immediately reflects changes

### Future Enhancements

-   Add supply usage conditional visibility (if manual supply usage is implemented)
-   Add configuration status indicator in form header
-   Add quick configuration change buttons in form
-   Add validation to prevent manual data entry when automated methods are active

### Production Ready Features

-   ✅ Null-safe configuration checking
-   ✅ Real-time configuration loading
-   ✅ Clear user communication
-   ✅ Comprehensive error prevention
-   ✅ Detailed logging for debugging
-   ✅ Graceful fallback behavior
-   ✅ Permission-aware visibility logic

This implementation ensures that the records form accurately reflects the livestock's configuration state, preventing data duplication and providing clear guidance to users about the appropriate data entry method for each type of information.

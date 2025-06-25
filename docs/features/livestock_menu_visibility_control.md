# Livestock Menu Visibility Control

## Tanggal: 2025-06-23

## Status: IMPLEMENTED

### Overview

Implementasi kontrol visibilitas menu berdasarkan konfigurasi livestock yang telah disimpan. Menu "Manual Depletion" dan "Manual Usage" hanya akan muncul jika method manual sudah dikonfigurasi untuk livestock tersebut.

### Problem Statement

Sebelumnya, menu "Manual Depletion" dan "Manual Usage" selalu muncul untuk semua livestock, terlepas dari konfigurasi yang telah disimpan. Hal ini dapat membingungkan pengguna dan tidak konsisten dengan sistem konfigurasi yang telah diimplementasikan.

### Solution Implementation

#### 1. Helper Methods di Model Livestock

Ditambahkan helper methods di `app/Models/Livestock.php` untuk mengecek konfigurasi:

```php
/**
 * Check if manual depletion is configured for this livestock
 */
public function isManualDepletionEnabled(): bool
{
    $config = $this->getDataColumn('config');

    if (!$config || !isset($config['depletion_method'])) {
        return false;
    }

    return $config['depletion_method'] === 'manual';
}

/**
 * Check if manual feed usage is configured for this livestock
 */
public function isManualFeedUsageEnabled(): bool
{
    $config = $this->getDataColumn('config');

    if (!$config || !isset($config['feed_usage_method'])) {
        return false;
    }

    return $config['feed_usage_method'] === 'manual';
}
```

#### 2. Additional Configuration Helper Methods

Ditambahkan methods untuk akses konfigurasi yang lebih lengkap:

-   `getConfiguredRecordingMethod()` - Get recording method yang dikonfigurasi
-   `getConfiguredDepletionMethod()` - Get depletion method yang dikonfigurasi
-   `getConfiguredMutationMethod()` - Get mutation method yang dikonfigurasi
-   `getConfiguredFeedUsageMethod()` - Get feed usage method yang dikonfigurasi
-   `hasConfiguration()` - Check apakah livestock memiliki konfigurasi
-   `getConfiguration()` - Get full configuration array

#### 3. Conditional Menu Visibility

Updated `resources/views/pages/masterdata/livestock/_actions.blade.php`:

```php
@if($livestock->isManualDepletionEnabled())
<!--begin::Menu item-->
<div class="menu-item px-3">
    <a href="#" class="menu-link px-3" data-livestock-id="{{ $livestock->id }}" data-kt-action="manual_depletion">
        <i class="ki-duotone ki-minus-circle fs-6 me-2">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>
        Manual Depletion
    </a>
</div>
<!--end::Menu item-->
@endif

@if($livestock->isManualFeedUsageEnabled())
<!--begin::Menu item-->
<div class="menu-item px-3">
    <a href="#" class="menu-link px-3" data-livestock-id="{{ $livestock->id }}" data-kt-action="manual_usage">
        <i class="ki-duotone ki-minus-circle fs-6 me-2">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>
        Manual Usage
    </a>
</div>
<!--end::Menu item-->
@endif
```

### Configuration Logic

1. **No Configuration:** Menu tidak muncul (default behavior)
2. **FIFO/LIFO Configuration:** Menu tidak muncul
3. **Manual Configuration:** Menu muncul dan dapat digunakan

### Behavior Scenarios

#### Scenario 1: Fresh Livestock (No Configuration)

-   **Manual Depletion:** ❌ Hidden
-   **Manual Usage:** ❌ Hidden
-   **Action Required:** User harus mengonfigurasi settings terlebih dahulu

#### Scenario 2: FIFO/LIFO Configuration

-   **Manual Depletion:** ❌ Hidden (karena depletion_method = 'fifo'/'lifo')
-   **Manual Usage:** ❌ Hidden (karena feed_usage_method = 'fifo'/'lifo')
-   **Action Required:** User dapat mengubah ke manual di settings

#### Scenario 3: Manual Configuration

-   **Manual Depletion:** ✅ Visible (karena depletion_method = 'manual')
-   **Manual Usage:** ✅ Visible (karena feed_usage_method = 'manual')
-   **Action Available:** User dapat langsung menggunakan menu

#### Scenario 4: Mixed Configuration

-   **Manual Depletion:** ✅ Visible (depletion_method = 'manual')
-   **Manual Usage:** ❌ Hidden (feed_usage_method = 'fifo')
-   **Behavior:** Hanya menu yang dikonfigurasi manual yang muncul

### Files Modified

-   `app/Models/Livestock.php`
    -   Added `isManualDepletionEnabled()` method
    -   Added `isManualFeedUsageEnabled()` method
    -   Added configuration helper methods
-   `resources/views/pages/masterdata/livestock/_actions.blade.php`
    -   Added conditional visibility for Manual Depletion menu
    -   Added conditional visibility for Manual Usage menu

### Benefits

1. **User Experience:** Menu hanya muncul ketika relevan
2. **Consistency:** Sesuai dengan sistem konfigurasi yang ada
3. **Error Prevention:** Mencegah akses ke fitur yang belum dikonfigurasi
4. **Clarity:** User jelas memahami status konfigurasi livestock

### Testing Verification

1. **Fresh Livestock:** Buka actions menu → Manual menus hidden
2. **Configure to Manual:** Set depletion/usage to manual → Manual menus appear
3. **Configure to FIFO:** Set depletion/usage to FIFO → Manual menus hidden
4. **Mixed Config:** Set depletion=manual, usage=FIFO → Only Manual Depletion visible

### Future Enhancements

-   Add tooltips explaining why menu is hidden
-   Add quick configuration shortcut in actions menu
-   Add status indicators showing current configuration
-   Implement similar logic for other method-dependent features

### Production Ready Features

-   ✅ Null-safe configuration checking
-   ✅ Fallback to safe defaults
-   ✅ Clear separation of concerns
-   ✅ Maintainable helper methods
-   ✅ Consistent naming conventions
-   ✅ Comprehensive documentation

This implementation ensures that menu visibility accurately reflects the livestock's configuration state, providing a more intuitive and consistent user experience.

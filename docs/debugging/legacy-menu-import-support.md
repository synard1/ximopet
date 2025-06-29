# Legacy Menu Import Support

## Overview

**Date:** 2025-06-29  
**Feature:** Enhanced menu import functionality with legacy format support  
**Status:** ✅ Production Ready

Sistem menu import telah dikembangkan untuk mendukung backward compatibility dengan backup lama yang menggunakan ID integer (legacy format) dan format terbaru yang menggunakan UUID.

## Problem Statement

Backup menu lama menggunakan format dengan ID integer dan struktur yang berbeda:

```json
{
    "id": 48, // Integer ID (legacy)
    "name": "dashboard",
    "roles": [{ "id": 1, "name": "SuperAdmin" }], // Integer role IDs
    "permissions": [{ "id": 1, "name": "access master data" }] // Integer permission IDs
}
```

Sementara format terbaru menggunakan UUID:

```json
{
    "id": "9f44d80d-8f40-4e12-84d8-a5221da177f7", // UUID (current)
    "name": "dashboard",
    "roles": [{ "id": "uuid-string", "name": "SuperAdmin" }], // UUID role IDs
    "permissions": [{ "id": "uuid-string", "name": "access master data" }] // UUID permission IDs
}
```

## Solution Architecture

### 1. LegacyMenuImportService

Service utama yang menangani import dengan deteksi format otomatis:

```php
<?php

namespace App\Services;

class LegacyMenuImportService
{
    /**
     * Import menu configuration with legacy support
     */
    public function importMenuConfiguration(array $menuConfig, string $location = 'sidebar'): array

    /**
     * Detect if the menu configuration is in legacy format
     */
    private function detectLegacyFormat(array $menuConfig): bool

    /**
     * Import legacy format menus (with integer IDs)
     */
    private function importLegacyMenus(array $menuItems, ?string $parentId, string $location, ?User $adminUser): array

    /**
     * Import current format menus (with UUID)
     */
    private function importCurrentMenus(array $menuItems, ?string $parentId, string $location, ?User $adminUser): array

    /**
     * Get import preview information
     */
    public function getImportPreview(array $menuConfig): array

    /**
     * Validate menu configuration structure
     */
    public function validateMenuConfiguration(array $menuConfig): array
}
```

### 2. Enhanced MenuController

Controller yang diupdate untuk menggunakan service baru:

```php
public function import(Request $request)
{
    // Validate menu configuration
    $validation = $this->legacyMenuImportService->validateMenuConfiguration($menuConfig);

    // Import menu configuration using the legacy service
    $result = $this->legacyMenuImportService->importMenuConfiguration($menuConfig, 'sidebar');

    if ($result['success']) {
        $message = sprintf(
            'Menu configuration imported successfully! Format: %s, Imported: %d menus, Roles: %d, Permissions: %d',
            $result['format'],
            $result['imported_count'],
            $result['roles_attached'],
            $result['permissions_attached']
        );
    }
}

public function importPreview(Request $request)
{
    // Get preview information before actual import
    $preview = $this->legacyMenuImportService->getImportPreview($menuConfig);
    $validation = $this->legacyMenuImportService->validateMenuConfiguration($menuConfig);
}
```

## Key Features

### 1. Automatic Format Detection

System otomatis mendeteksi format berdasarkan tipe ID:

```php
private function detectLegacyFormat(array $menuConfig): bool
{
    if (empty($menuConfig)) {
        return false;
    }

    $firstMenu = $menuConfig[0];

    // Check if ID is integer (legacy) or string/UUID (current)
    if (isset($firstMenu['id'])) {
        return is_int($firstMenu['id']);
    }

    // Additional checks for roles and permissions
    // ...
}
```

### 2. Legacy ID Handling

Legacy integer IDs diabaikan dan sistem generate UUID baru:

```php
// Legacy IDs are ignored, new UUIDs are generated
$menuData = [
    'parent_id' => $parentId,
    'name' => $menuItem['name'] ?? null,
    'label' => $menuItem['label'] ?? null,
    // ... other fields
    'created_by' => $adminUser ? $adminUser->id : null,
    'updated_by' => $adminUser ? $adminUser->id : null,
];

$menu = Menu::create($menuData); // Laravel generates new UUID
```

### 3. Role and Permission Mapping

System map berdasarkan name, bukan ID:

```php
// Legacy format: Extract names from legacy data
$roleNames = $this->extractLegacyRoleNames($menuItem['roles']);
$roleIds = Role::whereIn('name', $roleNames)->pluck('id');

// Current format: Already uses names
$roleNames = array_column($menuItem['roles'], 'name');
$roleIds = Role::whereIn('name', $roleNames)->pluck('id');
```

### 4. Import Preview

Preview sebelum import untuk validasi:

```php
$preview = [
    'format' => $isLegacyFormat ? 'legacy' : 'current',
    'total_menus' => $totalMenus,
    'total_roles' => $totalRoles,
    'total_permissions' => $totalPermissions,
    'top_level_menus' => count($menuConfig),
    'has_children' => $this->hasChildrenMenus($menuConfig)
];
```

### 5. Comprehensive Validation

Validasi struktur data sebelum import:

```php
public function validateMenuConfiguration(array $menuConfig): array
{
    $errors = [];
    $warnings = [];

    // Required fields validation
    if (empty($menuItem['name'])) {
        $errors[] = "$menuPath: 'name' field is required";
    }

    if (empty($menuItem['label'])) {
        $errors[] = "$menuPath: 'label' field is required";
    }

    // Recursive validation for children
    // ...
}
```

## Usage Examples

### 1. Import Legacy Format

```bash
# Upload file dengan format legacy (integer IDs)
POST /administrator/menu/import
Content-Type: multipart/form-data

# Response:
"Menu configuration imported successfully! Format: legacy, Imported: 47 menus, Roles: 25, Permissions: 15"
```

### 2. Import Current Format

```bash
# Upload file dengan format current (UUID)
POST /administrator/menu/import
Content-Type: multipart/form-data

# Response:
"Menu configuration imported successfully! Format: current, Imported: 47 menus, Roles: 25, Permissions: 15"
```

### 3. Preview Import

```bash
# Preview sebelum import
POST /administrator/menu/import-preview
Content-Type: multipart/form-data

# Response:
{
    "success": true,
    "preview": {
        "format": "legacy",
        "total_menus": 47,
        "total_roles": 25,
        "total_permissions": 15,
        "top_level_menus": 9,
        "has_children": true
    },
    "validation": {
        "errors": [],
        "warnings": ["menu[5]: 'route' field is empty"]
    }
}
```

## Backward Compatibility

### Legacy Format Support

✅ **Integer IDs**: Legacy integer IDs diabaikan, UUID baru di-generate  
✅ **Role Mapping**: Map berdasarkan name, bukan ID  
✅ **Permission Mapping**: Map berdasarkan name, bukan ID  
✅ **Hierarchical Structure**: Children menu tetap diproses dengan benar  
✅ **Field Mapping**: Semua field kompatibel

### Current Format Support

✅ **UUID Support**: Format terbaru tetap didukung  
✅ **Enhanced Structure**: Struktur terbaru dengan metadata tambahan  
✅ **New Fields**: Field baru otomatis ter-handle

## Testing

### 1. Legacy Format Test

```php
// Test dengan data legacy
$legacyData = [
    [
        "id" => 48,  // Integer ID
        "name" => "dashboard",
        "roles" => [["id" => 1, "name" => "SuperAdmin"]]
    ]
];

$service = new LegacyMenuImportService();
$preview = $service->getImportPreview($legacyData);

// Expected: format = "legacy"
assert($preview['format'] === 'legacy');
```

### 2. Current Format Test

```php
// Test dengan data current
$currentData = [
    [
        "id" => "uuid-string",  // UUID
        "name" => "dashboard",
        "roles" => [["id" => "uuid-string", "name" => "SuperAdmin"]]
    ]
];

$preview = $service->getImportPreview($currentData);

// Expected: format = "current"
assert($preview['format'] === 'current');
```

## Files Modified

### Core Files

1. **`app/Services/LegacyMenuImportService.php`** - Service utama untuk legacy import
2. **`app/Http/Controllers/MenuController.php`** - Enhanced controller dengan preview
3. **`routes/web.php`** - Route untuk import preview

### Testing Files

1. **`testing/legacy-menu-sample.json`** - Sample data legacy untuk testing

## Routes Added

```php
// Import preview route
Route::post('import-preview', [MenuController::class, 'importPreview'])->name('import-preview');
```

## Error Handling

### Validation Errors

```php
// Required field validation
if (empty($menuItem['name'])) {
    $errors[] = "$menuPath: 'name' field is required";
}

// JSON format validation
if (json_last_error() !== JSON_ERROR_NONE) {
    return 'Invalid JSON format in the uploaded file.';
}
```

### Import Errors

```php
try {
    DB::beginTransaction();
    // Import process
    DB::commit();
} catch (\Exception $e) {
    DB::rollBack();
    Log::error('Menu import failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    return ['success' => false, 'error' => $e->getMessage()];
}
```

## Logging

Comprehensive logging untuk debugging:

```php
Log::info('Menu import started', [
    'format' => $isLegacyFormat ? 'legacy' : 'current',
    'location' => $location,
    'menu_count' => count($menuConfig)
]);

Log::info('Legacy menu created', [
    'new_id' => $menu->id,
    'legacy_id' => $menuItem['id'] ?? 'unknown',
    'name' => $menu->name
]);
```

## Performance Considerations

1. **Batch Processing**: Import dilakukan dalam transaction tunggal
2. **Memory Efficient**: Streaming processing untuk file besar
3. **Database Optimization**: Bulk operations untuk roles/permissions
4. **Caching**: Clear cache setelah import selesai

## Security

1. **File Validation**: Hanya file JSON yang diizinkan
2. **Size Limits**: File size limits untuk mencegah abuse
3. **Permission Check**: Hanya SuperAdmin yang bisa import
4. **SQL Injection Prevention**: Menggunakan Eloquent ORM

## Future Enhancements

1. **Incremental Import**: Import hanya menu yang berubah
2. **Conflict Resolution**: Handle konflik nama menu
3. **Rollback Capability**: Rollback ke state sebelum import
4. **Import History**: Track history import dengan metadata

## Conclusion

Fitur legacy menu import telah berhasil diimplementasikan dengan:

✅ **Full Backward Compatibility** - Backup lama tetap bisa di-import  
✅ **Auto Format Detection** - Deteksi otomatis format legacy/current  
✅ **Production Ready** - Error handling dan logging lengkap  
✅ **Preview Capability** - Preview sebelum import  
✅ **Comprehensive Validation** - Validasi struktur dan data  
✅ **Future Proof** - Extensible untuk format baru

System sekarang dapat menghandle semua backup menu lama dan baru tanpa perlu modifikasi manual.

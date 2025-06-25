# Configuration Management System

**Tanggal:** 23 Juni 2025  
**Versi:** 1.0  
**Status:** ✅ IMPLEMENTED

## Overview

Sistem manajemen konfigurasi yang memisahkan pengaturan yang dapat dimodifikasi oleh user dari pengaturan yang hanya boleh dimodifikasi oleh developer/sistem. Sistem ini memastikan keamanan aplikasi dan mencegah user mengubah pengaturan kritis yang dapat merusak fungsionalitas.

## Architecture

### 1. Configuration Levels

```
┌─────────────────────────────────────────────────────────┐
│                   SYSTEM LEVEL                          │
│  (Developer/System Admin Only - Requires Deployment)    │
├─────────────────────────────────────────────────────────┤
│                   COMPANY LEVEL                         │
│     (Company Admin - Runtime Configuration)             │
├─────────────────────────────────────────────────────────┤
│                    USER LEVEL                           │
│        (End User - Operational Settings)                │
└─────────────────────────────────────────────────────────┘
```

### 2. Core Components

#### A. CompanyConfig.php

-   `getDefaultLivestockConfig()` - Base configuration dengan semua settings
-   `getUserConfigurableSettings()` - Settings yang boleh diubah user
-   `getDeveloperOnlySettings()` - Settings yang protected untuk developer
-   `getConfigMetadata()` - Metadata tentang levels dan validasi

#### B. ConfigurationService.php

-   `getMergedConfig()` - Merge config dengan aman
-   `updateCompanyConfig()` - Update config dengan validasi
-   `isPathUserEditable()` - Check apakah path boleh diubah user
-   `isPathDeveloperOnly()` - Check apakah path protected

## User-Configurable Settings

### Livestock Configuration

#### ✅ **Method Defaults (User dapat memilih)**

```php
'user_editable_paths' => [
    'recording_method.batch_settings.depletion_method_default',
    'recording_method.batch_settings.mutation_method_default',
    'recording_method.batch_settings.feed_usage_method_default',
]

'user_editable_values' => [
    'depletion_method_default' => ['fifo', 'manual'], // Hanya ready methods
    'mutation_method_default' => ['fifo'], // Hanya ready methods
    'feed_usage_method_default' => ['fifo', 'manual'], // Hanya ready methods
]
```

#### ✅ **Feature Toggles (User dapat enable/disable)**

```php
'lifecycle_management.enabled',
'health_management.vaccination_tracking.enabled',
'health_management.disease_tracking.enabled',
'performance_metrics.enabled',
'cost_tracking.enabled',
'reporting.enabled',
```

#### ✅ **Validation Rules (User dapat customize)**

```php
'validation_rules.require_farm',
'validation_rules.require_coop',
'validation_rules.min_quantity',
'validation_rules.max_quantity',
```

#### ✅ **Workflow Settings (User dapat customize)**

```php
'feed_usage.methods.manual.workflow_settings.enable_preview_step',
'feed_usage.methods.manual.workflow_settings.require_confirmation',
'feed_usage.methods.manual.input_restrictions.allow_same_day_repeated_input',
'feed_usage.methods.manual.input_restrictions.max_usage_per_day_per_batch',
```

## Developer-Only Settings

### 🔒 **Core Method Configuration (PROTECTED)**

```php
'protected_paths' => [
    // Method availability dan status
    'recording_method.batch_settings.depletion_methods.*.enabled',
    'recording_method.batch_settings.depletion_methods.*.status',
    'recording_method.batch_settings.depletion_methods.*.auto_select',

    // Algorithm settings
    'recording_method.batch_settings.depletion_methods.*.batch_selection_criteria',
    'recording_method.batch_settings.depletion_methods.*.quantity_distribution',
    'recording_method.batch_settings.depletion_methods.*.performance_optimization',
    'recording_method.batch_settings.depletion_methods.*.audit_trail',

    // Security settings
    'feed_usage.methods.manual.input_restrictions.prevent_duplicate_stocks',
    'feed_usage.methods.manual.input_restrictions.require_stock_availability_check',
    'feed_usage.methods.manual.edit_mode_settings',

    // Core validation
    'validation_rules.enabled',
    'recording_method.type',
    'recording_method.allow_multiple_batches',
]
```

## Implementation Example

### 1. Safe Config Loading

```php
// OLD: Unsafe merge
$config = array_merge($defaultConfig, $companyConfig);

// NEW: Safe merge via ConfigurationService
$config = ConfigurationService::getMergedConfig($companyId, 'livestock');
```

### 2. Safe Config Updates

```php
// Validate dan update config dengan aman
$success = ConfigurationService::updateCompanyConfig(
    $companyId,
    'livestock',
    'recording_method.batch_settings.depletion_method_default',
    'manual',
    $userId
);
```

### 3. Permission Checking

```php
// Check apakah user boleh edit setting tertentu
$canEdit = ConfigurationService::isPathUserEditable('livestock', $path);
$isProtected = ConfigurationService::isPathDeveloperOnly('livestock', $path);
```

## Security Features

### 1. Path Validation

-   Wildcard pattern matching untuk protected paths
-   Whitelist approach untuk user-editable paths
-   Automatic rejection untuk paths yang tidak terdaftar

### 2. Value Validation

-   Type checking (boolean, numeric, string)
-   Range validation untuk numeric values
-   Allowed values list untuk specific settings
-   Length limits untuk string values

### 3. Audit Trail

-   Automatic backup sebelum config changes
-   Comprehensive logging untuk semua config operations
-   User tracking untuk accountability
-   Timestamp dan snapshot untuk rollback

### 4. Rollback Capability

```php
// Config backup otomatis
Log::info('Config backup created', [
    'company_id' => $companyId,
    'backup_timestamp' => now()->toDateTimeString(),
    'user_id' => $userId,
    'config_snapshot' => $config
]);
```

## Usage Examples

### 1. User Interface Implementation

```php
// Di Livewire component
public function updateMethodDefault($methodType, $newValue)
{
    $path = "recording_method.batch_settings.{$methodType}_method_default";

    $success = ConfigurationService::updateCompanyConfig(
        $this->company_id,
        'livestock',
        $path,
        $newValue,
        auth()->id()
    );

    if ($success) {
        $this->dispatch('success', 'Pengaturan berhasil disimpan');
        $this->loadConfig(); // Reload config
    } else {
        $this->dispatch('error', 'Gagal menyimpan pengaturan');
    }
}
```

### 2. Available Options for User

```php
// Get methods yang boleh dipilih user
$availableMethods = ConfigurationService::getAvailableMethodsForUser('depletion');
// Returns: ['fifo' => [...], 'manual' => [...]] (hanya ready methods)
```

### 3. UI Permission Handling

```php
// Di Blade template
@if(ConfigurationService::isPathUserEditable('livestock', $configPath))
    <select wire:model="selectedValue">
        <!-- User dapat mengubah -->
    </select>
@else
    <select disabled>
        <!-- Read-only untuk user -->
    </select>
    <small class="text-muted">Pengaturan ini dikontrol oleh sistem</small>
@endif
```

## Benefits

### 1. Security

-   ✅ User tidak dapat mengubah core algorithm settings
-   ✅ Method availability tetap dikontrol developer
-   ✅ Performance dan security settings protected
-   ✅ Audit trail untuk semua perubahan

### 2. Flexibility

-   ✅ User dapat customize operational settings
-   ✅ Company-specific configuration tanpa deployment
-   ✅ Runtime configuration changes
-   ✅ Granular permission control

### 3. Maintainability

-   ✅ Clear separation of concerns
-   ✅ Centralized configuration management
-   ✅ Easy to add new user-configurable settings
-   ✅ Automatic validation dan backup

### 4. User Experience

-   ✅ User dapat mengatur sesuai kebutuhan operasional
-   ✅ Clear indication mana yang bisa diubah
-   ✅ Immediate feedback untuk invalid changes
-   ✅ Rollback capability jika ada masalah

## Future Enhancements

### 1. Role-Based Configuration

```php
// Different config levels berdasarkan user role
'company_admin' => ['can_edit' => ['all_user_configurable']],
'supervisor' => ['can_edit' => ['operational_settings_only']],
'operator' => ['can_edit' => ['basic_preferences_only']],
```

### 2. Configuration Templates

```php
// Pre-defined configuration templates
'templates' => [
    'small_farm' => [...],
    'large_commercial' => [...],
    'organic_farm' => [...],
]
```

### 3. Dynamic Method Enablement

```php
// Allow company admin to enable/disable methods
// (with developer approval workflow)
'pending_method_changes' => [
    'enable_lifo_method' => ['requested_by' => 123, 'status' => 'pending'],
]
```

### 4. Configuration Versioning

```php
// Track configuration versions
'config_version' => '2.1',
'migration_required' => false,
'compatibility_check' => 'passed',
```

---

**Status:** ✅ Production Ready  
**Next Steps:** User testing dan feedback collection

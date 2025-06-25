# Manual Feed Usage - Configurable Edit Strategies

**Tanggal:** 20 Desember 2024  
**Waktu:** 14:30 WIB  
**Developer:** AI Assistant  
**Jenis:** Feature Enhancement

## Overview

Implementasi sistem konfigurasi untuk menentukan strategi edit pada manual feed usage, memberikan fleksibilitas antara update langsung atau delete-recreate dengan opsi soft/hard delete.

## Problem Statement

User membutuhkan kontrol atas bagaimana data existing akan diproses saat edit:

-   **Update Strategy**: Memodifikasi data existing tanpa menambah usage count
-   **Delete-Recreate Strategy**: Menghapus data lama dan membuat baru (menambah usage count jika soft delete)

## Solution Implementation

### 1. Configuration Structure

#### A. Edit Mode Settings dalam CompanyConfig

```php
'edit_mode_settings' => [
    // Strategi utama: 'update' atau 'delete_recreate'
    'edit_strategy' => 'update',

    // Jenis delete untuk delete_recreate: 'soft' atau 'hard'
    'delete_strategy' => 'soft',

    // Backup data sebelum edit
    'create_backup_before_edit' => true,

    // Track operasi edit untuk audit
    'track_edit_operations' => true,

    // Pengaturan soft delete
    'soft_delete_settings' => [
        'increment_usage_count' => true,
        'default_delete_reason' => 'edited',
        'preserve_original_data' => true,
    ],

    // Pengaturan hard delete
    'hard_delete_settings' => [
        'validate_references' => true,
        'restore_stock_quantities' => true,
        'update_livestock_totals' => true,
    ],

    // Pengaturan update strategy
    'update_settings' => [
        'track_field_changes' => true,
        'validate_business_rules' => true,
        'update_timestamps' => true,
    ],

    // Notifikasi
    'notifications' => [
        'notify_on_edit' => true,
        'notify_on_delete_recreate' => true,
        'include_change_summary' => true,
    ],
]
```

#### B. Configuration Access Method

```php
public static function getManualFeedUsageEditModeSettings(): array
{
    $config = self::getManualFeedUsageConfig();
    return $config['edit_mode_settings'] ?? [/* default values */];
}
```

### 2. Service Layer Enhancement

#### A. Main Update Method

```php
public function updateExistingFeedUsage(array $usageData): array
{
    // Get configuration
    $editSettings = $this->getFeedUsageEditModeSettings();
    $editStrategy = $editSettings['edit_strategy'] ?? 'update';

    // Create backup if configured
    if ($editSettings['create_backup_before_edit'] ?? true) {
        $this->createEditBackup($usageData['existing_usage_ids']);
    }

    // Execute strategy
    switch ($editStrategy) {
        case 'delete_recreate':
            return $this->updateViaDeleteRecreate($usageData, $editSettings);
        case 'update':
        default:
            return $this->updateViaDirectUpdate($usageData, $editSettings);
    }
}
```

#### B. Strategy Implementation Methods

**1. Direct Update Strategy**

-   Mempertahankan record existing
-   Update field-field yang berubah
-   Replace details dengan data baru
-   Tidak menambah usage count

**2. Delete-Recreate Strategy**

-   Hapus record existing (soft/hard delete)
-   Buat record baru
-   Menambah usage count jika soft delete
-   Preserve audit trail

### 3. Feature Details

#### A. Backup System

```php
private function createEditBackup(array $usageIds): void
{
    $existingUsages = FeedUsage::with('details')->whereIn('id', $usageIds)->get();

    foreach ($existingUsages as $usage) {
        $backupData = [
            'original_data' => $usage->toArray(),
            'backup_created_at' => now()->toISOString(),
            'backup_reason' => 'pre_edit_backup'
        ];

        $usage->update([
            'metadata' => array_merge($usage->metadata ?? [], ['edit_backup' => $backupData])
        ]);
    }
}
```

#### B. Soft Delete Implementation

```php
private function performSoftDelete(array $usageIds, array $editSettings): void
{
    $softDeleteSettings = $editSettings['soft_delete_settings'] ?? [];

    // Restore stock quantities
    // Update livestock totals
    // Add soft delete metadata
    // Perform Laravel soft delete
}
```

#### C. Hard Delete Implementation

```php
private function performHardDelete(array $usageIds, array $editSettings): void
{
    $hardDeleteSettings = $editSettings['hard_delete_settings'] ?? [];

    // Validate references
    // Restore stock quantities
    // Update livestock totals
    // Force delete records
}
```

#### D. Field Change Tracking

```php
private function trackFieldChanges($existingUsages, array $newUsageData): array
{
    $changes = [];
    $mainUsage = $existingUsages->first();

    // Track main field changes
    $fieldsToTrack = ['usage_date', 'purpose', 'notes', 'livestock_batch_id'];

    foreach ($fieldsToTrack as $field) {
        $oldValue = $mainUsage->{$field};
        $newValue = $newUsageData[$field] ?? null;

        if ($oldValue != $newValue) {
            $changes[$field] = ['old' => $oldValue, 'new' => $newValue];
        }
    }

    // Track stock changes
    // Compare old vs new stock selections

    return $changes;
}
```

## Configuration Options Explained

### Edit Strategy Options

#### 1. Update Strategy (`'edit_strategy' => 'update'`)

-   **Behavior**: Memodifikasi data existing langsung
-   **Usage Count**: Tidak bertambah
-   **Performance**: Lebih cepat
-   **Audit Trail**: Field changes tracking
-   **Use Case**: Koreksi data, perubahan minor

#### 2. Delete-Recreate Strategy (`'edit_strategy' => 'delete_recreate'`)

-   **Behavior**: Hapus data lama, buat data baru
-   **Usage Count**: Bertambah (jika soft delete)
-   **Performance**: Lebih lambat
-   **Audit Trail**: Complete operation history
-   **Use Case**: Major changes, compliance requirements

### Delete Strategy Options (untuk Delete-Recreate)

#### 1. Soft Delete (`'delete_strategy' => 'soft'`)

-   **Behavior**: Laravel soft delete (deleted_at)
-   **Usage Count**: Bertambah di database
-   **Data Recovery**: Bisa di-restore
-   **Audit**: Complete audit trail
-   **Storage**: Membutuhkan lebih banyak storage

#### 2. Hard Delete (`'delete_strategy' => 'hard'`)

-   **Behavior**: Permanent delete dari database
-   **Usage Count**: Tidak bertambah
-   **Data Recovery**: Tidak bisa di-restore
-   **Audit**: Limited audit trail
-   **Storage**: Lebih efisien

## Usage Examples

### Configuration untuk Production

```php
// Untuk environment yang membutuhkan audit trail lengkap
'edit_mode_settings' => [
    'edit_strategy' => 'delete_recreate',
    'delete_strategy' => 'soft',
    'create_backup_before_edit' => true,
    'track_edit_operations' => true,
    'soft_delete_settings' => [
        'increment_usage_count' => true,
        'preserve_original_data' => true,
    ],
]
```

### Configuration untuk Development

```php
// Untuk environment development yang membutuhkan performa
'edit_mode_settings' => [
    'edit_strategy' => 'update',
    'create_backup_before_edit' => false,
    'track_edit_operations' => false,
    'update_settings' => [
        'track_field_changes' => false,
        'validate_business_rules' => true,
    ],
]
```

## Implementation Benefits

### 1. Flexibility

-   Admin dapat mengatur strategi sesuai kebutuhan bisnis
-   Berbeda environment bisa pakai strategi berbeda
-   Mudah switch tanpa code changes

### 2. Audit & Compliance

-   Complete audit trail untuk compliance
-   Backup system untuk data recovery
-   Field-level change tracking

### 3. Performance Control

-   Update strategy untuk performa tinggi
-   Delete-recreate untuk data integrity
-   Configurable backup dan tracking

### 4. Data Integrity

-   Proper stock quantity restoration
-   Livestock totals recalculation
-   Business rule validation

## Technical Implementation Details

### Database Impact

#### Update Strategy

-   **Records**: Existing records modified
-   **Usage Count**: Unchanged
-   **Storage**: Minimal additional storage

#### Delete-Recreate + Soft Delete

-   **Records**: Old records soft deleted, new records created
-   **Usage Count**: Increased (shows edit activity)
-   **Storage**: Doubled storage requirement

#### Delete-Recreate + Hard Delete

-   **Records**: Old records permanently deleted, new records created
-   **Usage Count**: Unchanged
-   **Storage**: Same as original

### Logging & Monitoring

Setiap strategi menghasilkan log yang berbeda:

```php
// Update Strategy
Log::info('🔄 Using direct update strategy', [
    'livestock_id' => $livestock->id,
    'field_changes' => $fieldChanges
]);

// Delete-Recreate Strategy
Log::info('🗑️ Using delete-recreate strategy', [
    'delete_strategy' => $deleteStrategy,
    'livestock_id' => $livestock->id
]);
```

## Testing Scenarios

### Test Case 1: Update Strategy

1. Edit existing feed usage
2. Verify data updated in-place
3. Verify usage count unchanged
4. Verify field changes tracked

### Test Case 2: Delete-Recreate + Soft Delete

1. Edit existing feed usage
2. Verify old record soft deleted
3. Verify new record created
4. Verify usage count increased
5. Verify backup created

### Test Case 3: Delete-Recreate + Hard Delete

1. Edit existing feed usage
2. Verify old record permanently deleted
3. Verify new record created
4. Verify usage count unchanged
5. Verify stock quantities properly restored

## Future Enhancements

### 1. Advanced Notifications

-   Email notifications untuk major edits
-   Slack integration untuk audit alerts
-   Dashboard untuk edit statistics

### 2. Batch Operations

-   Bulk edit dengan configurable strategy
-   Batch rollback functionality
-   Mass data migration tools

### 3. Advanced Audit

-   Visual diff untuk field changes
-   Edit approval workflow
-   Automated compliance reporting

## Configuration Management

### Environment-Specific Configs

```php
// config/company.php
'environments' => [
    'production' => [
        'edit_strategy' => 'delete_recreate',
        'delete_strategy' => 'soft',
    ],
    'staging' => [
        'edit_strategy' => 'update',
        'track_edit_operations' => true,
    ],
    'development' => [
        'edit_strategy' => 'update',
        'create_backup_before_edit' => false,
    ],
]
```

## Conclusion

Implementasi configurable edit strategies memberikan:

-   **Fleksibilitas** dalam pengelolaan data
-   **Kontrol penuh** atas audit trail
-   **Optimasi performa** sesuai kebutuhan
-   **Compliance support** untuk regulasi
-   **Future-proof architecture** untuk enhancement

Fitur ini memungkinkan sistem beradaptasi dengan berbagai requirement bisnis tanpa perlu modifikasi code, hanya dengan mengubah konfigurasi.

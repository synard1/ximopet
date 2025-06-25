# Manual Livestock Mutation Implementation Guide

**Tanggal:** 23 Januari 2025  
**Versi:** 1.0  
**Status:** Production Ready

## Overview

Fitur Manual Livestock Mutation adalah sistem komprehensif untuk mengelola mutasi ternak secara manual dengan dukungan batch selection, edit mode, validasi real-time, dan audit trail yang lengkap. Fitur ini dibangun dengan arsitektur yang modular, reusable, dan future-proof.

## Key Features

### 1. Manual Batch Selection

-   **Seleksi Batch Manual**: User dapat memilih batch tertentu dengan jumlah spesifik
-   **Real-time Availability**: Menampilkan ketersediaan batch secara real-time
-   **Batch Details**: Informasi lengkap termasuk umur, utilisasi, dan ketersediaan
-   **Multiple Batch Support**: Mendukung seleksi multiple batch dalam satu mutasi

### 2. Edit Mode Functionality

-   **Automatic Detection**: Deteksi otomatis data existing saat memilih tanggal
-   **Data Loading**: Load otomatis data existing untuk editing
-   **Configurable Strategy**: Dua strategi update (UPDATE_EXISTING / DELETE_AND_CREATE)
-   **Cancel Support**: Kemampuan untuk membatalkan edit mode

### 3. Validation & Error Handling

-   **Real-time Validation**: Validasi real-time untuk semua input
-   **Batch Availability**: Validasi ketersediaan batch sebelum processing
-   **Business Rules**: Validasi business rules sesuai konfigurasi company
-   **Comprehensive Error Messages**: Pesan error yang informatif dan actionable

### 4. Configuration-Based System

-   **Company Config**: Konfigurasi per company untuk flexibilitas
-   **Validation Rules**: Aturan validasi yang dapat dikonfigurasi
-   **Workflow Settings**: Pengaturan workflow sesuai kebutuhan bisnis
-   **Edit Mode Settings**: Konfigurasi edit mode behavior

## Technical Architecture

### 1. Service Layer

#### LivestockMutationService

```php
app/Services/Livestock/LivestockMutationService.php
```

**Key Methods:**

-   `processMutation(array $mutationData)`: Main processing method
-   `processManualBatchMutation()`: Handle manual batch mutations
-   `getAvailableBatchesForMutation()`: Get available batches for selection
-   `previewManualBatchMutation()`: Generate mutation preview
-   `mutateBatch()`: Process individual batch mutation

**Features:**

-   Transaction safety dengan DB::transaction()
-   Comprehensive logging untuk debugging
-   Error handling yang robust
-   Support untuk multiple mutation methods (manual, FIFO, LIFO)
-   Real-time quantity calculations
-   Audit trail lengkap

### 2. Model Layer

#### LivestockMutation Model

```php
app/Models/LivestockMutation.php
```

**Properties:**

-   `source_livestock_id`: ID ternak sumber
-   `destination_livestock_id`: ID ternak tujuan (nullable)
-   `tanggal`: Tanggal mutasi
-   `jumlah`: Jumlah yang dimutasi
-   `jenis`: Jenis mutasi (internal, external, farm_transfer, etc.)
-   `direction`: Arah mutasi (in, out)
-   `data`: JSON data untuk informasi tambahan
-   `metadata`: JSON metadata untuk audit trail

**Relationships:**

-   `sourceLivestock()`: Relasi ke ternak sumber
-   `destinationLivestock()`: Relasi ke ternak tujuan
-   `company()`: Relasi ke company
-   `creator()`: Relasi ke user creator

**Scopes:**

-   `scopeOutgoing()`: Filter mutasi keluar
-   `scopeIncoming()`: Filter mutasi masuk
-   `scopeOfType()`: Filter berdasarkan jenis
-   `scopeForLivestock()`: Filter untuk livestock tertentu
-   `scopeDateRange()`: Filter berdasarkan range tanggal

### 3. Component Layer

#### ManualLivestockMutation Livewire Component

```php
app/Livewire/Livestock/Mutation/ManualLivestockMutation.php
```

**Key Properties:**

-   Basic mutation properties (date, source, destination, type, direction)
-   Manual batch selection (`manualBatches`, `availableBatches`)
-   Edit mode properties (`isEditing`, `existingMutationIds`)
-   UI state properties (`showModal`, `isLoading`, `showPreview`)
-   Configuration properties (`config`, `validationRules`, `workflowSettings`)

**Key Methods:**

-   `openModal()`: Open modal with optional edit data
-   `loadEditMode()`: Load existing data for editing
-   `addBatch()` / `removeBatch()`: Manage batch selection
-   `showPreview()`: Generate mutation preview
-   `processMutation()`: Process the mutation
-   `checkForExistingMutations()`: Auto-detect edit mode

### 4. Configuration Layer

#### CompanyConfig Extensions

```php
app/Config/CompanyConfig.php
```

**New Methods:**

-   `getManualMutationConfig()`: Get manual mutation configuration
-   `getManualMutationHistorySettings()`: Get history/edit mode settings
-   `getManualMutationValidationRules()`: Get validation rules
-   `getManualMutationWorkflowSettings()`: Get workflow settings
-   `getManualMutationBatchSettings()`: Get batch selection settings

### 5. Database Layer

#### Migration: livestock_mutations Table

```php
database/migrations/2025_01_23_000001_create_livestock_mutations_table.php
```

**Schema:**

-   Primary key: UUID
-   Foreign keys: company_id, source_livestock_id, destination_livestock_id
-   Data fields: tanggal, jumlah, jenis, direction
-   JSON fields: data, metadata
-   Audit fields: created_by, updated_by, timestamps, soft deletes
-   Comprehensive indexes untuk performance

## Implementation Steps

### 1. Database Setup

```bash
# Run migration
php artisan migrate

# Verify table structure
php artisan tinker
>>> Schema::hasTable('livestock_mutations')
>>> Schema::getColumnListing('livestock_mutations')
```

### 2. Model Relationships

Update `Livestock` model untuk include mutation relationships:

```php
// Add to Livestock model
public function outgoingMutations()
{
    return $this->hasMany(LivestockMutation::class, 'source_livestock_id');
}

public function incomingMutations()
{
    return $this->hasMany(LivestockMutation::class, 'destination_livestock_id');
}
```

### 3. Service Integration

```php
// Inject service in controller or component
use App\Services\Livestock\LivestockMutationService;

public function __construct(LivestockMutationService $mutationService)
{
    $this->mutationService = $mutationService;
}
```

### 4. Component Usage

#### Basic Usage

```php
// Open modal for new mutation
$this->dispatch('openMutationModal', ['livestockId' => $livestockId]);

// Open modal for editing
$this->dispatch('openMutationModal', [
    'livestockId' => $livestockId,
    'editData' => ['mutation_ids' => $mutationIds]
]);
```

#### JavaScript Integration

```javascript
// Open modal from JavaScript
openLivestockMutationModal("livestock-id-here");

// Open for editing
openLivestockMutationModal("livestock-id-here", {
    mutation_ids: ["mutation-id-1", "mutation-id-2"],
});
```

### 5. Configuration Setup

```php
// Set company configuration
$company = auth()->user()->company;
$config = $company->config ?? [];

$config['livestock']['manual_mutation'] = [
    'enabled' => true,
    'default_method' => 'manual',
    'validation_rules' => [
        'require_destination' => true,
        'min_quantity' => 1,
        'validate_batch_availability' => true
    ],
    'history_settings' => [
        'history_enabled' => false, // or true for delete_and_create
        'strategy' => 'update_existing'
    ]
];

$company->update(['config' => $config]);
```

## Usage Guide

### 1. Creating New Mutation

#### Step 1: Open Modal

```php
// From Livewire component
$this->dispatch('openMutationModal', ['livestockId' => $livestockId]);
```

#### Step 2: Fill Basic Information

-   **Tanggal Mutasi**: Pilih tanggal mutasi
-   **Ternak Sumber**: Pilih ternak yang akan dimutasi
-   **Jenis Mutasi**: Pilih jenis (internal, external, farm_transfer, dll.)
-   **Arah Mutasi**: Pilih arah (keluar/masuk)
-   **Ternak Tujuan**: Pilih ternak tujuan (untuk mutasi keluar)

#### Step 3: Select Batches

-   Pilih batch dari dropdown yang tersedia
-   Masukkan jumlah yang akan dimutasi
-   Tambahkan catatan jika diperlukan
-   Klik "Tambah" untuk menambahkan ke seleksi
-   Ulangi untuk batch lainnya jika diperlukan

#### Step 4: Preview & Process

-   Klik "Preview" untuk melihat ringkasan mutasi
-   Verifikasi semua data sudah benar
-   Klik "Proses Mutasi" untuk memproses

### 2. Editing Existing Mutation

#### Auto-Detection

Sistem akan otomatis mendeteksi data existing saat:

-   Memilih ternak sumber yang memiliki data mutasi
-   Pada tanggal yang sudah ada data mutasi
-   Edit mode akan otomatis diaktifkan

#### Manual Edit

```php
// Open with edit data
$this->dispatch('openMutationModal', [
    'livestockId' => $livestockId,
    'editData' => ['mutation_ids' => $mutationIds]
]);
```

#### Edit Process

-   Data existing akan otomatis dimuat
-   Banner edit mode akan ditampilkan
-   Modifikasi data sesuai kebutuhan
-   Sistem akan menggunakan strategy sesuai konfigurasi:
    -   **UPDATE_EXISTING**: Update record existing
    -   **DELETE_AND_CREATE**: Hapus record lama, buat record baru

### 3. Configuration Management

#### Company-Level Configuration

```php
use App\Config\CompanyConfig;

// Get current configuration
$config = CompanyConfig::getManualMutationConfig();

// Get validation rules
$rules = CompanyConfig::getManualMutationValidationRules();

// Get workflow settings
$workflow = CompanyConfig::getManualMutationWorkflowSettings();
```

#### User-Configurable Settings

-   Validation rules (require_destination, min_quantity, dll.)
-   Workflow settings (require_confirmation, show_preview, dll.)
-   Edit mode behavior (auto_load_existing, show_edit_banner, dll.)

## API Reference

### LivestockMutationService Methods

#### processMutation(array $mutationData)

Process livestock mutation with comprehensive validation and error handling.

**Parameters:**

```php
$mutationData = [
    'source_livestock_id' => 'uuid',
    'destination_livestock_id' => 'uuid', // optional for incoming
    'date' => 'Y-m-d',
    'type' => 'internal|external|farm_transfer|location_transfer|emergency_transfer',
    'direction' => 'in|out',
    'reason' => 'string', // optional
    'notes' => 'string', // optional
    'manual_batches' => [
        [
            'batch_id' => 'uuid',
            'quantity' => 'integer',
            'note' => 'string' // optional
        ]
    ],
    'mutation_method' => 'manual',
    'is_editing' => 'boolean', // optional
    'existing_mutation_ids' => ['uuid'] // optional for edit mode
];
```

**Returns:**

```php
[
    'success' => true,
    'source_livestock_id' => 'uuid',
    'total_mutated' => 100,
    'processed_batches' => [...],
    'mutation_method' => 'manual',
    'mutation_type' => 'internal',
    'mutation_direction' => 'out',
    'edit_mode' => false,
    'update_strategy' => 'CREATE_NEW',
    'message' => 'Manual batch mutation out berhasil diproses'
]
```

#### getAvailableBatchesForMutation(string $livestockId)

Get available batches for manual selection.

**Returns:**

```php
[
    'livestock_id' => 'uuid',
    'livestock_name' => 'string',
    'total_batches' => 5,
    'batches' => [
        [
            'batch_id' => 'uuid',
            'batch_name' => 'string',
            'start_date' => 'date',
            'age_days' => 30,
            'initial_quantity' => 1000,
            'used_quantity' => [...],
            'available_quantity' => 800,
            'utilization_rate' => 20.0,
            'status' => 'active'
        ]
    ]
]
```

#### previewManualBatchMutation(array $mutationData)

Generate preview for manual batch mutation.

**Returns:**

```php
[
    'method' => 'manual',
    'livestock_id' => 'uuid',
    'livestock_name' => 'string',
    'total_quantity' => 150,
    'can_fulfill' => true,
    'batches_count' => 2,
    'batches_preview' => [...],
    'errors' => [],
    'validation_passed' => true
]
```

### LivestockMutation Model Methods

#### createMutation(array $data)

Create mutation record with validation.

#### getLivestockMutationSummary(string $livestockId)

Get mutation summary for specific livestock.

### Livewire Component Events

#### Listening Events

-   `openMutationModal`: Open modal with optional parameters
-   `closeMutationModal`: Close modal
-   `refreshMutationData`: Refresh component data

#### Dispatched Events

-   `show-livestock-mutation`: Show modal
-   `close-livestock-mutation`: Close modal
-   `mutation-completed`: Mutation processing completed
-   `edit-mode-enabled`: Edit mode activated
-   `edit-mode-cancelled`: Edit mode cancelled

## Configuration Options

### Manual Mutation Configuration

```php
[
    'enabled' => true,
    'default_method' => 'manual',
    'supported_methods' => ['manual', 'fifo', 'lifo'],
    'validation_rules' => [
        'require_destination' => true,
        'require_reason' => false,
        'min_quantity' => 1,
        'max_quantity_percentage' => 100,
        'validate_batch_availability' => true,
        'allow_partial_mutation' => true
    ],
    'batch_settings' => [
        'track_age' => true,
        'show_batch_details' => true,
        'show_utilization_rate' => true,
        'auto_assign_batch' => true,
        'require_batch_selection' => false
    ],
    'workflow_settings' => [
        'require_confirmation' => true,
        'show_preview' => true,
        'auto_close_modal' => true,
        'notification_enabled' => true
    ],
    'edit_mode_settings' => [
        'enabled' => true,
        'auto_load_existing' => true,
        'show_edit_banner' => true,
        'allow_cancel' => true
    ],
    'history_settings' => [
        'history_enabled' => false,
        'strategy' => 'update_existing',
        'audit_trail' => true,
        'backup_before_edit' => true,
        'max_backups' => 10
    ]
]
```

## Error Handling

### Validation Errors

-   **Missing Required Fields**: Validasi field yang wajib diisi
-   **Invalid Livestock Selection**: Validasi ternak sumber dan tujuan
-   **Batch Availability**: Validasi ketersediaan batch
-   **Quantity Validation**: Validasi jumlah minimum dan maksimum
-   **Business Rules**: Validasi aturan bisnis company

### Processing Errors

-   **Database Transaction Errors**: Error saat processing database
-   **Batch Processing Errors**: Error saat processing individual batch
-   **Quantity Calculation Errors**: Error saat kalkulasi quantity
-   **Audit Trail Errors**: Error saat create audit trail

### Error Response Format

```php
[
    'success' => false,
    'error' => 'Error message',
    'details' => [...], // Additional error details
    'validation_errors' => [...] // Validation specific errors
]
```

## Logging & Debugging

### Log Levels

-   **INFO**: Normal operations, successful processing
-   **WARNING**: Non-critical issues, fallback scenarios
-   **ERROR**: Critical errors, failed operations

### Log Context

```php
Log::info('🔄 Manual batch mutation process started', [
    'source_livestock_id' => $sourceLivestock->id,
    'manual_batches' => count($mutationData['manual_batches']),
    'mutation_type' => $mutationData['type'],
    'mode' => $isEditMode ? 'UPDATE' : 'CREATE'
]);
```

### Debug Functions

```javascript
// Global debug functions
openLivestockMutationModal(livestockId, editData);
closeLivestockMutationModal();
```

## Performance Considerations

### Database Optimization

-   **Indexes**: Comprehensive indexes untuk query performance
-   **Foreign Keys**: Proper foreign key constraints
-   **Soft Deletes**: Soft delete untuk audit trail
-   **JSON Fields**: Efficient JSON storage untuk flexible data

### Query Optimization

-   **Eager Loading**: Load relationships efficiently
-   **Batch Processing**: Process multiple batches efficiently
-   **Transaction Safety**: Use database transactions
-   **Real-time Calculations**: Efficient quantity calculations

### Caching Strategy

-   **Configuration Caching**: Cache company configuration
-   **Batch Data Caching**: Cache batch availability data
-   **Livestock Data Caching**: Cache livestock information

## Security Considerations

### Authorization

-   **Company Scope**: Data terisolasi per company
-   **User Permissions**: Check user permissions
-   **Livestock Access**: Validate livestock access rights

### Data Validation

-   **Input Sanitization**: Sanitize all user inputs
-   **SQL Injection Prevention**: Use parameter binding
-   **XSS Prevention**: Escape output data
-   **CSRF Protection**: Laravel CSRF protection

### Audit Trail

-   **User Tracking**: Track user who created/updated
-   **Change History**: Track all changes
-   **Data Integrity**: Ensure data consistency
-   **Backup Strategy**: Backup before major changes

## Testing Strategy

### Unit Tests

-   Service layer methods
-   Model relationships and methods
-   Configuration methods
-   Validation logic

### Integration Tests

-   End-to-end mutation processing
-   Edit mode functionality
-   Database transactions
-   Error handling scenarios

### Feature Tests

-   Livewire component interactions
-   Modal functionality
-   Form validation
-   User interface flows

## Future Enhancements

### Planned Features

1. **FIFO/LIFO Mutation Methods**: Automated batch selection
2. **Bulk Mutation Processing**: Process multiple mutations at once
3. **Advanced Reporting**: Comprehensive mutation reports
4. **API Endpoints**: REST API untuk external integration
5. **Mobile Support**: Mobile-optimized interface
6. **Notification System**: Real-time notifications
7. **Approval Workflow**: Multi-level approval system
8. **Integration**: Integration dengan external systems

### Extensibility Points

-   **Custom Mutation Types**: Add custom mutation types
-   **Custom Validation Rules**: Add custom validation logic
-   **Custom Workflow Steps**: Add custom workflow steps
-   **Custom Reports**: Add custom reporting features

## Troubleshooting

### Common Issues

#### 1. Edit Mode Not Activating

**Symptoms:** Edit mode tidak otomatis aktif saat ada data existing
**Solutions:**

-   Check `edit_mode_settings.enabled` configuration
-   Verify `checkForExistingMutations()` method
-   Check date format dan livestock selection

#### 2. Batch Not Available

**Symptoms:** Batch tidak muncul di dropdown
**Solutions:**

-   Verify batch status = 'active'
-   Check available quantity > 0
-   Verify batch belongs to selected livestock

#### 3. Processing Errors

**Symptoms:** Error saat processing mutation
**Solutions:**

-   Check database connection
-   Verify all required fields
-   Check batch availability
-   Review error logs

#### 4. Performance Issues

**Symptoms:** Slow loading atau processing
**Solutions:**

-   Check database indexes
-   Optimize queries dengan eager loading
-   Review batch data size
-   Check server resources

### Debug Commands

```bash
# Check migration status
php artisan migrate:status

# Verify table structure
php artisan tinker
>>> Schema::getColumnListing('livestock_mutations')

# Check model relationships
>>> $livestock = App\Models\Livestock::first()
>>> $livestock->outgoingMutations
>>> $livestock->incomingMutations

# Test service methods
>>> $service = app(App\Services\Livestock\LivestockMutationService::class)
>>> $service->getAvailableBatchesForMutation('livestock-id')
```

## Conclusion

Manual Livestock Mutation feature provides comprehensive solution untuk mengelola mutasi ternak dengan:

1. **Robust Architecture**: Service-based architecture yang modular dan maintainable
2. **User-Friendly Interface**: Modern UI dengan real-time feedback
3. **Flexible Configuration**: Configuration-driven behavior
4. **Edit Mode Support**: Comprehensive edit mode dengan multiple strategies
5. **Audit Trail**: Complete audit trail untuk compliance
6. **Future-Proof Design**: Extensible untuk future enhancements

Feature ini ready untuk production use dan dapat di-extend sesuai kebutuhan bisnis yang berkembang.

---

**Dokumentasi ini akan terus diupdate seiring dengan pengembangan dan enhancement feature.**

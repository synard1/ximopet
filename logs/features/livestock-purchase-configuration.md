# Livestock Purchase Configuration Feature

## Tanggal: 2025-01-27

## Status: ✅ Completed

## Overview

Fitur konfigurasi Livestock Purchase adalah sistem manajemen pengaturan komprehensif untuk mengatur workflow, validasi, approval, dan business rules dalam sistem pembelian ternak. Fitur ini memungkinkan administrator untuk menyesuaikan sistem sesuai dengan kebutuhan bisnis perusahaan.

## Komponen yang Dibuat

### 1. Configuration Class

**File:** `app/Config/LivestockPurchaseConfig.php`

#### Fitur Utama:

-   **Workflow Management**: Konfigurasi status flow dan transisi
-   **Validation Rules**: Pengaturan field wajib dan business rules
-   **Approval System**: Multi-level approval dengan threshold
-   **Batch Management**: Pengaturan batch creation dan tracking
-   **Cost Tracking**: Metode tracking biaya dan breakdown
-   **Document Management**: Pengaturan dokumen wajib dan validasi
-   **Notification System**: Channel dan trigger notifikasi
-   **Business Rules**: Limit pembelian dan quality rules
-   **Reporting**: Konfigurasi laporan dan export
-   **Integration**: Pengaturan integrasi sistem eksternal

#### Method Utama:

```php
// Get full configuration
LivestockPurchaseConfig::getConfig()

// Get specific section
LivestockPurchaseConfig::getFor('workflow')

// Check if feature enabled
LivestockPurchaseConfig::isEnabled('batch_management')

// Get status configuration
LivestockPurchaseConfig::getStatusConfig('pending')

// Check status transition
LivestockPurchaseConfig::canTransitionTo('draft', 'pending')
```

### 2. Livewire Component

**File:** `app/Livewire/LivestockPurchase/Configuration.php`

#### Fitur Utama:

-   **Configuration Management**: Load, edit, save configuration
-   **Tab-based Interface**: 10 tab untuk berbagai aspek konfigurasi
-   **Real-time Validation**: Validasi konfigurasi secara real-time
-   **Import/Export**: Backup dan restore konfigurasi
-   **Test Configuration**: Validasi konfigurasi sebelum deploy
-   **Change Tracking**: Deteksi perubahan konfigurasi

#### Method Utama:

```php
// Load configuration
loadConfiguration()

// Save configuration
saveConfiguration()

// Test configuration
testConfiguration()

// Export configuration
exportConfiguration()

// Import configuration
importConfiguration($file)
```

### 3. View Component

**File:** `resources/views/livewire/livestock-purchase/configuration.blade.php`

#### Fitur Utama:

-   **Dashboard Summary**: Overview konfigurasi aktif
-   **Tab Navigation**: 10 tab untuk berbagai aspek
-   **Interactive Forms**: Form yang responsif dan user-friendly
-   **Status Indicators**: Visual indicator untuk status konfigurasi
-   **Modal Dialogs**: Konfirmasi untuk aksi penting

## Konfigurasi Detail

### 1. Workflow Configuration

```php
'status_flow' => [
    'draft' => [
        'enabled' => true,
        'can_edit' => true,
        'can_delete' => true,
        'requires_approval' => false,
        'auto_numbering' => false,
        'next_statuses' => ['pending', 'confirmed'],
    ],
    'pending' => [
        'enabled' => true,
        'can_edit' => false,
        'can_delete' => false,
        'requires_approval' => true,
        'auto_numbering' => true,
        'next_statuses' => ['confirmed', 'cancelled'],
    ],
    // ... more statuses
]
```

### 2. Validation Configuration

```php
'required_fields' => [
    'tanggal' => true,
    'supplier_id' => true,
    'farm_id' => true,
    'coop_id' => true,
    'invoice_number' => true,
],
'business_rules' => [
    'min_quantity' => 1,
    'max_quantity' => 50000,
    'min_price_per_unit' => 1000,
    'max_price_per_unit' => 1000000,
    'require_unique_invoice' => true,
]
```

### 3. Approval Configuration

```php
'levels' => [
    1 => [
        'name' => 'Manager Approval',
        'roles' => ['Manager', 'Supervisor'],
        'required' => true,
        'auto_approve' => false,
        'threshold' => 0,
    ],
    2 => [
        'name' => 'Director Approval',
        'roles' => ['Director', 'Administrator'],
        'required' => false,
        'auto_approve' => true,
        'threshold' => 50000000,
    ],
]
```

### 4. Batch Management Configuration

```php
'auto_create_batch' => [
    'enabled' => true,
    'trigger' => 'status_in_coop',
    'naming_convention' => 'LSP-{FARM}-{COOP}-{DATE}-{SEQ}',
],
'batch_tracking' => [
    'enabled' => true,
    'track_age' => true,
    'track_health' => true,
    'track_mortality' => true,
]
```

### 5. Cost Tracking Configuration

```php
'tracking_methods' => [
    'unit_cost' => [
        'enabled' => true,
        'include_transport' => true,
        'include_tax' => true,
        'include_handling' => true,
    ],
    'total_cost' => [
        'enabled' => true,
        'include_all_expenses' => true,
        'calculate_per_unit' => true,
    ],
]
```

### 6. Notification Configuration

```php
'channels' => [
    'email' => [
        'enabled' => true,
        'recipients' => ['manager', 'supervisor'],
        'templates' => [
            'purchase_created' => 'emails.livestock-purchase.created',
            'purchase_approved' => 'emails.livestock-purchase.approved',
        ],
    ],
    'push' => [
        'enabled' => true,
        'recipients' => ['manager', 'supervisor'],
    ],
]
```

### 7. Business Rules Configuration

```php
'purchase_limits' => [
    'daily_limit' => [
        'enabled' => true,
        'max_quantity' => 10000,
        'max_amount' => 500000000,
    ],
    'monthly_limit' => [
        'enabled' => true,
        'max_quantity' => 100000,
        'max_amount' => 5000000000,
    ],
],
'quality_rules' => [
    'health_requirements' => [
        'enabled' => true,
        'require_health_certificate' => false,
        'health_inspection_required' => true,
    ],
    'age_requirements' => [
        'enabled' => true,
        'min_age_days' => 1,
        'max_age_days' => 30,
    ],
]
```

## Business Flow Integration

### 1. Status Workflow

```
Draft → Pending → Confirmed → In Transit → Arrived → In Coop → Completed
  ↓       ↓         ↓           ↓          ↓         ↓
Cancelled ← ← ← ← ← ← ← ← ← ← ← ← ← ← ← ← ← ← ← ← ← ←
```

### 2. Approval Flow

```
Purchase Created → Level 1 Approval → Level 2 Approval (if needed) → Confirmed
```

### 3. Batch Creation Flow

```
Purchase In Coop → Auto Create Batch → Assign Batch Number → Start Tracking
```

### 4. Notification Flow

```
Status Change → Check Notification Rules → Send Notifications → Update Recipients
```

## Usage Examples

### 1. Menggunakan Konfigurasi dalam Controller

```php
use App\Config\LivestockPurchaseConfig;

class LivestockPurchaseController extends Controller
{
    public function store(Request $request)
    {
        // Get validation rules from config
        $validationRules = LivestockPurchaseConfig::getValidationRulesForStatus('draft');

        // Validate request
        $request->validate($validationRules);

        // Check business rules
        $businessRules = LivestockPurchaseConfig::getFor('business_rules');
        $this->validateBusinessRules($request, $businessRules);

        // Create purchase
        $purchase = LivestockPurchase::create($request->validated());

        // Check if approval required
        $statusConfig = LivestockPurchaseConfig::getStatusConfig('pending');
        if ($statusConfig['requires_approval']) {
            $this->requestApproval($purchase);
        }
    }
}
```

### 2. Menggunakan Konfigurasi dalam Model

```php
use App\Config\LivestockPurchaseConfig;

class LivestockPurchase extends Model
{
    public function canTransitionTo($newStatus)
    {
        return LivestockPurchaseConfig::canTransitionTo($this->status, $newStatus);
    }

    public function getNextAvailableStatuses()
    {
        return LivestockPurchaseConfig::getNextStatuses($this->status);
    }

    public function requiresApproval()
    {
        $statusConfig = LivestockPurchaseConfig::getStatusConfig($this->status);
        return $statusConfig['requires_approval'] ?? false;
    }
}
```

### 3. Menggunakan Konfigurasi dalam Service

```php
use App\Config\LivestockPurchaseConfig;

class LivestockPurchaseService
{
    public function processPurchase($purchase)
    {
        // Get workflow config
        $workflowConfig = LivestockPurchaseConfig::getFor('workflow');

        // Check if auto numbering enabled
        $statusConfig = $workflowConfig['status_flow'][$purchase->status];
        if ($statusConfig['auto_numbering']) {
            $this->generateAutoNumber($purchase);
        }

        // Check if batch creation enabled
        $batchConfig = LivestockPurchaseConfig::getFor('batch_management');
        if ($batchConfig['auto_create_batch']['enabled']) {
            $this->createBatch($purchase);
        }

        // Send notifications
        $notificationConfig = LivestockPurchaseConfig::getFor('notification');
        $this->sendNotifications($purchase, $notificationConfig);
    }
}
```

## Testing

### 1. Unit Tests

```php
class LivestockPurchaseConfigTest extends TestCase
{
    public function test_can_get_full_configuration()
    {
        $config = LivestockPurchaseConfig::getConfig();

        $this->assertIsArray($config);
        $this->assertArrayHasKey('workflow', $config);
        $this->assertArrayHasKey('validation', $config);
        $this->assertArrayHasKey('approval', $config);
    }

    public function test_can_check_status_transition()
    {
        $canTransition = LivestockPurchaseConfig::canTransitionTo('draft', 'pending');
        $this->assertTrue($canTransition);

        $cannotTransition = LivestockPurchaseConfig::canTransitionTo('draft', 'completed');
        $this->assertFalse($cannotTransition);
    }
}
```

### 2. Feature Tests

```php
class LivestockPurchaseConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_access_configuration_page()
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)
            ->get(route('livestock-purchase.configuration'));

        $response->assertStatus(200);
        $response->assertSee('Konfigurasi Livestock Purchase');
    }

    public function test_can_save_configuration()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $company = Company::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('livestock-purchase.configuration.save'), [
                'workflow' => [
                    'status_flow' => [
                        'draft' => [
                            'enabled' => true,
                            'can_edit' => true,
                        ]
                    ]
                ]
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'config->livestock_purchase->workflow->status_flow->draft->enabled' => true
        ]);
    }
}
```

## Deployment Checklist

### 1. Database Migration

-   [ ] Pastikan tabel `companies` memiliki kolom `config` (JSON)
-   [ ] Backup data konfigurasi existing jika ada

### 2. File Deployment

-   [ ] Deploy `app/Config/LivestockPurchaseConfig.php`
-   [ ] Deploy `app/Livewire/LivestockPurchase/Configuration.php`
-   [ ] Deploy `resources/views/livewire/livestock-purchase/configuration.blade.php`

### 3. Route Configuration

```php
// routes/web.php
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/livestock-purchase/configuration', \App\Livewire\LivestockPurchase\Configuration::class)
        ->name('livestock-purchase.configuration');
});
```

### 4. Permission Setup

```php
// Seeder atau migration untuk permission
Permission::create(['name' => 'manage livestock purchase configuration']);
Role::findByName('admin')->givePermissionTo('manage livestock purchase configuration');
```

### 5. Testing

-   [ ] Unit tests untuk config class
-   [ ] Feature tests untuk Livewire component
-   [ ] Integration tests untuk workflow
-   [ ] Manual testing untuk UI/UX

## Monitoring dan Maintenance

### 1. Logging

-   Semua perubahan konfigurasi di-log
-   Error handling untuk konfigurasi invalid
-   Audit trail untuk perubahan konfigurasi

### 2. Performance

-   Cache konfigurasi untuk performa optimal
-   Lazy loading untuk konfigurasi yang jarang digunakan
-   Database indexing untuk query konfigurasi

### 3. Backup

-   Auto backup konfigurasi sebelum perubahan
-   Export/import functionality untuk backup manual
-   Version control untuk konfigurasi

## Future Enhancements

### 1. Advanced Features

-   [ ] Configuration templates untuk industry standard
-   [ ] Configuration inheritance (parent-child company)
-   [ ] Configuration versioning dan rollback
-   [ ] Configuration analytics dan reporting

### 2. Integration Features

-   [ ] API endpoints untuk external configuration
-   [ ] Webhook untuk configuration changes
-   [ ] Integration dengan external ERP systems
-   [ ] Configuration synchronization across environments

### 3. User Experience

-   [ ] Drag-and-drop workflow builder
-   [ ] Visual workflow diagram
-   [ ] Configuration wizard untuk setup awal
-   [ ] Mobile-responsive configuration interface

## Conclusion

Fitur konfigurasi Livestock Purchase memberikan fleksibilitas maksimal untuk menyesuaikan sistem dengan kebutuhan bisnis yang spesifik. Dengan 10 aspek konfigurasi yang komprehensif, sistem dapat diadaptasi untuk berbagai skenario bisnis tanpa perlu modifikasi kode.

Fitur ini juga memastikan konsistensi dan compliance dengan business rules yang telah ditetapkan, sambil memberikan kemudahan dalam maintenance dan scaling sistem di masa depan.

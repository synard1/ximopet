# Supply Usage Bypass Configuration

## Overview

Supply Usage Bypass Configuration adalah fitur yang memungkinkan administrator untuk mengatur aturan bisnis yang fleksibel untuk perubahan status supply usage. Fitur ini memungkinkan bypass beberapa proses approval dan validasi berdasarkan role pengguna dan konfigurasi perusahaan.

## Fitur Utama

### 1. Direct Status Transitions

-   **Draft → In Process**: Operator dapat langsung mengubah status dari draft ke in_process tanpa approval
-   **Draft → Completed**: Operator dapat langsung menyelesaikan usage tanpa approval

### 2. Role-based Bypass

-   **Operator Bypass**: Skip approval untuk role Operator
-   **Supervisor Bypass**: Skip approval untuk role Supervisor
-   **Auto-approve Roles**: Role yang otomatis mendapat approval (Manager, Administrator)

### 3. Flexible Status Changes

Setiap role memiliki aturan transisi status yang berbeda:

#### Operator

-   Draft → Pending, Cancelled
-   Pending → In Process, Cancelled
-   In Process → Completed, Partially Used, Cancelled

#### Supervisor

-   Draft → Pending, In Process, Cancelled
-   Pending → In Process, Under Review, Cancelled
-   In Process → Completed, Partially Used, Cancelled
-   Under Review → In Process, Rejected, Cancelled

#### Manager

-   Draft → Pending, In Process, Completed, Cancelled
-   Pending → In Process, Under Review, Completed, Cancelled
-   In Process → Completed, Partially Used, Cancelled
-   Under Review → In Process, Rejected, Completed, Cancelled
-   Rejected → Draft, Cancelled

### 4. Workflow Settings

-   **Require Notes**: Wajib mengisi notes saat perubahan status
-   **Audit Trail**: Mencatat semua perubahan status untuk audit
-   **Stock Impact Bypass**: Transisi yang tidak mempengaruhi stock

## Konfigurasi Default

```php
const DEFAULT_BYPASS_CONFIG = [
    'allow_direct_to_in_process' => false,      // Draft → in_process langsung
    'allow_direct_to_completed' => false,       // Draft → completed langsung
    'skip_approval_for_operator' => false,      // Skip approval untuk operator
    'skip_approval_for_supervisor' => false,    // Skip approval untuk supervisor
    'auto_approve_for_roles' => [               // Role auto-approval
        'Manager',
        'Administrator'
    ],
    'require_notes_for_status_change' => false, // Wajib notes
    'enable_audit_trail' => true,               // Audit trail
    'stock_impact_bypass' => [                  // Bypass stock impact
        'draft_to_in_process',
        'draft_to_completed'
    ]
];
```

## Cara Penggunaan

### 1. Melalui UI (Company Settings)

1. Buka halaman Company Settings
2. Scroll ke bagian "Supply Usage Bypass Configuration"
3. Aktifkan/disable fitur yang diinginkan:
    - Direct Transitions
    - Role Bypass
    - Workflow Settings
4. Klik "Save Configuration"

### 2. Melalui Command Line

#### Menampilkan Konfigurasi

```bash
php artisan supply-usage:bypass-config show
```

#### Mengatur Konfigurasi

```bash
# Enable direct draft to in_process
php artisan supply-usage:bypass-config set --key=allow_direct_to_in_process --value=true

# Enable operator bypass
php artisan supply-usage:bypass-config set --key=skip_approval_for_operator --value=true

# Set auto-approve roles
php artisan supply-usage:bypass-config set --key=auto_approve_for_roles --value='["Manager","Administrator","Supervisor"]'
```

#### Reset ke Default

```bash
php artisan supply-usage:bypass-config reset
```

#### Test Konfigurasi

```bash
# Test untuk role Operator dari status Draft
php artisan supply-usage:bypass-config test --role=Operator --status=draft

# Test untuk role Supervisor dari status Pending
php artisan supply-usage:bypass-config test --role=Supervisor --status=pending
```

### 3. Melalui Code

#### Mengambil Konfigurasi

```php
// Get current configuration
$config = SupplyUsage::getBypassConfig();

// Get configuration for specific company
$config = SupplyUsage::getBypassConfigForCompany($companyId);
```

#### Update Konfigurasi

```php
$config = [
    'allow_direct_to_in_process' => true,
    'skip_approval_for_operator' => true,
    'auto_approve_for_roles' => ['Manager', 'Administrator', 'Supervisor']
];

SupplyUsage::updateBypassConfig($config);
```

#### Validasi Status Transition

```php
$usage = SupplyUsage::find($id);
$user = Auth::user();

// Check if user can transition to new status
if ($usage->canTransitionTo('in_process', $user)) {
    // Allowed transition
}

// Check if user can bypass approval
if ($usage->canBypassApproval('in_process', $user)) {
    // Can bypass approval process
}

// Get allowed transitions for user
$allowedTransitions = $usage->getAllowedStatusTransitions($user);
```

## Implementasi di Livewire Component

### Status Update dengan Bypass

```php
public function updateStatusSupplyUsage($usageId, $status, $notes = null)
{
    $usage = SupplyUsage::findOrFail($usageId);
    $user = Auth::user();

    // Check if notes are required
    if (SupplyUsage::requiresNotesForStatusChange() && empty($notes)) {
        $this->dispatch('error', 'Notes wajib diisi untuk perubahan status.');
        return;
    }

    // Validate status transition using bypass configuration
    if (!$usage->canTransitionTo($status, $user)) {
        $this->dispatch('error', 'Transisi status tidak valid.');
        return;
    }

    // Check if user can bypass approval
    $canBypass = $usage->canBypassApproval($status, $user);

    if ($canBypass) {
        // Direct transition without approval
        $usage->status = $status;
        $usage->save();
    } else {
        // Use service for approval process
        $service = resolve(SupplyUsageService::class);
        $result = $service->approveUsage($usage);
    }

    // Log audit trail if enabled
    if (SupplyUsage::isAuditTrailEnabled()) {
        logInfoIfDebug('Supply Usage Status Change', [
            'usage_id' => $usage->id,
            'previous_status' => $previousStatus,
            'new_status' => $status,
            'user_id' => $user->id,
            'bypass_used' => $canBypass
        ]);
    }
}
```

## DataTable Integration

### Status Dropdown dengan Bypass

```php
->editColumn('status', function (SupplyUsage $usage) {
    $allowedTransitions = $usage->getAllowedStatusTransitions();

    $html = '<select class="form-select form-select-sm status-select">';

    foreach (SupplyUsage::STATUS_LABELS as $value => $label) {
        $selected = $value === $usage->status ? 'selected' : '';
        $disabled = !in_array($value, $allowedTransitions) ? 'disabled' : '';

        $html .= "<option value='{$value}' {$selected} {$disabled}>{$label}</option>";
    }

    $html .= '</select>';
    return $html;
})
```

## Business Flow Examples

### Example 1: Operator Bypass Enabled

```
Draft → In Process (langsung, tanpa approval)
In Process → Completed (langsung, tanpa approval)
```

### Example 2: Strict Approval Flow

```
Draft → Pending (perlu approval)
Pending → In Process (perlu approval)
In Process → Completed (perlu approval)
```

### Example 3: Mixed Flow

```
Draft → In Process (langsung untuk Operator)
In Process → Completed (perlu approval untuk semua)
```

## Monitoring dan Audit

### Audit Trail

Jika `enable_audit_trail` diaktifkan, semua perubahan status akan dicatat dengan detail:

-   Usage ID
-   Previous status
-   New status
-   User ID dan role
-   Timestamp
-   Bypass used flag
-   Notes

### Logging

Semua operasi bypass configuration akan di-log untuk debugging:

```php
logDebugIfDebug('updateStatusSupplyUsage: Bypass checks', [
    'can_bypass' => $canBypass,
    'bypasses_stock_impact' => $bypassesStockImpact,
    'transition' => $previousStatus . '_to_' . $status
]);
```

## Best Practices

### 1. Security

-   Selalu validasi permission user sebelum mengizinkan bypass
-   Gunakan audit trail untuk tracking semua perubahan
-   Batasi bypass hanya untuk role yang diperlukan

### 2. Business Rules

-   Sesuaikan konfigurasi dengan workflow bisnis perusahaan
-   Test konfigurasi sebelum deploy ke production
-   Dokumentasikan semua aturan bypass

### 3. Performance

-   Cache konfigurasi jika diperlukan
-   Gunakan database indexing untuk query yang sering
-   Monitor penggunaan bypass untuk optimasi

### 4. Maintenance

-   Review konfigurasi secara berkala
-   Update dokumentasi saat ada perubahan
-   Backup konfigurasi sebelum perubahan besar

## Troubleshooting

### Common Issues

1. **Status tidak bisa diubah**

    - Check role user
    - Verify bypass configuration
    - Check current status

2. **Bypass tidak bekerja**

    - Verify company configuration
    - Check user role assignment
    - Review bypass rules

3. **Audit trail tidak muncul**
    - Check `enable_audit_trail` setting
    - Verify logging configuration
    - Check database permissions

### Debug Commands

```bash
# Test configuration for specific scenario
php artisan supply-usage:bypass-config test --role=Operator --status=draft

# Show current configuration
php artisan supply-usage:bypass-config show

# Reset to default if issues occur
php artisan supply-usage:bypass-config reset
```

## Migration dan Deployment

### Pre-deployment Checklist

-   [ ] Test bypass configuration di environment staging
-   [ ] Verify role assignments untuk semua user
-   [ ] Review audit trail settings
-   [ ] Backup existing configuration
-   [ ] Update documentation

### Post-deployment Validation

-   [ ] Test status transitions untuk semua role
-   [ ] Verify audit trail recording
-   [ ] Check performance impact
-   [ ] Monitor error logs
-   [ ] User acceptance testing

## Future Enhancements

### Planned Features

1. **Conditional Bypass**: Bypass berdasarkan kondisi tertentu (quantity, supply type, etc.)
2. **Time-based Bypass**: Bypass hanya pada waktu tertentu
3. **Notification Integration**: Notifikasi otomatis untuk bypass events
4. **Approval Chain**: Multi-level approval dengan bypass options
5. **Mobile Support**: Bypass configuration untuk mobile app

### API Integration

-   REST API untuk bypass configuration management
-   Webhook untuk bypass events
-   Integration dengan external approval systems

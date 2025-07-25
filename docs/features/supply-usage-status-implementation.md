# Supply Usage Status Implementation

## Overview

Implementasi sistem status yang komprehensif untuk Supply Usage dengan business flow yang terstruktur dan permission-based access control.

## 🎯 Status Types

### Core Status (7 Status Utama)

| Status           | Kode           | Deskripsi                          | Badge Color       | Icon              | Business Logic                           |
| ---------------- | -------------- | ---------------------------------- | ----------------- | ----------------- | ---------------------------------------- |
| **Draft**        | `draft`        | Data masih dalam tahap penyusunan  | `badge-secondary` | `ki-file`         | Bisa diedit, belum mempengaruhi stock    |
| **Pending**      | `pending`      | Menunggu approval/konfirmasi       | `badge-warning`   | `ki-clock`        | Stock di-reserve, menunggu approval      |
| **In Process**   | `in_process`   | Sedang diproses (stock dikurangi)  | `badge-info`      | `ki-gear`         | Stock sedang diupdate, transaksi aktif   |
| **Completed**    | `completed`    | Selesai dan tersimpan dengan benar | `badge-success`   | `ki-check-circle` | Stock sudah dikurangi, transaksi final   |
| **Under Review** | `under_review` | Sedang direview supervisor/manager | `badge-primary`   | `ki-eye`          | Menunggu review, tidak bisa diedit       |
| **Rejected**     | `rejected`     | Ditolak oleh reviewer              | `badge-danger`    | `ki-cross`        | Perlu perbaikan, bisa diedit ulang       |
| **Cancelled**    | `cancelled`    | Dibatalkan                         | `badge-danger`    | `ki-cross-circle` | Stock dikembalikan, transaksi dibatalkan |

### Special Status (3 Status Khusus)

| Status             | Kode             | Deskripsi                              | Badge Color     | Icon                | Business Logic                       |
| ------------------ | ---------------- | -------------------------------------- | --------------- | ------------------- | ------------------------------------ |
| **Partially Used** | `partially_used` | Sebagian supply sudah digunakan        | `badge-warning` | `ki-half-star`      | Stock sebagian terpakai              |
| **Expired**        | `expired`        | Supply sudah expired sebelum digunakan | `badge-danger`  | `ki-calendar-cross` | Tidak bisa digunakan, perlu disposal |
| **Damaged**        | `damaged`        | Supply rusak saat proses               | `badge-danger`  | `ki-warning`        | Perlu penanganan khusus              |

## 🔄 Status Flow

### Normal Flow

```
Draft → Pending → In Process → Completed
  ↓        ↓         ↓
Rejected  Cancelled  Cancelled
```

### Review Flow

```
Draft → Pending → Under Review → Approved → In Process → Completed
  ↓        ↓           ↓
Rejected  Rejected    Rejected
```

### Error Flow

```
Draft → Pending → In Process → Partially Used → Completed
  ↓        ↓         ↓
Expired  Damaged    Damaged
```

## 🛡️ Permission Matrix

### Status Permissions

```php
const STATUS_PERMISSIONS = [
    'draft' => ['edit', 'delete', 'submit'],
    'pending' => ['view', 'cancel', 'approve'],
    'in_process' => ['view', 'cancel'],
    'completed' => ['view', 'edit_limited', 'delete_limited'],
    'cancelled' => ['view', 'restore'],
    'under_review' => ['view'],
    'rejected' => ['view', 'edit', 'resubmit'],
    'partially_used' => ['view', 'complete'],
    'expired' => ['view', 'dispose'],
    'damaged' => ['view', 'report'],
];
```

### Stock Impact Rules

```php
const STOCK_IMPACT = [
    'draft' => 'NO_IMPACT',
    'pending' => 'RESERVED',
    'in_process' => 'REDUCING',
    'completed' => 'REDUCED',
    'cancelled' => 'RESTORED',
    'under_review' => 'RESERVED',
    'rejected' => 'NO_IMPACT',
    'partially_used' => 'PARTIALLY_REDUCED',
    'expired' => 'NO_IMPACT',
    'damaged' => 'NO_IMPACT',
];
```

## 🏗️ Implementation Details

### Model Methods

#### Status Check Methods

```php
$usage->isDraft()           // Check if status is draft
$usage->isPending()         // Check if status is pending
$usage->isInProcess()       // Check if status is in_process
$usage->isCompleted()       // Check if status is completed
$usage->isCancelled()       // Check if status is cancelled
$usage->isUnderReview()     // Check if status is under_review
$usage->isRejected()        // Check if status is rejected
```

#### Permission Methods

```php
$usage->canBeEdited()       // Check if usage can be edited
$usage->canBeDeleted()      // Check if usage can be deleted
$usage->canBeCancelled()    // Check if usage can be cancelled
$usage->canBeApproved()     // Check if usage can be approved
$usage->canBeSubmitted()    // Check if usage can be submitted
$usage->canBeRestored()     // Check if usage can be restored
```

#### Status Transition Methods

```php
$usage->submit()            // Draft → Pending
$usage->approve()           // Pending/Rejected → In Process
$usage->complete()          // In Process → Completed
$usage->cancel()            // Draft/Pending/In Process → Cancelled
$usage->reject($reason)     // Pending/Under Review → Rejected
$usage->restore()           // Cancelled → Pending
```

#### Display Methods

```php
$usage->getStatusLabel()    // Get human-readable status label
$usage->getStatusBadgeClass() // Get Bootstrap badge class
$usage->getStatusIcon()     // Get Keenthemes icon class
$usage->getStockImpact()    // Get stock impact description
$usage->getAvailablePermissions() // Get available permissions
```

### Service Methods

#### SupplyUsageService Status Methods

```php
$service->submitForApproval($usage)     // Submit usage for approval
$service->approveUsage($usage)          // Approve usage
$service->completeUsage($usage)         // Complete usage
$service->cancelUsage($usage, $reason)  // Cancel usage
$service->rejectUsage($usage, $reason)  // Reject usage
$service->restoreUsage($usage)          // Restore cancelled usage
```

## 📊 Database Schema

### supply_usages Table

```sql
ALTER TABLE supply_usages
MODIFY COLUMN status ENUM(
    'draft',
    'pending',
    'in_process',
    'completed',
    'cancelled',
    'under_review',
    'rejected',
    'partially_used',
    'expired',
    'damaged'
) DEFAULT 'draft';
```

## 🎨 UI Implementation

### Legend Card

-   **Collapsible**: Legend dapat di-collapse untuk menghemat ruang
-   **7 Status Display**: Menampilkan 7 status utama dengan icon dan deskripsi
-   **Responsive**: Layout responsive dengan Bootstrap grid

### DataTable Display

-   **Status Badges**: Setiap status ditampilkan dengan badge yang sesuai
-   **Icons**: Icon Keenthemes untuk setiap status
-   **Colors**: Warna yang konsisten dengan status

### Status Statistics

```php
SupplyUsage::getStatusStatistics()
// Returns array with count, label, badge_class, icon for each status
```

## 🔧 Configuration

### Environment Variables

```env
# Status Configuration
SUPPLY_USAGE_DEFAULT_STATUS=draft
SUPPLY_USAGE_AUTO_APPROVE=false
SUPPLY_USAGE_REQUIRE_REVIEW=true
```

### Company Settings

```php
// Company-specific status rules
$company->supply_usage_settings = [
    'require_approval' => true,
    'auto_complete' => false,
    'allow_cancellation' => true,
    'cancellation_window_hours' => 24,
];
```

## 📈 Reporting & Analytics

### Status Distribution Report

```php
// Get status distribution
$stats = SupplyUsage::getStatusStatistics();

// Example output:
[
    'draft' => ['count' => 15, 'label' => 'Draft', ...],
    'pending' => ['count' => 8, 'label' => 'Pending', ...],
    'completed' => ['count' => 45, 'label' => 'Completed', ...],
    // ...
]
```

### Processing Time Analysis

```php
// Average time from draft to completed
$avgProcessingTime = SupplyUsage::where('status', 'completed')
    ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as avg_hours')
    ->first();
```

### Error Rate Analysis

```php
// Error status count
$errorCount = SupplyUsage::whereIn('status', ['rejected', 'expired', 'damaged'])
    ->count();
```

## 🚀 Usage Examples

### Creating New Usage

```php
$usage = SupplyUsage::create([
    'farm_id' => $farmId,
    'coop_id' => $coopId,
    'usage_date' => now(),
    'status' => SupplyUsage::STATUS_DRAFT, // Default status
    // ... other fields
]);
```

### Status Transition

```php
// Submit for approval
$service->submitForApproval($usage);

// Approve usage
$service->approveUsage($usage);

// Complete usage
$service->completeUsage($usage);
```

### Permission Check

```php
if ($usage->canBeEdited()) {
    // Show edit button
}

if ($usage->canBeApproved()) {
    // Show approve button
}
```

### Display Status

```php
// In Blade template
<span class="badge {{ $usage->getStatusBadgeClass() }}">
    <i class="{{ $usage->getStatusIcon() }}"></i>
    {{ $usage->getStatusLabel() }}
</span>
```

## 🔒 Security Considerations

### Permission Validation

-   Setiap status transition divalidasi permission
-   User hanya bisa mengubah status sesuai role
-   Audit trail untuk semua status changes

### Data Integrity

-   Stock impact dikelola dengan transaction
-   Rollback otomatis jika terjadi error
-   Validation sebelum status transition

### Audit Trail

```php
// Log semua status changes
Log::info("Supply usage status changed", [
    'usage_id' => $usage->id,
    'old_status' => $oldStatus,
    'new_status' => $newStatus,
    'changed_by' => Auth::id(),
    'reason' => $reason ?? null,
]);
```

## 🧪 Testing

### Unit Tests

```php
// Test status transitions
public function test_draft_can_be_submitted()
{
    $usage = SupplyUsage::factory()->create(['status' => 'draft']);
    $this->assertTrue($usage->canBeSubmitted());
}

public function test_pending_cannot_be_submitted()
{
    $usage = SupplyUsage::factory()->create(['status' => 'pending']);
    $this->assertFalse($usage->canBeSubmitted());
}
```

### Integration Tests

```php
// Test service methods
public function test_submit_for_approval_reserves_stock()
{
    $usage = SupplyUsage::factory()->create(['status' => 'draft']);
    $service = new SupplyUsageService();

    $result = $service->submitForApproval($usage);

    $this->assertEquals('pending', $usage->fresh()->status);
    $this->assertTrue($result['success']);
}
```

## 📝 Migration Guide

### From Old Status System

```php
// Migration script to update existing records
DB::table('supply_usages')
    ->where('status', 'incomplete')
    ->update(['status' => 'draft']);

DB::table('supply_usages')
    ->where('status', 'completed')
    ->update(['status' => 'completed']); // No change needed
```

### Database Migration

```bash
php artisan make:migration update_supply_usages_status_enum
```

```php
public function up()
{
    DB::statement("ALTER TABLE supply_usages MODIFY COLUMN status ENUM(
        'draft', 'pending', 'in_process', 'completed', 'cancelled',
        'under_review', 'rejected', 'partially_used', 'expired', 'damaged'
    ) DEFAULT 'draft'");
}
```

## 🎯 Benefits

1. **Clear Business Flow**: Setiap status memiliki definisi yang jelas
2. **Permission Control**: Access control berdasarkan status
3. **Stock Management**: Stock impact yang terdefinisi dengan baik
4. **Audit Trail**: Tracking lengkap untuk compliance
5. **User Experience**: UI yang informatif dan intuitif
6. **Scalability**: Mudah untuk menambah status baru
7. **Reporting**: Analytics yang komprehensif

## 🔮 Future Enhancements

1. **Workflow Engine**: Automated status transitions
2. **Notification System**: Email/SMS notifications for status changes
3. **Mobile App**: Status updates via mobile
4. **Integration**: ERP/Accounting system integration
5. **AI/ML**: Predictive analytics for status patterns

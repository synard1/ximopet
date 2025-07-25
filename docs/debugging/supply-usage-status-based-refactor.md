# Supply Usage Status-Based Refactor

## 📋 Overview

Refactor pada `Usage.php` untuk memastikan kalkulasi SupplyStock dan CurrentSupply hanya terjadi pada status yang sesuai, tidak ter-trigger saat data supply usage baru dibuat dengan status draft.

## 🔄 Perubahan Utama

### 1. Status-Based Stock Management

**Sebelum:**

-   Stock calculations selalu dijalankan saat create/update usage
-   Tidak ada pengecekan status sebelum memodifikasi stock
-   Draft status tetap mempengaruhi stock

**Sesudah:**

-   Stock calculations hanya dijalankan pada status yang sesuai
-   Status yang memungkinkan stock modifications:
    -   `STATUS_PENDING`
    -   `STATUS_IN_PROCESS`
    -   `STATUS_COMPLETED`
-   Draft status tidak mempengaruhi stock

### 2. Enhanced Create Usage Process

```php
private function createUsageWithService($service)
{
    // Create usage with draft status
    $usage = SupplyUsage::create([
        'status' => SupplyUsage::STATUS_DRAFT,
        // ... other fields
    ]);

    // Create usage details without affecting stock
    $this->createUsageDetails($usage);

    // Update total quantity
    $totalQuantity = collect($this->items)->sum('converted_quantity');
    $usage->update(['total_quantity' => $totalQuantity]);

    // DO NOT sync CurrentSupply or recalculate SupplyStock for draft status
    // These will be handled when status changes to appropriate state

    return [
        'success' => true,
        'usage_id' => $usage->id,
        'status' => $usage->status,
        'message' => 'Supply usage draft created successfully. Submit for approval to process stock.',
        'mode' => 'draft_creation'
    ];
}
```

### 3. Enhanced Update Usage Process

```php
private function updateUsageWithService($service)
{
    $usage = SupplyUsage::findOrFail($this->usageId);

    // Check if status allows stock modifications
    $canModifyStock = in_array($usage->status, [
        SupplyUsage::STATUS_PENDING,
        SupplyUsage::STATUS_IN_PROCESS,
        SupplyUsage::STATUS_COMPLETED
    ]);

    if ($canModifyStock) {
        // Restore previous stock usage (rollback)
        $this->restorePreviousStockUsage($usage);
    }

    // Update usage details...

    // Only sync CurrentSupply and recalculate SupplyStock if status allows
    if ($canModifyStock) {
        foreach ($this->items as $item) {
            if (!empty($item['supply_id'])) {
                $this->syncCurrentSupply($this->farm_id, $this->livestock_id, $item['supply_id']);
            }
            $stock = \App\Models\SupplyStock::find($item['supply_stock_id']);
            if ($stock) {
                $stock->recalculateQuantityUsed();
            }
        }
    }
}
```

### 4. New Status Transition Methods

#### Submit for Approval

```php
public function submitForApproval()
{
    $usage = SupplyUsage::findOrFail($this->usageId);

    if (!$usage->canBeSubmitted()) {
        $this->dispatch('error', 'Usage tidak dapat disubmit.');
        return;
    }

    $service = resolve(SupplyUsageService::class);
    $result = $service->submitForApproval($usage);

    $this->dispatch('success', $result['message']);
    $this->close();
}
```

#### Approve Usage

```php
public function approveUsage()
{
    $usage = SupplyUsage::findOrFail($this->usageId);

    if (!$usage->canBeApproved()) {
        $this->dispatch('error', 'Usage tidak dapat diapprove.');
        return;
    }

    $service = resolve(SupplyUsageService::class);
    $result = $service->approveUsage($usage);

    $this->dispatch('success', $result['message']);
    $this->close();
}
```

#### Complete Usage

```php
public function completeUsage()
{
    $usage = SupplyUsage::findOrFail($this->usageId);

    if (!$usage->isInProcess()) {
        $this->dispatch('error', 'Usage tidak dapat diselesaikan.');
        return;
    }

    $service = resolve(SupplyUsageService::class);
    $result = $service->completeUsage($usage);

    $this->dispatch('success', $result['message']);
    $this->close();
}
```

### 5. Enhanced Delete/Cancel Methods

#### Cancel Usage with Stock Restoration

```php
public function cancelSupplyUsage($id)
{
    $usage = SupplyUsage::findOrFail($id);

    if (!$usage->canBeCancelled()) {
        $this->dispatch('error', 'Usage tidak dapat dibatalkan.');
        return;
    }

    $service = resolve(SupplyUsageService::class);
    $result = $service->cancelUsage($usage, 'Cancelled by user');

    $this->dispatch('success', $result['message']);
}
```

#### Delete Usage with Status Check

```php
public function deleteSupplyUsage($id)
{
    $usage = SupplyUsage::findOrFail($id);

    if (!$usage->canBeDeleted()) {
        $this->dispatch('error', 'Usage tidak dapat dihapus.');
        return;
    }

    DB::transaction(function () use ($usage) {
        // Only restore stock if status allows stock modifications
        $canModifyStock = in_array($usage->status, [
            SupplyUsage::STATUS_PENDING,
            SupplyUsage::STATUS_IN_PROCESS,
            SupplyUsage::STATUS_COMPLETED
        ]);

        if ($canModifyStock) {
            $this->restorePreviousStockUsage($usage);
        }

        // Delete usage and details
        $usage->details()->delete();
        $usage->delete();

        // Only sync CurrentSupply if stock was modified
        if ($canModifyStock) {
            foreach ($supplyIds as $supplyId) {
                $this->syncCurrentSupply($usage->farm_id, $usage->livestock_id, $supplyId);
            }
        }
    });
}
```

## 🎯 Business Logic Flow

### Draft Creation Flow

1. User creates supply usage
2. Status set to `STATUS_DRAFT`
3. Usage details created
4. **NO stock calculations**
5. **NO CurrentSupply sync**
6. User can edit freely

### Submit for Approval Flow

1. User submits draft for approval
2. Status changes to `STATUS_PENDING`
3. Stock calculations triggered
4. CurrentSupply updated
5. Stock reserved for usage

### Approval Flow

1. Supervisor/Manager approves usage
2. Status changes to `STATUS_IN_PROCESS`
3. Stock calculations updated
4. CurrentSupply recalculated

### Completion Flow

1. Usage marked as completed
2. Status changes to `STATUS_COMPLETED`
3. Final stock calculations
4. Audit trail created

## 🔧 Technical Implementation

### Status Validation Constants

```php
// Status yang memungkinkan stock modifications
$canModifyStock = in_array($usage->status, [
    SupplyUsage::STATUS_PENDING,
    SupplyUsage::STATUS_IN_PROCESS,
    SupplyUsage::STATUS_COMPLETED
]);
```

### Enhanced Logging

```php
logDebugIfDebug('Usage@createUsageWithService: Usage details created in draft mode.', [
    'usage_id' => $usage->id,
    'total_quantity' => $totalQuantity,
    'details_count' => count($this->items)
]);

logDebugIfDebug('Usage@updateUsageWithService: Stock calculations skipped due to status.', [
    'usage_id' => $usage->id,
    'status' => $usage->status
]);
```

### Error Handling

```php
try {
    // Status transition operations
} catch (\Exception $e) {
    logErrorIfDebug('Usage@methodName: Error message.', [
        'usage_id' => $this->usageId,
        'error' => $e->getMessage()
    ]);
    $this->dispatch('error', 'Gagal operation: ' . $e->getMessage());
}
```

## 📊 Benefits

### 1. Data Integrity

-   Stock tidak terpengaruh saat draft creation
-   Kalkulasi hanya terjadi pada status yang tepat
-   Mencegah data inconsistency

### 2. User Experience

-   User dapat membuat draft tanpa mempengaruhi stock
-   Clear workflow dengan status transitions
-   Proper error messages untuk invalid operations

### 3. Business Logic

-   Workflow approval yang jelas
-   Stock management yang akurat
-   Audit trail yang lengkap

### 4. Performance

-   Mengurangi unnecessary calculations
-   Optimized database operations
-   Better resource utilization

## 🚀 Next Steps

### 1. UI Integration

-   Add status transition buttons
-   Implement status-based UI visibility
-   Add confirmation dialogs

### 2. Notification System

-   Email notifications for status changes
-   In-app notifications
-   SMS alerts for critical status changes

### 3. Workflow Automation

-   Auto-approval rules
-   Scheduled status transitions
-   Integration with external systems

### 4. Reporting

-   Status-based reports
-   Workflow analytics
-   Performance metrics

## 📝 Migration Notes

### Database Changes

-   No schema changes required
-   Existing data remains compatible
-   Status field already exists

### Code Changes

-   Backward compatible
-   Existing methods still work
-   New methods are additive

### Testing

-   Test all status transitions
-   Verify stock calculations
-   Validate error handling

## 🔍 Debugging

### Common Issues

1. **Stock not updating**: Check if status allows stock modifications
2. **Permission errors**: Verify user has appropriate role
3. **Status transition fails**: Check business rules validation

### Debug Commands

```php
// Check usage status
$usage = SupplyUsage::find($id);
dd($usage->status, $usage->canBeSubmitted());

// Check stock calculations
$stock = SupplyStock::find($stockId);
dd($stock->quantity_used, $stock->recalculateQuantityUsed());
```

### Log Analysis

```bash
# Check for stock calculation logs
grep "Stock calculations" storage/logs/laravel.log

# Check for status transition logs
grep "Status transition" storage/logs/laravel.log
```

## 📚 Related Documentation

-   [Supply Usage Status Implementation](../features/supply-usage-status-implementation.md)
-   [SupplyUsageService Documentation](../services/supply-usage-service.md)
-   [SupplyStock Model Documentation](../models/supply-stock.md)

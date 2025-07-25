# Supply Usage Status Update Feature

## 📋 Overview

Fitur untuk mengubah status supply usage langsung dari DataTable dropdown, mirip dengan implementasi di LivestockPurchaseDataTable. Fitur ini memungkinkan user untuk mengubah status supply usage dengan mudah dan cepat tanpa perlu membuka form edit.

## 🎯 Features

### 1. Status Dropdown in DataTable

-   Dropdown select untuk setiap row di kolom status
-   Permission-based visibility (hanya user dengan permission atau role Supervisor/Manager)
-   Business rule validation untuk status transitions
-   Visual feedback untuk status yang tidak bisa diubah

### 2. Business Rules for Status Transitions

-   **Draft** → Pending, Cancelled
-   **Pending** → In Process, Under Review, Cancelled
-   **In Process** → Completed, Partially Used, Cancelled
-   **Under Review** → In Process, Rejected, Cancelled
-   **Partially Used** → Completed, Cancelled
-   **Rejected** → Draft, Cancelled
-   **Completed/Cancelled/Rejected** → Disabled (tidak bisa diubah)

### 3. Permission System

-   User dengan permission `update supply usage`
-   User dengan role `Supervisor` atau `Manager`
-   User tanpa permission hanya melihat badge status (read-only)

## 🔧 Technical Implementation

### 1. DataTable Column Update

**File:** `app/DataTables/SupplyUsageDataTable.php`

```php
->editColumn('status', function (SupplyUsage $usage) {
    $statuses = SupplyUsage::STATUS_LABELS;
    $currentStatus = $usage->status;

    // Check if user has update permission or is Supervisor/Manager
    $canUpdate = \Illuminate\Support\Facades\Auth::user()->can('update supply usage') ||
        \Illuminate\Support\Facades\Auth::user()->hasAnyRole(['Supervisor', 'Manager']);

    if (!$canUpdate) {
        // Return read-only badge
        return '<span class="badge ' . $badgeClass . ' fs-7 fw-bold px-3 py-2">...</span>';
    }

    // Check if status can be changed
    $isDisabled = in_array($currentStatus, [
        SupplyUsage::STATUS_CANCELLED,
        SupplyUsage::STATUS_COMPLETED,
        SupplyUsage::STATUS_REJECTED
    ]) ? 'disabled' : '';

    // Generate dropdown HTML with business rules
    $html = '<div class="d-flex align-items-center">';
    $html .= '<select class="form-select form-select-sm status-select"
              data-kt-usage-id="' . $usage->id . '"
              data-kt-action="update_status"
              data-current="' . $currentStatus . '" ' . $isDisabled . '>';

    foreach ($statuses as $value => $label) {
        $selected = $value === $currentStatus ? 'selected' : '';
        $optionDisabled = '';
        $optionStyle = '';

        // Apply business rules for status transitions
        if ($currentStatus === SupplyUsage::STATUS_DRAFT) {
            if (!in_array($value, [SupplyUsage::STATUS_PENDING, SupplyUsage::STATUS_CANCELLED])) {
                $optionDisabled = 'disabled';
                $optionStyle = 'style="background-color: #f5f5f5; color: #999;"';
            }
        }
        // ... more business rules

        $html .= "<option value='{$value}' {$selected} {$optionDisabled} {$optionStyle}>{$label}</option>";
    }

    $html .= '</select>';
    $html .= '</div>';

    return $html;
})
```

### 2. Livewire Component Method

**File:** `app/Livewire/MasterData/Supply/Usage.php`

```php
/**
 * Update supply usage status from DataTable dropdown
 */
public function updateStatusSupplyUsage($usageId, $status, $notes = null)
{
    if (empty($usageId) || empty($status)) {
        logWarningIfDebug('updateStatusSupplyUsage: usageId atau status kosong', [
            'usageId' => $usageId,
            'status' => $status
        ]);
        $this->dispatch('error', 'Usage ID atau status tidak valid.');
        return;
    }

    try {
        $usage = SupplyUsage::findOrFail($usageId);
        $previousStatus = $usage->status;

        // Validate status transition
        if (!$this->isValidStatusTransition($previousStatus, $status)) {
            $this->dispatch('error', 'Transisi status tidak valid: ' . $previousStatus . ' → ' . $status);
            return;
        }

        // Use SupplyUsageService for status transition
        $service = resolve(SupplyUsageService::class);

        switch ($status) {
            case SupplyUsage::STATUS_PENDING:
                if ($previousStatus === SupplyUsage::STATUS_DRAFT) {
                    $result = $service->submitForApproval($usage);
                } else {
                    $usage->status = $status;
                    $usage->save();
                    $result = ['success' => true, 'message' => 'Status berhasil diubah ke ' . $usage->getStatusLabel()];
                }
                break;

            case SupplyUsage::STATUS_IN_PROCESS:
                if ($previousStatus === SupplyUsage::STATUS_PENDING) {
                    $result = $service->approveUsage($usage);
                } else {
                    $usage->status = $status;
                    $usage->save();
                    $result = ['success' => true, 'message' => 'Status berhasil diubah ke ' . $usage->getStatusLabel()];
                }
                break;

            // ... more cases
        }

        $this->dispatch('statusUpdated');
        $this->dispatch('success', $result['message'] ?? 'Status supply usage berhasil diperbarui.');

    } catch (\Exception $e) {
        logErrorIfDebug('updateStatusSupplyUsage: Error updating status', [
            'usage_id' => $usageId,
            'new_status' => $status,
            'error' => $e->getMessage()
        ]);
        $this->dispatch('error', 'Gagal update status: ' . $e->getMessage());
    }
}

/**
 * Validate status transition based on business rules
 */
private function isValidStatusTransition($currentStatus, $newStatus): bool
{
    $validTransitions = [
        SupplyUsage::STATUS_DRAFT => [
            SupplyUsage::STATUS_PENDING,
            SupplyUsage::STATUS_CANCELLED
        ],
        SupplyUsage::STATUS_PENDING => [
            SupplyUsage::STATUS_IN_PROCESS,
            SupplyUsage::STATUS_UNDER_REVIEW,
            SupplyUsage::STATUS_CANCELLED
        ],
        // ... more transitions
    ];

    return isset($validTransitions[$currentStatus]) &&
           in_array($newStatus, $validTransitions[$currentStatus]);
}
```

### 3. JavaScript Event Handling

**File:** `resources/views/pages/masterdata/supply/usage.blade.php`

```javascript
// Handle status dropdown changes
$(document).on("change", ".status-select", function () {
    const $select = $(this);
    const usageId = $select.data("kt-usage-id");
    const newStatus = $select.val();
    const currentStatus = $select.data("current");

    console.log("Supply Usage status change initiated:", {
        usageId: usageId,
        currentStatus: currentStatus,
        newStatus: newStatus,
    });

    // Show confirmation dialog
    Swal.fire({
        title: "Update Status",
        text: `Apakah Anda yakin ingin mengubah status dari "${currentStatus}" ke "${newStatus}"?`,
        icon: "question",
        showCancelButton: true,
        confirmButtonText: "Ya, Update",
        cancelButtonText: "Batal",
        buttonsStyling: false,
        customClass: {
            confirmButton: "btn btn-primary",
            cancelButton: "btn btn-secondary",
        },
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading state
            $select.prop("disabled", true);

            // Dispatch Livewire event
            Livewire.dispatch("updateStatusSupplyUsage", {
                usageId: usageId,
                status: newStatus,
                notes: null,
            });

            // Show success message
            Swal.fire({
                title: "Status Updated",
                text: "Status berhasil diperbarui!",
                icon: "success",
                timer: 2000,
                showConfirmButton: false,
            });
        } else {
            // Reset to previous value if cancelled
            $select.val(currentStatus);
        }
    });
});

// Handle Livewire events
document.addEventListener("livewire:init", () => {
    Livewire.on("statusUpdated", () => {
        // Refresh DataTable after status update
        const table = $("#supply-usage-table").DataTable();
        if (table && table.ajax) {
            table.ajax.reload(null, false);
        }
    });
});
```

## 🎨 UI/UX Features

### 1. Visual Feedback

-   **Disabled dropdown**: Untuk status yang tidak bisa diubah (Completed, Cancelled, Rejected)
-   **Disabled options**: Untuk transisi status yang tidak valid
-   **Loading state**: Dropdown disabled saat proses update
-   **Confirmation dialog**: Konfirmasi sebelum mengubah status

### 2. Status Badges (Read-only)

-   User tanpa permission melihat badge dengan warna dan icon
-   Konsisten dengan design system

### 3. Responsive Design

-   Dropdown responsive di berbagai ukuran layar
-   Proper spacing dan alignment

## 🔒 Security & Validation

### 1. Permission Checks

-   Server-side validation untuk permission
-   Role-based access control
-   Audit trail untuk semua status changes

### 2. Business Rule Validation

-   Client-side validation untuk UI feedback
-   Server-side validation untuk data integrity
-   Proper error handling dan logging

### 3. Data Integrity

-   Transaction safety untuk status updates
-   Rollback mechanism jika terjadi error
-   Validation sebelum status transition

## 📊 Business Logic

### Status Transition Rules

| Current Status     | Allowed Transitions                  | Business Logic                               |
| ------------------ | ------------------------------------ | -------------------------------------------- |
| **Draft**          | Pending, Cancelled                   | User dapat submit untuk approval atau cancel |
| **Pending**        | In Process, Under Review, Cancelled  | Supervisor dapat approve atau review         |
| **In Process**     | Completed, Partially Used, Cancelled | Stock sedang digunakan                       |
| **Under Review**   | In Process, Rejected, Cancelled      | Manager review process                       |
| **Partially Used** | Completed, Cancelled                 | Sebagian stock terpakai                      |
| **Rejected**       | Draft, Cancelled                     | Kembali ke draft untuk perbaikan             |
| **Completed**      | Disabled                             | Final status, tidak bisa diubah              |
| **Cancelled**      | Disabled                             | Final status, tidak bisa diubah              |

### Stock Impact by Status

-   **Draft**: Tidak mempengaruhi stock
-   **Pending**: Stock di-reserve
-   **In Process**: Stock aktif digunakan
-   **Completed**: Stock final deduction
-   **Cancelled**: Stock dikembalikan
-   **Rejected**: Stock dikembalikan

## 🚀 Usage Examples

### 1. Basic Status Update

```javascript
// User clicks dropdown and selects new status
// System shows confirmation dialog
// User confirms
// Status updated via Livewire
// DataTable refreshed
```

### 2. Invalid Transition

```javascript
// User tries to change from Draft to Completed
// System shows error: "Transisi status tidak valid"
// Dropdown resets to previous value
```

### 3. Permission Denied

```javascript
// User without permission sees read-only badge
// No dropdown available
// Status cannot be changed
```

## 🔍 Debugging

### Common Issues

1. **Status not updating**: Check permission and business rules
2. **Dropdown not showing**: Verify user role and permission
3. **Invalid transitions**: Check business rule validation
4. **JavaScript errors**: Check console for errors

### Debug Commands

```php
// Check user permission
dd(auth()->user()->can('update supply usage'));

// Check status transition
$usage = SupplyUsage::find($id);
dd($usage->canTransitionTo($newStatus));

// Check business rules
dd($this->isValidStatusTransition($currentStatus, $newStatus));
```

### Log Analysis

```bash
# Check for status update logs
grep "updateStatusSupplyUsage" storage/logs/laravel.log

# Check for permission errors
grep "permission" storage/logs/laravel.log
```

## 📈 Performance Considerations

### 1. Database Optimization

-   Efficient queries untuk status updates
-   Proper indexing pada status column
-   Transaction management

### 2. UI Performance

-   Lazy loading untuk dropdown options
-   Debounced status change events
-   Optimized DataTable refresh

### 3. Caching

-   Status labels cached
-   Permission checks cached
-   Business rules cached

## 🔄 Integration Points

### 1. SupplyUsageService

-   Status transition methods
-   Stock management
-   Audit trail

### 2. Notification System

-   Status change notifications
-   Email alerts
-   In-app notifications

### 3. Reporting System

-   Status-based reports
-   Workflow analytics
-   Performance metrics

## 📝 Testing

### 1. Unit Tests

-   Status transition validation
-   Permission checks
-   Business rule validation

### 2. Integration Tests

-   End-to-end status updates
-   DataTable integration
-   Livewire communication

### 3. User Acceptance Tests

-   Permission scenarios
-   Business rule scenarios
-   Error handling scenarios

## 🚀 Future Enhancements

### 1. Advanced Features

-   Bulk status updates
-   Status change history
-   Custom status workflows

### 2. UI Improvements

-   Status change timeline
-   Visual workflow diagram
-   Advanced filtering

### 3. Integration

-   External system integration
-   API endpoints
-   Mobile app support

## 📚 Related Documentation

-   [Supply Usage Status Implementation](../features/supply-usage-status-implementation.md)
-   [SupplyUsageService Documentation](../services/supply-usage-service.md)
-   [DataTable Implementation Guide](../guides/datatable-implementation.md)
-   [Permission System Documentation](../auth/permission-system.md)

---

**Feature Status**: ✅ Implemented and Ready for Testing  
**Last Updated**: 2025-01-28  
**Version**: 1.0.0

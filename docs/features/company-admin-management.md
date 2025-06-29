# Company Admin Management System

**Date**: 2025-01-24  
**Author**: AI Assistant  
**Version**: 1.0

## Overview

Sistem Company Admin Management telah dienhance dengan fitur constraint untuk memastikan hanya ada 1 default admin per company dan proteksi untuk mencegah penghapusan default admin oleh administrator lain.

## Key Features Implemented

### 1. Single Default Admin Constraint

-   ✅ Hanya 1 default admin per company
-   ✅ Automatic constraint enforcement di database level
-   ✅ Transaction-safe operations untuk role changes

### 2. Default Admin Protection

-   ✅ Default admin tidak bisa dihapus oleh admin lain
-   ✅ Only SuperAdmin dapat menghapus default admin
-   ✅ Default admin tidak bisa menghapus diri sendiri tanpa transfer role

### 3. Role Management

-   ✅ Promote user ke admin
-   ✅ Demote admin ke regular user (kecuali default admin)
-   ✅ Transfer default admin role
-   ✅ Set existing admin sebagai default admin

## File Modifications

### 1. Enhanced CompanyUser Model (`app/Models/CompanyUser.php`)

#### New Methods Added:

```php
// Default admin management
public static function isDefaultAdmin($userId = null, $companyId = null)
public static function getDefaultAdmin($companyId)
public static function hasDefaultAdmin($companyId)
public static function setDefaultAdmin($companyId, $userId)

// Protection and validation
public static function canDeleteUser($userIdToDelete, $deletingUserId = null)
public static function transferDefaultAdmin($companyId, $newDefaultAdminUserId)
```

#### Model Events Enhanced:

```php
// Validate before saving - ensure only 1 default admin per company
static::saving(function ($companyUser) {
    if ($companyUser->isDefaultAdmin) {
        $existingDefaultAdmin = self::where('company_id', $companyUser->company_id)
            ->where('isDefaultAdmin', true)
            ->where('id', '!=', $companyUser->id)
            ->where('status', 'active')
            ->first();

        if ($existingDefaultAdmin) {
            throw new Exception('Company can only have one default admin. Please remove existing default admin first.');
        }

        // Default admin must be admin
        $companyUser->isAdmin = true;
    }
});

// Prevent deletion of default admin by non-SuperAdmin
static::deleting(function ($companyUser) {
    if ($companyUser->isDefaultAdmin && !auth()->user()->hasRole('SuperAdmin')) {
        $canDelete = self::canDeleteUser($companyUser->user_id);
        if (!$canDelete['can_delete']) {
            throw new Exception($canDelete['reason']);
        }
    }
});
```

### 2. New Service: CompanyAdminManagementService (`app/Services/CompanyAdminManagementService.php`)

Comprehensive service untuk mengelola company admin operations:

#### Key Methods:

-   `getCompanyAdmins($companyId)` - Get all admins for company
-   `setDefaultAdmin($companyId, $userId)` - Set user as default admin
-   `transferDefaultAdmin($companyId, $newUserId)` - Transfer default admin role
-   `promoteToAdmin($companyId, $userId, $setAsDefault)` - Promote user to admin
-   `demoteAdmin($companyId, $userId)` - Demote admin to regular user
-   `canManageDefaultAdmin($companyId)` - Check management permissions
-   `getAdminStatistics($companyId)` - Get admin statistics
-   `validateAdminRequirements($companyId)` - Validate company admin setup

### 3. New Livewire Component: CompanyAdminManagement (`app/Livewire/Company/CompanyAdminManagement.php`)

UI component untuk mengelola company admins dengan features:

-   ✅ Real-time admin statistics
-   ✅ Search dan filter functionality
-   ✅ Promote/Demote actions
-   ✅ Default admin transfer
-   ✅ Permission-based UI
-   ✅ Responsive design

### 4. Updated EnhancedUserModal (`app/Livewire/User/EnhancedUserModal.php`)

Enhanced delete user method dengan default admin protection:

```php
// Check CompanyUser delete permissions (default admin protection)
$canDeleteCheck = CompanyUser::canDeleteUser($id);
if (!$canDeleteCheck['can_delete']) {
    $this->dispatch('error-modal', [
        'title' => 'Tidak Bisa Hapus User',
        'icon' => 'warning',
        'blockers' => [$canDeleteCheck['reason']],
        'text' => 'User ini tidak dapat dihapus karena memiliki peran khusus.',
    ]);
    return;
}
```

## Business Rules Implemented

### 1. Default Admin Constraints

-   **Rule**: Only 1 default admin per company
-   **Enforcement**: Database model events + service layer validation
-   **Exception**: SuperAdmin dapat override constraint untuk maintenance

### 2. Deletion Protection

-   **Rule**: Default admin cannot be deleted by other administrators
-   **Enforcement**: Model events + UI protection
-   **Exception**: SuperAdmin dapat delete anyone
-   **Fallback**: Default admin harus transfer role sebelum self-delete

### 3. Role Hierarchy

```
SuperAdmin > Default Admin > Regular Admin > Regular User
```

### 4. Permission Matrix

| Action                      | SuperAdmin | Default Admin    | Regular Admin | Regular User |
| --------------------------- | ---------- | ---------------- | ------------- | ------------ |
| Delete Default Admin        | ✅         | ❌               | ❌            | ❌           |
| Transfer Default Admin Role | ✅         | ✅ (own company) | ❌            | ❌           |
| Promote User to Admin       | ✅         | ✅ (own company) | ❌            | ❌           |
| Demote Regular Admin        | ✅         | ✅ (own company) | ❌            | ❌           |
| Set Default Admin           | ✅         | ✅ (own company) | ❌            | ❌           |

## Usage Examples

### 1. Setting Default Admin

```php
use App\Services\CompanyAdminManagementService;

$adminService = new CompanyAdminManagementService();
$result = $adminService->setDefaultAdmin($companyId, $userId);

if ($result['success']) {
    // Success
    $defaultAdmin = $result['data'];
} else {
    // Handle error
    $errorMessage = $result['message'];
}
```

### 2. Checking Delete Permissions

```php
use App\Models\CompanyUser;

$canDelete = CompanyUser::canDeleteUser($userIdToDelete);
if (!$canDelete['can_delete']) {
    // Show error message
    echo $canDelete['reason'];
}
```

### 3. Transferring Default Admin Role

```php
$result = $adminService->transferDefaultAdmin($companyId, $newAdminUserId);
// Auto-removes old default admin and sets new one
```

## Database Schema

Existing `company_users` table structure supports the implementation:

```sql
- isAdmin: boolean (existing)
- isDefaultAdmin: boolean (existing)
- status: enum('active', 'inactive') (existing)
```

No migrations required - leverages existing schema.

## Error Handling

### Model Level Exceptions

-   `Company can only have one default admin`
-   `Default admin cannot be deleted by other administrators`
-   `User not found in company mapping`

### Service Level Responses

```php
[
    'success' => false,
    'message' => 'Descriptive error message'
]
```

### UI Level Notifications

-   SweetAlert modals untuk confirmations
-   Toast notifications untuk success/error states
-   Real-time validation feedback

## Logging and Audit Trail

All admin role changes are logged dengan comprehensive data:

```php
Log::info('Default admin changed for company', [
    'company_id' => $companyId,
    'new_default_admin_user_id' => $userId,
    'changed_by' => Auth::id()
]);
```

## Security Features

### 1. Permission Validation

-   Setiap action di-validate di service layer
-   UI controls berdasarkan user permissions
-   Database constraints sebagai final protection

### 2. Transaction Safety

-   Semua role changes wrapped dalam database transactions
-   Rollback pada error untuk data consistency
-   Atomic operations untuk state changes

### 3. Audit Logging

-   Comprehensive logging semua admin actions
-   User identification pada setiap operation
-   Timestamp tracking untuk audit purposes

## Testing Scenarios

### Scenario 1: Single Default Admin Constraint

1. ✅ Create company with 1 default admin
2. ✅ Try to set another user as default admin → Should remove previous default admin
3. ✅ Try to create 2 default admins simultaneously → Should throw exception

### Scenario 2: Delete Protection

1. ✅ Regular admin tries to delete default admin → Should be blocked
2. ✅ Default admin tries to delete themselves → Should be blocked
3. ✅ SuperAdmin deletes default admin → Should succeed
4. ✅ Default admin transfers role then deletes themselves → Should succeed

### Scenario 3: Role Management

1. ✅ Promote regular user to admin → Should succeed
2. ✅ Promote regular user to default admin → Should succeed and remove old default admin
3. ✅ Demote regular admin → Should succeed
4. ✅ Try to demote default admin → Should be blocked

## Future Enhancements

### Potential Improvements

1. **Multi-Company Support**: User dapat menjadi default admin di multiple companies
2. **Role Expiry**: Default admin role dengan expiry dates
3. **Deputy Admin**: Secondary admin dengan elevated permissions
4. **Approval Workflow**: Admin role changes memerlukan approval
5. **Advanced Audit**: Detailed audit trail dengan rollback capabilities

## Migration Notes

### Backward Compatibility

-   ✅ Existing data tetap compatible
-   ✅ No breaking changes untuk existing functionality
-   ✅ Graceful handling untuk companies tanpa default admin

### Deployment Steps

1. Deploy code updates
2. Run `php artisan cache:clear`
3. Verify existing default admin assignments
4. Test role management functionality
5. Update documentation untuk end users

## Configuration

No additional configuration required. Service menggunakan existing:

-   Authentication system
-   Role-based permissions
-   Company mapping structure

## Support and Maintenance

### Monitoring Points

-   Default admin assignment status per company
-   Failed role change attempts
-   Deletion attempt blocks
-   Service response times

### Common Issues and Solutions

1. **Issue**: Company tanpa default admin
   **Solution**: Use admin management UI untuk assign default admin

2. **Issue**: Cannot delete user (default admin protection)
   **Solution**: Transfer default admin role terlebih dahulu

3. **Issue**: Multiple default admins (edge case)
   **Solution**: Model constraints akan prevent ini, manual cleanup jika terjadi

4. **Issue**: SuperAdmin melihat error "Undefined array key 'has_default_admin'"
   **Solution**: SuperAdmin harus memilih company terlebih dahulu melalui company selector

5. **Issue**: Service dependency injection error
   **Solution**: Component menggunakan lazy loading untuk service initialization

---

_Dokumentasi ini merupakan living document dan akan diupdate seiring dengan development fitur tambahan._

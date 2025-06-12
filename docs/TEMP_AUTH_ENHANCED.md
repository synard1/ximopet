# 🔐 Enhanced Temporary Authorization System

## 📋 Overview

Sistem **Enhanced Temporary Authorization** adalah pengembangan dari fitur temporary authorization sebelumnya yang kini mendukung multiple authorization modes:

-   **Password-based Authorization**: Autorisasi menggunakan password khusus
-   **User-based Authorization**: Autorisasi melalui user lain yang memiliki hak
-   **Mixed Mode**: Kombinasi kedua mode di atas

## 🏗️ Architecture

### Models

1. **TempAuthAuthorizer**: Menyimpan data user yang memiliki hak memberikan autorisasi
2. **TempAuthLog**: Audit trail untuk semua aktivitas temporary authorization
3. **User**: Extended dengan methods untuk temp authorization

### Configuration Modes

```php
// config/temp_auth.php
'mode' => 'mixed', // password, user, mixed
```

## 🚀 Installation & Setup

### 1. Run Migrations

```bash
php artisan migrate --path=database/migrations/2025_06_09_024940_create_temp_auth_authorizers_table.php
php artisan migrate --path=database/migrations/2025_06_09_025033_create_temp_auth_logs_table.php
```

### 2. Seed Demo Data

```bash
php artisan db:seed --class=TempAuthSeeder
```

Demo users yang dibuat:

-   **manager@demo.com** (password: password) - Role: Manager
-   **superadmin@demo.com** (password: password) - Role: Super Admin

### 3. Configuration

Update `.env` file:

```env
# Authorization Mode
TEMP_AUTH_MODE=mixed

# Password-based Auth
TEMP_AUTH_PASSWORD_ENABLED=true
TEMP_AUTH_PASSWORD=admin123

# User-based Auth
TEMP_AUTH_USER_ENABLED=true
TEMP_AUTH_USER_REQUIRE_PASSWORD=true
TEMP_AUTH_USER_METHOD=role

# Audit & Logging
TEMP_AUTH_AUDIT_ENABLED=true
TEMP_AUTH_STORE_DB=true
```

## 🎯 Features

### 1. Multiple Authorization Modes

#### Password Mode

-   User memasukkan password khusus untuk mendapatkan autorisasi
-   Password dikonfigurasi di config/env
-   Cocok untuk environment sederhana

#### User Mode

-   User memasukkan email/username authorizer secara manual
-   Authorizer memasukkan password login mereka
-   Mendukung role-based dan permission-based authorization
-   Audit trail lengkap dengan informasi authorizer
-   **Security Enhancement**: Tidak expose daftar user yang memiliki hak autorisasi

#### Mixed Mode

-   User dapat memilih antara password atau user authorization
-   Fleksibilitas maksimal untuk berbagai skenario

### 2. User Authorization Management

#### Via Database (TempAuthAuthorizer)

```php
TempAuthAuthorizer::create([
    'user_id' => $userId,
    'authorized_by' => $authorizerId,
    'is_active' => true,
    'max_authorization_duration' => 60, // minutes
    'allowed_components' => ['Create', 'Edit'],
    'expires_at' => now()->addMonths(6),
]);
```

#### Via Role/Permission

```php
// Config: temp_auth.user.authorized_roles
'authorized_roles' => [
    'Super Admin',
    'Manager',
    'Supervisor',
]

// Config: temp_auth.user.authorized_permissions
'authorized_permissions' => [
    'grant temp authorization',
    'override data locks',
]
```

### 3. Comprehensive Audit Trail

Semua aktivitas dicatat di `temp_auth_logs`:

-   User yang mendapat autorisasi
-   User yang memberikan autorisasi (jika user mode)
-   Method autorisasi (password/user/role/permission)
-   Komponen yang diautorisasi
-   Alasan autorisasi
-   IP address dan user agent
-   Timestamps lengkap

### 4. Management Commands

```bash
# List all authorizers
php artisan temp-auth:manage list

# Grant authorization to user
php artisan temp-auth:manage grant --user=user@example.com --authorizer=admin@example.com

# Revoke authorization
php artisan temp-auth:manage revoke --user=user@example.com

# Cleanup expired authorizations
php artisan temp-auth:manage cleanup
```

## 🔧 Usage

### 1. Basic Implementation

```php
class YourComponent extends Component
{
    use HasTempAuthorization;

    public function mount()
    {
        $this->initializeTempAuth();
    }

    public function isReadonly()
    {
        if ($this->tempAuthEnabled) {
            return false;
        }

        return $this->status === 'locked';
    }
}
```

### 2. View Integration

```blade
<!-- Include component -->
<livewire:temp-authorization />

<!-- Use in form fields -->
<input type="text"
       wire:model="field"
       @if($this->isReadonly()) readonly @endif>

<!-- Authorization button -->
@if(!$this->tempAuthEnabled && $this->isReadonly())
<button wire:click="requestTempAuth('{{ class_basename($this) }}')"
        class="btn btn-warning">
    🔓 Minta Autorisasi
</button>
@endif
```

### 3. User Helper Methods

```php
// Check if user can grant authorization
$user->canGrantTempAuthorization();

// Check for specific component
$user->canAuthorizeTempAccessFor('LivestockPurchase');

// Get active authorizer record
$authorizer = $user->getActiveTempAuthAuthorizer();
```

## 📊 Configuration Options

### Authorization Methods

```php
// config/temp_auth.php
'user' => [
    'authorization_method' => 'role', // role, permission, database_field

    // For role-based
    'authorized_roles' => ['Super Admin', 'Manager'],

    // For permission-based
    'authorized_permissions' => ['grant temp authorization'],

    // For database field
    'database_field' => 'can_authorize_temp_access',
]
```

### Component Restrictions

```php
TempAuthAuthorizer::create([
    'allowed_components' => ['Create', 'Edit', 'LivestockPurchase'],
    // null = all components allowed
]);
```

### Duration Limits

```php
TempAuthAuthorizer::create([
    'max_authorization_duration' => 60, // minutes
    // null = no limit (uses config default)
]);
```

## 🔍 Monitoring & Reporting

### 1. Active Authorizations

```php
// Get all active temp authorizations
$active = TempAuthLog::where('action', 'granted')
    ->whereNull('revoked_at')
    ->whereNull('auto_expired_at')
    ->where('expires_at', '>', now())
    ->get();
```

### 2. Audit Reports

```php
// Authorization activity by date range
$logs = TempAuthLog::dateRange($startDate, $endDate)
    ->with(['user', 'authorizerUser'])
    ->get();

// By specific action
$grants = TempAuthLog::byAction('granted')->get();
$revokes = TempAuthLog::byAction('revoked')->get();
```

### 3. User Activity

```php
// Authorizations received by user
$userAuths = $user->tempAuthLogs()->get();

// Authorizations granted by user
$grantedAuths = $user->givenTempAuthLogs()->get();
```

## 🛡️ Security Features

### 1. Password Validation

-   Authorizer password di-hash dan diverifikasi
-   Support untuk custom password requirements

### 2. Component-level Restrictions

-   Authorizer dapat dibatasi untuk komponen tertentu
-   Granular access control

### 3. Time-based Expiry

-   Authorization otomatis expire sesuai durasi
-   Cleanup command untuk expired records

### 4. Comprehensive Logging

-   IP address dan user agent tracking
-   Metadata lengkap untuk forensic analysis

### 5. Privacy Protection ⭐ **ENHANCED**

-   **No User Enumeration**: Sistem tidak menampilkan daftar user yang memiliki hak autorisasi
-   **Manual Entry**: User harus mengetahui email/username authorizer untuk menggunakan user mode
-   **Reduced Attack Surface**: Mengurangi exposure informasi sensitive tentang system administrators

## 🚨 Troubleshooting

### Common Issues

1. **Modal tidak muncul**

    - Check browser console untuk errors
    - Pastikan Livewire events ter-dispatch dengan benar

2. **User tidak bisa memberikan autorisasi**

    - Check role/permission user
    - Verify TempAuthAuthorizer record aktif
    - Check component restrictions

3. **Authorization tidak tersimpan**
    - Check database connection
    - Verify migration sudah dijalankan
    - Check config `temp_auth.audit.store_in_database`

### Debug Commands

```bash
# Check authorizers
php artisan temp-auth:manage list

# Check user roles
php artisan tinker --execute="User::find(1)->roles"

# Check permissions
php artisan tinker --execute="User::find(1)->permissions"
```

## 📈 Performance Considerations

### 1. Database Indexes

-   Indexes sudah dibuat untuk query performance
-   Regular cleanup untuk old logs

### 2. Caching

-   Consider caching user permissions/roles
-   Cache available authorizers list

### 3. Cleanup Strategy

```bash
# Schedule cleanup command
# In app/Console/Kernel.php
$schedule->command('temp-auth:manage cleanup')->daily();
```

## 🔄 Migration from Simple Version

Jika upgrade dari versi simple:

1. Backup existing config
2. Run new migrations
3. Update config dengan new structure
4. Test authorization flows
5. Seed demo data untuk testing

## 📞 Support

Untuk issues atau questions:

1. Check dokumentasi ini
2. Review audit logs
3. Use debug commands
4. Check Laravel logs untuk detailed errors

---

**Version**: 2.0 Enhanced  
**Last Updated**: December 2024  
**Compatibility**: Laravel 10.x, Livewire 3.x
 
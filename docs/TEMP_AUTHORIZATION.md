# Fitur Autorisasi Temporer

## Deskripsi

Fitur autorisasi temporer memungkinkan pengguna untuk mengubah data yang statusnya sudah di-lock atau readonly dengan memberikan autorisasi khusus yang bersifat sementara.

## Fitur Utama

### 1. Autorisasi Berbasis Waktu

-   Autorisasi berlaku untuk durasi tertentu (default: 30 menit)
-   Otomatis expired setelah waktu habis
-   Dapat dicabut secara manual

### 2. Keamanan

-   Memerlukan password khusus
-   Wajib memberikan alasan autorisasi
-   Audit trail untuk semua aktivitas
-   Role-based access control

### 3. User Experience

-   Modal dialog yang user-friendly
-   Status indicator real-time
-   Countdown timer
-   Notifikasi visual

## Implementasi

### 1. Komponen Livewire

```php
// Include di halaman yang memerlukan
<livewire:temp-authorization />
```

### 2. Trait untuk Komponen

```php
use App\Traits\HasTempAuthorization;

class YourComponent extends Component
{
    use HasTempAuthorization;

    public function mount()
    {
        $this->initializeTempAuth();
    }

    public function isReadonly()
    {
        // If temp auth is enabled, not readonly
        if ($this->tempAuthEnabled) {
            return false;
        }

        // Check local conditions
        return $this->status === 'locked';
    }
}
```

### 3. View Implementation

```blade
@if($this->isReadonly() && !$tempAuthEnabled)
    <div class="alert alert-warning">
        <div>Data dalam mode readonly</div>
        @if($this->canRequestTempAuth())
            <button wire:click="requestTempAuth">Minta Autorisasi</button>
        @endif
    </div>
@endif

<input type="text" @if($this->isReadonly()) readonly @endif>
```

## Konfigurasi

### Environment Variables

```env
TEMP_AUTH_DURATION=30
TEMP_AUTH_PASSWORD=your_secure_password
TEMP_AUTH_LOG=true
TEMP_AUTH_REQUIRE_REASON=true
TEMP_AUTH_AUDIT=true
```

### Config File

File: `config/temp_auth.php`

```php
return [
    'default_duration' => env('TEMP_AUTH_DURATION', 30),
    'default_password' => env('TEMP_AUTH_PASSWORD', 'admin123'),
    'allowed_roles' => ['Admin', 'Supervisor'],
    'bypass_permissions' => ['super admin', 'override temp auth'],
    // ... other configs
];
```

## Penggunaan

### 1. Request Autorisasi

1. User melihat data yang di-lock
2. Klik tombol "Minta Autorisasi"
3. Modal muncul meminta password dan alasan
4. Setelah valid, autorisasi diberikan

### 2. Status Autorisasi

-   Indicator visual menunjukkan status aktif
-   Countdown timer menunjukkan sisa waktu
-   Dapat dicabut manual sebelum expired

### 3. Audit Trail

-   Semua aktivitas tercatat
-   Informasi user, waktu, alasan
-   Log untuk monitoring dan compliance

## Security Features

### 1. Role-Based Access

-   Hanya role tertentu yang dapat request
-   Permission bypass untuk super admin
-   Configurable role list

### 2. Session Management

-   Autorisasi disimpan di session
-   Auto cleanup expired sessions
-   Maximum concurrent authorizations

### 3. Audit & Monitoring

-   Complete audit trail
-   Notification system (optional)
-   Activity logging

## Commands

### Cleanup Expired Sessions

```bash
php artisan temp-auth:cleanup
php artisan temp-auth:cleanup --force
```

## API Events

### Livewire Events

-   `requestTempAuth` - Request authorization
-   `tempAuthGranted` - Authorization granted
-   `tempAuthRevoked` - Authorization revoked

### JavaScript Events

```javascript
Livewire.on("tempAuthGranted", function (data) {
    // Handle authorization granted
});

Livewire.on("tempAuthRevoked", function () {
    // Handle authorization revoked
});
```

## Best Practices

### 1. Security

-   Gunakan password yang kuat
-   Set durasi sesuai kebutuhan
-   Monitor audit trail secara berkala
-   Limit concurrent authorizations

### 2. User Experience

-   Berikan feedback yang jelas
-   Tampilkan countdown timer
-   Notifikasi sebelum expired
-   Easy access untuk request

### 3. Development

-   Gunakan trait untuk konsistensi
-   Implement proper error handling
-   Test authorization flow
-   Document custom implementations

## Troubleshooting

### Common Issues

1. **Authorization tidak bekerja**

    - Check session configuration
    - Verify user roles/permissions
    - Check config values

2. **Modal tidak muncul**

    - Ensure component included
    - Check JavaScript console
    - Verify Livewire events

3. **Session expired terlalu cepat**
    - Check session lifetime
    - Verify timezone settings
    - Check server time

### Debug Commands

```bash
# Check session files
ls -la storage/framework/sessions/

# Check config
php artisan config:show temp_auth

# Clear sessions
php artisan session:clear
```

## Migration Guide

### From Manual Implementation

1. Replace manual checks dengan trait
2. Update views dengan helper methods
3. Configure environment variables
4. Test authorization flow

### Adding to Existing Components

1. Add trait to component
2. Call `initializeTempAuth()` in mount
3. Replace readonly conditions
4. Include component in view

## Important Notes

### Method Implementation

Setiap komponen yang menggunakan trait harus mengimplementasikan method `isReadonly()` dan `isDisabled()` sendiri untuk menghindari konflik dengan parent class. Trait menyediakan helper methods `checkIsReadonly()` dan `checkIsDisabled()` yang dapat digunakan jika diperlukan.

### Example Pattern

```php
public function isReadonly()
{
    // If temp auth is enabled, not readonly
    if ($this->tempAuthEnabled) {
        return false;
    }

    // Check your specific conditions
    return $this->status === 'locked' || $this->edit_mode;
}
```

## Examples

### Basic Implementation

```php
class EditForm extends Component
{
    use HasTempAuthorization;

    public $status = 'locked';

    public function mount()
    {
        $this->initializeTempAuth();
    }

    public function isReadonly()
    {
        // Use helper method from trait
        return $this->checkIsReadonly([
            $this->status === 'locked'
        ]);
    }
}
```

### Advanced Implementation

```php
class AdvancedForm extends Component
{
    use HasTempAuthorization;

    public function canEdit()
    {
        return $this->tempAuthEnabled ||
               auth()->user()->can('override locks');
    }

    public function save()
    {
        if (!$this->canEdit()) {
            $this->requestTempAuth('Save Operation');
            return;
        }

        // Proceed with save
    }
}
```

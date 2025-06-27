# Production Deployment Fix Documentation

## Overview

Dokumentasi ini menjelaskan perbaikan yang dilakukan untuk mengatasi error saat menjalankan `composer install --no-dev` di environment production.

## Issues yang Diperbaiki

### 1. Service Provider Error

**Error:**

```
In TelescopeServiceProvider.php line 11:
  Class "Laravel\Telescope\TelescopeApplicationServiceProvider" not found
```

**Penyebab:**

-   Service provider development (Telescope, Pulse) tetap didaftarkan di `config/app.php` meski package tidak diinstall di production.
-   `composer install --no-dev` tidak menginstall package di `require-dev`, sehingga class tidak tersedia.

**Solusi:**

-   Mengubah `config/app.php` untuk menggunakan environment-based service providers.
-   Service provider development hanya didaftarkan saat `APP_ENV=local`.

**Implementasi:**

```php
'providers' => array_merge([
    // Core providers...
], env('APP_ENV') === 'local' ? [
    App\Providers\TelescopeServiceProvider::class,
    App\Providers\PulseServiceProvider::class,
] : []),
```

### 2. PSR-4 Autoloading Error

**Error:**

```
Class App\DataTables\CoopsDataTable located in ./app/DataTables/KandangsDataTable.php does not comply with psr-4 autoloading standard
```

**Penyebab:**

-   Nama class tidak sesuai dengan nama file (PSR-4 violation).
-   File backup/copy yang tidak sesuai namespace.

**Solusi:**

-   Mengubah nama class dari `CoopsDataTable` menjadi `KandangsDataTable` di file `app/DataTables/KandangsDataTable.php`.
-   Menghapus file backup/copy yang tidak sesuai PSR-4.

**Files yang Dihapus:**

-   `app/Livewire/Records_backup_20250123.php`
-   `app/Livewire/Records copy 3.php`
-   `app/Livewire/Records copy 2.php`
-   `app/Livewire/Records copy.php`
-   `app/Livewire/Records_backup_20250623_ 24654.php`
-   `app/Livewire/Records copy.php.backup`
-   `app/Livewire/Records.php.backup`

### 3. Config Pulse Error

**Error:**

```
Class "Laravel\Pulse\Pulse" not found in config/pulse.php line 140
```

**Penyebab:**

-   Config file menggunakan class Pulse yang tidak tersedia di production.

**Solusi:**

-   Menambahkan pengecekan `class_exists()` sebelum menggunakan class Pulse.
-   Menggunakan fallback values saat class tidak tersedia.

## Files yang Dimodifikasi

### 1. config/app.php

-   Mengubah providers array untuk environment-based loading
-   Menambahkan conditional loading untuk development service providers

### 2. app/DataTables/KandangsDataTable.php

-   Mengubah nama class dari `CoopsDataTable` menjadi `KandangsDataTable`

### 3. config/pulse.php

-   Menambahkan pengecekan class availability
-   Menggunakan fallback values untuk production environment

## Testing

### Test Scripts

1. **test_production_ready.php** - Comprehensive production readiness test
2. **test_pulse_config.php** - Pulse configuration test

### Manual Testing

```bash
# Test production environment
APP_ENV=production APP_DEBUG=false php artisan config:clear

# Test local environment
APP_ENV=local APP_DEBUG=true php artisan config:clear

# Test composer install --no-dev
composer install --no-dev
```

## Deployment Workflow

### Production Deployment

```bash
# 1. Install dependencies without dev packages
composer install --no-dev

# 2. Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# 3. Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 4. Run migrations
php artisan migrate --force
```

### Development Environment

```bash
# 1. Install all dependencies including dev packages
composer install

# 2. Publish development package assets
php artisan vendor:publish --tag=telescope-assets --force
php artisan vendor:publish --tag=pulse-assets --force

# 3. Clear caches
php artisan config:clear
php artisan cache:clear
```

## Environment Variables

### Production (.env)

```env
APP_ENV=production
APP_DEBUG=false
```

### Development (.env)

```env
APP_ENV=local
APP_DEBUG=true
```

## Security Considerations

### 1. Development Packages Isolation

-   Telescope dan Pulse hanya aktif di local environment
-   Tidak ada exposure development tools di production
-   Service providers tidak di-load di production

### 2. Error Prevention

-   Graceful degradation saat package tidak tersedia
-   Environment-based configuration loading
-   Proper fallback mechanisms

## Monitoring & Maintenance

### 1. Regular Checks

-   Monitor composer install logs
-   Check for new backup/copy files
-   Verify PSR-4 compliance

### 2. Automated Testing

-   Run `test_production_ready.php` before deployment
-   Check service provider loading
-   Verify config file integrity

## Troubleshooting

### Common Issues

1. **Service Provider Still Loading in Production**

    - Check `APP_ENV` environment variable
    - Verify config/app.php conditional logic
    - Clear config cache

2. **PSR-4 Errors**

    - Check for backup/copy files
    - Verify class names match file names
    - Run `composer dump-autoload`

3. **Config Loading Errors**
    - Check for hardcoded class references
    - Verify environment-based conditionals
    - Test config files individually

### Debug Commands

```bash
# Check environment
php artisan env:packages status

# Test production readiness
php test_production_ready.php

# Check autoloading
composer dump-autoload

# Clear all caches
php artisan optimize:clear
```

## Best Practices

1. **Always use environment-based configuration**
2. **Keep development packages in require-dev**
3. **Use --no-dev flag in production**
4. **Regular cleanup of backup/copy files**
5. **Test both environments before deployment**
6. **Monitor composer install logs**
7. **Use proper PSR-4 naming conventions**

## Future Improvements

1. **Automated PSR-4 compliance checking**
2. **Backup file detection and cleanup**
3. **Environment-specific composer scripts**
4. **Automated production readiness testing**
5. **Enhanced error reporting and logging**

## Conclusion

Dengan perbaikan ini, aplikasi sekarang dapat:

-   Berjalan dengan aman di production environment
-   Menggunakan `composer install --no-dev` tanpa error
-   Memisahkan development dan production dependencies
-   Mematuhi standar PSR-4 autoloading
-   Menyediakan graceful degradation untuk missing packages

Semua perubahan bersifat backward compatible dan tidak mempengaruhi fungsionalitas development environment.

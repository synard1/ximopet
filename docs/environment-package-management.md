# Environment-Based Package Management

## Overview

Sistem ini memungkinkan Laravel Pulse dan Telescope hanya diinstall dan aktif saat environment local, dengan kontrol yang ketat untuk production environment.

## Architecture

### 1. Service Providers

-   **PulseServiceProvider**: Mengontrol registrasi Laravel Pulse
-   **TelescopeServiceProvider**: Mengontrol registrasi Laravel Telescope
-   **EnvironmentHelper**: Helper class untuk environment checks

### 2. Environment Control

-   Package hanya aktif saat `APP_ENV=local` dan `APP_DEBUG=true`
-   Service providers melakukan environment check sebelum registrasi
-   Graceful degradation di production environment

## Implementation Details

### EnvironmentHelper Class

```php
// Check if development packages should be loaded
EnvironmentHelper::shouldLoadDevPackages()

// Check specific package
EnvironmentHelper::shouldLoadPackage('laravel/telescope')
```

### Service Provider Pattern

```php
// PulseServiceProvider
if (EnvironmentHelper::shouldLoadPackage('laravel/pulse')) {
    $this->app->register(\Laravel\Pulse\PulseServiceProvider::class);
}

// TelescopeServiceProvider
if (!EnvironmentHelper::shouldLoadPackage('laravel/telescope')) {
    return; // Early return if not local
}
```

## Usage

### 1. Environment Setup

```bash
# Local Development (.env)
APP_ENV=local
APP_DEBUG=true

# Production (.env)
APP_ENV=production
APP_DEBUG=false
```

### 2. Package Installation

```bash
# Install all development packages
composer install

# Check package status
php artisan env:packages status

# Publish assets for local environment
php artisan env:packages publish
```

### 3. Manual Control

```bash
# Force installation in production (not recommended)
php artisan env:packages install --force

# Check current environment status
php artisan env:packages status
```

## Configuration

### Composer.json Scripts

```json
{
    "scripts": {
        "post-install-cmd": [
            "@php artisan vendor:publish --tag=telescope-assets --ansi --force",
            "@php artisan vendor:publish --tag=pulse-assets --ansi --force"
        ]
    }
}
```

### Environment Variables

```env
# Telescope Configuration
TELESCOPE_ENABLED=true
TELESCOPE_PATH=telescope

# Pulse Configuration
PULSE_ENABLED=true
PULSE_PATH=pulse
```

## Security Considerations

### 1. Production Safety

-   Service providers tidak akan registrasi di production
-   Gate definitions hanya aktif di local environment
-   Sensitive data protection di non-local environments

### 2. Access Control

```php
// Telescope Gate (only in local)
Gate::define('viewTelescope', function ($user) {
    return in_array($user->email, [
        // Add authorized emails here
    ]);
});

// Pulse Gate (only in local)
Gate::define('viewPulse', function ($user = null) {
    return true; // Allow all in local
});
```

## Monitoring & Debugging

### 1. Status Check

```bash
php artisan env:packages status
```

Output:

```
Environment Package Manager Status
==================================
Environment: local
Debug Mode: enabled
Dev Packages Enabled: yes

Development Packages:
  ✓ laravel/telescope
  ✓ laravel/pulse
  ✓ barryvdh/laravel-debugbar
  ✓ nunomaduro/collision
  ✓ spatie/laravel-ignition
```

### 2. Logging

-   Service provider registrations logged
-   Environment checks logged
-   Package loading status tracked

## Deployment Workflow

### 1. Development

```bash
# Local environment
composer install
php artisan env:packages publish
php artisan migrate
```

### 2. Production

```bash
# Production environment
composer install --no-dev  # Exclude dev dependencies
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Troubleshooting

### Common Issues

1. **Packages not loading in local**

    - Check `APP_ENV=local`
    - Check `APP_DEBUG=true`
    - Verify service provider registration

2. **Packages loading in production**

    - Verify environment variables
    - Check service provider logic
    - Review composer.json scripts

3. **Assets not published**
    - Run `php artisan env:packages publish`
    - Check file permissions
    - Verify package installation

### Debug Commands

```bash
# Check environment
php artisan env:packages status

# Force asset publishing
php artisan vendor:publish --tag=telescope-assets --force
php artisan vendor:publish --tag=pulse-assets --force

# Clear caches
php artisan config:clear
php artisan cache:clear
```

## Best Practices

1. **Always check environment before package operations**
2. **Use EnvironmentHelper for consistent checks**
3. **Test both local and production environments**
4. **Monitor package loading in logs**
5. **Keep development packages in require-dev**
6. **Use --no-dev flag in production composer install**

## Future Enhancements

1. **Package-specific configuration files**
2. **Automated environment detection**
3. **Package dependency management**
4. **Performance monitoring integration**
5. **Custom package loading strategies**

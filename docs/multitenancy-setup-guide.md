# Laravel Multitenancy Setup Guide

**Dibuat:** 26 Juni 2025  
**Dokumentasi:** Setup Laravel Multitenancy menggunakan Spatie Package

## ✅ Langkah yang Sudah Selesai

### 1. Konfigurasi Database Connections

File: `config/database.php`

```php
'connections' => [
    'tenant' => [
        'driver' => 'mysql',
        'database' => null, // Akan diset dinamis
        'host' => '127.0.0.1',
        'username' => 'root',
        'password' => '',
    ],

    'landlord' => [
        'driver' => 'mysql',
        'database' => 'landlord_demo51',
        'host' => '127.0.0.1',
        'username' => 'root',
        'password' => '',
    ],
],
```

### 2. Konfigurasi Multitenancy Config

File: `config/multitenancy.php`

-   ✅ `tenant_database_connection_name` = 'tenant'
-   ✅ `landlord_database_connection_name` = 'landlord'
-   ✅ Switch tenant tasks diaktifkan:
    -   `PrefixCacheTask`
    -   `SwitchTenantDatabaseTask`
-   ✅ Tenant finder setup: `DomainTenantFinder`

### 3. Migrasi Landlord Database

-   ✅ Database landlord berhasil dibuat: `landlord_demo51`
-   ✅ Tabel `tenants` berhasil dibuat dengan structure:
    -   `id`, `name`, `domain`, `database`, `timestamps`

### 4. Tenant Record & Database

-   ✅ Tenant record dibuat:
    -   Name: 'demo51_tenant'
    -   Domain: 'demo51.local'
    -   Database: 'demo51_tenant_db'
-   ✅ Database tenant dibuat: `demo51_tenant_db`

### 5. Model Connection Traits

-   ✅ `User` model: ditambahkan `UsesLandlordConnection`
-   ✅ `Company` model: ditambahkan `UsesLandlordConnection`
-   ✅ `Livestock` model: ditambahkan `UsesTenantConnection`

### 6. Tenant Finder Implementation

File: `app/TenantFinder/DomainTenantFinder.php`

-   ✅ Domain-based tenant detection
-   ✅ Support untuk development local
-   ✅ Fallback mechanism

## 🔄 Langkah yang Masih Diperlukan

### 1. Migration Tenant Database

```bash
# Jalankan migrasi untuk tenant (skip telescope conflicts)
php artisan tenants:artisan "migrate --database=tenant --except=telescope"

# Atau jalankan per file migrasi specific
php artisan tenants:artisan "migrate:refresh --database=tenant"
```

### 2. Seeding Tenant Database (Opsional)

```bash
# Jika perlu seed data untuk tenant
php artisan tenants:artisan "db:seed --database=tenant"
```

### 3. Model Connection Traits - Update Semua Model

**Models yang perlu `UsesLandlordConnection`:**

-   ✅ User
-   ✅ Company
-   CompanyUser
-   TempAuthAuthorizer
-   TempAuthLog
-   Role & Permission (jika tidak global)

**Models yang perlu `UsesTenantConnection`:**

-   ✅ Livestock
-   LivestockBatch
-   Feed
-   Supply
-   Recording
-   FeedUsage
-   LivestockDepletion
-   Farm
-   Coop
-   Worker
-   Dan semua model bisnis lainnya

### 4. Middleware Setup

File: `bootstrap/app.php` - Sudah ada middleware:

```php
\Spatie\Multitenancy\Http\Middleware\NeedsTenant::class,
\Spatie\Multitenancy\Http\Middleware\EnsureValidTenantSession::class,
```

### 5. Route Configuration

Perlu setup route yang memerlukan tenant vs yang tidak:

```php
// routes/web.php
Route::middleware(['tenant'])->group(function () {
    // Routes yang memerlukan tenant
    Route::resource('livestock', LivestockController::class);
    Route::resource('feeds', FeedController::class);
    // dst...
});

// Routes yang tidak memerlukan tenant (landlord)
Route::prefix('admin')->group(function () {
    Route::resource('companies', CompanyController::class);
    Route::resource('users', UserController::class);
});
```

### 6. Testing Setup

```bash
# Test apakah tenant switching berfungsi
php artisan tenants:list

# Test koneksi tenant
php artisan tenants:artisan "migrate:status --database=tenant"
```

### 7. Environment & Production Considerations

**Development:**

-   Setup virtual hosts untuk domain testing
-   Update `/etc/hosts` untuk local domains

**Production:**

-   DNS configuration untuk subdomains
-   SSL certificates untuk setiap tenant domain
-   Database backup strategy per tenant

## 🎯 Next Steps Priority

1. **Immediate (Hari ini):**

    - Fix tenant database migration conflicts
    - Update semua model dengan connection traits yang benar
    - Test basic tenant switching

2. **Short term (Minggu ini):**

    - Route configuration berdasarkan tenant
    - UI untuk tenant management
    - Database seeding per tenant

3. **Medium term (Bulan ini):**
    - Production deployment strategy
    - Backup & restore per tenant
    - Performance optimization

## 🚨 Warning & Considerations

1. **Data Migration:** Existing data perlu dimigrasikan ke tenant-specific databases
2. **Performance:** Multiple database connections dapat mempengaruhi performance
3. **Backup Strategy:** Setiap tenant perlu backup strategy terpisah
4. **Cache Isolation:** Redis/cache keys harus isolated per tenant
5. **File Storage:** Asset/file storage perlu dipisah per tenant

## 📝 Testing Commands

```bash
# Check tenant setup
php artisan tenants:list

# Run command for specific tenant
php artisan tenants:artisan "migrate:status --database=tenant" --tenant=1

# Check current tenant in request
php artisan tinker
>>> app('currentTenant')

# Test tenant switching
>>> Tenant::current()
```

## 📚 References

-   [Spatie Laravel Multitenancy Documentation](https://spatie.be/docs/laravel-multitenancy/v4/installation/using-multiple-databases)
-   Konfigurasi project: `config/multitenancy.php`
-   Migration landlord: `database/migrations/landlord/`
-   Tenant finder: `app/TenantFinder/DomainTenantFinder.php`

---

**Status:** Setup dasar selesai, perlu finalisasi migration dan model traits
**Last Updated:** 26 Juni 2025, 07:00 WIB

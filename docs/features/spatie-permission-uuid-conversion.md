# Konversi UUID untuk Spatie Laravel Permission

## Overview

Dokumen ini menjelaskan proses konversi sistem permission dari integer ID ke UUID untuk meningkatkan konsistensi dan keamanan sistem.

## Status Saat Ini

### ✅ Yang Sudah Menggunakan UUID

-   Tabel `permissions` - menggunakan `uuid('id')`
-   Tabel `roles` - menggunakan `uuid('id')`
-   Tabel `role_has_permissions` - menggunakan `uuid` untuk foreign keys
-   Tabel `model_has_permissions` - menggunakan `uuid` untuk permission_id
-   Tabel `model_has_roles` - menggunakan `uuid` untuk role_id

### ❌ Yang Perlu Dikonversi

-   Tabel `users` - masih menggunakan `id()` (auto-increment integer)
-   Tabel `model_has_permissions` - `model_morph_key` masih `unsignedBigInteger`
-   Tabel `model_has_roles` - `model_morph_key` masih `unsignedBigInteger`
-   Configuration `permission.php` - `model_morph_key` masih 'model_id'

## Implementasi

### 1. Konversi User Model ke UUID

#### 1.1 Buat Migration untuk Konversi Users Table

```php
// database/migrations/2025_01_25_000000_convert_users_to_uuid.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop existing primary key
            $table->dropPrimary();

            // Add UUID column
            $table->uuid('uuid')->after('id')->unique();

            // Make UUID the primary key
            $table->primary('uuid');

            // Drop old id column
            $table->dropColumn('id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropPrimary();
            $table->dropColumn('uuid');
            $table->id()->first();
            $table->primary('id');
        });
    }
};
```

#### 1.2 Update User Model

```php
// app/Models/User.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasUuids, HasRoles;

    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';

    // ... existing code ...
}
```

### 2. Update Permission Tables

#### 2.1 Buat Migration untuk Update Model Morph Key

```php
// database/migrations/2025_01_25_000001_update_permission_tables_for_uuid.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Update model_has_permissions table
        Schema::table('model_has_permissions', function (Blueprint $table) {
            // Drop existing foreign key constraints if they exist
            try {
                $table->dropForeign(['model_id']);
            } catch (\Exception $e) {
                // Foreign key constraint doesn't exist, continue
            }

            // Change column type
            $table->uuid('model_id')->change();

            // Recreate index
            $table->index(['model_id', 'model_type'], 'model_has_permissions_model_id_model_type_index');
        });

        // Update model_has_roles table
        Schema::table('model_has_roles', function (Blueprint $table) {
            // Drop existing foreign key constraints if they exist
            try {
                $table->dropForeign(['model_id']);
            } catch (\Exception $e) {
                // Foreign key constraint doesn't exist, continue
            }

            // Change column type
            $table->uuid('model_id')->change();

            // Recreate index
            $table->index(['model_id', 'model_type'], 'model_has_roles_model_id_model_type_index');
        });
    }

    public function down(): void
    {
        // Revert model_has_permissions table
        Schema::table('model_has_permissions', function (Blueprint $table) {
            $table->dropIndex('model_has_permissions_model_id_model_type_index');
            $table->unsignedBigInteger('model_id')->change();
            $table->index(['model_id', 'model_type'], 'model_has_permissions_model_id_model_type_index');
        });

        // Revert model_has_roles table
        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->dropIndex('model_has_roles_model_id_model_type_index');
            $table->unsignedBigInteger('model_id')->change();
            $table->index(['model_id', 'model_type'], 'model_has_roles_model_id_model_type_index');
        });
    }
};
```

### 3. Update Configuration

#### 3.1 Update Permission Configuration

```php
// config/permission.php
'column_names' => [
    'role_pivot_key' => null,
    'permission_pivot_key' => null,

    // Change this to use UUID
    'model_morph_key' => 'model_uuid',

    'team_foreign_key' => 'team_id',
],
```

### 4. Update Related Tables

#### 4.1 Update Foreign Key References

Buat migration untuk update semua foreign key yang mereferensikan users.id:

```php
// database/migrations/2025_01_25_000002_update_foreign_keys_to_uuid.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $tables = [
        'audit_trails',
        'companies',
        'company_users',
        'login_logs',
        'model_verifications',
        'units',
        'partners',
        'expeditions',
        'workers',
        'livestock_mutations',
        'livestock_mutation_items',
        'feed_purchases',
        'feed_purchase_batches',
        'livestock_purchases',
        'livestock_purchase_batches',
        'supply_purchases',
        'supply_purchase_batches',
        'feed_mutations',
        'supply_mutations',
        'feed_usages',
        'supply_usages',
        'recordings',
        'ovk_records',
        'sales_transactions',
        'analytics_alerts',
        'verification_logs',
        'qa_checklists',
        'qa_todo_lists',
        'qa_todo_comments',
        'temp_auth_authorizers',
        'temp_auth_logs',
        'reports',
        'analytics_tables',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    // Update created_by column
                    if (Schema::hasColumn($tableName, 'created_by')) {
                        try {
                            $table->dropForeign(['created_by']);
                        } catch (\Exception $e) {
                            // Foreign key constraint doesn't exist, continue
                        }
                        $table->uuid('created_by')->change();
                        $table->foreign('created_by')->references('uuid')->on('users');
                    }

                    // Update updated_by column
                    if (Schema::hasColumn($tableName, 'updated_by')) {
                        try {
                            $table->dropForeign(['updated_by']);
                        } catch (\Exception $e) {
                            // Foreign key constraint doesn't exist, continue
                        }
                        $table->uuid('updated_by')->change();
                        $table->foreign('updated_by')->references('uuid')->on('users');
                    }

                    // Update user_id column if exists
                    if (Schema::hasColumn($tableName, 'user_id')) {
                        try {
                            $table->dropForeign(['user_id']);
                        } catch (\Exception $e) {
                            // Foreign key constraint doesn't exist, continue
                        }
                        $table->uuid('user_id')->change();
                        $table->foreign('user_id')->references('uuid')->on('users');
                    }

                    // Update verified_by column if exists
                    if (Schema::hasColumn($tableName, 'verified_by')) {
                        try {
                            $table->dropForeign(['verified_by']);
                        } catch (\Exception $e) {
                            // Foreign key constraint doesn't exist, continue
                        }
                        $table->uuid('verified_by')->change();
                        $table->foreign('verified_by')->references('uuid')->on('users');
                    }
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    // Revert created_by column
                    if (Schema::hasColumn($tableName, 'created_by')) {
                        try {
                            $table->dropForeign(['created_by']);
                        } catch (\Exception $e) {
                            // Foreign key constraint doesn't exist, continue
                        }
                        $table->unsignedBigInteger('created_by')->change();
                        $table->foreign('created_by')->references('id')->on('users');
                    }

                    // Revert updated_by column
                    if (Schema::hasColumn($tableName, 'updated_by')) {
                        try {
                            $table->dropForeign(['updated_by']);
                        } catch (\Exception $e) {
                            // Foreign key constraint doesn't exist, continue
                        }
                        $table->unsignedBigInteger('updated_by')->change();
                        $table->foreign('updated_by')->references('id')->on('users');
                    }

                    // Revert user_id column if exists
                    if (Schema::hasColumn($tableName, 'user_id')) {
                        try {
                            $table->dropForeign(['user_id']);
                        } catch (\Exception $e) {
                            // Foreign key constraint doesn't exist, continue
                        }
                        $table->unsignedBigInteger('user_id')->change();
                        $table->foreign('user_id')->references('id')->on('users');
                    }

                    // Revert verified_by column if exists
                    if (Schema::hasColumn($tableName, 'verified_by')) {
                        try {
                            $table->dropForeign(['verified_by']);
                        } catch (\Exception $e) {
                            // Foreign key constraint doesn't exist, continue
                        }
                        $table->unsignedBigInteger('verified_by')->change();
                        $table->foreign('verified_by')->references('id')->on('users');
                    }
                });
            }
        }
    }
};
```

## Deployment & Testing

### 1. Automated Migration Command

```bash
# Run migration with validation
php artisan uuid:migrate

# Dry run (test without making changes)
php artisan uuid:migrate --dry-run

# Force migration (skip confirmation)
php artisan uuid:migrate --force
```

### 2. Service-Based Migration

```php
use App\Services\UuidMigrationService;

$service = new UuidMigrationService();
$result = $service->migrate();

if ($result['success']) {
    echo "Migration successful!";
    print_r($result['stats']);
} else {
    echo "Migration failed: " . $result['error'];
}
```

### 3. Comprehensive Testing

```bash
# Run UUID migration tests
php artisan test tests/Feature/UuidMigrationTest.php

# Run all tests
php artisan test
```

## Testing Checklist

### Pre-Migration Tests

-   [ ] Backup database
-   [ ] Test di environment development
-   [ ] Verifikasi semua foreign key constraints
-   [ ] Test semua permission/role functionality

### Post-Migration Tests

-   [ ] Test user authentication
-   [ ] Test role assignment
-   [ ] Test permission checking
-   [ ] Test all existing functionality
-   [ ] Verify data integrity

## Rollback Plan

### Emergency Rollback

Jika terjadi masalah, gunakan migration rollback:

```bash
php artisan migrate:rollback --step=3
```

### Service-Based Rollback

```php
use App\Services\UuidMigrationService;

$service = new UuidMigrationService();
$success = $service->rollback();

if ($success) {
    echo "Rollback successful!";
} else {
    echo "Rollback failed!";
}
```

### Data Recovery

Jika perlu recovery data:

```bash
# Restore dari backup
mysql -u username -p database_name < backup_file.sql
```

## Performance Considerations

### Indexing

-   Pastikan semua UUID columns memiliki proper indexing
-   Monitor query performance setelah konversi

### Storage

-   UUID menggunakan lebih banyak storage (36 bytes vs 8 bytes)
-   Pertimbangkan impact pada storage requirements

## Security Benefits

1. **Predictability**: UUID tidak dapat diprediksi seperti auto-increment ID
2. **Information Disclosure**: Mencegah exposure informasi internal melalui ID
3. **Enumeration Attacks**: Lebih sulit untuk melakukan enumeration attacks

## Maintenance

### Regular Tasks

-   Monitor performance metrics
-   Update documentation
-   Review security implications
-   Test backup/restore procedures

### Monitoring

-   Database performance
-   Application response times
-   Error rates
-   Storage usage

## Timeline

### Phase 1: Preparation (1-2 days)

-   [ ] Backup database
-   [ ] Create test environment
-   [ ] Review all foreign key dependencies

### Phase 2: Implementation (1 day)

-   [ ] Run migrations
-   [ ] Update models
-   [ ] Update configuration

### Phase 3: Testing (1-2 days)

-   [ ] Comprehensive testing
-   [ ] Performance testing
-   [ ] Security testing

### Phase 4: Deployment (1 day)

-   [ ] Production deployment
-   [ ] Monitoring
-   [ ] Documentation update

## Improvements & Best Practices

### 1. Error Handling & Validation

-   **Comprehensive Validation**: Service class validates environment before migration
-   **Error Recovery**: Automatic rollback on failure
-   **Progress Tracking**: Detailed statistics and progress reporting
-   **Logging**: Complete audit trail of migration process

### 2. Performance Optimizations

-   **Batch Processing**: Process records in batches for large datasets
-   **Index Management**: Proper indexing for UUID columns
-   **Memory Management**: Efficient memory usage during migration
-   **Transaction Safety**: Database transactions for data integrity

### 3. Security Enhancements

-   **UUID Generation**: Cryptographically secure UUID generation
-   **Data Integrity**: Validation of migration results
-   **Access Control**: Proper permission checks during migration
-   **Audit Trail**: Complete logging of all changes

### 4. Monitoring & Maintenance

-   **Health Checks**: Automated validation of migration results
-   **Performance Monitoring**: Track performance impact
-   **Rollback Capability**: Safe rollback procedures
-   **Documentation**: Comprehensive documentation and guides

## Conclusion

Konversi ke UUID akan memberikan:

-   Konsistensi data type di seluruh sistem
-   Peningkatan keamanan
-   Better scalability
-   Future-proof architecture

Pastikan untuk melakukan testing yang komprehensif sebelum deployment ke production.

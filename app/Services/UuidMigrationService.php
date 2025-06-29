<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Exception;

class UuidMigrationService
{
    protected $mappings = [];
    protected $errors = [];
    protected $stats = [
        'users_processed' => 0,
        'permissions_updated' => 0,
        'roles_updated' => 0,
        'foreign_keys_updated' => 0,
        'tables_processed' => 0,
    ];

    /**
     * Execute complete UUID migration
     */
    public function migrate(): array
    {
        try {
            $this->validateEnvironment();
            $this->createUuidMappingsTable();
            $this->generateUserUuids();
            $this->updatePermissionTables();
            $this->updateForeignKeys();
            $this->cleanup();

            return [
                'success' => true,
                'stats' => $this->stats,
                'errors' => $this->errors,
            ];
        } catch (Exception $e) {
            Log::error('UUID Migration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'stats' => $this->stats,
                'errors' => $this->errors,
            ];
        }
    }

    /**
     * Validate environment before migration
     */
    protected function validateEnvironment(): void
    {
        // Check database connection
        DB::connection()->getPdo();

        // Check required tables
        $requiredTables = [
            'users',
            'permissions',
            'roles',
            'model_has_permissions',
            'model_has_roles',
            'role_has_permissions',
        ];

        foreach ($requiredTables as $table) {
            if (!Schema::hasTable($table)) {
                throw new Exception("Required table '{$table}' not found");
            }
        }

        // Check if users table has id column
        if (!Schema::hasColumn('users', 'id')) {
            throw new Exception("Users table must have 'id' column for migration");
        }

        // Check if users table already has uuid column
        if (Schema::hasColumn('users', 'uuid')) {
            throw new Exception("Users table already has 'uuid' column. Migration may have been run already.");
        }
    }

    /**
     * Create UUID mappings table
     */
    protected function createUuidMappingsTable(): void
    {
        if (!Schema::hasTable('uuid_mappings')) {
            Schema::create('uuid_mappings', function ($table) {
                $table->id();
                $table->unsignedBigInteger('old_id');
                $table->uuid('new_uuid');
                $table->string('table_name');
                $table->timestamps();

                $table->index(['old_id', 'table_name']);
                $table->index(['new_uuid', 'table_name']);
            });
        }
    }

    /**
     * Generate UUIDs for all users
     */
    protected function generateUserUuids(): void
    {
        $users = DB::table('users')->get();

        foreach ($users as $user) {
            $uuid = Str::uuid();

            // Add uuid column to users table if not exists
            if (!Schema::hasColumn('users', 'uuid')) {
                Schema::table('users', function ($table) {
                    $table->uuid('uuid')->after('id')->unique();
                });
            }

            DB::table('users')
                ->where('id', $user->id)
                ->update(['uuid' => $uuid]);

            // Store mapping
            DB::table('uuid_mappings')->insert([
                'old_id' => $user->id,
                'new_uuid' => $uuid,
                'table_name' => 'users',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->mappings[$user->id] = $uuid;
            $this->stats['users_processed']++;
        }
    }

    /**
     * Update permission tables with new UUIDs
     */
    protected function updatePermissionTables(): void
    {
        // Update model_has_permissions
        $permissions = DB::table('model_has_permissions')
            ->where('model_type', 'App\\Models\\User')
            ->get();

        foreach ($permissions as $permission) {
            if (isset($this->mappings[$permission->model_id])) {
                DB::table('model_has_permissions')
                    ->where('permission_id', $permission->permission_id)
                    ->where('model_id', $permission->model_id)
                    ->where('model_type', $permission->model_type)
                    ->update(['model_id' => $this->mappings[$permission->model_id]]);

                $this->stats['permissions_updated']++;
            }
        }

        // Update model_has_roles
        $roles = DB::table('model_has_roles')
            ->where('model_type', 'App\\Models\\User')
            ->get();

        foreach ($roles as $role) {
            if (isset($this->mappings[$role->model_id])) {
                DB::table('model_has_roles')
                    ->where('role_id', $role->role_id)
                    ->where('model_id', $role->model_id)
                    ->where('model_type', $role->model_type)
                    ->update(['model_id' => $this->mappings[$role->model_id]]);

                $this->stats['roles_updated']++;
            }
        }
    }

    /**
     * Update all foreign key references
     */
    protected function updateForeignKeys(): void
    {
        $tables = [
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

        $columns = ['created_by', 'updated_by', 'user_id', 'verified_by'];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                $this->stats['tables_processed']++;

                foreach ($columns as $column) {
                    if (Schema::hasColumn($table, $column)) {
                        $this->updateTableColumn($table, $column);
                    }
                }
            }
        }
    }

    /**
     * Update specific table column
     */
    protected function updateTableColumn(string $table, string $column): void
    {
        $records = DB::table($table)->whereNotNull($column)->get();

        foreach ($records as $record) {
            if (isset($this->mappings[$record->$column])) {
                DB::table($table)
                    ->where('id', $record->id)
                    ->update([$column => $this->mappings[$record->$column]]);

                $this->stats['foreign_keys_updated']++;
            }
        }
    }

    /**
     * Perform cleanup after migration
     */
    protected function cleanup(): void
    {
        // Clear caches
        Artisan::call('config:clear');
        Artisan::call('cache:clear');
        Artisan::call('permission:cache-reset');
    }

    /**
     * Validate migration results
     */
    public function validateMigration(): array
    {
        $validation = [
            'success' => true,
            'issues' => [],
        ];

        // Check if all users have UUIDs
        $usersWithoutUuid = DB::table('users')->whereNull('uuid')->count();
        if ($usersWithoutUuid > 0) {
            $validation['success'] = false;
            $validation['issues'][] = "{$usersWithoutUuid} users without UUID";
        }

        // Check if all mappings are correct
        $mappingCount = DB::table('uuid_mappings')->count();
        $userCount = DB::table('users')->count();
        if ($mappingCount !== $userCount) {
            $validation['success'] = false;
            $validation['issues'][] = "Mapping count mismatch: {$mappingCount} mappings vs {$userCount} users";
        }

        // Check permission table integrity
        $orphanedPermissions = DB::table('model_has_permissions')
            ->where('model_type', 'App\\Models\\User')
            ->whereNotIn('model_id', array_values($this->mappings))
            ->count();

        if ($orphanedPermissions > 0) {
            $validation['success'] = false;
            $validation['issues'][] = "{$orphanedPermissions} orphaned permission records";
        }

        return $validation;
    }

    /**
     * Get migration statistics
     */
    public function getStats(): array
    {
        return $this->stats;
    }

    /**
     * Get migration errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Rollback migration (simplified)
     */
    public function rollback(): bool
    {
        try {
            // Drop uuid_mappings table
            if (Schema::hasTable('uuid_mappings')) {
                Schema::dropIfExists('uuid_mappings');
            }

            // Remove uuid column from users table
            if (Schema::hasColumn('users', 'uuid')) {
                Schema::table('users', function ($table) {
                    $table->dropColumn('uuid');
                });
            }

            return true;
        } catch (Exception $e) {
            Log::error('UUID Migration rollback failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}

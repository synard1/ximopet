<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class UuidMigrationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting UUID migration...');

        // Step 1: Generate UUIDs for existing users
        $this->generateUuidsForUsers();

        // Step 2: Update permission tables with new UUIDs
        $this->updatePermissionTables();

        // Step 3: Update all foreign key references
        $this->updateForeignKeys();

        $this->command->info('UUID migration completed successfully!');
    }

    /**
     * Generate UUIDs for existing users
     */
    private function generateUuidsForUsers(): void
    {
        $this->command->info('Generating UUIDs for users...');

        $users = DB::table('users')->get();

        foreach ($users as $user) {
            $uuid = Str::uuid();

            DB::table('users')
                ->where('id', $user->id)
                ->update(['uuid' => $uuid]);

            // Store mapping for later use
            DB::table('uuid_mappings')->insert([
                'old_id' => $user->id,
                'new_uuid' => $uuid,
                'table_name' => 'users',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info("Generated UUIDs for {$users->count()} users");
    }

    /**
     * Update permission tables with new UUIDs
     */
    private function updatePermissionTables(): void
    {
        $this->command->info('Updating permission tables...');

        // Update model_has_permissions
        $permissions = DB::table('model_has_permissions')
            ->where('model_type', 'App\\Models\\User')
            ->get();

        foreach ($permissions as $permission) {
            $mapping = DB::table('uuid_mappings')
                ->where('old_id', $permission->model_id)
                ->where('table_name', 'users')
                ->first();

            if ($mapping) {
                DB::table('model_has_permissions')
                    ->where('permission_id', $permission->permission_id)
                    ->where('model_id', $permission->model_id)
                    ->where('model_type', $permission->model_type)
                    ->update(['model_id' => $mapping->new_uuid]);
            }
        }

        // Update model_has_roles
        $roles = DB::table('model_has_roles')
            ->where('model_type', 'App\\Models\\User')
            ->get();

        foreach ($roles as $role) {
            $mapping = DB::table('uuid_mappings')
                ->where('old_id', $role->model_id)
                ->where('table_name', 'users')
                ->first();

            if ($mapping) {
                DB::table('model_has_roles')
                    ->where('role_id', $role->role_id)
                    ->where('model_id', $role->model_id)
                    ->where('model_type', $role->model_type)
                    ->update(['model_id' => $mapping->new_uuid]);
            }
        }

        $this->command->info("Updated permission tables");
    }

    /**
     * Update all foreign key references
     */
    private function updateForeignKeys(): void
    {
        $this->command->info('Updating foreign key references...');

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
                foreach ($columns as $column) {
                    if (Schema::hasColumn($table, $column)) {
                        $this->updateTableColumn($table, $column);
                    }
                }
            }
        }

        $this->command->info('Updated foreign key references');
    }

    /**
     * Update specific table column
     */
    private function updateTableColumn(string $table, string $column): void
    {
        $records = DB::table($table)->whereNotNull($column)->get();

        foreach ($records as $record) {
            $mapping = DB::table('uuid_mappings')
                ->where('old_id', $record->$column)
                ->where('table_name', 'users')
                ->first();

            if ($mapping) {
                DB::table($table)
                    ->where('id', $record->id)
                    ->update([$column => $mapping->new_uuid]);
            }
        }
    }
}

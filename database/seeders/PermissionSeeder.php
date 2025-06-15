<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * List of models that need validation permissions
     */
    protected $validatableModels = [
        'transaction',
        'feed_purchase',
        'feed_purchase_batch',
        'livestock_purchase',
        'livestock_purchase_batch',
        'supply_purchase',
        'supply_purchase_batch',
        'feed_mutation',
        'livestock_mutation',
        'supply_mutation',
        'feed_usage',
        'supply_usage',
        'recording',
        'ovk_record',
        'sales_transaction',
        'analytics_alert',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create validation permissions for each model
        foreach ($this->validatableModels as $model) {
            Permission::create(['name' => 'validate ' . $model]);
            Permission::create(['name' => 'view ' . $model . ' validation history']);
        }

        // Assign permissions to roles
        $roles = ['SuperAdmin', 'Manager', 'Validator'];
        foreach ($roles as $roleName) {
            $role = Role::firstOrCreate(['name' => $roleName]);

            // Give all validation permissions to the role
            foreach ($this->validatableModels as $model) {
                $role->givePermissionTo([
                    'validate ' . $model,
                    'view ' . $model . ' validation history'
                ]);
            }
        }
    }
}

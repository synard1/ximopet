<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Spatie\Permission\Models\Permission;
use App\Models\Role;

class CompanyRolesPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companyId = Config::get('seeder.current_company_id');

        if (empty($companyId)) {
            Log::warning('CompanyRolesPermissionsSeeder executed without company context. Skipping.');
            return;
        }

        // Define default abilities and module mapping (re-use from RolesPermissionsSeeder)
        $abilities = ['access', 'create', 'read', 'update', 'delete', 'export', 'import', 'print'];

        $permissions_by_role = [
            'Administrator' => [
                'user management',
                'supplier management',
                'customer management',
                'farm master data',
                'kandang management',
                'stok management',
                'inventory management',
                'report management',
                'database management',
                'repository management',
                'records management',
                'transaction',
                'pembelian',
                'penjualan',
                'ekspedisi',
                'roles',
                'permissions',
            ],
            'Manager' => [
                'supplier management',
                'customer management',
                'inventory management',
                'report management',
                'transaction',
            ],
            'Supervisor' => [
                'master data',
                'farm master data',
                'supplier management',
                'customer management',
                'transaction',
            ],
            'Operator' => [
                'stok management',
                'transaction',
                'records management',
                'report management',
            ],
        ];

        // Ensure global permissions exist (or create if missing) – do NOT duplicate per company
        foreach ($permissions_by_role as $modules) {
            foreach ($modules as $module) {
                foreach ($abilities as $ability) {
                    Permission::firstOrCreate([
                        'name'       => "$ability $module",
                        'guard_name' => 'web',
                    ]);
                }
            }
        }

        // Create company-specific roles and sync permissions
        foreach ($permissions_by_role as $roleName => $modules) {
            $role = Role::firstOrCreate([
                'name'       => $roleName,
                'company_id' => $companyId,
                'guard_name' => 'web',
            ]);

            $permissions = [];
            foreach ($modules as $module) {
                foreach ($abilities as $ability) {
                    $permissions[] = "$ability $module";
                }
            }
            $role->syncPermissions($permissions);
        }

        Log::info('CompanyRolesPermissionsSeeder executed', ['company_id' => $companyId]);
    }
}

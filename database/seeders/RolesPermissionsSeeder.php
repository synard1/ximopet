<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
// use Spatie\Permission\Models\Permission;
// use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;
use App\Models\Permission;
use App\Models\Role;

class RolesPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $abilities = ['access', 'create', 'read', 'update', 'delete', 'export', 'import', 'print'];

        $permissions_by_role = [
            'System' => [
                'master data',
                'user management',
                'supplier management',
                'customer management',
                'farm master data',
                'farm operator',
                'farm storage',
                'kandang management',
                'stok management',
                'inventory management',
                'report management',
                'api controls',
                'database management',
                'repository management',
                'records management',
                'transaction',
                'pembelian',
                'penjualan',
                'ekspedisi',
                'roles',
                'permissions',
                'route manager',
                'qa checklist',
                'worker assignment',
                'livestock management',
                'system management',
                'template management'
            ],
            'SuperAdmin' => [
                'master data',
                'user management',
                'supplier management',
                'customer management',
                'farm master data',
                'farm operator',
                'farm storage',
                'kandang management',
                'stok management',
                'inventory management',
                'report management',
                'api controls',
                'database management',
                'repository management',
                'records management',
                'transaction',
                'pembelian',
                'penjualan',
                'ekspedisi',
                'roles',
                'permissions',
                'route manager',
                'qa checklist',
                'worker assignment',
                'livestock management'
            ],
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
                'permissions'
            ],
            'Supervisor' => [
                'master data',
                'farm master data',
                'supplier management',
                'customer management',
                'transaction'
            ],
            'Manager' => [
                'supplier management',
                'customer management',
                'inventory management',
                'report management',
                'transaction'
            ],
            'Operator' => [
                'stok management',
                'transaction',
                'records management',
                'report management',
            ],
        ];

        // Gabungkan semua unique permissions dari System dan SuperAdmin untuk membuat base permissions
        $allModules = array_unique(array_merge(
            $permissions_by_role['System'],
            $permissions_by_role['SuperAdmin']
        ));

        // Buat semua permissions
        foreach ($allModules as $permission) {
            foreach ($abilities as $ability) {
                Permission::firstOrCreate(['name' => "$ability $permission"]);
            }
        }

        // Buat roles & assign permissions
        foreach ($permissions_by_role as $role => $modules) {
            $permissions = [];
            foreach ($modules as $module) {
                foreach ($abilities as $ability) {
                    $permissions[] = "$ability $module";
                }
            }

            Role::firstOrCreate(['name' => $role])->syncPermissions($permissions);
        }

        // Core system users
        $systemUserMap = [
            'system@peternakan.digital' => 'System',
            'admin@peternakan.digital'  => 'SuperAdmin',
            'synard1@gmail.com'        => 'SuperAdmin',
        ];

        // Demo users mapping
        $demoUserMap = [
            'admin@demo.com'      => 'Administrator',
            'supervisor@demo.com' => 'Supervisor',
            'operator@demo.com'   => 'Operator',
            'operator2@demo.com'  => 'Operator',
            'manager@demo.com'    => 'Manager',

            'admin@demo2.com'      => 'Administrator',
            'supervisor@demo2.com' => 'Supervisor',
            'operator@demo2.com'   => 'Operator',
            'operator2@demo2.com'  => 'Operator',
            'manager@demo2.com'    => 'Manager',
        ];

        // Assign roles to system users
        foreach ($systemUserMap as $email => $role) {
            $user = \App\Models\User::where('email', $email)->first();
            if ($user) {
                $user->syncRoles([$role]);
                $this->command->info("✅ Assigned role {$role} to system user {$email}");
            }
        }

        // Assign roles to demo users if they exist
        foreach ($demoUserMap as $email => $role) {
            $user = \App\Models\User::where('email', $email)->first();
            if ($user) {
                $user->syncRoles([$role]);
                $this->command->info("✅ Assigned role {$role} to demo user {$email}");
            }
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $abilities = ['access', 'create', 'read', 'update', 'delete', 'export', 'import', 'print'];

        $permissions_by_role = [
            'SuperAdmin' => [
                'user management',
                'supplier management',
                'customer management',
                'farm management',
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
                'qa checklist'
            ],
            'Administrator' => [
                'user management',
                'supplier management',
                'customer management',
                'farm management',
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

        // Buat semua permissions berdasarkan SuperAdmin scope
        foreach ($permissions_by_role['SuperAdmin'] as $permission) {
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

        // Mapping email => role
        $userRoleMap = [
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

        // Assign roles to users by email
        foreach ($userRoleMap as $email => $role) {
            $user = \App\Models\User::where('email', $email)->first();
            if ($user) {
                $user->assignRole($role);
            }
        }

        // Assign SuperAdmin ke user ID 1 (creator utama)
        \App\Models\User::find(1)?->assignRole('SuperAdmin');
    }
}

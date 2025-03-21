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
        $abilities = [
            'access',
            'create',
            'read',
            'update',
            'delete',
        ];

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
                'transaksi'
            ],
            'Administrator' => [
                'user management',
                'supplier management',
                'customer management',
            ],
            'Supervisor' => [
                'supplier management',
                'customer management',
                'transaksi'
            ],
            'Manager' => [
                'supplier management',
                'customer management',
                'inventory management',
                'transaksi'
            ],
            'Operator' => [
                'stok management',
                'transaksi'
            ],
            // 'trial' => [
            // ],
        ];

        foreach ($permissions_by_role['SuperAdmin'] as $permission) {
            foreach ($abilities as $ability) {
                Permission::firstOrCreate(['name' => $ability . ' ' . $permission]);
            }
        }

        foreach ($permissions_by_role as $role => $permissions) {
            $full_permissions_list = [];
            foreach ($abilities as $ability) {
                foreach ($permissions as $permission) {
                    $full_permissions_list[] = $ability . ' ' . $permission;
                }
            }
            Role::firstOrCreate(['name' => $role])->syncPermissions($full_permissions_list);
        }

        User::find(1)->assignRole('SuperAdmin');
        User::find(2)->assignRole('Administrator');
        User::find(3)->assignRole('Supervisor');
        User::find(4)->assignRole('Operator');
        User::find(5)->assignRole('Operator');
        User::find(6)->assignRole('Manager');
    }
}

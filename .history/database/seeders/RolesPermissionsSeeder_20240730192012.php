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
            'read',
            'write',
            'create',
            'delete',
        ];

        $permissions_by_role = [
            'super_admin' => [
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
            ],
            'administrator' => [
                'api controls',
                'database management',
                'repository management',
            ],
            'supervisor' => [
                'payroll',
            ],
            'operator' => [
                'reporting',
            ],
            'trial' => [
            ],
        ];

        foreach ($permissions_by_role['super_admin'] as $permission) {
            foreach ($abilities as $ability) {
                Permission::create(['name' => $ability . ' ' . $permission]);
            }
        }

        foreach ($permissions_by_role as $role => $permissions) {
            $full_permissions_list = [];
            foreach ($abilities as $ability) {
                foreach ($permissions as $permission) {
                    $full_permissions_list[] = $ability . ' ' . $permission;
                }
            }
            Role::create(['name' => $role])->syncPermissions($full_permissions_list);
        }

        User::find(1)->assignRole('super_admin');
        User::find(2)->assignRole('administrator');
        User::find(3)->assignRole('supervisor');
        User::find(4)->assignRole('operator');
    }
}

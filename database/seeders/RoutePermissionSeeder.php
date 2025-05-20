<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoutePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Create permissions
        $abilities = ['access', 'create', 'read', 'update', 'delete'];
        $module = 'route manager';

        foreach ($abilities as $ability) {
            Permission::create(['name' => $ability . ' ' . $module]);
        }

        // Assign permissions to admin role
        $adminRole = Role::where('name', 'Admin')->first();
        if ($adminRole) {
            $permissions = Permission::where('name', 'like', '%' . $module)->get();
            $adminRole->syncPermissions($permissions);
        }
    }
}

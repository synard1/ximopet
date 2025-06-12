<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ModifyReadOnlyDataPermissionSeeder extends Seeder
{
    public function run()
    {
        // Create permission
        $permission = Permission::create(['name' => 'modify_readonly_data']);

        // Assign permission to admin role
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo($permission);
        }
    }
}

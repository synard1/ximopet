<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
// use Spatie\Permission\Models\Permission;
// use Spatie\Permission\Models\Role;
use App\Models\Permission;
use App\Models\Role;

class QaPermissionSeeder extends Seeder
{
    public function run()
    {
        // Create QA-specific permissions
        $abilities = ['access', 'create', 'read', 'update', 'delete', 'export'];
        $modules = ['qa checklist'];

        foreach ($modules as $module) {
            foreach ($abilities as $ability) {
                Permission::firstOrCreate(['name' => $ability . ' ' . $module]);
            }
        }

        // Create QA role if not exists
        $qaRole = Role::firstOrCreate(['name' => 'QA Tester']);

        // Assign QA permissions to QA role
        $permissions = Permission::whereIn('name', [
            'access qa checklist',
            'create qa checklist',
            'read qa checklist',
            'update qa checklist',
            'delete qa checklist',
            'export qa checklist'
        ])->get();

        $qaRole->syncPermissions($permissions);
    }
}

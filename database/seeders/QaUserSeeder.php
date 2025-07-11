<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
// use Spatie\Permission\Models\Permission;
// use Spatie\Permission\Models\Role;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class QaUserSeeder extends Seeder
{
    public function run()
    {
        // Create QA user
        $user = User::updateOrCreate([
            'name' => 'Nova Indah',
            'email' => 'n0v4ip@gmail.com',
            'password' => Hash::make('Admin123!@'),
            'email_verified_at' => now(),
        ]);

        // Create QA role if not exists
        $qaRole = Role::firstOrCreate(['name' => 'SuperAdmin']);

        // Assign all permissions to QA role
        $permissions = Permission::all();
        $qaRole->syncPermissions($permissions);

        // Assign QA role to user
        $user->assignRole($qaRole);
    }
}

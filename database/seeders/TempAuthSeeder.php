<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\TempAuthAuthorizer;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class TempAuthSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permissions jika belum ada
        $permissions = [
            'grant temp authorization',
            'override data locks',
            'bypass temp authorization',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles jika belum ada
        $roles = ['Manager', 'Super Admin'];
        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName]);
        }

        // Assign permissions to roles
        $managerRole = Role::where('name', 'Manager')->first();
        $superAdminRole = Role::where('name', 'Super Admin')->first();

        if ($managerRole) {
            $managerRole->givePermissionTo(['grant temp authorization', 'override data locks']);
        }

        if ($superAdminRole) {
            $superAdminRole->givePermissionTo($permissions);
        }

        // Create demo users dengan hak autorisasi
        $demoUsers = [
            [
                'name' => 'Manager Demo',
                'email' => 'manager@demo.com',
                'password' => bcrypt('password'),
                'role' => 'Manager',
            ],
            [
                'name' => 'Super Admin Demo',
                'email' => 'superadmin@demo.com',
                'password' => bcrypt('password'),
                'role' => 'Super Admin',
            ],
        ];

        foreach ($demoUsers as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => $userData['password'],
                    'email_verified_at' => now(),
                ]
            );

            // Assign role
            if (!$user->hasRole($userData['role'])) {
                $user->assignRole($userData['role']);
            }
        }

        // Create explicit temp auth authorizers
        $managerUser = User::where('email', 'manager@demo.com')->first();
        $superAdminUser = User::where('email', 'superadmin@demo.com')->first();

        if ($managerUser && $superAdminUser) {
            // Manager bisa mengautorisasi untuk beberapa komponen
            TempAuthAuthorizer::firstOrCreate(
                ['user_id' => $managerUser->id],
                [
                    'authorized_by' => $superAdminUser->id,
                    'is_active' => true,
                    'can_authorize_self' => false,
                    'max_authorization_duration' => 60, // 60 menit
                    'allowed_components' => ['Create', 'Edit', 'LivestockPurchase'],
                    'notes' => 'Manager dengan hak autorisasi untuk komponen livestock',
                    'authorized_at' => now(),
                    'expires_at' => now()->addMonths(6), // Berlaku 6 bulan
                ]
            );

            // Super Admin bisa mengautorisasi semua (tanpa batasan komponen)
            TempAuthAuthorizer::firstOrCreate(
                ['user_id' => $superAdminUser->id],
                [
                    'authorized_by' => $superAdminUser->id, // Self-authorized
                    'is_active' => true,
                    'can_authorize_self' => true,
                    'max_authorization_duration' => null, // No limit
                    'allowed_components' => null, // All components
                    'notes' => 'Super Admin dengan hak autorisasi penuh',
                    'authorized_at' => now(),
                    'expires_at' => null, // Permanent
                ]
            );
        }

        $this->command->info('Temp Auth demo data created successfully!');
        $this->command->info('Demo users:');
        $this->command->info('- manager@demo.com (password: password)');
        $this->command->info('- superadmin@demo.com (password: password)');
    }
}

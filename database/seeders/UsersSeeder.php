<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Company;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;


class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(Generator $faker)
    {
        $superAdmins = [
            [
                'name' => 'Mhd Iqbal Syahputra',
                'email' => 'synard1@gmail.com'
            ],
            [
                'name' => 'Admin',
                'email' => 'admin@peternakan.digital'
            ]
        ];

        foreach ($superAdmins as $admin) {
            try {
                // Create super admin user
                $user = User::create([
                    'name'              => $admin['name'],
                    'email'             => $admin['email'],
                    'password'          => Hash::make('Admin123!@'),
                    'email_verified_at' => now(),
                ]);

                // Assign SuperAdmin role
                if (!$user->hasRole('SuperAdmin')) {
                    $user->assignRole('SuperAdmin');
                }

                $this->command->info("✅ Created super admin user: {$admin['email']}");

                Log::info('UsersSeeder: Created super admin user', [
                    'user_id' => $user->id,
                    'email' => $user->email
                ]);

            } catch (\Exception $e) {
                Log::error('UsersSeeder: Failed to create super admin user', [
                    'error' => $e->getMessage(),
                    'email' => $admin['email']
                ]);
                throw $e;
            }
        }
    }
}

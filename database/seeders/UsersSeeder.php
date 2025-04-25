<?php

namespace Database\Seeders;

use App\Models\User;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Jobs\SendEmailJob;
use App\Models\FarmOperator;
use App\Models\Farm;


class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(Generator $faker)
    {
        // Super admin manual
        User::create([
            'name'              => 'Mhd Iqbal Syahputra',
            'email'             => 'synard1@gmail.com',
            'password'          => Hash::make('Admin123!@'),
            'email_verified_at' => now(),
        ]);

        // Template demo emails
        $demoAccounts = [
            'admin@demo.com',
            'supervisor@demo.com',
            'operator@demo.com',
            'operator2@demo.com',
            'manager@demo.com',
        ];

        foreach ($demoAccounts as $demoEmail) {
            // Buat akun original @demo.com
            User::create([
                'name'              => $faker->name,
                'email'             => $demoEmail,
                'password'          => Hash::make('demo'),
                'email_verified_at' => now(),
            ]);

            // Ganti domain ke @demo2.com
            $demo2Email = str_replace('@demo.com', '@demo2.com', $demoEmail);

            // Buat akun duplikat @demo2.com
            User::create([
                'name'              => $faker->name,
                'email'             => $demo2Email,
                'password'          => Hash::make('demo'),
                'email_verified_at' => now(),
            ]);
        }
    }
}

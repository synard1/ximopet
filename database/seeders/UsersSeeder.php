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

        $superUser = User::create([
            'name'              => 'Mhd Iqbal Syahputra',
            'email'             => 'synard1@gmail.com',
            'password'          => Hash::make('Admin123!@'),
            'email_verified_at' => now(),
        ]);

        $demoUser = User::create([
            'name'              => $faker->name,
            'email'             => 'admin@demo.com',
            'password'          => Hash::make('demo'),
            'email_verified_at' => now(),
        ]);

        $demoUser2 = User::create([
            'name'              => $faker->name,
            'email'             => 'supervisor@demo.com',
            'password'          => Hash::make('demo'),
            'email_verified_at' => now(),
        ]);
        
        $demoUser3 = User::create([
            'name'              => $faker->name,
            'email'             => 'operator@demo.com',
            'password'          => Hash::make('demo'),
            'email_verified_at' => now(),
        ]);

        $demoUser4 = User::create([
            'name'              => $faker->name,
            'email'             => 'operator2@demo.com',
            'password'          => Hash::make('demo'),
            'email_verified_at' => now(),
        ]);

        

        // Create 1000 users using the factory
        // User::factory()->count(1000)->create();
        // User::factory()->count(10000)->create()->each(function ($user) {
        //     dispatch(new SendEmailJob($user));
        // });
    }
}

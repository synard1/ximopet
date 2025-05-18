<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Worker;
use App\Models\User;
use Faker\Factory as Faker;
use Illuminate\Support\Str; // Import Str class

class WorkerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('id_ID'); // Sesuaikan dengan locale yang Anda inginkan
        // Ambil ID user pertama (atau sesuaikan dengan kebutuhan Anda)
        $user = User::first();
        $userId = $user ? $user->id : 1; // Jika tidak ada user, pakai ID 1

        for ($i = 0; $i < 50; $i++) {
            Worker::create([
                'id' => Str::uuid(), // Generate UUID
                'name' => $faker->name,
                'phone' => $faker->phoneNumber,
                'address' => $faker->address,
                'status' => 'active',
                'created_by' => $userId,
                // 'status' => $faker->randomElement(['aktif', 'blacklist', 'nonaktif', 'cuti']),
            ]);
        }
    }
}

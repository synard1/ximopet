<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Address;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            UsersSeeder::class,
            RolesPermissionsSeeder::class,
            DemoSeeder::class,
            LivestockPurchaseSeeder::class,
            QaPermissionSeeder::class,
            QaUserSeeder::class,
            // RoutePermissionSeeder::class,
            MenuSeeder::class,
            // UnitSeeder::class,
            SupplyCategorySeeder::class,
            // BreedSeeder::class,
            // WorkerSeeder::class,
            // OVKSeeder::class,
            LivestockBatchSeeder::class,
            QaTodoPermissionSeeder::class,
            QaTodoMasterDataSeeder::class,
            // QaChecklistSeeder::class,
            ExpeditionSeeder::class,
        ]);

        // \App\Models\User::factory(20)->create();

        // Address::factory(20)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}

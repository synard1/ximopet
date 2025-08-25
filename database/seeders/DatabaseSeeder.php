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
            // ===== SYSTEM FOUNDATION SEEDERS =====
            // These must run first to establish system infrastructure
            SystemCompanySeeder::class,      // Creates SYSTEM company from xolution.php config
            SystemConfigSeeder::class,       // Seeds system configuration (depends on SystemCompanySeeder)

            // ===== DEMO/TESTING SEEDERS =====
            // Optional seeders for development and testing
            DemoSeeder::class,               // Creates DEMO company if enabled in xolution.php

            // ===== USER & PERMISSION SEEDERS =====
            // Core user management and permissions
            UsersSeeder::class,
            RolesPermissionsSeeder::class,

            // ===== MASTER DATA SEEDERS =====
            // Core application data
            MenuSeeder::class,
            UnitSeeder::class,
            SupplyCategorySeeder::class,
            OVKSeeder::class,               // OVK supplies for system company
            StrainSeeder::class,


            // ===== OPTIONAL SEEDERS =====
            // Uncomment as needed for specific features
            // LivestockPurchaseSeeder::class,
            // QaPermissionSeeder::class,
            // QaUserSeeder::class,
            // RoutePermissionSeeder::class,
            // WorkerSeeder::class,
            // LivestockBatchSeeder::class,
            // QaTodoPermissionSeeder::class,
            // QaTodoMasterDataSeeder::class,
            // QaChecklistSeeder::class,
            // ExpeditionSeeder::class,
            // VerificationRuleSeeder::class,
        ]);

        // \App\Models\User::factory(20)->create();

        // Address::factory(20)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}

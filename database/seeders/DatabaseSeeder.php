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
            // ===== ROLES & PERMISSIONS SEEDERS =====
            // Must run first to establish authorization system
            RolesPermissionsSeeder::class,     // Creates basic roles and permissions

            // ===== SYSTEM FOUNDATION SEEDERS =====
            // These must run after roles but before other seeders
            SystemCompanySeeder::class,        // Creates SYSTEM company & system user
            SystemConfigSeeder::class,         // Seeds system configuration

            // ===== USER SEEDERS =====
            // Core user management
            UsersSeeder::class,               // Creates super admin users

            // ===== DEMO/TESTING SEEDERS =====
            // Optional seeders for development and testing
            DemoSeeder::class,                // Creates DEMO company & demo users if enabled

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
            
            // ===== AI CHAT V2 SEEDERS =====
            // UserChatSettingsV2Seeder::class,
        ]);

        // \App\Models\User::factory(20)->create();

        // Address::factory(20)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}

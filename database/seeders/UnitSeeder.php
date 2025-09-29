<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use App\Models\Company;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Seeds units for the system template company.
     * Uses configuration from xolution.php for consistency and reusability.
     */
    public function run(): void
    {
        $defaultCompanyCode = config('xolution.SEEDER.DEFAULT_COMPANY_CODE', 'SYSTEM');

        Log::info('UnitSeeder: Starting unit seeding', [
            'default_company_code' => $defaultCompanyCode,
            'config_source' => 'xolution.php',
            'timestamp' => now()->toISOString()
        ]);

        try {
            // Make sure FK checks don't interfere when users table is empty
            Schema::disableForeignKeyConstraints();

            // Get company_id from config (for job-triggered seeding)
            $companyId = Company::where('code', $defaultCompanyCode)->first()->id;

            if ($companyId) {
                // Seed for specific company from config
                $this->seedForCompany($companyId);
                $this->command->info("✅ UnitSeeder: Seeded units for company ID: {$companyId}");
            } else {
                // Get system company from xolution.php configuration
                $systemCompany = \App\Models\Company::where('code', $defaultCompanyCode)->first();

                if (!$systemCompany) {
                    $errorMessage = "System company with code '{$defaultCompanyCode}' not found. Please run SystemCompanySeeder first.";
                    Log::error('UnitSeeder: System company not found', [
                        'company_code' => $defaultCompanyCode,
                        'error' => $errorMessage
                    ]);

                    $this->command->error("❌ {$errorMessage}");
                    throw new \RuntimeException($errorMessage);
                }

                Log::info('UnitSeeder: Found system company', [
                    'company_id' => $systemCompany->id,
                    'company_code' => $systemCompany->code,
                    'company_name' => $systemCompany->name
                ]);

                // Seed for system company
                $this->seedForCompany($systemCompany->id);

                // Also seed for demo company if enabled and exists
                if (config('xolution.SEEDER.CREATE_DEMO_COMPANY', false)) {
                    $demoCompany = \App\Models\Company::where('code', 'DEMO')->first();
                    if ($demoCompany) {
                        $this->seedForCompany($demoCompany->id);
                        $this->command->info("✅ UnitSeeder: Also seeded for DEMO company ID: {$demoCompany->id}");
                    }
                }

                $this->command->info("✅ UnitSeeder: Successfully seeded units");
                $this->command->info("   📊 System Company: {$systemCompany->name} ({$systemCompany->code})");
                $this->command->info("   🔧 Config Source: xolution.php");
            }

            Schema::enableForeignKeyConstraints();
        } catch (\Exception $e) {
            Log::error('UnitSeeder: Failed to seed units', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'config_source' => 'xolution.php'
            ]);

            $this->command->error("❌ UnitSeeder: Failed to seed units: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Seed units for a specific company
     * 
     * @param string $companyId Company ID
     * @return void
     */
    private function seedForCompany(string $companyId): void
    {
        $units = $this->getUnits();

        Log::info('UnitSeeder: Seeding units for company', [
            'company_id' => $companyId,
            'units_count' => count($units)
        ]);

        $created = 0;
        $updated = 0;

        foreach ($units as $unitData) {
            $existingUnit = Unit::where('code', $unitData['code'])
                ->where('company_id', $companyId)
                ->first();

            if ($existingUnit) {
                // Update existing unit with latest data
                $existingUnit->update([
                    'type' => $unitData['type'],
                    'name' => $unitData['name'],
                    'symbol' => $unitData['symbol'],
                    'description' => $unitData['description'],
                    'status' => 'active',
                    'updated_by' => $this->getDefaultUserId($companyId),
                ]);
                $updated++;
            } else {
                // Create new unit
                Unit::create([
                    'id' => Str::uuid(),
                    'company_id' => $companyId,
                    'code' => $unitData['code'],
                    'type' => $unitData['type'],
                    'name' => $unitData['name'],
                    'symbol' => $unitData['symbol'],
                    'description' => $unitData['description'],
                    'status' => 'active',
                    'created_by' => $this->getDefaultUserId($companyId),
                    'updated_by' => $this->getDefaultUserId($companyId),
                ]);
                $created++;
            }
        }

        Log::info('UnitSeeder: Completed seeding for company', [
            'company_id' => $companyId,
            'created' => $created,
            'updated' => $updated,
            'total' => count($units)
        ]);

        $this->command->info("   📋 Company {$companyId}: Created {$created}, Updated {$updated}");
    }

    /**
     * Get units configuration
     * 
     * @return array Units data
     */
    private function getUnits(): array
    {
        return [
            // Obat
            ['type' => 'Obat', 'code' => 'SAT01', 'name' => 'AMPUL', 'symbol' => 'amp', 'description' => 'Unit ampul untuk obat cair', 'status' => 'active'],
            ['type' => 'Obat', 'code' => 'SAT02', 'name' => 'BOTOL', 'symbol' => 'btl', 'description' => 'Unit botol untuk obat cair', 'status' => 'active'],
            ['type' => 'Obat', 'code' => 'SAT06', 'name' => 'FLS', 'symbol' => 'fls', 'description' => 'Unit flask untuk obat', 'status' => 'active'],
            ['type' => 'Obat', 'code' => 'SAT08', 'name' => 'INHALER', 'symbol' => 'inh', 'description' => 'Unit inhaler untuk obat hirup', 'status' => 'active'],
            ['type' => 'Obat', 'code' => 'KAP', 'name' => 'KAPSUL', 'symbol' => 'kap', 'description' => 'Unit kapsul untuk obat padat', 'status' => 'active'],
            ['type' => 'Obat', 'code' => 'SAT14', 'name' => 'NEBULE', 'symbol' => 'neb', 'description' => 'Unit nebule untuk obat hirup', 'status' => 'active'],
            ['type' => 'Obat', 'code' => 'PUYER', 'name' => 'PUYER', 'symbol' => 'pyr', 'description' => 'Unit puyer untuk obat bubuk', 'status' => 'active'],
            ['type' => 'Obat', 'code' => 'SAT20', 'name' => 'SACHET', 'symbol' => 'sch', 'description' => 'Unit sachet untuk obat bubuk', 'status' => 'active'],
            ['type' => 'Obat', 'code' => 'SAT22', 'name' => 'STRIP', 'symbol' => 'str', 'description' => 'Unit strip untuk obat tablet', 'status' => 'active'],
            ['type' => 'Obat', 'code' => 'SAT23', 'name' => 'SUPP', 'symbol' => 'sup', 'description' => 'Unit suppositoria', 'status' => 'active'],
            ['type' => 'Obat', 'code' => 'SAT25', 'name' => 'TAB', 'symbol' => 'tab', 'description' => 'Unit tab untuk obat tablet', 'status' => 'active'],
            ['type' => 'Obat', 'code' => 'SAT26', 'name' => 'TABLET', 'symbol' => 'tbl', 'description' => 'Unit tablet untuk obat', 'status' => 'active'],
            ['type' => 'Obat', 'code' => 'SAT27', 'name' => 'TUBE', 'symbol' => 'tbe', 'description' => 'Unit tube untuk obat salep', 'status' => 'active'],
            ['type' => 'Obat', 'code' => 'SAT28', 'name' => 'VIAL', 'symbol' => 'vial', 'description' => 'Unit vial untuk obat injeksi', 'status' => 'active'],

            // Umum
            ['type' => 'Umum', 'code' => 'BOX', 'name' => 'BOX', 'symbol' => 'box', 'description' => 'Unit box untuk kemasan', 'status' => 'active'],
            ['type' => 'Umum', 'code' => 'SAT03', 'name' => 'BUNGKUS', 'symbol' => 'bks', 'description' => 'Unit bungkus untuk kemasan', 'status' => 'active'],
            ['type' => 'Umum', 'code' => 'SAT10', 'name' => 'KOTAK', 'symbol' => 'ktk', 'description' => 'Unit kotak untuk kemasan', 'status' => 'active'],
            ['type' => 'Umum', 'code' => 'SAT11', 'name' => 'LEMBAR', 'symbol' => 'lbr', 'description' => 'Unit lembar untuk dokumen', 'status' => 'active'],
            ['type' => 'Umum', 'code' => 'SAT16', 'name' => 'PAKET', 'symbol' => 'pkt', 'description' => 'Unit paket untuk kemasan', 'status' => 'active'],
            ['type' => 'Umum', 'code' => 'SAT17', 'name' => 'PCS', 'symbol' => 'pcs', 'description' => 'Unit pieces untuk satuan', 'status' => 'active'],
            ['type' => 'Umum', 'code' => 'SAT18', 'name' => 'PSG', 'symbol' => 'psg', 'description' => 'Unit pasang untuk alat', 'status' => 'active'],
            ['type' => 'Umum', 'code' => 'SAT19', 'name' => 'ROL', 'symbol' => 'rol', 'description' => 'Unit rol untuk material', 'status' => 'active'],
            ['type' => 'Umum', 'code' => 'SAT21', 'name' => 'SET', 'symbol' => 'set', 'description' => 'Unit set untuk perangkat', 'status' => 'active'],

            // Volume
            ['type' => 'Volume', 'code' => 'SAT04', 'name' => 'CC', 'symbol' => 'cc', 'description' => 'Unit cubic centimeter', 'status' => 'active'],
            ['type' => 'Volume', 'code' => 'SAT07', 'name' => 'GALON', 'symbol' => 'gal', 'description' => 'Unit galon untuk cairan', 'status' => 'active'],
            ['type' => 'Volume', 'code' => 'SAT12', 'name' => 'LITER', 'symbol' => 'lt', 'description' => 'Unit liter untuk cairan', 'status' => 'active'],
            ['type' => 'Volume', 'code' => 'SAT31', 'name' => 'Jerigen 5 Liter', 'symbol' => 'jrg5', 'description' => 'Unit jerigen 5 liter', 'status' => 'active'],
            ['type' => 'Volume', 'code' => 'SAT32', 'name' => 'Jerigen 20 Liter', 'symbol' => 'jrg20', 'description' => 'Unit jerigen 20 liter', 'status' => 'active'],
            ['type' => 'Volume', 'code' => 'SAT33', 'name' => 'Jerigen 25 Liter', 'symbol' => 'jrg25', 'description' => 'Unit jerigen 25 liter', 'status' => 'active'],

            // Panjang
            ['type' => 'Panjang', 'code' => 'SAT05', 'name' => 'CM', 'symbol' => 'cm', 'description' => 'Unit centimeter', 'status' => 'active'],
            ['type' => 'Panjang', 'code' => 'SAT13', 'name' => 'METER', 'symbol' => 'm', 'description' => 'Unit meter', 'status' => 'active'],

            // Berat
            ['type' => 'Berat', 'code' => 'SAT', 'name' => 'GRAM', 'symbol' => 'gr', 'description' => 'Unit gram untuk berat', 'status' => 'active'],
            ['type' => 'Berat', 'code' => 'SAT09', 'name' => 'KG/GR', 'symbol' => 'kg', 'description' => 'Unit kilogram/gram', 'status' => 'active'],
            ['type' => 'Berat', 'code' => 'SAT15', 'name' => 'ONS/GR', 'symbol' => 'ons', 'description' => 'Unit ons/gram', 'status' => 'active'],
            ['type' => 'Berat', 'code' => 'SAT29', 'name' => 'KG', 'symbol' => 'kg', 'description' => 'Unit kilogram', 'status' => 'active'],
            ['type' => 'Berat', 'code' => 'SAT30', 'name' => 'SAK', 'symbol' => 'sak', 'description' => 'Unit sak untuk pakan', 'status' => 'active'],

            // Alat
            ['type' => 'Alat', 'code' => 'SAT24', 'name' => 'SYIRINGE', 'symbol' => 'syr', 'description' => 'Unit syringe untuk injeksi', 'status' => 'active'],
        ];
    }

    /**
     * Get default user ID for the company
     * 
     * @param string $companyId Company ID
     * @return string User ID
     */
    private function getDefaultUserId(string $companyId): string
    {
        // Try to get first user for the company
        $user = User::where('company_id', $companyId)->first();

        if ($user) {
            Log::info('UnitSeeder: Using company-specific user', [
                'company_id' => $companyId,
                'user_id' => $user->id
            ]);
            return $user->id;
        }

        // Fallback: get any user from the system if no company-specific user found
        $anyUser = User::first();
        if ($anyUser) {
            Log::warning('UnitSeeder: No company-specific user found, using system user', [
                'company_id' => $companyId,
                'fallback_user_id' => $anyUser->id
            ]);
            return $anyUser->id;
        }

        // Last resort: create a temporary system user for seeding purposes
        Log::warning('UnitSeeder: No users found, creating temporary system user for seeding', [
            'company_id' => $companyId,
            'note' => 'This is a fallback during initial database seeding'
        ]);

        $tempUser = User::create([
            'name' => 'Temporary System User',
            'email' => 'temp-system@seeder.local',
            'password' => \Illuminate\Support\Facades\Hash::make('temp-password'),
            'email_verified_at' => now(),
            'company_id' => $companyId,
        ]);

        Log::info('UnitSeeder: Created temporary system user', [
            'user_id' => $tempUser->id,
            'company_id' => $companyId
        ]);

        return $tempUser->id;
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SupplyCategory;
use App\Models\Supply;
use App\Models\User;
use App\Models\Unit;
use App\Models\UnitConversion;
use App\Models\Company;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class OVKSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Seeds OVK (Obat, Vitamin, dan Kimia) supplies for the system template company.
     * Uses configuration from xolution.php for consistency and reusability.
     */
    public function run(): void
    {
        $defaultCompanyCode = config('xolution.SEEDER.DEFAULT_COMPANY_CODE', 'SYSTEM');

        Log::info('OVKSeeder: Starting OVK supply seeding', [
            'default_company_code' => $defaultCompanyCode,
            'config_source' => 'xolution.php',
            'timestamp' => now()->toISOString()
        ]);

        try {
            // Get company_id from config (for job-triggered seeding)
            $companyId = Company::where('code', $defaultCompanyCode)->first()->id;

            if ($companyId) {
                // Seed for specific company from config
                $this->seedForCompany($companyId);
                $this->command->info("✅ OVKSeeder: Seeded OVK supplies for company ID: {$companyId}");
            } else {
                // Get system company from xolution.php configuration
                $systemCompany = Company::where('code', $defaultCompanyCode)->first();

                if (!$systemCompany) {
                    $errorMessage = "System company with code '{$defaultCompanyCode}' not found. Please run SystemCompanySeeder first.";
                    Log::error('OVKSeeder: System company not found', [
                        'company_code' => $defaultCompanyCode,
                        'error' => $errorMessage
                    ]);

                    $this->command->error("❌ {$errorMessage}");
                    throw new \RuntimeException($errorMessage);
                }

                Log::info('OVKSeeder: Found system company', [
                    'company_id' => $systemCompany->id,
                    'company_code' => $systemCompany->code,
                    'company_name' => $systemCompany->name
                ]);

                // Seed for system company
                $this->seedForCompany($systemCompany->id);

                // Also seed for demo company if enabled and exists
                if (config('xolution.SEEDER.CREATE_DEMO_COMPANY', false)) {
                    $demoCompany = Company::where('code', 'DEMO')->first();
                    if ($demoCompany) {
                        $this->seedForCompany($demoCompany->id);
                        $this->command->info("✅ OVKSeeder: Also seeded for DEMO company ID: {$demoCompany->id}");
                    }
                }

                $this->command->info("✅ OVKSeeder: Successfully seeded OVK supplies");
                $this->command->info("   📊 System Company: {$systemCompany->name} ({$systemCompany->code})");
                $this->command->info("   🔧 Config Source: xolution.php");
            }
        } catch (\Exception $e) {
            Log::error('OVKSeeder: Failed to seed OVK supplies', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'config_source' => 'xolution.php'
            ]);

            $this->command->error("❌ OVKSeeder: Failed to seed OVK supplies: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Seed OVK supplies for a specific company
     * 
     * @param string $companyId Company ID
     * @return void
     */
    private function seedForCompany(string $companyId): void
    {
        Log::info('OVKSeeder: Seeding OVK supplies for company', [
            'company_id' => $companyId
        ]);

        // 1. Get or Create the OVK Supply Category
        $ovkCategory = $this->getOrCreateOVKCategory($companyId);

        // 2. Get default user for the company
        $defaultUserId = $this->getDefaultUserId($companyId);

        // 3. Get OVK data
        $ovkData = $this->getOVKData();

        $created = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($ovkData as $data) {
            try {
                $baseUnit = Unit::where('name', $data['unit'])
                    ->where('company_id', $companyId)
                    ->first();

                if (!$baseUnit) {
                    Log::warning('OVKSeeder: Unit not found, skipping supply', [
                        'company_id' => $companyId,
                        'unit_name' => $data['unit'],
                        'supply_name' => $data['name']
                    ]);

                    $this->command->warn("⚠️ Unit {$data['unit']} not found. Skipping {$data['name']}");
                    $skipped++;
                    continue;
                }

                // Create the supply
                $supply = $this->createSupply($ovkCategory, $data, $baseUnit, $defaultUserId, $companyId);

                // Create unit conversion
                $this->createUnitConversion($supply, $baseUnit, $defaultUserId);

                $created++;

                Log::info('OVKSeeder: Successfully created supply', [
                    'company_id' => $companyId,
                    'supply_id' => $supply->id,
                    'supply_name' => $supply->name,
                    'unit_name' => $baseUnit->name
                ]);
            } catch (\Exception $e) {
                Log::error('OVKSeeder: Failed to create supply', [
                    'company_id' => $companyId,
                    'supply_name' => $data['name'],
                    'error' => $e->getMessage()
                ]);

                $this->command->error("❌ Failed to create supply {$data['name']}: {$e->getMessage()}");
                $errors++;
            }
        }

        Log::info('OVKSeeder: Completed seeding for company', [
            'company_id' => $companyId,
            'created' => $created,
            'skipped' => $skipped,
            'errors' => $errors
        ]);

        $this->command->info("   📋 Company {$companyId}: Created {$created}, Skipped {$skipped}, Errors {$errors}");
    }

    /**
     * Get or create OVK supply category
     * 
     * @param string $companyId Company ID
     * @return SupplyCategory
     */
    private function getOrCreateOVKCategory(string $companyId): SupplyCategory
    {
        $ovkCategory = SupplyCategory::where('name', 'OVK')
            ->where('company_id', $companyId)
            ->first();

        if (!$ovkCategory) {
            $ovkCategory = SupplyCategory::create([
                'id' => Str::uuid(),
                'code' => 'OVK',
                'name' => 'OVK',
                'description' => 'Obat, Vitamin, dan Kimia - Kategori gabungan',
                'status' => 'active',
                'company_id' => $companyId,
                'created_by' => $this->getDefaultUserId($companyId),
                'updated_by' => $this->getDefaultUserId($companyId),
            ]);

            Log::info('OVKSeeder: Created OVK category', [
                'company_id' => $companyId,
                'category_id' => $ovkCategory->id
            ]);
        }

        return $ovkCategory;
    }

    /**
     * Get OVK data configuration
     * 
     * @return array OVK data
     */
    private function getOVKData(): array
    {
        return [
            ['name' => 'Biocid', 'unit' => 'LITER', 'description' => 'Disinfektan untuk kandang'],
            ['name' => 'Biodes', 'unit' => 'LITER', 'description' => 'Desinfektan untuk sanitasi'],
            ['name' => 'Cevac New L 1000', 'unit' => 'VIAL', 'description' => 'Vaksin untuk unggas'],
            ['name' => 'Cevamune', 'unit' => 'TABLET', 'description' => 'Suplemen untuk unggas'],
            ['name' => 'Chickofit', 'unit' => 'LITER', 'description' => 'Vitamin untuk anak ayam'],
            ['name' => 'CID 2000', 'unit' => 'KG', 'description' => 'Pakan tambahan untuk unggas'],
            ['name' => 'Coxymas', 'unit' => 'LITER', 'description' => 'Obat untuk coccidiosis'],
            ['name' => 'Cupri Sulfate', 'unit' => 'KG', 'description' => 'Bahan kimia untuk sanitasi'],
            ['name' => 'Elektrovit', 'unit' => 'KG', 'description' => 'Vitamin elektrolit'],
            ['name' => 'Enroforte', 'unit' => 'LITER', 'description' => 'Antibiotik untuk unggas'],
            ['name' => 'Formalin', 'unit' => 'LITER', 'description' => 'Bahan kimia untuk pengawetan'],
            ['name' => 'Hiptovit', 'unit' => 'KG', 'description' => 'Vitamin untuk pertumbuhan'],
            ['name' => 'Kaporit Tepung', 'unit' => 'KG', 'description' => 'Klorin untuk sanitasi'],
            ['name' => 'Kumavit @250 Gram', 'unit' => 'BUNGKUS', 'description' => 'Vitamin untuk unggas'],
            ['name' => 'Nopstress', 'unit' => 'KG', 'description' => 'Anti stress untuk unggas'],
            ['name' => 'Rhodivit', 'unit' => 'KG', 'description' => 'Vitamin untuk unggas'],
            ['name' => 'Selco', 'unit' => 'LITER', 'description' => 'Suplemen untuk unggas'],
            ['name' => 'Starbio', 'unit' => 'LITER', 'description' => 'Probiotik untuk unggas'],
            ['name' => 'TH4', 'unit' => 'LITER', 'description' => 'Vitamin untuk unggas'],
            ['name' => 'Toltracox', 'unit' => 'LITER', 'description' => 'Obat untuk unggas'],
            ['name' => 'Vigosine', 'unit' => 'LITER', 'description' => 'Vitamin untuk unggas'],
            ['name' => 'Vitamin C', 'unit' => 'KG', 'description' => 'Vitamin C untuk unggas'],
            ['name' => 'Virukill', 'unit' => 'LITER', 'description' => 'Desinfektan untuk virus'],
            ['name' => 'Zuramox', 'unit' => 'KG', 'description' => 'Antibiotik untuk unggas'],
            ['name' => 'Cyprotylogrin', 'unit' => 'KG', 'description' => 'Bahan kimia untuk sanitasi'],
            ['name' => 'Acid Pack 4 Way', 'unit' => 'KG', 'description' => 'Asam untuk sanitasi'],
        ];
    }

    /**
     * Create supply with conversion units
     * 
     * @param SupplyCategory $ovkCategory OVK category
     * @param array $data Supply data
     * @param Unit $baseUnit Base unit
     * @param string $defaultUserId Default user ID
     * @param string $companyId Company ID
     * @return Supply
     */
    private function createSupply(SupplyCategory $ovkCategory, array $data, Unit $baseUnit, string $defaultUserId, string $companyId): Supply
    {
        $codePrefix = 'OVK-';
        $randomCode = strtoupper(Str::random(8));
        $code = $codePrefix . $randomCode;

        // Prepare the conversion units array - start with the base unit
        $conversionUnits = [
            [
                'unit_id' => $baseUnit->id,
                'unit_name' => $baseUnit->name,
                'value' => 1,
                'is_default_purchase' => true,
                'is_default_mutation' => true,
                'is_default_sale' => true,
                'is_smallest' => true,
            ]
        ];

        // Create the supply with all conversion units
        return Supply::create([
            'id' => Str::uuid(),
            'supply_category_id' => $ovkCategory->id,
            'code' => $code,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'status' => 'active',
            'company_id' => $companyId,
            'data' => [
                'unit_id' => $baseUnit->id,
                'unit_details' => [
                    'id' => $baseUnit->id,
                    'name' => $baseUnit->name,
                    'description' => $baseUnit->description,
                ],
                'conversion_units' => $conversionUnits,
            ],
            'created_by' => $defaultUserId,
            'updated_by' => $defaultUserId,
        ]);
    }

    /**
     * Create unit conversion for supply
     * 
     * @param Supply $supply Supply model
     * @param Unit $baseUnit Base unit
     * @param string $defaultUserId Default user ID
     * @return UnitConversion
     */
    private function createUnitConversion(Supply $supply, Unit $baseUnit, string $defaultUserId): UnitConversion
    {
        return UnitConversion::updateOrCreate(
            [
                'company_id' => $supply->company_id,
                'type' => 'Supply',
                'item_id' => $supply->id,
                'unit_id' => $baseUnit->id,
                'conversion_unit_id' => $baseUnit->id,
            ],
            [
                'conversion_value' => 1,
                'default_purchase' => true,
                'default_mutation' => true,
                'default_sale' => true,
                'smallest' => true,
                'created_by' => $defaultUserId,
                'updated_by' => $defaultUserId,
            ]
        );
    }

    /**
     * Get default user ID for the company
     * 
     * @param string $companyId Company ID
     * @return string User ID
     * @throws \Exception If no users found
     */
    private function getDefaultUserId(string $companyId): string
    {
        // Try to get first user for the company
        $user = User::where('company_id', $companyId)->first();

        if ($user) {
            Log::info('OVKSeeder: Using company-specific user', [
                'company_id' => $companyId,
                'user_id' => $user->id
            ]);
            return $user->id;
        }

        // Fallback: get any user from the system if no company-specific user found
        $anyUser = User::first();
        if ($anyUser) {
            Log::warning('OVKSeeder: No company-specific user found, using system user', [
                'company_id' => $companyId,
                'fallback_user_id' => $anyUser->id
            ]);
            return $anyUser->id;
        }

        // Last resort: return null if no users exist (will cause constraint error but shows the issue)
        $errorMessage = "No users found in database. Please create at least one user before running seeders.";
        Log::error('OVKSeeder: No users found', [
            'company_id' => $companyId,
            'error' => $errorMessage
        ]);

        throw new \Exception($errorMessage);
    }
}

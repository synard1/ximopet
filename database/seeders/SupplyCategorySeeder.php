<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SupplyCategory;
use App\Models\Company;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SupplyCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Seeds supply categories for the system template company.
     * Uses configuration from xolution.php for consistency and reusability.
     */
    public function run(): void
    {
        $defaultCompanyCode = config('xolution.SEEDER.DEFAULT_COMPANY_CODE', 'SYSTEM');

        Log::info('SupplyCategorySeeder: Starting supply category seeding', [
            'default_company_code' => $defaultCompanyCode,
            'config_source' => 'xolution.php',
            'timestamp' => now()->toISOString()
        ]);

        try {
            // Get company_id from config (for job-triggered seeding)
            $companyId = config('seeder.current_company_id');

            if ($companyId) {
                // Seed for specific company from config
                $this->seedForCompany($companyId);
                $this->command->info("✅ SupplyCategorySeeder: Seeded supply categories for company ID: {$companyId}");
            } else {
                // Get system company from xolution.php configuration
                $systemCompany = Company::where('code', $defaultCompanyCode)->first();

                if (!$systemCompany) {
                    $errorMessage = "System company with code '{$defaultCompanyCode}' not found. Please run SystemCompanySeeder first.";
                    Log::error('SupplyCategorySeeder: System company not found', [
                        'company_code' => $defaultCompanyCode,
                        'error' => $errorMessage
                    ]);

                    $this->command->error("❌ {$errorMessage}");
                    throw new \RuntimeException($errorMessage);
                }

                Log::info('SupplyCategorySeeder: Found system company', [
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
                        $this->command->info("✅ SupplyCategorySeeder: Also seeded for DEMO company ID: {$demoCompany->id}");
                    }
                }

                $this->command->info("✅ SupplyCategorySeeder: Successfully seeded supply categories");
                $this->command->info("   📊 System Company: {$systemCompany->name} ({$systemCompany->code})");
                $this->command->info("   🔧 Config Source: xolution.php");
            }
        } catch (\Exception $e) {
            Log::error('SupplyCategorySeeder: Failed to seed supply categories', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'config_source' => 'xolution.php'
            ]);

            $this->command->error("❌ SupplyCategorySeeder: Failed to seed supply categories: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Seed supply categories for a specific company
     * 
     * @param string $companyId Company ID
     * @return void
     */
    private function seedForCompany(string $companyId): void
    {
        $categories = $this->getSupplyCategories();

        Log::info('SupplyCategorySeeder: Seeding supply categories for company', [
            'company_id' => $companyId,
            'categories_count' => count($categories)
        ]);

        $created = 0;
        $updated = 0;

        foreach ($categories as $categoryData) {
            $existingCategory = SupplyCategory::where('name', $categoryData['name'])
                ->where('company_id', $companyId)
                ->first();

            if ($existingCategory) {
                // Update existing category with latest data
                $existingCategory->update([
                    'code' => $categoryData['code'],
                    'description' => $categoryData['description'],
                    'status' => $categoryData['status'],
                    'updated_by' => $this->getDefaultUserId($companyId),
                ]);
                $updated++;
            } else {
                // Create new category
                SupplyCategory::create([
                    'id' => Str::uuid(),
                    'code' => $categoryData['code'],
                    'name' => $categoryData['name'],
                    'description' => $categoryData['description'],
                    'status' => $categoryData['status'],
                    'company_id' => $companyId,
                    'created_by' => $this->getDefaultUserId($companyId),
                    'updated_by' => $this->getDefaultUserId($companyId),
                ]);
                $created++;
            }
        }

        Log::info('SupplyCategorySeeder: Completed seeding for company', [
            'company_id' => $companyId,
            'created' => $created,
            'updated' => $updated,
            'total' => count($categories)
        ]);

        $this->command->info("   📋 Company {$companyId}: Created {$created}, Updated {$updated}");
    }

    /**
     * Get supply categories configuration
     * 
     * @return array Supply categories data
     */
    private function getSupplyCategories(): array
    {
        return [
            [
                'code' => 'OBT',
                'name' => 'Obat',
                'description' => 'Kategori untuk semua jenis obat-obatan',
                'status' => 'active'
            ],
            [
                'code' => 'VIT',
                'name' => 'Vitamin',
                'description' => 'Kategori untuk vitamin dan suplemen',
                'status' => 'active'
            ],
            [
                'code' => 'KIM',
                'name' => 'Kimia',
                'description' => 'Kategori untuk bahan kimia',
                'status' => 'active'
            ],
            [
                'code' => 'DSF',
                'name' => 'Disinfektan',
                'description' => 'Kategori untuk disinfektan dan pembersih',
                'status' => 'active'
            ],
            [
                'code' => 'VKS',
                'name' => 'Vaksin',
                'description' => 'Kategori untuk vaksin dan imunologi',
                'status' => 'active'
            ],
            [
                'code' => 'ATB',
                'name' => 'Antibiotik',
                'description' => 'Kategori untuk antibiotik',
                'status' => 'active'
            ],
            [
                'code' => 'NUT',
                'name' => 'Nutrisi Tambahan',
                'description' => 'Kategori untuk nutrisi dan pakan tambahan',
                'status' => 'active'
            ],
            [
                'code' => 'OVK',
                'name' => 'OVK',
                'description' => 'Obat, Vitamin, dan Kimia - Kategori gabungan',
                'status' => 'active'
            ],
            [
                'code' => 'LNK',
                'name' => 'Lain - Lain',
                'description' => 'Kategori untuk supply lainnya',
                'status' => 'active'
            ]
        ];
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
        $user = \App\Models\User::where('company_id', $companyId)->first();

        if ($user) {
            Log::info('SupplyCategorySeeder: Using company-specific user', [
                'company_id' => $companyId,
                'user_id' => $user->id
            ]);
            return $user->id;
        }

        // Fallback: get any user from the system if no company-specific user found
        $anyUser = \App\Models\User::first();
        if ($anyUser) {
            Log::warning('SupplyCategorySeeder: No company-specific user found, using system user', [
                'company_id' => $companyId,
                'fallback_user_id' => $anyUser->id
            ]);
            return $anyUser->id;
        }

        // Last resort: return null if no users exist (will cause constraint error but shows the issue)
        $errorMessage = "No users found in database. Please create at least one user before running seeders.";
        Log::error('SupplyCategorySeeder: No users found', [
            'company_id' => $companyId,
            'error' => $errorMessage
        ]);

        throw new \Exception($errorMessage);
    }
}

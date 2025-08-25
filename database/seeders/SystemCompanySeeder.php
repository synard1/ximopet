<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SystemCompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Creates the system template company that serves as the foundation
     * for all other system configurations and master data.
     * Uses configuration from config/xolution.php for consistency and reusability.
     */
    public function run(): void
    {
        $code = config('xolution.SEEDER.DEFAULT_COMPANY_CODE', 'SYSTEM');

        Log::info('SystemCompanySeeder: Starting system company creation', [
            'company_code' => $code,
            'config_source' => 'xolution.php',
            'timestamp' => now()->toISOString()
        ]);

        try {
            // Get company configuration from xolution.php
            $companyConfig = config("xolution.DEFAULT_COMPANY.{$code}");

            if (!$companyConfig) {
                throw new \RuntimeException("Company configuration for code '{$code}' not found in xolution.php");
            }

            Log::info('SystemCompanySeeder: Loaded company configuration from xolution.php', [
                'company_code' => $code,
                'config_keys' => array_keys($companyConfig)
            ]);

            // Check if company already exists
            $existingCompany = Company::where('code', $code)->first();

            if ($existingCompany) {
                Log::info('SystemCompanySeeder: Company already exists, updating metadata', [
                    'company_id' => $existingCompany->id,
                    'company_code' => $code
                ]);

                // Update existing company with latest metadata from config
                $updateData = [
                    'name' => $companyConfig['name'],
                    'type' => $companyConfig['type'],
                    'status' => $companyConfig['status'],
                    'is_locked' => $companyConfig['is_locked'],
                    'metadata' => array_merge($companyConfig['metadata'], [
                        'last_updated' => now()->toISOString(),
                        'updated_by' => 'SystemCompanySeeder',
                        'config_source' => 'xolution.php'
                    ]),
                ];

                $existingCompany->update($updateData);

                $this->command->info("✅ SystemCompanySeeder: Updated existing company '{$code}' (ID: {$existingCompany->id})");
                $this->command->info("   📊 Updated from: {$companyConfig['name']} ({$companyConfig['type']})");

                // Store company ID in config for other seeders
                config(['seeder.system_company_id' => $existingCompany->id]);

                return;
            }

            // Create new system company using configuration from xolution.php
            $companyData = [
                'id' => (string) Str::uuid(),
                'code' => $companyConfig['code'],
                'name' => $companyConfig['name'],
                'type' => $companyConfig['type'],
                'status' => $companyConfig['status'],
                'is_locked' => $companyConfig['is_locked'],
                'metadata' => array_merge($companyConfig['metadata'], [
                    'created_at' => now()->toISOString(),
                    'config_source' => 'xolution.php'
                ]),
            ];

            $company = Company::create($companyData);

            Log::info('SystemCompanySeeder: Successfully created system company', [
                'company_id' => $company->id,
                'company_code' => $code,
                'company_name' => $company->name,
                'company_type' => $company->type,
                'config_source' => 'xolution.php'
            ]);

            $this->command->info("✅ SystemCompanySeeder: Created new system company '{$code}' (ID: {$company->id})");
            $this->command->info("   📊 Company: {$companyConfig['name']} ({$companyConfig['type']})");
            $this->command->info("   🔧 Config Source: xolution.php");
            $this->command->info("   📋 Features: " . implode(', ', $companyConfig['metadata']['features'] ?? []));

            // Store company ID in config for other seeders
            config(['seeder.system_company_id' => $company->id]);
        } catch (\Exception $e) {
            Log::error('SystemCompanySeeder: Failed to create/update system company', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'company_code' => $code,
                'config_source' => 'xolution.php'
            ]);

            $this->command->error("❌ SystemCompanySeeder: Failed to create/update system company: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Get company configuration from xolution.php
     * 
     * @param string $code Company code
     * @return array|null Company configuration
     */
    protected function getCompanyConfig(string $code): ?array
    {
        return config("xolution.DEFAULT_COMPANY.{$code}");
    }

    /**
     * Validate company configuration
     * 
     * @param array $config Company configuration
     * @return bool Validation result
     */
    protected function validateCompanyConfig(array $config): bool
    {
        $requiredFields = ['code', 'name', 'type', 'status', 'is_locked', 'metadata'];

        foreach ($requiredFields as $field) {
            if (!isset($config[$field])) {
                Log::error("SystemCompanySeeder: Missing required field '{$field}' in company configuration");
                return false;
            }
        }

        return true;
    }
}

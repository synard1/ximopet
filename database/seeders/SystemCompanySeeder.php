<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
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
        Log::info('SystemCompanySeeder: ========== STARTING SYSTEM COMPANY SEEDER ==========');
        
        $code = config('xolution.SEEDER.DEFAULT_COMPANY_CODE', 'SYSTEM');

        Log::info('SystemCompanySeeder: Retrieved company code from configuration', [
            'company_code' => $code,
            'config_key' => 'xolution.SEEDER.DEFAULT_COMPANY_CODE',
            'default_fallback' => 'SYSTEM',
            'timestamp' => now()->toISOString()
        ]);

        Log::info('SystemCompanySeeder: Starting system company creation process', [
            'company_code' => $code,
            'config_source' => 'xolution.php',
            'seeder_class' => self::class,
            'timestamp' => now()->toISOString()
        ]);

        try {
            Log::debug('SystemCompanySeeder: Attempting to load company configuration', [
                'config_path' => "xolution.DEFAULT_COMPANY.{$code}",
                'company_code' => $code
            ]);

            // Get company configuration from xolution.php
            $companyConfig = config("xolution.DEFAULT_COMPANY.{$code}");

            if (!$companyConfig) {
                Log::error('SystemCompanySeeder: Company configuration not found', [
                    'company_code' => $code,
                    'config_path' => "xolution.DEFAULT_COMPANY.{$code}",
                    'available_configs' => array_keys(config('xolution.DEFAULT_COMPANY', []))
                ]);
                throw new \RuntimeException("Company configuration for code '{$code}' not found in xolution.php");
            }

            Log::info('SystemCompanySeeder: Successfully loaded company configuration', [
                'company_code' => $code,
                'config_keys' => array_keys($companyConfig),
                'config_size' => count($companyConfig),
                'has_metadata' => isset($companyConfig['metadata'])
            ]);

            Log::debug('SystemCompanySeeder: Company configuration details', [
                'company_code' => $code,
                'company_name' => $companyConfig['name'] ?? 'N/A',
                'company_type' => $companyConfig['type'] ?? 'N/A',
                'company_status' => $companyConfig['status'] ?? 'N/A',
                'is_locked' => $companyConfig['is_locked'] ?? 'N/A',
                'metadata_keys' => isset($companyConfig['metadata']) ? array_keys($companyConfig['metadata']) : []
            ]);

            Log::debug('SystemCompanySeeder: Checking if company already exists in database', [
                'company_code' => $code,
                'query' => "Company::where('code', '{$code}')"
            ]);

            // Check if company already exists
            $existingCompany = Company::where('code', $code)->first();

            if ($existingCompany) {
                Log::info('SystemCompanySeeder: Found existing company in database', [
                    'company_id' => $existingCompany->id,
                    'company_code' => $code,
                    'company_name' => $existingCompany->name,
                    'company_type' => $existingCompany->type,
                    'current_status' => $existingCompany->status,
                    'is_locked' => $existingCompany->is_locked,
                    'created_at' => $existingCompany->created_at,
                    'updated_at' => $existingCompany->updated_at
                ]);

                Log::debug('SystemCompanySeeder: Preparing update data for existing company', [
                    'company_id' => $existingCompany->id,
                    'new_name' => $companyConfig['name'],
                    'new_type' => $companyConfig['type'],
                    'new_status' => $companyConfig['status'],
                    'new_is_locked' => $companyConfig['is_locked']
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

                Log::debug('SystemCompanySeeder: Final update data prepared', [
                    'company_id' => $existingCompany->id,
                    'update_data_keys' => array_keys($updateData),
                    'metadata_size' => count($updateData['metadata'])
                ]);

                try {
                    $existingCompany->update($updateData);
                    
                    Log::info('SystemCompanySeeder: Successfully updated existing company', [
                        'company_id' => $existingCompany->id,
                        'company_code' => $code,
                        'updated_fields' => array_keys($updateData),
                        'timestamp' => now()->toISOString()
                    ]);
                } catch (\Exception $updateException) {
                    Log::error('SystemCompanySeeder: Failed to update existing company', [
                        'company_id' => $existingCompany->id,
                        'company_code' => $code,
                        'error' => $updateException->getMessage(),
                        'update_data' => $updateData
                    ]);
                    throw $updateException;
                }

                $this->command->info("✅ SystemCompanySeeder: Updated existing company '{$code}' (ID: {$existingCompany->id})");
                $this->command->info("   📊 Updated from: {$companyConfig['name']} ({$companyConfig['type']})");

                Log::info('SystemCompanySeeder: Storing company ID in runtime config for other seeders', [
                    'company_id' => $existingCompany->id,
                    'config_key' => 'seeder.system_company_id'
                ]);

                // Store company ID in config for other seeders
                config(['seeder.system_company_id' => $existingCompany->id]);

                Log::info('SystemCompanySeeder: ========== COMPLETED (EXISTING COMPANY UPDATED) ==========', [
                    'company_id' => $existingCompany->id,
                    'company_code' => $code,
                    'execution_time' => microtime(true) - LARAVEL_START
                ]);

                return;
            }

            Log::info('SystemCompanySeeder: No existing company found, proceeding with new company creation', [
                'company_code' => $code
            ]);

            // Create new system company using configuration from xolution.php
            $generatedId = (string) Str::uuid();
            
            Log::debug('SystemCompanySeeder: Generating UUID for new company', [
                'generated_id' => $generatedId,
                'company_code' => $code
            ]);

            $companyData = [
                'id' => $generatedId,
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

            Log::debug('SystemCompanySeeder: Prepared company data for creation', [
                'company_data_keys' => array_keys($companyData),
                'company_id' => $companyData['id'],
                'company_code' => $companyData['code'],
                'company_name' => $companyData['name'],
                'metadata_size' => count($companyData['metadata'])
            ]);

            Log::info('SystemCompanySeeder: Attempting to create new company in database', [
                'company_id' => $generatedId,
                'company_code' => $code,
                'company_name' => $companyConfig['name']
            ]);

            try {
                $company = Company::create($companyData);
                
                Log::info('SystemCompanySeeder: Successfully created new system company', [
                    'company_id' => $company->id,
                    'company_code' => $code,
                    'company_name' => $company->name,
                    'company_type' => $company->type,
                    'company_status' => $company->status,
                    'is_locked' => $company->is_locked,
                    'config_source' => 'xolution.php',
                    'created_at' => $company->created_at,
                    'timestamp' => now()->toISOString()
                ]);
            } catch (\Exception $createException) {
                Log::error('SystemCompanySeeder: Failed to create new company', [
                    'company_code' => $code,
                    'company_data' => $companyData,
                    'error' => $createException->getMessage(),
                    'trace' => $createException->getTraceAsString()
                ]);
                throw $createException;
            }

            $this->command->info("✅ SystemCompanySeeder: Created new system company '{$code}' (ID: {$company->id})");
            $this->command->info("   📊 Company: {$companyConfig['name']} ({$companyConfig['type']})");
            $this->command->info("   🔧 Config Source: xolution.php");
            $this->command->info("   📋 Features: " . implode(', ', $companyConfig['metadata']['features'] ?? []));

            Log::info('SystemCompanySeeder: Starting system user creation process', [
                'company_id' => $company->id,
                'company_code' => $code
            ]);

            // Create system user and associate with company
            $this->createSystemUser($company);

            Log::info('SystemCompanySeeder: Storing company ID in runtime config for other seeders', [
                'company_id' => $company->id,
                'config_key' => 'seeder.system_company_id'
            ]);

            // Store company ID in config for other seeders
            config(['seeder.system_company_id' => $company->id]);

            Log::info('SystemCompanySeeder: ========== COMPLETED (NEW COMPANY CREATED) ==========', [
                'company_id' => $company->id,
                'company_code' => $code,
                'execution_time' => microtime(true) - LARAVEL_START
            ]);
        } catch (\Exception $e) {
            Log::error('SystemCompanySeeder: ========== SEEDER FAILED ==========', [
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'company_code' => $code,
                'config_source' => 'xolution.php',
                'seeder_class' => self::class,
                'timestamp' => now()->toISOString()
            ]);

            $this->command->error("❌ SystemCompanySeeder: Failed to create/update system company: {$e->getMessage()}");
            $this->command->error("   📍 Error Location: {$e->getFile()}:{$e->getLine()}");
            $this->command->error("   🔍 Check logs for detailed trace information");
            
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

    /**
     * Create system user and associate it with the system company
     */
    protected function createSystemUser(Company $company): void
    {
        Log::info('SystemCompanySeeder: ========== STARTING SYSTEM USER CREATION ==========', [
            'company_id' => $company->id,
            'company_code' => $company->code,
            'company_name' => $company->name
        ]);

        try {
            $systemEmail = 'system@peternakan.digital';
            $systemPassword = 'System123!@';

            Log::debug('SystemCompanySeeder: Checking if system user already exists', [
                'email' => $systemEmail,
                'company_id' => $company->id
            ]);

            // Check if system user already exists
            $existingUser = \App\Models\User::where('email', $systemEmail)->first();
            
            if ($existingUser) {
                Log::info('SystemCompanySeeder: System user already exists', [
                    'user_id' => $existingUser->id,
                    'email' => $existingUser->email,
                    'existing_company_id' => $existingUser->company_id,
                    'target_company_id' => $company->id
                ]);

                // Update company_id if different
                if ($existingUser->company_id !== $company->id) {
                    Log::info('SystemCompanySeeder: Updating system user company association', [
                        'user_id' => $existingUser->id,
                        'old_company_id' => $existingUser->company_id,
                        'new_company_id' => $company->id
                    ]);
                    
                    $existingUser->update(['company_id' => $company->id]);
                }

                $systemUser = $existingUser;
            } else {
                Log::info('SystemCompanySeeder: Creating new system user', [
                    'email' => $systemEmail,
                    'company_id' => $company->id
                ]);

                $userData = [
                    'name'              => 'System',
                    'email'             => $systemEmail,
                    'password'          => \Illuminate\Support\Facades\Hash::make($systemPassword),
                    'email_verified_at' => now(),
                    'company_id'        => $company->id,
                ];

                Log::debug('SystemCompanySeeder: Prepared user data for creation', [
                    'user_data_keys' => array_keys($userData),
                    'email' => $userData['email'],
                    'company_id' => $userData['company_id'],
                    'email_verified' => !is_null($userData['email_verified_at'])
                ]);

                // Create system user
                $systemUser = \App\Models\User::create($userData);

                Log::info('SystemCompanySeeder: Successfully created system user', [
                    'user_id' => $systemUser->id,
                    'email' => $systemUser->email,
                    'company_id' => $systemUser->company_id,
                    'created_at' => $systemUser->created_at
                ]);
            }

            Log::debug('SystemCompanySeeder: Checking CompanyUser association', [
                'company_id' => $company->id,
                'user_id' => $systemUser->id
            ]);

            // Check if CompanyUser association already exists
            $existingCompanyUser = \App\Models\CompanyUser::where('company_id', $company->id)
                ->where('user_id', $systemUser->id)
                ->first();

            if (!$existingCompanyUser) {
                Log::info('SystemCompanySeeder: Creating CompanyUser association', [
                    'company_id' => $company->id,
                    'user_id' => $systemUser->id
                ]);

                $companyUserData = [
                    'company_id' => $company->id,
                    'user_id' => $systemUser->id,
                    'role' => 'System',
                    'status' => 'active',
                    'isAdmin' => true,
                    'isDefaultAdmin' => true,
                ];

                Log::debug('SystemCompanySeeder: Prepared CompanyUser data', [
                    'company_user_data' => $companyUserData
                ]);

                // Associate with system company using CompanyUser model
                $companyUser = \App\Models\CompanyUser::create($companyUserData);

                Log::info('SystemCompanySeeder: Successfully created CompanyUser association', [
                    'company_user_id' => $companyUser->id,
                    'company_id' => $company->id,
                    'user_id' => $systemUser->id,
                    'role' => $companyUser->role,
                    'is_admin' => $companyUser->isAdmin
                ]);
            } else {
                Log::info('SystemCompanySeeder: CompanyUser association already exists', [
                    'company_user_id' => $existingCompanyUser->id,
                    'company_id' => $company->id,
                    'user_id' => $systemUser->id,
                    'current_role' => $existingCompanyUser->role,
                    'current_status' => $existingCompanyUser->status
                ]);
            }

            Log::debug('SystemCompanySeeder: Checking system role assignment', [
                'user_id' => $systemUser->id,
                'target_role' => 'System'
            ]);

            // Assign system role
            if (!$systemUser->hasRole('System')) {
                Log::info('SystemCompanySeeder: Assigning System role to user', [
                    'user_id' => $systemUser->id,
                    'role' => 'System'
                ]);

                $systemUser->assignRole('System');

                Log::info('SystemCompanySeeder: Successfully assigned System role', [
                    'user_id' => $systemUser->id,
                    'role' => 'System'
                ]);
            } else {
                Log::info('SystemCompanySeeder: User already has System role', [
                    'user_id' => $systemUser->id,
                    'role' => 'System'
                ]);
            }

            Log::info('SystemCompanySeeder: ========== SYSTEM USER CREATION COMPLETED ==========', [
                'user_id' => $systemUser->id,
                'email' => $systemUser->email,
                'company_id' => $company->id,
                'has_system_role' => $systemUser->hasRole('System'),
                'timestamp' => now()->toISOString()
            ]);

            $this->command->info("   👤 Created/Updated system user: {$systemEmail}");
            $this->command->info("   🔑 User ID: {$systemUser->id}");
            $this->command->info("   🏢 Associated with company: {$company->name} ({$company->id})");

        } catch (\Exception $e) {
            Log::error('SystemCompanySeeder: ========== SYSTEM USER CREATION FAILED ==========', [
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'company_id' => $company->id,
                'company_code' => $company->code,
                'timestamp' => now()->toISOString()
            ]);

            $this->command->error("   ❌ Failed to create system user: {$e->getMessage()}");
            $this->command->error("   📍 Error Location: {$e->getFile()}:{$e->getLine()}");
            
            throw $e;
        }
    }
}

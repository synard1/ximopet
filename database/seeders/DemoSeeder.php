<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class DemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates demo company and its users for testing and development purposes.
     * Uses configuration from config/xolution.php for consistency and reusability.
     */
    public function run(): void
    {
        // Check if demo company creation is enabled
        if (!config('xolution.SEEDER.CREATE_DEMO_COMPANY', false)) {
            $this->command->info("ℹ️ DemoSeeder: Demo company creation is disabled. Set CREATE_DEMO_COMPANY=true in .env to enable.");
            return;
        }

        $code = 'DEMO';

        Log::info('DemoSeeder: Starting demo company and users creation', [
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

            Log::info('DemoSeeder: Loaded demo company configuration from xolution.php', [
                'company_code' => $code,
                'config_keys' => array_keys($companyConfig)
            ]);

            // Check if demo company already exists
            $existingCompany = Company::where('code', $code)->first();

            if ($existingCompany) {
                Log::info('DemoSeeder: Demo company already exists, updating metadata', [
                    'company_id' => $existingCompany->id,
                    'company_code' => $code
                ]);

                // Update existing demo company with latest metadata from config
                $updateData = [
                    'name' => $companyConfig['name'],
                    'type' => $companyConfig['type'],
                    'status' => $companyConfig['status'],
                    'is_locked' => $companyConfig['is_locked'],
                    'metadata' => array_merge($companyConfig['metadata'], [
                        'last_updated' => now()->toISOString(),
                        'updated_by' => 'DemoSeeder',
                        'config_source' => 'xolution.php'
                    ]),
                ];

                $existingCompany->update($updateData);

                $this->command->info("✅ DemoSeeder: Updated existing demo company '{$code}' (ID: {$existingCompany->id})");
                $this->command->info("   📊 Updated from: {$companyConfig['name']} ({$companyConfig['type']})");
                $this->command->info("   🎯 Purpose: {$companyConfig['description']}");

                return;
            }

            // Create new demo company using configuration from xolution.php
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

            Log::info('DemoSeeder: Successfully created demo company', [
                'company_id' => $company->id,
                'company_code' => $code,
                'company_name' => $company->name,
                'company_type' => $company->type,
                'config_source' => 'xolution.php'
            ]);

            $this->command->info("✅ DemoSeeder: Created new demo company '{$code}' (ID: {$company->id})");
            $this->command->info("   📊 Company: {$companyConfig['name']} ({$companyConfig['type']})");
            $this->command->info("   🔧 Config Source: xolution.php");
            $this->command->info("   🎯 Purpose: {$companyConfig['description']}");
            $this->command->info("   📋 Features: " . implode(', ', $companyConfig['metadata']['features'] ?? []));
            $this->command->info("   🌍 Environment: {$companyConfig['metadata']['environment']}");

            // Create demo users for the new company
            $this->createDemoUsers($company);

        } catch (\Exception $e) {
            Log::error('DemoSeeder: Failed to create/update demo company', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'company_code' => $code,
                'config_source' => 'xolution.php'
            ]);

            $this->command->error("❌ DemoSeeder: Failed to create/update demo company: {$e->getMessage()}");
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
                Log::error("DemoSeeder: Missing required field '{$field}' in company configuration");
                return false;
            }
        }

        return true;
    }

    /**
     * Create demo users and associate them with the demo company
     */
    protected function createDemoUsers(Company $company)
    {
        $faker = app(\Faker\Generator::class);

        $demoAccounts = [
            [
                'email' => 'admin@demo.com',
                'name' => 'Admin Demo',
                'role' => 'Administrator'
            ],
            [
                'email' => 'supervisor@demo.com',
                'name' => 'Supervisor Demo',
                'role' => 'Supervisor'
            ],
            [
                'email' => 'operator@demo.com',
                'name' => 'Operator Demo',
                'role' => 'Operator'
            ],
            [
                'email' => 'operator2@demo.com',
                'name' => 'Operator Demo 2',
                'role' => 'Operator'
            ],
            [
                'email' => 'manager@demo.com',
                'name' => 'Manager Demo',
                'role' => 'Manager'
            ]
        ];

        Log::info('DemoSeeder: Starting demo users creation', [
            'company_id' => $company->id,
            'company_code' => $company->code,
            'user_count' => count($demoAccounts) * 2 // *2 karena ada demo dan demo2
        ]);

        foreach ($demoAccounts as $account) {
            $email = $account['email'];
            $name = $account['name'];
            $role = $account['role'];

            try {
                // Create demo@demo.com user
                $user = \App\Models\User::create([
                    'name'              => $name,
                    'email'             => $email,
                    'password'          => \Illuminate\Support\Facades\Hash::make('demo'),
                    'email_verified_at' => now(),
                    'created_by'        => 'DemoSeeder',
                    'updated_by'        => 'DemoSeeder'
                ]);

                // Create demo@demo2.com user
                $demo2Email = str_replace('@demo.com', '@demo2.com', $email);
                $user2 = \App\Models\User::create([
                    'name'              => $name . ' (Demo 2)',
                    'email'             => $demo2Email,
                    'password'          => \Illuminate\Support\Facades\Hash::make('demo'),
                    'email_verified_at' => now(),
                    'created_by'        => 'DemoSeeder',
                    'updated_by'        => 'DemoSeeder'
                ]);

                // Associate both users with demo company and assign roles
                $this->associateUserWithCompany($user, $company, $role);
                $this->associateUserWithCompany($user2, $company, $role);

                $this->command->info("✅ Created demo users with role {$role}:");
                $this->command->info("   - {$email}");
                $this->command->info("   - {$demo2Email}");

            } catch (\Exception $e) {
                Log::error('DemoSeeder: Failed to create demo user', [
                    'error' => $e->getMessage(),
                    'email' => $email,
                    'role' => $role,
                    'company_id' => $company->id
                ]);
                $this->command->error("❌ Failed to create demo user {$email}: {$e->getMessage()}");
            }
        }
    }

    /**
     * Associate user with company and assign role
     */
    protected function associateUserWithCompany($user, $company, $role)
    {
        // Set isAdmin dan isDefaultAdmin untuk Administrator role
        $isAdmin = $role === 'Administrator';
        $isDefaultAdmin = $isAdmin && $user->email === 'admin@demo.com';

        // Associate user with company using pivot table
        $user->companies()->attach($company->id, [
            'role' => $role,
            'status' => 'active',
            'isAdmin' => $isAdmin,
            'isDefaultAdmin' => $isDefaultAdmin,
            'created_at' => now(),
            'updated_at' => now(),
            'created_by' => 'DemoSeeder',
            'updated_by' => 'DemoSeeder'
        ]);

        // Assign role through Spatie permissions if not already assigned
        if (!$user->hasRole($role)) {
            $user->assignRole($role);
        }
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\User;
use App\Models\CompanyUser;
use App\Jobs\SyncCompanyDefaultMasterData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;

class CreateCompanyCommand extends Command
{
    protected $signature = 'company:create
                            {name : Nama perusahaan}
                            {email : Email admin perusahaan}
                            {--password= : Password untuk admin (optional)}
                            {--package=basic : Package perusahaan (basic/premium/enterprise)}
                            {--domain= : Domain perusahaan (optional)}';

    protected $description = 'Create a new company with sample data and admin user';

    public function handle()
    {
        try {
            DB::beginTransaction();

            // Get input values
            $name = $this->argument('name');
            $email = $this->argument('email');
            $password = $this->option('password') ?? Str::random(12);
            $package = $this->option('package');
            $domain = $this->option('domain') ?? Str::slug($name) . '.example.com';

            // Create admin user first
            $admin = $this->createAdminUser($email, $password);

            // Create company with admin as creator
            $company = $this->createCompany($name, $domain, $package, $admin->id);

            // Associate admin with company
            $this->associateAdminWithCompany($admin, $company);

            DB::commit();

            // Generate template data (async)
            SyncCompanyDefaultMasterData::dispatch($company);

            // Show success message with credentials
            $this->showSuccessMessage($company, $admin, $password);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create company', [
                'error' => $e->getMessage(),
                'name' => $name ?? null,
                'email' => $email ?? null
            ]);

            $this->error('Failed to create company: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    protected function createCompany(string $name, string $domain, string $package, string $creatorId): Company
    {
        $this->info('Creating company...');

        $companyCode = strtoupper(substr(str_replace([' ', '.'], '', $name), 0, 10));

        // Get system company's metadata as reference
        $systemCompany = Company::where('code', 'SYSTEM')->first();
        $baseMetadata = $systemCompany ? ($systemCompany->metadata ?? []) : [
            'purpose' => 'client_company',
            'environment' => 'production',
            'seeder_version' => '1.0.0'
        ];

        // Prepare metadata for new company
        $metadata = array_merge($baseMetadata, [
            'features' => [
                'basic_features' => true,
                'advanced_features' => $package !== 'basic',
                'premium_features' => $package === 'enterprise'
            ],
            'created_at' => now()->toISOString(),
            'created_by' => $creatorId,
            'config_source' => 'CreateCompanyCommand'
        ]);

        try {
            // Create company with all required fields
            $company = Company::create([
                'code' => $companyCode,
                'name' => $name,
                'domain' => $domain,
                'database' => Str::slug($name),
                'package' => $package,
                'type' => 'client', // As opposed to 'system'
                'is_locked' => false,
                'status' => 'active',
                'notes' => 'Created via CLI',
                'metadata' => $metadata,
                'address' => '-',
                'phone' => '-',
                'email' => '-',
                'created_by' => $creatorId,
                'updated_by' => $creatorId
            ]);

            $this->info("Company created successfully with code: {$companyCode}");
            return $company;

        } catch (\Exception $e) {
            Log::error('Failed to create company', [
                'name' => $name,
                'code' => $companyCode,
                'creator_id' => $creatorId,
                'error' => $e->getMessage()
            ]);

            throw new \Exception(
                "Failed to create company. Possible causes:\n" .
                "- Company code '{$companyCode}' might already exist\n" .
                "- Database constraint error: " . $e->getMessage()
            );
        }
    }

    protected function createAdminUser(string $email, string $password): User
    {
        $this->info('Creating admin user...');

        try {
            // Get system user as creator and template
            $systemUser = User::whereHas('companies', function($q) {
                $q->where('companies.code', 'SYSTEM');
            })->first();

            if (!$systemUser) {
                throw new \Exception(
                    "System user not found. Please ensure system company and user are properly seeded first.\n" .
                    "Run: php artisan db:seed --class=SystemCompanySeeder"
                );
            }

            $baseMetadata = $systemUser->metadata ?? [];

            // Prepare user metadata
            $metadata = array_merge($baseMetadata, [
                'is_system_generated' => true,
                'created_via' => 'CreateCompanyCommand',
                'created_at' => now()->toISOString(),
                'created_by' => $systemUser->id
            ]);

            $admin = User::create([
                'name' => 'Admin ' . Str::before($email, '@'),
                'email' => $email,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
                'status' => 'active',
                'metadata' => $metadata,
                'preferences' => [
                    'notifications' => [
                        'email' => true,
                        'database' => true
                    ],
                    'theme' => 'light'
                ],
                'phone' => '-',
                'address' => '-',
                'created_by' => $systemUser->id,
                'updated_by' => $systemUser->id
            ]);

            $this->info("Admin user created successfully: {$admin->email}");
            return $admin;

        } catch (\Exception $e) {
            Log::error('Failed to create admin user', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);

            throw new \Exception(
                "Failed to create admin user. Possible causes:\n" .
                "- Email '{$email}' might already be taken\n" .
                "- System user not found or invalid\n" .
                "- Database error: " . $e->getMessage()
            );
        }
    }

    protected function associateAdminWithCompany(User $admin, Company $company): void
    {
        $this->info('Associating admin with company...');

        try {
            // Check if Administrator role exists
            $adminRole = \Spatie\Permission\Models\Role::where('name', 'Administrator')->first();
            if (!$adminRole) {
                throw new \Exception(
                    "Role 'Administrator' not found. Please ensure RolesPermissionsSeeder has been run.\n" .
                    "Run: php artisan db:seed --class=RolesPermissionsSeeder"
                );
            }

            // Assign role first to ensure it exists
            if (!$admin->hasRole('Administrator')) {
                $admin->assignRole('Administrator');
            }

            // Associate via pivot table with admin flags
            $admin->companies()->attach($company->id, [
                'id' => (string) Str::uuid(),
                'status' => 'active',
                'isAdmin' => true,
                'isDefaultAdmin' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'created_by' => $admin->id,
                'updated_by' => $admin->id
            ]);

            // Set as default company for admin
            $admin->update([
                'company_id' => $company->id,
                'updated_by' => $admin->id
            ]);

            // Assign Administrator role if not already assigned
            if (!$admin->hasRole('Administrator')) {
                $admin->assignRole('Administrator');
            }

            $this->info('Admin associated with company successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to associate admin with company', [
                'admin_id' => $admin->id,
                'company_id' => $company->id,
                'error' => $e->getMessage()
            ]);

            throw new \Exception(
                "Failed to associate admin with company. Possible causes:\n" .
                "- Role 'Administrator' might not exist\n" .
                "- Database constraint error: " . $e->getMessage()
            );
        }
    }

    protected function showSuccessMessage(Company $company, User $admin, string $password): void
    {
        $this->newLine();
        $this->info('Company created successfully! 🎉');
        $this->newLine();

        // Company info
        $this->line('Company Details:');
        $this->table(
            ['Name', 'Domain', 'Package', 'Status'],
            [[
                $company->name,
                $company->domain,
                $company->package,
                $company->status
            ]]
        );

        // Admin credentials
        $this->newLine();
        $this->line('Admin Credentials:');
        $this->table(
            ['Name', 'Email', 'Password'],
            [[
                $admin->name,
                $admin->email,
                $password
            ]]
        );

        $this->newLine();
        $this->info('Template data generation has been queued and will be processed in the background.');
        $this->line('You can monitor the progress using:');
        $this->line("php artisan company:setup-progress {$company->id}");

        // Show initial progress
        $this->newLine();
        $this->comment('Initial setup progress:');
        $this->call('company:setup-progress', [
            'company_id' => $company->id
        ]);
    }
}

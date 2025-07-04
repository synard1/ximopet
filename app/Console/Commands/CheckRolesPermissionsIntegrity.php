<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Company;
use App\Models\Role;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;

class CheckRolesPermissionsIntegrity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'company:roles-integrity {companyId? : Optional company uuid or "all" for every company} {--fix : Attempt to repair missing roles or permissions} {--details : Display detailed permission list per role}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validate that each company has complete default roles & permissions. Optionally check a single company.';

    private array $defaultRoles = ['Administrator', 'Manager', 'Supervisor', 'Operator'];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $companyId = $this->argument('companyId');
        $shouldFix   = $this->option('fix');
        $showDetails = $this->option('details');

        $companiesQuery = Company::query();
        if ($companyId && $companyId !== 'all') {
            $companiesQuery->where('id', $companyId);
        }

        $companies = $companiesQuery->get();

        if ($companies->isEmpty()) {
            $this->error('No companies found for given criteria.');
            return Command::FAILURE;
        }

        foreach ($companies as $company) {
            $this->checkCompany($company, $shouldFix, $showDetails);
        }

        // Also check global roles (company_id null)
        if (!$companyId || $companyId === 'all') {
            $this->checkGlobalRoles($shouldFix, $showDetails);
        }

        $this->info('Integrity check completed.');
        return Command::SUCCESS;
    }

    private function checkCompany(Company $company, bool $shouldFix, bool $showDetails): void
    {
        $this->info("\nChecking company: {$company->id} / {$company->name}");
        foreach ($this->defaultRoles as $roleName) {
            $role = Role::where(['name' => $roleName, 'company_id' => $company->id])->first();
            if (!$role) {
                $this->warn("  - Missing role: {$roleName}");
                if ($shouldFix) {
                    $this->fixMissingRole($company, $roleName);
                    $role = Role::where(['name' => $roleName, 'company_id' => $company->id])->first();
                } else {
                    continue;
                }
            }
            $missingPerms = $this->getMissingPermissions($role);
            if ($missingPerms) {
                $this->warn("  - Role {$roleName} missing permissions: " . implode(',', $missingPerms));
                if ($shouldFix) {
                    $role->givePermissionTo($missingPerms);
                    $this->line("    • Added " . count($missingPerms) . " permissions");
                }
            } else {
                $permCount = $role->permissions->count();
                $this->line("  ✔ {$roleName} ({$permCount} perms)");
                if ($showDetails) {
                    $this->line("    • " . $role->permissions->pluck('name')->implode(', '));
                }
            }
        }
    }

    private function checkGlobalRoles(bool $shouldFix, bool $showDetails): void
    {
        $this->info("\nChecking global roles (company_id NULL)");
        foreach ($this->defaultRoles as $roleName) {
            $role = Role::where(['name' => $roleName, 'company_id' => null])->first();
            if (!$role) {
                $this->warn("  - Missing global role: {$roleName}");
                if ($shouldFix) {
                    $this->seedGlobalDefaults();
                    $role = Role::where(['name' => $roleName, 'company_id' => null])->first();
                } else {
                    continue;
                }
            }
            $missingPerms = $this->getMissingPermissions($role);
            if ($missingPerms) {
                $this->warn("  - Global role {$roleName} missing permissions: " . implode(',', $missingPerms));
                if ($shouldFix) {
                    $role->givePermissionTo($missingPerms);
                    $this->line("    • Added " . count($missingPerms) . " permissions");
                }
            } else {
                $permCount = $role->permissions->count();
                $this->line("  ✔ Global {$roleName} ({$permCount} perms)");
                if ($showDetails) {
                    $this->line("    • " . $role->permissions->pluck('name')->implode(', '));
                }
            }
        }
    }

    private function getMissingPermissions(Role $role): array
    {
        // Determine expected perms by inspecting global template role (company_id null)
        $template = Role::where(['name' => $role->name, 'company_id' => null])->first();
        if (!$template) {
            return [];
        }
        $expected = $template->permissions->pluck('name')->toArray();
        $actual   = $role->permissions->pluck('name')->toArray();
        return array_diff($expected, $actual);
    }

    private function fixMissingRole(Company $company, string $roleName): void
    {
        // Ensure template exists
        $template = Role::where(['name' => $roleName, 'company_id' => null])->first();
        if (!$template) {
            $this->seedGlobalDefaults();
            $template = Role::where(['name' => $roleName, 'company_id' => null])->first();
        }

        if (!$template) {
            $this->error("    • Cannot create role {$roleName}. Template not found.");
            return;
        }

        $newRole = Role::firstOrCreate([
            'name'       => $roleName,
            'company_id' => $company->id,
            'guard_name' => 'web',
        ]);

        $newRole->syncPermissions($template->permissions);
        $this->line("    • Role {$roleName} created with " . $template->permissions->count() . " permissions");
    }

    private function seedGlobalDefaults(): void
    {
        $this->line('    • Seeding global default roles/permissions');
        Artisan::call('db:seed', [
            '--class' => \Database\Seeders\RolesPermissionsSeeder::class,
            '--force' => true,
        ]);
    }
}

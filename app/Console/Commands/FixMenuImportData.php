<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Facades\Log;

class FixMenuImportData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'menu:fix-import-data {--dry-run : Show what would be created without actually creating}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix missing roles and permissions required for menu import';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');

        $this->info('🔍 Analyzing missing roles and permissions for menu import...');

        // Missing roles from JSON data analysis
        $requiredRoles = [
            'QA Tester'
        ];

        // Missing permissions extracted from JSON data
        $requiredPermissions = [
            'access coop master data',
            'read coop master data',
            'access expedition master data',
            'read expedition master data',
            'access unit master data',
            'read unit master data',
            'access worker master data',
            'read worker master data',
            'access livestock strain',
            'read livestock strain',
            'access livestock standard',
            'read livestock standard',
            'access livestock management',
            'read livestock management',
            'access inventory management',
            'read inventory management',
            'access feed stock',
            'read feed stock',
            'access supply stock',
            'read supply stock',
            'access transaction',
            'read transaction',
            'access livestock purchasing',
            'read livestock purchasing',
            'access supply mutation',
            'read supply mutation',
            'access livestock mutation',
            'read livestock mutation',
            'access feed mutation',
            'read feed mutation',
            'access report',
            'read report',
            'access report daily recording',
            'read report daily recording',
            'access report daily cost',
            'read report daily cost',
            'access report performance',
            'read report performance',
            'access report smart analytics',
            'read report smart analytics',
            'access report livestock purchasing',
            'read report livestock purchasing',
            'access report feed purchasing',
            'read report feed purchasing',
            'access report supply purchasing',
            'read report supply purchasing',
            'access report batch worker',
            'read report batch worker',
            'access company master data',
            'create company master data',
            'read company master data',
            'update company master data',
            'delete company master data'
        ];

        // Check and create missing roles
        $this->info("\n📋 Checking Roles...");
        $createdRoles = 0;

        foreach ($requiredRoles as $roleName) {
            $role = Role::where('name', $roleName)->first();

            if (!$role) {
                if ($isDryRun) {
                    $this->warn("  [DRY-RUN] Would create role: {$roleName}");
                } else {
                    Role::create([
                        'name' => $roleName,
                        'guard_name' => 'web'
                    ]);
                    $this->info("  ✅ Created role: {$roleName}");
                    $createdRoles++;
                }
            } else {
                $this->comment("  ✓ Role exists: {$roleName}");
            }
        }

        // Check and create missing permissions
        $this->info("\n📋 Checking Permissions...");
        $createdPermissions = 0;

        foreach ($requiredPermissions as $permissionName) {
            $permission = Permission::where('name', $permissionName)->first();

            if (!$permission) {
                if ($isDryRun) {
                    $this->warn("  [DRY-RUN] Would create permission: {$permissionName}");
                } else {
                    Permission::create([
                        'name' => $permissionName,
                        'guard_name' => 'web'
                    ]);
                    $this->info("  ✅ Created permission: {$permissionName}");
                    $createdPermissions++;
                }
            } else {
                $this->comment("  ✓ Permission exists: {$permissionName}");
            }
        }

        // Summary
        $this->info("\n📊 Summary:");

        if ($isDryRun) {
            $missingRoles = collect($requiredRoles)->filter(function ($roleName) {
                return !Role::where('name', $roleName)->exists();
            })->count();

            $missingPermissions = collect($requiredPermissions)->filter(function ($permissionName) {
                return !Permission::where('name', $permissionName)->exists();
            })->count();

            $this->info("  - Roles that would be created: {$missingRoles}");
            $this->info("  - Permissions that would be created: {$missingPermissions}");
            $this->info("\n💡 Run without --dry-run to actually create the missing data");
        } else {
            $this->info("  - Roles created: {$createdRoles}");
            $this->info("  - Permissions created: {$createdPermissions}");

            if ($createdRoles > 0 || $createdPermissions > 0) {
                $this->info("\n🎉 Missing data has been created successfully!");
                $this->info("📝 You can now re-run the menu import and it should work properly.");

                // Log the action
                Log::info('Menu import data fixed', [
                    'roles_created' => $createdRoles,
                    'permissions_created' => $createdPermissions,
                    'command' => 'menu:fix-import-data'
                ]);
            } else {
                $this->info("\n✅ All required roles and permissions already exist.");
            }
        }

        return Command::SUCCESS;
    }
}

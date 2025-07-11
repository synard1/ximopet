<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SupplyUsage;
use App\Models\Company;

class SupplyUsageBypassConfig extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'supply-usage:bypass-config 
                            {action : Action to perform (companies, show, set, reset, test)}
                            {--id= : Company UUID}
                            {--key= : Configuration key to set}
                            {--value= : Configuration value to set}
                            {--role= : Role to test configuration for}
                            {--status= : Status to test transition from}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage supply usage bypass configuration for flexible business rules';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');
        $companyId = $this->option('id');

        switch ($action) {
            case 'companies':
                $this->listCompanies();
                break;
            case 'show':
                $this->showConfig($companyId);
                break;
            case 'set':
                $this->setConfig($companyId);
                break;
            case 'reset':
                $this->resetConfig($companyId);
                break;
            case 'test':
                $this->testConfig($companyId);
                break;
            default:
                $this->error('Invalid action. Use: companies, show, set, reset, or test');
                return 1;
        }

        return 0;
    }

    private function listCompanies()
    {
        $companies = \App\Models\Company::all(['id', 'name', 'email', 'status']);
        $this->table(['ID', 'Name', 'Email', 'Status'], $companies->toArray());
    }

    /**
     * Show current bypass configuration
     */
    private function showConfig($companyId)
    {
        $this->info("Supply Usage Bypass Configuration for Company ID: {$companyId}");
        $this->line('');

        $config = SupplyUsage::getBypassConfigForCompany($companyId);
        $summary = SupplyUsage::getBypassConfigSummary();

        $this->table(
            ['Configuration', 'Value'],
            [
                ['Direct Draft → In Process', $summary['direct_transitions']['draft_to_in_process'] ? 'Enabled' : 'Disabled'],
                ['Direct Draft → Completed', $summary['direct_transitions']['draft_to_completed'] ? 'Enabled' : 'Disabled'],
                ['Skip Approval for Operator', $summary['role_bypass']['operator'] ? 'Enabled' : 'Disabled'],
                ['Skip Approval for Supervisor', $summary['role_bypass']['supervisor'] ? 'Enabled' : 'Disabled'],
                ['Require Notes for Status Change', $summary['require_notes'] ? 'Enabled' : 'Disabled'],
                ['Audit Trail', $summary['audit_trail'] ? 'Enabled' : 'Disabled'],
            ]
        );

        $this->line('');
        $this->info('Auto-approve Roles:');
        foreach ($summary['auto_approve_roles'] as $role) {
            $this->line("  - {$role}");
        }

        $this->line('');
        $this->info('Stock Impact Bypass:');
        foreach ($summary['stock_impact_bypass'] as $transition) {
            $this->line("  - {$transition}");
        }

        $this->line('');
        $this->info('Role-based Status Changes:');
        $this->showRoleBasedChanges($config);
    }

    /**
     * Show role-based status changes
     */
    private function showRoleBasedChanges($config)
    {
        $roles = ['allow_operator_status_changes', 'allow_supervisor_status_changes', 'allow_manager_status_changes'];

        foreach ($roles as $roleKey) {
            $roleName = str_replace('allow_', '', str_replace('_status_changes', '', $roleKey));
            $this->line("  {$roleName}:");

            if (isset($config[$roleKey])) {
                foreach ($config[$roleKey] as $fromStatus => $toStatuses) {
                    $this->line("    {$fromStatus} → " . implode(', ', $toStatuses));
                }
            }
            $this->line('');
        }
    }

    /**
     * Set bypass configuration
     */
    private function setConfig($companyId)
    {
        $key = $this->option('key');
        $value = $this->option('value');

        if (!$key || !$value) {
            $this->error('Both --key and --value options are required for set action');
            return 1;
        }

        $config = SupplyUsage::getBypassConfigForCompany($companyId);

        // Handle different value types
        if (in_array($value, ['true', 'false'])) {
            $config[$key] = $value === 'true';
        } elseif (is_numeric($value)) {
            $config[$key] = (int) $value;
        } elseif (strpos($value, '[') === 0) {
            $config[$key] = json_decode($value, true);
        } else {
            $config[$key] = $value;
        }

        // Update configuration directly for command line
        $company = Company::where('id', $companyId)->first();
        if (!$company) {
            $this->error("Company with ID {$companyId} not found");
            return 1;
        }

        $existingConfig = is_array($company->config) ? $company->config : json_decode($company->config, true);
        $existingConfig['supply_usage_bypass_rules'] = $config;

        $company->config = $existingConfig;
        $company->save();

        $this->info("Configuration updated: {$key} = {$value}");
        $this->showConfig($companyId);
    }

    /**
     * Reset bypass configuration to default
     */
    private function resetConfig($companyId)
    {
        if ($this->confirm("Are you sure you want to reset bypass configuration for company ID {$companyId}?")) {
            SupplyUsage::resetBypassConfig($companyId);
            $this->info("Bypass configuration reset to default for company ID {$companyId}");
            $this->showConfig($companyId);
        }
    }

    /**
     * Test bypass configuration
     */
    private function testConfig($companyId)
    {
        $role = $this->option('role');
        $status = $this->option('status');

        if (!$role || !$status) {
            $this->error('Both --role and --status options are required for test action');
            return 1;
        }

        $this->info("Testing bypass configuration for role: {$role}, from status: {$status}");
        $this->line('');

        // Create a mock usage instance
        $usage = new SupplyUsage();
        $usage->status = $status;

        // Create a mock user
        $user = (object) [
            'getRoleNames' => function () use ($role) {
                return collect([$role]);
            },
            'hasRole' => function ($checkRole) use ($role) {
                return $checkRole === $role;
            }
        ];

        $allowedTransitions = $usage->getAllowedStatusTransitions($user);

        $this->table(
            ['Status', 'Allowed'],
            collect(SupplyUsage::STATUS_LABELS)->map(function ($label, $value) use ($allowedTransitions) {
                return [$label, in_array($value, $allowedTransitions) ? 'Yes' : 'No'];
            })->toArray()
        );

        $this->line('');
        $this->info('Bypass checks:');

        foreach (SupplyUsage::STATUS_LABELS as $value => $label) {
            if (in_array($value, $allowedTransitions)) {
                $canBypass = $usage->canBypassApproval($value, $user);
                $bypassesStock = $usage->bypassesStockImpact($value);

                $this->line("  {$status} → {$value}:");
                $this->line("    Can bypass approval: " . ($canBypass ? 'Yes' : 'No'));
                $this->line("    Bypasses stock impact: " . ($bypassesStock ? 'Yes' : 'No'));
                $this->line('');
            }
        }
    }
}

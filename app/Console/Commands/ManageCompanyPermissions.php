<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Company;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Log;

class ManageCompanyPermissions extends Command
{
    protected $signature = 'company:permissions {companyId} {--add=* : Permission names to add} {--remove=* : Permission names to remove} {--list : List current permissions}';

    protected $description = 'Add, remove, or list permissions allowed for a specific company.';

    public function handle(): int
    {
        $companyId = $this->argument('companyId');
        $company = Company::find($companyId);
        if (!$company) {
            $this->error('Company not found');
            return Command::FAILURE;
        }

        $addPerms = collect($this->option('add'))->filter();
        $removePerms = collect($this->option('remove'))->filter();
        $listOnly = $this->option('list');

        if ($listOnly) {
            $this->line("Allowed permissions for {$company->name}:");
            $company->allowedPermissions->pluck('name')->sort()->each(fn($p) => $this->line("- $p"));
            return Command::SUCCESS;
        }

        if ($addPerms->isEmpty() && $removePerms->isEmpty()) {
            $this->error('Specify --add or --remove or --list');
            return Command::FAILURE;
        }

        if ($addPerms->isNotEmpty()) {
            $addIds = Permission::whereIn('name', $addPerms)->pluck('id');
            $company->allowedPermissions()->syncWithoutDetaching($addIds);
            $this->info('Added ' . $addIds->count() . ' permissions');
        }

        if ($removePerms->isNotEmpty()) {
            $removeIds = Permission::whereIn('name', $removePerms)->pluck('id');
            $company->allowedPermissions()->detach($removeIds);
            $this->info('Removed ' . $removeIds->count() . ' permissions');
        }

        return Command::SUCCESS;
    }
}

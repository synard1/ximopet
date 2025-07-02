<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Company;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class CheckCompanyDataIntegrity extends Command
{
    protected $signature = 'company:check-data-integrity {--fix : Automatically fix missing data}';

    protected $description = 'Check all companies for missing master data (unit, supply, feed) and optionally sync default values.';

    public function handle(): int
    {
        $fix = $this->option('fix');

        $this->info('Checking companies for master data integrity...');

        $companies = Company::all();
        $missingTotal = 0;

        foreach ($companies as $company) {
            $this->line("\nCompany: {$company->name} ({$company->id})");
            if (method_exists($company, 'makeCurrent')) {
                $company->makeCurrent();
            }

            $issues = [];

            // Build closure helper to count by company_id if column exists
            $countByCompany = function (string $modelClass) use ($company) {
                if (!class_exists($modelClass)) {
                    return 0;
                }
                $model = new $modelClass;
                if ($model->getConnection()->getSchemaBuilder()->hasColumn($model->getTable(), 'company_id')) {
                    return $modelClass::where('company_id', $company->id)->count();
                }
                return $modelClass::count();
            };

            if ($countByCompany(\App\Models\Unit::class) === 0) {
                $issues[] = 'units';
            }
            if ($countByCompany(\App\Models\Feed::class) === 0) {
                $issues[] = 'feeds';
            }
            if ($countByCompany(\App\Models\Supply::class) === 0) {
                $issues[] = 'supplies';
            }

            if (empty($issues)) {
                $this->info('  ✔ Master data already present.');
                continue;
            }

            $missingTotal += count($issues);
            $this->warn('  ⚠ Missing: ' . implode(', ', $issues));

            if ($fix) {
                $this->info('  → Attempting to fix...');

                // Provide company id to seeders via config
                config(['seeder.current_company_id' => $company->id]);

                // Update existing records with NULL company_id
                $this->updateExistingRecords($company);

                // Reuse job in sync way (because we are in CLI) using Artisan seed
                $seeders = [
                    \Database\Seeders\UnitSeeder::class,
                    \Database\Seeders\SupplyCategorySeeder::class,
                    \Database\Seeders\FeedSeeder::class,
                    \Database\Seeders\SupplySeeder::class,
                ];

                foreach ($seeders as $seederClass) {
                    if (class_exists($seederClass)) {
                        $this->line("    Seeding {$seederClass} ...");
                        // Pass company_id via environment for seeder
                        $exitCode = Artisan::call('db:seed', [
                            '--class' => $seederClass,
                            '--force' => true,
                        ], null);

                        if ($exitCode !== 0) {
                            $this->error("    Failed to seed {$seederClass}");
                        }
                    }
                }

                $this->info('  ✔ Fix complete.');

                // Clear config after seeding to avoid leakage
                config()->offsetUnset('seeder.current_company_id');
            }
        }

        $this->info("\nIntegrity check finished. Total missing datasets found: {$missingTotal}");
        return Command::SUCCESS;
    }

    private function updateExistingRecords($company)
    {
        $models = [
            \App\Models\Unit::class => 'units',
            \App\Models\Feed::class => 'feeds',
            \App\Models\Supply::class => 'supplies',
        ];

        foreach ($models as $modelClass => $name) {
            if (!class_exists($modelClass)) {
                continue;
            }

            $model = new $modelClass;
            if (!$model->getConnection()->getSchemaBuilder()->hasColumn($model->getTable(), 'company_id')) {
                continue;
            }

            $updated = $modelClass::whereNull('company_id')->update(['company_id' => $company->id]);
            if ($updated > 0) {
                $this->line("    Updated {$updated} existing {$name} records with company_id");
            }
        }
    }
}

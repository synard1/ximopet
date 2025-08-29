<?php

namespace App\Console\Commands;

use App\Models\Company;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class ReseedCompanyTemplateData extends Command
{
    protected $signature = 'company:reseed-template {company_id : ID perusahaan yang akan direstore}
                           {--clean-first : Hapus data yang ada sebelum re-seed}';

    protected $description = 'Re-seed template data untuk company tertentu';

    public function handle()
    {
        $companyId = $this->argument('company_id');
        $cleanFirst = $this->option('clean-first');

        try {
            $company = Company::findOrFail($companyId);

            // Make company as current tenant if using tenancy
            if (method_exists($company, 'makeCurrent')) {
                $company->makeCurrent();
                $this->info("Switched to company context: {$company->name}");
            }

            if ($cleanFirst) {
                $this->warn("Cleaning existing template data for company {$company->name}...");

                // Clean existing data
                DB::transaction(function () use ($company) {
                    // Delete units
                    DB::table('units')->where('company_id', $company->id)->delete();

                    // Delete supply categories
                    DB::table('supply_categories')->where('company_id', $company->id)->delete();

                    // Delete feeds
                    DB::table('feeds')->where('company_id', $company->id)->delete();

                    // Delete supplies
                    DB::table('supplies')->where('company_id', $company->id)->delete();

                    // Do not delete roles/permissions as they might be in use
                });

                $this->info("✅ Existing data cleaned");
            }

            // Make company id accessible to seeders via config
            // Override seeder configurations
            config([
                'seeder.current_company_id' => $company->id,
                'xolution.SEEDER.DEFAULT_COMPANY_CODE' => $company->code
            ]);

            // Define seeders to run
            $seeders = [
                \Database\Seeders\UnitSeeder::class => 'Generating default units',
                \Database\Seeders\SupplyCategorySeeder::class => 'Generating supply categories',
                \Database\Seeders\FeedSeeder::class => 'Generating feed templates',
                \Database\Seeders\SupplySeeder::class => 'Generating supply templates',
                \Database\Seeders\CompanyRolesPermissionsSeeder::class => 'Setting up roles and permissions',
            ];

            $bar = $this->output->createProgressBar(count($seeders));
            $bar->start();

            // Run each seeder
            foreach ($seeders as $seeder => $description) {
                $this->info("\n\nRunning: {$description}");

                try {
                    // Run seeder and capture output
                    $output = Artisan::call('db:seed', [
                        '--class' => $seeder,
                        '--force' => true,
                        '--no-interaction' => true,
                        '--verbose' => true
                    ]);

                    // Show seeder output
                    $this->line(Artisan::output());

                    $this->info("✅ {$description}");
                } catch (\Exception $e) {
                    $this->error("❌ Failed: {$e->getMessage()}");
                    Log::error("Seeder failed: {$seeder}", [
                        'company_id' => $company->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }

                $bar->advance();
            }

            $bar->finish();

            // Verify data
            $this->newLine(2);
            $this->info("Verifying seeded data...");

            $verifications = [
                'units' => DB::table('units')->where('company_id', $company->id)->count(),
                'supply_categories' => DB::table('supply_categories')->where('company_id', $company->id)->count(),
                'feeds' => DB::table('feeds')->where('company_id', $company->id)->count(),
                'supplies' => DB::table('supplies')->where('company_id', $company->id)->count(),
            ];

            $this->table(
                ['Type', 'Count'],
                collect($verifications)->map(fn($count, $type) => [
                    'type' => $type,
                    'count' => $count ?: '❌ 0'
                ])->toArray()
            );

            $this->newLine();
            $this->info('Template data re-seeding completed! 🎉');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Failed to reseed template data: {$e->getMessage()}");
            Log::error('Template data reseed failed', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Command::FAILURE;
        }
    }
}

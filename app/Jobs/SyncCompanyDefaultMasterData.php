<?php

namespace App\Jobs;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use App\Services\CompanySetupService\CompanySetupOrchestrator;
use App\Events\CompanyTemplateDataProgress;
use Illuminate\Support\Facades\Cache;

class SyncCompanyDefaultMasterData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected function reportProgress(string $stage, string $message, int $progress, ?string $error = null): void
    {
        // Store progress in cache
        $cacheKey = "company_setup_progress_{$this->company->id}";
        Cache::put($cacheKey, [
            'stage' => $stage,
            'message' => $message,
            'progress' => $progress,
            'error' => $error
        ], now()->addHours(1));

        // Dispatch event
        CompanyTemplateDataProgress::dispatch(
            $this->company,
            $stage,
            $message,
            $progress,
            $error
        );

        // Log progress
        Log::info("Company setup progress: {$message}", [
            'company_id' => $this->company->id,
            'stage' => $stage,
            'progress' => "{$progress}%",
            'error' => $error
        ]);
    }

    public Company $company;

    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(Company $company)
    {
        $this->company = $company;
    }

    /**
     * Execute the job.
     */
    public function handle(CompanySetupOrchestrator $orchestrator): void
    {
        $this->reportProgress('starting', 'Starting template data generation', 0);
        Log::info('SyncCompanyDefaultMasterData: started', ['company_id' => $this->company->id]);

        // Use tenancy if installed
        if (method_exists($this->company, 'makeCurrent')) {
            $this->company->makeCurrent();
            Log::info('Tenant context switched', ['company' => $this->company->id]);
        }

        try {
            // Generate template data using orchestrator
            $this->reportProgress('settings', 'Generating company settings and configurations', 20);
            if (!$orchestrator->generateTemplateData($this->company)) {
                throw new \Exception('Failed to generate template data');
            }

            // Make company id accessible to seeders via config
            config(['seeder.current_company_id' => $this->company->id]);

            // Define seeders with their descriptions
            $seeders = [
                \Database\Seeders\UnitSeeder::class => 'Generating default units',
                \Database\Seeders\SupplyCategorySeeder::class => 'Generating supply categories',
                \Database\Seeders\FeedSeeder::class => 'Generating feed templates',
                \Database\Seeders\SupplySeeder::class => 'Generating supply templates',
                \Database\Seeders\CompanyRolesPermissionsSeeder::class => 'Setting up roles and permissions',
            ];

            $totalSeeders = count($seeders);
            $currentSeeder = 0;

            foreach ($seeders as $seederClass => $description) {
                $currentSeeder++;
                $progress = 20 + (60 * ($currentSeeder / $totalSeeders)); // 20-80% progress range

                $this->reportProgress(
                    'seeding',
                    $description,
                    (int)$progress
                );

                if (class_exists($seederClass)) {
                    Artisan::call('db:seed', [
                        '--class' => $seederClass,
                        '--force' => true,
                    ]);
                    Log::info('Seeder executed', ['seeder' => $seederClass]);
                }
            }
        } catch (\Throwable $e) {
            $error = [
                'company_id' => $this->company->id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ];

            Log::error('Company setup failed: ' . $e->getMessage(), $error);
            $this->reportProgress('error', $e->getMessage(), 0, json_encode($error));

            throw $e;
        }

        // Optionally revert to original tenant state if using tenancy
        if (function_exists('tenant') && class_exists('Spatie\\Multitenancy\\Models\\Tenant')) {
            // Ensure cleanup of current tenant context
            \call_user_func(['Spatie\\Multitenancy\\Models\\Tenant', 'forgetCurrent']);
        }

        Log::info('SyncCompanyDefaultMasterData: completed', ['company_id' => $this->company->id]);
    }
}

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

class SyncCompanyDefaultMasterData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
    public function handle(): void
    {
        Log::info('SyncCompanyDefaultMasterData: started', ['company_id' => $this->company->id]);

        // Use tenancy if installed
        if (method_exists($this->company, 'makeCurrent')) {
            $this->company->makeCurrent();
            Log::info('Tenant context switched', ['company' => $this->company->id]);
        }

        // Make company id accessible to seeders via config
        config(['seeder.current_company_id' => $this->company->id]);

        // Run the default seeders. Wrap each in try/catch to prevent global failure.
        $seeders = [
            \Database\Seeders\UnitSeeder::class,
            \Database\Seeders\SupplyCategorySeeder::class,
            \Database\Seeders\FeedSeeder::class,
            \Database\Seeders\SupplySeeder::class,
            \Database\Seeders\CompanyRolesPermissionsSeeder::class,
        ];

        foreach ($seeders as $seederClass) {
            try {
                if (class_exists($seederClass)) {
                    Artisan::call('db:seed', [
                        '--class' => $seederClass,
                        '--force' => true,
                    ]);
                    Log::info('Seeder executed', ['seeder' => $seederClass]);
                }
            } catch (\Throwable $e) {
                Log::error('Seeder failed', [
                    'seeder' => $seederClass,
                    'error'  => $e->getMessage(),
                ]);
            }
        }

        // Optionally revert to original tenant state if using tenancy
        if (function_exists('tenant') && class_exists('Spatie\\Multitenancy\\Models\\Tenant')) {
            // Ensure cleanup of current tenant context
            \call_user_func(['Spatie\\Multitenancy\\Models\\Tenant', 'forgetCurrent']);
        }

        Log::info('SyncCompanyDefaultMasterData: completed', ['company_id' => $this->company->id]);
    }
}

<?php

namespace App\Services\CompanySetupService;

use App\Models\Company;
use App\Contracts\CompanySetup\DataGeneratorInterface;
use App\Events\CompanySetupStarted;
use App\Events\CompanySetupCompleted;
use App\Events\CompanySetupFailed;
use App\Services\CompanySetupService\DataGenerators\{
    SettingsGenerator,
    MasterDataGenerator,
    SystemConfigGenerator
};
use Illuminate\Support\Facades\Log;

class CompanySetupOrchestrator
{
    protected array $generators = [];
    protected array $generatedData = [];

    public function __construct()
    {
        $this->registerGenerators([
            new SettingsGenerator(),
            new MasterDataGenerator(),
            new SystemConfigGenerator()
        ]);
    }

    /**
     * Register data generators
     */
    public function registerGenerators(array $generators): void
    {
        foreach ($generators as $generator) {
            if (!($generator instanceof DataGeneratorInterface)) {
                throw new \InvalidArgumentException('Generator must implement DataGeneratorInterface');
            }

            $this->generators[] = $generator;
        }
    }

    /**
     * Generate template data for new company
     */
    public function generateTemplateData(Company $company): bool
    {
        try {
            Log::info('Starting company setup', [
                'company_id' => $company->id
            ]);

            // Dispatch setup started event
            CompanySetupStarted::dispatch($company);

            // Generate data using each generator
            foreach ($this->generators as $generator) {
                if (!$generator->generate($company)) {
                    throw new \Exception('Failed to generate data using ' . get_class($generator));
                }

                if (!$generator->validate($company)) {
                    throw new \Exception('Data validation failed for ' . get_class($generator));
                }

                // Track generated data
                $this->generatedData[get_class($generator)] = true;
            }

            // Dispatch setup completed event
            CompanySetupCompleted::dispatch($company, $this->generatedData);

            Log::info('Company setup completed successfully', [
                'company_id' => $company->id,
                'generators' => array_keys($this->generatedData)
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Company setup failed', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
                'generators_completed' => array_keys($this->generatedData)
            ]);

            // Cleanup on failure
            $this->cleanup($company);

            // Dispatch setup failed event
            CompanySetupFailed::dispatch($company, $e);

            return false;
        }
    }

    /**
     * Cleanup if setup fails
     */
    protected function cleanup(Company $company): void
    {
        foreach ($this->generators as $generator) {
            try {
                $generator->cleanup($company);
            } catch (\Exception $e) {
                Log::error('Failed to cleanup using generator', [
                    'company_id' => $company->id,
                    'generator' => get_class($generator),
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}

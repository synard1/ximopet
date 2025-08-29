<?php

namespace App\Services\CompanySetupService\DataGenerators;

use App\Contracts\CompanySetup\DataGeneratorInterface;
use App\Models\Company;
use App\Config\CompanyConfig;
use Illuminate\Support\Facades\Log;

class SettingsGenerator implements DataGeneratorInterface
{
    /**
     * Generate company settings from template
     */
    public function generate(Company $company): bool
    {
        try {
            // Get template config (all possible settings)
            $templateConfig = CompanyConfig::getDefaultTemplateConfig();

            // Get active config (only ready to use settings)
            $activeConfig = CompanyConfig::getDefaultActiveConfig();

            // Save initial company config merging template and active
            $company->config = array_merge(
                // Base from template for future-proofing
                $templateConfig,
                // Override with active configs
                $activeConfig
            );

            return $company->save();
        } catch (\Exception $e) {
            Log::error('Failed to generate company settings', [
                'company_id' => $company->id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Validate company settings
     */
    public function validate(Company $company): bool
    {
        if (!$company->config) {
            return false;
        }

        // Validate required active configs exist
        $activeConfig = CompanyConfig::getDefaultActiveConfig();
        foreach ($activeConfig as $key => $value) {
            if (!isset($company->config[$key])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Cleanup invalid settings
     */
    public function cleanup(Company $company): void
    {
        // Remove invalid config
        $company->config = [];
        $company->save();
    }
}

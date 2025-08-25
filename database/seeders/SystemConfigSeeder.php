<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config as LaravelConfig;
use Illuminate\Support\Facades\Log;
use App\Models\Company;
use App\Models\AppConfig;
use App\Config\company\CompanyConfigService;

class SystemConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Seeds system configuration data for the system company.
     * This seeder depends on SystemCompanySeeder being run first.
     * Uses configuration from config/xolution.php for consistency and reusability.
     */
    public function run(): void
    {
        $code = config('xolution.SEEDER.DEFAULT_COMPANY_CODE', 'SYSTEM');

        Log::info('SystemConfigSeeder: Starting system configuration seeding', [
            'company_code' => $code,
            'config_source' => 'xolution.php',
            'timestamp' => now()->toISOString()
        ]);

        try {
            // Get company configuration from xolution.php
            $companyConfig = config("xolution.DEFAULT_COMPANY.{$code}");

            if (!$companyConfig) {
                throw new \RuntimeException("Company configuration for code '{$code}' not found in xolution.php");
            }

            Log::info('SystemConfigSeeder: Loaded company configuration from xolution.php', [
                'company_code' => $code,
                'config_sections' => $companyConfig['config_sections'] ?? [],
                'config_keys' => array_keys($companyConfig)
            ]);

            // Get system company (should be created by SystemCompanySeeder)
            $company = Company::where('code', $code)->first();

            if (!$company) {
                $errorMessage = "System company with code '{$code}' not found. Please run SystemCompanySeeder first.";
                Log::error('SystemConfigSeeder: System company not found', [
                    'company_code' => $code,
                    'error' => $errorMessage,
                    'config_source' => 'xolution.php'
                ]);

                $this->command->error("❌ {$errorMessage}");
                throw new \RuntimeException($errorMessage);
            }

            Log::info('SystemConfigSeeder: Found system company', [
                'company_id' => $company->id,
                'company_code' => $code,
                'company_name' => $company->name,
                'company_type' => $company->type,
                'config_source' => 'xolution.php'
            ]);

            // Get default template configuration
            $defaultTemplate = CompanyConfigService::getDefaultTemplateConfig();

            Log::info('SystemConfigSeeder: Loaded default template configuration', [
                'config_sections' => array_keys($defaultTemplate),
                'config_size' => count($defaultTemplate),
                'expected_sections' => $companyConfig['config_sections'] ?? []
            ]);

            // Validate configuration sections based on xolution.php config
            $this->validateConfigurationSections($defaultTemplate, $companyConfig);

            // Seed AppConfig for system company
            $appConfig = AppConfig::firstOrNew(['company_id' => $company->id]);

            if ($appConfig->exists) {
                Log::info('SystemConfigSeeder: Updating existing AppConfig', [
                    'app_config_id' => $appConfig->id,
                    'company_id' => $company->id,
                    'config_source' => 'xolution.php'
                ]);
            } else {
                Log::info('SystemConfigSeeder: Creating new AppConfig', [
                    'company_id' => $company->id,
                    'config_source' => 'xolution.php'
                ]);
            }

            $appConfig->config = $defaultTemplate;
            $appConfig->save();

            // Also update the company's config field for consistency
            $company->updateConfig($defaultTemplate);

            Log::info('SystemConfigSeeder: Successfully seeded system configuration', [
                'company_id' => $company->id,
                'app_config_id' => $appConfig->id,
                'config_sections' => array_keys($defaultTemplate),
                'config_source' => 'xolution.php',
                'company_features' => $companyConfig['metadata']['features'] ?? []
            ]);

            // Store current company context for other seeders
            LaravelConfig::set('seeder.current_company_id', $company->id);
            LaravelConfig::set('seeder.system_company_id', $company->id);

            $this->command->info("✅ SystemConfigSeeder: Successfully seeded system configuration for company '{$code}'");
            $this->command->info("   📊 Company: {$companyConfig['name']} ({$companyConfig['type']})");
            $this->command->info("   🔧 Config Source: xolution.php");
            $this->command->info("   📋 Config sections: " . implode(', ', array_keys($defaultTemplate)));
            $this->command->info("   🚀 Features: " . implode(', ', $companyConfig['metadata']['features'] ?? []));
        } catch (\Exception $e) {
            Log::error('SystemConfigSeeder: Failed to seed system configuration', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'company_code' => $code,
                'config_source' => 'xolution.php'
            ]);

            $this->command->error("❌ SystemConfigSeeder: Failed to seed system configuration: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Validate configuration sections based on xolution.php config
     * 
     * @param array $templateConfig Template configuration
     * @param array $companyConfig Company configuration from xolution.php
     * @return void
     */
    protected function validateConfigurationSections(array $templateConfig, array $companyConfig): void
    {
        $expectedSections = $companyConfig['config_sections'] ?? [];
        $templateSections = array_keys($templateConfig);

        // Check if all expected sections are present in template
        foreach ($expectedSections as $section => $enabled) {
            if ($enabled && !in_array($section, $templateSections)) {
                Log::warning("SystemConfigSeeder: Expected config section '{$section}' not found in template", [
                    'expected_sections' => $expectedSections,
                    'template_sections' => $templateSections
                ]);
            }
        }

        // Log validation results
        $enabledSections = array_filter($expectedSections);
        $enabledSectionNames = array_keys($enabledSections);

        Log::info('SystemConfigSeeder: Configuration section validation completed', [
            'enabled_sections' => $enabledSectionNames,
            'disabled_sections' => array_keys(array_filter($expectedSections, fn($enabled) => !$enabled)),
            'template_sections' => $templateSections
        ]);
    }

    /**
     * Get company configuration from xolution.php
     * 
     * @param string $code Company code
     * @return array|null Company configuration
     */
    protected function getCompanyConfig(string $code): ?array
    {
        return config("xolution.DEFAULT_COMPANY.{$code}");
    }

    /**
     * Check if configuration section is enabled for company
     * 
     * @param array $companyConfig Company configuration
     * @param string $section Configuration section name
     * @return bool Whether section is enabled
     */
    protected function isSectionEnabled(array $companyConfig, string $section): bool
    {
        return $companyConfig['config_sections'][$section] ?? false;
    }
}

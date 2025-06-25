<?php

namespace App\Livewire\Company;

use Livewire\Component;
use App\Models\Company;
use App\Config\CompanyConfig;
use Illuminate\Support\Facades\Log;

class CompanySettings extends Component
{
    public Company $company;
    public array $settings = [];
    public array $purchasingSettings = [];
    public array $livestockSettings = [];
    public array $mutationSettings = [];
    public array $usageSettings = [];
    public array $notificationSettings = [];
    public array $reportingSettings = [];
    public array $templateConfig = [];

    public function mount(Company $company)
    {
        $this->company = $company;
        $dbSettings = $company->config ?? [];
        $activeConfig = CompanyConfig::getDefaultActiveConfig();
        $this->templateConfig = CompanyConfig::getDefaultTemplateConfig();
        $this->settings = $dbSettings;

        // Inisialisasi settings hanya untuk section yang ada di active config, merge DB config jika ada
        foreach ($activeConfig as $section => $default) {
            $fromDb = $dbSettings[$section] ?? [];
            // Merge DB config dengan default agar key baru tetap muncul
            $merged = array_replace_recursive($default, $fromDb);
            $this->{$section . 'Settings'} = $merged;
        }

        // Apply livestock multiple batch logic
        $this->applyLivestockMultipleBatchLogic();

        Log::info('CompanySettings mount', [
            'settings' => $this->settings,
            'activeConfig' => $activeConfig,
            'sections' => array_keys($activeConfig)
        ]);
    }

    /**
     * Apply livestock multiple batch business logic
     * - If allow_multiple_batches is false: set recording type to 'total' and hide methods
     * - If allow_multiple_batches is true: set recording type to 'batch' and show methods
     */
    private function applyLivestockMultipleBatchLogic()
    {
        if (!isset($this->livestockSettings['recording_method'])) {
            return;
        }

        $allowMultipleBatches = $this->livestockSettings['recording_method']['allow_multiple_batches'] ?? true;

        if (!$allowMultipleBatches) {
            // Force recording type to 'total' when multiple batches is disabled
            $this->livestockSettings['recording_method']['type'] = 'total';

            // Hide/disable all methods by setting them to development status
            $this->hideAllLivestockMethods();

            Log::info('Livestock multiple batch disabled - forced to total recording', [
                'company_id' => $this->company->id,
                'recording_type' => 'total'
            ]);
        } else {
            // Force recording type to 'batch' when multiple batches is enabled
            $this->livestockSettings['recording_method']['type'] = 'batch';

            // Ensure methods are available based on config
            $this->showAvailableLivestockMethods();

            Log::info('Livestock multiple batch enabled - forced to batch recording', [
                'company_id' => $this->company->id,
                'recording_type' => 'batch'
            ]);
        }
    }

    /**
     * Hide all livestock methods when multiple batches is disabled
     */
    private function hideAllLivestockMethods()
    {
        $methodTypes = ['depletion_methods', 'mutation_methods', 'feed_usage_methods'];

        foreach ($methodTypes as $methodType) {
            if (isset($this->livestockSettings['recording_method']['batch_settings'][$methodType])) {
                foreach ($this->livestockSettings['recording_method']['batch_settings'][$methodType] as $method => &$config) {
                    if (is_array($config)) {
                        $config['enabled'] = false;
                        $config['status'] = 'not_applicable';
                    }
                }
            }
        }
    }

    /**
     * Show available livestock methods when multiple batches is enabled
     */
    private function showAvailableLivestockMethods()
    {
        $defaultConfig = CompanyConfig::getDefaultLivestockConfig();
        $methodTypes = ['depletion_methods', 'mutation_methods', 'feed_usage_methods'];

        foreach ($methodTypes as $methodType) {
            if (isset($defaultConfig['recording_method']['batch_settings'][$methodType])) {
                $defaultMethods = $defaultConfig['recording_method']['batch_settings'][$methodType];

                foreach ($defaultMethods as $method => $config) {
                    if (is_array($config) && isset($config['enabled'], $config['status'])) {
                        // Restore original enabled/status from default config
                        $this->livestockSettings['recording_method']['batch_settings'][$methodType][$method]['enabled'] = $config['enabled'];
                        $this->livestockSettings['recording_method']['batch_settings'][$methodType][$method]['status'] = $config['status'];
                    }
                }
            }
        }
    }

    /**
     * Check if livestock multiple batches is allowed
     */
    public function isLivestockMultipleBatchesAllowed(): bool
    {
        return $this->livestockSettings['recording_method']['allow_multiple_batches'] ?? true;
    }

    /**
     * Check if livestock recording type is editable
     * Recording type is not editable - it's automatically determined by allow_multiple_batches setting
     */
    public function isLivestockRecordingTypeEditable(): bool
    {
        return false; // Always false as per requirement - automatically determined
    }

    /**
     * Get livestock recording type (automatically determined)
     */
    public function getLivestockRecordingType(): string
    {
        $allowMultipleBatches = $this->isLivestockMultipleBatchesAllowed();
        return $allowMultipleBatches ? 'batch' : 'total';
    }

    /**
     * Check if livestock methods should be visible
     */
    public function shouldShowLivestockMethods(): bool
    {
        return $this->isLivestockMultipleBatchesAllowed();
    }

    /**
     * Handle when allow_multiple_batches is changed
     */
    public function updatedLivestockSettingsRecordingMethodAllowMultipleBatches($value)
    {
        Log::info('Livestock allow_multiple_batches changed', [
            'company_id' => $this->company->id,
            'new_value' => $value
        ]);

        // Reapply the logic when the setting changes
        $this->applyLivestockMultipleBatchLogic();

        // Dispatch event to update UI
        $this->dispatch('livestockMultipleBatchChanged', [
            'allow_multiple_batches' => $value,
            'recording_type' => $this->getLivestockRecordingType(),
            'show_methods' => $this->shouldShowLivestockMethods()
        ]);
    }

    /**
     * Recursively force enabled=true for features present/enabled in activeConfig, and clean up user disables
     */
    private function forceEnableFeatures(array &$userConfig, array $activeConfig)
    {
        foreach ($activeConfig as $key => $value) {
            if (is_array($value)) {
                // Pastikan $userConfig[$key] adalah array
                if (!isset($userConfig[$key]) || !is_array($userConfig[$key])) {
                    $userConfig[$key] = [];
                }
                if (isset($value['enabled'])) {
                    if ($value['enabled'] === true) {
                        $userConfig[$key]['enabled'] = true;
                    } elseif (isset($userConfig[$key]['enabled'])) {
                        $userConfig[$key]['enabled'] = false;
                    }
                }
                if (isset($userConfig[$key]) && is_array($userConfig[$key])) {
                    $this->forceEnableFeatures($userConfig[$key], $value);
                }
            }
        }
    }

    /**
     * Helper untuk mengambil sub config secara dinamis dari active config
     */
    public function getSubConfig($section, $subSection)
    {
        return CompanyConfig::getActiveConfigSection($section, $subSection);
    }

    /**
     * Helper untuk mengecek apakah fitur/subfitur enabled
     */
    public function isFeatureEnabled($config, $key)
    {
        return isset($config[$key]['enabled']) ? $config[$key]['enabled'] : (isset($config[$key]) ? (is_array($config[$key]) ? ($config[$key]['enabled'] ?? true) : $config[$key]) : false);
    }

    /**
     * Helper untuk mengecek apakah seluruh fitur di section dinonaktifkan
     */
    public function isSectionEnabled($sectionSettings)
    {
        if (!is_array($sectionSettings)) return false;
        foreach ($sectionSettings as $key => $sub) {
            // Jika ada subfitur yang array dan punya key 'enabled', cek itu
            if (is_array($sub) && array_key_exists('enabled', $sub)) {
                if ($sub['enabled']) return true;
            } elseif (is_array($sub)) {
                // Jika subfitur nested, cek recursive
                if ($this->isSectionEnabled($sub)) return true;
            } elseif ($sub === true) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get config value by dot notation path
     */
    public function getConfigValue($path, $default = null)
    {
        $keys = explode('.', $path);
        $value = $this->settings;

        foreach ($keys as $key) {
            if (is_array($value) && array_key_exists($key, $value)) {
                $value = $value[$key];
            } else {
                return $default;
            }
        }

        return $value;
    }

    /**
     * Set config value by dot notation path
     */
    public function setConfigValue($path, $value)
    {
        $keys = explode('.', $path);
        $current = &$this->settings;

        foreach ($keys as $i => $key) {
            if ($i === count($keys) - 1) {
                $current[$key] = $value;
            } else {
                if (!isset($current[$key]) || !is_array($current[$key])) {
                    $current[$key] = [];
                }
                $current = &$current[$key];
            }
        }

        // Also update the specific section settings
        $section = $keys[0];
        if (property_exists($this, $section . 'Settings')) {
            $sectionData = $this->settings[$section] ?? [];
            $this->{$section . 'Settings'} = $sectionData;
        }
    }

    /**
     * Validate configuration before saving
     */
    private function validateConfiguration()
    {
        $errors = [];

        // Validate livestock depletion settings
        if (isset($this->livestockSettings['depletion_tracking']['input_restrictions'])) {
            $restrictions = $this->livestockSettings['depletion_tracking']['input_restrictions'];

            if (
                isset($restrictions['max_depletion_per_day_per_batch']) &&
                (!is_numeric($restrictions['max_depletion_per_day_per_batch']) ||
                    $restrictions['max_depletion_per_day_per_batch'] < 1)
            ) {
                $errors[] = 'Maximum depletion per day per batch must be a positive number';
            }

            if (
                isset($restrictions['min_interval_minutes']) &&
                (!is_numeric($restrictions['min_interval_minutes']) ||
                    $restrictions['min_interval_minutes'] < 0)
            ) {
                $errors[] = 'Minimum interval minutes must be a non-negative number';
            }
        }

        return $errors;
    }

    public function saveSettings()
    {
        // Apply livestock multiple batch logic before saving
        $this->applyLivestockMultipleBatchLogic();

        // Validate configuration
        $validationErrors = $this->validateConfiguration();
        if (!empty($validationErrors)) {
            $this->dispatch('error', implode(', ', $validationErrors));
            return;
        }

        $activeConfig = CompanyConfig::getDefaultActiveConfig();
        $saveSettings = [];

        foreach ($activeConfig as $section => $default) {
            $saveSettings[$section] = $this->{$section . 'Settings'};
        }

        $this->settings = $saveSettings;
        $this->company->config = $this->settings;
        $this->company->save();

        Log::info('CompanySettings saved', [
            'company_id' => $this->company->id,
            'settings' => $this->settings,
            'sections_saved' => array_keys($saveSettings),
            'livestock_recording_type' => $this->getLivestockRecordingType(),
            'allow_multiple_batches' => $this->isLivestockMultipleBatchesAllowed()
        ]);

        $this->dispatch('success', 'Company settings updated successfully');
    }

    public function render()
    {
        $activeConfig = CompanyConfig::getDefaultActiveConfig();

        return view('livewire.company.company-settings', [
            'templateConfig' => $this->templateConfig,
            'activeConfig' => $activeConfig,
        ]);
    }
}

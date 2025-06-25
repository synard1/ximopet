<?php

namespace App\Livewire\MasterData\Livestock;

use Livewire\Component;
use App\Models\Company;
use App\Models\Livestock;
use App\Models\CompanyUser;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use App\Config\CompanyConfig;
use App\Services\ConfigurationService;
use Exception;

class Settings extends Component
{
    public $company_id;
    public $livestock_id;
    public $livestock_name;
    public $recording_method;
    public $depletion_method;
    public $mutation_method;
    public $feed_usage_method;
    public $is_override = false;
    public $has_single_batch = false;
    public $showFormSettings = false;
    public $available_methods = [];

    protected $listeners = [
        'setLivestockIdSetting' => 'setLivestockIdSetting'
    ];

    public function mount()
    {
        // Get company from CompanyUser mapping
        $companyUser = CompanyUser::getUserMapping();
        $this->company_id = $companyUser ? $companyUser->company_id : null;
    }

    public function setLivestockIdSetting($id, $name = null)
    {
        $this->livestock_id = $id;
        $this->livestock_name = $name;

        // Force clear any cached data
        $this->available_methods = [];

        $this->loadConfig();

        // Additional debug logging
        Log::info('Livestock Settings - After setLivestockIdSetting', [
            'livestock_id' => $this->livestock_id,
            'livestock_name' => $this->livestock_name,
            'has_single_batch' => $this->has_single_batch,
            'available_methods_keys' => array_keys($this->available_methods),
            'depletion_methods' => array_keys($this->available_methods['depletion_methods'] ?? []),
            'mutation_methods' => array_keys($this->available_methods['mutation_methods'] ?? []),
            'feed_usage_methods' => array_keys($this->available_methods['feed_usage_methods'] ?? []),
        ]);

        // Dispatch browser event to open modal
        $this->dispatch('show-livestock-setting');
        $this->showFormSettings = true;
    }

    public function closeSettings()
    {
        $this->showFormSettings = false;
        $this->dispatch('hide-livestock-setting');
    }

    public function loadConfig()
    {
        if (!$this->livestock_id) return;

        // Check if livestock has single batch
        $livestock = Livestock::find($this->livestock_id);
        if ($livestock) {
            $this->has_single_batch = $livestock->getActiveBatchesCount() <= 1;
        }

        // Use ConfigurationService for safe config loading
        $companyId = $this->company_id ? (int) $this->company_id : null;
        $config = ConfigurationService::getMergedConfig($companyId, 'livestock');

        // Log the config being used
        Log::info('Livestock Settings - Config Source', [
            'livestock_id' => $this->livestock_id,
            'using_configuration_service' => true,
            'company_id' => $this->company_id,
        ]);

        // Load all available methods from config
        $this->available_methods = [
            'recording_method' => ['batch', 'total'], // This can remain simple
            'depletion_methods' => $config['recording_method']['batch_settings']['depletion_methods'],
            'mutation_methods' => $config['recording_method']['batch_settings']['mutation_methods'],
            'feed_usage_methods' => $config['recording_method']['batch_settings']['feed_usage_methods'],
        ];

        // Debug logging with detailed method info
        Log::info('Livestock Settings - Available Methods Loaded', [
            'livestock_id' => $this->livestock_id,
            'depletion_methods_count' => count($this->available_methods['depletion_methods']),
            'mutation_methods_count' => count($this->available_methods['mutation_methods']),
            'feed_usage_methods_count' => count($this->available_methods['feed_usage_methods']),
            'depletion_manual_config' => $this->available_methods['depletion_methods']['manual'] ?? 'NOT_FOUND',
            'feed_usage_manual_config' => $this->available_methods['feed_usage_methods']['manual'] ?? 'NOT_FOUND',
        ]);

        // Specific manual method validation
        $depletionManual = $this->available_methods['depletion_methods']['manual'] ?? null;
        $feedUsageManual = $this->available_methods['feed_usage_methods']['manual'] ?? null;

        Log::info('Manual Method Validation', [
            'depletion_manual_exists' => $depletionManual !== null,
            'depletion_manual_enabled' => $depletionManual['enabled'] ?? 'NOT_SET',
            'depletion_manual_status' => $depletionManual['status'] ?? 'NOT_SET',
            'depletion_manual_usable' => $depletionManual ? $this->isMethodUsable($depletionManual) : false,
            'feed_usage_manual_exists' => $feedUsageManual !== null,
            'feed_usage_manual_enabled' => $feedUsageManual['enabled'] ?? 'NOT_SET',
            'feed_usage_manual_status' => $feedUsageManual['status'] ?? 'NOT_SET',
            'feed_usage_manual_usable' => $feedUsageManual ? $this->isMethodUsable($feedUsageManual) : false,
        ]);

        // Load saved configuration from livestock data if exists
        $savedConfig = null;
        if ($livestock && $livestock->data && isset($livestock->data['config'])) {
            $savedConfig = $livestock->data['config'];
            Log::info('Livestock Settings - Found Saved Config', [
                'livestock_id' => $this->livestock_id,
                'saved_config' => $savedConfig,
            ]);
        }

        // Set defaults based on config rules (auto_select = true OR fallback to first ready method)
        $depletionDefault = $this->findDefaultMethod($this->available_methods['depletion_methods']);
        $mutationDefault = $this->findDefaultMethod($this->available_methods['mutation_methods']);
        $feedUsageDefault = $this->findDefaultMethod($this->available_methods['feed_usage_methods']);

        // Apply saved configuration if exists, otherwise use defaults
        if ($savedConfig) {
            // Use saved configuration
            $this->recording_method = $savedConfig['recording_method'] ?? ($this->has_single_batch ? 'total' : 'batch');
            $this->depletion_method = $savedConfig['depletion_method'] ?? $depletionDefault;
            $this->mutation_method = $savedConfig['mutation_method'] ?? $mutationDefault;
            $this->feed_usage_method = $savedConfig['feed_usage_method'] ?? ($this->has_single_batch ? 'total' : $feedUsageDefault);

            Log::info('Livestock Settings - Applied Saved Config', [
                'livestock_id' => $this->livestock_id,
                'from_saved_config' => true,
                'recording_method' => $this->recording_method,
                'depletion_method' => $this->depletion_method,
                'mutation_method' => $this->mutation_method,
                'feed_usage_method' => $this->feed_usage_method,
            ]);
        } else {
            // Use default configuration rules
            if ($this->has_single_batch) {
                $this->recording_method = 'total';
                $this->depletion_method = $depletionDefault;
                $this->mutation_method = $mutationDefault;
                $this->feed_usage_method = 'total'; // Single batch uses 'total' for feed usage
            } else {
                $this->recording_method = 'batch';
                $this->depletion_method = $depletionDefault;
                $this->mutation_method = $mutationDefault;
                $this->feed_usage_method = $feedUsageDefault;
            }

            Log::info('Livestock Settings - Applied Default Config', [
                'livestock_id' => $this->livestock_id,
                'from_saved_config' => false,
                'recording_method' => $this->recording_method,
                'depletion_method' => $this->depletion_method,
                'mutation_method' => $this->mutation_method,
                'feed_usage_method' => $this->feed_usage_method,
            ]);
        }
    }

    /**
     * Find default method based on config rules:
     * 1. Method with auto_select = true
     * 2. First method with status = 'ready' and enabled = true
     * 3. First enabled method as fallback
     */
    private function findDefaultMethod($methods)
    {
        if (empty($methods)) return null;

        // Rule 1: Find method with auto_select = true
        foreach ($methods as $key => $method) {
            if (isset($method['auto_select']) && $method['auto_select'] === true) {
                // Validate that auto_select method is also usable
                if ($this->isMethodUsable($method)) {
                    return $key;
                }
            }
        }

        // Rule 2: Find first method with status = 'ready' and enabled = true
        foreach ($methods as $key => $method) {
            if ($this->isMethodUsable($method)) {
                return $key;
            }
        }

        // Rule 3: Fallback to first enabled method (ignore status)
        foreach ($methods as $key => $method) {
            if (isset($method['enabled']) && $method['enabled'] === true) {
                return $key;
            }
        }

        // Ultimate fallback: first method key
        return array_key_first($methods);
    }

    /**
     * Check if method is usable based on config rules:
     * - enabled = true
     * - status = 'ready' (not 'development' or 'not_applicable')
     */
    private function isMethodUsable($method)
    {
        // Check if enabled
        if (!isset($method['enabled']) || $method['enabled'] !== true) {
            return false;
        }

        // Check status - only 'ready' methods are usable
        if (!isset($method['status']) || $method['status'] !== 'ready') {
            return false;
        }

        return true;
    }

    /**
     * Get status text for display based on method configuration
     */
    public function getStatusText($method)
    {
        // Check if method is enabled first
        if (!isset($method['enabled']) || $method['enabled'] !== true) {
            return 'Tidak Aktif';
        }

        // Check status
        $status = $method['status'] ?? 'unknown';

        switch ($status) {
            case 'ready':
                return 'Tersedia';
            case 'development':
                return 'Dalam Pengembangan';
            case 'not_applicable':
                return 'Tidak Berlaku';
            default:
                return 'Status Tidak Diketahui';
        }
    }

    public function saveRecordingMethod()
    {
        $user = auth()->user();

        // Get company from CompanyUser mapping
        $companyUser = CompanyUser::getUserMapping();
        if (!$companyUser) {
            Log::info('Save recording method failed: user not mapped to any company', [
                'user_id' => $user->id
            ]);
            return;
        }

        if (!$this->livestock_id) {
            Log::info('Save recording method failed: livestock_id not found');
            return;
        }

        $livestock = Livestock::find($this->livestock_id);
        if (!$livestock) {
            Log::info('Save recording method failed: livestock not found', [
                'livestock_id' => $this->livestock_id
            ]);
            return;
        }

        Log::info('Saving recording method configuration to livestock data', [
            'livestock_id' => $this->livestock_id,
            'company_id' => $companyUser->company_id,
            'recording_method' => $this->recording_method,
            'depletion_method' => $this->depletion_method,
            'mutation_method' => $this->mutation_method,
            'feed_usage_method' => $this->feed_usage_method
        ]);

        // Prepare configuration data
        $recordingConfig = [
            'recording_method' => $this->recording_method,
            'depletion_method' => $this->depletion_method,
            'mutation_method' => $this->mutation_method,
            'feed_usage_method' => $this->feed_usage_method,
            'saved_at' => now()->toDateTimeString(),
            'saved_by' => $user->id
        ];

        // Save configuration to livestock data column
        $success = $livestock->updateDataColumn('config', $recordingConfig);

        if ($success) {
            Log::info('Recording method configuration saved successfully to livestock data', [
                'livestock_id' => $this->livestock_id,
                'company_id' => $companyUser->company_id,
                'config' => $recordingConfig
            ]);

            $this->dispatch('success', 'Pengaturan berhasil disimpan');
            $this->showFormSettings = false;
            $this->dispatch('hide-livestock-setting');
        } else {
            Log::error('Failed to save recording method configuration to livestock data', [
                'livestock_id' => $this->livestock_id,
                'company_id' => $companyUser->company_id
            ]);

            $this->dispatch('error', 'Gagal menyimpan pengaturan');
        }
    }

    public function resetFormSettings()
    {
        $this->livestock_id = null;
        $this->livestock_name = null;
        $this->recording_method = null;
        $this->depletion_method = null;
        $this->mutation_method = null;
        $this->feed_usage_method = null;
        $this->is_override = false;
        $this->has_single_batch = false;
        $this->showFormSettings = false;
    }

    public function testConfig()
    {
        $defaultConfig = CompanyConfig::getDefaultLivestockConfig();

        // Test type conversion fix
        Log::info('Test Config - Type Conversion Test', [
            'original_company_id' => $this->company_id,
            'company_id_type' => gettype($this->company_id),
            'converted_company_id' => $this->company_id ? (int) $this->company_id : null,
            'converted_type' => gettype($this->company_id ? (int) $this->company_id : null),
        ]);

        // Test ConfigurationService with type conversion
        try {
            $companyId = $this->company_id ? (int) $this->company_id : null;
            $config = ConfigurationService::getMergedConfig($companyId, 'livestock');

            Log::info('Test Config - ConfigurationService Success', [
                'config_loaded' => true,
                'company_id_used' => $companyId,
                'config_sections' => array_keys($config),
            ]);
        } catch (Exception $e) {
            Log::error('Test Config - ConfigurationService Failed', [
                'error' => $e->getMessage(),
                'company_id' => $this->company_id,
            ]);
        }

        // Test manual method configurations specifically
        $depletionMethods = $defaultConfig['recording_method']['batch_settings']['depletion_methods'];
        $feedUsageMethods = $defaultConfig['recording_method']['batch_settings']['feed_usage_methods'];

        $depletionManual = $depletionMethods['manual'] ?? null;
        $feedUsageManual = $feedUsageMethods['manual'] ?? null;

        // Log the test results
        Log::info('Test Config - Manual Method Analysis', [
            'depletion_manual_config' => $depletionManual,
            'depletion_manual_enabled' => $depletionManual['enabled'] ?? 'NOT_SET',
            'depletion_manual_status' => $depletionManual['status'] ?? 'NOT_SET',
            'depletion_manual_should_show_tersedia' =>
            isset($depletionManual['enabled']) && $depletionManual['enabled'] === true &&
                isset($depletionManual['status']) && $depletionManual['status'] === 'ready',
            'feed_usage_manual_config' => $feedUsageManual,
            'feed_usage_manual_enabled' => $feedUsageManual['enabled'] ?? 'NOT_SET',
            'feed_usage_manual_status' => $feedUsageManual['status'] ?? 'NOT_SET',
            'feed_usage_manual_should_show_tersedia' =>
            isset($feedUsageManual['enabled']) && $feedUsageManual['enabled'] === true &&
                isset($feedUsageManual['status']) && $feedUsageManual['status'] === 'ready',
        ]);

        // Test getStatusText method directly
        if ($depletionManual) {
            $depletionStatusText = $this->getStatusText($depletionManual);
            Log::info('Test Config - Depletion Manual Status Text', [
                'status_text' => $depletionStatusText,
                'expected' => 'Tersedia',
                'matches_expected' => $depletionStatusText === 'Tersedia',
            ]);
        }

        if ($feedUsageManual) {
            $feedUsageStatusText = $this->getStatusText($feedUsageManual);
            Log::info('Test Config - Feed Usage Manual Status Text', [
                'status_text' => $feedUsageStatusText,
                'expected' => 'Tersedia',
                'matches_expected' => $feedUsageStatusText === 'Tersedia',
            ]);
        }

        $this->dispatch('success', 'Configuration test completed. Check logs for detailed analysis including type conversion fix.');
    }

    public function render()
    {
        return view('livewire.master-data.livestock.settings');
    }
}

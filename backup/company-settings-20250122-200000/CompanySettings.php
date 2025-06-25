<?php

namespace App\Livewire\Company;

use Livewire\Component;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\Config\CompanyConfig;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class CompanySettings extends Component
{
    public $company;
    public $company_id;
    public $settings = [];
    public $availableMethods = [];
    public $isLoading = false;

    // Settings properties
    public $purchasingSettings = [];
    public $livestockSettings = [];
    public $mutationSettings = [];
    public $usageSettings = [];
    public $notificationSettings = [];
    public $reportingSettings = [];

    protected $listeners = ['refresh' => '$refresh'];

    public function mount()
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        Log::info('[CompanySettings] Production Mode - Mount started', [
            'user_id' => Auth::id(),
            'timestamp' => now()->format('Y-m-d H:i:s.u')
        ]);

        try {
            $this->isLoading = true;
            // $this->company = Auth::user()->company;

            // Get company from CompanyUser mapping
            $companyUser = CompanyUser::getUserMapping();
            $this->company_id = $companyUser ? $companyUser->company_id : null;
            $this->company = Company::find($this->company_id);
            // dd($this->company);

            if (!$this->company) {
                throw new \Exception('No company found for user');
            }

            $this->loadSettings();
            $this->loadAvailableMethods();
            $this->isLoading = false;

            $endTime = microtime(true);
            $endMemory = memory_get_usage();

            Log::info('[CompanySettings] Production Mode - Mount completed successfully', [
                'execution_time_ms' => round(($endTime - $startTime) * 1000, 2),
                'memory_used_mb' => round(($endMemory - $startMemory) / 1024 / 1024, 2),
                'total_memory_mb' => round($endMemory / 1024 / 1024, 2),
                'settings_count' => count($this->settings),
                'methods_count' => count($this->availableMethods),
                'company_id' => $this->company->id
            ]);
        } catch (\Exception $e) {
            $this->isLoading = false;
            Log::error('[CompanySettings] Production Mode - Mount failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            session()->flash('error', 'Failed to load company settings: ' . $e->getMessage());
        }
    }

    private function loadSettings()
    {
        Log::info('[CompanySettings] Production Mode - Loading settings from database');

        // Get existing config from database
        $existingConfig = $this->company->config ?? [];

        // Load purchasing settings from database or defaults
        $this->purchasingSettings = [
            'feed_usage_method' => $existingConfig['purchasing']['feed_purchase']['batch_settings']['feed_usage_method'] ?? 'fifo',
            'auto_alerts' => $existingConfig['purchasing']['auto_alerts'] ?? true,
            'minimum_stock_level' => $existingConfig['purchasing']['minimum_stock_level'] ?? 100,
            'reorder_point' => $existingConfig['purchasing']['reorder_point'] ?? 50
        ];

        // Load livestock settings from database or defaults
        $livestockConfig = $existingConfig['livestock'] ?? [];
        $recordingMethod = $livestockConfig['recording_method'] ?? [];

        $this->livestockSettings = [
            'recording_method' => $recordingMethod['type'] ?? 'batch',
            'mutation_method' => $recordingMethod['batch_settings']['mutation_method'] ?? 'fifo',
            'allow_multiple_batches' => $recordingMethod['allow_multiple_batches'] ?? true,
            'auto_generate_batch' => $recordingMethod['batch_settings']['auto_generate_batch'] ?? true,
            'default_mortality_rate' => $livestockConfig['performance_metrics']['mortality_rate_threshold'] ?? 5,
            'weight_tracking' => $livestockConfig['weight_tracking']['enabled'] ?? true,
            'age_tracking' => $livestockConfig['lifecycle_management']['age_tracking']['enabled'] ?? true,
            'health_monitoring' => $livestockConfig['health_management']['enabled'] ?? true,
            'vaccination_schedule' => $livestockConfig['health_management']['vaccination_tracking']['enabled'] ?? true,
            'breeding_records' => false, // Not available in current config structure
            'feed_conversion_tracking' => $livestockConfig['feed_tracking']['fcr_calculation'] ?? true,
            'performance_metrics' => $livestockConfig['performance_metrics']['enabled'] ?? true
        ];

        // Load mutation settings from database or defaults
        $this->mutationSettings = [
            'feed_usage_method' => $livestockConfig['recording_method']['batch_settings']['mutation_method'] ?? 'fifo',
            'enabled' => $livestockConfig['depletion_tracking']['enabled'] ?? true,
            'auto_approve' => $livestockConfig['auto_approve'] ?? false,
            'require_reason' => $livestockConfig['depletion_tracking']['types']['culling']['require_reason'] ?? true
        ];

        // Load usage settings from database or defaults
        $this->usageSettings = [
            'feed_usage_method' => $livestockConfig['recording_method']['batch_settings']['feed_usage_method'] ?? 'fifo',
            'enabled' => $livestockConfig['feed_tracking']['enabled'] ?? true,
            'track_wastage' => $livestockConfig['feed_tracking']['track_wastage'] ?? true,
            'daily_reports' => $livestockConfig['reporting']['reports']['inventory_report']['frequency'] === 'daily' ?? true
        ];

        // // Load notification settings from database or defaults
        // $this->notificationSettings = [
        //     'enabled' => $livestockConfig['reporting']['dashboards']['enabled'] ?? true,
        //     'email_alerts' => $existingConfig['notifications']['email_alerts'] ?? true,
        //     'sms_alerts' => $existingConfig['notifications']['sms_alerts'] ?? false
        // ];

        // // Load reporting settings from database or defaults
        // $this->reportingSettings = [
        //     'enabled' => $livestockConfig['reporting']['enabled'] ?? true,
        //     'auto_generate' => $livestockConfig['reporting']['dashboards']['real_time_monitoring'] ?? true,
        //     'schedule' => $livestockConfig['reporting']['reports']['performance_report']['frequency'] ?? 'daily'
        // ];

        // Combine all settings
        $this->settings = [
            'purchasing' => $this->purchasingSettings,
            'livestock' => $this->livestockSettings,
            'mutation' => $this->mutationSettings,
            'usage' => $this->usageSettings,
            // 'notification' => $this->notificationSettings,
            // 'reporting' => $this->reportingSettings
        ];

        Log::info('[CompanySettings] Production Mode - Settings loaded from database', [
            'sections' => array_keys($this->settings),
            'total_options' => array_sum(array_map('count', $this->settings)),
            'source' => 'database'
        ]);
    }

    private function loadAvailableMethods()
    {
        Log::info('[CompanySettings] Production Mode - Loading available methods');

        $this->availableMethods = [
            'depletion_methods' => ['fifo', 'lifo', 'manual'],
            'mutation_methods' => ['fifo', 'lifo', 'manual'],
            'feed_usage_methods' => ['fifo', 'lifo', 'manual']
        ];

        Log::info('[CompanySettings] Production Mode - Available methods loaded', [
            'method_types' => array_keys($this->availableMethods),
            'total_methods' => array_sum(array_map('count', $this->availableMethods))
        ]);
    }

    public function saveSettings()
    {
        $startTime = microtime(true);
        // dd($this->company_id);

        // Re-initialize company if null (session timeout or state issue)
        if (!$this->company) {
            Log::warning('[CompanySettings] Production Mode - Company is null, re-initializing', [
                'user_id' => Auth::id(),
                'auth_user_exists' => Auth::check() ? 'yes' : 'no'
            ]);

            $this->company = Auth::user()->company ?? null;

            if (!$this->company) {
                Log::error('[CompanySettings] Production Mode - Save failed: No company found after re-init', [
                    'user_id' => Auth::id(),
                    'auth_user' => Auth::user() ? 'exists' : 'null'
                ]);

                $this->dispatch('error', 'No company found. Please refresh the page and try again.');
                return;
            }
        }

        Log::info('[CompanySettings] Production Mode - Save settings started', [
            'user_id' => Auth::id(),
            'company_id' => $this->company->id
        ]);

        try {
            $this->isLoading = true;

            // Validate settings
            $this->validate([
                'purchasingSettings.feed_usage_method' => 'required|in:fifo,lifo,manual',
                'livestockSettings.recording_method' => 'required|in:batch,individual',
                'livestockSettings.mutation_method' => 'nullable|in:fifo,lifo,manual'
            ]);

            // Get current config from database
            $currentConfig = $this->company->config ?? [];

            // Update purchasing settings in config structure
            if (!isset($currentConfig['purchasing'])) $currentConfig['purchasing'] = [];
            $currentConfig['purchasing']['auto_alerts'] = $this->purchasingSettings['auto_alerts'];
            $currentConfig['purchasing']['minimum_stock_level'] = $this->purchasingSettings['minimum_stock_level'];
            $currentConfig['purchasing']['reorder_point'] = $this->purchasingSettings['reorder_point'];

            if (!isset($currentConfig['purchasing']['feed_purchase']['batch_settings'])) {
                $currentConfig['purchasing']['feed_purchase']['batch_settings'] = [];
            }
            $currentConfig['purchasing']['feed_purchase']['batch_settings']['feed_usage_method'] = $this->purchasingSettings['feed_usage_method'];

            // Update livestock settings in config structure
            if (!isset($currentConfig['livestock'])) $currentConfig['livestock'] = [];

            // Recording method settings
            if (!isset($currentConfig['livestock']['recording_method'])) $currentConfig['livestock']['recording_method'] = [];
            $currentConfig['livestock']['recording_method']['type'] = $this->livestockSettings['recording_method'];
            $currentConfig['livestock']['recording_method']['allow_multiple_batches'] = $this->livestockSettings['allow_multiple_batches'];

            if (!isset($currentConfig['livestock']['recording_method']['batch_settings'])) {
                $currentConfig['livestock']['recording_method']['batch_settings'] = [];
            }
            $currentConfig['livestock']['recording_method']['batch_settings']['mutation_method'] = $this->livestockSettings['mutation_method'];
            $currentConfig['livestock']['recording_method']['batch_settings']['auto_generate_batch'] = $this->livestockSettings['auto_generate_batch'];
            $currentConfig['livestock']['recording_method']['batch_settings']['feed_usage_method'] = $this->usageSettings['feed_usage_method'];

            // Performance metrics settings
            if (!isset($currentConfig['livestock']['performance_metrics'])) $currentConfig['livestock']['performance_metrics'] = [];
            $currentConfig['livestock']['performance_metrics']['enabled'] = $this->livestockSettings['performance_metrics'];
            $currentConfig['livestock']['performance_metrics']['mortality_rate_threshold'] = $this->livestockSettings['default_mortality_rate'];

            // Weight tracking settings
            if (!isset($currentConfig['livestock']['weight_tracking'])) $currentConfig['livestock']['weight_tracking'] = [];
            $currentConfig['livestock']['weight_tracking']['enabled'] = $this->livestockSettings['weight_tracking'];

            // Age tracking settings
            if (!isset($currentConfig['livestock']['lifecycle_management']['age_tracking'])) {
                $currentConfig['livestock']['lifecycle_management']['age_tracking'] = [];
            }
            $currentConfig['livestock']['lifecycle_management']['age_tracking']['enabled'] = $this->livestockSettings['age_tracking'];

            // Health management settings
            if (!isset($currentConfig['livestock']['health_management'])) $currentConfig['livestock']['health_management'] = [];
            $currentConfig['livestock']['health_management']['enabled'] = $this->livestockSettings['health_monitoring'];

            if (!isset($currentConfig['livestock']['health_management']['vaccination_tracking'])) {
                $currentConfig['livestock']['health_management']['vaccination_tracking'] = [];
            }
            $currentConfig['livestock']['health_management']['vaccination_tracking']['enabled'] = $this->livestockSettings['vaccination_schedule'];

            // Feed tracking settings
            if (!isset($currentConfig['livestock']['feed_tracking'])) $currentConfig['livestock']['feed_tracking'] = [];
            $currentConfig['livestock']['feed_tracking']['enabled'] = $this->usageSettings['enabled'];
            $currentConfig['livestock']['feed_tracking']['fcr_calculation'] = $this->livestockSettings['feed_conversion_tracking'];
            $currentConfig['livestock']['feed_tracking']['track_wastage'] = $this->usageSettings['track_wastage'];

            // Depletion tracking settings
            if (!isset($currentConfig['livestock']['depletion_tracking'])) $currentConfig['livestock']['depletion_tracking'] = [];
            $currentConfig['livestock']['depletion_tracking']['enabled'] = $this->mutationSettings['enabled'];

            if (!isset($currentConfig['livestock']['depletion_tracking']['types']['culling'])) {
                $currentConfig['livestock']['depletion_tracking']['types']['culling'] = [];
            }
            $currentConfig['livestock']['depletion_tracking']['types']['culling']['require_reason'] = $this->mutationSettings['require_reason'];

            // Reporting settings
            if (!isset($currentConfig['livestock']['reporting'])) $currentConfig['livestock']['reporting'] = [];
            $currentConfig['livestock']['reporting']['enabled'] = $this->reportingSettings['enabled'];

            if (!isset($currentConfig['livestock']['reporting']['dashboards'])) {
                $currentConfig['livestock']['reporting']['dashboards'] = [];
            }
            $currentConfig['livestock']['reporting']['dashboards']['enabled'] = $this->notificationSettings['enabled'];
            $currentConfig['livestock']['reporting']['dashboards']['real_time_monitoring'] = $this->reportingSettings['auto_generate'];

            if (!isset($currentConfig['livestock']['reporting']['reports']['performance_report'])) {
                $currentConfig['livestock']['reporting']['reports']['performance_report'] = [];
            }
            $currentConfig['livestock']['reporting']['reports']['performance_report']['frequency'] = $this->reportingSettings['schedule'];

            if (!isset($currentConfig['livestock']['reporting']['reports']['inventory_report'])) {
                $currentConfig['livestock']['reporting']['reports']['inventory_report'] = [];
            }
            $currentConfig['livestock']['reporting']['reports']['inventory_report']['frequency'] = $this->usageSettings['daily_reports'] ? 'daily' : 'weekly';

            // Notification settings
            if (!isset($currentConfig['notifications'])) $currentConfig['notifications'] = [];
            $currentConfig['notifications']['email_alerts'] = $this->notificationSettings['email_alerts'];
            $currentConfig['notifications']['sms_alerts'] = $this->notificationSettings['sms_alerts'];

            // Save to database
            $this->company->config = $currentConfig;
            $this->company->save();

            $this->isLoading = false;

            $endTime = microtime(true);

            Log::info('[CompanySettings] Production Mode - Settings saved successfully to database', [
                'execution_time_ms' => round(($endTime - $startTime) * 1000, 2),
                'settings_sections' => count($this->settings),
                'company_id' => $this->company->id,
                'config_size' => strlen(json_encode($currentConfig))
            ]);

            $this->dispatch('success', 'Company settings have been saved successfully to database!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->isLoading = false;

            Log::warning('[CompanySettings] Production Mode - Validation failed', [
                'errors' => $e->errors(),
                'user_id' => Auth::id(),
                'company_id' => $this->company->id ?? 'null'
            ]);

            // Let Livewire handle validation errors automatically
            throw $e;
        } catch (\Exception $e) {
            $this->isLoading = false;

            Log::error('[CompanySettings] Production Mode - Save failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'company_exists' => $this->company ? 'yes' : 'no',
                'user_id' => Auth::id()
            ]);

            $this->dispatch('error', 'Failed to save settings: ' . $e->getMessage());
        }
    }

    public function testLivestockSettings()
    {
        Log::info('[CompanySettings] Test Livestock Settings', [
            'livestockSettings_exists' => isset($this->livestockSettings),
            'livestockSettings_type' => gettype($this->livestockSettings),
            'livestockSettings_count' => count($this->livestockSettings ?? []),
            'livestockSettings_keys' => array_keys($this->livestockSettings ?? []),
            'livestockSettings_data' => $this->livestockSettings
        ]);

        session()->flash('info', 'Livestock settings test completed. Check logs for details.');
    }

    public function debug()
    {
        // Re-initialize company if null
        if (!$this->company) {
            $this->company = Auth::user()->company ?? null;
        }

        Log::info('[CompanySettings] Production Mode - Debug requested', [
            'settings_count' => count($this->settings),
            'methods_count' => count($this->availableMethods),
            'loading_state' => $this->isLoading,
            'company_id' => $this->company->id ?? 'N/A',
            'user_id' => Auth::id()
        ]);

        session()->flash('info', 'Debug information logged. Check logs for details.');
    }

    public function resetLoadingState()
    {
        Log::info('[CompanySettings] Production Mode - Reset loading state');

        $this->isLoading = false;
        $this->loadSettings();
        $this->loadAvailableMethods();

        session()->flash('success', 'Component state has been reset successfully.');
    }

    public function render()
    {
        $startTime = microtime(true);

        Log::info('[CompanySettings] Production Mode - Rendering', [
            'settings_sections' => count($this->settings),
            'available_methods' => count($this->availableMethods),
            'loading_state' => $this->isLoading,
            'livestock_settings_count' => count($this->livestockSettings),
            'livestock_settings_keys' => array_keys($this->livestockSettings),
            'purchasing_settings_count' => count($this->purchasingSettings)
        ]);

        $view = view('livewire.company.company-settings-production');

        $endTime = microtime(true);

        Log::info('[CompanySettings] Production Mode - Render completed', [
            'render_time_ms' => round(($endTime - $startTime) * 1000, 2),
            'memory_mb' => round(memory_get_usage() / 1024 / 1024, 2)
        ]);

        return $view;
    }
}

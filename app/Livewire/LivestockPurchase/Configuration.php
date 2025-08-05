<?php

namespace App\Livewire\LivestockPurchase;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Log;
use App\Config\LivestockPurchaseConfig;
use App\Models\Company;
use Illuminate\Support\Facades\Auth;

/**
 * Livestock Purchase Configuration Component
 * 
 * Component untuk mengelola konfigurasi sistem pembelian ternak
 * Mencakup workflow, validasi, approval, dan business rules
 */
class Configuration extends Component
{
    use WithPagination;

    // Component properties
    public $company;
    public $config = [];
    public $activeTab = 'workflow';
    public $isEditing = false;
    public $showResetModal = false;
    public $showSaveModal = false;
    public $hasChanges = false;
    public $originalConfig = [];

    // Form properties
    public $workflowConfig = [];
    public $validationConfig = [];
    public $approvalConfig = [];
    public $batchConfig = [];
    public $costConfig = [];
    public $documentConfig = [];
    public $notificationConfig = [];
    public $businessRulesConfig = [];
    public $reportingConfig = [];
    public $integrationConfig = [];

    // Validation messages
    protected $messages = [
        'workflowConfig.status_flow.*.enabled.required' => 'Status harus diaktifkan atau dinonaktifkan',
        'validationConfig.required_fields.*.required' => 'Field wajib harus ditentukan',
        'approvalConfig.levels.*.name.required' => 'Nama level approval harus diisi',
        'batchConfig.enabled.required' => 'Batch management harus diaktifkan atau dinonaktifkan',
    ];

    /**
     * Mount component
     */
    public function mount(Company $company = null)
    {
        $this->company = $company ?? Auth::user()->company;
        $this->loadConfiguration();
        Log::info("[LivestockPurchaseConfig] Component mounted for company: {$this->company->name}");
    }

    /**
     * Load configuration from company or default
     */
    public function loadConfiguration()
    {
        try {
            // Load from company config if exists, otherwise use default
            $companyConfig = $this->company->config ?? [];
            $defaultConfig = LivestockPurchaseConfig::getConfig();

            $this->config = array_merge($defaultConfig, $companyConfig['livestock_purchase'] ?? []);
            $this->originalConfig = $this->config;

            // Initialize form properties
            $this->workflowConfig = $this->config['workflow'] ?? [];
            $this->validationConfig = $this->config['validation'] ?? [];
            $this->approvalConfig = $this->config['approval'] ?? [];
            $this->batchConfig = $this->config['batch_management'] ?? [];
            $this->costConfig = $this->config['cost_tracking'] ?? [];
            $this->documentConfig = $this->config['document_management'] ?? [];
            $this->notificationConfig = $this->config['notification'] ?? [];
            $this->businessRulesConfig = $this->config['business_rules'] ?? [];
            $this->reportingConfig = $this->config['reporting'] ?? [];
            $this->integrationConfig = $this->config['integration'] ?? [];

            Log::info("[LivestockPurchaseConfig] Configuration loaded successfully");
        } catch (\Exception $e) {
            Log::error("[LivestockPurchaseConfig] Error loading configuration: " . $e->getMessage());
            session()->flash('error', 'Gagal memuat konfigurasi: ' . $e->getMessage());
        }
    }

    /**
     * Set active tab
     */
    public function setTab($tab)
    {
        $this->activeTab = $tab;
        Log::info("[LivestockPurchaseConfig] Tab changed to: {$tab}");
    }

    /**
     * Start editing mode
     */
    public function startEditing()
    {
        $this->isEditing = true;
        $this->hasChanges = false;
        Log::info("[LivestockPurchaseConfig] Entered editing mode");
    }

    /**
     * Cancel editing
     */
    public function cancelEditing()
    {
        $this->isEditing = false;
        $this->hasChanges = false;
        $this->loadConfiguration(); // Reload original config
        Log::info("[LivestockPurchaseConfig] Cancelled editing");
    }

    /**
     * Save configuration
     */
    public function saveConfiguration()
    {
        try {
            $this->validate();

            // Update config with form data
            $this->config['workflow'] = $this->workflowConfig;
            $this->config['validation'] = $this->validationConfig;
            $this->config['approval'] = $this->approvalConfig;
            $this->config['batch_management'] = $this->batchConfig;
            $this->config['cost_tracking'] = $this->costConfig;
            $this->config['document_management'] = $this->documentConfig;
            $this->config['notification'] = $this->notificationConfig;
            $this->config['business_rules'] = $this->businessRulesConfig;
            $this->config['reporting'] = $this->reportingConfig;
            $this->config['integration'] = $this->integrationConfig;

            // Save to company config
            $companyConfig = $this->company->config ?? [];
            $companyConfig['livestock_purchase'] = $this->config;

            $this->company->update(['config' => $companyConfig]);

            $this->isEditing = false;
            $this->hasChanges = false;
            $this->originalConfig = $this->config;

            Log::info("[LivestockPurchaseConfig] Configuration saved successfully");
            session()->flash('success', 'Konfigurasi berhasil disimpan');
        } catch (\Exception $e) {
            Log::error("[LivestockPurchaseConfig] Error saving configuration: " . $e->getMessage());
            session()->flash('error', 'Gagal menyimpan konfigurasi: ' . $e->getMessage());
        }
    }

    /**
     * Reset to default configuration
     */
    public function resetToDefault()
    {
        try {
            $defaultConfig = LivestockPurchaseConfig::getConfig();

            // Update company config
            $companyConfig = $this->company->config ?? [];
            $companyConfig['livestock_purchase'] = $defaultConfig;

            $this->company->update(['config' => $companyConfig]);

            // Reload configuration
            $this->loadConfiguration();
            $this->showResetModal = false;

            Log::info("[LivestockPurchaseConfig] Configuration reset to default");
            session()->flash('success', 'Konfigurasi berhasil direset ke default');
        } catch (\Exception $e) {
            Log::error("[LivestockPurchaseConfig] Error resetting configuration: " . $e->getMessage());
            session()->flash('error', 'Gagal mereset konfigurasi: ' . $e->getMessage());
        }
    }

    /**
     * Toggle workflow status
     */
    public function toggleWorkflowStatus($status)
    {
        if (isset($this->workflowConfig['status_flow'][$status])) {
            $this->workflowConfig['status_flow'][$status]['enabled'] =
                !$this->workflowConfig['status_flow'][$status]['enabled'];
            $this->hasChanges = true;

            Log::info("[LivestockPurchaseConfig] Toggled workflow status: {$status}");
        }
    }

    /**
     * Update workflow settings
     */
    public function updateWorkflowSettings($status, $field, $value)
    {
        if (isset($this->workflowConfig['status_flow'][$status])) {
            $this->workflowConfig['status_flow'][$status][$field] = $value;
            $this->hasChanges = true;

            Log::info("[LivestockPurchaseConfig] Updated workflow settings: {$status}.{$field} = {$value}");
        }
    }

    /**
     * Toggle validation rule
     */
    public function toggleValidationRule($section, $field)
    {
        if (isset($this->validationConfig[$section][$field])) {
            $this->validationConfig[$section][$field] = !$this->validationConfig[$section][$field];
            $this->hasChanges = true;

            Log::info("[LivestockPurchaseConfig] Toggled validation rule: {$section}.{$field}");
        }
    }

    /**
     * Update approval settings
     */
    public function updateApprovalSettings($level, $field, $value)
    {
        if (isset($this->approvalConfig['levels'][$level])) {
            $this->approvalConfig['levels'][$level][$field] = $value;
            $this->hasChanges = true;

            Log::info("[LivestockPurchaseConfig] Updated approval settings: level {$level}.{$field} = {$value}");
        }
    }

    /**
     * Toggle batch management feature
     */
    public function toggleBatchFeature($feature)
    {
        if (isset($this->batchConfig[$feature])) {
            $this->batchConfig[$feature] = !$this->batchConfig[$feature];
            $this->hasChanges = true;

            Log::info("[LivestockPurchaseConfig] Toggled batch feature: {$feature}");
        }
    }

    /**
     * Update cost tracking settings
     */
    public function updateCostSettings($section, $field, $value)
    {
        if (isset($this->costConfig[$section][$field])) {
            $this->costConfig[$section][$field] = $value;
            $this->hasChanges = true;

            Log::info("[LivestockPurchaseConfig] Updated cost settings: {$section}.{$field} = {$value}");
        }
    }

    /**
     * Toggle notification channel
     */
    public function toggleNotificationChannel($channel)
    {
        if (isset($this->notificationConfig['channels'][$channel])) {
            $this->notificationConfig['channels'][$channel]['enabled'] =
                !$this->notificationConfig['channels'][$channel]['enabled'];
            $this->hasChanges = true;

            Log::info("[LivestockPurchaseConfig] Toggled notification channel: {$channel}");
        }
    }

    /**
     * Update business rules
     */
    public function updateBusinessRule($section, $field, $value)
    {
        if (isset($this->businessRulesConfig[$section][$field])) {
            $this->businessRulesConfig[$section][$field] = $value;
            $this->hasChanges = true;

            Log::info("[LivestockPurchaseConfig] Updated business rule: {$section}.{$field} = {$value}");
        }
    }

    /**
     * Test configuration
     */
    public function testConfiguration()
    {
        try {
            // Validate configuration
            $this->validateConfiguration();

            // Test workflow
            $this->testWorkflow();

            // Test validation rules
            $this->testValidationRules();

            Log::info("[LivestockPurchaseConfig] Configuration test completed successfully");
            session()->flash('success', 'Test konfigurasi berhasil - semua pengaturan valid');
        } catch (\Exception $e) {
            Log::error("[LivestockPurchaseConfig] Configuration test failed: " . $e->getMessage());
            session()->flash('error', 'Test konfigurasi gagal: ' . $e->getMessage());
        }
    }

    /**
     * Validate configuration
     */
    private function validateConfiguration()
    {
        $errors = [];

        // Validate workflow
        if (!isset($this->workflowConfig['status_flow'])) {
            $errors[] = 'Workflow status flow tidak ditemukan';
        }

        // Validate approval levels
        if (isset($this->approvalConfig['levels'])) {
            foreach ($this->approvalConfig['levels'] as $level => $config) {
                if (empty($config['name'])) {
                    $errors[] = "Nama level approval {$level} tidak boleh kosong";
                }
            }
        }

        // Validate business rules
        if (isset($this->businessRulesConfig['purchase_limits'])) {
            $limits = $this->businessRulesConfig['purchase_limits'];
            if (isset($limits['daily_limit']['max_quantity']) && $limits['daily_limit']['max_quantity'] <= 0) {
                $errors[] = 'Limit harian harus lebih dari 0';
            }
        }

        if (!empty($errors)) {
            throw new \Exception(implode(', ', $errors));
        }
    }

    /**
     * Test workflow configuration
     */
    private function testWorkflow()
    {
        $statusFlow = $this->workflowConfig['status_flow'] ?? [];

        foreach ($statusFlow as $status => $config) {
            if ($config['enabled']) {
                // Test status transition
                $nextStatuses = $config['next_statuses'] ?? [];
                foreach ($nextStatuses as $nextStatus) {
                    if (!isset($statusFlow[$nextStatus])) {
                        throw new \Exception("Status '{$nextStatus}' tidak ditemukan dalam workflow");
                    }
                }
            }
        }
    }

    /**
     * Test validation rules
     */
    private function testValidationRules()
    {
        $validation = $this->validationConfig ?? [];

        // Test required fields
        $requiredFields = $validation['required_fields'] ?? [];
        foreach ($requiredFields as $field => $required) {
            if ($required && !in_array($field, ['tanggal', 'supplier_id', 'farm_id', 'coop_id', 'invoice_number'])) {
                // Additional validation for custom required fields
            }
        }
    }

    /**
     * Get configuration summary
     */
    public function getConfigurationSummary()
    {
        $summary = [
            'total_statuses' => count($this->workflowConfig['status_flow'] ?? []),
            'enabled_statuses' => 0,
            'approval_required' => false,
            'batch_management_enabled' => $this->batchConfig['enabled'] ?? false,
            'cost_tracking_enabled' => $this->costConfig['enabled'] ?? false,
            'notification_channels' => 0,
            'business_rules_count' => 0,
        ];

        // Count enabled statuses
        foreach ($this->workflowConfig['status_flow'] ?? [] as $status => $config) {
            if ($config['enabled']) {
                $summary['enabled_statuses']++;
            }
            if ($config['requires_approval']) {
                $summary['approval_required'] = true;
            }
        }

        // Count notification channels
        foreach ($this->notificationConfig['channels'] ?? [] as $channel => $config) {
            if ($config['enabled']) {
                $summary['notification_channels']++;
            }
        }

        // Count business rules
        foreach ($this->businessRulesConfig ?? [] as $section => $rules) {
            $summary['business_rules_count'] += count($rules);
        }

        return $summary;
    }

    /**
     * Export configuration
     */
    public function exportConfiguration()
    {
        try {
            $exportData = [
                'company_name' => $this->company->name,
                'export_date' => now()->format('Y-m-d H:i:s'),
                'configuration' => $this->config,
                'summary' => $this->getConfigurationSummary(),
            ];

            $filename = "livestock_purchase_config_{$this->company->name}_" . now()->format('Y-m-d_H-i-s') . ".json";

            Log::info("[LivestockPurchaseConfig] Configuration exported: {$filename}");

            return response()->json($exportData)
                ->header('Content-Disposition', "attachment; filename={$filename}")
                ->header('Content-Type', 'application/json');
        } catch (\Exception $e) {
            Log::error("[LivestockPurchaseConfig] Export failed: " . $e->getMessage());
            session()->flash('error', 'Gagal mengekspor konfigurasi: ' . $e->getMessage());
        }
    }

    /**
     * Import configuration
     */
    public function importConfiguration($file)
    {
        try {
            $content = file_get_contents($file->getRealPath());
            $importData = json_decode($content, true);

            if (!$importData || !isset($importData['configuration'])) {
                throw new \Exception('File konfigurasi tidak valid');
            }

            // Validate imported configuration
            $this->validateImportedConfig($importData['configuration']);

            // Update configuration
            $this->config = $importData['configuration'];
            $this->loadConfiguration();

            Log::info("[LivestockPurchaseConfig] Configuration imported successfully");
            session()->flash('success', 'Konfigurasi berhasil diimpor');
        } catch (\Exception $e) {
            Log::error("[LivestockPurchaseConfig] Import failed: " . $e->getMessage());
            session()->flash('error', 'Gagal mengimpor konfigurasi: ' . $e->getMessage());
        }
    }

    /**
     * Validate imported configuration
     */
    private function validateImportedConfig($config)
    {
        $requiredSections = ['workflow', 'validation', 'approval', 'batch_management'];

        foreach ($requiredSections as $section) {
            if (!isset($config[$section])) {
                throw new \Exception("Section '{$section}' tidak ditemukan dalam file konfigurasi");
            }
        }
    }

    /**
     * Render component
     */
    public function render()
    {
        $summary = $this->getConfigurationSummary();

        return view('livewire.livestock-purchase.configuration', [
            'summary' => $summary,
            'tabs' => [
                'workflow' => 'Workflow',
                'validation' => 'Validasi',
                'approval' => 'Approval',
                'batch' => 'Batch Management',
                'cost' => 'Cost Tracking',
                'document' => 'Document Management',
                'notification' => 'Notifikasi',
                'business_rules' => 'Business Rules',
                'reporting' => 'Reporting',
                'integration' => 'Integrasi',
            ],
        ]);
    }
}

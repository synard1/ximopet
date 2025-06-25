<?php

namespace App\Livewire\Company;

use Livewire\Component;
use App\Models\Company;
use App\Config\CompanyConfig;

class LivestockRecordingSettings extends Component
{
    public $company;
    public $recordingType = 'batch';
    public $allowMultipleBatches = true;
    public $batchSettings = [];
    public $totalSettings = [];
    public $isEditing = false;

    protected $rules = [
        'recordingType' => 'required|in:batch,total',
        'allowMultipleBatches' => 'boolean',
        'batchSettings.enabled' => 'boolean',
        'batchSettings.auto_generate_batch' => 'boolean',
        'batchSettings.require_batch_number' => 'boolean',
        'batchSettings.track_batch_details' => 'boolean',
        'batchSettings.batch_details.weight' => 'boolean',
        'batchSettings.batch_details.age' => 'boolean',
        'batchSettings.batch_details.breed' => 'boolean',
        'batchSettings.batch_details.health_status' => 'boolean',
        'batchSettings.batch_details.notes' => 'boolean',
        'totalSettings.enabled' => 'boolean',
        'totalSettings.track_total_only' => 'boolean',
        'totalSettings.total_details.total_count' => 'boolean',
        'totalSettings.total_details.average_weight' => 'boolean',
        'totalSettings.total_details.total_weight' => 'boolean',
    ];

    public function mount(Company $company)
    {
        $this->company = $company;
        $this->loadSettings();
    }

    public function loadSettings()
    {
        $config = $this->company->getLivestockRecordingConfig();

        $this->recordingType = $config['type'] ?? 'batch';
        $this->allowMultipleBatches = $config['allow_multiple_batches'] ?? true;
        $this->batchSettings = $config['batch_settings'] ?? [];
        $this->totalSettings = $config['total_settings'] ?? [];
    }

    public function toggleEdit()
    {
        $this->isEditing = !$this->isEditing;
    }

    public function save()
    {
        $this->validate();

        try {
            $config = [
                'type' => $this->recordingType,
                'allow_multiple_batches' => $this->allowMultipleBatches,
                'batch_settings' => $this->batchSettings,
                'total_settings' => $this->totalSettings
            ];

            $this->company->updateLivestockRecordingConfig($config);

            $this->isEditing = false;
            $this->dispatch('success', 'Livestock recording settings updated successfully.');
        } catch (\Exception $e) {
            $this->dispatch('error', 'Failed to update settings: ' . $e->getMessage());
        }
    }

    public function resetToDefaults()
    {
        $defaultConfig = CompanyConfig::getDefaultConfig()['livestock']['recording_method'];

        $this->recordingType = $defaultConfig['type'];
        $this->allowMultipleBatches = $defaultConfig['allow_multiple_batches'];
        $this->batchSettings = $defaultConfig['batch_settings'];
        $this->totalSettings = $defaultConfig['total_settings'];
    }

    public function render()
    {
        return view('livewire.company.livestock-recording-settings');
    }
}

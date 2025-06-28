<?php

namespace App\Livewire\Company;

use App\Models\Company;
use App\Config\CompanyConfig;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class CompanyForm extends Component
{
    use WithFileUploads;

    public $companyId;
    public $name;
    public $address;
    public $phone;
    public $email;
    public $logo;
    public $domain;
    public $database;
    public $package;
    public $status = 'active';
    public $notes;
    public $isEditing = false;
    public $showForm = false;
    public $livestockRecordingType = 'batch';
    public $allowMultipleBatches = true;
    public $batchSettings = [];
    public $totalSettings = [];

    protected $rules = [
        'name' => 'required|min:3',
        'address' => 'required',
        'phone' => 'required',
        'email' => 'required|email',
        'domain' => 'nullable',
        'database' => 'nullable',
        'package' => 'nullable',
        'status' => 'required',
        'logo' => 'nullable|file|image|max:5120', // 5MB in kilobytes
        'livestockRecordingType' => 'required|in:batch,total',
        'allowMultipleBatches' => 'boolean',
        'batchSettings' => 'array',
        'totalSettings' => 'array',
    ];

    public $listeners = [
        'editCompany' => 'edit',
        'createCompany' => 'createCompany',
        'closeForm' => 'closeForm',
        'deleteCompany' => 'delete',
        'resetConfig' => 'resetConfig',
        'updateConfigSection' => 'updateConfigSection',
    ];

    public function mount()
    {
        $this->resetForm();
        $this->initializeLivestockConfig();
    }

    protected function initializeLivestockConfig()
    {
        if ($this->isEditing && $this->companyId) {
            $company = Company::find($this->companyId);
            $recordingConfig = $company->getLivestockRecordingConfig();

            $this->livestockRecordingType = $recordingConfig['type'] ?? 'batch';
            $this->allowMultipleBatches = $recordingConfig['allow_multiple_batches'] ?? true;
            $this->batchSettings = $recordingConfig['batch_settings'] ?? [];
            $this->totalSettings = $recordingConfig['total_settings'] ?? [];
        }
    }

    public function createCompany()
    {
        $this->resetForm();
        $this->isEditing = false;
        $this->showForm = true;
    }

    public function edit($id)
    {
        $company = Company::findOrFail($id);

        // Check if user has permission to edit this company
        if (!auth()->user()->hasRole('SuperAdmin') && $company->id !== auth()->user()->company_id) {
            session()->flash('error', 'You do not have permission to edit this company.');
            return;
        }

        // If config is missing or empty, set to default
        if (empty($company->config)) {
            $company->config = CompanyConfig::getDefaultConfig();
            $company->save();
        }

        $this->companyId = $company->id;
        $this->name = $company->name;
        $this->address = $company->address;
        $this->phone = $company->phone;
        $this->email = $company->email;
        $this->domain = $company->domain;
        $this->database = $company->database;
        $this->package = $company->package;
        $this->status = $company->status;
        $this->notes = $company->notes;
        $this->logo = $company->logo;
        $this->isEditing = true;
        $this->showForm = true;
    }

    public function save()
    {
        Log::info('CompanyForm save method called', [
            'is_editing' => $this->isEditing,
            'company_id' => $this->companyId,
            'form_data' => [
                'name' => $this->name,
                'email' => $this->email,
                'phone' => $this->phone,
                'address' => $this->address,
                'status' => $this->status,
            ]
        ]);

        $this->validate();

        try {
            $data = [
                'name' => $this->name,
                'address' => $this->address,
                'phone' => $this->phone,
                'email' => $this->email,
                'domain' => $this->domain ?? null,
                'database' => $this->database ?? null,
                'package' => $this->package ?? null,
                'status' => $this->status,
                'notes' => $this->notes ?? null,
            ];

            // Handle logo upload
            if (is_object($this->logo) && method_exists($this->logo, 'getRealPath')) {
                $imageContents = file_get_contents($this->logo->getRealPath());
                $mimeType = $this->logo->getMimeType();
                $base64 = 'data:' . $mimeType . ';base64,' . base64_encode($imageContents);
                $data['logo'] = $base64;
            } elseif (is_string($this->logo) && str_starts_with($this->logo, 'data:image')) {
                $data['logo'] = $this->logo;
            }

            if ($this->isEditing) {
                $company = Company::find($this->companyId);

                // Update company basic info
                $company->update($data);

                // Update livestock recording configuration
                $recordingConfig = [
                    'type' => $this->livestockRecordingType,
                    'allow_multiple_batches' => $this->allowMultipleBatches,
                    'batch_settings' => $this->batchSettings,
                    'total_settings' => $this->totalSettings
                ];
                $company->updateLivestockRecordingConfig($recordingConfig);

                Log::info('Company updated successfully', [
                    'company_id' => $company->id,
                    'name' => $company->name,
                    'livestock_config' => $recordingConfig
                ]);

                session()->flash('message', 'Company updated successfully.');
                $this->dispatch('success', 'Company updated successfully.');
            } else {
                // Get default config for new company
                $defaultConfig = CompanyConfig::getDefaultConfig();

                // Update livestock recording config in default config
                $defaultConfig['livestock']['recording_method'] = [
                    'type' => $this->livestockRecordingType,
                    'allow_multiple_batches' => $this->allowMultipleBatches,
                    'batch_settings' => $this->batchSettings,
                    'total_settings' => $this->totalSettings
                ];

                $data['config'] = $defaultConfig;
                $company = Company::create($data);

                Log::info('Company created successfully with config', [
                    'company_id' => $company->id,
                    'name' => $company->name,
                    'livestock_config' => $defaultConfig['livestock']['recording_method']
                ]);

                session()->flash('message', 'Company created successfully with configuration.');
                $this->dispatch('success', 'Company created successfully.');
            }

            $this->resetForm();
            $this->dispatch('closeForm');
            $this->showForm = false;
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error in CompanyForm save', [
                'errors' => $e->errors(),
                'form_data' => $data ?? []
            ]);
            throw $e; // Re-throw to let Livewire handle validation errors
        } catch (\Exception $e) {
            Log::error('Error saving company', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'company_data' => $data ?? []
            ]);
            session()->flash('error', 'Error saving company: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        // Only SuperAdmin can delete companies
        if (!auth()->user()->hasRole('SuperAdmin')) {
            session()->flash('error', 'You do not have permission to delete companies.');
            return;
        }

        $company = Company::findOrFail($id);

        // Check if company has any active user mappings
        if (\App\Models\CompanyUser::where('company_id', $id)->where('status', 'active')->exists()) {
            session()->flash('error', 'Cannot delete company because it has active user mappings. Please remove all user mappings first.');
            return;
        }

        if ($company->logo) {
            Storage::disk('public')->delete($company->logo);
        }

        $company->delete();
        session()->flash('message', 'Company deleted successfully.');
        $this->dispatch('closeForm');
    }

    /**
     * Reset company configuration to defaults
     */
    public function resetConfig($id)
    {
        try {
            $company = Company::findOrFail($id);

            // Check if user has permission
            if (!auth()->user()->hasRole('SuperAdmin') && $company->id !== auth()->user()->company_id) {
                session()->flash('error', 'You do not have permission to reset configuration.');
                return;
            }

            $defaultConfig = CompanyConfig::getDefaultConfig();
            $company->update(['config' => $defaultConfig]);

            Log::info('Company configuration reset to defaults', [
                'company_id' => $company->id,
                'name' => $company->name
            ]);

            session()->flash('message', 'Company configuration reset to defaults successfully.');
        } catch (\Exception $e) {
            Log::error('Error resetting company configuration', [
                'error' => $e->getMessage(),
                'company_id' => $id
            ]);
            session()->flash('error', 'Error resetting configuration: ' . $e->getMessage());
        }
    }

    /**
     * Update specific configuration section
     */
    public function updateConfigSection($id, $section, $config)
    {
        try {
            $company = Company::findOrFail($id);

            // Check if user has permission
            if (!auth()->user()->hasRole('SuperAdmin') && $company->id !== auth()->user()->company_id) {
                session()->flash('error', 'You do not have permission to update configuration.');
                return;
            }

            $company->updateConfigSection($section, $config);

            Log::info('Company configuration section updated', [
                'company_id' => $company->id,
                'name' => $company->name,
                'section' => $section
            ]);

            session()->flash('message', 'Configuration section updated successfully.');
        } catch (\Exception $e) {
            Log::error('Error updating company configuration section', [
                'error' => $e->getMessage(),
                'company_id' => $id,
                'section' => $section
            ]);
            session()->flash('error', 'Error updating configuration: ' . $e->getMessage());
        }
    }

    /**
     * Close the form
     */
    public function closeForm()
    {
        $this->resetForm();
        $this->showForm = false;
        $this->isEditing = false;
    }

    private function resetForm()
    {
        $this->companyId = null;
        $this->name = '';
        $this->address = '';
        $this->phone = '';
        $this->email = '';
        $this->logo = null;
        $this->domain = null;
        $this->database = null;
        $this->package = null;
        $this->status = 'active';
        $this->notes = '';
        $this->livestockRecordingType = 'batch';
        $this->allowMultipleBatches = true;
        $this->batchSettings = [];
        $this->totalSettings = [];
    }

    public function render()
    {
        return view('livewire.company.form');
    }
}

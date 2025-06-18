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

    protected $rules = [
        'name' => 'required|min:3',
        'address' => 'required',
        'phone' => 'required',
        'email' => 'required|email',
        'domain' => 'required',
        'database' => 'required',
        'package' => 'required',
        'status' => 'required',
        'logo' => 'nullable|file|image|max:5120', // 5MB in kilobytes
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
        // Dynamic validation for logo
        $rules = [
            'name' => 'required|min:3',
            'address' => 'required',
            'phone' => 'required',
            'email' => 'required|email',
            'status' => 'required',
        ];
        // Only validate logo as file if it's an instance of UploadedFile
        if (is_object($this->logo) && method_exists($this->logo, 'getRealPath')) {
            $rules['logo'] = 'nullable|file|image|max:5120';
        }
        $this->validate($rules);

        // Check if user has permission to save
        if (!$this->isEditing && !auth()->user()->hasRole('SuperAdmin')) {
            session()->flash('error', 'You do not have permission to create companies.');
            return;
        }

        if ($this->isEditing && !auth()->user()->hasRole('SuperAdmin') && $this->companyId !== auth()->user()->company_id) {
            session()->flash('error', 'You do not have permission to edit this company.');
            return;
        }

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

            if (is_object($this->logo) && method_exists($this->logo, 'getRealPath')) {
                // Convert image to base64 and keep original size
                $imageContents = file_get_contents($this->logo->getRealPath());
                $mimeType = $this->logo->getMimeType();
                $base64 = 'data:' . $mimeType . ';base64,' . base64_encode($imageContents);
                $data['logo'] = $base64;
            } elseif (is_string($this->logo) && str_starts_with($this->logo, 'data:image')) {
                $data['logo'] = $this->logo;
            }

            if ($this->isEditing) {
                $company = Company::find($this->companyId);
                // If config is missing or empty, set to default
                if (empty($company->config)) {
                    $data['config'] = CompanyConfig::getDefaultConfig();
                }
                $company->update($data);
                Log::info('Company updated successfully', [
                    'company_id' => $company->id,
                    'name' => $company->name
                ]);
                session()->flash('message', 'Company updated successfully.');
                $this->dispatch('success', 'Company updated successfully.');
            } else {
                // Get default config for new company
                $defaultConfig = CompanyConfig::getDefaultConfig();
                $data['config'] = $defaultConfig;

                $company = Company::create($data);
                Log::info('Company created successfully with default config', [
                    'company_id' => $company->id,
                    'name' => $company->name,
                    'config' => $defaultConfig
                ]);
                session()->flash('message', 'Company created successfully with default configuration.');
                $this->dispatch('success', 'Company created successfully.');
            }

            $this->resetForm();
            $this->dispatch('closeForm');
            $this->showForm = false;
        } catch (\Exception $e) {
            Log::error('Error saving company', [
                'error' => $e->getMessage(),
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
    }

    public function render()
    {
        return view('livewire.company.form');
    }
}

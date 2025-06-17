<?php

namespace App\Livewire\Company;

use App\Models\Company;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

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
    public $keterangan;
    public $isEditing = false;

    protected $rules = [
        'name' => 'required|min:3',
        'address' => 'required',
        'phone' => 'required',
        'email' => 'required|email',
        'domain' => 'required',
        'database' => 'required',
        'package' => 'required',
        'status' => 'required',
    ];

    public function mount()
    {
        $this->resetForm();
    }

    public function createCompany()
    {
        $this->resetForm();
        $this->isEditing = false;
    }

    public function edit($id)
    {
        $company = Company::findOrFail($id);

        // Check if user has permission to edit this company
        if (!auth()->user()->hasRole('SuperAdmin') && $company->id !== auth()->user()->company_id) {
            session()->flash('error', 'You do not have permission to edit this company.');
            return;
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
        $this->keterangan = $company->keterangan;
        $this->isEditing = true;
    }

    public function save()
    {
        $this->validate();

        // Check if user has permission to save
        if (!$this->isEditing && !auth()->user()->hasRole('SuperAdmin')) {
            session()->flash('error', 'You do not have permission to create companies.');
            return;
        }

        if ($this->isEditing && !auth()->user()->hasRole('SuperAdmin') && $this->companyId !== auth()->user()->company_id) {
            session()->flash('error', 'You do not have permission to edit this company.');
            return;
        }

        $data = [
            'name' => $this->name,
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'domain' => $this->domain,
            'database' => $this->database,
            'package' => $this->package,
            'status' => $this->status,
            'keterangan' => $this->keterangan,
        ];

        if ($this->logo) {
            $data['logo'] = $this->logo->store('company-logos', 'public');
        }

        if ($this->isEditing) {
            Company::find($this->companyId)->update($data);
            session()->flash('message', 'Company updated successfully.');
        } else {
            Company::create($data);
            session()->flash('message', 'Company created successfully.');
        }

        $this->resetForm();
        $this->dispatch('closeForm');
    }

    public function delete($id)
    {
        // Only SuperAdmin can delete companies
        if (!auth()->user()->hasRole('SuperAdmin')) {
            session()->flash('error', 'You do not have permission to delete companies.');
            return;
        }

        $company = Company::findOrFail($id);

        if ($company->logo) {
            Storage::disk('public')->delete($company->logo);
        }

        $company->delete();
        session()->flash('message', 'Company deleted successfully.');
        $this->dispatch('closeForm');
    }

    private function resetForm()
    {
        $this->companyId = null;
        $this->name = '';
        $this->address = '';
        $this->phone = '';
        $this->email = '';
        $this->logo = null;
        $this->domain = '';
        $this->database = '';
        $this->package = '';
        $this->status = 'active';
        $this->keterangan = '';
    }

    public function render()
    {
        return view('livewire.company.form');
    }
}

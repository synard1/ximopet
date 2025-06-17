<?php

namespace App\Livewire\Company;

use App\Models\Company;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class CompanyIndex extends Component
{
    use WithPagination, WithFileUploads;

    public $search = '';
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
    public $companyId;
    public $isEditing = false;
    public $showModal = false;
    public $isSuperAdmin = false;

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
        $this->isSuperAdmin = Auth::user()->hasRole('SuperAdmin');
    }

    public function render()
    {
        $query = Company::query();

        // If not SuperAdmin, only show user's company
        if (!$this->isSuperAdmin) {
            $query->where('id', Auth::user()->company_id);
        }

        // Apply search if search term exists
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%')
                    ->orWhere('domain', 'like', '%' . $this->search . '%');
            });
        }

        $companies = $query->paginate(10);

        return view('livewire.company.index', [
            'companies' => $companies,
            'isSuperAdmin' => $this->isSuperAdmin
        ]);
    }

    public function create()
    {
        // Only SuperAdmin can create new companies
        if (!$this->isSuperAdmin) {
            session()->flash('error', 'You do not have permission to create companies.');
            return;
        }

        $this->resetInputs();
        $this->isEditing = false;
        $this->showModal = true;
    }

    public function edit(Company $company)
    {
        // Check if user has permission to edit this company
        if (!$this->isSuperAdmin && $company->id !== Auth::user()->company_id) {
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
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        // Check if user has permission to save
        if (!$this->isSuperAdmin && $this->companyId !== Auth::user()->company_id) {
            session()->flash('error', 'You do not have permission to save this company.');
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

        $this->closeModal();
        $this->resetInputs();
    }

    public function delete(Company $company)
    {
        // Only SuperAdmin can delete companies
        if (!$this->isSuperAdmin) {
            session()->flash('error', 'You do not have permission to delete companies.');
            return;
        }

        if ($company->logo) {
            Storage::disk('public')->delete($company->logo);
        }
        $company->delete();
        session()->flash('message', 'Company deleted successfully.');
    }

    private function resetInputs()
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

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetInputs();
    }
}

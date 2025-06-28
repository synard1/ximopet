<?php

namespace App\Livewire\Company;

use Livewire\Component;
use App\Models\CompanyUser;
use App\Models\User;
use App\Models\Company;

class CompanyUserMappingForm extends Component
{
    public $mappingId;
    public $user_id;
    public $company_id;
    public $status = 'active';
    public $isAdmin = 0;
    public $showForm = false;

    public $users = [];
    public $companies = [];

    protected $rules = [
        'user_id' => 'required|exists:users,id',
        'company_id' => 'required|exists:companies,id',
        'status' => 'required|in:active,inactive',
        'isAdmin' => 'required|boolean',
    ];

    public $listeners = [
        'createMapping' => 'createMapping',
        'createMappingWithId' => 'createMappingWithId',
        'closeMapping' => 'closeMapping'
    ];

    public function mount($mappingId = null)
    {
        $this->users = User::orderBy('name')->get();
        $this->companies = Company::orderBy('name')->get();
        if ($mappingId) {
            $mapping = CompanyUser::findOrFail($mappingId);
            $this->mappingId = $mapping->id;
            $this->user_id = $mapping->user_id;
            $this->company_id = $mapping->company_id;
            $this->status = $mapping->status;
            $this->isAdmin = $mapping->isAdmin;
        }
    }

    public function createMapping()
    {
        $this->showForm = true;
        $this->dispatch('hide-datatable');
    }

    public function createMappingWithId($id)
    {
        $this->showForm = true;
        $this->dispatch('hide-datatable');
        $this->mappingId = $id;
    }

    public function closeMapping()
    {
        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('show-datatable');
    }

    public function save()
    {
        $this->validate();

        // Create or update company user mapping
        // The sync to User model is handled by CompanyUser model events
        $mapping = CompanyUser::updateOrCreate(
            ['id' => $this->mappingId],
            [
                'user_id' => $this->user_id,
                'company_id' => $this->company_id,
                'status' => $this->status,
                'isAdmin' => $this->isAdmin,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]
        );

        $this->dispatch('show-datatable');
        $this->showForm = false;
        $this->dispatch('success', 'Mapping berhasil disimpan!');
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->mappingId = null;
        $this->user_id = null;
        $this->company_id = null;
        $this->status = 'active';
        $this->isAdmin = 0;
    }

    public function render()
    {
        return view('livewire.company.user-mapping-form');
    }
}

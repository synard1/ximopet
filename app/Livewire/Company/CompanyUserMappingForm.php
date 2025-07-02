<?php

namespace App\Livewire\Company;

use Livewire\Component;
use App\Models\CompanyUser;
use App\Models\User;
use App\Models\Company;
use Illuminate\Support\Facades\Log;

class CompanyUserMappingForm extends Component
{
    public $mappingId;
    public $user_id;
    public $company_id;
    public $status = 'active';
    public $isAdmin = 0;
    public $isDefaultAdmin = 0;
    public $showForm = false;

    public $users = [];
    public $companies = [];

    // Validation states
    public $companyHasDefaultAdmin = false;
    public $shouldAutoSetDefaults = false;



    protected $rules = [
        'user_id' => 'required|exists:users,id',
        'company_id' => 'required|exists:companies,id',
        'status' => 'required|in:active,inactive',
        'isAdmin' => 'required|boolean',
        'isDefaultAdmin' => 'required|boolean',
    ];

    public $listeners = [
        'createMapping' => 'createMapping',
        'createMappingWithId' => 'createMappingWithId',
        'closeMapping' => 'closeMapping',
        'confirmClearAllMapping' => 'confirmClearAllMapping',
        'confirmClearMappingForCompany' => 'confirmClearMappingForCompany',
        'clearMappingForCompany' => 'clearMappingForCompany',
        'clearAllMapping' => 'clearAllMapping',
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
            $this->isDefaultAdmin = $mapping->isDefaultAdmin;

            // Check if company has default admin
            $this->checkCompanyDefaultAdminStatus();
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

        // If mappingId valid use existing, else attempt to restore soft-deleted duplicate
        if ($this->mappingId) {
            $mapping = CompanyUser::withTrashed()->find($this->mappingId);
        } else {
            $mapping = CompanyUser::withTrashed()
                ->where('company_id', $this->company_id)
                ->where('user_id', $this->user_id)
                ->first();
        }

        if ($mapping) {
            // restore if trashed
            if ($mapping->trashed()) {
                $mapping->restore();
            }
            $mapping->update([
                'status' => $this->status,
                'isAdmin' => $this->isAdmin,
                'isDefaultAdmin' => $this->isDefaultAdmin,
                'updated_by' => auth()->id(),
            ]);
        } else {
            $mapping = CompanyUser::create([
                'user_id' => $this->user_id,
                'company_id' => $this->company_id,
                'status' => $this->status,
                'isAdmin' => $this->isAdmin,
                'isDefaultAdmin' => $this->isDefaultAdmin,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);
        }

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
        $this->isDefaultAdmin = 0;
        $this->companyHasDefaultAdmin = false;
        $this->shouldAutoSetDefaults = false;
    }

    /**
     * Method called when company_id is updated via wire:model.live
     */
    public function updatedCompanyId($value)
    {
        $this->checkCompanyDefaultAdminStatus();
    }

    /**
     * Check if selected company has default admin and set validation states
     */
    public function checkCompanyDefaultAdminStatus()
    {
        if (!$this->company_id) {
            $this->resetDefaultAdminState();
            return;
        }

        $this->companyHasDefaultAdmin = CompanyUser::hasDefaultAdmin($this->company_id);

        if (!$this->companyHasDefaultAdmin) {
            $this->shouldAutoSetDefaults = true;
            $this->isAdmin = 1;
            $this->isDefaultAdmin = 1;
            $this->dispatch('company-default-admin-check', [
                'hasDefaultAdmin' => false,
                'message' => 'Company belum memiliki Default Admin. User ini akan otomatis dijadikan Admin dan Default Admin.'
            ]);
        } else {
            $this->shouldAutoSetDefaults = false;
            $this->isAdmin = 0;
            $this->isDefaultAdmin = 0;
        }
    }

    /**
     * Reset default admin validation state
     */
    private function resetDefaultAdminState()
    {
        $this->companyHasDefaultAdmin = false;
        $this->shouldAutoSetDefaults = false;
    }

    /**
     * Check if isAdmin checkbox should be disabled
     */
    public function getIsAdminDisabledProperty()
    {
        return $this->shouldAutoSetDefaults;
    }

    /**
     * Check if isDefaultAdmin checkbox should be disabled
     */
    public function getIsDefaultAdminDisabledProperty()
    {
        return $this->shouldAutoSetDefaults || ($this->companyHasDefaultAdmin && !$this->isCurrentDefaultAdmin());
    }

    /**
     * Check if current user is the default admin being edited
     */
    private function isCurrentDefaultAdmin()
    {
        if (!$this->mappingId || !$this->company_id) {
            return false;
        }

        $currentMapping = CompanyUser::find($this->mappingId);
        return $currentMapping && $currentMapping->isDefaultAdmin;
    }

    /**
     * Validate that only one default admin per company
     */
    public function updatedIsDefaultAdmin($value)
    {
        if ($value && $this->company_id) {
            $existingDefaultAdmin = CompanyUser::getDefaultAdmin($this->company_id);

            // If there's already a default admin and it's not current mapping
            if ($existingDefaultAdmin && $existingDefaultAdmin->id !== $this->mappingId) {
                $message = 'Company sudah memiliki Default Admin: ' . $existingDefaultAdmin->user->name . '. Hanya bisa ada 1 Default Admin per company.';

                // Dispatch browser event
                $this->dispatch('default-admin-conflict', [
                    'message' => $message
                ]);

                // Also add session flash for fallback
                session()->flash('error', $message);

                $this->isDefaultAdmin = 0;
                return;
            }

            // If setting as default admin, must be admin too
            if ($value) {
                $this->isAdmin = 1;
            }
        }
    }

    /**
     * Debug method to test company admin status
     */
    public function debugCompanyStatus()
    {
        if (!$this->company_id) {
            session()->flash('debug', 'Tidak ada company yang dipilih');
            return;
        }

        $hasDefaultAdmin = CompanyUser::hasDefaultAdmin($this->company_id);
        $defaultAdmin = CompanyUser::getDefaultAdmin($this->company_id);

        $debugInfo = [
            'company_id' => $this->company_id,
            'hasDefaultAdmin' => $hasDefaultAdmin,
            'defaultAdmin' => $defaultAdmin ? $defaultAdmin->user->name : 'None',
            'shouldAutoSetDefaults' => $this->shouldAutoSetDefaults,
            'companyHasDefaultAdmin' => $this->companyHasDefaultAdmin,
            'isAdmin' => $this->isAdmin,
            'isDefaultAdmin' => $this->isDefaultAdmin
        ];

        Log::info('Debug Company Status', $debugInfo);
        session()->flash('debug', 'Debug info logged. Check logs. Info: ' . json_encode($debugInfo));
    }

    public function confirmClearMappingForCompany()
    {
        if (!$this->company_id) return;
        $this->dispatch('confirm-clear-mapping', [
            'company_id' => $this->company_id,
            'all' => false
        ]);
    }

    public function confirmClearAllMapping()
    {
        $this->dispatch('confirm-clear-mapping', [
            'company_id' => null,
            'all' => true
        ]);
    }

    public function clearMappingForCompany($companyId = null)
    {
        // Jika tidak ada parameter, gunakan state komponen
        $companyId = $companyId ?? $this->company_id;

        Log::info('clearMappingForCompany called', [
            'user_id' => auth()->id(),
            'user_roles' => auth()->user()?->getRoleNames()->toArray(),
            'company_id' => $companyId,
            'has_super_admin_role' => auth()->user()?->hasRole('SuperAdmin')
        ]);

        if (!auth()->user() || !auth()->user()->hasRole('SuperAdmin')) {
            Log::warning('Access denied for clearMappingForCompany', [
                'user_id' => auth()->id(),
                'company_id' => $companyId
            ]);

            $this->dispatch('mapping-cleared', [
                'message' => 'Akses ditolak. Hanya SuperAdmin yang bisa melakukan aksi ini.',
                'error' => true
            ]);
            $this->dispatch('error', 'Akses ditolak. Hanya SuperAdmin yang bisa melakukan aksi ini.');
            return;
        }

        if (!$companyId) {
            Log::warning('No company_id provided for clearMappingForCompany', [
                'user_id' => auth()->id()
            ]);
            $this->dispatch('mapping-cleared', [
                'message' => 'Gagal: Tidak ada company yang dipilih.',
                'error' => true
            ]);
            return;
        }

        Log::info('Starting clearUserMapping', [
            'company_id' => $companyId,
            'user_id' => auth()->id()
        ]);

        $count = \App\Models\CompanyUser::clearUserMapping($companyId);

        if ($count > 0) {
            $this->dispatch('mapping-cleared', [
                'message' => "$count mapping berhasil dihapus untuk company ini.",
                'error' => false
            ]);
        } else {
            $this->dispatch('mapping-cleared', [
                'message' => "Tidak ada data mapping yang ditemukan untuk dihapus pada company ini.",
                'error' => true
            ]);
        }

        $this->resetForm();
    }

    public function clearAllMapping()
    {
        if (!auth()->user() || !auth()->user()->hasRole('SuperAdmin')) {
            $this->dispatch('mapping-cleared', [
                'message' => 'Akses ditolak. Hanya SuperAdmin yang bisa melakukan aksi ini.',
                'error' => true
            ]);
            return;
        }
        $count = CompanyUser::clearUserMapping();

        $this->dispatch('mapping-cleared', [
            'message' => $count
                ? "$count mapping berhasil dihapus."
                : 'Tidak ada data yang dihapus.',
            'error' => $count == 0
        ]);

        $this->resetForm();
    }

    public function render()
    {
        return view('livewire.company.user-mapping-form');
    }
}

<?php

namespace App\Livewire\Company;

use App\Models\CompanyUser;
use App\Models\User;
use App\Services\CompanyAdminManagementService;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CompanyAdminManagement extends Component
{
    use WithPagination;

    public $companyId;
    public $showModal = false;
    public $modalType = 'promote'; // promote, transfer, demote
    public $selectedUserId;
    public $selectedUserName;
    public $setAsDefault = false;
    public $searchTerm = '';
    public $availableCompanies = [];
    public $showCompanySelector = false;

    // Modal data
    public $admins = [];
    public $promotableUsers = [];
    public $defaultAdmin = null;
    public $adminStatistics = [];

    protected $adminService;

    public function boot()
    {
        $this->adminService = app(CompanyAdminManagementService::class);
    }

    protected function getAdminService()
    {
        if (!$this->adminService) {
            $this->adminService = app(CompanyAdminManagementService::class);
        }
        return $this->adminService;
    }

    public function mount($companyId = null)
    {
        // Check if SuperAdmin without specific company
        if (!$companyId && !auth()->user()->company_id && auth()->user()->hasRole('SuperAdmin')) {
            $this->showCompanySelector = true;
            $this->loadAvailableCompanies();
        } else {
            $this->companyId = $companyId ?? auth()->user()->company_id;
        }

        $this->initializeEmptyData();
        $this->loadData();
    }

    protected function initializeEmptyData()
    {
        $this->admins = collect();
        $this->promotableUsers = collect();
        $this->defaultAdmin = null;
        $this->adminStatistics = [
            'total_users' => 0,
            'total_admins' => 0,
            'has_default_admin' => false,
            'default_admin' => null
        ];
    }

    public function loadData()
    {
        if (!$this->companyId) {
            // Don't show error if company selector is active
            if (!$this->showCompanySelector && auth()->user()->hasRole('SuperAdmin')) {
                $this->showCompanySelector = true;
                $this->loadAvailableCompanies();
            }
            return;
        }

        try {
            $this->admins = $this->getAdminService()->getCompanyAdmins($this->companyId);
            $this->promotableUsers = $this->getAdminService()->getPromotableUsers($this->companyId);
            $this->defaultAdmin = $this->getAdminService()->getDefaultAdmin($this->companyId);
            $this->adminStatistics = $this->getAdminService()->getAdminStatistics($this->companyId);
        } catch (\Exception $e) {
            Log::error('Error loading admin data', [
                'company_id' => $this->companyId,
                'error' => $e->getMessage()
            ]);

            session()->flash('error', 'Error loading admin data: ' . $e->getMessage());
            $this->initializeEmptyData();
        }
    }

    public function render()
    {
        return view('livewire.company.company-admin-management');
    }

    public function openPromoteModal($userId, $userName)
    {
        $this->selectedUserId = $userId;
        $this->selectedUserName = $userName;
        $this->modalType = 'promote';
        $this->setAsDefault = false;
        $this->showModal = true;
    }

    public function openTransferModal()
    {
        // Only current default admin can transfer
        if (!$this->getAdminService()->canManageDefaultAdmin($this->companyId)) {
            session()->flash('error', 'You do not have permission to transfer default admin role.');
            return;
        }

        $this->modalType = 'transfer';
        $this->showModal = true;
    }

    public function openDemoteModal($userId, $userName)
    {
        $this->selectedUserId = $userId;
        $this->selectedUserName = $userName;
        $this->modalType = 'demote';
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->selectedUserId = null;
        $this->selectedUserName = null;
        $this->setAsDefault = false;
        $this->modalType = 'promote';
    }

    public function promoteUser()
    {
        $result = $this->getAdminService()->promoteToAdmin(
            $this->companyId,
            $this->selectedUserId,
            $this->setAsDefault
        );

        if ($result['success']) {
            session()->flash('message', $result['message']);
            $this->loadData();
            $this->closeModal();
        } else {
            session()->flash('error', $result['message']);
        }
    }

    public function transferDefaultAdmin($newUserId)
    {
        $result = $this->getAdminService()->transferDefaultAdmin($this->companyId, $newUserId);

        if ($result['success']) {
            session()->flash('message', $result['message']);
            $this->loadData();
            $this->closeModal();
        } else {
            session()->flash('error', $result['message']);
        }
    }

    public function demoteUser()
    {
        $result = $this->getAdminService()->demoteAdmin($this->companyId, $this->selectedUserId);

        if ($result['success']) {
            session()->flash('message', $result['message']);
            $this->loadData();
            $this->closeModal();
        } else {
            session()->flash('error', $result['message']);
        }
    }

    public function setDefaultAdmin($userId)
    {
        $result = $this->getAdminService()->setDefaultAdmin($this->companyId, $userId);

        if ($result['success']) {
            session()->flash('message', $result['message']);
            $this->loadData();
        } else {
            session()->flash('error', $result['message']);
        }
    }

    public function refreshData()
    {
        $this->loadData();
        session()->flash('message', 'Data refreshed successfully.');
    }

    public function getCanManageDefaultAdminProperty()
    {
        return $this->getAdminService()->canManageDefaultAdmin($this->companyId);
    }

    public function getFilteredPromotableUsersProperty()
    {
        if (empty($this->searchTerm)) {
            return collect($this->promotableUsers);
        }

        return collect($this->promotableUsers)->filter(function ($companyUser) {
            return stripos($companyUser->user->name, $this->searchTerm) !== false ||
                stripos($companyUser->user->email, $this->searchTerm) !== false;
        });
    }

    public function getFilteredAdminsProperty()
    {
        if (empty($this->searchTerm)) {
            return collect($this->admins);
        }

        return collect($this->admins)->filter(function ($companyUser) {
            return stripos($companyUser->user->name, $this->searchTerm) !== false ||
                stripos($companyUser->user->email, $this->searchTerm) !== false;
        });
    }

    public function updatedSearchTerm()
    {
        // Reset pagination when search term changes
        $this->resetPage();
    }

    protected function loadAvailableCompanies()
    {
        if (auth()->user()->hasRole('SuperAdmin')) {
            $this->availableCompanies = \App\Models\Company::select('id', 'name', 'status')
                ->where('status', 'active')
                ->orderBy('name')
                ->get();
        }
    }

    public function selectCompany($companyId)
    {
        $this->companyId = $companyId;
        $this->showCompanySelector = false;
        $this->loadData();
        session()->flash('message', 'Company selected successfully.');
    }

    public function showCompanySelectorModal()
    {
        $this->showCompanySelector = true;
        $this->loadAvailableCompanies();
    }

    public function hideCompanySelector()
    {
        $this->showCompanySelector = false;
    }
}

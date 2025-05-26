<?php

namespace App\Livewire\AdminMonitoring;

use Livewire\Component;
use App\Services\PermissionInfoService;

class PermissionInfo extends Component
{
    public $permissions;
    public $currentRoute;
    public $userRoles;
    public $userPermissions;
    public $relevantRoles;
    public $relevantPermissions;

    protected $permissionService;

    public function mount()
    {
        $this->currentRoute = request()->route()->getName(); // Get the current route name
        $this->userRoles = auth()->user()->getRoleNames()->toArray(); // Convert to array
        $this->userPermissions = auth()->user()->getAllPermissions(); // Get user permissions

        // Initialize the service
        $this->permissionService = new PermissionInfoService(auth()->user());

        // Fetch permissions directly from the service
        $this->permissions = $this->permissionService->getPermissions();

        // Load relevant roles and permissions based on the current URL
        $this->loadRelevantRolesAndPermissions();
    }

    private function loadRelevantRolesAndPermissions()
    {
        $url = request()->path();
        $relevantData = $this->permissionService->getRelevantRolesAndPermissions($url);

        // dd($relevantData);

        $this->relevantRoles = $relevantData['roles'];
        $this->relevantPermissions = $relevantData['permissions'];
    }

    public function render()
    {
        return view('livewire.admin-monitoring.permission-info');
    }
}

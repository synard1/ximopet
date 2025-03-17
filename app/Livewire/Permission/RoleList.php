<?php

namespace App\Livewire\Permission;

use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;


class RoleList extends Component
{
    public array|Collection $roles;

    protected $listeners = ['success' => 'updateRoleList'];

    protected function getRoles()
    {
        $query = Role::with('permissions');

        // If user is not SuperAdmin, exclude SuperAdmin role
        if (!Auth::user()->hasRole('SuperAdmin')) {
            $query->where('name', '!=', 'SuperAdmin');
        }

        $roles = $query->get();

        // Filter permissions for each role to exclude those containing 'access'
        foreach ($roles as $role) {
            $role->permissions = $role->permissions->filter(function ($permission) {
                return !Str::contains(strtolower($permission->name), 'access'); // Case-insensitive check
            });
        }

        return $roles;
    }

    public function render()
    {
        $this->roles = $this->getRoles();
        return view('livewire.permission.role-list');
    }

    public function updateRoleList()
    {
        $this->roles = $this->getRoles();
    }

    public function hydrate()
    {
        $this->resetErrorBag();
        $this->resetValidation();
    }
}

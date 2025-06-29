<?php

namespace App\Livewire\Permission;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Livewire\Component;
// use Spatie\Permission\Models\Permission;
// use Spatie\Permission\Models\Role;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User; // Ensure User model is imported
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\RoleBackupService;
use Spatie\ResponseCache\Facades\ResponseCache;


class RoleModal extends Component
{
    public $name;
    public $checked_permissions;
    public $check_all;

    public Role $role;
    public Collection $permissions;
    protected $roleBackupService;

    protected $rules = [
        'name' => 'required|string',
    ];

    protected $listeners = [
        'modal.show.role_name' => 'mountRole',
        'submitRole' => 'submit',
        'delete_role' => 'delete'
    ];

    public function boot(RoleBackupService $roleBackupService)
    {
        $this->roleBackupService = $roleBackupService;
    }

    public function mountRole($role_name = '')
    {
        if (empty($role_name)) {
            // Create new
            $this->role = new Role;
            $this->name = '';
            $this->checked_permissions = [];
            return;
        }

        // Get the role by name.
        $role = Role::where('name', $role_name)->first();
        if (is_null($role)) {
            $this->dispatch('error', 'The selected role [' . $role_name . '] is not found');
            return;
        }

        $this->role = $role;

        // Set the name and checked permissions properties to the role's values.
        $this->name = $this->role->name;
        $this->checked_permissions = $this->role->permissions->pluck('name')->toArray();
    }

    public function mount()
    {
        // Check if the user is an Admin
        if (auth()->user()->hasRole('Administrator')) {
            // Get only the permissions that the current user has
            // $this->permissions = Permission::whereIn('id', function($query) {
            //     $query->select('permission_id')
            //         ->from('model_has_permissions')
            //         ->where('model_type', User::class)
            //         ->where('model_id', auth()->id())
            //         ->where('name', 'like','%access%');
            // })->get();

            // Get only the access permissions
            $this->permissions = Permission::where('name', 'like', '%access%')->get();
        } else {
            // For other roles (like SuperAdmin), get all permissions
            // $this->permissions = Permission::all();
            $this->permissions = Permission::where('name', 'like', '%access%')->get();
        }


        // dump($this->permissions);

        // If the Admin does not have access, hide all permissions
        if (!$this->permissions->isNotEmpty()) {
            $this->permissions = collect(); // Set to empty collection
        }

        // Set the checked permissions property to an empty array
        $this->checked_permissions = [];
    }

    public function render()
    {
        // Define main groups and their keywords
        $main_groups = [
            'Master Data' => ['master data'],
            'Purchasing' => ['purchasing', 'purchasing', 'purchase'],
            'Management' => ['management', 'manage'],
            'Usage' => ['usage', 'use'],
            'Mutation' => ['mutation', 'mutasi'],
            'Report' => ['report'],
        ];

        $permissions_by_main_group = [];
        $other_permissions = [];

        foreach ($this->permissions ?? [] as $permission) {
            $ability = Str::after($permission->name, ' ');
            $grouped = false;
            foreach ($main_groups as $group => $keywords) {
                foreach ($keywords as $keyword) {
                    if (Str::contains(Str::lower($ability), $keyword)) {
                        $query = Permission::where('name', 'like', "%{$ability}%");
                        if (!auth()->user()->hasRole('SuperAdmin')) {
                            $query->where('name', '!=', $permission->name);
                        }
                        $permissions = $query->get();
                        $ordered_permissions = collect(['read', 'create', 'update', 'delete', 'access'])
                            ->map(fn($action) => $permissions->firstWhere('name', "$action $ability"))
                            ->filter()
                            ->values();
                        if ($ordered_permissions->isNotEmpty()) {
                            $permissions_by_main_group[$group][$ability] = $ordered_permissions->all();
                        }
                        $grouped = true;
                        break 2;
                    }
                }
            }
            if (!$grouped) {
                $other_permissions[] = $permission;
            }
        }

        // Group remaining permissions under 'Other'
        foreach ($other_permissions as $permission) {
            $ability = Str::after($permission->name, ' ');
            $query = Permission::where('name', 'like', "%{$ability}%");
            if (!auth()->user()->hasRole('SuperAdmin')) {
                $query->where('name', '!=', $permission->name);
            }
            $permissions = $query->get();
            $ordered_permissions = collect(['read', 'create', 'update', 'delete', 'access'])
                ->map(fn($action) => $permissions->firstWhere('name', "$action $ability"))
                ->filter()
                ->values();
            if ($ordered_permissions->isNotEmpty()) {
                $permissions_by_main_group['Other'][$ability] = $ordered_permissions->all();
            }
        }

        return view('livewire.permission.role-modal', ['permissions_by_main_group' => $permissions_by_main_group]);
    }

    public function submit()
    {
        $this->validate();

        // dd($this->all());

        try {
            DB::beginTransaction();

            $this->role->name = $this->name;
            if ($this->role->isDirty()) {
                $this->role->save();
            }

            $new_access = []; // Initialize array for permissions containing 'access'
            foreach ($this->checked_permissions as $permission) {
                if (Str::contains(strtolower($permission), 'access')) {
                    $new_access[] = $permission;
                }
            }

            // Sync the role's permissions
            $this->role->syncPermissions($this->checked_permissions);

            // Get Administrator role and users
            $administratorRole = Role::findByName('Administrator');
            $administrators = User::role('Administrator')->get();

            // Give permissions to all Administrators
            foreach ($administrators as $user) {
                $user->givePermissionTo($new_access);
            }

            // Clear the response cache
            ResponseCache::clear();
            DB::commit();

            // Create backup after successful update
            try {
                Log::info('Creating backup after role update', [
                    'role_id' => $this->role->id,
                    'role_name' => $this->role->name,
                    'permissions' => $this->checked_permissions
                ]);
                $this->roleBackupService->createBackup('role_updated');
            } catch (\Exception $e) {
                Log::error('Backup creation failed after role update', [
                    'role_id' => $this->role->id,
                    'error' => $e->getMessage()
                ]);
                // Don't throw the error, just log it
            }

            $this->dispatch('success', 'Role updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update role', [
                'role_id' => $this->role->id ?? null,
                'error' => $e->getMessage()
            ]);
            $this->dispatch('error', 'Failed to update role: ' . $e->getMessage());
        }
    }

    public function checkAll()
    {
        // dump('check all');
        // If the check_all property is true, set the checked permissions property to all of the permissions.
        if ($this->check_all) {
            $new_permissions = [];

            foreach ($this->permissions ?? [] as $permission) {
                $ability = Str::after($permission->name, ' ');

                $relatedPermissions = Permission::where('name', 'like', '%' . $ability . '%')
                    ->where('name', '!=', $permission->name)
                    ->pluck('name') // Use pluck to get only the 'name' values
                    ->toArray();   // Convert the collection to an array

                // Merge the related permissions into the $new_permissions array
                $new_permissions = array_merge($new_permissions, $relatedPermissions);
            }
            $new_permissions = array_unique($new_permissions);

            // $this->checked_permissions = $this->permissions->pluck('name');
            $this->checked_permissions = $new_permissions;
        } else {
            // Otherwise, set the checked permissions property to an empty array.
            $this->checked_permissions = [];
        }
    }

    public function delete($name)
    {
        try {
            DB::beginTransaction();

            $role = Role::where('name', $name)->first();

            if (!is_null($role)) {
                Log::info('Deleting role', [
                    'role_id' => $role->id,
                    'role_name' => $role->name
                ]);

                $role->delete();
                DB::commit();

                // Create backup after successful deletion
                try {
                    $this->roleBackupService->createBackup('role_deleted');
                } catch (\Exception $e) {
                    Log::error('Backup creation failed after role deletion', [
                        'role_id' => $role->id,
                        'error' => $e->getMessage()
                    ]);
                }

                $this->dispatch('success', 'Role deleted successfully');
            } else {
                Log::warning('Attempted to delete non-existent role', ['name' => $name]);
                $this->dispatch('error', 'Role not found');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete role', [
                'error' => $e->getMessage(),
                'role_name' => $name
            ]);
            $this->dispatch('error', 'Failed to delete role. Please try again.');
        }
    }

    public function hydrate()
    {
        $this->resetErrorBag();
        $this->resetValidation();
    }
}

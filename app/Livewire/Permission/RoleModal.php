<?php

namespace App\Livewire\Permission;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User; // Ensure User model is imported

class RoleModal extends Component
{
    public $name;
    public $checked_permissions;
    public $check_all;

    public Role $role;
    public Collection $permissions;

    protected $rules = [
        'name' => 'required|string',
    ];

    protected $listeners = [
        'modal.show.role_name' => 'mountRole',
        'submitRole' => 'submit',
    ];

    public function mountRole($role_name = '')
    {
        if (empty($role_name)) {
            // Create new
            $this->role = new Role;
            $this->name = '';
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

        // Check if the user is an Admin
        if (auth()->user()->hasRole('Administrator')) {
            // Get only the permissions that the current user has
            // $this->checked_permissions = auth()->user()->getAllPermissions()->pluck('name');
            $this->checked_permissions = $this->role->permissions->pluck('name');

        } else {
            // For other roles, set checked permissions from the role
            $this->checked_permissions = $this->role->permissions->pluck('name');
        }
    }

    public function mount()
    {
        // Check if the user is an Admin
        if (auth()->user()->hasRole('Administrator')) {
            // Get only the permissions that the current user has
            $this->permissions = Permission::whereIn('id', function($query) {
                $query->select('permission_id')
                    ->from('model_has_permissions')
                    ->where('model_type', User::class)
                    ->where('model_id', auth()->id())
                    ->where('name', 'like','%access%');
            })->get();
        } else {
            // For other roles (like SuperAdmin), get all permissions
            // $this->permissions = Permission::all();
            $this->permissions = Permission::where('name', 'like','%access%')->get();
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

        
        // Create an array of permissions grouped by ability.
        $permissions_by_group = [];
        foreach ($this->permissions ?? [] as $permission) {
            // dump($permission);

            $ability = Str::after($permission->name, ' ');

            if (auth()->user()->hasRole('SuperAdmin')) {
                $a = Permission::where('name', 'like', '%' . $ability . '%')
                                ->get();
            }else{
                $a = Permission::where('name', 'like', '%' . $ability . '%')
                                ->where('name', '!=', $permission->name)
                                ->get();

            }

            // dump($a[0]);

            for ($i=0; $i < count($a); $i++) { 
                # code...
                $permissions_by_group[$ability][] = $a[$i];

            }


            // $permissions_by_group[$ability][] = $a;
            // $permissions_by_group[$ability][] = $permission;
        }

        // dump($permissions_by_group);


        // Return the view with the permissions_by_group variable passed in.
        return view('livewire.permission.role-modal', compact('permissions_by_group'));
    }

    public function submit()
    {
        $this->validate();

        $this->role->name = $this->name;
        if ($this->role->isDirty()) {
            $this->role->save();
        }

        // dd($this->checked_permissions);

        $new_access = []; // Inisialisasi array untuk permission yang mengandung 'access'

        foreach ($this->checked_permissions as $permission) {
            if (Str::contains(strtolower($permission), 'access')) { // Case-insensitive check
                $new_access[] = $permission;
            }
        }

        // dd($new_access);


        // Sync the role's permissions with the checked permissions property.
        try {
            $this->role->syncPermissions($this->checked_permissions);

            // Ambil role Administrator
            $administratorRole = Role::findByName('Administrator');

            // Ambil semua user yang memiliki role Administrator
            $administrators = User::role('Administrator')->get();


            // Option 1: Give the SAME permission to all Administrators (using a loop)
            // $permission = Permission::findByName('permission_name'); // Replace with the actual permission name

            foreach ($administrators as $user) {
                $user->givePermissionTo($new_access);
            }

            // $user->givePermissionTo($new_access);

            $this->dispatch('success', 'Role updated successfully!');
        } catch (\Exception $e) {
            $this->dispatch('error', 'Failed to update role: ' . $e->getMessage());
        }
        $this->role->syncPermissions($this->checked_permissions);
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

    public function hydrate()
    {
        $this->resetErrorBag();
        $this->resetValidation();
    }
}

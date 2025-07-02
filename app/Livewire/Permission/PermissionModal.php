<?php

namespace App\Livewire\Permission;

use Livewire\Component;
use App\Models\Permission;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PermissionModal extends Component
{
    public $name;

    public Permission $permission;

    protected $rules = [
        'name' => 'required|string',
    ];

    // This is the list of listeners that this component listens to.
    protected $listeners = [
        'modal.show.permission_name' => 'mountPermission',
        'delete_permission' => 'delete'
    ];

    public function render()
    {
        return view('livewire.permission.permission-modal');
    }

    public function mountPermission($permission_name = '')
    {
        try {
            if (empty($permission_name)) {
                // Create new
                $this->permission = new Permission;
                $this->name = '';
                Log::info('Initializing new permission form');
                return;
            }

            // Get the permission by name.
            $permission = Permission::where('name', $permission_name)->first();
            if (is_null($permission)) {
                Log::error('Permission not found', ['name' => $permission_name]);
                $this->dispatch('error', 'The selected permission [' . $permission_name . '] is not found');
                return;
            }

            $this->permission = $permission;
            $this->name = $this->permission->name;
            Log::info('Permission loaded for editing', [
                'permission_id' => $permission->id,
                'permission_name' => $permission->name
            ]);
        } catch (\Exception $e) {
            Log::error('Error in mountPermission', [
                'error' => $e->getMessage(),
                'permission_name' => $permission_name
            ]);
            $this->dispatch('error', 'An error occurred while loading the permission');
        }
    }

    public function submit()
    {
        try {
            $this->validate();

            DB::beginTransaction();

            $oldName = $this->permission->name;
            $this->permission->name = strtolower($this->name);

            if ($this->permission->isDirty()) {
                Log::info('Updating permission', [
                    'permission_id' => $this->permission->id,
                    'old_name' => $oldName,
                    'new_name' => $this->permission->name
                ]);

                $this->permission->save();
            }

            DB::commit();

            // Emit a success event with a message indicating that the permissions have been updated.
            $this->dispatch('success', 'Permission updated successfully');
            Log::info('Permission updated successfully', [
                'permission_id' => $this->permission->id,
                'permission_name' => $this->permission->name
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update permission', [
                'error' => $e->getMessage(),
                'permission_id' => $this->permission->id ?? null,
                'permission_name' => $this->name
            ]);
            $this->dispatch('error', 'Failed to update permission. Please try again.');
        }
    }

    public function delete($name)
    {
        try {
            DB::beginTransaction();

            $permission = Permission::where('name', $name)->first();

            if (!is_null($permission)) {
                Log::info('Deleting permission', [
                    'permission_id' => $permission->id,
                    'permission_name' => $permission->name
                ]);

                $permission->delete();
                DB::commit();

                $this->dispatch('success', 'Permission deleted successfully');
                Log::info('Permission deleted successfully', [
                    'permission_id' => $permission->id,
                    'permission_name' => $permission->name
                ]);
            } else {
                Log::warning('Attempted to delete non-existent permission', ['name' => $name]);
                $this->dispatch('error', 'Permission not found');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete permission', [
                'error' => $e->getMessage(),
                'permission_name' => $name
            ]);
            $this->dispatch('error', 'Failed to delete permission. Please try again.');
        }
    }

    public function hydrate()
    {
        $this->resetErrorBag();
        $this->resetValidation();
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Permission;

class DeletePermissionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permission:delete {name : The base name of the permissions to delete}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete permissions from the system based on the base name';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $baseName = $this->argument('name');

        // Define the abilities to check against
        $abilities = ['access', 'create', 'read', 'update', 'delete', 'export', 'import', 'print'];

        // Initialize an array to hold permissions to delete
        $permissionsToDelete = [];

        // Get all permissions for the specified base name and check against abilities
        foreach ($abilities as $ability) {
            $permissionName = "$ability $baseName"; // Construct the permission name
            $permission = Permission::where('name', $permissionName)->first();

            if ($permission) {
                $permissionsToDelete[] = $permission; // Add to the list of permissions to delete
            }
        }

        if (empty($permissionsToDelete)) {
            $this->error("No permissions found for base name '{$baseName}'.");
            return 1; // Return a non-zero exit code for failure
        }

        // Detach the permissions from any roles and delete them
        foreach ($permissionsToDelete as $permission) {
            // Revoke the permission from any roles
            $roles = $permission->roles; // Get roles associated with the permission
            foreach ($roles as $role) {
                $role->revokePermissionTo($permission); // Revoke the permission from the role
            }

            // Delete the permission
            $permission->delete();
            $this->info("Deleted permission: '{$permission->name}' and revoked from associated roles.");
        }

        $this->info("All permissions related to '{$baseName}' have been deleted successfully.");
        return 0; // Return zero for success
    }
}

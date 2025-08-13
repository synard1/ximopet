<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class ShowUserPermissions extends Command
{
    protected $signature = 'user:permissions {user_id}';
    protected $description = 'Display roles and effective permissions (including role-based) for a specific user by ID';

    public function handle()
    {
        $userId = $this->argument('user_id');

        $user = User::find($userId);

        if (!$user) {
            $this->error("User with ID {$userId} not found.");
            return 1;
        }

        $this->info("User: {$user->name} ({$user->id})");

        // Roles
        $roles = $user->roles;
        if ($roles->isEmpty()) {
            $this->line('Roles: (none)');
        } else {
            $this->info('Roles:');
            $this->table(
                ['ID', 'Name', 'Guard Name', 'Company ID', 'Created At'],
                $roles->map(function ($role) {
                    return [
                        $role->id,
                        $role->name,
                        $role->guard_name,
                        $role->company_id ?? '-',
                        $role->created_at,
                    ];
                })->toArray()
            );
        }

        // Direct permissions assigned to the user
        $directPermissions = $user->permissions; // spatie relation
        if ($directPermissions->isEmpty()) {
            $this->line('Direct Permissions: (none)');
        } else {
            $this->info('Direct Permissions:');
            $this->table(
                ['ID', 'Name', 'Guard Name', 'Company ID', 'Created At'],
                $directPermissions->map(function ($permission) {
                    return [
                        $permission->id,
                        $permission->name,
                        $permission->guard_name,
                        $permission->company_id ?? '-',
                        $permission->created_at,
                    ];
                })->toArray()
            );
        }

        // Effective permissions (direct + via roles)
        $effective = $user->getAllPermissions()->unique('id')->values();
        if ($effective->isEmpty()) {
            $this->info("Effective Permissions: none");
        } else {
            $this->info('Effective Permissions (including via roles):');
            $this->table(
                ['ID', 'Name', 'Guard Name', 'Company ID', 'Created At'],
                $effective->map(function ($permission) {
                    return [
                        $permission->id,
                        $permission->name,
                        $permission->guard_name,
                        $permission->company_id ?? '-',
                        $permission->created_at,
                    ];
                })->toArray()
            );
        }

        return 0;
    }
}

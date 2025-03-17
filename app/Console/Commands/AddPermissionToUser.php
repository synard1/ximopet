<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class AddPermissionToUser extends Command
{
    protected $signature = 'permission:add-to-user {user_id} {permission_name}';
    protected $description = 'Add a permission to a specific user';

    public function handle()
    {
        $userId = $this->argument('user_id');
        $permissionName = $this->argument('permission_name');

        $user = User::find($userId);
        if (!$user) {
            $this->error('User not found.');
            return 1;
        }

        // $permission = Permission::whereName($permissionName);

        // if (!$permission) {
        //     $this->error('Permission not found.');
        //     return 1;
        // }

        // $user->givePermissionTo($permission);

        $user->givePermissionTo('access user management', 'delete transaksi');

        $this->info("Permission '{$permissionName}' added to user '{$user->name}'.");
        return 0;
    }
}

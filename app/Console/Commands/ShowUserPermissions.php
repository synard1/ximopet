<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User; // Pastikan Anda mengimpor model User Anda
use App\Models\Permission;

class ShowUserPermissions extends Command
{
    protected $signature = 'user:permissions {user_id}';
    protected $description = 'Display permissions for a specific user by ID';

    public function handle()
    {
        $userId = $this->argument('user_id');

        $user = User::find($userId);

        if (!$user) {
            $this->error("User with ID {$userId} not found.");
            return 1;
        }

        $permissions = $user->permissions; // Gunakan relasi permissions yang didefinisikan oleh Spatie

        if ($permissions->isEmpty()) {
            $this->info("User {$user->name} has no permissions assigned.");
            return 0;
        }

        $this->info("Permissions for user {$user->name}:");

        $this->table(
            ['ID', 'Name', 'Guard Name', 'Created At', 'Updated At'],
            $permissions->map(function ($permission) {
                return [
                    $permission->id,
                    $permission->name,
                    $permission->guard_name,
                    $permission->created_at,
                    $permission->updated_at,
                ];
            })->toArray()
        );

        // Opsi tampilan list sederhana:
        /*
        foreach ($permissions as $permission) {
            $this->line("- " . $permission->name);
        }
        */

        return 0;
    }
}

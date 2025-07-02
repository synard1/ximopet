<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Permission;

class ShowAllPermissions extends Command
{
    protected $signature = 'permission:show-all';
    protected $description = 'Display all defined permissions';

    public function handle()
    {
        $permissions = Permission::all();

        if ($permissions->isEmpty()) {
            $this->info('No permissions found.');
            return 0;
        }

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


        // Optional: Show permissions in a simpler list format
        /*
        $this->info('List of all permissions:');
        foreach ($permissions as $permission) {
            $this->line("- " . $permission->name);
        }
        */

        return 0;
    }
}

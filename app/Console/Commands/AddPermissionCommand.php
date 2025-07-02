<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Permission;

class AddPermissionCommand extends Command
{
    protected $signature = 'permission:add {module : The module name (e.g. livestock management)}';
    protected $description = 'Add permissions for a module with all abilities';

    public function handle()
    {
        $module = $this->argument('module');
        $abilities = ['access', 'create', 'read', 'update', 'delete', 'export', 'import', 'print'];

        foreach ($abilities as $ability) {
            $permissionName = "$ability $module";
            Permission::firstOrCreate(['name' => $permissionName]);
            $this->info("Created permission: $permissionName");
        }

        $this->info('All permissions have been created successfully!');
    }
}

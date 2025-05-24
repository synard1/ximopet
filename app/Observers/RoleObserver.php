<?php

namespace App\Observers;

use Spatie\Permission\Models\Role;
use App\Services\RoleBackupService;
use Illuminate\Support\Facades\Log;

class RoleObserver
{
    protected $roleBackupService;

    public function __construct(RoleBackupService $roleBackupService)
    {
        $this->roleBackupService = $roleBackupService;
    }

    /**
     * Handle the Role "created" event.
     */
    public function created(Role $role): void
    {
        try {
            Log::info('Role created, creating backup', [
                'role_id' => $role->id,
                'role_name' => $role->name
            ]);

            $this->roleBackupService->createBackup('created');
        } catch (\Exception $e) {
            Log::error('Failed to create backup after role creation', [
                'role_id' => $role->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle the Role "updated" event.
     */
    public function updated(Role $role): void
    {
        try {
            Log::info('Role updated, creating backup', [
                'role_id' => $role->id,
                'role_name' => $role->name,
                'changes' => $role->getChanges()
            ]);

            $this->roleBackupService->createBackup('updated');
        } catch (\Exception $e) {
            Log::error('Failed to create backup after role update', [
                'role_id' => $role->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle the Role "deleted" event.
     */
    public function deleted(Role $role): void
    {
        try {
            Log::info('Role deleted, creating backup', [
                'role_id' => $role->id,
                'role_name' => $role->name
            ]);

            $this->roleBackupService->createBackup('deleted');
        } catch (\Exception $e) {
            Log::error('Failed to create backup after role deletion', [
                'role_id' => $role->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}

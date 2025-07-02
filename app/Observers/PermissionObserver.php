<?php

namespace App\Observers;

use App\Models\Permission;
use App\Services\RoleBackupService;
use Illuminate\Support\Facades\Log;

class PermissionObserver
{
    protected $roleBackupService;

    public function __construct(RoleBackupService $roleBackupService)
    {
        $this->roleBackupService = $roleBackupService;
    }

    /**
     * Handle the Permission "created" event.
     */
    public function created(Permission $permission): void
    {
        try {
            Log::info('Permission created, creating backup', [
                'permission_id' => $permission->id,
                'permission_name' => $permission->name
            ]);

            $this->roleBackupService->createBackup('permission_created');
        } catch (\Exception $e) {
            Log::error('Failed to create backup after permission creation', [
                'permission_id' => $permission->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle the Permission "updated" event.
     */
    public function updated(Permission $permission): void
    {
        try {
            Log::info('Permission updated, creating backup', [
                'permission_id' => $permission->id,
                'permission_name' => $permission->name,
                'changes' => $permission->getChanges()
            ]);

            $this->roleBackupService->createBackup('permission_updated');
        } catch (\Exception $e) {
            Log::error('Failed to create backup after permission update', [
                'permission_id' => $permission->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle the Permission "deleted" event.
     */
    public function deleted(Permission $permission): void
    {
        try {
            Log::info('Permission deleted, creating backup', [
                'permission_id' => $permission->id,
                'permission_name' => $permission->name
            ]);

            $this->roleBackupService->createBackup('permission_deleted');
        } catch (\Exception $e) {
            Log::error('Failed to create backup after permission deletion', [
                'permission_id' => $permission->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}

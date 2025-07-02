<?php

namespace App\Services;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;

class RoleBackupService
{
    protected $backupPath = 'backups/roles';
    protected $maxBackups = 5;
    protected static $isRestoring = false;
    protected static $isSeeding = false;
    protected $backupLockTimeout = 60; // 1 minute lock timeout
    protected $minBackupInterval = 30; // Minimum seconds between backups

    public function createBackup()
    {
        // Skip backup if we're in the middle of a restore operation or seeding
        if (self::$isRestoring || self::$isSeeding || App::runningInConsole()) {
            return [
                'success' => true,
                'message' => 'Skipped backup during ' . (self::$isRestoring ? 'restore operation' : 'seeding/console operation')
            ];
        }

        // Check if backup is locked
        if (Cache::has('role_backup_lock')) {
            return [
                'success' => true,
                'message' => 'Backup already in progress'
            ];
        }

        // Check for recent backup
        $lastBackup = $this->getLastBackupTime();
        if ($lastBackup && Carbon::parse($lastBackup)->diffInSeconds(now()) < $this->minBackupInterval) {
            return [
                'success' => true,
                'message' => 'Skipped backup: too soon after last backup'
            ];
        }

        try {
            // Set backup lock
            Cache::put('role_backup_lock', true, $this->backupLockTimeout);

            $roles = Role::with('permissions')->get();
            $data = [];

            foreach ($roles as $role) {
                $data[] = [
                    'name' => $role->name,
                    'permissions' => $role->permissions->pluck('name')->toArray()
                ];
            }

            $timestamp = Carbon::now()->format('Y-m-d_His');
            $filename = "role_backup_{$timestamp}.json";
            $fullPath = "{$this->backupPath}/{$filename}";

            Storage::put($fullPath, json_encode($data, JSON_PRETTY_PRINT));

            // Clean up old backups
            $this->cleanupOldBackups();

            // Release backup lock
            Cache::forget('role_backup_lock');

            return [
                'success' => true,
                'filename' => $filename,
                'path' => $fullPath,
                'timestamp' => $timestamp
            ];
        } catch (\Exception $e) {
            // Release backup lock on error
            Cache::forget('role_backup_lock');

            Log::error('Role backup failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    protected function getLastBackupTime()
    {
        try {
            $backups = $this->getBackups();
            return !empty($backups) ? $backups[0]['created_at'] : null;
        } catch (\Exception $e) {
            Log::error('Failed to get last backup time', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function restoreBackup($filename)
    {
        try {
            self::$isRestoring = true; // Set restoring flag

            $fullPath = "{$this->backupPath}/{$filename}";

            if (!Storage::exists($fullPath)) {
                throw new \Exception('Backup file not found');
            }

            $content = Storage::get($fullPath);
            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid backup file format');
            }

            foreach ($data as $roleData) {
                $role = Role::firstOrCreate(['name' => $roleData['name']]);

                if (isset($roleData['permissions']) && is_array($roleData['permissions'])) {
                    $permissions = collect($roleData['permissions'])->map(function ($permissionName) {
                        return Permission::firstOrCreate(['name' => $permissionName]);
                    });

                    $role->syncPermissions($permissions);
                }
            }

            self::$isRestoring = false; // Reset restoring flag

            return [
                'success' => true,
                'message' => 'Backup restored successfully'
            ];
        } catch (\Exception $e) {
            self::$isRestoring = false; // Reset restoring flag even on error
            Log::error('Role restore failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function getBackups()
    {
        try {
            Log::info('Getting list of backups');
            $files = Storage::files('backups/roles');
            $backups = [];

            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'json') {
                    $backups[] = [
                        'filename' => basename($file),
                        'created_at' => date('Y-m-d H:i:s', Storage::lastModified($file))
                    ];
                }
            }

            // Sort backups by created_at in descending order
            usort($backups, function ($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });

            Log::info('Found backups', ['count' => count($backups)]);
            return $backups;
        } catch (\Exception $e) {
            Log::error('Failed to get backups', ['error' => $e->getMessage()]);
            return [];
        }
    }

    public function getBackupData($filename)
    {
        try {
            $path = "backups/roles/{$filename}";

            if (!Storage::exists($path)) {
                return null;
            }

            $content = Storage::get($path);
            $rolesData = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Invalid backup file format', ['filename' => $filename]);
                return null;
            }

            // Ensure each role has properly structured permissions
            $formattedRoles = [];
            foreach ($rolesData as $role) {
                $formattedRole = [
                    'name' => $role['name'],
                    'permissions' => []
                ];

                // Handle permissions whether they're strings or arrays
                if (isset($role['permissions'])) {
                    if (is_array($role['permissions'])) {
                        foreach ($role['permissions'] as $permission) {
                            if (is_array($permission)) {
                                $formattedRole['permissions'][] = $permission['name'];
                            } else {
                                $formattedRole['permissions'][] = $permission;
                            }
                        }
                    } else {
                        $formattedRole['permissions'][] = $role['permissions'];
                    }
                }

                $formattedRoles[] = $formattedRole;
            }

            // Extract unique permissions
            $permissions = [];
            foreach ($formattedRoles as $role) {
                foreach ($role['permissions'] as $permissionName) {
                    if (!isset($permissions[$permissionName])) {
                        $permissions[$permissionName] = [
                            'name' => $permissionName,
                            'guard_name' => 'web'
                        ];
                    }
                }
            }

            return [
                'roles' => $formattedRoles,
                'permissions' => array_values($permissions)
            ];
        } catch (\Exception $e) {
            Log::error('Error reading backup file', [
                'filename' => $filename,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    protected function cleanupOldBackups()
    {
        try {
            $backups = $this->getBackups();

            // If we have more than maxBackups, delete the oldest ones
            if (count($backups) > $this->maxBackups) {
                $backupsToDelete = array_slice($backups, $this->maxBackups);

                foreach ($backupsToDelete as $backup) {
                    Storage::delete("{$this->backupPath}/{$backup['filename']}");
                    Log::info('Deleted old backup: ' . $backup['filename']);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to cleanup old backups: ' . $e->getMessage());
        }
    }

    public static function setSeeding($isSeeding = true)
    {
        self::$isSeeding = $isSeeding;
    }
}

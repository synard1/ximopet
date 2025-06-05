<?php

namespace App\Livewire\Permission;

use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Storage;
use App\Services\RoleBackupService;

class RoleList extends Component
{
    use WithFileUploads;

    public array|Collection $roles;
    public $file;
    public $showImportModal = false;
    public $showRestoreModal = false;
    public $importProgress = 0;
    public $importStatus = '';
    public $importErrors = [];
    public $backups = [];
    public $selectedBackup = null;
    public $restoreStatus = '';
    public $restoreError = '';
    public $importType = 'file';
    public $selectedBackupFile = '';
    public $backupComparison = null;
    public $showComparison = false;
    public $isImporting = false;

    protected $listeners = [
        'refreshDatatable' => '$refresh',
        'success' => 'updateRoleList',
        'showRestoreModal' => 'showRestoreModal',
        'hideRestoreModal' => 'closeRestoreModal',
        'showImport' => 'showImport',
        'importTypeUpdated' => 'handleImportTypeChange'
    ];

    protected $backupService;

    public function boot(RoleBackupService $backupService)
    {
        $this->backupService = $backupService;
    }

    public function mount()
    {
        Log::info('Initializing RoleList component');
        $this->roles = $this->getRoles();
        $this->loadBackups();
    }

    protected function getRoles()
    {
        Log::info('Fetching roles with permissions');
        $query = Role::with('permissions');

        if (!Auth::user()->hasRole('SuperAdmin')) {
            Log::info('Filtering out SuperAdmin role for non-superadmin user');
            $query->where('name', '!=', 'SuperAdmin');
        }

        $roles = $query->get();
        Log::info('Found ' . $roles->count() . ' roles');

        foreach ($roles as $role) {
            $filteredPermissions = $role->permissions->filter(function ($permission) {
                return !Str::contains(strtolower($permission->name), 'access');
            });
            $role->setRelation('permissions', $filteredPermissions);
        }

        return $roles;
    }

    public function render()
    {
        Log::info('Rendering RoleList component');
        $this->roles = $this->getRoles();

        $dataTable = $this->prepareDataTable();
        return view('livewire.permission.role-list', ['dataTable' => $dataTable]);
    }

    protected function prepareDataTable()
    {
        Log::info('Preparing DataTable for roles');
        return DataTables::of($this->roles)
            ->addIndexColumn()
            ->addColumn('action', function ($role) {
                return view('livewire.permission.role-actions', ['role' => $role])->render();
            })
            ->addColumn('permissions', function ($role) {
                return $role->permissions->pluck('name')->implode(', ');
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function updateRoleList()
    {
        Log::info('Updating role list and creating backup');
        $this->roles = $this->getRoles();
        $this->createBackup();
    }

    public function exportPermissions()
    {
        Log::info('Exporting roles and permissions');
        try {
            // Get all roles with their permissions
            $roles = Role::with('permissions')->get();

            // Get all permissions, including unused ones
            $allPermissions = Permission::all();

            // Prepare the export data structure
            $data = [
                'roles' => [],
                'permissions' => [],
                'metadata' => [
                    'exported_at' => now()->toIso8601String(),
                    'total_roles' => $roles->count(),
                    'total_permissions' => $allPermissions->count()
                ]
            ];

            // Process roles and their permissions
            foreach ($roles as $role) {
                $roleData = [
                    'name' => $role->name,
                    'guard_name' => $role->guard_name,
                    'permissions' => $role->permissions->pluck('name')->toArray(),
                    'users_count' => $role->users()->count()
                ];
                $data['roles'][] = $roleData;
            }

            // Process all permissions
            foreach ($allPermissions as $permission) {
                $permissionData = [
                    'name' => $permission->name,
                    'guard_name' => $permission->guard_name,
                    'roles_count' => $permission->roles()->count(),
                    'is_used' => $permission->roles()->count() > 0
                ];
                $data['permissions'][] = $permissionData;
            }

            // Create the export file
            $filename = 'roles_permissions_export_' . date('Y-m-d_His') . '.json';
            $path = storage_path('app/public/' . $filename);

            // Write the data with pretty print for better readability
            file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            Log::info('Export completed successfully', ['filename' => $filename]);

            // Return the file for download and delete it after sending
            return response()->download($path)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('Failed to export permissions', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->dispatch('error', 'Failed to export permissions: ' . $e->getMessage());
        }
    }

    protected function createBackup()
    {
        Log::info('Attempting to create backup of roles and permissions');
        try {
            $result = $this->backupService->createBackup();

            if ($result['success']) {
                // Only show success notification if a backup was actually created
                if (($result['message'] ?? '') === 'Backup created successfully') {
                    $this->loadBackups();
                    $message = $result['message'] ?? 'Backup created successfully';
                    Log::info($message, ['filename' => $result['filename'] ?? null]);
                    $this->dispatch('success', $message);
                } else {
                    // Log skipped backup attempts without notifying the user
                    Log::info($result['message'] ?? 'Backup skipped or other success', ['filename' => $result['filename'] ?? null]);
                }
            } else {
                Log::error('Auto backup failed', ['error' => $result['error'] ?? 'Unknown error']);
                $this->dispatch('error', 'Backup failed: ' . ($result['error'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            Log::error('Backup creation failed', ['error' => $e->getMessage()]);
            $this->dispatch('error', 'Backup failed: ' . $e->getMessage());
        }
    }

    public function loadBackups()
    {
        Log::info('Loading available backups');
        $this->backups = $this->backupService->getBackups();
        Log::info('Loaded ' . count($this->backups) . ' backups');
    }

    public function updatedImportType($value)
    {
        Log::info('Import type changed', ['type' => $value]);
        $this->reset(['file', 'selectedBackupFile', 'importProgress', 'importStatus', 'importErrors']);
        $this->dispatch('importTypeUpdated', $value);
    }

    public function handleImportTypeChange($type)
    {
        Log::info('Handling import type change', ['type' => $type]);
        $this->importType = $type;
        $this->reset(['file', 'selectedBackupFile', 'importProgress', 'importStatus', 'importErrors']);
    }

    public function showRestoreModal()
    {
        Log::info('Showing restore modal');
        $this->reset(['selectedBackup', 'restoreStatus', 'restoreError']);
        $this->showRestoreModal = true;
        $this->dispatch('showRestoreModal');
    }

    public function closeRestoreModal()
    {
        Log::info('Closing restore modal');
        $this->showRestoreModal = false;
        $this->reset(['selectedBackup', 'restoreStatus', 'restoreError']);
        $this->dispatch('hideRestoreModal');
    }

    public function restoreBackup()
    {
        Log::info('Attempting to restore backup', ['backup' => $this->selectedBackup]);
        $this->validate(['selectedBackup' => 'required']);

        $result = $this->backupService->restoreBackup($this->selectedBackup);

        if ($result['success']) {
            Log::info('Backup restored successfully');
            $this->restoreStatus = $result['message'] ?? 'Backup restored successfully';
            $this->dispatch('refreshDatatable');
            $this->dispatch('success', $this->restoreStatus);
            $this->closeRestoreModal();
        } else {
            Log::error('Backup restore failed', ['error' => $result['error'] ?? 'Unknown error']);
            $this->restoreError = $result['error'] ?? 'Failed to restore backup';
        }
    }

    public function importPermissions()
    {
        Log::info('Starting import process', ['type' => $this->importType]);
        $this->isImporting = true;

        try {
            if ($this->importType === 'backup') {
                $this->validate(['selectedBackupFile' => 'required']);
                $this->importFromBackup();
            } else {
                $this->validate(['file' => 'required|file|mimes:json|max:10240']);
                $this->importFromFile();
            }
        } catch (\Exception $e) {
            // Handle the exception
        } finally {
            $this->isImporting = false;
        }
    }

    protected function importFromBackup()
    {
        Log::info('Importing from backup', ['file' => $this->selectedBackupFile]);
        try {
            $this->resetImportState();
            $content = Storage::get("backups/roles/{$this->selectedBackupFile}");
            $data = $this->validateAndParseJson($content);
            $this->processImportData($data);
        } catch (\Exception $e) {
            $this->handleImportError($e, 'backup');
        }
    }

    protected function importFromFile()
    {
        Log::info('Importing from file');
        try {
            $this->resetImportState();
            $content = file_get_contents($this->file->getRealPath());
            $data = $this->validateAndParseJson($content);
            $this->processImportData($data);
        } catch (\Exception $e) {
            $this->handleImportError($e, 'file');
        }
    }

    protected function resetImportState()
    {
        $this->importProgress = 0;
        $this->importStatus = 'Reading file...';
        $this->importErrors = [];
    }

    protected function validateAndParseJson($content)
    {
        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON format');
        }
        if (!is_array($data)) {
            throw new \Exception('Invalid data format: expected array');
        }
        return $data;
    }

    protected function handleImportError(\Exception $e, $source)
    {
        Log::error("Import from {$source} failed", ['error' => $e->getMessage()]);
        $this->importErrors[] = 'Import failed: ' . $e->getMessage();
        $this->importStatus = 'Import failed!';
    }

    protected function processImportData($data)
    {
        Log::info('Processing import data', ['data_structure' => array_keys($data)]);

        DB::beginTransaction();
        try {
            // Process permissions first
            if (isset($data['permissions']) && is_array($data['permissions'])) {
                $this->processPermissions($data['permissions']);
            }

            // Then process roles
            if (isset($data['roles']) && is_array($data['roles'])) {
                $this->processRoles($data['roles']);
            } else if (isset($data[0])) {
                // Handle legacy format (array of roles)
                $this->processRoles($data);
            }

            DB::commit();
            $this->handleSuccessfulImport();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    protected function processPermissions($permissions)
    {
        Log::info('Processing permissions', ['count' => count($permissions)]);
        foreach ($permissions as $permissionData) {
            if (!isset($permissionData['name'])) {
                Log::warning('Skipping permission: missing name', ['data' => $permissionData]);
                continue;
            }

            try {
                Permission::firstOrCreate(
                    ['name' => $permissionData['name']],
                    ['guard_name' => $permissionData['guard_name'] ?? 'web']
                );
                Log::info('Processed permission', ['name' => $permissionData['name']]);
            } catch (\Exception $e) {
                Log::error('Error processing permission', [
                    'name' => $permissionData['name'],
                    'error' => $e->getMessage()
                ]);
                $this->importErrors[] = "Error processing permission {$permissionData['name']}: " . $e->getMessage();
            }
        }
    }

    protected function processRoles($roles)
    {
        Log::info('Processing roles', ['count' => count($roles)]);
        $total = count($roles);
        $processed = 0;

        foreach ($roles as $roleData) {
            try {
                if (!isset($roleData['name'])) {
                    throw new \Exception('Role name is required');
                }

                Log::info('Processing role', ['name' => $roleData['name']]);
                $role = Role::firstOrCreate(
                    ['name' => $roleData['name']],
                    ['guard_name' => $roleData['guard_name'] ?? 'web']
                );

                if (isset($roleData['permissions']) && is_array($roleData['permissions'])) {
                    $this->syncRolePermissions($role, $roleData['permissions']);
                }

                $processed++;
                $this->updateImportProgress($processed, $total);
            } catch (\Exception $e) {
                $this->handleRoleProcessingError($roleData, $e);
            }
        }
    }

    protected function syncRolePermissions($role, $permissions)
    {
        Log::info('Syncing permissions for role', ['role' => $role->name, 'permissions' => $permissions]);
        $permissionModels = collect($permissions)->map(function ($permissionName) {
            return Permission::firstOrCreate(
                ['name' => $permissionName],
                ['guard_name' => 'web']
            );
        });
        $role->syncPermissions($permissionModels);
    }

    protected function updateImportProgress($processed, $total)
    {
        $this->importProgress = ($processed / $total) * 100;
        $this->importStatus = "Processed {$processed} of {$total} roles...";
    }

    protected function handleRoleProcessingError($roleData, \Exception $e)
    {
        $roleName = isset($roleData['name']) ? $roleData['name'] : 'unknown';
        Log::error('Error processing role', ['role' => $roleName, 'error' => $e->getMessage()]);
        $this->importErrors[] = "Error processing role {$roleName}: " . $e->getMessage();
    }

    protected function handleSuccessfulImport()
    {
        Log::info('Import completed successfully');
        $this->importStatus = 'Import completed successfully!';
        $this->dispatch('refreshDatatable');
        $this->dispatch('success', 'Permissions imported successfully');
        $this->dispatch('closeImportModal');
    }

    public function showImport()
    {
        Log::info('Showing import modal');
        $this->reset(['file', 'importProgress', 'importStatus', 'importErrors', 'importType', 'selectedBackupFile']);
        $this->showImportModal = true;
        $this->dispatch('showImportModal');
    }

    public function closeImportModal()
    {
        Log::info('Closing import modal');
        $this->showImportModal = false;
        $this->reset(['file', 'importProgress', 'importStatus', 'importErrors', 'importType', 'selectedBackupFile']);
        $this->dispatch('hideImportModal');
    }

    public function updatedSelectedBackupFile($value)
    {
        Log::info('Selected backup file changed', ['file' => $value]);
        if ($value) {
            $this->compareBackupWithExisting($value);
        } else {
            $this->backupComparison = null;
            $this->showComparison = false;
        }
    }

    protected function compareBackupWithExisting($backupFile)
    {
        Log::info('Starting backup comparison', ['file' => $backupFile]);
        try {
            $backupData = $this->backupService->getBackupData($backupFile);
            if (!$backupData) {
                throw new \Exception('Could not read backup file');
            }

            $comparison = $this->initializeComparisonStructure();
            $this->compareRoles($comparison, $backupData);
            $this->comparePermissions($comparison, $backupData);

            $this->backupComparison = $comparison;
            $this->showComparison = true;
            Log::info('Backup comparison completed successfully');
        } catch (\Exception $e) {
            Log::error('Backup comparison failed', ['error' => $e->getMessage()]);
            $this->addError('selectedBackupFile', 'Error comparing backup: ' . $e->getMessage());
        }
    }

    protected function initializeComparisonStructure()
    {
        return [
            'roles' => [
                'added' => [],
                'removed' => [],
                'modified' => []
            ],
            'permissions' => [
                'added' => [],
                'removed' => [],
                'modified' => []
            ]
        ];
    }

    protected function compareRoles(&$comparison, $backupData)
    {
        Log::info('Comparing roles');
        $existingRoles = Role::with('permissions')->get();
        $existingRolesMap = $this->createRolesMap($existingRoles);
        $backupRolesMap = $this->createBackupRolesMap($backupData['roles']);

        $this->findAddedAndModifiedRoles($comparison, $existingRolesMap, $backupRolesMap);
        $this->findRemovedRoles($comparison, $existingRolesMap, $backupRolesMap);
    }

    protected function createRolesMap($roles)
    {
        $map = [];
        foreach ($roles as $role) {
            $map[$role->name] = $role;
        }
        return $map;
    }

    protected function createBackupRolesMap($roles)
    {
        $map = [];
        foreach ($roles as $role) {
            $map[$role['name']] = $role;
        }
        return $map;
    }

    protected function findAddedAndModifiedRoles(&$comparison, $existingRolesMap, $backupRolesMap)
    {
        foreach ($backupRolesMap as $roleName => $backupRole) {
            if (!isset($existingRolesMap[$roleName])) {
                $comparison['roles']['added'][] = [
                    'name' => $backupRole['name'],
                    'permissions' => $backupRole['permissions']
                ];
            } else {
                $this->checkForRoleModifications($comparison, $existingRolesMap[$roleName], $backupRole);
            }
        }
    }

    protected function checkForRoleModifications(&$comparison, $existingRole, $backupRole)
    {
        $existingPermissions = $existingRole->permissions->pluck('name')->toArray();
        $backupPermissions = $backupRole['permissions'];

        if (
            count(array_diff($existingPermissions, $backupPermissions)) > 0 ||
            count(array_diff($backupPermissions, $existingPermissions)) > 0
        ) {
            $comparison['roles']['modified'][] = [
                'name' => $existingRole->name,
                'existing_permissions' => $existingPermissions,
                'backup_permissions' => $backupPermissions
            ];
        }
    }

    protected function findRemovedRoles(&$comparison, $existingRolesMap, $backupRolesMap)
    {
        foreach ($existingRolesMap as $roleName => $existingRole) {
            if (!isset($backupRolesMap[$roleName])) {
                $comparison['roles']['removed'][] = [
                    'name' => $roleName,
                    'permissions' => $existingRole->permissions->pluck('name')->toArray()
                ];
            }
        }
    }

    protected function comparePermissions(&$comparison, $backupData)
    {
        Log::info('Comparing permissions');
        $existingPermissions = Permission::all();
        $existingPermissionsMap = $this->createPermissionsMap($existingPermissions);
        $backupPermissionsMap = $this->createBackupPermissionsMap($backupData['permissions']);

        $this->findAddedPermissions($comparison, $existingPermissionsMap, $backupPermissionsMap);
        $this->findRemovedPermissions($comparison, $existingPermissionsMap, $backupPermissionsMap);
    }

    protected function createPermissionsMap($permissions)
    {
        $map = [];
        foreach ($permissions as $permission) {
            $map[$permission->name] = $permission;
        }
        return $map;
    }

    protected function createBackupPermissionsMap($permissions)
    {
        $map = [];
        foreach ($permissions as $permission) {
            $map[$permission['name']] = $permission;
        }
        return $map;
    }

    protected function findAddedPermissions(&$comparison, $existingPermissionsMap, $backupPermissionsMap)
    {
        foreach ($backupPermissionsMap as $permissionName => $backupPermission) {
            if (!isset($existingPermissionsMap[$permissionName])) {
                $comparison['permissions']['added'][] = $backupPermission;
            }
        }
    }

    protected function findRemovedPermissions(&$comparison, $existingPermissionsMap, $backupPermissionsMap)
    {
        foreach ($existingPermissionsMap as $permissionName => $existingPermission) {
            if (!isset($backupPermissionsMap[$permissionName])) {
                $comparison['permissions']['removed'][] = [
                    'name' => $permissionName,
                    'guard_name' => $existingPermission->guard_name
                ];
            }
        }
    }
}

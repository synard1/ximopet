<?php

namespace App\Livewire\Menu;

use Livewire\Component;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use App\Models\Menu;
use Illuminate\Support\Facades\DB;
use App\Models\Role;
use App\Models\Permission;

class RestoreModal extends Component
{
    public $isOpen = false;
    public $backupFiles = [];
    public $selectedBackup = null;
    public $backupData = null;
    public $differences = [];
    public $showDiff = false;
    public $selectedBackupType = null;
    public $tempComparisonSource = null;

    protected $listeners = ['openRestoreModal'];

    public function mount()
    {
        $this->loadBackupFiles();
    }

    public function loadBackupFiles()
    {
        Log::info('Attempting to load backup files');
        $backupDir = storage_path('app/backups/menus');
        $comparisonDir = storage_path('app/comparison_temp');

        $backupFiles = collect();

        if (File::exists($backupDir)) {
            $files = File::files($backupDir);
            $backupFiles = collect($files)
                ->map(function ($file) {
                    return [
                        'name' => $file->getFilename(),
                        'path' => $file->getPathname(),
                        'date' => date('Y-m-d H:i:s', $file->getMTime()),
                        'type' => 'backup'
                    ];
                })
                ->sortByDesc('date')
                ->values();
            Log::info('Successfully loaded backup files', ['count' => count($backupFiles), 'directory' => $backupDir]);
        } else {
            Log::warning('Backup directory not found', ['directory' => $backupDir]);
        }

        $comparisonFiles = collect();
        if (File::exists($comparisonDir)) {
            $files = File::files($comparisonDir);
            $comparisonFiles = collect($files)
                ->map(function ($file) {
                    return [
                        'name' => $file->getFilename(),
                        'path' => $file->getPathname(),
                        'date' => date('Y-m-d H:i:s', $file->getMTime()),
                        'type' => 'comparison'
                    ];
                })
                ->sortByDesc('date')
                ->values();
            Log::info('Successfully loaded comparison temp files', ['count' => count($comparisonFiles), 'directory' => $comparisonDir]);
        } else {
            Log::warning('Comparison temp directory not found', ['directory' => $comparisonDir]);
        }

        $this->backupFiles = $backupFiles->concat($comparisonFiles)->sortByDesc('date')->values()->toArray();

        Log::info('Total files loaded for selection', ['count' => count($this->backupFiles)]);
    }

    public function openRestoreModal()
    {
        $this->isOpen = true;
        $this->loadBackupFiles();
        Log::info('Restore modal opened');
    }

    public function closeModal()
    {
        $this->isOpen = false;
        $this->reset(['selectedBackup', 'backupData', 'differences', 'showDiff']);
        Log::info('Restore modal closed');
    }

    public function resetSelection()
    {
        $this->reset(['selectedBackup', 'backupData', 'differences', 'showDiff']);
        $this->loadBackupFiles(); // Reload files in case new backups were made
        Log::info('Restore modal selection reset');
    }

    public function loadBackupData($filename)
    {
        Log::info('Attempting to load backup data', ['filename' => $filename]);
        try {
            $selectedFileDetails = collect($this->backupFiles)->firstWhere('name', $filename);

            if (!$selectedFileDetails) {
                throw new \Exception('Selected file details not found in list');
            }

            $filePath = $selectedFileDetails['path'];

            if (File::exists($filePath)) {
                $content = File::get($filePath);
                $this->backupData = json_decode($content, true);
                $this->selectedBackup = $filename;
                $this->selectedBackupType = $selectedFileDetails['type'];

                Log::info('Backup data loaded successfully', ['filename' => $filename, 'type' => $this->selectedBackupType]);

                if ($this->selectedBackupType === 'comparison') {
                    Log::info('Selected file is a comparison temp file, setting up file-to-file comparison source.');
                    $this->tempComparisonSource = $this->backupData;
                    $this->compareData();
                } else {
                    $this->tempComparisonSource = null;
                    $this->compareData();
                }

                sleep(1);
            } else {
                throw new \Exception('Selected file not found on disk');
            }
        } catch (\Exception $e) {
            Log::error('Failed to load backup data', ['filename' => $filename, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $this->dispatch('error', 'Failed to load backup data: ' . $e->getMessage());
        }
    }

    public function compareData()
    {
        Log::info('Attempting to compare backup data with current data');
        if (!$this->backupData) {
            Log::warning('Comparison skipped: No backup data loaded');
            return;
        }
        try {
            $currentMenus = [];

            if ($this->tempComparisonSource !== null) {
                // If tempComparisonSource is set (from a comparison temp file), use it as the source for current data
                Log::info('Using tempComparisonSource for current data (file-to-file comparison)');
                $currentMenus = $this->tempComparisonSource;
            } else {
                // Otherwise, read current data from the database (database-to-file comparison)
                Log::info('Reading current data from database (database-to-file comparison)');
                $currentMenus = Menu::with(['roles', 'permissions', 'children'])
                    ->whereNull('parent_id')
                    ->orderBy('order_number')
                    ->get()
                    ->toArray();
                Log::info('Successfully loaded current data from database', ['menus_count' => count($currentMenus)]);
            }

            // Recursive function to compare menus and their children
            $compareRecursive = function ($currentItems, $backupItems, $parentId = null) use (&$compareRecursive) {
                $results = [];
                $currentCollection = collect($currentItems);
                $backupCollection = collect($backupItems);

                // Find added and modified items in backup
                foreach ($backupCollection as $backupItem) {
                    // Attempt to find current item by name and parent_id
                    $currentItem = $currentCollection->firstWhere(function ($item) use ($backupItem, $parentId) {
                        // Use strict comparison for parent_id, including null
                        // Added 'route' to the matching criteria to handle non-unique names under the same parent
                        return ($item['name'] ?? null) === ($backupItem['name'] ?? null)
                            && ($item['parent_id'] ?? null) === $parentId
                            && ($item['route'] ?? null) === ($backupItem['route'] ?? null);
                    });

                    if (!$currentItem) {
                        // Item is added
                        $results[] = [
                            'status' => 'added',
                            'data' => $backupItem,
                            'children' => array_map(function ($child) {
                                return ['status' => 'added', 'data' => $child, 'children' => []];
                            }, $backupItem['children'] ?? []),
                            'changes' => []
                        ];
                        Log::info('Identified added menu item', ['name' => $backupItem['name'] ?? 'N/A', 'parent_id' => $parentId ?? 'null']);
                    } else {
                        // Item potentially modified or unchanged
                        $changes = $this->compareMenuItemChanges($currentItem, $backupItem);

                        // Recursively compare children
                        $childrenComparison = $compareRecursive($currentItem['children'] ?? [], $backupItem['children'] ?? [], $currentItem['id']);

                        if (!empty($changes) || !empty($childrenComparison['added']) || !empty($childrenComparison['modified']) || !empty($childrenComparison['deleted'])) {
                            // Item is modified
                            $results[] = [
                                'status' => 'modified',
                                'data' => $currentItem, // Show current data for modified item
                                'backup_data' => $backupItem, // Include backup data for comparison context
                                'changes' => $changes,
                                'children' => array_merge($childrenComparison['added'], $childrenComparison['modified'], $childrenComparison['deleted'])
                            ];
                            Log::info('Identified modified menu item', ['name' => $backupItem['name'] ?? 'N/A', 'parent_id' => $parentId ?? 'null', 'changes' => $changes]);
                        } else {
                            // Item is unchanged - we might not need to list these explicitly, but for completeness:
                            // $results[] = ['status' => 'unchanged', 'data' => $currentItem, 'children' => []];
                            Log::info('Identified unchanged menu item', ['name' => $backupItem['name'] ?? 'N/A', 'parent_id' => $parentId ?? 'null']);
                        }
                    }
                }

                // Find deleted items in current (that are not in backup at this level/name)
                foreach ($currentCollection as $currentItem) {
                    // Attempt to find backup item by name and parent_id
                    $backupItem = $backupCollection->firstWhere(function ($item) use ($currentItem, $parentId) {
                        // Use strict comparison for parent_id, including null
                        return ($item['name'] ?? null) === ($currentItem['name'] ?? null)
                            && ($item['parent_id'] ?? null) === $parentId;
                    });

                    if (!$backupItem) {
                        // Item is deleted
                        $results[] = [
                            'status' => 'deleted',
                            'data' => $currentItem,
                            'children' => array_map(function ($child) {
                                return ['status' => 'deleted', 'data' => $child, 'children' => []];
                            }, $currentItem['children'] ?? []),
                            'changes' => []
                        ];
                        Log::info('Identified deleted menu item', ['name' => $currentItem['name'] ?? 'N/A', 'parent_id' => $parentId ?? 'null']);
                    }
                    // Note: Recursive check for deleted children within potentially modified parents is handled
                    // when comparing children in the 'modified' branch above.
                }

                // Separate results by status for easier processing later
                $categorizedResults = [
                    'added' => collect($results)->where('status', 'added')->values()->toArray(),
                    'modified' => collect($results)->where('status', 'modified')->values()->toArray(),
                    'deleted' => collect($results)->where('status', 'deleted')->values()->toArray(),
                    // 'unchanged' => collect($results)->where('status', 'unchanged')->values()->toArray(),
                ];

                return $categorizedResults;
            };

            // Start recursive comparison from top-level menus (parent_id = null)
            $comparisonResults = $compareRecursive($currentMenus, $this->backupData, null);

            // Store results in differences, structured for display
            // The structure should reflect the hierarchy and status of each item
            $this->differences = $comparisonResults;

            $this->showDiff = true;
            Log::info('Comparison completed with detailed results', [
                'added_top_level' => count($this->differences['added']),
                'modified_top_level' => count($this->differences['modified']),
                'deleted_top_level' => count($this->differences['deleted']),
                // 'unchanged_top_level' => count($this->differences['unchanged'] ?? []),
            ]);

            // Dispatch an event to ensure UI updates after comparison
            $this->dispatch('backup-data-compared');
        } catch (\Exception $e) {
            Log::error('Failed to compare data', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $this->dispatch('error', 'Failed to compare data: ' . $e->getMessage());
        }
    }

    // Helper function to compare individual menu item properties (excluding children, roles, permissions handled separately)
    private function compareMenuItemChanges($current, $backup)
    {
        $changes = [];
        // Fields to compare - exclude structural fields like parent_id (handled by recursive logic)
        $fieldsToCompare = ['name', 'label', 'route', 'icon', 'location', 'order_number', 'is_active'];

        foreach ($fieldsToCompare as $field) {
            $currentValue = $current[$field] ?? null;
            $backupValue = $backup[$field] ?? null;

            // Special handling for boolean or other types if necessary
            if ($field === 'is_active') {
                $currentValue = (bool) $currentValue;
                $backupValue = (bool) $backupValue;
            }

            // Handle null vs empty string consistently for comparison
            $currentValueNormalized = ($currentValue === null || $currentValue === '') ? null : $currentValue;
            $backupValueNormalized = ($backupValue === null || $backupValue === '') ? null : $backupValue;

            if ($currentValueNormalized !== $backupValueNormalized) {
                $changes[$field] = [
                    'current' => $currentValue,
                    'backup' => $backupValue
                ];
            }
        }

        // Compare roles (by name)
        $currentRoleNames = collect($current['roles'] ?? [])->pluck('name')->filter()->sort()->values()->toArray();
        $backupRoleNames = collect($backup['roles'] ?? [])->pluck('name')->filter()->sort()->values()->toArray();

        if ($currentRoleNames !== $backupRoleNames) {
            $changes['roles'] = [
                'current' => $currentRoleNames,
                'backup' => $backupRoleNames
            ];
            Log::info('Detected role changes for menu item (by name)', ['name' => $current['name'] ?? 'N/A', 'current' => $currentRoleNames, 'backup' => $backupRoleNames]);
        }

        // Compare permissions (by name)
        $currentPermissionNames = collect($current['permissions'] ?? [])->pluck('name')->filter()->sort()->values()->toArray();
        $backupPermissionNames = collect($backup['permissions'] ?? [])->pluck('name')->filter()->sort()->values()->toArray();

        if ($currentPermissionNames !== $backupPermissionNames) {
            $changes['permissions'] = [
                'current' => $currentPermissionNames,
                'backup' => $backupPermissionNames
            ];
            Log::info('Detected permission changes for menu item (by name)', ['name' => $current['name'] ?? 'N/A', 'current' => $currentPermissionNames, 'backup' => $backupPermissionNames]);
        }

        // Log values for all compared fields when no direct changes are detected in this method
        if (empty($changes)) {
            $allComparedValues = [];
            foreach ($fieldsToCompare as $field) {
                $currentValue = $current[$field] ?? null;
                $backupValue = $backup[$field] ?? null;
                if ($field === 'is_active') {
                    $currentValue = (bool) $currentValue;
                    $backupValue = (bool) $backupValue;
                }
                $allComparedValues[$field] = ['current' => $currentValue, 'backup' => $backupValue];
            }
            $allComparedValues['roles'] = ['current' => $currentRoleNames, 'backup' => $backupRoleNames];
            $allComparedValues['permissions'] = ['current' => $currentPermissionNames, 'backup' => $backupPermissionNames];
            Log::info('Compared values for item with no direct changes detected', ['name' => $current['name'] ?? 'N/A', 'values' => $allComparedValues]);
        }

        return $changes;
    }

    public function restore()
    {
        try {
            Log::info('Starting menu restore process', [
                'file' => $this->selectedBackup
            ]);

            // Validate file
            if (!$this->selectedBackup) {
                throw new \Exception('No file selected');
            }

            $filePath = storage_path("app/backups/menus/{$this->selectedBackup}");
            if (!File::exists($filePath)) {
                throw new \Exception('Backup file not found');
            }

            // Read and validate backup data
            $backupData = json_decode(File::get($filePath), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON format in backup file');
            }

            if (!is_array($backupData)) {
                throw new \Exception('Invalid backup data format');
            }

            Log::info('Backup data loaded successfully', [
                'menus_count' => count($backupData)
            ]);

            // Disable foreign key checks before truncate
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            // Clear existing data
            $this->clearExistingMenuData();

            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            // Create menus from backup data
            $this->createMenusFromBackupData($backupData);

            // Attach roles and permissions
            $this->attachRolesAndPermissions($backupData);

            Log::info('Menu restore completed successfully');

            // After successful restore, save the used backup data to a temporary file for comparison
            try {
                $comparisonDir = storage_path('app/comparison_temp');
                if (!File::exists($comparisonDir)) {
                    File::makeDirectory($comparisonDir, 0755, true);
                    Log::info('Created comparison temp directory', ['path' => $comparisonDir]);
                }
                // Create a more descriptive filename for the comparison file
                $originalFilename = pathinfo($this->selectedBackup, PATHINFO_FILENAME); // Get filename without extension
                $timestamp = now()->format('Ymd_His');
                $comparisonFilename = "restored_state_from_{$originalFilename}_{$timestamp}.json";
                $comparisonFilePath = $comparisonDir . '/' . $comparisonFilename;

                File::put(
                    $comparisonFilePath,
                    json_encode($this->backupData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                );
                Log::info('Saved backup data to temporary comparison file', ['path' => $comparisonFilePath]);
            } catch (\Exception $e) {
                Log::error('Failed to save backup data to temporary comparison file', ['error' => $e->getMessage()]);
                // Continue with restore success flow even if saving temp file fails
            }

            // Close modal first
            $this->isOpen = false;
            $this->reset(['selectedBackup', 'backupData', 'differences', 'showDiff']);

            // Then dispatch events
            $this->dispatch('menu-restored');
            $this->dispatch('success', 'Menu configuration restored successfully');
        } catch (\Exception $e) {
            Log::error('Failed to restore menu', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->dispatch('error', 'Failed to restore menu: ' . $e->getMessage());
        }
    }

    private function clearExistingMenuData()
    {
        try {
            Log::info('Clearing existing menu data');

            // Truncate pivot tables first
            DB::table('menu_role')->truncate();
            DB::table('menu_permission')->truncate();

            // Then truncate menus table
            DB::table('menus')->truncate();

            Log::info('Existing menu data cleared successfully');
        } catch (\Exception $e) {
            Log::error('Failed to clear existing menu data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \Exception('Failed to clear existing menu data: ' . $e->getMessage());
        }
    }

    private function createMenusFromBackupData(array $backupData)
    {
        try {
            Log::info('Creating menus from backup data');

            foreach ($backupData as $menuData) {
                // Create parent menu
                $menu = Menu::create([
                    'name' => $menuData['name'],
                    'label' => $menuData['label'],
                    'route' => $menuData['route'],
                    'icon' => $menuData['icon'],
                    'location' => $menuData['location'],
                    'order_number' => $menuData['order_number'],
                    'is_active' => $menuData['is_active'] ?? true
                ]);

                Log::info('Created parent menu', [
                    'id' => $menu->id,
                    'name' => $menu->name
                ]);

                // Create child menus if any
                if (!empty($menuData['children'])) {
                    foreach ($menuData['children'] as $childData) {
                        $child = Menu::create([
                            'parent_id' => $menu->id,
                            'name' => $childData['name'],
                            'label' => $childData['label'],
                            'route' => $childData['route'],
                            'icon' => $childData['icon'],
                            'location' => $childData['location'],
                            'order_number' => $childData['order_number'],
                            'is_active' => $childData['is_active'] ?? true
                        ]);

                        Log::info('Created child menu', [
                            'id' => $child->id,
                            'name' => $child->name,
                            'parent_id' => $menu->id
                        ]);
                    }
                }
            }

            Log::info('All menus created successfully');
        } catch (\Exception $e) {
            Log::error('Failed to create menus from backup data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \Exception('Failed to create menus: ' . $e->getMessage());
        }
    }

    private function attachRolesAndPermissions(array $backupData)
    {
        try {
            Log::info('Attaching roles and permissions to menus');

            foreach ($backupData as $menuData) {
                $menu = Menu::where('name', $menuData['name'])->first();
                if (!$menu) {
                    Log::warning('Menu not found for attaching roles/permissions', [
                        'menu_name' => $menuData['name']
                    ]);
                    continue;
                }

                // Attach roles
                if (!empty($menuData['roles'])) {
                    $roleIds = collect($menuData['roles'])->pluck('id')->toArray();
                    $menu->roles()->attach($roleIds);
                    Log::info('Attached roles to menu', [
                        'menu_id' => $menu->id,
                        'role_count' => count($roleIds)
                    ]);
                }

                // Attach permissions
                if (!empty($menuData['permissions'])) {
                    $permissionIds = collect($menuData['permissions'])->pluck('id')->toArray();
                    $menu->permissions()->attach($permissionIds);
                    Log::info('Attached permissions to menu', [
                        'menu_id' => $menu->id,
                        'permission_count' => count($permissionIds)
                    ]);
                }

                // Handle children
                if (!empty($menuData['children'])) {
                    foreach ($menuData['children'] as $childData) {
                        $child = Menu::where('name', $childData['name'])
                            ->where('parent_id', $menu->id)
                            ->first();

                        if (!$child) {
                            Log::warning('Child menu not found for attaching roles/permissions', [
                                'child_name' => $childData['name'],
                                'parent_id' => $menu->id
                            ]);
                            continue;
                        }

                        // Attach roles to child
                        if (!empty($childData['roles'])) {
                            $childRoleIds = collect($childData['roles'])->pluck('id')->toArray();
                            $child->roles()->attach($childRoleIds);
                            Log::info('Attached roles to child menu', [
                                'child_id' => $child->id,
                                'role_count' => count($childRoleIds)
                            ]);
                        }

                        // Attach permissions to child
                        if (!empty($childData['permissions'])) {
                            $childPermissionIds = collect($childData['permissions'])->pluck('id')->toArray();
                            $child->permissions()->attach($childPermissionIds);
                            Log::info('Attached permissions to child menu', [
                                'child_id' => $child->id,
                                'permission_count' => count($childPermissionIds)
                            ]);
                        }
                    }
                }
            }

            Log::info('Roles and permissions attached successfully');
        } catch (\Exception $e) {
            Log::error('Failed to attach roles and permissions', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \Exception('Failed to attach roles and permissions: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.menu.restore-modal');
    }
}

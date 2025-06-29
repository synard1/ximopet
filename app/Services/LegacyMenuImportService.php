<?php

namespace App\Services;

use App\Models\Menu;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class LegacyMenuImportService
{
    /**
     * Import menu configuration with legacy support
     * 
     * @param array $menuConfig
     * @param string $location
     * @return array
     */
    public function importMenuConfiguration(array $menuConfig, string $location = 'sidebar'): array
    {
        try {
            DB::beginTransaction();

            // Detect if this is legacy format (integer IDs) or current format (UUID)
            $isLegacyFormat = $this->detectLegacyFormat($menuConfig);

            Log::info('Menu import started', [
                'format' => $isLegacyFormat ? 'legacy' : 'current',
                'location' => $location,
                'menu_count' => count($menuConfig)
            ]);

            // Get admin user for created_by field
            $adminUser = $this->getAdminUser();

            // Pre-import validation for missing data
            $missingData = $this->validateRequiredData($menuConfig);

            // Clear existing menus in the target location
            $this->clearExistingMenus($location);

            // Import menus with appropriate method
            if ($isLegacyFormat) {
                $result = $this->importLegacyMenus($menuConfig, null, $location, $adminUser);
            } else {
                $result = $this->importCurrentMenus($menuConfig, null, $location, $adminUser);
            }

            DB::commit();

            Log::info('Menu import completed successfully', array_merge($result, [
                'missing_roles' => $missingData['roles'],
                'missing_permissions' => $missingData['permissions']
            ]));

            return [
                'success' => true,
                'format' => $isLegacyFormat ? 'legacy' : 'current',
                'imported_count' => $result['imported_count'],
                'roles_attached' => $result['roles_attached'],
                'permissions_attached' => $result['permissions_attached'],
                'missing_roles' => $missingData['roles'],
                'missing_permissions' => $missingData['permissions'],
                'warnings' => $this->generateWarnings($missingData)
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Menu import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Detect if the menu configuration is in legacy format
     * 
     * @param array $menuConfig
     * @return bool
     */
    private function detectLegacyFormat(array $menuConfig): bool
    {
        if (empty($menuConfig)) {
            return false;
        }

        $firstMenu = $menuConfig[0];

        // Check if ID is integer (legacy) or string/UUID (current)
        if (isset($firstMenu['id'])) {
            return is_int($firstMenu['id']);
        }

        // Check roles and permissions format
        if (isset($firstMenu['roles']) && !empty($firstMenu['roles'])) {
            $firstRole = $firstMenu['roles'][0];
            if (isset($firstRole['id'])) {
                return is_int($firstRole['id']);
            }
        }

        if (isset($firstMenu['permissions']) && !empty($firstMenu['permissions'])) {
            $firstPermission = $firstMenu['permissions'][0];
            if (isset($firstPermission['id'])) {
                return is_int($firstPermission['id']);
            }
        }

        return false;
    }

    /**
     * Import legacy format menus (with integer IDs)
     * 
     * @param array $menuItems
     * @param string|null $parentId
     * @param string $location
     * @param User|null $adminUser
     * @return array
     */
    private function importLegacyMenus(array $menuItems, ?string $parentId, string $location, ?User $adminUser): array
    {
        $importedCount = 0;
        $rolesAttached = 0;
        $permissionsAttached = 0;

        foreach ($menuItems as $menuItem) {
            // Create menu without legacy ID (let Laravel generate new UUID)
            $menuData = [
                'parent_id' => $parentId,
                'name' => $menuItem['name'] ?? null,
                'label' => $menuItem['label'] ?? null,
                'route' => $menuItem['route'] ?? null,
                'icon' => $menuItem['icon'] ?? null,
                'location' => $location,
                'order_number' => $menuItem['order_number'] ?? 0,
                'is_active' => $menuItem['is_active'] ?? true,
                'created_by' => $adminUser ? $adminUser->id : null,
                'updated_by' => $adminUser ? $adminUser->id : null,
            ];

            $menu = Menu::create($menuData);
            $importedCount++;

            Log::info('Legacy menu created', [
                'new_id' => $menu->id,
                'legacy_id' => $menuItem['id'] ?? 'unknown',
                'name' => $menu->name
            ]);

            // Attach roles (convert legacy integer IDs to current role names)
            if (isset($menuItem['roles']) && is_array($menuItem['roles'])) {
                $roleNames = $this->extractLegacyRoleNames($menuItem['roles']);
                $roleIds = Role::whereIn('name', $roleNames)->pluck('id');
                if ($roleIds->isNotEmpty()) {
                    $menu->roles()->attach($roleIds);
                    $rolesAttached += $roleIds->count();
                }
            }

            // Attach permissions (convert legacy integer IDs to current permission names)
            if (isset($menuItem['permissions']) && is_array($menuItem['permissions'])) {
                $permissionNames = $this->extractLegacyPermissionNames($menuItem['permissions']);
                $permissionIds = Permission::whereIn('name', $permissionNames)->pluck('id');
                if ($permissionIds->isNotEmpty()) {
                    $menu->permissions()->attach($permissionIds);
                    $permissionsAttached += $permissionIds->count();
                }
            }

            // Recursively import children
            if (isset($menuItem['children']) && is_array($menuItem['children']) && !empty($menuItem['children'])) {
                $childResult = $this->importLegacyMenus($menuItem['children'], $menu->id, $location, $adminUser);
                $importedCount += $childResult['imported_count'];
                $rolesAttached += $childResult['roles_attached'];
                $permissionsAttached += $childResult['permissions_attached'];
            }
        }

        return [
            'imported_count' => $importedCount,
            'roles_attached' => $rolesAttached,
            'permissions_attached' => $permissionsAttached
        ];
    }

    /**
     * Import current format menus (with UUID)
     * 
     * @param array $menuItems
     * @param string|null $parentId
     * @param string $location
     * @param User|null $adminUser
     * @return array
     */
    private function importCurrentMenus(array $menuItems, ?string $parentId, string $location, ?User $adminUser): array
    {
        $importedCount = 0;
        $rolesAttached = 0;
        $permissionsAttached = 0;

        foreach ($menuItems as $menuItem) {
            // Create menu (let Laravel generate new UUID, ignore imported ID)
            $menuData = [
                'parent_id' => $parentId,
                'name' => $menuItem['name'] ?? null,
                'label' => $menuItem['label'] ?? null,
                'route' => $menuItem['route'] ?? null,
                'icon' => $menuItem['icon'] ?? null,
                'location' => $location,
                'order_number' => $menuItem['order_number'] ?? 0,
                'is_active' => $menuItem['is_active'] ?? true,
                'created_by' => $adminUser ? $adminUser->id : null,
                'updated_by' => $adminUser ? $adminUser->id : null,
            ];

            $menu = Menu::create($menuData);
            $importedCount++;

            // Attach roles by name (current format already uses names)
            if (isset($menuItem['roles']) && is_array($menuItem['roles'])) {
                $roleNames = array_column($menuItem['roles'], 'name');
                $roleIds = Role::whereIn('name', $roleNames)->pluck('id');
                if ($roleIds->isNotEmpty()) {
                    $menu->roles()->attach($roleIds);
                    $rolesAttached += $roleIds->count();
                }
            }

            // Attach permissions by name (current format already uses names)
            if (isset($menuItem['permissions']) && is_array($menuItem['permissions'])) {
                $permissionNames = array_column($menuItem['permissions'], 'name');
                $permissionIds = Permission::whereIn('name', $permissionNames)->pluck('id');
                if ($permissionIds->isNotEmpty()) {
                    $menu->permissions()->attach($permissionIds);
                    $permissionsAttached += $permissionIds->count();
                }
            }

            // Recursively import children
            if (isset($menuItem['children']) && is_array($menuItem['children']) && !empty($menuItem['children'])) {
                $childResult = $this->importCurrentMenus($menuItem['children'], $menu->id, $location, $adminUser);
                $importedCount += $childResult['imported_count'];
                $rolesAttached += $childResult['roles_attached'];
                $permissionsAttached += $childResult['permissions_attached'];
            }
        }

        return [
            'imported_count' => $importedCount,
            'roles_attached' => $rolesAttached,
            'permissions_attached' => $permissionsAttached
        ];
    }

    /**
     * Extract role names from legacy role data
     * 
     * @param array $legacyRoles
     * @return array
     */
    private function extractLegacyRoleNames(array $legacyRoles): array
    {
        return array_column($legacyRoles, 'name');
    }

    /**
     * Extract permission names from legacy permission data
     * 
     * @param array $legacyPermissions
     * @return array
     */
    private function extractLegacyPermissionNames(array $legacyPermissions): array
    {
        return array_column($legacyPermissions, 'name');
    }

    /**
     * Get admin user for created_by field
     * 
     * @return User|null
     */
    private function getAdminUser(): ?User
    {
        $adminUser = User::where('email', 'admin@peternakan.digital')->first();
        if (!$adminUser) {
            $adminUser = User::first(); // Fallback to any user
        }
        return $adminUser;
    }

    /**
     * Clear existing menus in the target location
     * 
     * @param string $location
     * @return void
     */
    private function clearExistingMenus(string $location): void
    {
        // Get IDs of existing menus in the target location
        $existingMenuIds = Menu::where('location', $location)->pluck('id');

        if ($existingMenuIds->isNotEmpty()) {
            // Detach roles and permissions for these menus
            DB::table('menu_role')->whereIn('menu_id', $existingMenuIds)->delete();
            DB::table('menu_permission')->whereIn('menu_id', $existingMenuIds)->delete();

            // Delete existing menus in the target location
            Menu::where('location', $location)->delete();

            Log::info('Cleared existing menus', [
                'location' => $location,
                'count' => $existingMenuIds->count()
            ]);
        }
    }

    /**
     * Validate menu configuration structure
     * 
     * @param array $menuConfig
     * @return array
     */
    public function validateMenuConfiguration(array $menuConfig): array
    {
        $errors = [];
        $warnings = [];

        if (empty($menuConfig)) {
            $errors[] = 'Menu configuration is empty';
            return ['errors' => $errors, 'warnings' => $warnings];
        }

        foreach ($menuConfig as $index => $menuItem) {
            $menuPath = "menu[$index]";

            // Required fields validation
            if (empty($menuItem['name'])) {
                $errors[] = "$menuPath: 'name' field is required";
            }

            if (empty($menuItem['label'])) {
                $errors[] = "$menuPath: 'label' field is required";
            }

            // Optional but recommended fields
            if (empty($menuItem['route'])) {
                $warnings[] = "$menuPath: 'route' field is empty";
            }

            // Validate children recursively
            if (isset($menuItem['children']) && is_array($menuItem['children'])) {
                foreach ($menuItem['children'] as $childIndex => $childItem) {
                    $childPath = "$menuPath.children[$childIndex]";

                    if (empty($childItem['name'])) {
                        $errors[] = "$childPath: 'name' field is required";
                    }

                    if (empty($childItem['label'])) {
                        $errors[] = "$childPath: 'label' field is required";
                    }
                }
            }
        }

        return ['errors' => $errors, 'warnings' => $warnings];
    }

    /**
     * Get import preview information
     * 
     * @param array $menuConfig
     * @return array
     */
    public function getImportPreview(array $menuConfig): array
    {
        $isLegacyFormat = $this->detectLegacyFormat($menuConfig);
        $totalMenus = $this->countMenusRecursively($menuConfig);
        $uniqueRoles = $this->getUniqueRoles($menuConfig);
        $uniquePermissions = $this->getUniquePermissions($menuConfig);
        $parentMenus = count($menuConfig);
        $childMenus = $totalMenus - $parentMenus;

        return [
            'format' => $isLegacyFormat ? 'legacy' : 'current',
            'total_menus' => $totalMenus,
            'parent_menus' => $parentMenus,
            'child_menus' => $childMenus,
            'unique_roles' => count($uniqueRoles),
            'unique_permissions' => count($uniquePermissions),
            'roles' => array_values($uniqueRoles),
            'permissions' => array_values($uniquePermissions)
        ];
    }

    /**
     * Count total menus recursively
     * 
     * @param array $menuItems
     * @return int
     */
    private function countMenusRecursively(array $menuItems): int
    {
        $count = count($menuItems);

        foreach ($menuItems as $menuItem) {
            if (isset($menuItem['children']) && is_array($menuItem['children'])) {
                $count += $this->countMenusRecursively($menuItem['children']);
            }
        }

        return $count;
    }

    /**
     * Count total roles recursively
     * 
     * @param array $menuItems
     * @return int
     */
    private function countRolesRecursively(array $menuItems): int
    {
        $count = 0;

        foreach ($menuItems as $menuItem) {
            if (isset($menuItem['roles']) && is_array($menuItem['roles'])) {
                $count += count($menuItem['roles']);
            }

            if (isset($menuItem['children']) && is_array($menuItem['children'])) {
                $count += $this->countRolesRecursively($menuItem['children']);
            }
        }

        return $count;
    }

    /**
     * Count total permissions recursively
     * 
     * @param array $menuItems
     * @return int
     */
    private function countPermissionsRecursively(array $menuItems): int
    {
        $count = 0;

        foreach ($menuItems as $menuItem) {
            if (isset($menuItem['permissions']) && is_array($menuItem['permissions'])) {
                $count += count($menuItem['permissions']);
            }

            if (isset($menuItem['children']) && is_array($menuItem['children'])) {
                $count += $this->countPermissionsRecursively($menuItem['children']);
            }
        }

        return $count;
    }

    /**
     * Check if menu configuration has children
     * 
     * @param array $menuItems
     * @return bool
     */
    private function hasChildrenMenus(array $menuItems): bool
    {
        foreach ($menuItems as $menuItem) {
            if (isset($menuItem['children']) && is_array($menuItem['children']) && !empty($menuItem['children'])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get unique role names from menu configuration
     * 
     * @param array $menuItems
     * @return array
     */
    private function getUniqueRoles(array $menuItems): array
    {
        $roles = [];

        foreach ($menuItems as $menuItem) {
            if (isset($menuItem['roles']) && is_array($menuItem['roles'])) {
                foreach ($menuItem['roles'] as $role) {
                    if (isset($role['name'])) {
                        $roles[$role['name']] = $role['name'];
                    }
                }
            }

            if (isset($menuItem['children']) && is_array($menuItem['children'])) {
                $childRoles = $this->getUniqueRoles($menuItem['children']);
                $roles = array_merge($roles, $childRoles);
            }
        }

        return $roles;
    }

    /**
     * Get unique permission names from menu configuration
     * 
     * @param array $menuItems
     * @return array
     */
    private function getUniquePermissions(array $menuItems): array
    {
        $permissions = [];

        foreach ($menuItems as $menuItem) {
            if (isset($menuItem['permissions']) && is_array($menuItem['permissions'])) {
                foreach ($menuItem['permissions'] as $permission) {
                    if (isset($permission['name'])) {
                        $permissions[$permission['name']] = $permission['name'];
                    }
                }
            }

            if (isset($menuItem['children']) && is_array($menuItem['children'])) {
                $childPermissions = $this->getUniquePermissions($menuItem['children']);
                $permissions = array_merge($permissions, $childPermissions);
            }
        }

        return $permissions;
    }

    /**
     * Validate required data (roles and permissions) exist in database
     * 
     * @param array $menuConfig
     * @return array
     */
    public function validateRequiredData(array $menuConfig): array
    {
        $requiredRoles = array_values($this->getUniqueRoles($menuConfig));
        $requiredPermissions = array_values($this->getUniquePermissions($menuConfig));

        // Check missing roles
        $existingRoles = Role::whereIn('name', $requiredRoles)->pluck('name')->toArray();
        $missingRoles = array_diff($requiredRoles, $existingRoles);

        // Check missing permissions
        $existingPermissions = Permission::whereIn('name', $requiredPermissions)->pluck('name')->toArray();
        $missingPermissions = array_diff($requiredPermissions, $existingPermissions);

        return [
            'roles' => array_values($missingRoles),
            'permissions' => array_values($missingPermissions),
            'required_roles' => $requiredRoles,
            'required_permissions' => $requiredPermissions,
            'existing_roles' => $existingRoles,
            'existing_permissions' => $existingPermissions
        ];
    }

    /**
     * Generate warnings for missing data
     * 
     * @param array $missingData
     * @return array
     */
    public function generateWarnings(array $missingData): array
    {
        $warnings = [];

        if (!empty($missingData['roles'])) {
            $warnings[] = sprintf(
                'Missing %d roles: %s. Menu items will be created but role associations will be skipped.',
                count($missingData['roles']),
                implode(', ', $missingData['roles'])
            );
        }

        if (!empty($missingData['permissions'])) {
            $permissionList = count($missingData['permissions']) > 5
                ? implode(', ', array_slice($missingData['permissions'], 0, 5)) . ' and ' . (count($missingData['permissions']) - 5) . ' more'
                : implode(', ', $missingData['permissions']);

            $warnings[] = sprintf(
                'Missing %d permissions: %s. Menu items will be created but permission associations will be skipped.',
                count($missingData['permissions']),
                $permissionList
            );
        }

        return $warnings;
    }
}

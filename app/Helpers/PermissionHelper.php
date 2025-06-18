<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;

class PermissionHelper
{
    /**
     * Get all permissions the authenticated user has.
     *
     * @return array
     */
    public static function getUserPermissions()
    {
        $user = Auth::user();
        if (!$user) {
            return [];
        }

        $permissions = [];
        // Use user->getAllPermissions() for combined role/direct permissions
        foreach ($user->getAllPermissions() as $permission) {
            $permissions[] = [
                'name' => $permission->name,
                'guard_name' => $permission->guard_name
            ];
        }

        return $permissions;
    }

    /**
     * Get all roles the authenticated user has.
     *
     * @return array
     */
    public static function getUserRoles()
    {
        $user = Auth::user();
        if (!$user) {
            return [];
        }

        $roles = [];
        foreach ($user->roles as $role) {
            $roles[] = [
                'name' => $role->name,
                'guard_name' => $role->guard_name
            ];
        }

        return $roles;
    }

    /**
     * Get a list of permissions commonly required for Farm Management actions.
     *
     * @return array
     */
    public static function getFarmManagementPermissions()
    {
        return [
            'read farm master data',
            'create farm master data',
            'update farm master data',
            'delete farm master data',
            'access farm master data', // Used for operator/storage tabs access
            'read farm operator',     // Used for operator tab data
            'access farm storage',    // Used for storage tab access
            'read farm storage',      // Used for storage tab data
            // Add other relevant farm permissions here if needed
        ];
    }

    /**
     * Get a list of roles commonly relevant to Farm Management.
     *
     * @return array
     */
    public static function getFarmManagementRoles()
    {
        // List roles that typically interact with farm management
        return [
            'Supervisor',
            'Operator',
            'Manager', // If managers interact with farms
            // Add other relevant roles here
        ];
    }

    /**
     * Get required permissions for a specific route name.
     * (Less useful for dynamic pages, but kept for standard routes)
     *
     * @param string $routeName
     * @return array
     */
    public static function getRequiredPermissions($routeName)
    {
        // Define *route-level* required permissions (if any)
        $routePermissions = [
            'farm.index' => ['read farm master data'], // Basic view permission for the index page itself
            // Add other specific route permissions if applicable
        ];

        return $routePermissions[$routeName] ?? [];
    }

    /**
     * Get required roles for a specific route name.
     * (Less useful for dynamic pages, but kept for standard routes)
     *
     * @param string $routeName
     * @return array
     */
    public static function getRequiredRoles($routeName)
    {
        // Define *route-level* required roles (if any)
        $routeRoles = [
            'farm.index' => ['Supervisor', 'Operator'], // Roles that can typically access the index route
            // Add other specific route roles if applicable
        ];

        return $routeRoles[$routeName] ?? [];
    }
}

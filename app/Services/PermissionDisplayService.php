<?php

namespace App\Services;

use App\Helpers\PermissionHelper;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

class PermissionDisplayService
{
    public static function getPermissionInfo()
    {
        $user = Auth::user();
        $currentRoute = Route::currentRouteName();

        $userPermissions = PermissionHelper::getUserPermissions();
        $userRoles = PermissionHelper::getUserRoles();

        // Get all relevant farm management permissions and roles
        $relevantPermissions = PermissionHelper::getFarmManagementPermissions();
        $relevantRoles = PermissionHelper::getFarmManagementRoles();

        // Check user's possession of relevant permissions and roles
        $userHasPermission = [];
        $userPermissionNames = array_column($userPermissions, 'name');
        foreach ($relevantPermissions as $permission) {
            $userHasPermission[$permission] = in_array($permission, $userPermissionNames);
        }

        $userHasRole = [];
        $userRoleNames = array_column($userRoles, 'name');
        foreach ($relevantRoles as $role) {
            $userHasRole[$role] = in_array($role, $userRoleNames);
        }

        // Determine access status based on route-level required permissions/roles (if any)
        // Note: This is less meaningful for dynamic pages, but included for completeness.
        // The more useful info is whether the user has the *relevant* permissions listed.
        $routeRequiredPermissions = PermissionHelper::getRequiredPermissions($currentRoute);
        $routeRequiredRoles = PermissionHelper::getRequiredRoles($currentRoute);

        $hasRouteRequiredPermissions = self::checkRequiredPermissions($userPermissions, $routeRequiredPermissions);
        $hasRouteRequiredRoles = self::checkRequiredRoles($userRoles, $routeRequiredRoles);


        return [
            'current_route' => $currentRoute,
            'user_permissions' => $userPermissions,
            'user_roles' => $userRoles,
            'relevant_permissions' => $userHasPermission, // Show which relevant permissions the user has
            'relevant_roles' => $userHasRole,           // Show which relevant roles the user has
            'route_required_permissions' => $routeRequiredPermissions, // Still show route requirements
            'route_required_roles' => $routeRequiredRoles,       // Still show route requirements
            'has_route_required_permissions' => $hasRouteRequiredPermissions, // Still show route access status
            'has_route_required_roles' => $hasRouteRequiredRoles             // Still show route access status
        ];
    }

    private static function checkRequiredPermissions($userPermissions, $requiredPermissions)
    {
        if (empty($requiredPermissions)) {
            return true;
        }

        $userPermissionNames = array_column($userPermissions, 'name');
        foreach ($requiredPermissions as $permission) {
            if (!in_array($permission, $userPermissionNames)) {
                return false;
            }
        }

        return true;
    }

    private static function checkRequiredRoles($userRoles, $requiredRoles)
    {
        if (empty($requiredRoles)) {
            return true;
        }

        $userRoleNames = array_column($userRoles, 'name');
        foreach ($requiredRoles as $role) {
            if (!in_array($role, $userRoleNames)) {
                return false;
            }
        }

        return true;
    }
}

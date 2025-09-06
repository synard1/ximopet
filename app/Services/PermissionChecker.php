<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Exception;

class PermissionChecker
{
    /**
     * Data access permissions matrix
     */
    private array $permissionMatrix = [
        'company_list' => [
            'required_permissions' => ['access company master data', 'read company master data'],
            'roles' => ['SuperAdmin', 'company_admin'],
            'description' => 'View company information and listings'
        ],
        'livestock_data' => [
            'required_permissions' => ['access livestock', 'read livestock', 'view livestock'],
            'roles' => ['SuperAdmin', 'company_admin', 'farm_manager', 'farm_worker'],
            'description' => 'Access livestock information and statistics'
        ],
        'financial_data' => [
            'required_permissions' => ['access report', 'read report', 'view financial data'],
            'roles' => ['SuperAdmin', 'company_admin', 'farm_manager'],
            'description' => 'View financial reports and analysis'
        ],
        'farm_data' => [
            'required_permissions' => ['access farm management', 'read farm management', 'view farms'],
            'roles' => ['SuperAdmin', 'company_admin', 'farm_manager', 'farm_worker'],
            'description' => 'Access farm management information'
        ],
        'supply_data' => [
            'required_permissions' => ['access inventory', 'read inventory', 'view supplies'],
            'roles' => ['SuperAdmin', 'company_admin', 'farm_manager', 'inventory_manager'],
            'description' => 'Access supply and inventory information'
        ],
        'analytics_data' => [
            'required_permissions' => ['access analytics', 'read analytics', 'view reports'],
            'roles' => ['SuperAdmin', 'company_admin', 'farm_manager'],
            'description' => 'Access analytics and advanced reporting'
        ]
    ];

    /**
     * Check if user can access specific data type
     */
    public function canAccessData(User $user, string $dataType): bool
    {
        try {
            // SuperAdmin and System users get full access to everything
            $userRoles = $user->getRoleNames()->toArray();
            $isSuperAdmin = !empty(array_intersect(
                array_map('strtolower', $userRoles),
                ['superadmin', 'super-admin', 'system', 'admin']
            ));

            if ($isSuperAdmin) {
                Log::info('PermissionChecker: SuperAdmin access granted', [
                    'data_type' => $dataType,
                    'user_id' => $user->id,
                    'user_roles' => $userRoles
                ]);
                return true;
            }

            if (!isset($this->permissionMatrix[$dataType])) {
                Log::warning('PermissionChecker: Unknown data type requested', [
                    'data_type' => $dataType,
                    'user_id' => $user->id
                ]);
                return false;
            }

            $config = $this->permissionMatrix[$dataType];

            // Check if user has any of the required roles (case-insensitive)
            $requiredRoles = array_map('strtolower', $config['roles']);
            $hasRequiredRole = !empty(array_intersect(
                array_map('strtolower', $userRoles),
                $requiredRoles
            ));

            if (!$hasRequiredRole) {
                Log::info('PermissionChecker: Access denied - insufficient role', [
                    'data_type' => $dataType,
                    'user_id' => $user->id,
                    'user_roles' => $userRoles,
                    'required_roles' => $config['roles']
                ]);
                return false;
            }

            // Check if user has any of the required permissions
            $hasRequiredPermission = false;
            foreach ($config['required_permissions'] as $permission) {
                if ($user->can($permission)) {
                    $hasRequiredPermission = true;
                    break;
                }
            }

            if (!$hasRequiredPermission) {
                Log::info('PermissionChecker: Access denied - insufficient permissions', [
                    'data_type' => $dataType,
                    'user_id' => $user->id,
                    'required_permissions' => $config['required_permissions']
                ]);
                return false;
            }

            // Additional checks for specific data types
            return $this->performAdditionalChecks($user, $dataType);

        } catch (Exception $e) {
            Log::error('PermissionChecker: Error checking data access', [
                'error' => $e->getMessage(),
                'data_type' => $dataType,
                'user_id' => $user->id
            ]);

            // Default to deny access on error
            return false;
        }
    }

    /**
     * Get all data types user can access
     */
    public function getAllowedDataTypes(User $user, array $requestedTypes = []): array
    {
        $allowedTypes = [];

        $typesToCheck = empty($requestedTypes) ? array_keys($this->permissionMatrix) : $requestedTypes;

        foreach ($typesToCheck as $dataType) {
            if ($this->canAccessData($user, $dataType)) {
                $allowedTypes[] = $dataType;
            }
        }

        return $allowedTypes;
    }

    /**
     * Filter data collection based on user permissions and role
     */
    public function filterDataByPermission(User $user, string $dataType, Collection $data): Collection
    {
        try {
            if (!$this->canAccessData($user, $dataType)) {
                return collect();
            }

            // Apply company scope - fundamental security layer
            $data = $this->applyCompanyScope($user, $data);

            // Apply role-based filtering
            $userRole = $user->getRoleNames()->first();

            switch ($userRole) {
                case 'farm_worker':
                    return $this->filterForWorker($user, $dataType, $data);

                case 'farm_manager':
                    return $this->filterForManager($user, $dataType, $data);

                case 'company_admin':
                    return $this->filterForCompanyAdmin($user, $dataType, $data);

                case 'SuperAdmin':
                    return $data; // Full access

                default:
                    Log::warning('PermissionChecker: Unknown user role for filtering', [
                        'user_id' => $user->id,
                        'role' => $userRole,
                        'data_type' => $dataType
                    ]);
                    return collect(); // No access for unknown roles
            }

        } catch (Exception $e) {
            Log::error('PermissionChecker: Error filtering data', [
                'error' => $e->getMessage(),
                'data_type' => $dataType,
                'user_id' => $user->id
            ]);

            return collect(); // Return empty collection on error
        }
    }

    /**
     * Apply company scoping to data
     */
    private function applyCompanyScope(User $user, Collection $data): Collection
    {
        if (!$user->company_id) {
            // Users without company association get no data
            return collect();
        }

        return $data->where('company_id', $user->company_id);
    }

    /**
     * Filter data for farm worker role
     */
    private function filterForWorker(User $user, string $dataType, Collection $data): Collection
    {
        // For now, farm workers get company-scoped access
        // TODO: Implement proper farm assignment system when farm-user relationships are defined
        Log::info('Filtering data for farm worker - company scoped', [
            'user_id' => $user->id,
            'data_type' => $dataType,
            'company_id' => $user->company_id
        ]);

        // Return company-scoped data for now
        return $data;
    }

    /**
     * Filter data for farm manager role
     */
    private function filterForManager(User $user, string $dataType, Collection $data): Collection
    {
        // Farm managers have broader access within their company
        // but may still be limited to their managed farms for some data types

        switch ($dataType) {
            case 'financial_data':
                // Managers can see summary financial data but not detailed transactions
                return $this->filterFinancialDataForManager($data);

            default:
                // Full company access for other data types
                return $data;
        }
    }

    /**
     * Filter data for company admin role
     */
    private function filterForCompanyAdmin(User $user, string $dataType, Collection $data): Collection
    {
        // Company admins have full access to all company data
        return $data;
    }

    /**
     * Filter financial data for managers
     */
    private function filterFinancialDataForManager(Collection $data): Collection
    {
        // Remove sensitive financial details, keep summary information
        return $data->map(function ($item) {
            if (is_array($item)) {
                // Remove detailed transaction information
                unset($item['detailed_transactions']);
                unset($item['profit_breakdown']);
            } elseif (is_object($item)) {
                // Remove sensitive attributes from objects
                $item->makeHidden(['detailed_transactions', 'profit_breakdown']);
            }
            return $item;
        });
    }

    /**
     * Perform additional security checks for specific data types
     */
    private function performAdditionalChecks(User $user, string $dataType): bool
    {
        switch ($dataType) {
            case 'company_list':
                // Only SuperAdmin and company_admin can see multiple companies
                if (!$user->hasRole(['SuperAdmin', 'company_admin'])) {
                    // Regular users can only see their own company
                    return $user->company_id !== null;
                }
                return true;

            case 'financial_data':
                // Additional check for financial data access during business hours
                if (config('chat.security.restrict_financial_hours', false)) {
                    $currentHour = now()->hour;
                    $businessStart = config('chat.security.business_hours_start', 8);
                    $businessEnd = config('chat.security.business_hours_end', 18);

                    if ($currentHour < $businessStart || $currentHour > $businessEnd) {
                        Log::info('PermissionChecker: Financial data access outside business hours', [
                            'user_id' => $user->id,
                            'current_hour' => $currentHour
                        ]);
                        return false;
                    }
                }
                return true;

            case 'livestock_data':
            case 'farm_data':
            case 'supply_data':
                // Must have company association for these data types
                return $user->company_id !== null;

            default:
                return true;
        }
    }

    /**
     * Get permission description for a data type
     */
    public function getPermissionDescription(string $dataType): string
    {
        return $this->permissionMatrix[$dataType]['description'] ?? 'Unknown data type';
    }

    /**
     * Get required roles for a data type
     */
    public function getRequiredRoles(string $dataType): array
    {
        return $this->permissionMatrix[$dataType]['roles'] ?? [];
    }

    /**
     * Get required permissions for a data type
     */
    public function getRequiredPermissions(string $dataType): array
    {
        return $this->permissionMatrix[$dataType]['required_permissions'] ?? [];
    }

    /**
     * Check if user can access specific company data
     */
    public function canAccessCompanyData(User $user, string $companyId): bool
    {
        // Superadmin can access all company data
        if ($user->hasRole('SuperAdmin')) {
            return true;
        }

        // Users can only access their own company data
        return $user->company_id === $companyId;
    }

    /**
     * Get access summary for user
     */
    public function getUserAccessSummary(User $user): array
    {
        $summary = [
            'user_id' => $user->id,
            'company_id' => $user->company_id,
            'roles' => $user->getRoleNames()->toArray(),
            'allowed_data_types' => [],
            'access_level' => 'none'
        ];

        foreach ($this->permissionMatrix as $dataType => $config) {
            if ($this->canAccessData($user, $dataType)) {
                $summary['allowed_data_types'][] = $dataType;
            }
        }

        // Determine access level
        if ($user->hasRole('SuperAdmin')) {
            $summary['access_level'] = 'full';
        } elseif ($user->hasRole(['company_admin', 'farm_manager'])) {
            $summary['access_level'] = 'high';
        } elseif ($user->hasRole('farm_worker')) {
            $summary['access_level'] = 'limited';
        }

        return $summary;
    }
}

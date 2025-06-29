<?php

namespace App\Services;

use App\Models\CompanyUser;
use App\Models\User;
use App\Models\Company;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class CompanyAdminManagementService
{
    /**
     * Get all admins for a company
     *
     * @param string $companyId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCompanyAdmins($companyId)
    {
        return CompanyUser::where('company_id', $companyId)
            ->where('isAdmin', true)
            ->where('status', 'active')
            ->with(['user' => function ($query) {
                $query->select('id', 'name', 'email', 'created_at');
            }])
            ->get();
    }

    /**
     * Get default admin for company
     *
     * @param string $companyId
     * @return CompanyUser|null
     */
    public function getDefaultAdmin($companyId)
    {
        return CompanyUser::getDefaultAdmin($companyId);
    }

    /**
     * Set user as default admin
     * Automatically removes existing default admin
     *
     * @param string $companyId
     * @param string $userId
     * @return array
     */
    public function setDefaultAdmin($companyId, $userId)
    {
        try {
            // Validate permissions
            if (!$this->canManageDefaultAdmin($companyId)) {
                return [
                    'success' => false,
                    'message' => 'You do not have permission to manage default admin for this company.'
                ];
            }

            // Check if user exists in company
            $companyUser = CompanyUser::where('company_id', $companyId)
                ->where('user_id', $userId)
                ->where('status', 'active')
                ->first();

            if (!$companyUser) {
                return [
                    'success' => false,
                    'message' => 'User is not mapped to this company.'
                ];
            }

            // Set as default admin
            CompanyUser::setDefaultAdmin($companyId, $userId);

            return [
                'success' => true,
                'message' => 'Default admin has been set successfully.',
                'data' => $this->getDefaultAdmin($companyId)
            ];
        } catch (Exception $e) {
            Log::error('Failed to set default admin', [
                'company_id' => $companyId,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Transfer default admin role to another user
     *
     * @param string $companyId
     * @param string $newDefaultAdminUserId
     * @return array
     */
    public function transferDefaultAdmin($companyId, $newDefaultAdminUserId)
    {
        try {
            CompanyUser::transferDefaultAdmin($companyId, $newDefaultAdminUserId);

            return [
                'success' => true,
                'message' => 'Default admin role has been transferred successfully.',
                'data' => $this->getDefaultAdmin($companyId)
            ];
        } catch (Exception $e) {
            Log::error('Failed to transfer default admin', [
                'company_id' => $companyId,
                'new_user_id' => $newDefaultAdminUserId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Promote user to admin
     *
     * @param string $companyId
     * @param string $userId
     * @param bool $setAsDefault
     * @return array
     */
    public function promoteToAdmin($companyId, $userId, $setAsDefault = false)
    {
        try {
            DB::beginTransaction();

            $companyUser = CompanyUser::where('company_id', $companyId)
                ->where('user_id', $userId)
                ->where('status', 'active')
                ->first();

            if (!$companyUser) {
                return [
                    'success' => false,
                    'message' => 'User is not mapped to this company.'
                ];
            }

            // Promote to admin
            $companyUser->update(['isAdmin' => true]);

            // Set as default admin if requested
            if ($setAsDefault) {
                CompanyUser::setDefaultAdmin($companyId, $userId);
            }

            DB::commit();

            Log::info('User promoted to admin', [
                'company_id' => $companyId,
                'user_id' => $userId,
                'is_default' => $setAsDefault,
                'promoted_by' => Auth::id()
            ]);

            return [
                'success' => true,
                'message' => 'User has been promoted to admin successfully.',
                'data' => $companyUser->fresh()
            ];
        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Failed to promote user to admin', [
                'company_id' => $companyId,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Demote admin to regular user
     * Cannot demote default admin
     *
     * @param string $companyId
     * @param string $userId
     * @return array
     */
    public function demoteAdmin($companyId, $userId)
    {
        try {
            $companyUser = CompanyUser::where('company_id', $companyId)
                ->where('user_id', $userId)
                ->where('status', 'active')
                ->first();

            if (!$companyUser) {
                return [
                    'success' => false,
                    'message' => 'User is not mapped to this company.'
                ];
            }

            // Cannot demote default admin
            if ($companyUser->isDefaultAdmin) {
                return [
                    'success' => false,
                    'message' => 'Cannot demote default admin. Please transfer default admin role first.'
                ];
            }

            // Demote from admin
            $companyUser->update(['isAdmin' => false]);

            Log::info('Admin demoted to regular user', [
                'company_id' => $companyId,
                'user_id' => $userId,
                'demoted_by' => Auth::id()
            ]);

            return [
                'success' => true,
                'message' => 'Admin has been demoted to regular user successfully.',
                'data' => $companyUser->fresh()
            ];
        } catch (Exception $e) {
            Log::error('Failed to demote admin', [
                'company_id' => $companyId,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Check if current user can manage default admin for company
     *
     * @param string $companyId
     * @return bool
     */
    public function canManageDefaultAdmin($companyId)
    {
        // SuperAdmin can manage all
        if (auth()->user()->hasRole('SuperAdmin')) {
            return true;
        }

        // Default admin can manage their own company
        if (CompanyUser::checkIsDefaultAdmin(Auth::id(), $companyId)) {
            return true;
        }

        return false;
    }

    /**
     * Get company admin statistics
     *
     * @param string $companyId
     * @return array
     */
    public function getAdminStatistics($companyId)
    {
        $stats = [
            'total_users' => CompanyUser::where('company_id', $companyId)
                ->where('status', 'active')
                ->count(),
            'total_admins' => CompanyUser::where('company_id', $companyId)
                ->where('isAdmin', true)
                ->where('status', 'active')
                ->count(),
            'has_default_admin' => CompanyUser::hasDefaultAdmin($companyId),
            'default_admin' => null
        ];

        if ($stats['has_default_admin']) {
            $defaultAdmin = $this->getDefaultAdmin($companyId);
            $stats['default_admin'] = [
                'id' => $defaultAdmin->user_id,
                'name' => $defaultAdmin->user->name,
                'email' => $defaultAdmin->user->email,
                'created_at' => $defaultAdmin->created_at
            ];
        }

        return $stats;
    }

    /**
     * Validate company admin requirements
     *
     * @param string $companyId
     * @return array
     */
    public function validateAdminRequirements($companyId)
    {
        $issues = [];

        // Check if company has default admin
        if (!CompanyUser::hasDefaultAdmin($companyId)) {
            $issues[] = 'Company does not have a default admin assigned.';
        }

        // Check if company has at least one admin
        $adminCount = CompanyUser::where('company_id', $companyId)
            ->where('isAdmin', true)
            ->where('status', 'active')
            ->count();

        if ($adminCount === 0) {
            $issues[] = 'Company does not have any administrators.';
        }

        return [
            'is_valid' => empty($issues),
            'issues' => $issues,
            'admin_count' => $adminCount
        ];
    }

    /**
     * Get available users for promotion to admin
     *
     * @param string $companyId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPromotableUsers($companyId)
    {
        return CompanyUser::where('company_id', $companyId)
            ->where('isAdmin', false)
            ->where('status', 'active')
            ->with(['user' => function ($query) {
                $query->select('id', 'name', 'email', 'created_at');
            }])
            ->get();
    }

    /**
     * Get admin history for company
     *
     * @param string $companyId
     * @param int $limit
     * @return array
     */
    public function getAdminHistory($companyId, $limit = 20)
    {
        // This would typically come from an audit log table
        // For now, return basic structure
        return [
            'current_admins' => $this->getCompanyAdmins($companyId),
            'default_admin' => $this->getDefaultAdmin($companyId),
            'statistics' => $this->getAdminStatistics($companyId)
        ];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class CompanyUser extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'company_users';

    protected $fillable = [
        'id',
        'company_id',
        'user_id',
        'isAdmin',
        'isDefaultAdmin',
        'status',
        'created_by',
        'updated_by',
    ];

    /**
     * Check if user is mapped to a company
     * 
     * @param int $userId
     * @return bool
     */
    public static function isUserMapped($userId = null)
    {
        $userId = $userId ?? Auth::id();
        return self::where('user_id', $userId)
            ->where('status', 'active')
            ->exists();
    }

    /**
     * Get company mapping for user
     * 
     * @param int $userId
     * @return CompanyUser|null
     */
    public static function getUserMapping($userId = null)
    {
        $userId = $userId ?? Auth::id();
        return self::where('user_id', $userId)
            ->where('status', 'active')
            ->first();
    }

    /**
     * Check if user is company admin
     * 
     * @param int $userId
     * @return bool
     */
    public static function isCompanyAdmin($userId = null)
    {
        $userId = $userId ?? Auth::id();
        return self::where('user_id', $userId)
            ->where('isAdmin', true)
            ->where('status', 'active')
            ->exists();
    }

    /**
     * Check if user is default admin for company
     * 
     * @param int|null $userId
     * @param string|null $companyId
     * @return bool
     */
    public static function checkIsDefaultAdmin($userId = null, $companyId = null)
    {
        $userId = $userId ?? Auth::id();

        $query = self::where('user_id', $userId)
            ->where('isDefaultAdmin', true)
            ->where('status', 'active');

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        return $query->exists();
    }

    /**
     * Get default admin for company
     * 
     * @param string $companyId
     * @return CompanyUser|null
     */
    public static function getDefaultAdmin($companyId)
    {
        return self::where('company_id', $companyId)
            ->where('isDefaultAdmin', true)
            ->where('status', 'active')
            ->first();
    }

    /**
     * Check if company has default admin
     * 
     * @param string $companyId
     * @return bool
     */
    public static function hasDefaultAdmin($companyId)
    {
        return self::where('company_id', $companyId)
            ->where('isDefaultAdmin', true)
            ->where('status', 'active')
            ->exists();
    }

    /**
     * Set user as default admin for company
     * Ensures only one default admin per company
     * 
     * @param string $companyId
     * @param string $userId
     * @return bool
     * @throws Exception
     */
    public static function setDefaultAdmin($companyId, $userId)
    {
        try {
            DB::beginTransaction();

            // Remove existing default admin for this company
            self::where('company_id', $companyId)
                ->where('isDefaultAdmin', true)
                ->update(['isDefaultAdmin' => false]);

            // Set new default admin
            $companyUser = self::where('company_id', $companyId)
                ->where('user_id', $userId)
                ->where('status', 'active')
                ->first();

            if (!$companyUser) {
                throw new Exception('User not found in company mapping');
            }

            $companyUser->update([
                'isDefaultAdmin' => true,
                'isAdmin' => true // Default admin must be admin
            ]);

            Log::info('Default admin changed for company', [
                'company_id' => $companyId,
                'new_default_admin_user_id' => $userId,
                'changed_by' => Auth::id()
            ]);

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to set default admin', [
                'company_id' => $companyId,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Check if user can be deleted
     * Default admin cannot be deleted by other admins
     * 
     * @param string $userIdToDelete
     * @param string|null $deletingUserId
     * @return array ['can_delete' => bool, 'reason' => string]
     */
    public static function canDeleteUser($userIdToDelete, $deletingUserId = null)
    {
        $deletingUserId = $deletingUserId ?? Auth::id();

        // SuperAdmin can delete anyone
        if (auth()->user()->hasRole('SuperAdmin')) {
            return ['can_delete' => true, 'reason' => ''];
        }

        // Get the user to delete mapping
        $userToDelete = self::where('user_id', $userIdToDelete)
            ->where('status', 'active')
            ->first();

        if (!$userToDelete) {
            return ['can_delete' => true, 'reason' => ''];
        }

        // Check if user to delete is default admin – always protected (except by SuperAdmin above)
        if ($userToDelete->isDefaultAdmin) {
            // Get current user mapping
            $currentUser = self::where('user_id', $deletingUserId)
                ->where('status', 'active')
                ->first();

            // If deleting user is from same company and is admin but not default admin
            if (
                $currentUser &&
                $currentUser->company_id === $userToDelete->company_id &&
                $currentUser->isAdmin &&
                !$currentUser->isDefaultAdmin
            ) {

                return [
                    'can_delete' => false,
                    'reason' => 'Default admin cannot be deleted by other administrators. Only SuperAdmin can delete default admin.'
                ];
            }

            // If trying to delete themselves as default admin
            if ($deletingUserId === $userIdToDelete) {
                return [
                    'can_delete' => false,
                    'reason' => 'Default admin cannot delete themselves. Please transfer default admin role first.'
                ];
            }
        }

        // Block deletion of company administrators (isAdmin) by non-SuperAdmin
        if ($userToDelete->isAdmin) {
            return [
                'can_delete' => false,
                'reason' => 'Administrator users cannot be deleted by non-SuperAdmin. Please downgrade the user first or ask SuperAdmin.'
            ];
        }

        return ['can_delete' => true, 'reason' => ''];
    }

    /**
     * Transfer default admin role to another user
     * 
     * @param string $companyId
     * @param string $newDefaultAdminUserId
     * @return bool
     * @throws Exception
     */
    public static function transferDefaultAdmin($companyId, $newDefaultAdminUserId)
    {
        $currentUserId = Auth::id();

        // Check if current user is default admin
        if (!self::checkIsDefaultAdmin($currentUserId, $companyId)) {
            throw new Exception('Only default admin can transfer the role');
        }

        // Check if new user exists in company
        $newUser = self::where('company_id', $companyId)
            ->where('user_id', $newDefaultAdminUserId)
            ->where('status', 'active')
            ->first();

        if (!$newUser) {
            throw new Exception('New default admin user not found in company');
        }

        return self::setDefaultAdmin($companyId, $newDefaultAdminUserId);
    }

    /**
     * Get all companies for user
     * 
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getUserCompanies($userId = null)
    {
        $userId = $userId ?? Auth::id();
        return self::where('user_id', $userId)
            ->where('status', 'active')
            ->with('company')
            ->get();
    }

    /**
     * Check if a company has any users mapped
     *
     * @param string|int $companyId
     * @return bool
     */
    public static function checkCompanyHasUsers($companyId): bool
    {
        return self::where('company_id', $companyId)
            ->where('status', 'active')
            ->exists();
    }

    /**
     * Relationship with Company model
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Relationship with User model
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        // Validate before saving
        static::saving(function ($companyUser) {
            // If setting as default admin, ensure only one default admin per company
            if ($companyUser->isDefaultAdmin) {
                $existingDefaultAdmin = self::where('company_id', $companyUser->company_id)
                    ->where('isDefaultAdmin', true)
                    ->where('id', '!=', $companyUser->id)
                    ->where('status', 'active')
                    ->first();

                if ($existingDefaultAdmin) {
                    throw new Exception('Company can only have one default admin. Please remove existing default admin first.');
                }

                // Default admin must be admin
                $companyUser->isAdmin = true;
            }
        });

        // Prevent deletion of default admin by non-SuperAdmin
        static::deleting(function ($companyUser) {
            if ($companyUser->isDefaultAdmin && !auth()->user()->hasRole('SuperAdmin')) {
                $canDelete = self::canDeleteUser($companyUser->user_id);
                if (!$canDelete['can_delete']) {
                    throw new Exception($canDelete['reason']);
                }
            }
        });

        // After creating/updating company user mapping, sync to User model
        static::saved(function ($companyUser) {
            $companyUser->syncToUserModel();
        });

        // After deleting company user mapping, check if user needs company_id removed
        static::deleted(function ($companyUser) {
            $companyUser->handleUserCompanyIdOnDelete();
        });
    }

    /**
     * Sync company_id to User model
     */
    public function syncToUserModel()
    {
        if (!$this->user) {
            return;
        }

        if ($this->status === 'active') {
            // Set company_id to user
            $this->user->update(['company_id' => $this->company_id]);
        } else {
            // Check if user has other active mappings
            $activeMappings = self::where('user_id', $this->user_id)
                ->where('status', 'active')
                ->where('id', '!=', $this->id)
                ->first();

            if (!$activeMappings) {
                // No other active mappings, remove company_id from user
                $this->user->update(['company_id' => null]);
            }
        }
    }

    /**
     * Handle User company_id when CompanyUser is deleted
     */
    public function handleUserCompanyIdOnDelete()
    {
        if (!$this->user) {
            return;
        }

        // Check if user has other active mappings
        $activeMappings = self::where('user_id', $this->user_id)
            ->where('status', 'active')
            ->first();

        if (!$activeMappings) {
            // No other active mappings, remove company_id from user
            $this->user->update(['company_id' => null]);
        }
    }

    /**
     * Clear all user mapping for specific company or all companies
     * @param string|null $companyId
     * @return int jumlah data yang dihapus
     */
    public static function clearUserMapping($companyId = null): int
    {
        $baseQuery = self::query();
        if ($companyId) {
            $baseQuery->where('company_id', $companyId);
        }

        $deleted = 0;
        // gunakan cursor agar memory efisien & aman untuk UUID key
        foreach ($baseQuery->cursor() as $row) {
            // Soft delete agar masih tercatat (atau gunakan forceDelete() jika benar2 mau hilang)
            $row->delete();
            $deleted++;
        }

        Log::info('ClearUserMapping executed', [
            'company_id' => $companyId,
            'deleted_count' => $deleted,
            'by_user' => auth()->id()
        ]);

        return $deleted;
    }
}

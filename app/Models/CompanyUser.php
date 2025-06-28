<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

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
}

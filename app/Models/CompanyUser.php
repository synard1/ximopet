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
}

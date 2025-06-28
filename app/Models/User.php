<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Permission\Models\Role;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;
    use HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at',
        'last_login_at',
        'last_login_ip',
        'profile_photo_path',
        'status',
        'company_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Get the name of the unique identifier for the user.
     *
     * @return string
     */
    public function getAuthIdentifierName()
    {
        return 'id';
    }

    /**
     * Get the unique identifier for the user.
     *
     * @return mixed
     */
    public function getAuthIdentifier()
    {
        return $this->{$this->getAuthIdentifierName()};
    }

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->password;
    }

    /**
     * Get the remember token for the user.
     *
     * @return string|null
     */
    public function getRememberToken()
    {
        return $this->remember_token;
    }

    /**
     * Set the remember token for the user.
     *
     * @param  string  $value
     * @return void
     */
    public function setRememberToken($value)
    {
        $this->remember_token = $value;
    }

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function getRememberTokenName()
    {
        return 'remember_token';
    }

    public function getProfilePhotoUrlAttribute()
    {
        if ($this->profile_photo_path) {
            return asset('storage/' . $this->profile_photo_path);
        }

        return $this->profile_photo_path;
    }

    public function farmOperators()
    {
        return $this->hasMany(FarmOperator::class);
    }

    // Define relationships with Farm and User models
    public function farms()
    {
        return $this->belongsTo(Farm::class);
    }

    public function loginLogs()
    {
        return $this->hasMany(LoginLog::class);
    }

    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return null; // see the note above in Gate::before about why null must be returned here.
    }

    /**
     * Temp Auth relationships
     */

    /**
     * Hak autorisasi yang dimiliki user ini
     */
    public function tempAuthAuthorizers()
    {
        return $this->hasMany(TempAuthAuthorizer::class);
    }

    /**
     * Hak autorisasi yang diberikan oleh user ini ke user lain
     */
    public function givenTempAuthAuthorizers()
    {
        return $this->hasMany(TempAuthAuthorizer::class, 'authorized_by');
    }

    /**
     * Log autorisasi temporer yang diterima user ini
     */
    public function tempAuthLogs()
    {
        return $this->hasMany(TempAuthLog::class);
    }

    /**
     * Log autorisasi temporer yang diberikan oleh user ini
     */
    public function givenTempAuthLogs()
    {
        return $this->hasMany(TempAuthLog::class, 'authorizer_user_id');
    }

    /**
     * Helper methods untuk temp authorization
     */

    /**
     * Check apakah user dapat memberikan autorisasi temporer
     */
    public function canGrantTempAuthorization(): bool
    {
        // Check by role
        $authorizedRoles = config('temp_auth.user.authorized_roles', []);
        if ($this->hasAnyRole($authorizedRoles)) {
            return true;
        }

        // Check by permission
        $authorizedPermissions = config('temp_auth.user.authorized_permissions', []);
        if ($this->hasAnyPermission($authorizedPermissions)) {
            return true;
        }

        // Check by database field (if configured)
        $dbField = config('temp_auth.user.database_field');
        if ($dbField && isset($this->attributes[$dbField])) {
            return (bool) $this->attributes[$dbField];
        }

        // Check by explicit authorizer record
        return $this->tempAuthAuthorizers()->active()->exists();
    }

    /**
     * Get active temp auth authorizer record
     */
    public function getActiveTempAuthAuthorizer()
    {
        return $this->tempAuthAuthorizers()->active()->first();
    }

    /**
     * Check apakah user bisa memberikan autorisasi untuk komponen tertentu
     */
    public function canAuthorizeTempAccessFor(string $component): bool
    {
        if (!$this->canGrantTempAuthorization()) {
            return false;
        }

        $authorizer = $this->getActiveTempAuthAuthorizer();
        if ($authorizer) {
            return $authorizer->canAuthorizeComponent($component);
        }

        return true; // Jika authorization via role/permission, allow semua komponen
    }

    /**
     * Get the company users associated with the user.
     */
    public function companyUsers()
    {
        return $this->hasMany(CompanyUser::class);
    }

    /**
     * Get the company associated with the user.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the primary company for the user (from company_users table)
     */
    public function getPrimaryCompany()
    {
        $companyUser = $this->companyUsers()
            ->where('status', 'active')
            ->where('isAdmin', true)
            ->first();

        return $companyUser ? $companyUser->company : $this->company;
    }

    /**
     * Check if user has a company assigned
     */
    public function hasCompany(): bool
    {
        return !is_null($this->company_id) || $this->companyUsers()->where('status', 'active')->exists();
    }

    /**
     * Get available roles based on user type
     * 
     * @return array
     */
    public function getAvailableRoles()
    {
        if ($this->hasRole('SuperAdmin')) {
            return Role::all();
        }

        if ($this->hasRole('Administrator')) {
            $configRoles = config('xolution.company_roles', []);
            return Role::whereIn('name', array_keys($configRoles))->get();
        }

        return collect();
    }

    /**
     * Check if user can be deleted (no related data)
     */
    public function canBeDeleted(): bool
    {
        // Check company users
        if ($this->companyUsers()->exists()) {
            return false;
        }

        // Check farm operators
        if ($this->farmOperators()->exists()) {
            return false;
        }

        // Check login logs
        if ($this->loginLogs()->exists()) {
            return false;
        }

        // Check temp auth logs
        if ($this->tempAuthLogs()->exists()) {
            return false;
        }

        // Check given temp auth logs
        if ($this->givenTempAuthLogs()->exists()) {
            return false;
        }

        // Check temp auth authorizers
        if ($this->tempAuthAuthorizers()->exists()) {
            return false;
        }

        // Check given temp auth authorizers
        if ($this->givenTempAuthAuthorizers()->exists()) {
            return false;
        }

        // Check farms through farm operators
        if ($this->farms()->exists()) {
            return false;
        }

        // Check audit trails
        if (\App\Models\AuditTrail::where('user_id', $this->id)->exists()) {
            return false;
        }

        // Check data audit trails
        if (\App\Models\DataAuditTrail::where('user_id', $this->id)->exists()) {
            return false;
        }

        // Check model verifications
        if (\App\Models\ModelVerification::where('verified_by', $this->id)->exists()) {
            return false;
        }

        // Check verification logs
        if (\App\Models\VerificationLog::where('user_id', $this->id)->exists()) {
            return false;
        }

        // Check created_by/updated_by in various models
        $modelsToCheck = [
            \App\Models\LivestockPurchase::class,
            \App\Models\FeedPurchase::class,
            \App\Models\SupplyPurchase::class,
            \App\Models\LivestockMutation::class,
            \App\Models\FeedMutation::class,
            \App\Models\SupplyMutation::class,
            \App\Models\Recording::class,
            \App\Models\FeedUsage::class,
            \App\Models\SupplyUsage::class,
            \App\Models\OVKRecord::class,
            \App\Models\SalesTransaction::class,
            // \App\Models\Transaksi::class,
            // \App\Models\TransaksiBeli::class,
            // \App\Models\TransaksiJual::class,
            // \App\Models\TransaksiHarian::class,
            // \App\Models\TransaksiTernak::class,
        ];

        foreach ($modelsToCheck as $model) {
            if ($model::where('created_by', $this->id)->orWhere('updated_by', $this->id)->exists()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get list of reasons why user cannot be deleted
     */
    public function getDeletionBlockers(): array
    {
        $blockers = [];

        if ($this->companyUsers()->exists()) {
            $blockers[] = 'User is associated with companies';
        }

        if ($this->farmOperators()->exists()) {
            $blockers[] = 'User is a farm operator';
        }

        if ($this->loginLogs()->exists()) {
            $blockers[] = 'User has login history';
        }

        if ($this->tempAuthLogs()->exists()) {
            $blockers[] = 'User has temporary authorization logs';
        }

        if ($this->givenTempAuthLogs()->exists()) {
            $blockers[] = 'User has given temporary authorizations';
        }

        if ($this->tempAuthAuthorizers()->exists()) {
            $blockers[] = 'User is a temporary authorizer';
        }

        if ($this->givenTempAuthAuthorizers()->exists()) {
            $blockers[] = 'User has given temporary authorizations';
        }

        if ($this->farms()->exists()) {
            $blockers[] = 'User is associated with farms';
        }

        if (\App\Models\AuditTrail::where('user_id', $this->id)->exists()) {
            $blockers[] = 'User has audit trail records';
        }

        if (\App\Models\DataAuditTrail::where('user_id', $this->id)->exists()) {
            $blockers[] = 'User has data audit trail records';
        }

        if (\App\Models\ModelVerification::where('verified_by', $this->id)->exists()) {
            $blockers[] = 'User has verification records';
        }

        if (\App\Models\VerificationLog::where('user_id', $this->id)->exists()) {
            $blockers[] = 'User has verification logs';
        }

        // Check created_by/updated_by in various models
        $modelsToCheck = [
            \App\Models\LivestockPurchase::class => 'livestock purchases',
            \App\Models\FeedPurchase::class => 'feed purchases',
            \App\Models\SupplyPurchase::class => 'supply purchases',
            \App\Models\LivestockMutation::class => 'livestock mutations',
            \App\Models\FeedMutation::class => 'feed mutations',
            \App\Models\SupplyMutation::class => 'supply mutations',
            \App\Models\Recording::class => 'recordings',
            \App\Models\FeedUsage::class => 'feed usages',
            \App\Models\SupplyUsage::class => 'supply usages',
            \App\Models\OVKRecord::class => 'OVK records',
            \App\Models\SalesTransaction::class => 'sales transactions',
            // \App\Models\Transaksi::class => 'transactions',
            // \App\Models\TransaksiBeli::class => 'purchase transactions',
            // \App\Models\TransaksiJual::class => 'sales transactions',
            // \App\Models\TransaksiHarian::class => 'daily transactions',
            // \App\Models\TransaksiTernak::class => 'livestock transactions',
        ];

        foreach ($modelsToCheck as $model => $description) {
            if ($model::where('created_by', $this->id)->orWhere('updated_by', $this->id)->exists()) {
                $blockers[] = "User has {$description}";
            }
        }

        return $blockers;
    }
}

<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

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
    ];

    public function getProfilePhotoUrlAttribute()
    {
        if ($this->profile_photo_path) {
            return asset('storage/' . $this->profile_photo_path);
        }

        return $this->profile_photo_path;
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function getDefaultAddressAttribute()
    {
        return $this->addresses?->first();
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
}

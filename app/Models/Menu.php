<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class Menu extends BaseModel
{
    use HasFactory;
    use HasUuids;

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * Menu tidak memerlukan company_id karena digunakan untuk semua tenant
     * Roles dan permissions tetap di-scope oleh company_id
     */
    protected $requiresCompanyId = false;

    protected $fillable = [
        'parent_id',
        'name',
        'label',
        'route',
        'icon',
        'location',
        'order_number',
        'is_active',
        'created_by',
        'updated_by'
    ];

    public function parent()
    {
        return $this->belongsTo(Menu::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Menu::class, 'parent_id')->orderBy('order_number');
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class);
    }

    /**
     * Custom scope untuk bypass company scope pada Menu
     * Menu tidak memiliki company_id scope, digunakan untuk semua tenant
     */
    public function scopeWithoutCompanyScope($query)
    {
        // Menu tidak memiliki company_id scope, jadi tidak perlu bypass
        // Method ini dibuat untuk konsistensi dengan model lain
        return $query;
    }

    /**
     * Get menu by location with company scope bypass for menu
     * Roles and permissions tetap di-scope oleh company_id
     */
    public static function getMenuByLocation($location, $user)
    {
        // Log untuk debugging
        Log::info('Menu::getMenuByLocation called', [
            'location' => $location,
            'user_id' => $user->id,
            'user_company_id' => $user->company_id,
            'user_roles' => $user->roles->pluck('name')->toArray()
        ]);

        // Query menu tanpa company scope (karena menu untuk semua tenant)
        // Menu model tidak memiliki global company scope, jadi tidak perlu withoutCompanyScope()
        $query = self::where('location', $location)
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->orderBy('order_number');

        if (!$user->hasRole('SuperAdmin')) {
            $query->where(function ($q) use ($user) {
                // Menu yang memiliki roles yang sesuai dengan user
                // Roles tetap di-scope oleh company_id (via HasCompanyScope trait)
                $q->whereHas('roles', function ($q) use ($user) {
                    $q->whereIn('roles.id', $user->roles->pluck('id'));
                    // Roles sudah otomatis di-scope oleh CompanyScope
                })
                    // ATAU Menu yang memiliki permissions yang sesuai dengan user
                    // Permissions tetap di-scope oleh company_id (via HasCompanyScope trait)
                    ->orWhereHas('permissions', function ($q) use ($user) {
                        $q->whereIn('permissions.id', $user->getAllPermissions()->pluck('id'));
                        // Permissions sudah otomatis di-scope oleh CompanyScope
                    });
            });
        }

        // Log query yang akan dijalankan
        Log::info('Menu query built', [
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings()
        ]);

        $result = $query->with(['children' => function ($q) use ($user) {
            if (!$user->hasRole('SuperAdmin')) {
                $q->where(function ($q) use ($user) {
                    // Menu yang memiliki roles yang sesuai dengan user
                    $q->whereHas('roles', function ($q) use ($user) {
                        $q->whereIn('roles.id', $user->roles->pluck('id'));
                    })
                        // ATAU Menu yang memiliki permissions yang sesuai dengan user
                        ->orWhereHas('permissions', function ($q) use ($user) {
                            $q->whereIn('permissions.id', $user->getAllPermissions()->pluck('id'));
                        });
                });
            }
        }])->get();

        // Log hasil query
        Log::info('Menu query result', [
            'count' => $result->count(),
            'menu_names' => $result->pluck('name')->toArray()
        ]);

        return $result;
    }

    /**
     * Get menu by location for API with company scope bypass for menu
     * Roles and permissions tetap di-scope oleh company_id
     */
    public static function getMenuByLocationApi($location, $token)
    {
        // Authenticate the user using the token
        $user = Auth::guard('sanctum')->user(); // Use the Sanctum guard to get the authenticated user

        if (!$user) {
            throw new \Exception('Unauthenticated'); // Handle unauthenticated user
        }

        // Log untuk debugging
        Log::info('Menu::getMenuByLocationApi called', [
            'location' => $location,
            'user_id' => $user->id,
            'user_company_id' => $user->company_id,
            'user_roles' => $user->roles->pluck('name')->toArray()
        ]);

        // Query menu tanpa company scope (karena menu untuk semua tenant)
        // Menu model tidak memiliki global company scope, jadi tidak perlu withoutCompanyScope()
        $query = self::where('location', $location)
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->orderBy('order_number');

        if (!$user->hasRole('SuperAdmin')) {
            $query->where(function ($q) use ($user) {
                // Menu yang memiliki roles yang sesuai dengan user
                // Roles tetap di-scope oleh company_id (via HasCompanyScope trait)
                $q->whereHas('roles', function ($q) use ($user) {
                    $q->whereIn('roles.id', $user->roles->pluck('id'));
                    // Roles sudah otomatis di-scope oleh CompanyScope
                })
                    // ATAU Menu yang memiliki permissions yang sesuai dengan user
                    // Permissions tetap di-scope oleh company_id (via HasCompanyScope trait)
                    ->orWhereHas('permissions', function ($q) use ($user) {
                        $q->whereIn('permissions.id', $user->getAllPermissions()->pluck('id'));
                        // Permissions sudah otomatis di-scope oleh CompanyScope
                    });
            });
        }

        // Log query yang akan dijalankan
        Log::info('Menu API query built', [
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings()
        ]);

        $result = $query->with(['children' => function ($q) use ($user) {
            if (!$user->hasRole('SuperAdmin')) {
                $q->where(function ($q) use ($user) {
                    // Menu yang memiliki roles yang sesuai dengan user
                    $q->whereHas('roles', function ($q) use ($user) {
                        $q->whereIn('roles.id', $user->roles->pluck('id'));
                    })
                        // ATAU Menu yang memiliki permissions yang sesuai dengan user
                        ->orWhereHas('permissions', function ($q) use ($user) {
                            $q->whereIn('permissions.id', $user->getAllPermissions()->pluck('id'));
                        });
                });
            }
        }])->get();

        // Log hasil query
        Log::info('Menu API query result', [
            'count' => $result->count(),
            'menu_names' => $result->pluck('name')->toArray()
        ]);

        return $result;
    }
}

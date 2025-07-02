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

    public static function getMenuByLocation($location, $user)
    {
        $query = self::where('location', $location)
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->orderBy('order_number');

        if (!$user->hasRole('SuperAdmin')) {
            $query->where(function ($q) use ($user) {
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

        return $query->with(['children' => function ($q) use ($user) {
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
    }

    public static function getMenuByLocationApi($location, $token)
    {
        // Authenticate the user using the token
        $user = Auth::guard('sanctum')->user(); // Use the Sanctum guard to get the authenticated user

        if (!$user) {
            throw new \Exception('Unauthenticated'); // Handle unauthenticated user
        }

        $query = self::where('location', $location)
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->orderBy('order_number');

        if (!$user->hasRole('SuperAdmin')) {
            $query->where(function ($q) use ($user) {
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

        return $query->with(['children' => function ($q) use ($user) {
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
    }
}

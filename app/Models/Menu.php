<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Log;

class Menu extends Model
{
    protected $fillable = [
        'parent_id',
        'name',
        'label',
        'route',
        'icon',
        'location',
        'order_number',
        'is_active'
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
}

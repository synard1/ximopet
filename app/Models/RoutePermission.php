<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoutePermission extends Model
{
    protected $fillable = [
        'route_name',
        'route_path',
        'method',
        'middleware',
        'permission_name',
        'is_active',
        'description'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'middleware' => 'array'
    ];
}

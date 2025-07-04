<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Models\Role as SpatieRole;
use Illuminate\Support\Facades\Auth;

class Role extends SpatieRole
{
    use HasFactory;
    use HasUuids;
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'company_id',
        'name',
        'guard_name',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (is_null($model->company_id) && !is_null(Auth::id())) {
                $model->company_id = Auth::user()->company_id;
            }
        });
    }
}

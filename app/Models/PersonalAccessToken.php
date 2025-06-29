<?php

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;
use Illuminate\Support\Str;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $casts = [
        'abilities' => 'array',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }

            // Ensure abilities is JSON encoded if it's an array
            if (is_array($model->abilities)) {
                $model->abilities = json_encode($model->abilities);
            }
        });
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class BaseModel extends Model
{
    // Specify the fillable fields
    protected $fillable = [
        'created_by',
        'updated_by',
    ];

    // Boot method to listen for events
    protected static function boot()
    {
        parent::boot();

        // Set created_by on creation
        static::creating(function ($model) {
            if (!$model->created_by && Auth::id()) {
                $model->created_by = Auth::id();
            }
        });

        // Set updated_by on update 
        static::updating(function ($model) {
            if (Auth::id()) {
                $model->updated_by = Auth::id();
            }
        });

        // Set updated_by on update 
        static::updated(function ($model) {
            if (Auth::id()) {
                $model->updated_by = Auth::id();
            }
        });
    }
}

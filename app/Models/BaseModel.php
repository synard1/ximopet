<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class BaseModel extends Model
{
    // Specify the fillable fields
    protected $fillable = [
        'company_id',
        'created_by',
        'updated_by',
    ];

    // Boot method to listen for events
    protected static function boot()
    {
        parent::boot();

        // Set created_by, updated_by and company_id on creation
        static::creating(function ($model) {
            if (!$model->created_by && Auth::id()) {
                $model->created_by = Auth::id();
            }

            // Set updated_by during creation as well (since DB expects NOT NULL)
            if (!$model->updated_by && Auth::id()) {
                $model->updated_by = Auth::id();
            }

            // Set company_id from User model if not already set
            if (!$model->company_id && Auth::id()) {
                $user = Auth::user();
                if ($user && $user->company_id) {
                    $model->company_id = $user->company_id;
                }
            }
        });

        // Set updated_by and company_id on update 
        static::updating(function ($model) {
            if (Auth::id()) {
                $model->updated_by = Auth::id();
            }

            // Set company_id from User model if still null/empty
            if (!$model->company_id && Auth::id()) {
                $user = Auth::user();
                if ($user && $user->company_id) {
                    $model->company_id = $user->company_id;
                }
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

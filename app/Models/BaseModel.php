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
            $model->created_by = Auth::id(); // Get the authenticated user's ID
        });

        // Set updated_by on update
        static::updating(function ($model) {
            $model->updated_by = Auth::id(); // Get the authenticated user's ID
        });
    }
}

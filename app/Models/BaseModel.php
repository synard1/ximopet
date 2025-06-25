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

        // Set created_by and company_id on creation
        static::creating(function ($model) {
            if (!$model->created_by && Auth::id()) {
                $model->created_by = Auth::id();
            }
            
            // Set company_id from CompanyUser if not already set
            if (!$model->company_id && Auth::id()) {
                $companyUser = \App\Models\CompanyUser::where('user_id', Auth::id())->first();
                if ($companyUser) {
                    $model->company_id = $companyUser->company_id;
                }
            }
        });

        // Set updated_by and company_id on update 
        static::updating(function ($model) {
            if (Auth::id()) {
                $model->updated_by = Auth::id();
            }
            
            // Set company_id from CompanyUser if still null/empty
            if (!$model->company_id && Auth::id()) {
                $companyUser = \App\Models\CompanyUser::where('user_id', Auth::id())->first();
                if ($companyUser) {
                    $model->company_id = $companyUser->company_id;
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

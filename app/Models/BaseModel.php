<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class BaseModel extends Model
{
    /**
     * Whether this model requires company_id handling
     * Set to false for detail models that inherit company_id from parent
     *
     * @var bool
     */
    protected $requiresCompanyId = true;

    /**
     * Additional fillable fields for child models
     *
     * @var array
     */
    protected $additionalFillable = [];

    /**
     * Setup fillable fields dynamically in constructor
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        // Only set $fillable if not already set in child
        if (!property_exists($this, 'fillable') || empty($this->fillable)) {
            $baseFillable = ['created_by', 'updated_by'];
            if ($this->requiresCompanyId) {
                $baseFillable[] = 'company_id';
            }
            $this->fillable = array_merge($baseFillable, $this->additionalFillable);
        }
    }

    /**
     * Boot method to listen for events
     */
    protected static function boot()
    {
        parent::boot();

        // Set created_by, updated_by and company_id on creation
        static::creating(function ($model) {
            // Always set created_by if not set and user is authenticated
            if (!$model->created_by && Auth::id()) {
                $model->created_by = Auth::id();
            }

            // Set updated_by during creation as well (since DB expects NOT NULL)
            if (!$model->updated_by && Auth::id()) {
                $model->updated_by = Auth::id();
            }

            // Set company_id only if this model requires it
            if ($model->requiresCompanyId && !$model->company_id && Auth::id()) {
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

            // Set company_id only if this model requires it and still null/empty
            if ($model->requiresCompanyId && !$model->company_id && Auth::id()) {
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

    /**
     * Check if this model requires company_id
     *
     * @return bool
     */
    public function requiresCompanyId(): bool
    {
        return $this->requiresCompanyId;
    }

    /**
     * Set whether this model requires company_id
     *
     * @param bool $requires
     * @return $this
     */
    public function setRequiresCompanyId(bool $requires): self
    {
        $this->requiresCompanyId = $requires;
        // Update fillable property accordingly
        $baseFillable = ['created_by', 'updated_by'];
        if ($this->requiresCompanyId) {
            $baseFillable[] = 'company_id';
        }
        $this->fillable = array_merge($baseFillable, $this->additionalFillable);
        return $this;
    }
}

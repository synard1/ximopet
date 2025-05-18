<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class AuditTrail extends Model
{
    use HasUuids;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_name',
        'model_id',
        'model_ids',
        'ip_address',
        'user_agent',
        'reason',
        'related_records',
        'additional_info',
        'user_info',
        'timestamp',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'model_ids' => 'array',
        'related_records' => 'array',
        'additional_info' => 'array',
        'user_info' => 'array',
        'timestamp' => 'datetime',
    ];
    
    /**
     * Get the user that performed the action.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Scope a query to only include delete operations.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDeletes($query)
    {
        return $query->whereIn('action', ['delete', 'batch_delete', 'cascade_delete']);
    }
    
    /**
     * Scope a query to specific model type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $modelType
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForModel($query, $modelType)
    {
        return $query->where('model_type', $modelType);
    }
    
    /**
     * Scope a query to actions by a specific user.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
    
    /**
     * Scope a query to actions within a date range.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $startDate
     * @param  string  $endDate
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('timestamp', [$startDate, $endDate]);
    }
}
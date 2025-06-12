<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\BaseModel;

class SupplyStatusHistory extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'supplyable_type',
        'supplyable_id',
        'model_name',
        'status_from',
        'status_to',
        'notes',
        'metadata',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'metadata' => 'array'
    ];

    /**
     * Get the parent supplyable model (SupplyPurchaseBatch, SupplyPurchase, SupplyStock, etc.)
     */
    public function supplyable()
    {
        return $this->morphTo();
    }

    /**
     * Get the user who created this status change
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this status change
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope for filtering by specific model type
     */
    public function scopeForModel($query, $modelClass)
    {
        return $query->where('supplyable_type', $modelClass);
    }

    /**
     * Scope for filtering by specific model instance
     */
    public function scopeForModelInstance($query, $model)
    {
        return $query->where('supplyable_type', get_class($model))
            ->where('supplyable_id', $model->id);
    }

    /**
     * Scope for filtering by status transition
     */
    public function scopeStatusTransition($query, $from, $to)
    {
        return $query->where('status_from', $from)
            ->where('status_to', $to);
    }

    /**
     * Get formatted status transition
     */
    public function getStatusTransitionAttribute()
    {
        return "{$this->status_from} → {$this->status_to}";
    }

    /**
     * Get human readable model name
     */
    public function getHumanReadableModelNameAttribute()
    {
        $modelMapping = [
            'App\Models\SupplyPurchaseBatch' => 'Supply Purchase Batch',
            'App\Models\SupplyPurchase' => 'Supply Purchase',
            'App\Models\SupplyStock' => 'Supply Stock',
            'App\Models\SupplyUsage' => 'Supply Usage',
            'App\Models\SupplyMutation' => 'Supply Mutation',
            'App\Models\CurrentSupply' => 'Current Supply',
        ];

        return $modelMapping[$this->supplyable_type] ?? class_basename($this->supplyable_type);
    }

    /**
     * Create status history for a model
     */
    public static function createForModel($model, $statusFrom, $statusTo, $notes = null, $metadata = [])
    {
        $userId = auth()->id();
        $ip = request()->ip();

        // Merge additional metadata
        $metadata = array_merge($metadata, [
            'ip_address' => $ip,
            'user_id' => $userId,
            'user_agent' => request()->header('User-Agent'),
            'timestamp' => now()->toDateTimeString(),
        ]);

        return static::create([
            'supplyable_type' => get_class($model),
            'supplyable_id' => $model->id,
            'model_name' => class_basename($model),
            'status_from' => $statusFrom,
            'status_to' => $statusTo,
            'notes' => $notes,
            'metadata' => $metadata,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
    }
}

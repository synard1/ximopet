<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\BaseModel;

class FeedStatusHistory extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'feedable_type',
        'feedable_id',
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
     * Get the parent feedable model (FeedPurchaseBatch, FeedPurchase, FeedStock, etc.)
     */
    public function feedable()
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
        return $query->where('feedable_type', $modelClass);
    }

    /**
     * Scope for filtering by specific model instance
     */
    public function scopeForModelInstance($query, $model)
    {
        return $query->where('feedable_type', get_class($model))
            ->where('feedable_id', $model->id);
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
            'App\Models\FeedPurchaseBatch' => 'Feed Purchase Batch',
            'App\Models\FeedPurchase' => 'Feed Purchase',
            'App\Models\FeedStock' => 'Feed Stock',
            'App\Models\FeedUsage' => 'Feed Usage',
            'App\Models\FeedMutation' => 'Feed Mutation',
            'App\Models\CurrentFeed' => 'Current Feed',
        ];

        return $modelMapping[$this->feedable_type] ?? class_basename($this->feedable_type);
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
            'feedable_type' => get_class($model),
            'feedable_id' => $model->id,
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

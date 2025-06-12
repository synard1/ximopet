<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Traits\HasFeedStatusHistory;

class FeedPurchaseBatch extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids, HasFeedStatusHistory;

    // Status Constants
    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_IN_TRANSIT = 'in_transit';
    const STATUS_ARRIVED = 'arrived';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_COMPLETED = 'completed';

    // Status Labels
    const STATUS_LABELS = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_PENDING => 'Pending',
        self::STATUS_CONFIRMED => 'Confirmed',
        self::STATUS_IN_TRANSIT => 'In Transit',
        self::STATUS_ARRIVED => 'Arrived',
        self::STATUS_CANCELLED => 'Cancelled',
        self::STATUS_COMPLETED => 'Completed'
    ];

    protected $fillable = [
        'id',
        'invoice_number',
        'do_number',
        'supplier_id',
        'expedition_id',
        'date',
        'expedition_fee',
        'data',
        'notes',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date' => 'date',
        'data' => 'array',

    ];

    // Helper Methods
    public function isDraft()
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isConfirmed()
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function isInTransit()
    {
        return $this->status === self::STATUS_IN_TRANSIT;
    }

    public function isArrived()
    {
        return $this->status === self::STATUS_ARRIVED;
    }

    public function isCancelled()
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isCompleted()
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function canBeEdited()
    {
        return in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_PENDING
        ]);
    }

    public function canBeCancelled()
    {
        return !in_array($this->status, [
            self::STATUS_CANCELLED,
            self::STATUS_COMPLETED
        ]);
    }

    public function getStatusLabel()
    {
        return self::STATUS_LABELS[$this->status] ?? 'Unknown';
    }

    // Relations
    public function supplier()
    {
        return $this->belongsTo(Partner::class, 'supplier_id', 'id');
    }

    public function expedition()
    {
        return $this->belongsTo(Partner::class, 'expedition_id', 'id');
    }

    public function feedPurchases()
    {
        return $this->hasMany(FeedPurchase::class);
    }

    /**
     * Override trait method to define which statuses require notes
     */
    protected function requiresNotesForStatus($status)
    {
        return in_array($status, [self::STATUS_CANCELLED, self::STATUS_COMPLETED]);
    }

    /**
     * Override trait method to get available statuses
     */
    public function getAvailableStatuses()
    {
        return array_keys(self::STATUS_LABELS);
    }

    /**
     * Update status using the new universal system
     * 
     * @deprecated Use updateFeedStatus() instead
     */
    public function updateStatus($newStatus, $notes = null, $metadata = [])
    {
        return $this->updateFeedStatus($newStatus, $notes, $metadata);
    }
}

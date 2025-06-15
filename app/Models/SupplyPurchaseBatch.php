<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Traits\HasSupplyStatusHistory;

class SupplyPurchaseBatch extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids, HasSupplyStatusHistory;

    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING = 'pending';
    const STATUS_IN_TRANSIT = 'in_transit';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_ARRIVED = 'arrived';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_COMPLETED = 'completed';

    const STATUS_LABELS = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_PENDING => 'Pending',
        self::STATUS_IN_TRANSIT => 'In Transit',
        self::STATUS_CONFIRMED => 'Confirmed',
        self::STATUS_ARRIVED => 'Arrived',
        self::STATUS_CANCELLED => 'Cancelled',
        self::STATUS_COMPLETED => 'Completed',
    ];

    protected $fillable = [
        'id',
        'invoice_number',
        'do_number',
        'farm_id',
        'coop_id',
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

    public function getStatusLabel()
    {
        return self::STATUS_LABELS[$this->status] ?? 'Unknown';
    }

    public function isDraft()
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isInTransit()
    {
        return $this->status === self::STATUS_IN_TRANSIT;
    }

    public function isArrived()
    {
        return $this->status === self::STATUS_ARRIVED;
    }

    public function isConfirmed()
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function isCancelled()
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isCompleted()
    {
        return $this->status === self::STATUS_COMPLETED;
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

    public function supplyPurchases()
    {
        return $this->hasMany(SupplyPurchase::class);
    }

    public function farm()
    {
        return $this->belongsTo(\App\Models\Farm::class, 'farm_id', 'id');
    }

    /**
     * Update status using the new SupplyStatusHistory system
     */
    public function updateStatus($newStatus, $notes = null, $metadata = [])
    {
        return $this->updateSupplyStatus($newStatus, $notes, $metadata);
    }

    /**
     * Override trait method to define which statuses require notes
     */
    protected function requiresNotesForSupplyStatus($status)
    {
        return in_array($status, [
            self::STATUS_CANCELLED,
            self::STATUS_COMPLETED
        ]);
    }

    /**
     * Override trait method to define available statuses
     */
    public function getAvailableSupplyStatuses()
    {
        return [
            self::STATUS_DRAFT,
            self::STATUS_PENDING,
            self::STATUS_IN_TRANSIT,
            self::STATUS_CONFIRMED,
            self::STATUS_ARRIVED,
            self::STATUS_CANCELLED,
            self::STATUS_COMPLETED,
        ];
    }
}

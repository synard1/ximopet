<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Traits\LivestockLockCheck;

class Livestock extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;
    use LivestockLockCheck;

    // Status Constants
    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_IN_TRANSIT = 'in_transit';
    const STATUS_IN_USE = 'in_use';
    const STATUS_ARRIVED = 'arrived';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_COMPLETED = 'completed';
    const STATUS_READY = 'ready'; // New status for when quantity stock is ready to be used
    const STATUS_ACTIVE = 'active'; // New status for when quantity is fully used

    // Status Labels
    const STATUS_LABELS = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_PENDING => 'Pending',
        self::STATUS_CONFIRMED => 'Confirmed',
        self::STATUS_IN_TRANSIT => 'In Transit',
        self::STATUS_IN_USE => 'In Use',
        self::STATUS_ARRIVED => 'Arrived',
        self::STATUS_CANCELLED => 'Cancelled',
        self::STATUS_COMPLETED => 'Completed',
        self::STATUS_READY => 'Ready',
        self::STATUS_ACTIVE => 'Active' // Label for the new status
    ];

    protected $table = 'livestocks';

    protected $fillable = [
        'farm_id',
        'coop_id',
        'name',
        'start_date',
        'end_date',
        'initial_quantity',
        'quantity_depletion',
        'quantity_sales',
        'quantity_mutated',
        'initial_weight',
        'price',
        'notes',
        'status',
        'data',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'data' => 'array'
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

    public function isInUse()
    {
        return $this->status === self::STATUS_IN_USE;
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

    public function isReady()
    {
        return $this->status === self::STATUS_READY;
    }

    public function isActive() // New method to check if the status is exhausted
    {
        return $this->status === self::STATUS_ACTIVE;
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

    public function farm()
    {
        return $this->belongsTo(Farm::class, 'farm_id', 'id');
    }

    public function coop()
    {
        return $this->belongsTo(Coop::class, 'coop_id', 'id');
    }

    public function kandang()
    {
        return $this->belongsTo(Coop::class, 'coop_id', 'id');
    }

    public function batches()
    {
        return $this->hasMany(LivestockBatch::class);
    }

    public function standardWeight()
    {
        return $this->belongsTo(StandarBobot::class, 'standar_bobot_id', 'id');
    }

    public function livestockDepletion()
    {
        return $this->hasMany(LivestockDepletion::class, 'livestock_id', 'id');
    }

    public function recordings()
    {
        return $this->hasMany(Recording::class, 'livestock_id', 'id');
    }

    public function currentLivestock()
    {
        return $this->hasOne(CurrentLivestock::class, 'livestock_id', 'id');
    }

    public function livestockPurchaseItems()
    {
        return $this->hasMany(LivestockPurchaseItem::class, 'livestock_id', 'id');
    }

    public function isLocked()
    {
        return $this->status === 'locked';
    }

    // Helper method to get total current population
    public function getTotalPopulation()
    {
        return $this->batches()
            ->where('status', 'active')
            ->sum('populasi_awal');
    }

    // Helper method to get total current weight
    public function getTotalWeight()
    {
        return $this->batches()
            ->where('status', 'active')
            ->sum('berat_awal');
    }
}

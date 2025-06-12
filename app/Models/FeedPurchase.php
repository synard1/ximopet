<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class FeedPurchase extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

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
    const STATUS_EXHAUSTED = 'exhausted'; // New status for when quantity is fully used

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
        self::STATUS_EXHAUSTED => 'Exhausted' // Label for the new status
    ];

    protected $fillable = [
        'id',
        'livestock_id',
        'feed_purchase_batch_id',
        'feed_id',
        'unit_id',
        'quantity',
        'converted_unit',
        'converted_quantity',
        'price_per_unit',
        'price_per_converted_unit',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date' => 'date',
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

    public function isExhausted() // New method to check if the status is exhausted
    {
        return $this->status === self::STATUS_EXHAUSTED;
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

    public function livestock()
    {
        return $this->belongsTo(Livestock::class, 'livestock_id', 'id');
    }

    public function feedItem()
    {
        return $this->belongsTo(Feed::class, 'feed_id');
    }

    public function feed()
    {
        return $this->belongsTo(Feed::class, 'feed_id');
    }

    public function batch()
    {
        return $this->belongsTo(FeedPurchaseBatch::class, 'feed_purchase_batch_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id', 'id');
    }

    public function convertedUnit()
    {
        return $this->belongsTo(Unit::class, 'converted_unit', 'id');
    }

    // FeedPurchase.php
    public function feedStocks()
    {
        return $this->hasMany(FeedStock::class, 'feed_purchase_id');
    }

    public function calculateConvertedQuantity()
    {
        // Jika tidak ada converted_unit, return quantity apa adanya
        if (!$this->converted_unit || !$this->unit_id) {
            return (float) $this->quantity;
        }
        // Ambil rasio konversi dari unit ke converted_unit
        $conversion = \App\Models\UnitConversion::where('unit_id', $this->unit_id)
            ->where('conversion_unit_id', $this->converted_unit)
            ->first();
        if ($conversion && $conversion->conversion_value) {
            return (float) $this->quantity * (float) $conversion->conversion_value;
        }
        // Jika tidak ada data konversi, fallback ke quantity
        return (float) $this->quantity;
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($feedPurchase) {
            // Update CurrentFeed quantity when FeedPurchase is deleted
            $currentFeed = CurrentFeed::where('livestock_id', $feedPurchase->livestock_id)
                ->where('feed_id', $feedPurchase->feed_id)
                ->first();

            if ($currentFeed) {
                // Decrease the quantity based on the deleted feed purchase
                $currentFeed->quantity -= $feedPurchase->converted_quantity;
                $currentFeed->save();
            }
        });
    }
}

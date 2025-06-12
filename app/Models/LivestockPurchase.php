<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class LivestockPurchase extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes;

    // Status Constants
    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_IN_TRANSIT = 'in_transit';
    const STATUS_ARRIVED = 'arrived';
    const STATUS_IN_COOP = 'in_coop';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_COMPLETED = 'completed';

    // Status Labels
    const STATUS_LABELS = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_PENDING => 'Pending',
        self::STATUS_CONFIRMED => 'Confirmed',
        self::STATUS_IN_TRANSIT => 'In Transit',
        self::STATUS_ARRIVED => 'Arrived',
        self::STATUS_IN_COOP => 'In Coop',
        self::STATUS_CANCELLED => 'Cancelled',
        self::STATUS_COMPLETED => 'Completed'
    ];

    protected $fillable = [
        'id',
        'tanggal',
        'invoice_number',
        'supplier_id',
        'farm_id',
        'coop_id',
        'expedition_id',
        'expedition_fee',
        'data',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tanggal' => 'datetime',
        'expedition_fee' => 'decimal:2',
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

    public function isInCoop()
    {
        return $this->status === self::STATUS_IN_COOP;
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

    public function details()
    {
        return $this->hasMany(LivestockPurchaseItem::class, 'livestock_purchase_id', 'id');
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class, 'farm_id');
    }

    public function coop()
    {
        return $this->belongsTo(Coop::class, 'coop_id');
    }

    public function livestocks()
    {
        return $this->hasMany(Livestock::class, 'purchase_id', 'id');
    }

    public function livestockBatches()
    {
        return $this->hasMany(LivestockBatch::class, 'source_id', 'id');
    }

    public function supplier()
    {
        return $this->belongsTo(Partner::class, 'supplier_id');
    }

    public function expedition()
    {
        return $this->belongsTo(Partner::class, 'expedition_id');
    }

    public function statusHistories()
    {
        return $this->hasMany(LivestockPurchaseStatusHistory::class);
    }

    public function updateStatus($newStatus, $notes = null, $metadata = [])
    {
        $oldStatus = $this->status;

        // Validasi notes wajib untuk status tertentu
        if (in_array($newStatus, [self::STATUS_CANCELLED, self::STATUS_COMPLETED]) && empty($notes)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'notes' => 'Catatan wajib diisi untuk status ' . $newStatus . '.'
            ]);
        }

        // Ambil IP address user
        $ip = request()->ip();
        $userId = auth()->id();

        // Gabungkan metadata tambahan
        $metadata = array_merge($metadata, [
            'ip_address' => $ip,
            'user_id' => $userId,
            'user_agent' => request()->header('User-Agent'),
            'updated_at' => now()->toDateTimeString(),
        ]);

        // Update status
        $this->update([
            'status' => $newStatus,
            'updated_by' => $userId
        ]);

        // Create status history
        $this->statusHistories()->create([
            'status_from' => $oldStatus,
            'status_to' => $newStatus,
            'notes' => $notes,
            'metadata' => $metadata,
            'created_by' => $userId,
            'updated_by' => $userId
        ]);

        return $this;
    }

    public function getLatestStatusHistory()
    {
        return $this->statusHistories()
            ->latest()
            ->first();
    }

    public function getStatusHistory()
    {
        return $this->statusHistories()
            ->orderBy('created_at', 'desc')
            ->get();
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
}

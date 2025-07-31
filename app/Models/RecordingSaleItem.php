<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecordingSaleItem extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'recording_sale_items';

    protected $fillable = [
        'recording_sale_id',
        'livestock_id',
        'livestock_batch_id',
        'quantity',
        'weight',
        'price_per_unit',
        'amount',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'weight' => 'decimal:2',
        'price_per_unit' => 'decimal:2',
        'amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    /**
     * Relationship: Belongs to recording sale (header)
     */
    public function recordingSale(): BelongsTo
    {
        return $this->belongsTo(RecordingSale::class, 'recording_sale_id');
    }

    /**
     * Alias for recordingSale relationship (for compatibility)
     */
    public function header(): BelongsTo
    {
        return $this->recordingSale();
    }

    /**
     * Relationship: Belongs to livestock batch
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(LivestockBatch::class, 'livestock_batch_id');
    }

    /**
     * Relationship: Belongs to livestock
     */
    public function livestock(): BelongsTo
    {
        return $this->belongsTo(Livestock::class, 'livestock_id');
    }

    /**
     * Relationship: Created by user
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relationship: Updated by user
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Calculate amount based on quantity and price_per_unit
     */
    public function calculateAmount(): void
    {
        $this->amount = $this->quantity * $this->price_per_unit;
    }

    /**
     * Calculate average weight per unit
     */
    public function getWeightPerUnit(): float
    {
        return $this->quantity > 0 ? $this->weight / $this->quantity : 0;
    }

    /**
     * Boot method to auto-calculate amount
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            // Auto-calculate amount if not provided
            if ($item->quantity && $item->price_per_unit && !$item->amount) {
                $item->calculateAmount();
            }
        });
    }

    /**
     * Scope: Filter by batch
     */
    public function scopeByBatch($query, string $batchId)
    {
        return $query->where('livestock_batch_id', $batchId);
    }

    /**
     * Scope: Filter by recording sale
     */
    public function scopeByRecordingSale($query, string $recordingSaleId)
    {
        return $query->where('recording_sale_id', $recordingSaleId);
    }

    /**
     * Get formatted display for UI
     */
    public function getDisplayInfo(): array
    {
        return [
            'batch_name' => $this->batch?->name ?? 'Unknown Batch',
            'quantity' => $this->quantity,
            'weight' => number_format($this->weight, 2),
            'weight_per_unit' => number_format($this->getWeightPerUnit(), 2),
            'price_per_unit' => number_format($this->price_per_unit, 0),
            'amount' => number_format($this->amount, 0),
        ];
    }
}

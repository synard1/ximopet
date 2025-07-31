<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecordingSale extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'company_id',
        'livestock_id',
        'recording_id',
        'livestock_batch_id',
        'date',
        'quantity',
        'weight',
        'price',
        'total_quantity',
        'total_weight',
        'total_amount',
        'is_header',
        'batch_count',
        'status',
        'data',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date' => 'date',
        'price' => 'float',
        'total_quantity' => 'integer',
        'total_weight' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'is_header' => 'boolean',
        'batch_count' => 'integer',
        'metadata' => 'array',
        'data' => 'array',
    ];

    // Status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    // Status labels
    const STATUS_LABELS = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_PENDING => 'Pending',
        self::STATUS_CONFIRMED => 'Confirmed',
        self::STATUS_COMPLETED => 'Completed',
        self::STATUS_CANCELLED => 'Cancelled',
    ];

    /**
     * Relationship: Has many sale items (for header records)
     */
    public function items(): HasMany
    {
        return $this->hasMany(RecordingSaleItem::class, 'recording_sale_id');
    }

    /**
     * Relationship: Belongs to recording
     */
    public function recording(): BelongsTo
    {
        return $this->belongsTo(Recording::class, 'recording_id');
    }

    /**
     * Relationship: Belongs to livestock
     */
    public function livestock(): BelongsTo
    {
        return $this->belongsTo(Livestock::class, 'livestock_id');
    }

    /**
     * Relationship: Belongs to livestock batch (for single batch records)
     */
    public function livestockBatch(): BelongsTo
    {
        return $this->belongsTo(LivestockBatch::class, 'livestock_batch_id');
    }

    /**
     * Relationship: Belongs to company
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
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
     * Helper methods for status
     */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Check if this is a header record (multiple batches)
     */
    public function isHeader(): bool
    {
        return $this->is_header === true;
    }

    /**
     * Check if this is a legacy single record
     */
    public function isLegacySingle(): bool
    {
        return $this->is_header === false;
    }

    /**
     * Calculate totals from items (for header records)
     */
    public function calculateTotals(): void
    {
        if ($this->isHeader()) {
            $this->total_quantity = $this->items()->sum('quantity');
            $this->total_weight = $this->items()->sum('weight');
            $this->total_amount = $this->items()->sum('amount');
            $this->batch_count = $this->items()->count();
        } else {
            // For legacy single records, copy to total fields
            $this->total_quantity = $this->quantity;
            $this->total_weight = $this->weight;
            $this->total_amount = $this->quantity * $this->price;
            $this->batch_count = 1;
        }
    }

    /**
     * Get batch breakdown from items (for header records) or data column (legacy)
     */
    public function getBatchBreakdown(): array
    {
        if ($this->isHeader()) {
            return $this->items()->with('batch')->get()->map(function ($item) {
                return [
                    'batch_id' => $item->livestock_batch_id,
                    'batch_name' => $item->batch?->name ?? 'Unknown Batch',
                    'quantity' => $item->quantity,
                    'weight' => $item->weight,
                    'price_per_unit' => $item->price_per_unit,
                    'amount' => $item->amount,
                    'metadata' => $item->metadata,
                ];
            })->toArray();
        } else {
            // Legacy: get from data column
            return $this->data['batch_breakdown'] ?? [
                [
                    'batch_id' => $this->livestock_batch_id,
                    'batch_name' => $this->livestockBatch?->name ?? 'Main Batch',
                    'quantity' => $this->quantity,
                    'weight' => $this->weight,
                    'price_per_unit' => $this->price,
                    'amount' => $this->quantity * $this->price,
                ]
            ];
        }
    }

    /**
     * Get total values (works for both header and legacy records)
     */
    public function getTotalQuantity(): int
    {
        return $this->isHeader() ? $this->total_quantity : $this->quantity;
    }

    public function getTotalWeight(): float
    {
        return $this->isHeader() ? $this->total_weight : $this->weight;
    }

    public function getTotalAmount(): float
    {
        return $this->isHeader() ? $this->total_amount : ($this->quantity * $this->price);
    }

    /**
     * Scope: Header records only
     */
    public function scopeHeaders($query)
    {
        return $query->where('is_header', true);
    }

    /**
     * Scope: Legacy single records only
     */
    public function scopeLegacySingle($query)
    {
        return $query->where('is_header', false);
    }

    /**
     * Scope: Filter by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Filter by date range
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope: Filter by livestock
     */
    public function scopeByLivestock($query, string $livestockId)
    {
        return $query->where('livestock_id', $livestockId);
    }

    /**
     * Boot method to auto-calculate totals
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($sale) {
            // Skip auto-calculation if explicitly set by RecordingPersistenceService
            if (
                isset($sale->metadata['update_source']) &&
                $sale->metadata['update_source'] === 'RecordingPersistenceService::processSalesData'
            ) {
                return;
            }

            // Auto-calculate totals before saving
            $sale->calculateTotals();
        });
    }
}

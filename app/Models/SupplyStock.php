<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class SupplyStock extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'id',
        'livestock_id',
        'farm_id',
        'coop_id',
        'supply_id',
        'supply_purchase_id',
        'date',
        'source_type',
        'source_id',
        'quantity_in',
        'quantity_used',
        'quantity_mutated',
        'quantity_reserved',
        'quantity_available',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date' => 'date',
        'metadata' => 'array',
    ];

    public function farm()
    {
        return $this->belongsTo(Farm::class, 'farm_id', 'id');
    }

    public function supply()
    {
        return $this->belongsTo(Supply::class, 'supply_id', 'id');
    }

    // FeedStock.php
    public function feed()
    {
        return $this->belongsTo(Item::class, 'feed_id', 'id');
    }

    public function livestock()
    {
        return $this->belongsTo(Livestock::class, 'livestock_id', 'id');
    }

    public function supplyPurchase()
    {
        return $this->belongsTo(SupplyPurchase::class, 'supply_purchase_id', 'id');
    }

    public function supplyPurchaseDetail()
    {
        return $this->belongsTo(SupplyPurchase::class, 'supply_purchase_id', 'id');
    }

    public function supplyUsageDetails()
    {
        return $this->hasMany(SupplyUsageDetail::class);
    }

    public function mutationDetails()
    {
        return $this->hasMany(SupplyMutationItem::class);
    }

    public function incomingMutation()
    {
        return $this->hasOne(SupplyMutationItem::class, 'supply_stock_id')->with('mutation.fromLivestock');
    }

    /**
     * Recalculate the quantity_used based on related SupplyUsageDetail records.
     */
    public function recalculateQuantityUsed(): void
    {
        // Explicitly query SupplyUsageDetail within the transaction for this stock_id
        $totalUsed = \App\Models\SupplyUsageDetail::where('supply_stock_id', $this->id)->sum('converted_quantity');
        $this->update(['quantity_used' => $totalUsed]);
    }

    /**
     * Recalculate the quantity_mutated based on related SupplyMutationItem records.
     */
    public function recalculateQuantityMutated(): void
    {
        // Assuming SupplyMutationItem quantity is stored in the same unit as SupplyStock
        $totalMutated = $this->mutationDetails()->sum('quantity');
        $this->update(['quantity_mutated' => $totalMutated]);
    }

    /**
     * Update quantity_available secara otomatis
     */
    public function updateAvailableQuantity(): void
    {
        $this->quantity_available = $this->quantity_in - $this->quantity_used - $this->quantity_mutated - $this->quantity_reserved;
        $this->save();
    }

    /**
     * Get metadata summary for display
     */
    public function getMetadataSummaryAttribute(): array
    {
        $metadataService = app(\App\Services\SupplyMetadataService::class);
        return $metadataService->getMetadataSummary($this->metadata ?? []);
    }

    /**
     * Get batch code from metadata
     */
    public function getBatchCodeAttribute(): string
    {
        return $this->metadata['batch_code'] ?? 'N/A';
    }

    /**
     * Get source type from metadata
     */
    public function getSourceTypeAttribute(): string
    {
        return $this->metadata['source_type'] ?? 'unknown';
    }

    /**
     * Get approval status from metadata
     */
    public function getApprovalStatusAttribute(): string
    {
        $approval = $this->metadata['approval'] ?? [];

        if (!empty($approval['approved_by'])) {
            return 'approved';
        }

        if (!empty($approval['verification_required'])) {
            return 'pending_verification';
        }

        return 'pending_approval';
    }

    /**
     * Get quality status from metadata
     */
    public function getQualityStatusAttribute(): string
    {
        $quality = $this->metadata['quality_check'] ?? [];

        if (isset($quality['passed'])) {
            return $quality['passed'] ? 'passed' : 'failed';
        }

        return 'not_checked';
    }

    /**
     * Get last activity from metadata history
     */
    public function getLastActivityAttribute(): string
    {
        $history = $this->metadata['history'] ?? [];
        return end($history)['date'] ?? 'N/A';
    }

    /**
     * Get total activities count from metadata history
     */
    public function getTotalActivitiesAttribute(): int
    {
        $history = $this->metadata['history'] ?? [];
        return count($history);
    }

    /**
     * Scope for purchase records
     */
    public function scopePurchase($query)
    {
        return $query->where('source_type', 'purchase');
    }

    /**
     * Scope for mutation records
     */
    public function scopeMutation($query)
    {
        return $query->where('source_type', 'mutation');
    }

    /**
     * Scope for available stock (quantity_available > 0)
     */
    public function scopeAvailable($query)
    {
        return $query->where('quantity_available', '>', 0);
    }

    /**
     * Scope for FIFO order
     */
    public function scopeFifo($query)
    {
        return $query->orderBy('date', 'asc')->orderBy('id', 'asc');
    }
}

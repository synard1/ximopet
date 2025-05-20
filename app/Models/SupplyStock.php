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
        'supply_id',
        'supply_purchase_id',
        'date',
        'source_type',
        'source_id',
        'quantity_in',
        'quantity_used',
        'quantity_mutated',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date' => 'date',
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
        $totalUsed = \App\Models\SupplyUsageDetail::where('supply_stock_id', $this->id)->sum('quantity_taken');
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
}

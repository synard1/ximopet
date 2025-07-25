<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class CurrentLivestock extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'id',
        'livestock_id',
        'farm_id',
        'coop_id',
        'quantity',
        'weight_total',
        'weight_avg',
        'data',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function livestock()
    {
        return $this->belongsTo(Livestock::class, 'livestock_id');
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class, 'farm_id');
    }

    public function coop()
    {
        return $this->belongsTo(Coop::class, 'coop_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }

    public function inventoryLocation()
    {
        return $this->belongsTo(InventoryLocation::class, 'location_id', 'id');
    }

    /**
     * Sync quantity with total available quantity from batches
     * 
     * @return bool
     */
    public function syncQuantityFromBatches(): bool
    {
        $livestock = $this->livestock;
        if (!$livestock) {
            return false;
        }

        $totalAvailable = $livestock->getTotalAvailableQuantity();

        if ($this->quantity !== $totalAvailable) {
            $this->quantity = $totalAvailable;
            return $this->save();
        }

        return true;
    }

    /**
     * Get quantity breakdown from batches
     * 
     * @return array
     */
    public function getQuantityBreakdownFromBatches(): array
    {
        $livestock = $this->livestock;
        if (!$livestock) {
            return [];
        }

        return $livestock->getOverallQuantityBreakdown();
    }

    /**
     * Check if current livestock has available quantity
     * 
     * @return bool
     */
    public function hasAvailableQuantity(): bool
    {
        return $this->quantity > 0;
    }

    /**
     * Get availability percentage
     * 
     * @return float
     */
    public function getAvailabilityPercentage(): float
    {
        $livestock = $this->livestock;
        if (!$livestock) {
            return 0;
        }

        return $livestock->getOverallAvailabilityPercentage();
    }

    /**
     * Get availability status
     * 
     * @return string
     */
    public function getAvailabilityStatus(): string
    {
        $livestock = $this->livestock;
        if (!$livestock) {
            return 'unknown';
        }

        return $livestock->getOverallAvailabilityStatus();
    }
}

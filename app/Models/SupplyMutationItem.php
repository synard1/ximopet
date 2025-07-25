<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class SupplyMutationItem extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'id',
        'company_id',
        'supply_mutation_id',
        'supply_stock_id',
        'supply_id',
        'quantity',
        'converted_quantity',
        'unit_id',
        'converted_unit_id',
        'expiry_date',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'converted_quantity' => 'decimal:2',
        'expiry_date' => 'date',
    ];

    /**
     * Get the parent supply mutation
     */
    public function supplyMutation()
    {
        return $this->belongsTo(SupplyMutation::class, 'supply_mutation_id', 'id');
    }

    /**
     * Get the mutation (alias for supplyMutation)
     */
    public function mutation()
    {
        return $this->belongsTo(SupplyMutation::class, 'supply_mutation_id', 'id');
    }

    /**
     * Get the supply stock
     */
    public function supplyStock()
    {
        return $this->belongsTo(SupplyStock::class, 'supply_stock_id', 'id');
    }

    /**
     * Get the supply through supply stock
     */
    public function supply()
    {
        return $this->hasOneThrough(
            Supply::class,
            SupplyStock::class,
            'id', // Foreign key on supply_stocks table
            'id', // Foreign key on supplies table
            'supply_stock_id', // Local key on supply_mutation_items table
            'supply_id' // Local key on supply_stocks table
        );
    }

    /**
     * Get the unit
     */
    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id', 'id');
    }

    /**
     * Get the converted unit
     */
    public function convertedUnit()
    {
        return $this->belongsTo(Unit::class, 'converted_unit_id', 'id');
    }

    /**
     * Get the creator user
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the updater user
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get formatted quantity with unit
     */
    public function getFormattedQuantityAttribute(): string
    {
        $unitName = $this->unit->name ?? 'Unit';
        return number_format($this->quantity, 2) . ' ' . $unitName;
    }

    /**
     * Get formatted converted quantity with unit
     */
    public function getFormattedConvertedQuantityAttribute(): string
    {
        $unitName = $this->convertedUnit->name ?? 'Unit';
        return number_format($this->converted_quantity, 2) . ' ' . $unitName;
    }

    /**
     * Check if item has expired
     */
    public function getIsExpiredAttribute(): bool
    {
        if (!$this->expiry_date) {
            return false;
        }
        return $this->expiry_date->isPast();
    }

    /**
     * Get days until expiry
     */
    public function getDaysUntilExpiryAttribute(): ?int
    {
        if (!$this->expiry_date) {
            return null;
        }
        return now()->diffInDays($this->expiry_date, false);
    }

    /**
     * Get item summary for display
     */
    public function getSummaryAttribute(): array
    {
        return [
            'id' => $this->id,
            'supply_name' => $this->supply->name ?? 'Unknown',
            'quantity' => $this->formatted_quantity,
            'converted_quantity' => $this->formatted_converted_quantity,
            'unit' => $this->unit->name ?? 'Unit',
            'converted_unit' => $this->convertedUnit->name ?? 'Unit',
            'expiry_date' => $this->expiry_date,
            'is_expired' => $this->is_expired,
            'days_until_expiry' => $this->days_until_expiry,
        ];
    }
}

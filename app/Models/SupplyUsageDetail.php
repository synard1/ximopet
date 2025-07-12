<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplyUsageDetail extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'supply_usage_id',
        'supply_stock_id',
        'supply_id',
        'quantity_taken',
        'unit_id',
        'converted_unit_id',
        'converted_quantity',
        'price_per_unit',
        'price_per_converted_unit',
        'total_price',
        'notes',
        'batch_number',
        'expiry_date',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'quantity_taken' => 'decimal:2',
        'converted_quantity' => 'decimal:2',
        'price_per_unit' => 'decimal:2',
        'price_per_converted_unit' => 'decimal:2',
        'total_price' => 'decimal:2',
        'expiry_date' => 'date',
    ];

    public function supplyUsage(): BelongsTo
    {
        return $this->belongsTo(SupplyUsage::class);
    }

    public function supplyStock(): BelongsTo
    {
        return $this->belongsTo(SupplyStock::class);
    }

    public function supply(): BelongsTo
    {
        return $this->belongsTo(Supply::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function convertedUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'converted_unit_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function setPricePerUnitAttribute($value)
    {
        $this->attributes['price_per_unit'] = ($value === '' || $value === null) ? null : $value;
    }
    public function setPricePerConvertedUnitAttribute($value)
    {
        $this->attributes['price_per_converted_unit'] = ($value === '' || $value === null) ? null : $value;
    }
    public function setTotalPriceAttribute($value)
    {
        $this->attributes['total_price'] = ($value === '' || $value === null) ? null : $value;
    }
}

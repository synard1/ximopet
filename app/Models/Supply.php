<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Supply extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'id',
        'supply_category_id',
        'code',
        'name',
        'data',
        'description',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    protected function status(): Attribute
    {
        return Attribute::make(
            get: fn(string $value) => ucfirst($value),
            set: fn(string $value) => strtolower($value),
        );
    }

    public function conversionUnits()
    {
        return $this->hasMany(UnitConversion::class, 'item_id');
    }

    public function supplyCategory()
    {
        return $this->belongsTo(SupplyCategory::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function supplyPurchase()
    {
        return $this->hasMany(SupplyPurchase::class, 'supply_id');
    }

    public function supplyUsage()
    {
        return $this->hasMany(SupplyUsage::class, 'supply_id');
    }

    public function supplyUsageDetail()
    {
        return $this->hasMany(SupplyUsageDetail::class, 'supply_id');
    }

    public function supplyStocks()
    {
        return $this->hasMany(SupplyStock::class, 'supply_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Unit extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'id',
        'type',
        'code',
        'symbol',
        'name',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [];

    public function supplyPurchase()
    {
        return $this->hasMany(SupplyPurchase::class, 'unit_id', 'id');
    }

    public function feedPurchase()
    {
        return $this->hasMany(FeedPurchase::class, 'unit_id', 'id');
    }

    public function unitConversion()
    {
        return $this->hasMany(UnitConversion::class, 'unit_id', 'id');
    }

    public function conversionUnit()
    {
        return $this->hasMany(UnitConversion::class, 'conversion_unit_id', 'id');
    }
}

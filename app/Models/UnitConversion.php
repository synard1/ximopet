<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class UnitConversion extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'id',
        'type',
        'item_id',
        'unit_id',
        'conversion_unit_id',
        'conversion_value',
        'default_purchase',
        'default_mutation',
        'default_sale',
        'smallest',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function conversionUnit()
    {
        return $this->belongsTo(Unit::class, 'conversion_unit_id', 'id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Supply extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'id',
        'supply_category_id',
        'code',
        'name',
        'unit',
        'unit_conversion',
        'conversion',
        'payload',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function category()
    {
        return $this->belongsTo(SupplyCategory::class);
    }
}

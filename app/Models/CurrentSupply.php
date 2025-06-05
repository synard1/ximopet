<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class CurrentSupply extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'id',
        'livestock_id',
        'farm_id',
        'coop_id',
        'unit_id',
        'item_id',
        'type',
        'quantity',
        'status',
        'created_by',
        'updated_by',
    ];

    public function supply()
    {
        return $this->belongsTo(Supply::class, 'item_id', 'id');
    }
}

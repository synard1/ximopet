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
        'supply_mutation_id',
        'supply_stock_id',
        'supply_id',
        'quantity',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    // FeedStock.php
    public function supplyMutation()
    {
        return $this->belongsTo(SupplyMutation::class,'supply_mutation_id','id');
    }

    public function mutation()
    {
        return $this->belongsTo(SupplyMutation::class,'supply_mutation_id','id');
    }

    public function supplyStock()
    {
        return $this->belongsTo(SupplyStock::class,'supply_stock_id','id');
    }
}

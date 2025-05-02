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
        'farm_id',
        'supply_id',
        'supply_purchase_id',
        'source_id',
        'quantity_in',
        'quantity_used',
        'quantity_mutated',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
    ];

    public function farm()
    {
        return $this->belongsTo(Farm::class,'farm_id','id');
    }

    public function supply()
    {
        return $this->belongsTo(Supply::class,'supply_id','id');
    }

    // FeedStock.php
    public function feed()
    {
        return $this->belongsTo(Item::class,'feed_id','id');
    }

    public function livestock()
    {
        return $this->belongsTo(Livestock::class,'livestock_id','id');
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

}

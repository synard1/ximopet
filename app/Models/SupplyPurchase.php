<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class SupplyPurchase extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'id',
        'farm_id',
        'supply_purchase_batch_id',
        'supply_id',
        'original_unit',
        'quantity',
        'price_per_unit',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
    ];

    public function farm()
    {
        return $this->belongsTo(Farm::class,'farm_id','id');
    }

    public function supplyItem()
    {
        return $this->belongsTo(Supply::class, 'supply_id');
    }

    public function feedItem()
    {
        return $this->belongsTo(Item::class, 'feed_id');
    }

    public function batch()
    {
        return $this->belongsTo(SupplyPurchaseBatch::class, 'supply_purchase_batch_id');
    }

    // FeedPurchase.php
    public function supplyStocks()
    {
        return $this->hasMany(SupplyStock::class, 'supply_purchase_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class FeedPurchase extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'id',
        'livestock_id',
        'feed_purchase_batch_id',
        'feed_id',
        'unit_id',
        'quantity',
        'converted_unit',
        'converted_quantity',
        'price_per_unit',
        'price_per_converted_unit',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function livestok()
    {
        return $this->belongsTo(Livestock::class, 'livestock_id', 'id');
    }

    public function feedItem()
    {
        return $this->belongsTo(Feed::class, 'feed_id');
    }

    public function feed()
    {
        return $this->belongsTo(Feed::class, 'feed_id');
    }

    public function batch()
    {
        return $this->belongsTo(FeedPurchaseBatch::class, 'feed_purchase_batch_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id', 'id');
    }

    public function convertedUnit()
    {
        return $this->belongsTo(Unit::class, 'converted_unit', 'id');
    }

    // FeedPurchase.php
    public function feedStocks()
    {
        return $this->hasMany(FeedStock::class, 'feed_purchase_id');
    }
}

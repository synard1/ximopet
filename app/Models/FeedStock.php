<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class FeedStock extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'id',
        'livestock_id',
        'feed_id',
        'feed_purchase_id',
        'date',
        'source_id',
        'amount',
        'used',
        'available',
        'quantity_in',
        'quantity_used',
        'quantity_mutated',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date' => 'date',

    ];

    // FeedStock.php
    public function feed()
    {
        return $this->belongsTo(Feed::class,'feed_id','id');
    }

    public function livestock()
    {
        return $this->belongsTo(Livestock::class,'livestock_id','id');
    }

    public function feedPurchase()
    {
        return $this->belongsTo(FeedPurchase::class, 'feed_purchase_id', 'id');
    }
    
    public function feedUsageDetails()
    {
        return $this->hasMany(FeedUsageDetail::class);
    }

    public function mutationDetails()
    {
        return $this->hasMany(FeedMutationItem::class);
    }

    public function incomingMutation()
    {
        return $this->hasOne(FeedMutationItem::class, 'feed_stock_id')->with('mutation.fromLivestock');
    }

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class FeedMutationItem extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'id',
        'feed_mutation_id',
        'feed_stock_id',
        'feed_id',
        'quantity',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    // FeedStock.php
    public function feedMutation()
    {
        return $this->belongsTo(FeedMutation::class,'feed_mutation_id','id');
    }

    public function mutation()
    {
        return $this->belongsTo(FeedMutation::class,'feed_mutation_id','id');
    }

    public function feedStock()
    {
        return $this->belongsTo(FeedStock::class,'feed_stock_id','id');
    }
}

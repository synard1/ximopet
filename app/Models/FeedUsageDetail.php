<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class FeedUsageDetail extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'id',
        'feed_usage_id',
        'feed_id',
        'feed_stock_id',
        'quantity_taken',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    // FeedStock.php
    public function feedStock()
    {
        return $this->belongsTo(FeedStock::class, 'feed_stock_id', 'id');
    }

    // FeedUsage.php
    public function feedUsage()
    {
        return $this->belongsTo(FeedUsage::class, 'feed_usage_id', 'id');
    }

    // FeedStock.php
    public function feed()
    {
        return $this->belongsTo(Feed::class, 'feed_id', 'id');
    }
}

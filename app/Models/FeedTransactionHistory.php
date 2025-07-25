<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class FeedTransactionHistory extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'id',
        'company_id',
        'feed_id',
        'livestock_id',
        'unit_id',
        'supplier_id',
        'batch_number',
        'expired_date',
        'date',
        'transaction_type',
        'transaction_number',
        'quantity_in',
        'quantity_out',
        'initial_stock',
        'quantity_available',
        'price',
        'description',
        'data',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date' => 'datetime',
        'expired_date' => 'date',
        'data' => 'array',
    ];

    // FeedStock.php
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function livestock()
    {
        return $this->belongsTo(Livestock::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Partner::class);
    }

    public function feed()
    {
        return $this->belongsTo(Feed::class);
    }

    public function feedPurchase()
    {
        return $this->belongsTo(FeedPurchase::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class FeedPurchaseBatch extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'id',
        'invoice_number',
        'do_number',
        'master_rekanan_id',
        'expedition_id',
        'date',
        'expedition_fee',
        'payload',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date' => 'date',
        'payload' => 'array',

    ];

    // FeedStock.php
    public function vendor()
    {
        return $this->belongsTo(Rekanan::class,'master_rekanan_id','id');
    }

    // FeedStock.php
    public function feedPurchases()
    {
        return $this->hasMany(FeedPurchase::class);
    }
}

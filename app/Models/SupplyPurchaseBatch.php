<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class SupplyPurchaseBatch extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'id',
        'invoice_number',
        'do_number',
        'supplier_id',
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
    public function supplier()
    {
        return $this->belongsTo(Partner::class,'supplier_id','id');
    }

    // FeedStock.php
    public function supplyPurchases()
    {
        return $this->hasMany(SupplyPurchase::class);
    }
}

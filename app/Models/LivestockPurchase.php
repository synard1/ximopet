<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class LivestockPurchase extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'id',
        'tanggal',
        'invoice_number',
        'vendor_id',
        'expedition_id',
        'expedition_fee',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tanggal' => 'datetime',
        'expedition_fee' => 'decimal:2',
    ];

    public function details()
    {
        return $this->hasMany(LivestockPurchaseItem::class, 'livestock_purchase_id', 'id');
    }

    public function livestocks()
    {
        return $this->hasMany(Livestock::class, 'purchase_id', 'id');
    }

    public function vendor()
    {
        return $this->belongsTo(Partner::class, 'vendor_id');
    }

    public function expedition()
    {
        return $this->belongsTo(Expedition::class, 'expedition_id');
    }
}

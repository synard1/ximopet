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
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tanggal' => 'datetime',
    ];

    public function livestockPurchaseItem(){
        return $this->hasMany(LivestockPurchaseItem::class, 'livestock_purchase_id', 'id');
    }

    public function livestocks()
    {
        return $this->hasMany(Livestock::class, 'purchase_id', 'id');
    }
}

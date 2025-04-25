<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class LivestockPurchaseItem extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'id',
        'livestock_purchase_id',
        'livestock_id',
        'jumlah',
        'harga_per_ekor',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tanggal' => 'datetime',
    ];

    public function livestockPurchase()
    {
        return $this->belongsTo(LivestockPurchase::class);
    }

    public function livestock()
    {
        return $this->belongsTo(Livestock::class);
    }
}

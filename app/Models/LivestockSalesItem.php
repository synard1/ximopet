<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class LivestockSalesItem extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'id',
        'livestock_sales_id',
        'livestock_id',
        'jumlah',
        'berat_total',
        'harga_satuan',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        // 'tanggal' => 'datetime',
    ];

    public function livestockSale()
    {
        return $this->belongsTo(LivestockSales::class, 'livestock_sales_id');
    }
}

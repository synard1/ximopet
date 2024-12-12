<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockMovement extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'stock_movements';

    protected $fillable = [
        'id',
        'transaksi_id',
        'parent_id',
        'item_id',
        'source_location_id',
        'destination_location_id',
        'movement_type',
        'tanggal',
        'batch_number',
        'expiry_date',
        'quantity',
        'satuan',
        'hpp',
        'status',
        'keterangan',
        'created_by',
        'updated_by',
    ];
}

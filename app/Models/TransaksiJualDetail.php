<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransaksiJualDetail extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'transaksi_jual_details';

    protected $fillable = [
        'id',
        'transaksi_jual_id',
        'rekanan_id',
        'farm_id',
        'kandang_id',
        'harga_beli',
        'harga_jual',
        'qty',
        'berat',
        'umur',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
    ];
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransaksiDetail extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'id',
        'transaksi_id',
        'parent_id',
        'item_id',
        'item_nama',
        'jenis',
        'jenis_barang',
        'tanggal',
        'rekanan_id',
        'farm_id',
        'kandang_id',
        'harga',
        'qty',
        'terpakai',
        'sisa',
        'sub_total',
        'periode',
        'status',
        'user_id',
    ];

    protected $casts = [
        'tanggal' => 'datetime',
    ];
}

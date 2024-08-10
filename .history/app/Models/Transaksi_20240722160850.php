<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaksi extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'id',
        'parent_id',
        'jenis',
        'jenis_barang',
        'faktur',
        'tanggal',
        'rekanan_id',
        'farm_id',
        'kandang_id',
        'rekanan_nama',
        'harga',
        'jumlah',
        'sub_total',
        'periode',
        'payload',
        'status',
        'user_id',
    ];

    protected $casts = [
        'payload' => 'array',
        'tanggal' => 'datetime',
    ];

    public function rekanans()
    {
        return $this->belongsTo(Rekanan::class);
    }
}

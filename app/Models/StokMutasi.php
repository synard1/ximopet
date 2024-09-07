<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class StokMutasi extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'stok_mutasis';

    protected $fillable = [
        'id',
        'transaksidet_id',
        'jenis',
        'jenis_barang',
        'tanggal',
        'rekanan_id',
        'farm_id',
        'kandang_id',
        'rekanan_nama',
        'kode',
        'item_id',
        'item_nama',
        'harga',
        'qty',
        'terpakai',
        'sisa',
        'satuan_besar',
        'satuan_kecil',
        'konversi',
        'status',
        'user_id',
    ];

    protected $casts = [
        'tanggal' => 'datetime',
    ];

    public function rekanans()
    {
        return $this->belongsTo(Rekanan::class, 'rekanan_id','id');
    }

    public function farms()
    {
        return $this->belongsTo(Farm::class, 'farm_id','id');
    }

    public function kandangs()
    {
        return $this->belongsTo(Kandang::class, 'kandang_id','id');
    }

    public function items()
    {
        return $this->belongsTo(Stok::class, 'item_id','id');
    }

    // public function transaksis()
    // {
    //     return $this->belongsTo(Transaksi::class, 'transaksi_id','id');
    // }
}

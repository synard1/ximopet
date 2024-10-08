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
        'jenis',
        'faktur',
        'tanggal',
        'rekanan_id',
        'farm_id',
        'kandang_id',
        'harga',
        'total_qty',
        'sub_total',
        'terpakai',
        'sisa',
        'periode',
        'status',
        'user_id',
    ];

    protected $casts = [
        'payload' => 'array',
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

    public function transaksiDetail()
    {
        return $this->hasMany(TransaksiDetail::class);
    }

    // public function transaksiDetails()
    // {
    //     return $this->hasMany(TransaksiDetail::class);
    // }
}

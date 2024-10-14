<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class StokHistory extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'stok_histories';

    protected $fillable = [
        'id',
        'transaksi_id',
        // 'transaksi_detail_id',
        'farm_id',
        'kandang_id',
        'tanggal',
        'jenis',
        'item_id',
        'qty',
        'stok_awal',
        'stok_akhir',
        'status',
        'keterangan',
        'user_id',
        'created_by',
        'updated_by',
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
        return $this->belongsTo(Item::class, 'item_id','id');
    }

    public function transaksi()
    {
        return $this->belongsTo(Transaksi::class); 
    }

    public function transaksiDetail()
    {
        return $this->belongsTo(TransaksiDetail::class, 'transaksi_detail_id','id');
    }
}

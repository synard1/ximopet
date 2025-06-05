<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class StokMutasi extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'histori_stok';

    protected $fillable = [
        'id',
        'transaksi_detail_id',
        'farm_id',
        'coop_id',
        'tanggal',
        'jenis',
        'item_id',
        'qty',
        'stok_awal',
        'stok_masuk',
        'stok_keluar',
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
        return $this->belongsTo(Rekanan::class, 'rekanan_id', 'id');
    }

    public function farms()
    {
        return $this->belongsTo(Farm::class, 'farm_id', 'id');
    }

    public function kandangs()
    {
        return $this->belongsTo(Coop::class, 'coop_id', 'id');
    }

    public function items()
    {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }

    public function stokTransaksi()
    {
        return $this->belongsTo(StokTransaksi::class);
    }

    public function TransaksiDetail()
    {
        return $this->belongsTo(TransaksiDetail::class, 'transaksi_detail_id', 'id');
    }
}

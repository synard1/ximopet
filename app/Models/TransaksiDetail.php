<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransaksiDetail extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'id',
        'transaksi_id',
        'parent_id',
        'item_id',
        'item_name',
        'jenis',
        'jenis_barang',
        'tanggal',
        'harga',
        'qty',
        'berat',
        'terpakai',
        'sisa',
        'satuan_besar',
        'satuan_kecil',
        'sub_total',
        'konversi',
        'status',
        'user_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tanggal' => 'datetime',
    ];

    public function items()
    {
        return $this->belongsTo(Item::class, 'item_id','id');
    }

    public function stokHistory()
    {
        return $this->hasOne(StokHistory::class);
    }

    public function transaksi()
    {
        return $this->belongsTo(Transaksi::class, 'transaksi_id','id');
    }
}

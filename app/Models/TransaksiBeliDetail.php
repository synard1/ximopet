<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransaksiBeliDetail extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'transaksi_beli_details';

    protected $fillable = [
        'id',
        'transaksi_id',
        'parent_id',
        'jenis',
        'jenis_barang',
        'tanggal',
        'item_id',
        'item_name',
        'qty',
        'berat',
        'harga',
        'sub_total',
        'terpakai',
        'sisa',
        'satuan_besar',
        'satuan_kecil',
        'konversi',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tanggal' => 'datetime',
    ];

    public function transaksiBeli()
    {
        return $this->belongsTo(TransaksiBeli::class, 'transaksi_id','id');
    }

    public function items()
    {
        return $this->belongsTo(Item::class, 'item_id','id');
    }
}
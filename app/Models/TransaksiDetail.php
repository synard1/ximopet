<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransaksiDetail extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * This is a detail model that inherits company_id from Transaksi parent
     * No need to handle company_id separately
     */
    protected $requiresCompanyId = false;

    /**
     * Additional fillable fields specific to this model
     */
    protected $additionalFillable = [
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
    ];

    protected $casts = [
        'tanggal' => 'datetime',
    ];

    public function items()
    {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }

    public function stokHistory()
    {
        return $this->hasOne(\App\Models\StockHistory::class);
    }

    public function transaksi()
    {
        return $this->belongsTo(Transaksi::class, 'transaksi_id', 'id');
    }
}

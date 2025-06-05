<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransaksiBeli extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'transaksi_beli';

    protected $fillable = [
        'id',
        'faktur',
        'jenis',
        'tanggal',
        'rekanan_id',
        'batch_number',
        'farm_id',
        'silo_id',
        'coop_id',
        'total_qty',
        'total_berat',
        'harga',
        'sub_total',
        'terpakai',
        'sisa',
        'ternak_id',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tanggal' => 'datetime',
    ];

    public function itemCategory()
    {
        return $this->belongsTo(ItemCategory::class, 'category_id', 'id');
    }

    public function transaksiDetails()
    {
        return $this->hasMany(TransaksiBeliDetail::class, 'transaksi_id', 'id');
    }

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

    public function ternak()
    {
        return $this->hasOne(Ternak::class, 'transaksi_id', 'id');
    }
}

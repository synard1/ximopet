<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaksi extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'id',
        'jenis',
        'faktur',
        'tanggal',
        'rekanan_id',
        'farm_id',
        'coop_id',
        'harga',
        'total_qty',
        'total_berat',
        'sub_total',
        'terpakai',
        'sisa',
        'status',
        'notes',
        'user_id',
        'kelompok_ternak_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'payload' => 'array',
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

    public function transaksiDetail()
    {
        return $this->hasMany(TransaksiDetail::class);
    }

    public function stokHistory()
    {
        return $this->hasMany(StokHistory::class);
    }

    public function kelompokTernak()
    {
        return $this->hasOne(KelompokTernak::class);
    }

    // public function transaksiDetails()
    // {
    //     return $this->hasMany(TransaksiDetail::class);
    // }
}

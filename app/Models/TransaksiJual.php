<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransaksiJual extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'transaksi_jual';

    protected $fillable = [
        'id',
        'faktur',
        'tanggal',
        'transaksi_beli_id',
        'kelompok_ternak_id',
        'ternak_jual_id',
        'jumlah',
        'harga',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tanggal' => 'datetime',
    ];

    public function detail()
    {
        return $this->hasOne('App\Models\TransaksiJualDetail', 'transaksi_jual_id');
    }

    public function kelompokTernak()
    {
        return $this->belongsTo(KelompokTernak::class);
    }

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
}

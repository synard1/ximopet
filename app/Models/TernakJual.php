<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class TernakJual extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'ternak_jual';

    protected $fillable = [
        'id',
        'kelompok_ternak_id',
        'transaksi_id',
        'transaksi_jual_id',
        'tipe_transaksi',
        'tanggal',
        'quantity',
        'total_berat',
        'umur',
        'status',
        'keterangan',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tanggal' => 'datetime',
    ];

    public function transaksi()
    {
        return $this->belongsTo(Transaksi::class, 'transaksi_id', 'id');
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class, 'farm_id', 'id');
    }

    public function kandang()
    {
        return $this->belongsTo(Coop::class, 'coop_id', 'id');
    }

    public function historyTernaks()
    {
        return $this->hasMany(TernakHistory::class, 'kelompok_ternak_id', 'id');
    }

    public function kelompokTernaks()
    {
        return $this->belongsTo(KelompokTernak::class, 'kelompok_ternak_id', 'id');
    }
}

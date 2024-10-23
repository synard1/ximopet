<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class KematianTernak extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'kematian_ternak';

    protected $fillable = [
        'id',
        'kelompok_ternak_id',
        'history_ternak_id',
        'tanggal',
        'farm_id',
        'kandang_id',
        'stok_awal',
        'quantity',
        'stok_akhir',
        'total_berat',
        'penyebab',
        'keterangan',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tanggal' => 'datetime',
    ];

    public function transaksi()
    {
        return $this->belongsTo(Transaksi::class, 'transaksi_id','id');
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class, 'farm_id', 'id');
    }

    public function kandang()
    {
        return $this->belongsTo(Kandang::class, 'kandang_id', 'id');
    }

    public function historyTernaks(){
        return $this->hasMany(TernakHistory::class, 'kelompok_ternak_id', 'id');
    }

    public function kelompokTernaks(){
        return $this->belongsTo(KelompokTernak::class, 'kelompok_ternak_id', 'id');
    }
    
}

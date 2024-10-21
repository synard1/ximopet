<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class TernakHistory extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'histori_ternak';

    protected $fillable = [
        'id',
        'transaksi_id',
        'kelompok_ternak_id',
        'parent_id',
        'farm_id',
        'kandang_id',
        'tanggal',
        'jenis',
        'perusahaan_nama',
        'hpp',
        'stok_awal',
        'stok_akhir',
        'stok_masuk',
        'stok_keluar',
        'total_berat',
        'status',
        'keterangan',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tanggal' => 'datetime',
    ];

    public function ternaks(){
        return $this->belongsTo(KelompokTernak::class, 'kelompok_ternak_id');
    }
}

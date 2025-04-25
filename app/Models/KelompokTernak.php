<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Traits\KelompokTernakLockCheck;


class KelompokTernak extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;
    use KelompokTernakLockCheck;


    protected $table = 'kelompok_ternak';

    protected $fillable = [
        'transaksi_id',
        'farm_id',
        'kandang_id',
        'standar_bobot_id',
        'name',
        'breed',
        'start_date',
        'populasi_awal',
        'berat_awal',
        'harga',
        'pic',
        'status',
        'data',
        'keterangan',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'data' => 'array',

    ];


    public function transaksiBeli()
    {
        return $this->belongsTo(TransaksiBeli::class, 'transaksi_id','id');
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class, 'farm_id', 'id');
    }

    public function kandang()
    {
        return $this->belongsTo(Kandang::class, 'kandang_id', 'id');
    }

    public function standarBobot()
    {
        return $this->belongsTo(StandarBobot::class, 'standar_bobot_id', 'id');
    }

    public function historyTernaks(){
        return $this->hasMany(TernakHistory::class, 'kelompok_ternak_id', 'id');
    }

    public function kematianTernak(){
        return $this->hasMany(KematianTernak::class, 'kelompok_ternak_id', 'id');
    }

    public function transaksiHarian()
    {
        return $this->hasMany(TransaksiHarian::class, 'kelompok_ternak_id','id');
    }

    public function ternakAfkir()
    {
        return $this->hasMany(TernakAfkir::class, 'kelompok_ternak_id','id');
    }

    public function penjualanTernaks()
    {
        return $this->hasMany(TernakJual::class, 'kelompok_ternak_id','id');
    }

    public function transaksiJuals()
    {
        return $this->hasMany(TransaksiJual::class, 'kelompok_ternak_id','id');
    }

    public function konsumsiPakan()
    {
        return $this->hasMany(KonsumsiPakan::class, 'kelompok_ternak_id','id');
    }

    public function transaksiHarians()
    {
        return $this->hasMany(TransaksiHarian::class);
    }

    public function isLocked()
    {
        return $this->status === 'Locked';
    }

    public function ternakDepletion(){
        return $this->hasMany(TernakDepletion::class, 'ternak_id', 'id');
    }
    
}

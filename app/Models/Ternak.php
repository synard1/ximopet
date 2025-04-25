<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Traits\TernakLockCheck;

class Ternak extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;
    use TernakLockCheck;


    protected $table = 'ternaks';

    protected $fillable = [
        'transaksi_id',
        'farm_id',
        'kandang_id',
        'standar_bobot_id',
        'name',
        'breed',
        'start_date',
        'end_date',
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
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function transaksi()
    {
        return $this->belongsTo(TransaksiBeli::class, 'transaksi_id','id');
    }

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

    public function kematianTernak(){
        return $this->hasMany(KematianTernak::class, 'kelompok_ternak_id', 'id');
    }

    public function ternakDepletion(){
        return $this->hasMany(TernakDepletion::class, 'ternak_id', 'id');
    }

    public function jenisTernak(){
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }

    public function isLocked()
    {
        return $this->status === 'Locked';
    }
    
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Traits\LivestockLockCheck;

class Livestock extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;
    use LivestockLockCheck;

    protected $table = 'livestocks';

    protected $fillable = [
        'transaksi_id',
        'farm_id',
        'kandang_id',
        'livestock_breed_id',
        'livestock_breed_standard_id',
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

    public function farm()
    {
        return $this->belongsTo(Farm::class, 'farm_id', 'id');
    }

    public function kandang()
    {
        return $this->belongsTo(Kandang::class, 'kandang_id', 'id');
    }

    public function standardWeight()
    {
        return $this->belongsTo(StandarBobot::class, 'standar_bobot_id', 'id');
    }

    public function livestockDepletion(){
        return $this->hasMany(LivestockDepletion::class, 'livestock_id', 'id');
    }

    public function recordings(){
        return $this->hasMany(Recording::class, 'livestock_id', 'id');
    }

    public function isLocked()
    {
        return $this->status === 'Locked';
    }
}

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
        'farm_id',
        'kandang_id',
        'name',
        'livestock_strain_id',
        'livestock_strain_standard_id',
        'livestock_strain_name',
        'start_date',
        'end_date',
        'populasi_awal',
        'quantity_depletion',
        'quantity_sales',
        'quantity_mutated',
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
        'data' => 'array'
    ];

    public function farm()
    {
        return $this->belongsTo(Farm::class, 'farm_id', 'id');
    }

    public function kandang()
    {
        return $this->belongsTo(Kandang::class, 'kandang_id', 'id');
    }

    public function batches()
    {
        return $this->hasMany(LivestockBatch::class);
    }

    public function standardWeight()
    {
        return $this->belongsTo(StandarBobot::class, 'standar_bobot_id', 'id');
    }

    public function livestockDepletion()
    {
        return $this->hasMany(LivestockDepletion::class, 'livestock_id', 'id');
    }

    public function recordings()
    {
        return $this->hasMany(Recording::class, 'livestock_id', 'id');
    }

    public function currentLivestock()
    {
        return $this->hasMany(CurrentLivestock::class, 'livestock_id', 'id');
    }

    public function isLocked()
    {
        return $this->status === 'Locked';
    }

    // Helper method to get total current population
    public function getTotalPopulation()
    {
        return $this->batches()
            ->where('status', 'active')
            ->sum('populasi_awal');
    }

    // Helper method to get total current weight
    public function getTotalWeight()
    {
        return $this->batches()
            ->where('status', 'active')
            ->sum('berat_awal');
    }
}

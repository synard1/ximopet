<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\BaseModel;

class Kandang extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'master_kandangs';

    protected $fillable = [
        'farm_id',
        'kode',
        'nama',
        'jumlah',
        'berat',
        'kapasitas',
        'status',
        'livestock_id',
        'created_by',
        'updated_by',
    ];

    public function farm()
    {
        return $this->belongsTo(Farm::class, 'farm_id');
    }

    public function livestock()
    {
        return $this->belongsTo(Livestock::class, 'livestock_id');
    }

    public function livestockBatches()
    {
        return $this->hasMany(LivestockBatch::class);
    }

    // Helper method to get current total population
    public function getCurrentPopulation()
    {
        return $this->livestockBatches()
            ->where('status', 'active')
            ->sum('populasi_awal');
    }

    // Helper method to get current total weight
    public function getCurrentWeight()
    {
        return $this->livestockBatches()
            ->where('status', 'active')
            ->sum('berat_awal');
    }

    // Helper method to check if kandang has available capacity
    public function hasAvailableCapacity($newBatchPopulation)
    {
        $currentPopulation = $this->getCurrentPopulation();
        return ($currentPopulation + $newBatchPopulation) <= $this->kapasitas;
    }
}

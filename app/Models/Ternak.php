<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Ternak extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'kelompok_ternak';

    protected $fillable = [
        'transaksi_id',
        'name',
        'breed',
        'start_date',
        'estimated_end_date',
        'initial_quantity',
        'current_quantity',
        'death_quantity',
        'slaughter_quantity',
        'sold_quantity',
        'remaining_quantity',
        'berat_beli',
        'berat_jual',
        'status',
        'farm_id',
        'kandang_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'estimated_end_date' => 'datetime',
    ];

    public function transaksi()
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

    public function jenisTernak(){
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }
    
}

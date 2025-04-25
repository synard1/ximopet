<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class CurrentTernak extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'current_ternaks';

    protected $fillable = [
        'id',
        'kelompok_ternak_id',
        'farm_id',
        'kandang_id',
        'quantity',
        'berat_total',
        'avg_berat',
        'umur',
        'status',
        'created_by',
        'updated_by',
    ];

    public function kelompokTernak(){
        return $this->belongsTo(KelompokTernak::class, 'kelompok_ternak_id', 'id');
    }

    public function ternak(){
        return $this->belongsTo(Ternak::class, 'kelompok_ternak_id', 'id');
    }
}

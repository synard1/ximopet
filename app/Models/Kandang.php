<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class Kandang extends Model
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
        'kelompok_ternak_id',
        'created_by',
        'updated_by',
    ];

    public function farms()
    {
        return $this->belongsTo(Farm::class,'farm_id');
    }

    public function kelompokTernak()
    {
        return $this->belongsTo(KelompokTernak::class,'kelompok_ternak_id');
    }
}

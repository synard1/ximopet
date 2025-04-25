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
        return $this->belongsTo(Farm::class,'farm_id');
    }

    public function livestock()
    {
        return $this->belongsTo(Livestock::class,'livestock_id');
    }
}

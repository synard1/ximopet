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

    protected $table = 'ternak_history';

    protected $fillable = [
        'id',
        'kelompok_ternak_id',
        'tanggal',
        'stok_awal',
        'stok_akhir',
        'ternak_afkir',
        'ternak_mati',
        'ternak_jual',
        'umur',
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

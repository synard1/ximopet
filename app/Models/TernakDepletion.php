<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class TernakDepletion extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'ternak_id',
        'tanggal_deplesi',
        'jumlah_deplesi',
        'jenis_deplesi',
        'alasan_deplesi',
        'keterangan',
        'data'
    ];

    protected $casts = [
        'tanggal_deplesi' => 'date',
        'data' => 'array'

    ];

    public function ternak()
    {
        return $this->belongsTo(Ternak::class);
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class, 'farm_id', 'id');
    }

    public function kandang()
    {
        return $this->belongsTo(Coop::class, 'coop_id', 'id');
    }
}

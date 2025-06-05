<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class LivestockDepletion extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'livestock_id',
        'recording_id',
        'tanggal',
        'jumlah',
        'jenis',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'data' => 'array'
    ];

    public function livestock()
    {
        return $this->belongsTo(Livestock::class);
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class, 'farm_id', 'id');
    }

    public function kandang()
    {
        return $this->belongsTo(Coop::class, 'coop_id', 'id');
    }

    public function recording()
    {
        return $this->belongsTo(Recording::class, 'recording_id', 'id');
    }
}

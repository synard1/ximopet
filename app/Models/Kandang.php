<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class Kandang extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'master_kandangs';

    protected $fillable = [
        'farm_id',
        'kode',
        'nama',
        'jumlah',
        'kapasitas',
        'status',
        'user_id',
    ];

    public function farms(): BelongsTo
    {
        return $this->belongsTo(Farm::class,'farm_id');
    }
}

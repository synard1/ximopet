<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class KonsumsiPakan extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'konsumsi_pakan';

    protected $fillable = [
        'id',
        'kelompok_ternak_id',
        'item_id',
        'quantity',
        'harga',
        'tanggal',
        'keterangan',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tanggal' => 'datetime',
    ];
}

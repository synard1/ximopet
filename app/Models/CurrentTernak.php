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
        'status',
        'created_by',
        'updated_by',
    ];
}

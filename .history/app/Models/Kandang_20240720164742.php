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
        'user_id',
        'kode',
        'nama',
        'jumlah',
        'kapasitas',
        'status',
    ];
}

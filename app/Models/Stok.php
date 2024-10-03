<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class Stok extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'stoks';

    protected $fillable = [
        'id',
        'jenis',
        'kode',
        'name',
        'satuan_besar',
        'satuan_kecil',
        'konversi',
        'status',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class Stok extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'master_stoks';

    protected $fillable = [
        'id',
        'jenis',
        'kode',
        'nama',
        'satuan_besar',
        'satuan_kecil',
        'konversi',
        'status',
    ];
}

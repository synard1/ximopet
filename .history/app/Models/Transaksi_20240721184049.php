<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaksi extends Model
{
    use HasFactory;

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

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaksi extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'id',
        'jenis',
        'faktur',
        'tanggal',
        'na',
        'satuan_besar',
        'satuan_kecil',
        'konversi',
        'status',
    ];

    protected $casts = [
        'payload' => 'array',
        'tanggal' => 'datetime',
    ];
}

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
        'rekanan_id',
        'rekanan_id',
        'rekanan_id',
        'rekanan_nama',
        'harga',
        'jumlah',
        'sub_total',
        'payload',
        'status',
    ];

    protected $casts = [
        'payload' => 'array',
        'tanggal' => 'datetime',
    ];
}

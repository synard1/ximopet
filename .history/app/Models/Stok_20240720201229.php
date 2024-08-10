<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class Stok extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'master_stocks';

    protected $fillable = [
        'id',
        'jenis',
        'kode',
        'nama',
        'email',
        'alamat',
        'telp',
        'pic',
        'telp_pic',
        'status',
    ];
}

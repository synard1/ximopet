<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;


class Rekanan extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'master_rekanan';

    protected $fillable = [
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

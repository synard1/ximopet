<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'items';

    protected $fillable = [
        'id',
        'kode',
        'jenis',
        'name',
        'satuan_besar',
        'satuan_kecil',
        'konversi',
        'status',
        'created_by',
        'updated_by',
    ];
}

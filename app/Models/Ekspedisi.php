<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ekspedisi extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'master_expeditions';

    protected $fillable = [
        'id',
        'kode',
        'nama',
        'alamat',
        'telp',
        'pic',
        'telp_pic',
        'keterangan',
        'status',
        'created_by',
        'updated_by',
    ];
}

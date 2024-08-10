<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;


class Farm extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'master_farms';

    protected $fillable = [
        'id',
        'kode',
        'nama',
        'alamat',
        'telp',
        'pic',
        'telp_pic',
        'status',
    ];

    public function kandangs()
    {
        return $this->hasMany(Kandang::class);
    }
}

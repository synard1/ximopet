<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class FarmOperator extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'master_farms';

    protected $fillable = [
        'id',
        'farm_id',
        'nama_farm',
        'nama_operator'
        'telp',
        'pic',
        'telp_pic',
        'status',
    ];

    public function farms()
    {
        return $this->hasMany(Farm::class);
    }
}

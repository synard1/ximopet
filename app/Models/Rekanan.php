<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\BaseModel;


class Rekanan extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'master_rekanans';

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
        'created_by',
        'updated_by',
    ];

    public function transaksiBelis(){
        return $this->hasMany(TransaksiBeli::class, 'rekanan_id','id');
    }

    public function transaksiHarians(){
        return $this->hasMany(TransaksiHarian::class, 'rekanan_id','id');
    }

    public function transaksiJuals(){
        return $this->hasMany(TransaksiJual::class, 'rekanan_id','id');
    }
}

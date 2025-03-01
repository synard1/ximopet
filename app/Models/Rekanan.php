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

    protected $table = 'master_rekanan';

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

    public function transaksi(){
        return $this->hasMany(Transaksi::class);
    }
}

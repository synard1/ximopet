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

    public function transaksiDetail()
    {
        return $this->hasMany(TransaksiDetail::class);
    }

    public function stokHistory()
    {
        return $this->hasMany(StokHistory::class,'item_id','id');
    }

//     public function farmOperator()
//    {
//        return $this->belongsToMany(User::class, 'farm_operators', 'item_id', 'user_id')
//                    ->withPivot('farm_id');
//    }
}

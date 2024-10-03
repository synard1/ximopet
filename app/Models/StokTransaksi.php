<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class StokTransaksi extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'stok_transaksis';

    protected $fillable = [
        'id',
        'jenis',
        'tanggal',
        'farm_id',
        'kandang_id',
        'qty',
        'terpakai',
        'sisa',
        'status',
        'user_id',
    ];

    protected $casts = [
        'tanggal' => 'datetime',
    ];

    public function farms()
    {
        return $this->belongsTo(Farm::class, 'farm_id','id');
    }

    public function kandangs()
    {
        return $this->belongsTo(Kandang::class, 'kandang_id','id');
    }

    public function stokMutasi()
    {
        return $this->hasMany(StokMutasi::class); 
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class StokTransaksi extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'transaksis';

    protected $fillable = [
        'id',
        'jenis',
        'tanggal',
        'farm_id',
        'coop_id',
        'total_qty',
        'terpakai',
        'sisa',
        'status',
        'user_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tanggal' => 'datetime',
    ];

    public function farms()
    {
        return $this->belongsTo(Farm::class, 'farm_id', 'id');
    }

    public function kandangs()
    {
        return $this->belongsTo(Coop::class, 'coop_id', 'id');
    }

    public function stokMutasi()
    {
        return $this->hasMany(StokMutasi::class);
    }

    public function transaksiDetail()
    {
        return $this->hasMany(TransaksiDetail::class);
    }

    public function kelompokTernak()
    {
        return $this->belongsTo(KelompokTernak::class, 'kelompok_ternak_id', 'id');
    }
}

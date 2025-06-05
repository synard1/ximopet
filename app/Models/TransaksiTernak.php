<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransaksiTernak extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'transaksi_ternak';

    protected $fillable = [
        'kelompok_ternak_id',
        'jenis_transaksi',
        'tanggal',
        'farm_id',
        'coop_id',
        'quantity',
        'berat_total',
        'berat_rata',
        'harga_satuan',
        'total_harga',
        'farm_tujuan_id',
        'kandang_tujuan_id',
        'pembeli_id',
        'status',
        'keterangan',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tanggal' => 'datetime',
    ];

    // Relationships
    public function kelompokTernak()
    {
        return $this->belongsTo(KelompokTernak::class);
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function kandang()
    {
        return $this->belongsTo(Coop::class);
    }

    public function farmTujuan()
    {
        return $this->belongsTo(Farm::class, 'farm_tujuan_id');
    }

    public function kandangTujuan()
    {
        return $this->belongsTo(Coop::class, 'kandang_tujuan_id');
    }

    public function pembeli()
    {
        return $this->belongsTo(Rekanan::class, 'pembeli_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}

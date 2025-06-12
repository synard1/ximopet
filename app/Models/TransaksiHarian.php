<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class TransaksiHarian extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'transaksi_harians';

    protected $fillable = [
        'tanggal',
        'kelompok_ternak_id',
        'farm_id',
        'coop_id',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $keyType = 'string';
    public $incrementing = false;

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function farm()
    {
        return $this->belongsTo('App\Models\Farm', 'farm_id');
    }

    public function kandang()
    {
        return $this->belongsTo(Coop::class, 'coop_id');
    }

    public function createdBy()
    {
        return $this->belongsTo('App\Models\User', 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo('App\Models\User', 'updated_by');
    }

    public function details()
    {
        return $this->hasMany(TransaksiHarianDetail::class, 'transaksi_id', 'id');
    }

    public function ternakAfkir()
    {
        return $this->hasMany(TernakAfkir::class, 'transaksi_id');
    }

    public function ternakJual()
    {
        return $this->hasMany(TernakJual::class, 'transaksi_id');
    }

    public function ternakMati()
    {
        return $this->hasMany(KematianTernak::class, 'transaksi_id');
    }

    public function rekanan()
    {
        return $this->belongsTo(Rekanan::class, 'rekanan_id');
    }

    public function kelompokTernak()
    {
        return $this->belongsTo(Ternak::class, 'kelompok_ternak_id', 'id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransaksiJualDetail extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'transaksi_jual_details';

    protected $fillable = [
        'id',
        'transaksi_jual_id',
        'rekanan_id',
        'farm_id',
        'coop_id',
        'harga_beli',
        'harga_jual',
        'qty',
        'berat',
        'umur',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [];

    // Define relationships
    public function transaksiJual()
    {
        return $this->belongsTo(TransaksiJual::class);
    }

    public function rekanan()
    {
        return $this->belongsTo(Rekanan::class);
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function kandang()
    {
        return $this->belongsTo(Coop::class);
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

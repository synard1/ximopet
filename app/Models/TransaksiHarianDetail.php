<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class TransaksiHarianDetail extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'transaksi_harian_details';

    protected $fillable = [
        'transaksi_id',
        'parent_id',
        'type',
        'item_id',
        'quantity',
        'total_berat',
        'harga',
        'notes',
        'payload',
        'created_by',
        'updated_by',
    ];

    protected $keyType = 'string';
    public $incrementing = false;
    protected $casts = [
        'payload' => 'array',
    ];

    public function transaksiHarian()
    {
        return $this->belongsTo('App\Models\TransaksiHarian', 'transaksi_id');
    }

    public function item()
    {
        return $this->belongsTo('App\Models\Item', 'item_id');
    }

    public function createdBy()
    {
        return $this->belongsTo('App\Models\User', 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo('App\Models\User', 'updated_by');
    }
}

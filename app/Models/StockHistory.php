<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockHistory extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'stock_histories';

    protected $fillable = [
        'id',
        'tanggal',
        'stock_id',
        'item_id',
        'location_id',
        'transaksi_id',
        'parent_id',
        'jenis',
        'batch_number',
        'expiry_date',
        'quantity',
        'reserved_quantity',
        'available_quantity',
        'hpp',
        'status',
        'keterangan',
        'user_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tanggal' => 'datetime',
    ];

    public function rekanans()
    {
        return $this->belongsTo(Rekanan::class, 'rekanan_id','id');
    }

    public function farms()
    {
        return $this->belongsTo(Farm::class, 'farm_id','id');
    }

    public function kandangs()
    {
        return $this->belongsTo(Kandang::class, 'kandang_id','id');
    }

    public function items()
    {
        return $this->belongsTo(Item::class, 'item_id','id');
    }

    public function transaksi()
    {
        return $this->belongsTo(Transaksi::class); 
    }

    public function transaksiBeli()
    {
        return $this->belongsTo(TransaksiBeli::class,'transaksi_id','id'); 
    }

    public function transaksiDetail()
    {
        return $this->belongsTo(TransaksiDetail::class, 'transaksi_detail_id','id');
    }

    public function inventoryLocation(){
        return $this->belongsTo(InventoryLocation::class, 'location_id','id');
    }

    public function transaksiHarianDetail(){
        return $this->hasMany(TransaksiHarianDetail::class,'stock_history_id','id');
    }
}

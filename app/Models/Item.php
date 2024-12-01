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
        'category_id',
        'kode',
        'name',
        'description',
        'satuan_besar',
        'satuan_kecil',
        'konversi',
        'minimum_stock',
        'maximum_stock',
        'reorder_point',
        'status',
        'is_feed',
        'created_by',
        'updated_by',
    ];

    public function itemCategory()
    {
        return $this->belongsTo(ItemCategory::class, 'category_id','id');
    }

    public function transaksiDetail()
    {
        return $this->hasMany(TransaksiDetail::class);
    }

    public function stockHistory()
    {
        return $this->hasMany(StockHistory::class,'item_id','id');
    }

    public function currentStock()
    {
        return $this->hasMany(CurrentStock::class,'item_id','id');
    }

    public function category(){
        return $this->belongsTo(ItemCategory::class, 'category_id','id');
    }

    public function inventoryLocation()
    {
        return $this->belongsTo(InventoryLocation::class);
    }

//     public function farmOperator()
//    {
//        return $this->belongsToMany(User::class, 'farm_operators', 'item_id', 'user_id')
//                    ->withPivot('farm_id');
//    }
}

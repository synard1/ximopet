<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class CurrentStock extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'current_stocks';

    protected $fillable = [
        'id',
        'item_id',
        'location_id',
        'expiry_date',
        'quantity',
        'reserved_quantity',
        'available_quantity',
        'hpp',
        'status',
        'created_by',
        'updated_by',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }

    public function inventoryLocation()
    {
        return $this->belongsTo(InventoryLocation::class, 'location_id', 'id');
    }
}

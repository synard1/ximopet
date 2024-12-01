<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItemLocation extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'item_location_mappings';

    protected $fillable = [
        'id',
        'item_id',
        'location_id',
        'farm_id',
        'status',
        'created_by',
        'updated_by',
    ];

    public function item(){
        return $this->belongsTo(Item::class);
    }

    public function farm(){
        return $this->belongsTo(Farm::class);
    }

    public function location(){
        return $this->belongsTo(InventoryLocation::class);
    }
}

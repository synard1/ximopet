<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class CurrentLivestock extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'id',
        'livestock_id',
        'farm_id',
        'coop_id',
        'quantity',
        'berat_total',
        'avg_berat',
        'age',
        'metadata',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function livestock()
    {
        return $this->belongsTo(Livestock::class, 'livestock_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }

    public function inventoryLocation()
    {
        return $this->belongsTo(InventoryLocation::class, 'location_id', 'id');
    }
}

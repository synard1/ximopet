<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryLocation extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'inventory_locations';

    protected $fillable = [
        'id',
        'farm_id',
        'kandang_id',
        'silo_id',
        'name',
        'code',
        'description',
        'type',
        'status',
        'created_by',
        'updated_by',
    ];

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function farmSilo()
    {
        return $this->belongsTo(FarmSilo::class);
    }
}
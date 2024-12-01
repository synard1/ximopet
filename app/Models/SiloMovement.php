<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class SiloMovement extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'silo_movements';

    protected $fillable = [
        'id',
        'silo_id',
        'item_id',
        'movement_type',
        'quantity',
        'remaining_quantity',
        'batch_number',
        'movement_date',
        'notes',
        'status',
        'created_by',
        'updated_by',
    ];
}

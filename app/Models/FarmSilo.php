<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class FarmSilo extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'silos';

    protected $fillable = [
        'id',
        'farm_id',
        'name',
        'code',
        'capacity',
        'description',
        'status',
        'created_by',
        'updated_by',
    ];
}
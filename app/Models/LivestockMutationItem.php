<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class LivestockMutationItem extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'id',
        'livestock_mutation_id',
        'quantity',
        'weight',
        'keterangan',
        'payload',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItemCategory extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'item_categories';

    protected $fillable = [
        'id',
        'name',
        'code',
        'description',
        'status',
        'created_by',
        'updated_by',
    ];

}

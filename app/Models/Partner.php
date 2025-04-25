<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\BaseModel;

class Partner extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'id',
        'type',
        'code',
        'name',
        'email',
        'address',
        'phone_number',
        'contact_person',
        'description',
        'status',
        'created_by',
        'updated_by',
    ];

}

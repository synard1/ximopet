<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'companies';

    protected $fillable = [
        'id',
        'name',
        'address',
        'phone',
        'email',
        'logo',
        'domain',
        'database',
        'package',
        'status',
        'keterangan',
        'created_by',
        'updated_by',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyUser extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'company_users';

    protected $fillable = [
        'id',
        'company_id',
        'user_id',
        'status',
        'created_by',
        'updated_by',
    ];
}

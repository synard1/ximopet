<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasCompanyScope;

class Permission extends SpatiePermission
{
    use HasFactory;
    use HasUuids;
    // use HasCompanyScope;

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $requiresCompanyId = false;

    protected $fillable = [
        'id',
        'company_id',
        'name',
        'guard_name',
    ];
}

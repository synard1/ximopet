<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class PermissionPreset extends BaseModel
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'permission_ids',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'permission_ids' => 'array',
    ];

    // Creator relation
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

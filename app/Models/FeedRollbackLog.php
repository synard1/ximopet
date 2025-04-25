<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class FeedRollbackLog extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'id',
        'feed_rollback_id',
        'before',
        'after',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'before' => 'array',
        'after' => 'array',
    ];
}

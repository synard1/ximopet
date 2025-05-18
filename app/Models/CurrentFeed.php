<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class CurrentFeed extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'id',
        'livestock_id',
        'farm_id',
        'kandang_id',
        'unit_id',
        'feed_id',
        'quantity',
        'status',
        'created_by',
        'updated_by',
    ];

    public function feed()
    {
        return $this->belongsTo(Feed::class, 'feed_id', 'id');
    }

}


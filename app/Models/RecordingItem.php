<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class RecordingItem extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'id',
        'recording_id',
        'type',
        'item_id',
        'quantity',
        'payload',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'payload' => 'array',

    ];

    public function feed()
    {
        return $this->belongsTo(Item::class,'item_id');
    }
}

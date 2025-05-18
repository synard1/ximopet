<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class FeedUsage extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'id',
        'livestock_id',
        'recording_id',
        'usage_date',
        'total_quantity',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'usage_date' => 'date',
    ];

    // FeedStock.php
    public function details()
    {
        return $this->hasMany(FeedUsageDetail::class);
    }

    public function livestock()
    {
        return $this->belongsTo(Livestock::class);
    }

    public function recording()
    {
        return $this->belongsTo(Recording::class);
    }
}

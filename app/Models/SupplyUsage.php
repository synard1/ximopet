<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class SupplyUsage extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'id',
        'farm_id',
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
        return $this->hasMany(SupplyUsageDetail::class);
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }
}

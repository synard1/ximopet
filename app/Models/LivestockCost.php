<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Traits\LivestockLockCheck;

class LivestockCost extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;
    use LivestockLockCheck;

    protected $fillable = [
        'id',
        'livestock_id',
        'tanggal',
        'recording_id',
        'total_cost',
        'cost_per_ayam',
        'cost_breakdown',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tanggal' => 'datetime',
        'cost_breakdown' => 'array',
    ];

    public function recording()
    {
        return $this->belongsTo(Recording::class);
    }

    public function livestok()
    {
        return $this->belongsTo(Livestock::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class RecordingSale extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'company_id',
        'livestock_id',
        'recording_id',
        'livestock_batch_id',
        'date',
        'quantity',
        'weight',
        'price',
        'status',
        'metadata',
        'data',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date' => 'date',
        'price' => 'float',
        'metadata' => 'array',
        'data' => 'array',
    ];

    public function recording(): BelongsTo
    {
        return $this->belongsTo(Recording::class, 'recording_id');
    }

    public function livestock(): BelongsTo
    {
        return $this->belongsTo(Livestock::class, 'livestock_id');
    }

    public function livestockBatch()
    {
        return $this->belongsTo(\App\Models\LivestockBatch::class, 'livestock_batch_id');
    }

    /**
     * Get batch detail from data column (if available)
     */
    public function getBatchDetail()
    {
        return $this->data['batch_detail'] ?? null;
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}

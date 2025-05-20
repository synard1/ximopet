<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OVKRecordItem extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = "ovk_record_items";

    protected $fillable = [
        'ovk_record_id',
        'supply_id',
        'quantity',
        'unit_id',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    public function ovkRecord(): BelongsTo
    {
        return $this->belongsTo(OVKRecord::class, 'ovk_record_id', 'id');
    }

    public function supply(): BelongsTo
    {
        return $this->belongsTo(Supply::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
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

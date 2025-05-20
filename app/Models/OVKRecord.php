<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OVKRecord extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = "ovk_records";

    protected $fillable = [
        'usage_date',
        'farm_id',
        'kandang_id',
        'livestock_id',
        'notes',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'usage_date' => 'date',
        'metadata' => 'array'
    ];

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function kandang(): BelongsTo
    {
        return $this->belongsTo(Kandang::class);
    }

    public function livestock(): BelongsTo
    {
        return $this->belongsTo(Livestock::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OVKRecordItem::class, 'ovk_record_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            if ($model->items->isNotEmpty()) {
                $model->metadata = [
                    'items' => $model->items->map(function ($item) {
                        return [
                            'supply_id' => $item->supply_id,
                            'supply_name' => $item->supply->name,
                            'quantity' => $item->quantity,
                            'unit_id' => $item->unit_id,
                            'unit_name' => $item->unit->name,
                            'notes' => $item->notes,
                        ];
                    })->toArray()
                ];
            }
        });
    }
}

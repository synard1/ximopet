<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class MutationItem extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'id',
        'mutation_id',
        'item_type',
        'item_id',
        'stock_id',
        'quantity',
        'amount',
        'unit_metadata',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date' => 'datetime',
        'unit_metadata' => 'array',
    ];

    public function mutation(): BelongsTo
    {
        return $this->belongsTo(Mutation::class);
    }

    public function item()
    {
        return match ($this->item_type) {
            'feed' => $this->belongsTo(\App\Models\Feed::class, 'item_id'),
            'supply' => $this->belongsTo(\App\Models\Supply::class, 'item_id'),
            'livestock' => $this->belongsTo(\App\Models\Livestock::class, 'item_id'),
            default => null,
        };
    }

    public function stocks()
    {
        return match ($this->item_type) {
            'feed' => $this->belongsTo(\App\Models\FeedStock::class, 'stock_id'),
            'supply' => $this->belongsTo(\App\Models\SupplyStock::class, 'stock_id'),
            'livestock' => $this->belongsTo(\App\Models\Livestock::class, 'item_id'),
            default => null,
        };
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ExpeditionTariff extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'company_id',
        'expedition_id',
        'zone_name',
        'zone_description',
        'estimated_rate_per_kg',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'estimated_rate_per_kg' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Relationship to Expedition
     */
    public function expedition(): BelongsTo
    {
        return $this->belongsTo(Expedition::class);
    }

    /**
     * Relationship to Company
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Scope for active tariffs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get estimated cost for reference (not for actual calculation)
     */
    public function getEstimatedCost(float $weight): float
    {
        if (!$this->estimated_rate_per_kg) {
            return 0;
        }

        return $weight * $this->estimated_rate_per_kg;
    }

    /**
     * Get available zones for an expedition
     */
    public static function getZonesForExpedition(string $expeditionId): array
    {
        return static::where('expedition_id', $expeditionId)
            ->active()
            ->pluck('zone_name')
            ->unique()
            ->values()
            ->toArray();
    }
}

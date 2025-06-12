<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class PerformanceBenchmark extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'strain_id',
        'age_week',
        'target_weight',
        'target_fcr',
        'target_mortality_rate',
        'target_daily_gain',
        'weight_min',
        'weight_max',
        'fcr_min',
        'fcr_max',
        'mortality_max',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'target_weight' => 'decimal:2',
        'target_fcr' => 'decimal:3',
        'target_mortality_rate' => 'decimal:2',
        'target_daily_gain' => 'decimal:2',
        'weight_min' => 'decimal:2',
        'weight_max' => 'decimal:2',
        'fcr_min' => 'decimal:3',
        'fcr_max' => 'decimal:3',
        'mortality_max' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get the livestock strain that owns the benchmark
     */
    public function strain(): BelongsTo
    {
        return $this->belongsTo(LivestockStrain::class, 'strain_id');
    }

    /**
     * Get the user who created the benchmark
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the benchmark
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope to get active benchmarks
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get benchmarks for specific strain
     */
    public function scopeForStrain($query, $strainId)
    {
        return $query->where('strain_id', $strainId);
    }

    /**
     * Scope to get benchmarks for specific age week
     */
    public function scopeForAgeWeek($query, $ageWeek)
    {
        return $query->where('age_week', $ageWeek);
    }

    /**
     * Get benchmark for specific strain and age
     */
    public static function getBenchmark($strainId, $ageWeek)
    {
        return static::active()
            ->forStrain($strainId)
            ->forAgeWeek($ageWeek)
            ->first();
    }

    /**
     * Check if a value is within acceptable range
     */
    public function isWeightInRange($weight): bool
    {
        return $weight >= $this->weight_min && $weight <= $this->weight_max;
    }

    /**
     * Check if FCR is within acceptable range
     */
    public function isFcrInRange($fcr): bool
    {
        return $fcr >= $this->fcr_min && $fcr <= $this->fcr_max;
    }

    /**
     * Check if mortality rate is acceptable
     */
    public function isMortalityAcceptable($mortalityRate): bool
    {
        return $mortalityRate <= $this->mortality_max;
    }

    /**
     * Get performance grade based on metrics
     */
    public function getPerformanceGrade(array $metrics): string
    {
        $score = 0;
        $maxScore = 4;

        // Weight score
        if (isset($metrics['weight']) && $this->isWeightInRange($metrics['weight'])) {
            $score++;
        }

        // FCR score
        if (isset($metrics['fcr']) && $this->isFcrInRange($metrics['fcr'])) {
            $score++;
        }

        // Mortality score
        if (isset($metrics['mortality_rate']) && $this->isMortalityAcceptable($metrics['mortality_rate'])) {
            $score++;
        }

        // Daily gain score
        if (isset($metrics['daily_gain']) && $metrics['daily_gain'] >= $this->target_daily_gain) {
            $score++;
        }

        $percentage = ($score / $maxScore) * 100;

        if ($percentage >= 90) return 'A+';
        if ($percentage >= 80) return 'A';
        if ($percentage >= 70) return 'B';
        if ($percentage >= 60) return 'C';
        return 'D';
    }
}

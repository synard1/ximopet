<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class PeriodAnalytics extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'livestock_id',
        'farm_id',
        'coop_id',
        'period_type',
        'period_start',
        'period_end',
        'total_mortality',
        'avg_mortality_rate',
        'total_sales',
        'total_revenue',
        'avg_fcr',
        'avg_daily_gain',
        'final_weight',
        'total_feed_cost',
        'profit_margin',
        'mortality_rank',
        'growth_rank',
        'efficiency_rank',
        'detailed_metrics',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'avg_mortality_rate' => 'decimal:2',
        'total_revenue' => 'decimal:2',
        'avg_fcr' => 'decimal:3',
        'avg_daily_gain' => 'decimal:2',
        'final_weight' => 'decimal:2',
        'total_feed_cost' => 'decimal:2',
        'profit_margin' => 'decimal:2',
        'detailed_metrics' => 'array',
    ];

    public function livestock()
    {
        return $this->belongsTo(Livestock::class);
    }

    public function farm()
    {
        return $this->belongsTo(Farm::class);
    }

    public function coop()
    {
        return $this->belongsTo(Coop::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeForPeriodType($query, $type)
    {
        return $query->where('period_type', $type);
    }

    public function scopeTopPerformers($query, $metric = 'efficiency_rank', $limit = 10)
    {
        return $query->orderBy($metric)->limit($limit);
    }

    public function scopeBottomPerformers($query, $metric = 'efficiency_rank', $limit = 10)
    {
        return $query->orderBy($metric, 'desc')->limit($limit);
    }

    public function getROIAttribute()
    {
        if ($this->total_feed_cost > 0) {
            return ($this->profit_margin / $this->total_feed_cost) * 100;
        }
        return 0;
    }

    public function getPerformanceGradeAttribute()
    {
        $score = 0;

        // FCR scoring (lower is better)
        if ($this->avg_fcr <= 1.5) $score += 25;
        elseif ($this->avg_fcr <= 1.7) $score += 20;
        elseif ($this->avg_fcr <= 2.0) $score += 15;
        elseif ($this->avg_fcr <= 2.3) $score += 10;
        else $score += 5;

        // Mortality scoring (lower is better)
        if ($this->avg_mortality_rate <= 3) $score += 25;
        elseif ($this->avg_mortality_rate <= 5) $score += 20;
        elseif ($this->avg_mortality_rate <= 7) $score += 15;
        elseif ($this->avg_mortality_rate <= 10) $score += 10;
        else $score += 5;

        // Daily gain scoring
        if ($this->avg_daily_gain >= 50) $score += 25;
        elseif ($this->avg_daily_gain >= 45) $score += 20;
        elseif ($this->avg_daily_gain >= 40) $score += 15;
        elseif ($this->avg_daily_gain >= 35) $score += 10;
        else $score += 5;

        // Profit margin scoring
        if ($this->profit_margin > 0) $score += 25;
        elseif ($this->profit_margin >= -5) $score += 15;
        elseif ($this->profit_margin >= -10) $score += 10;
        else $score += 5;

        if ($score >= 90) return 'A+';
        if ($score >= 80) return 'A';
        if ($score >= 70) return 'B+';
        if ($score >= 60) return 'B';
        if ($score >= 50) return 'C';
        return 'D';
    }
}

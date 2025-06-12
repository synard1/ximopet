<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class DailyAnalytics extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'date',
        'livestock_id',
        'farm_id',
        'coop_id',
        'mortality_count',
        'mortality_rate',
        'cumulative_mortality',
        'sales_count',
        'sales_weight',
        'sales_revenue',
        'current_population',
        'average_weight',
        'feed_consumption',
        'fcr',
        'age_days',
        'daily_weight_gain',
        'production_index',
        'efficiency_score',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date' => 'date',
        'mortality_rate' => 'decimal:2',
        'sales_weight' => 'decimal:2',
        'sales_revenue' => 'decimal:2',
        'average_weight' => 'decimal:2',
        'feed_consumption' => 'decimal:2',
        'fcr' => 'decimal:3',
        'daily_weight_gain' => 'decimal:2',
        'production_index' => 'decimal:2',
        'efficiency_score' => 'decimal:2',
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

    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    public function scopeForFarm($query, $farmId)
    {
        return $query->where('farm_id', $farmId);
    }

    public function scopeForCoop($query, $coopId)
    {
        return $query->where('coop_id', $coopId);
    }

    public function scopeHighMortality($query, $threshold = 5)
    {
        return $query->where('mortality_rate', '>', $threshold);
    }

    public function scopeLowEfficiency($query, $threshold = 60)
    {
        return $query->where('efficiency_score', '<', $threshold);
    }
}

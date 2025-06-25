<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class AlertLog extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'alert_logs';

    protected $fillable = [
        'type',
        'level',
        'title',
        'message',
        'data',
        'metadata',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'data' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    // Universal Alert Levels
    const LEVEL_INFO = 'info';
    const LEVEL_WARNING = 'warning';
    const LEVEL_ERROR = 'error';
    const LEVEL_CRITICAL = 'critical';

    /**
     * Get alerts by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get alerts by level
     */
    public function scopeByLevel($query, string $level)
    {
        return $query->where('level', $level);
    }

    /**
     * Get recent alerts
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Get critical alerts
     */
    public function scopeCritical($query)
    {
        return $query->where('level', self::LEVEL_CRITICAL);
    }

    /**
     * Get formatted alert data for display (generic implementation)
     */
    public function getFormattedDataAttribute(): array
    {
        $data = $this->data ?? [];
        $formatted = [];

        // Format common data fields (generic approach)
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $formatted[ucwords(str_replace('_', ' ', $key))] = $value;
            } elseif (is_numeric($value) && $key === 'quantity') {
                $formatted[ucwords(str_replace('_', ' ', $key))] = number_format($value, 2) . ' kg';
            } elseif (is_numeric($value) && str_contains($key, 'cost')) {
                $formatted[ucwords(str_replace('_', ' ', $key))] = 'Rp ' . number_format($value, 0, ',', '.');
            } else {
                $formatted[ucwords(str_replace('_', ' ', $key))] = $value;
            }
        }

        return $formatted;
    }

    /**
     * Get level badge color
     */
    public function getLevelColorAttribute(): string
    {
        $colors = [
            self::LEVEL_INFO => 'success',
            self::LEVEL_WARNING => 'warning',
            self::LEVEL_ERROR => 'danger',
            self::LEVEL_CRITICAL => 'dark'
        ];

        return $colors[$this->level] ?? 'secondary';
    }

    /**
     * Get level icon
     */
    public function getLevelIconAttribute(): string
    {
        $icons = [
            self::LEVEL_INFO => 'fas fa-info-circle',
            self::LEVEL_WARNING => 'fas fa-exclamation-triangle',
            self::LEVEL_ERROR => 'fas fa-times-circle',
            self::LEVEL_CRITICAL => 'fas fa-skull-crossbones'
        ];

        return $icons[$this->level] ?? 'fas fa-bell';
    }

    /**
     * Get alert summary for quick display
     */
    public function getSummaryAttribute(): string
    {
        return "[{$this->level}] {$this->title}: {$this->message}";
    }

    /**
     * Check if alert is recent (within last 24 hours)
     */
    public function getIsRecentAttribute(): bool
    {
        return $this->created_at->isAfter(now()->subDay());
    }

    /**
     * Get alert age in human readable format
     */
    public function getAgeAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }
}

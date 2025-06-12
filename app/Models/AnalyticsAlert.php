<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class AnalyticsAlert extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'livestock_id',
        'farm_id',
        'coop_id',
        'alert_type',
        'severity',
        'title',
        'description',
        'metrics',
        'recommendation',
        'is_resolved',
        'resolved_at',
        'resolved_by',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'metrics' => 'array',
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
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

    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeUnresolved($query)
    {
        return $query->where('is_resolved', false);
    }

    public function scopeBySeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('alert_type', $type);
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    public function scopeHigh($query)
    {
        return $query->where('severity', 'high');
    }

    public function resolve($resolvedBy = null)
    {
        $this->update([
            'is_resolved' => true,
            'resolved_at' => now(),
            'resolved_by' => $resolvedBy ?? auth()->id(),
        ]);
    }

    public function getSeverityColorAttribute()
    {
        return match ($this->severity) {
            'critical' => 'red',
            'high' => 'orange',
            'medium' => 'yellow',
            'low' => 'blue',
            default => 'gray'
        };
    }

    public function getSeverityIconAttribute()
    {
        return match ($this->severity) {
            'critical' => 'exclamation-triangle',
            'high' => 'exclamation',
            'medium' => 'info-circle',
            'low' => 'lightbulb',
            default => 'bell'
        };
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Carbon;

class SecurityViolation extends Model
{
    use HasUuids;

    protected $table = 'security_violations';

    public $timestamps = false; // Only has created_at

    protected $fillable = [
        'ip_address',
        'reason',
        'metadata',
        'user_agent',
        'created_at'
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime'
    ];

    /**
     * Record a new security violation
     */
    public static function recordViolation(string $ip, string $reason, array $metadata = []): void
    {
        static::create([
            'ip_address' => $ip,
            'reason' => $reason,
            'metadata' => $metadata,
            'user_agent' => request()->userAgent(),
            'created_at' => Carbon::now()
        ]);
    }

    /**
     * Get violation count for IP in time period
     */
    public static function getViolationCount(string $ip, int $hours = 24): int
    {
        return static::where('ip_address', $ip)
            ->where('created_at', '>=', Carbon::now()->subHours($hours))
            ->count();
    }

    /**
     * Get recent violations for IP
     */
    public static function getRecentViolations(string $ip, int $hours = 24)
    {
        return static::where('ip_address', $ip)
            ->where('created_at', '>=', Carbon::now()->subHours($hours))
            ->orderBy('created_at', 'desc');
    }

    /**
     * Clean old violations
     */
    public static function cleanOldViolations(int $days = 30): int
    {
        return static::where('created_at', '<=', Carbon::now()->subDays($days))
            ->delete();
    }

    /**
     * Get violation statistics
     */
    public static function getStatistics(int $days = 7): array
    {
        $since = Carbon::now()->subDays($days);

        return [
            'total_violations' => static::where('created_at', '>=', $since)->count(),
            'unique_ips' => static::where('created_at', '>=', $since)->distinct('ip_address')->count(),
            'top_reasons' => static::where('created_at', '>=', $since)
                ->selectRaw('reason, COUNT(*) as count')
                ->groupBy('reason')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get(),
            'top_ips' => static::where('created_at', '>=', $since)
                ->selectRaw('ip_address, COUNT(*) as count')
                ->groupBy('ip_address')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get()
        ];
    }
}

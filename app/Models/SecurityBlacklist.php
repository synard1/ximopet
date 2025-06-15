<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SecurityBlacklist extends Model
{
    use HasUuids;

    protected $table = 'security_blacklist';

    protected $fillable = [
        'ip_address',
        'reason',
        'violation_count',
        'expires_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'violation_count' => 'integer'
    ];

    /**
     * Check if IP is currently blacklisted
     */
    public static function isBlacklisted(string $ip): bool
    {
        return static::where('ip_address', $ip)
            ->where('expires_at', '>', Carbon::now())
            ->exists();
    }

    /**
     * Add or update blacklist entry
     */
    public static function addToBlacklist(string $ip, string $reason = 'security_violation', int $durationHours = 72): void
    {
        $expiresAt = Carbon::now()->addHours($durationHours);

        static::updateOrCreate(
            ['ip_address' => $ip],
            [
                'reason' => $reason,
                'expires_at' => $expiresAt,
                'violation_count' => DB::raw('COALESCE(violation_count, 0) + 1')
            ]
        );
    }

    /**
     * Remove from blacklist
     */
    public static function removeFromBlacklist(string $ip): bool
    {
        return static::where('ip_address', $ip)->delete();
    }

    /**
     * Clean expired entries
     */
    public static function cleanExpired(): int
    {
        return static::where('expires_at', '<=', Carbon::now())->delete();
    }

    /**
     * Get active blacklist entries
     */
    public static function getActive()
    {
        return static::where('expires_at', '>', Carbon::now())
            ->orderBy('created_at', 'desc');
    }

    /**
     * Check if blacklist entry is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
 
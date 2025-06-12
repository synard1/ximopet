<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TempAuthLog extends Model
{
    use HasFactory;

    protected $table = 'temp_auth_logs';

    protected $fillable = [
        'user_id',
        'authorizer_user_id',
        'action',
        'component',
        'request_url',
        'component_namespace',
        'request_method',
        'referrer_url',
        'reason',
        'duration_minutes',
        'auth_method',
        'ip_address',
        'user_agent',
        'granted_at',
        'expires_at',
        'revoked_at',
        'auto_expired_at',
        'metadata',
    ];

    protected $casts = [
        'granted_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
        'auto_expired_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * User yang mendapat autorisasi
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * User yang memberikan autorisasi
     */
    public function authorizerUser()
    {
        return $this->belongsTo(User::class, 'authorizer_user_id');
    }

    /**
     * Scope untuk log dengan action tertentu
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope untuk log dalam rentang tanggal
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Get duration string yang human readable
     */
    public function getDurationString(): string
    {
        if (!$this->duration_minutes) {
            return 'N/A';
        }

        $hours = floor($this->duration_minutes / 60);
        $minutes = $this->duration_minutes % 60;

        if ($hours > 0) {
            return $hours . ' jam ' . ($minutes > 0 ? $minutes . ' menit' : '');
        }

        return $minutes . ' menit';
    }

    /**
     * Check apakah authorization masih aktif
     */
    public function isActive(): bool
    {
        if ($this->revoked_at || $this->auto_expired_at) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }
}
 
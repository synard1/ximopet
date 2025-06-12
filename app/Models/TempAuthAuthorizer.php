<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TempAuthAuthorizer extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'temp_auth_authorizers';

    protected $fillable = [
        'user_id',
        'authorized_by',
        'is_active',
        'can_authorize_self',
        'max_authorization_duration',
        'allowed_components',
        'notes',
        'authorized_at',
        'expires_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'can_authorize_self' => 'boolean',
        'allowed_components' => 'array',
        'authorized_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * User yang diberikan hak autorisasi
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * User yang memberikan hak autorisasi
     */
    public function authorizedBy()
    {
        return $this->belongsTo(User::class, 'authorized_by');
    }

    /**
     * Scope untuk authorizer yang aktif
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Check apakah user dapat memberikan autorisasi
     */
    public function canAuthorize(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Check apakah user dapat memberikan autorisasi untuk komponen tertentu
     */
    public function canAuthorizeComponent(string $component): bool
    {
        if (!$this->canAuthorize()) {
            return false;
        }

        // Jika tidak ada batasan komponen, allow semua
        if (empty($this->allowed_components)) {
            return true;
        }

        return in_array($component, $this->allowed_components);
    }

    /**
     * Get maximum duration yang bisa diberikan user ini
     */
    public function getMaxDuration(): int
    {
        return $this->max_authorization_duration ?? config('temp_auth.default_duration', 30);
    }
}

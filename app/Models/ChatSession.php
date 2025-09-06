<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasCompanyScope;
use Carbon\Carbon;

class ChatSession extends Model
{
    use HasUuids, SoftDeletes, HasCompanyScope;

    protected $fillable = [
        'user_id',
        'company_id',
        'title',
        'ai_provider',
        'model_name',
        'context_data',
        'is_active',
        'last_activity_at'
    ];

    protected $casts = [
        'context_data' => 'array',
        'is_active' => 'boolean',
        'last_activity_at' => 'datetime'
    ];

    /**
     * Get the user that owns the chat session
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the company that owns the chat session
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get all messages for this chat session
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class)->orderBy('created_at');
    }

    /**
     * Get the latest message for this session
     */
    public function latestMessage(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ChatMessage::class)->latest();
    }

    /**
     * Scope for active sessions
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for recent sessions
     */
    public function scopeRecent($query, $days = 7)
    {
        return $query->where('last_activity_at', '>=', Carbon::now()->subDays($days));
    }

    /**
     * Scope for specific provider
     */
    public function scopeProvider($query, $provider)
    {
        return $query->where('ai_provider', $provider);
    }

    /**
     * Update last activity timestamp
     */
    public function updateActivity(): void
    {
        $this->update([
            'last_activity_at' => Carbon::now()
        ]);
    }

    /**
     * Generate a title based on the first message
     */
    public function generateTitle(string $firstMessage): string
    {
        $words = explode(' ', $firstMessage);
        $title = implode(' ', array_slice($words, 0, 6));

        return strlen($title) > 50 ? substr($title, 0, 47) . '...' : $title;
    }

    /**
     * Get the message count for this session
     */
    public function getMessageCountAttribute(): int
    {
        return $this->messages()->count();
    }

    /**
     * Check if session is expired (no activity for X days)
     */
    public function isExpired(int $days = 30): bool
    {
        if (!$this->last_activity_at) {
            return false;
        }

        return $this->last_activity_at->diffInDays(Carbon::now()) > $days;
    }
}

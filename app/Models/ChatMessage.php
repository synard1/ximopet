<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChatMessage extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'chat_session_id',
        'user_id',
        'message_type',
        'is_greeting',
        'content',
        'metadata',
        'processing_time',
        'token_count'
    ];

    protected $casts = [
        'metadata' => 'array',
        'processing_time' => 'decimal:3',
        'is_greeting' => 'boolean'
    ];

    /**
     * Get the chat session that owns the message
     */
    public function chatSession(): BelongsTo
    {
        return $this->belongsTo(ChatSession::class);
    }

    /**
     * Get the user that sent the message
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the rating for this message
     */
    public function rating(): HasOne
    {
        return $this->hasOne(ChatRating::class, 'chat_message_id');
    }

    /**
     * Scope for user messages
     */
    public function scopeUserMessages($query)
    {
        return $query->where('message_type', 'user');
    }

    /**
     * Scope for assistant messages
     */
    public function scopeAssistantMessages($query)
    {
        return $query->where('message_type', 'assistant');
    }

    /**
     * Scope for system messages
     */
    public function scopeSystemMessages($query)
    {
        return $query->where('message_type', 'system');
    }

    /**
     * Scope for greeting messages
     */
    public function scopeGreetingMessages($query)
    {
        return $query->where('is_greeting', true);
    }

    /**
     * Scope for recent messages
     */
    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    /**
     * Check if message is from user
     */
    public function isUserMessage(): bool
    {
        return $this->message_type === 'user';
    }

    /**
     * Check if message is from assistant
     */
    public function isAssistantMessage(): bool
    {
        return $this->message_type === 'assistant';
    }

    /**
     * Check if message is system message
     */
    public function isSystemMessage(): bool
    {
        return $this->message_type === 'system';
    }

    /**
     * Check if message is a greeting
     */
    public function isGreetingMessage(): bool
    {
        return $this->is_greeting === true;
    }

    /**
     * Get truncated content for preview
     */
    public function getPreviewAttribute(): string
    {
        return strlen($this->content) > 100
            ? substr($this->content, 0, 97) . '...'
            : $this->content;
    }

    /**
     * Get formatted processing time
     */
    public function getFormattedProcessingTimeAttribute(): string
    {
        if (!$this->processing_time) {
            return 'N/A';
        }

        return number_format($this->processing_time, 3) . 's';
    }

    /**
     * Get message type label
     */
    public function getTypeLabel(): string
    {
        return match($this->message_type) {
            'user' => 'User',
            'assistant' => 'AI Assistant',
            'system' => 'System',
            default => 'Unknown'
        };
    }
}

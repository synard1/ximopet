<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasCompanyScope;

class AiProvider extends Model
{
    use SoftDeletes, HasCompanyScope;

    protected $fillable = [
        'name',
        'type',
        'base_url',
        'api_key',
        'default_model',
        'available_models',
        'is_active',
        'company_id',
        'configuration'
    ];

    protected $casts = [
        'available_models' => 'array',
        'is_active' => 'boolean',
        'configuration' => 'array'
    ];

    protected $hidden = [
        'api_key'
    ];

    /**
     * Get the company that owns the AI provider
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get chat sessions using this provider
     */
    public function chatSessions(): HasMany
    {
        return $this->hasMany(ChatSession::class, 'ai_provider', 'type');
    }

    /**
     * Scope for active providers
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for specific provider type
     */
    public function scopeType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Check if provider has API key configured
     */
    public function hasApiKey(): bool
    {
        return !empty($this->api_key);
    }

    /**
     * Get masked API key for display
     */
    public function getMaskedApiKeyAttribute(): string
    {
        if (empty($this->api_key)) {
            return 'Not configured';
        }

        return substr($this->api_key, 0, 8) . '***';
    }

    /**
     * Check if a model is available for this provider
     */
    public function hasModel(string $model): bool
    {
        if (!$this->available_models) {
            return false;
        }

        return in_array($model, $this->available_models);
    }
}

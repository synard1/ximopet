<?php

namespace App\AiChatV2\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class UserChatSettingsV2 extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'user_chat_settings_v2';

    protected $fillable = [
        'user_id',
        'settings',
        'version'
    ];

    protected $casts = [
        'settings' => 'array'
    ];

    /**
     * Relasi ke User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Boot method untuk set default version
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->version)) {
                $model->version = '2.0';
            }
        });
    }

    /**
     * Scope untuk user tertentu
     */
    public function scopeForUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Mendapatkan setting tertentu
     */
    public function getSetting(string $key, $default = null)
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * Set setting tertentu
     */
    public function setSetting(string $key, $value): bool
    {
        $settings = $this->settings ?? [];
        data_set($settings, $key, $value);

        return $this->update(['settings' => $settings]);
    }

    /**
     * Merge settings baru dengan yang ada
     */
    public function mergeSettings(array $newSettings): bool
    {
        $currentSettings = $this->settings ?? [];
        $mergedSettings = array_merge_recursive($currentSettings, $newSettings);

        return $this->update(['settings' => $mergedSettings]);
    }

    /**
     * Reset settings ke default
     */
    public function resetToDefault(): bool
    {
        $defaultSettings = [
            'provider' => config('ai-chat-v2.default_provider', 'ollama'),
            'model' => config('ai-chat-v2.default_model', ''),
            'ui' => [
                'theme' => 'light',
                'position' => 'bottom-right',
                'size' => 'medium',
                'auto_scroll' => true,
                'show_timestamps' => true,
                'enable_sounds' => true,
                'enable_animations' => true
            ],
            'chat' => [
                'auto_save' => true,
                'max_context_messages' => 10,
                'enable_markdown' => true,
                'enable_code_highlighting' => true,
                'typing_indicator' => true
            ],
            'privacy' => [
                'save_history' => true,
                'share_analytics' => false
            ]
        ];

        return $this->update(['settings' => $defaultSettings]);
    }

    /**
     * Check apakah setting ada
     */
    public function hasSetting(string $key): bool
    {
        return data_get($this->settings, $key) !== null;
    }

    /**
     * Hapus setting tertentu
     */
    public function removeSetting(string $key): bool
    {
        $settings = $this->settings ?? [];

        if (str_contains($key, '.')) {
            // Handle nested keys
            $keys = explode('.', $key);
            $current = &$settings;

            for ($i = 0; $i < count($keys) - 1; $i++) {
                if (!isset($current[$keys[$i]])) {
                    return true; // Key doesn't exist, consider it removed
                }
                $current = &$current[$keys[$i]];
            }

            unset($current[end($keys)]);
        } else {
            unset($settings[$key]);
        }

        return $this->update(['settings' => $settings]);
    }

    /**
     * Mendapatkan semua settings dalam format flat
     */
    public function getFlatSettings(): array
    {
        return $this->flattenArray($this->settings ?? []);
    }

    /**
     * Helper untuk flatten array
     */
    private function flattenArray(array $array, string $prefix = ''): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $newKey = $prefix === '' ? $key : $prefix . '.' . $key;

            if (is_array($value)) {
                $result = array_merge($result, $this->flattenArray($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }

        return $result;
    }

    /**
     * Export settings untuk backup
     */
    public function exportSettings(): array
    {
        return [
            'user_id' => $this->user_id,
            'settings' => $this->settings,
            'version' => $this->version,
            'exported_at' => now()->toISOString()
        ];
    }

    /**
     * Import settings dari backup
     */
    public function importSettings(array $data): bool
    {
        if (!isset($data['settings'])) {
            throw new \InvalidArgumentException('Settings data is required');
        }

        return $this->update([
            'settings' => $data['settings'],
            'version' => $data['version'] ?? $this->version
        ]);
    }
}

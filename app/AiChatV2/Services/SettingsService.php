<?php

namespace App\AiChatV2\Services;

use App\AiChatV2\Contracts\SettingsServiceInterface;
use App\AiChatV2\Models\UserChatSettingsV2;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class SettingsService implements SettingsServiceInterface
{
    /**
     * Get user settings
     */
    public function getUserSettings(string $userId): array
    {
        $userId = $userId ?? Auth::id();
        
        if (!$userId) {
            return $this->getDefaultSettings();
        }
        
        $userSettings = UserChatSettingsV2::where('user_id', $userId)->first();
        
        if (!$userSettings) {
            return $this->getDefaultSettings();
        }
        
        return array_merge($this->getDefaultSettings(), $userSettings->settings);
    }

    /**
     * Update user settings
     */
    public function updateUserSettings(array $settings, string $userId = null): bool
    {
        try {
            $userId = $userId ?? Auth::id();
            
            if (!$userId) {
                throw new Exception('User ID is required');
            }
            
            // Validate settings
            $validatedSettings = $this->validateSettings($settings);
            
            // Get current settings
            $currentSettings = $this->getUserSettings($userId);
            
            // Merge with new settings
            $mergedSettings = array_merge($currentSettings, $validatedSettings);
            
            // Update or create user settings
            UserChatSettingsV2::updateOrCreate(
                ['user_id' => $userId],
                [
                    'settings' => $mergedSettings,
                    'version' => config('ai-chat-v2.settings.version', 1)
                ]
            );
            
            Log::info('User chat settings updated', [
                'user_id' => $userId,
                'settings_keys' => array_keys($validatedSettings)
            ]);
            
            return true;
            
        } catch (Exception $e) {
            Log::error('Failed to update user settings', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Reset user settings to default
     */
    public function resetToDefault(string $userId): bool
    {
        try {
            if (!$userId) {
                throw new Exception('User ID is required');
            }
            
            UserChatSettingsV2::where('user_id', $userId)->delete();
            
            Log::info('User chat settings reset to default', [
                'user_id' => $userId
            ]);
            
            return true;
            
        } catch (Exception $e) {
            Log::error('Failed to reset user settings', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get available providers
     */
    public function getAvailableProviders(): array
    {
        return config('ai-chat-v2.providers', []);
    }

    /**
     * Get available models for a provider
     */
    public function getAvailableModels(string $provider): array
    {
        $providerConfig = config("ai-chat-v2.providers.{$provider}");
        
        if (!$providerConfig) {
            return [];
        }
        
        return $providerConfig['models'] ?? [$providerConfig['default_model']];
    }

    /**
     * Get setting value
     */
    public function getSetting(string $key, string $userId = null, $default = null)
    {
        $settings = $this->getUserSettings($userId);
        
        return data_get($settings, $key, $default);
    }

    /**
     * Update single setting
     */
    public function updateSetting(string $key, $value, string $userId = null): bool
    {
        $currentSettings = $this->getUserSettings($userId);
        
        // Use dot notation to set nested values
        data_set($currentSettings, $key, $value);
        
        return $this->updateUserSettings($currentSettings, $userId);
    }

    /**
     * Export user settings
     */
    public function exportSettings(string $userId): array
    {
        if (!$userId) {
            return [];
        }
        
        $userSettings = UserChatSettingsV2::where('user_id', $userId)->first();
        
        return [
            'user_id' => $userId,
            'settings' => $userSettings ? $userSettings->settings : $this->getDefaultSettings(),
            'version' => $userSettings ? $userSettings->version : 1,
            'exported_at' => now()->toISOString()
        ];
    }

    /**
     * Import user settings
     */
    public function importSettings(string $userId, array $settingsData): bool
    {
        try {
            if (!$userId) {
                throw new Exception('User ID is required');
            }
            
            if (!isset($settingsData['settings']) || !is_array($settingsData['settings'])) {
                throw new Exception('Invalid settings data');
            }
            
            return $this->updateUserSettings($settingsData['settings'], $userId);
            
        } catch (Exception $e) {
            Log::error('Failed to import user settings', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Get default settings
     */
    public function getDefaultSettings(): array
    {
        return config('ai-chat-v2.defaults', [
            'provider' => 'ollama',
            'model' => 'llama2',
            'temperature' => 0.7,
            'max_tokens' => 2048,
            'context_length' => 10,
            'auto_save' => true,
            'theme' => 'light',
            'language' => 'en',
            'notifications' => [
                'sound' => true,
                'desktop' => false
            ],
            'ui' => [
                'show_timestamps' => true,
                'show_token_count' => false,
                'compact_mode' => false,
                'auto_scroll' => true
            ],
            'privacy' => [
                'save_history' => true,
                'analytics' => false
            ]
        ]);
    }
    
    /**
     * Validate settings
     */
    public function validateSettings(array $settings): array
    {
        $validatedSettings = [];
        $allowedKeys = $this->getAllowedSettingsKeys();
        
        foreach ($settings as $key => $value) {
            if (in_array($key, $allowedKeys)) {
                $validatedSettings[$key] = $this->validateSettingValue($key, $value);
            }
        }
        
        return $validatedSettings;
    }

    /**
     * Get allowed settings keys
     */
    private function getAllowedSettingsKeys(): array
    {
        return [
            'provider',
            'model',
            'temperature',
            'max_tokens',
            'context_length',
            'auto_save',
            'theme',
            'language',
            'notifications',
            'ui',
            'privacy'
        ];
    }

    /**
     * Validate setting value
     */
    private function validateSettingValue(string $key, $value)
    {
        switch ($key) {
            case 'provider':
                $availableProviders = array_keys($this->getAvailableProviders());
                return in_array($value, $availableProviders) ? $value : 'ollama';
                
            case 'model':
                return is_string($value) ? $value : 'llama2';
                
            case 'temperature':
                return is_numeric($value) ? max(0, min(2, (float) $value)) : 0.7;
                
            case 'max_tokens':
                return is_numeric($value) ? max(1, min(8192, (int) $value)) : 2048;
                
            case 'context_length':
                return is_numeric($value) ? max(1, min(50, (int) $value)) : 10;
                
            case 'auto_save':
                return is_bool($value) ? $value : true;
                
            case 'theme':
                return in_array($value, ['light', 'dark', 'auto']) ? $value : 'light';
                
            case 'language':
                return is_string($value) ? $value : 'en';
                
            case 'notifications':
            case 'ui':
            case 'privacy':
                return is_array($value) ? $value : [];
                
            default:
                return $value;
        }
    }

    /**
     * Update pengaturan provider
     */
    public function updateProvider(string $userId, string $provider): bool
    {
        try {
            $currentSettings = $this->getUserSettings($userId);
            $currentSettings['provider'] = $provider;
            return $this->updateUserSettings($currentSettings, $userId);
        } catch (Exception $e) {
            Log::error('Failed to update provider setting', [
                'user_id' => $userId,
                'provider' => $provider,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Update pengaturan model
     */
    public function updateModel(string $userId, string $model): bool
    {
        try {
            $currentSettings = $this->getUserSettings($userId);
            $currentSettings['model'] = $model;
            return $this->updateUserSettings($currentSettings, $userId);
        } catch (Exception $e) {
            Log::error('Failed to update model setting', [
                'user_id' => $userId,
                'model' => $model,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Update pengaturan UI
     */
    public function updateUISettings(string $userId, array $uiSettings): bool
    {
        try {
            $currentSettings = $this->getUserSettings($userId);
            $currentSettings['ui'] = array_merge($currentSettings['ui'] ?? [], $uiSettings);
            return $this->updateUserSettings($currentSettings, $userId);
        } catch (Exception $e) {
            Log::error('Failed to update UI settings', [
                'user_id' => $userId,
                'ui_settings' => $uiSettings,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Update pengaturan chat
     */
    public function updateChatSettings(string $userId, array $chatSettings): bool
    {
        try {
            $currentSettings = $this->getUserSettings($userId);
            $currentSettings = array_merge($currentSettings, $chatSettings);
            return $this->updateUserSettings($currentSettings, $userId);
        } catch (Exception $e) {
            Log::error('Failed to update chat settings', [
                'user_id' => $userId,
                'chat_settings' => $chatSettings,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
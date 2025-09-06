<?php

namespace App\AiChatV2\Contracts;

interface SettingsServiceInterface
{
    /**
     * Get user settings
     */
    public function getUserSettings(string $userId): array;

    /**
     * Update user settings
     */
    public function updateUserSettings(array $settings, string $userId = null): bool;

    /**
     * Reset user settings to default
     */
    public function resetToDefault(string $userId): bool;

    /**
     * Get available providers
     */
    public function getAvailableProviders(): array;

    /**
     * Get available models for a provider
     */
    public function getAvailableModels(string $provider): array;

    /**
     * Get setting value
     */
    public function getSetting(string $key, string $userId = null, $default = null);

    /**
     * Update single setting
     */
    public function updateSetting(string $key, $value, string $userId = null): bool;

    /**
     * Export user settings
     */
    public function exportSettings(string $userId): array;

    /**
     * Import user settings
     */
    public function importSettings(string $userId, array $settingsData): bool;

    /**
     * Get default settings
     */
    public function getDefaultSettings(): array;
}
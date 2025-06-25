<?php

namespace App\Services;

use App\Config\CompanyConfig;
use App\Models\Company;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Exception;

class ConfigurationService
{
    /**
     * Get merged configuration with proper separation of concerns
     * 
     * @param int|string|null $companyId
     * @param string $section
     * @return array
     */
    public static function getMergedConfig($companyId = null, string $section = 'livestock'): array
    {
        $defaultConfig = CompanyConfig::getDefaultLivestockConfig();

        // Handle type conversion for companyId
        if ($companyId !== null) {
            $companyId = is_numeric($companyId) ? (int) $companyId : null;
        }

        if (!$companyId) {
            return $defaultConfig;
        }

        $company = Company::find($companyId);
        if (!$company || !$company->config) {
            return $defaultConfig;
        }

        $companyConfig = $company->config[$section] ?? [];

        // Only merge user-configurable settings
        return self::safeConfigMerge($defaultConfig, $companyConfig, $section);
    }

    /**
     * Safely merge company config with default config
     * Only allows modification of user-configurable settings
     * 
     * @param array $defaultConfig
     * @param array $companyConfig  
     * @param string $section
     * @return array
     */
    private static function safeConfigMerge(array $defaultConfig, array $companyConfig, string $section): array
    {
        $userConfigurable = CompanyConfig::getUserConfigurableSettings();
        $allowedPaths = $userConfigurable[$section]['user_editable_paths'] ?? [];

        $mergedConfig = $defaultConfig;

        foreach ($allowedPaths as $path) {
            $companyValue = Arr::get($companyConfig, $path);
            if ($companyValue !== null) {
                // Validate the value is allowed
                if (self::isValueAllowed($path, $companyValue, $section)) {
                    Arr::set($mergedConfig, $path, $companyValue);

                    Log::info('Config merge: Applied user setting', [
                        'path' => $path,
                        'value' => $companyValue,
                        'section' => $section
                    ]);
                } else {
                    Log::warning('Config merge: Rejected invalid user setting', [
                        'path' => $path,
                        'value' => $companyValue,
                        'section' => $section
                    ]);
                }
            }
        }

        return $mergedConfig;
    }

    /**
     * Check if a value is allowed for a specific config path
     * 
     * @param string $path
     * @param mixed $value
     * @param string $section
     * @return bool
     */
    private static function isValueAllowed(string $path, $value, string $section): bool
    {
        $userConfigurable = CompanyConfig::getUserConfigurableSettings();
        $allowedValues = $userConfigurable[$section]['user_editable_values'] ?? [];

        if (isset($allowedValues[$path])) {
            return in_array($value, $allowedValues[$path]);
        }

        // For boolean settings, allow true/false
        if (is_bool($value)) {
            return true;
        }

        // For numeric settings, validate range
        if (is_numeric($value)) {
            return $value >= 0; // Basic validation
        }

        // For string settings, basic validation
        if (is_string($value)) {
            return strlen($value) <= 255;
        }

        return false;
    }

    /**
     * Update company configuration safely
     * Only allows modification of user-configurable settings
     * 
     * @param int|string $companyId
     * @param string $section
     * @param string $path
     * @param mixed $value
     * @param int $userId
     * @return bool
     */
    public static function updateCompanyConfig($companyId, string $section, string $path, $value, int $userId): bool
    {
        try {
            // Handle type conversion for companyId
            $companyId = is_numeric($companyId) ? (int) $companyId : null;

            if (!$companyId) {
                Log::error('Config update rejected: Invalid company ID', [
                    'company_id' => $companyId,
                    'user_id' => $userId
                ]);
                return false;
            }

            // Check if path is user-editable
            if (!self::isPathUserEditable($section, $path)) {
                Log::error('Config update rejected: Path not user-editable', [
                    'company_id' => $companyId,
                    'section' => $section,
                    'path' => $path,
                    'user_id' => $userId
                ]);
                return false;
            }

            // Validate value
            if (!self::isValueAllowed($path, $value, $section)) {
                Log::error('Config update rejected: Invalid value', [
                    'company_id' => $companyId,
                    'section' => $section,
                    'path' => $path,
                    'value' => $value,
                    'user_id' => $userId
                ]);
                return false;
            }

            $company = Company::find($companyId);
            if (!$company) {
                return false;
            }

            // Backup current config
            $currentConfig = $company->config ?? [];
            self::backupConfig($companyId, $currentConfig, $userId);

            // Update config
            $newConfig = $currentConfig;
            Arr::set($newConfig, "{$section}.{$path}", $value);

            $company->config = $newConfig;
            $success = $company->save();

            if ($success) {
                Log::info('Config updated successfully', [
                    'company_id' => $companyId,
                    'section' => $section,
                    'path' => $path,
                    'value' => $value,
                    'user_id' => $userId
                ]);
            }

            return $success;
        } catch (Exception $e) {
            Log::error('Config update failed', [
                'company_id' => $companyId,
                'section' => $section,
                'path' => $path,
                'error' => $e->getMessage(),
                'user_id' => $userId
            ]);
            return false;
        }
    }

    /**
     * Check if a config path is user-editable
     * 
     * @param string $section
     * @param string $path
     * @return bool
     */
    public static function isPathUserEditable(string $section, string $path): bool
    {
        $userConfigurable = CompanyConfig::getUserConfigurableSettings();
        $allowedPaths = $userConfigurable[$section]['user_editable_paths'] ?? [];

        return in_array($path, $allowedPaths);
    }

    /**
     * Check if a config path is developer-only
     * 
     * @param string $section
     * @param string $path
     * @return bool
     */
    public static function isPathDeveloperOnly(string $section, string $path): bool
    {
        $developerOnly = CompanyConfig::getDeveloperOnlySettings();
        $protectedPaths = $developerOnly[$section]['protected_paths'] ?? [];

        foreach ($protectedPaths as $protectedPath) {
            // Support wildcard matching
            if (str_contains($protectedPath, '*')) {
                $pattern = str_replace('*', '.*', $protectedPath);
                if (preg_match("/^{$pattern}$/", $path)) {
                    return true;
                }
            } elseif ($protectedPath === $path) {
                return true;
            }
        }

        return false;
    }

    /**
     * Backup configuration before changes
     * 
     * @param int $companyId
     * @param array $config
     * @param int $userId
     * @return void
     */
    private static function backupConfig(int $companyId, array $config, int $userId): void
    {
        Log::info('Config backup created', [
            'company_id' => $companyId,
            'backup_timestamp' => now()->toDateTimeString(),
            'user_id' => $userId,
            'config_snapshot' => $config
        ]);
    }

    /**
     * Get available user-configurable options for a specific section
     * 
     * @param string $section
     * @return array
     */
    public static function getUserConfigurableOptions(string $section): array
    {
        $userConfigurable = CompanyConfig::getUserConfigurableSettings();
        return $userConfigurable[$section] ?? [];
    }

    /**
     * Get available methods for a specific method type that users can select
     * 
     * @param string $methodType (depletion, mutation, feed_usage)
     * @return array
     */
    public static function getAvailableMethodsForUser(string $methodType): array
    {
        $defaultConfig = CompanyConfig::getDefaultLivestockConfig();
        $methods = $defaultConfig['recording_method']['batch_settings']["{$methodType}_methods"] ?? [];

        $availableMethods = [];
        foreach ($methods as $key => $method) {
            // Only include methods that are enabled and ready
            if (
                isset($method['enabled']) && $method['enabled'] === true &&
                isset($method['status']) && $method['status'] === 'ready'
            ) {
                $availableMethods[$key] = [
                    'name' => strtoupper($key),
                    'enabled' => $method['enabled'],
                    'status' => $method['status'],
                    'auto_select' => $method['auto_select'] ?? false,
                ];
            }
        }

        return $availableMethods;
    }
}

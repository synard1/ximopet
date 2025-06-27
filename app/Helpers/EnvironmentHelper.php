<?php

namespace App\Helpers;

class EnvironmentHelper
{
    /**
     * Check if current environment is local
     */
    public static function isLocal(): bool
    {
        return app()->environment('local');
    }

    /**
     * Check if current environment is production
     */
    public static function isProduction(): bool
    {
        return app()->environment('production');
    }

    /**
     * Check if development packages should be loaded
     */
    public static function shouldLoadDevPackages(): bool
    {
        return self::isLocal() && config('app.debug', false);
    }

    /**
     * Get list of development packages that should only be loaded in local
     */
    public static function getDevPackages(): array
    {
        return [
            'laravel/telescope',
            'laravel/pulse',
            'barryvdh/laravel-debugbar',
            'nunomaduro/collision',
            'spatie/laravel-ignition'
        ];
    }

    /**
     * Check if specific package should be loaded based on environment
     */
    public static function shouldLoadPackage(string $packageName): bool
    {
        if (!self::shouldLoadDevPackages()) {
            return false;
        }

        return in_array($packageName, self::getDevPackages());
    }

    /**
     * Get installed development packages from composer.json
     */
    public static function getInstalledDevPackages(): array
    {
        $composerJson = json_decode(file_get_contents(base_path('composer.json')), true);
        return array_keys($composerJson['require-dev'] ?? []);
    }

    /**
     * Check if package is actually installed
     */
    public static function isPackageInstalled(string $packageName): bool
    {
        $installedPackages = self::getInstalledDevPackages();
        return in_array($packageName, $installedPackages);
    }
}

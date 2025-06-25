<?php

namespace App\Services;

use App\Models\AppConfig;

class ConfigService
{
    public function get($key, $default = null)
    {
        $config = AppConfig::where('company_id', auth()->user()->company_id)->first();
        return $config ? ($config->config[$key] ?? $default) : $default;
    }

    public function set($key, $value)
    {
        $config = AppConfig::firstOrNew(['company_id' => auth()->user()->company_id]);
        $currentConfig = $config->config ?? [];
        $currentConfig[$key] = $value;
        $config->config = $currentConfig;
        $config->save();
    }

    public function remove($key)
    {
        $config = AppConfig::where('company_id', auth()->user()->company_id)->first();
        if ($config) {
            $currentConfig = $config->config;
            unset($currentConfig[$key]);
            $config->config = $currentConfig;
            $config->save();
        }
    }
}

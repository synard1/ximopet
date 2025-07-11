<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;

if (!function_exists('logInfoIfDebug')) {
    function logInfoIfDebug($message, array $context = [])
    {
        if (app()->environment('production') && !config('app.debug')) return;
        Log::info($message, $context);
    }
}

if (!function_exists('logDebugIfDebug')) {
    function logDebugIfDebug($message, array $context = [])
    {
        if (app()->environment('production') && !config('app.debug')) return;
        Log::debug($message, $context);
    }
}

if (!function_exists('logErrorIfDebug')) {
    function logErrorIfDebug($message, array $context = [])
    {
        if (app()->environment('production') && !config('app.debug')) return;
        Log::error($message, $context);
    }
}

if (!function_exists('logWarningIfDebug')) {
    function logWarningIfDebug($message, array $context = [])
    {
        if (app()->environment('production') && !config('app.debug')) return;
        Log::warning($message, $context);
    }
}

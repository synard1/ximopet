<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Supply Usage Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration settings for Supply Usage module including stock updates,
    | background jobs, and performance settings.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Background Job Settings
    |--------------------------------------------------------------------------
    |
    | Configure whether to use background jobs for stock updates and other
    | heavy operations. This helps improve UI responsiveness.
    |
    */
    'use_background_job_for_stock_update' => env('SUPPLY_USAGE_USE_BACKGROUND_JOB', false),

    /*
    |--------------------------------------------------------------------------
    | Stock Update Settings
    |--------------------------------------------------------------------------
    |
    | Settings for stock update behavior and validation.
    |
    */
    'stock_update' => [
        'enable_tracking' => true,
        'enable_audit_trail' => true,
        'max_retries' => 3,
        'timeout_seconds' => 300,
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    |
    | Performance optimization settings for the Supply Usage module.
    |
    */
    'performance' => [
        'enable_caching' => env('SUPPLY_USAGE_ENABLE_CACHING', true),
        'cache_ttl_seconds' => env('SUPPLY_USAGE_CACHE_TTL', 3600),
        'batch_size' => env('SUPPLY_USAGE_BATCH_SIZE', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Settings
    |--------------------------------------------------------------------------
    |
    | Validation rules and settings for Supply Usage operations.
    |
    */
    'validation' => [
        'enable_real_time_validation' => true,
        'enable_stock_validation' => true,
        'max_quantity_per_item' => 999999.99,
        'min_quantity_per_item' => 0.01,
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Settings
    |--------------------------------------------------------------------------
    |
    | Logging configuration for debugging and monitoring.
    |
    */
    'logging' => [
        'enable_debug_logging' => env('SUPPLY_USAGE_DEBUG_LOGGING', false),
        'enable_performance_logging' => env('SUPPLY_USAGE_PERFORMANCE_LOGGING', true),
        'log_stock_changes' => true,
        'log_status_changes' => true,
    ],
];

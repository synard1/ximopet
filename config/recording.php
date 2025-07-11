<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Recording Services Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the modular recording services system.
    | This includes feature flags, performance settings, and integration options.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Control which features are enabled in the recording system.
    | Use these flags for gradual rollout and A/B testing.
    |
    */
    'features' => [
        'use_modular_services' => true,
        'use_legacy_fallback' => false,
        'enable_performance_monitoring' => env('RECORDING_PERFORMANCE_MONITORING', false),
        'enable_async_processing' => env('RECORDING_ASYNC', false),
        'enable_batch_processing' => env('RECORDING_BATCH_ENABLED', false),
        'enable_validation_caching' => env('RECORDING_VALIDATION_CACHE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    |
    | Configure performance-related settings for recording operations.
    |
    */
    'performance' => [
        'batch_size' => env('RECORDING_BATCH_SIZE', 100),
        'cache_ttl' => env('RECORDING_CACHE_TTL', 3600), // 1 hour
        'validation_cache_ttl' => env('RECORDING_VALIDATION_CACHE_TTL', 1800), // 30 minutes
        'max_concurrent_jobs' => env('RECORDING_MAX_CONCURRENT_JOBS', 5),
        'timeout' => env('RECORDING_TIMEOUT', 30), // seconds
        'memory_limit' => env('RECORDING_MEMORY_LIMIT', '256M'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring & Logging
    |--------------------------------------------------------------------------
    |
    | Configure monitoring, logging, and alerting for recording operations.
    |
    */
    'monitoring' => [
        'enabled' => env('RECORDING_MONITORING', true),
        'log_level' => env('RECORDING_LOG_LEVEL', 'info'),
        'log_performance' => env('RECORDING_LOG_PERFORMANCE', true),
        'log_validation_errors' => env('RECORDING_LOG_VALIDATION_ERRORS', true),
        'alert_on_failure_rate' => env('RECORDING_ALERT_FAILURE_RATE', 5.0), // percentage
        'health_check_interval' => env('RECORDING_HEALTH_CHECK_INTERVAL', 300), // 5 minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Integration Settings
    |--------------------------------------------------------------------------
    |
    | Settings for integrating with legacy systems and external services.
    |
    */
    'integration' => [
        'legacy_compatibility_mode' => env('RECORDING_LEGACY_COMPATIBILITY', true),
        'data_migration_enabled' => env('RECORDING_DATA_MIGRATION', false),
        'external_validation' => env('RECORDING_EXTERNAL_VALIDATION', false),
        'webhook_notifications' => env('RECORDING_WEBHOOK_NOTIFICATIONS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    |
    | Default validation rules and thresholds for recording data.
    |
    */
    'validation' => [
        'strict_mode' => env('RECORDING_VALIDATION_STRICT', false),
        'max_feed_usage_per_day' => env('RECORDING_MAX_FEED_USAGE', 1000),
        'max_supply_usage_per_day' => env('RECORDING_MAX_SUPPLY_USAGE', 100),
        'max_mortality_rate' => env('RECORDING_MAX_MORTALITY_RATE', 10.0), // percentage
        'weight_change_threshold' => env('RECORDING_WEIGHT_CHANGE_THRESHOLD', 50.0), // percentage
        'fcr_threshold' => env('RECORDING_FCR_THRESHOLD', 10.0),
    ],

    /*
    |--------------------------------------------------------------------------
    | Service Bindings
    |--------------------------------------------------------------------------
    |
    | Configure which concrete implementations to use for service interfaces.
    |
    */
    'services' => [
        'data_service' => env('RECORDING_DATA_SERVICE', \App\Services\Recording\RecordingDataService::class),
        'validation_service' => env('RECORDING_VALIDATION_SERVICE', \App\Services\Recording\RecordingValidationService::class),
        'calculation_service' => env('RECORDING_CALCULATION_SERVICE', \App\Services\Recording\RecordingCalculationService::class),
        'persistence_service' => env('RECORDING_PERSISTENCE_SERVICE', \App\Services\Recording\RecordingPersistenceService::class),
        'integration_service' => env('RECORDING_INTEGRATION_SERVICE', \App\Services\Recording\RecordsIntegrationService::class),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure caching behavior for recording operations.
    |
    */
    'cache' => [
        'store' => env('RECORDING_CACHE_STORE', 'redis'),
        'prefix' => env('RECORDING_CACHE_PREFIX', 'recording:'),
        'tags' => [
            'validation' => 'recording:validation',
            'calculations' => 'recording:calculations',
            'livestock_data' => 'recording:livestock',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Configure queue settings for background processing.
    |
    */
    'queue' => [
        'connection' => env('RECORDING_QUEUE_CONNECTION', 'redis'),
        'queue' => env('RECORDING_QUEUE_NAME', 'recordings'),
        'retry_after' => env('RECORDING_QUEUE_RETRY_AFTER', 90), // seconds
        'max_tries' => env('RECORDING_QUEUE_MAX_TRIES', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Development & Testing
    |--------------------------------------------------------------------------
    |
    | Settings for development and testing environments.
    |
    */
    'development' => [
        'debug_mode' => env('RECORDING_DEBUG', false),
        'fake_external_services' => env('RECORDING_FAKE_EXTERNAL', false),
        'test_data_enabled' => env('RECORDING_TEST_DATA', false),
        'benchmark_enabled' => env('RECORDING_BENCHMARK', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Settings
    |--------------------------------------------------------------------------
    |
    | Settings for migrating from legacy to modular architecture.
    |
    */
    'migration' => [
        'enabled' => env('RECORDING_MIGRATION_ENABLED', false),
        'rollout_percentage' => env('RECORDING_ROLLOUT_PERCENTAGE', 0), // 0-100
        'user_whitelist' => env('RECORDING_USER_WHITELIST', ''), // comma-separated user IDs
        'company_whitelist' => env('RECORDING_COMPANY_WHITELIST', ''), // comma-separated company IDs
        'dry_run_mode' => env('RECORDING_DRY_RUN', false),
    ],
];

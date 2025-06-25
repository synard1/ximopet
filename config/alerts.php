<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Alert System Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the comprehensive alert system.
    | You can configure recipients, channels, throttling, and other settings.
    |
    */

    'enabled' => env('ALERTS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Default Mail Class
    |--------------------------------------------------------------------------
    |
    | The default mail class to use when no specific mail class is provided
    | for an alert type. This should be a generic alert mail class.
    |
    */

    'default_mail_class' => \App\Mail\Alert\GenericAlert::class,

    /*
    |--------------------------------------------------------------------------
    | Alert Recipients
    |--------------------------------------------------------------------------
    |
    | Configure email recipients for different types of alerts.
    | You can specify different recipients for different alert categories.
    |
    */

    'recipients' => [
        'default' => [
            env('ALERT_DEFAULT_EMAIL', 'admin@example.com'),
        ],

        'feed_stats' => [
            env('ALERT_FEED_STATS_EMAIL', 'admin@example.com'),
            // 'manager@example.com',
            // 'supervisor@example.com',
        ],

        'feed_usage' => [
            env('ALERT_FEED_USAGE_EMAIL', 'admin@example.com'),
            // 'operator@example.com',
        ],

        'anomaly' => [
            env('ALERT_ANOMALY_EMAIL', 'admin@example.com'),
            // 'analyst@example.com',
        ],

        'critical' => [
            env('ALERT_CRITICAL_EMAIL', 'admin@example.com'),
            // 'cto@example.com',
            // 'emergency@example.com',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Alert Channels
    |--------------------------------------------------------------------------
    |
    | Configure which channels should be used by default for alerts.
    | Available channels: email, log, database, slack, sms
    |
    */

    'default_channels' => [
        'email',
        'log',
        'database',
    ],

    /*
    |--------------------------------------------------------------------------
    | Channel Configuration
    |--------------------------------------------------------------------------
    |
    | Configure specific settings for each alert channel.
    |
    */

    'channels' => [
        'email' => [
            'enabled' => env('ALERT_EMAIL_ENABLED', true),
            'from' => [
                'address' => env('ALERT_FROM_EMAIL', env('MAIL_FROM_ADDRESS')),
                'name' => env('ALERT_FROM_NAME', env('MAIL_FROM_NAME', 'Feed Management System')),
            ],
        ],

        'log' => [
            'enabled' => env('ALERT_LOG_ENABLED', true),
            'channel' => env('ALERT_LOG_CHANNEL', 'single'),
        ],

        'database' => [
            'enabled' => env('ALERT_DATABASE_ENABLED', true),
            'retention_days' => env('ALERT_DATABASE_RETENTION_DAYS', 90),
        ],

        'slack' => [
            'enabled' => env('ALERT_SLACK_ENABLED', false),
            'webhook_url' => env('ALERT_SLACK_WEBHOOK_URL'),
            'channel' => env('ALERT_SLACK_CHANNEL', '#alerts'),
            'username' => env('ALERT_SLACK_USERNAME', 'Feed Alert Bot'),
        ],

        'sms' => [
            'enabled' => env('ALERT_SMS_ENABLED', false),
            'provider' => env('ALERT_SMS_PROVIDER', 'twilio'),
            'critical_only' => env('ALERT_SMS_CRITICAL_ONLY', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Alert Level Configuration
    |--------------------------------------------------------------------------
    |
    | Configure behavior for different alert levels.
    |
    */

    'levels' => [
        'info' => [
            'channels' => ['log', 'database'],
            'throttle_minutes' => 5,
        ],

        'warning' => [
            'channels' => ['email', 'log', 'database'],
            'throttle_minutes' => 15,
        ],

        'error' => [
            'channels' => ['email', 'log', 'database'],
            'throttle_minutes' => 30,
        ],

        'critical' => [
            'channels' => ['email', 'log', 'database', 'slack'],
            'throttle_minutes' => 60,
            'additional_recipients' => 'critical',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Alert Type Configuration
    |--------------------------------------------------------------------------
    |
    | Configure specific settings for different alert types.
    | This section is now more generic and extensible.
    |
    */

    'types' => [
        // Feed-specific types are now handled by FeedAlertService
        // This section can be used for system-wide alert type configurations
        
        'system_error' => [
            'level' => 'error',
            'channels' => ['email', 'log', 'database'],
            'recipients' => 'default',
            'throttle_minutes' => 30,
        ],

        'data_integrity_warning' => [
            'level' => 'warning',
            'channels' => ['email', 'log', 'database'],
            'recipients' => 'default',
            'throttle_minutes' => 60,
        ],

        'user_activity' => [
            'level' => 'info',
            'channels' => ['log', 'database'],
            'recipients' => 'default',
            'throttle_minutes' => 5,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Throttling Configuration
    |--------------------------------------------------------------------------
    |
    | Configure default throttling settings to prevent alert spam.
    |
    */

    'throttling' => [
        'enabled' => env('ALERT_THROTTLING_ENABLED', true),
        'default_minutes' => env('ALERT_THROTTLING_DEFAULT_MINUTES', 15),
        'cache_prefix' => 'alert_throttle_',
    ],

    /*
    |--------------------------------------------------------------------------
    | Template Configuration
    |--------------------------------------------------------------------------
    |
    | Configure email template settings.
    |
    */

    'templates' => [
        'feed_stats' => 'emails.alerts.feed-stats',
        'feed_usage' => 'emails.alerts.feed-usage',
        'default' => 'emails.alerts.default',
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring Configuration
    |--------------------------------------------------------------------------
    |
    | Configure alert monitoring and health checks.
    |
    */

    'monitoring' => [
        'enabled' => env('ALERT_MONITORING_ENABLED', true),
        'health_check_interval' => env('ALERT_HEALTH_CHECK_INTERVAL', 60), // minutes
        'max_failed_alerts' => env('ALERT_MAX_FAILED_ALERTS', 5),
        'alert_on_system_failure' => env('ALERT_ON_SYSTEM_FAILURE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Feed Usage Alert Configuration
    |--------------------------------------------------------------------------
    |
    | Specific configuration for feed usage monitoring.
    |
    */

    'feed_usage' => [
        'alert_on_create' => env('ALERT_FEED_USAGE_CREATE', true),
        'alert_on_update' => env('ALERT_FEED_USAGE_UPDATE', true),
        'alert_on_delete' => env('ALERT_FEED_USAGE_DELETE', true),
        'alert_on_large_quantity' => env('ALERT_FEED_LARGE_QUANTITY', true),
        'large_quantity_threshold' => env('ALERT_FEED_LARGE_QUANTITY_THRESHOLD', 1000), // kg
        'alert_on_high_cost' => env('ALERT_FEED_HIGH_COST', true),
        'high_cost_threshold' => env('ALERT_FEED_HIGH_COST_THRESHOLD', 10000000), // IDR
    ],

    /*
    |--------------------------------------------------------------------------
    | Feed Stats Alert Configuration
    |--------------------------------------------------------------------------
    |
    | Specific configuration for feed stats monitoring.
    |
    */

    'feed_stats' => [
        'auto_check_enabled' => env('ALERT_FEED_STATS_AUTO_CHECK', true),
        'check_interval' => env('ALERT_FEED_STATS_CHECK_INTERVAL', 60), // minutes
        'tolerance_threshold' => env('ALERT_FEED_STATS_TOLERANCE', 0.01), // tolerance for floating point comparison
        'alert_on_discrepancy' => env('ALERT_FEED_STATS_DISCREPANCY', true),
        'auto_fix_enabled' => env('ALERT_FEED_STATS_AUTO_FIX', false),
    ],
];

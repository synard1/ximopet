<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Temporary Authorization Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for temporary authorization feature that allows
    | modification of locked/readonly data
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Authorization Mode
    |--------------------------------------------------------------------------
    |
    | Supported modes: "password", "user", "mixed"
    | - password: Only password-based authorization
    | - user: Only user-based authorization (requires authorized users)
    | - mixed: Both password and user authorization available
    |
    */
    'mode' => env('TEMP_AUTH_MODE', 'mixed'),

    /*
    |--------------------------------------------------------------------------
    | Password-Based Authorization
    |--------------------------------------------------------------------------
    |
    | Configuration for password-based authorization.
    |
    */
    'password' => [
        'enabled' => env('TEMP_AUTH_PASSWORD_ENABLED', true),
        'default_password' => env('TEMP_AUTH_PASSWORD', 'admin123'),
    ],

    /*
    |--------------------------------------------------------------------------
    | User-Based Authorization
    |--------------------------------------------------------------------------
    |
    | Configuration for user-based authorization.
    |
    */
    'user' => [
        'enabled' => env('TEMP_AUTH_USER_ENABLED', true),
        'model' => env('TEMP_AUTH_USER_MODEL', 'App\Models\User'),
        'identifier_field' => 'email', // or 'username', 'employee_id', etc.
        'require_password' => env('TEMP_AUTH_USER_REQUIRE_PASSWORD', true),

        // Method to determine authorized users
        'authorization_method' => env('TEMP_AUTH_USER_METHOD', 'role'), // 'role', 'permission', 'database_field'

        // For role-based authorization
        'authorized_roles' => [
            'Super Admin',
            'Manager',
            'Supervisor',
        ],

        // For permission-based authorization
        'authorized_permissions' => [
            'grant temp authorization',
            'override data locks',
        ],

        // For database field authorization (if using custom field)
        'database_field' => 'can_authorize_temp_access', // boolean field in users table
    ],

    // Default duration in minutes for temporary authorization
    'default_duration' => env('TEMP_AUTH_DURATION', 30),

    // Maximum number of concurrent temporary authorizations per user
    'max_concurrent_auths' => env('TEMP_AUTH_MAX_CONCURRENT', 1),

    // Roles that can request temporary authorization
    'allowed_roles' => [
        'Admin',
        'Supervisor',
        'Manager'
    ],

    // Permissions that can bypass temp auth requirement
    'bypass_permissions' => [
        'super admin',
        'override temp auth',
        'bypass temp authorization'
    ],

    // Sessions cleanup interval in minutes
    'cleanup_interval' => env('TEMP_AUTH_CLEANUP_INTERVAL', 60),

    // Advanced security settings
    'security' => [
        // Require reason for authorization
        'require_reason' => env('TEMP_AUTH_REQUIRE_REASON', true),

        // Maximum reason length
        'max_reason_length' => 500,

        // Enable audit trail
        'audit_trail' => env('TEMP_AUTH_AUDIT', true),

        // Notification settings
        'notifications' => [
            // Send notification on auth grant
            'on_grant' => env('TEMP_AUTH_NOTIFY_GRANT', false),

            // Send notification on auth revoke
            'on_revoke' => env('TEMP_AUTH_NOTIFY_REVOKE', false),

            // Notification recipients (emails)
            'recipients' => explode(',', env('TEMP_AUTH_NOTIFY_EMAILS', '')),
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging & Audit
    |--------------------------------------------------------------------------
    |
    | Audit trail and logging configuration.
    |
    */
    'audit' => [
        'enabled' => env('TEMP_AUTH_AUDIT_ENABLED', true),
        'log_channel' => env('TEMP_AUTH_LOG_CHANNEL', 'daily'),
        'store_in_database' => env('TEMP_AUTH_STORE_DB', true),
    ],
];

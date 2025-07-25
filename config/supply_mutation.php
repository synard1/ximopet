<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Supply Mutation Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration settings for Supply Mutation module including validation,
    | workflow management, performance settings, and business rules.
    | This configuration is designed to be easily migratable to database storage.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Core Settings
    |--------------------------------------------------------------------------
    |
    | Basic configuration for supply mutation functionality.
    |
    */
    'enabled' => env('SUPPLY_MUTATION_ENABLED', true),
    'version' => '1.0.0',
    'debug_mode' => env('SUPPLY_MUTATION_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Mutation Types
    |--------------------------------------------------------------------------
    |
    | Supported mutation types and their configurations.
    |
    */
    'mutation_types' => [
        'batch' => [
            'enabled' => false,
            'name' => 'Batch Mutation',
            'description' => 'Standard batch-based mutation with manual selection',
            'default' => false,
        ],
        'fifo' => [
            'enabled' => true,
            'name' => 'FIFO Mutation',
            'description' => 'First In First Out automatic stock selection',
            'default' => true,
        ],
        'lifo' => [
            'enabled' => false,
            'name' => 'LIFO Mutation',
            'description' => 'Last In First Out automatic stock selection',
            'default' => false,
        ],
        'manual' => [
            'enabled' => false,
            'name' => 'Manual Selection',
            'description' => 'Manual stock selection with full control',
            'default' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Destination Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for flexible destination handling (coop vs livestock).
    |
    */
    'destination' => [
        'allow_coop_destination' => true,
        'allow_livestock_destination' => true,
        'allow_farm_destination' => true,
        'require_destination_validation' => true,
        'allow_same_source_destination' => false,
        'max_destination_distance_km' => 100, // For validation if needed
        'auto_validate_destination_capacity' => true,
        'destination_validation_rules' => [
            'check_coop_capacity' => true,
            'check_livestock_health' => true,
            'check_farm_availability' => true,
            'validate_transfer_restrictions' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    |
    | Comprehensive validation rules for supply mutations.
    |
    */
    'validation' => [
        'enable_real_time_validation' => true,
        'enable_stock_validation' => true,
        'enable_quantity_validation' => true,
        'enable_date_validation' => true,
        'enable_unit_validation' => true,

        'quantity' => [
            'min_quantity' => 0.01,
            'max_quantity' => 999999.99,
            'allow_decimal' => true,
            'decimal_places' => 2,
            'require_positive' => true,
            'max_mutation_percentage' => 100, // Max % of available stock
            'allow_partial_mutation' => true,
            'min_stock_remaining' => 0, // Minimum stock to leave behind
        ],

        'date' => [
            'allow_future_dates' => false,
            'allow_past_dates' => true,
            'max_days_in_past' => 365,
            'require_business_days' => false,
            'allow_weekends' => true,
            'allow_holidays' => true,
        ],

        'stock' => [
            'require_sufficient_stock' => true,
            'check_expiry_dates' => true,
            'warn_expiring_soon_days' => 30,
            'prevent_expired_stock_mutation' => true,
            'allow_zero_stock_mutation' => false,
            'validate_stock_ownership' => true,
        ],

        'unit' => [
            'require_unit_conversion' => true,
            'allow_cross_unit_mutation' => true,
            'validate_conversion_rates' => true,
            'auto_convert_quantities' => true,
            'precision_decimal_places' => 4,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Workflow Settings
    |--------------------------------------------------------------------------
    |
    | Approval workflow and status management.
    |
    */
    'workflow' => [
        'require_approval' => env('SUPPLY_MUTATION_REQUIRE_APPROVAL', false),
        'auto_approve_small_quantities' => true,
        'auto_approve_threshold' => 100, // Quantity threshold for auto-approval
        'require_approval_for_large_quantities' => true,
        'large_quantity_threshold' => 1000,
        'require_approval_for_high_value' => true,
        'high_value_threshold' => 1000000, // In base currency

        // Bypass Approval Configuration
        'bypass_approval' => [
            'enabled' => env('SUPPLY_MUTATION_BYPASS_APPROVAL', true),
            'require_verification' => env('SUPPLY_MUTATION_REQUIRE_VERIFICATION', false), // Changed to false
            'allow_verification_later' => true, // New: Allow verification after completion
            'bypass_roles' => [
                'admin',
                'supervisor',
                'operator',
                'manager'
            ],
            'bypass_quantity_threshold' => 500, // Quantity below this can be bypassed
            'bypass_value_threshold' => 500000, // Value below this can be bypassed
            'bypass_conditions' => [
                'same_farm_mutation' => true, // Allow bypass for same farm mutations
                'small_quantity' => true, // Allow bypass for small quantities
                'emergency_mutation' => true, // Allow bypass for emergency mutations
                'verified_data' => false, // Changed to false - verification not required for bypass
            ],
        ],

        // Verification Configuration
        'verification' => [
            'enabled' => env('SUPPLY_MUTATION_VERIFICATION_ENABLED', true),
            'require_verification_for_bypass' => false, // Changed to false
            'allow_verification_after_completion' => true, // New: Allow verification after completion
            'verification_roles' => [
                'admin',
                'supervisor',
                'operator'
            ],
            'verification_required_fields' => [
                'quantity',
                'unit_conversion',
                'source_stock',
                'destination_validity',
                'date_validity'
            ],
            'verification_notes_required' => false, // Changed to false
            'verification_expiry_hours' => 24, // Verification expires after 24 hours
        ],

        'approval_levels' => [
            'level_1' => [
                'enabled' => true,
                'role' => 'supervisor',
                'threshold' => 1000,
            ],
            'level_2' => [
                'enabled' => true,
                'role' => 'manager',
                'threshold' => 5000,
            ],
            'level_3' => [
                'enabled' => true,
                'role' => 'director',
                'threshold' => null, // No limit
            ],
        ],

        'status_flow' => [
            'draft' => ['pending', 'verified', 'completed', 'cancelled'], // Added completed
            'pending' => ['approved', 'rejected', 'verified', 'cancelled'], // Added verified
            'verified' => ['completed', 'cancelled'],
            'approved' => ['completed', 'verified', 'cancelled'], // Added verified
            'rejected' => ['draft', 'cancelled'],
            'completed' => ['verified', 'cancelled'], // Added verified - allow verification after completion
            'completed' => ['cancelled'], // Only cancellation allowed
            'cancelled' => [], // Terminal state
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Batch Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for batch tracking and management.
    |
    */
    'batch' => [
        'tracking_enabled' => true,
        'require_batch_number' => true,
        'auto_generate_batch' => true,
        'batch_number_format' => 'YEAR-SEQ', // YEAR-SEQ, UUID, CUSTOM
        'custom_batch_prefix' => 'MUT',
        'batch_number_sequence' => [
            'start_from' => 1,
            'padding' => 6,
            'prefix' => '',
            'suffix' => '',
        ],
        'batch_validation' => [
            'require_expiry_date' => false,
            'require_manufacturing_date' => false,
            'require_supplier_info' => false,
            'require_certificate' => false,
        ],
        'batch_tracking' => [
            'track_quantity_by_batch' => true,
            'track_cost_by_batch' => true,
            'track_location_by_batch' => true,
            'track_history_by_batch' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Document Settings
    |--------------------------------------------------------------------------
    |
    | Document and attachment requirements.
    |
    */
    'documents' => [
        'require_do_number' => false,
        'require_invoice' => false,
        'require_receipt' => false,
        'require_approval_document' => false,
        'require_photos' => false,
        'max_photo_count' => 5,
        'allowed_photo_types' => ['jpg', 'jpeg', 'png', 'pdf'],
        'max_file_size_mb' => 10,
        'document_number_format' => 'MUT-{YEAR}-{SEQ}',
        'auto_generate_document_number' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    |
    | Performance optimization and caching settings.
    |
    */
    'performance' => [
        'enable_caching' => env('SUPPLY_MUTATION_ENABLE_CACHING', true),
        'cache_ttl_seconds' => env('SUPPLY_MUTATION_CACHE_TTL', 3600),
        'batch_size' => env('SUPPLY_MUTATION_BATCH_SIZE', 100),
        'use_background_jobs' => env('SUPPLY_MUTATION_USE_BACKGROUND_JOBS', false),
        'background_job_timeout' => 300, // seconds
        'max_concurrent_jobs' => 5,
        'enable_query_optimization' => true,
        'enable_eager_loading' => true,
        'enable_database_indexing' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit and Logging
    |--------------------------------------------------------------------------
    |
    | Audit trail and logging configuration.
    |
    */
    'audit' => [
        'enable_audit_trail' => true,
        'log_all_changes' => true,
        'log_user_actions' => true,
        'log_system_actions' => true,
        'retention_days' => 2555, // 7 years
        'audit_fields' => [
            'quantity',
            'destination',
            'status',
            'approval',
            'notes',
            'metadata',
        ],
        'audit_events' => [
            'created',
            'updated',
            'deleted',
            'approved',
            'rejected',
            'completed',
            'cancelled',
        ],
    ],

    'logging' => [
        'enable_debug_logging' => env('SUPPLY_MUTATION_DEBUG_LOGGING', false),
        'enable_performance_logging' => env('SUPPLY_MUTATION_PERFORMANCE_LOGGING', true),
        'enable_error_logging' => true,
        'enable_validation_logging' => true,
        'log_stock_changes' => true,
        'log_status_changes' => true,
        'log_user_actions' => true,
        'log_performance_metrics' => true,
        'log_channel' => 'supply_mutation',
    ],

    /*
    |--------------------------------------------------------------------------
    | Business Rules
    |--------------------------------------------------------------------------
    |
    | Business logic and rule configurations.
    |
    */
    'business_rules' => [
        'allow_cross_farm_mutation' => true,
        'allow_cross_company_mutation' => false,
        'require_farm_approval' => false,
        'require_coop_approval' => false,
        'require_livestock_approval' => false,
        'allow_emergency_mutation' => true,
        'emergency_mutation_roles' => ['admin', 'supervisor'],
        'allow_scheduled_mutation' => true,
        'allow_recurring_mutation' => false,
        'mutation_schedule_advance_days' => 7,

        'cost_tracking' => [
            'enable_cost_tracking' => true,
            'track_fifo_cost' => true,
            'track_average_cost' => true,
            'track_standard_cost' => false,
            'auto_update_cost' => true,
        ],

        'inventory_impact' => [
            'update_source_stock' => true,
            'update_destination_stock' => true,
            'update_current_supply' => true,
            'update_supply_stock' => true,
            'recalculate_averages' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    |
    | Notification and alert configurations.
    |
    */
    'notifications' => [
        'enable_notifications' => true,
        'notify_on_creation' => true,
        'notify_on_approval' => true,
        'notify_on_rejection' => true,
        'notify_on_completion' => true,
        'notify_on_cancellation' => true,
        'notify_low_stock' => true,
        'notify_expiring_stock' => true,

        'notification_channels' => [
            'database' => true,
            'email' => true,
            'sms' => false,
            'push' => false,
            'slack' => false,
        ],

        'notification_roles' => [
            'creator' => true,
            'approver' => true,
            'supervisor' => true,
            'manager' => true,
            'admin' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Security and permission configurations.
    |
    */
    'security' => [
        'require_authentication' => true,
        'require_authorization' => true,
        'enable_rate_limiting' => true,
        'rate_limit_requests_per_minute' => 60,
        'enable_csrf_protection' => true,
        'enable_input_sanitization' => true,
        'enable_sql_injection_protection' => true,
        'enable_xss_protection' => true,

        'permissions' => [
            'create' => 'create supply mutation',
            'read' => 'read supply mutation',
            'update' => 'update supply mutation',
            'delete' => 'delete supply mutation',
            'approve' => 'approve supply mutation',
            'reject' => 'reject supply mutation',
            'cancel' => 'cancel supply mutation',
        ],

        'roles' => [
            'creator' => ['create', 'read', 'update'],
            'approver' => ['read', 'approve', 'reject'],
            'admin' => ['create', 'read', 'update', 'delete', 'approve', 'reject', 'cancel'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Integration Settings
    |--------------------------------------------------------------------------
    |
    | External system integration configurations.
    |
    */
    'integrations' => [
        'enable_api_access' => true,
        'enable_webhook_notifications' => false,
        'enable_third_party_integration' => false,
        'enable_erp_integration' => false,
        'enable_accounting_integration' => false,
        'enable_inventory_integration' => true,

        'api' => [
            'rate_limit' => 1000,
            'version' => 'v1',
            'enable_documentation' => true,
            'require_api_key' => true,
            'enable_oauth' => false,
        ],

        'webhooks' => [
            'mutation_created' => false,
            'mutation_approved' => false,
            'mutation_completed' => false,
            'mutation_cancelled' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Settings
    |--------------------------------------------------------------------------
    |
    | Settings for future database migration.
    |
    */
    'migration' => [
        'version' => '1.0.0',
        'database_table' => 'supply_mutation_configs',
        'company_specific' => true,
        'user_editable' => true,
        'admin_editable' => true,
        'backup_before_migration' => true,
        'validate_after_migration' => true,
        'rollback_enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Feature toggle configurations for gradual rollout.
    |
    */
    'features' => [
        'flexible_destination' => true,
        'batch_tracking' => true,
        'approval_workflow' => true,
        'audit_trail' => true,
        'cost_tracking' => true,
        'document_management' => true,
        'notification_system' => true,
        'api_integration' => true,
        'background_processing' => true,
        'caching_system' => true,
        'performance_monitoring' => true,
        'advanced_validation' => true,
        'business_rules_engine' => true,
        'reporting_analytics' => true,
    ],
];

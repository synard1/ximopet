<?php

return [
    'version' => '1.0',
    'last_updated' => '2025-06-23',
    'config_levels' => [
        'system' => [
            'description' => 'Core application settings managed by developers',
            'modifiable_by' => ['developer', 'system_admin'],
            'requires_deployment' => true,
        ],
        'company' => [
            'description' => 'Company-wide settings that can be configured by company admins',
            'modifiable_by' => ['company_admin', 'system_admin'],
            'requires_deployment' => false,
        ],
        'user' => [
            'description' => 'User-level preferences and operational settings',
            'modifiable_by' => ['user', 'company_admin', 'system_admin'],
            'requires_deployment' => false,
        ]
    ],
    'validation_rules' => [
        'user_config_changes' => [
            'must_validate_against_allowed_values' => true,
            'must_check_method_availability' => true,
            'must_log_changes' => true,
            'must_backup_before_change' => true,
        ]
    ]
];

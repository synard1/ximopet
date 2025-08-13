<?php

return [
    'livestock_purchase' => [
        'enabled' => true,
        'validation_rules' => [
            'require_strain' => true,
            'require_strain_standard' => false,
            'require_initial_weight' => true,
            'require_initial_price' => true,
            'require_supplier' => true,
            'require_expedition' => false,
            'require_do_number' => false,
            'require_invoice' => true,
        ],
        'batch_creation' => [
            'auto_create_batch' => true,
            'batch_naming' => 'auto',
            'batch_naming_format' => 'PR-{FARM}-{COOP}-{DATE}',
            'require_batch_name' => false,
        ],
        'strain_validation' => [
            'require_strain_selection' => true,
            'allow_multiple_strains' => false,
            'strain_standard_optional' => true,
            'validate_strain_availability' => true,
        ],
        'cost_tracking' => [
            'enabled' => true,
            'include_transport_cost' => true,
            'include_tax' => true,
            'track_unit_cost' => true,
            'track_total_cost' => true,
        ],
        'batch_settings' => [
            'enabled' => true,
            'tracking_enabled' => false,
            'history_enabled' => false,
            'allow_multiple_batches' => [
                'enabled' => false,
                'max_batches' => 3,
                'depletion_method' => 'fifo',
                'depletion_method_fifo' => [
                    'enabled' => true,
                    'track_age' => true,
                    'min_age_days' => 0,
                    'max_age_days' => null,
                ],
                'depletion_method_manual' => [
                    'enabled' => false,
                    'track_age' => false,
                    'min_age_days' => 0,
                    'max_age_days' => null,
                ],
            ],
        ],
        'approval_settings' => [
            'require_approval' => false,
            'approval_levels' => 1,
            'approval_roles' => ['admin', 'manager'],
            'auto_approve_small_amounts' => true,
            'auto_approve_threshold' => 1000000,
        ],
        'document_settings' => [
            'require_do_number' => false,
            'require_invoice' => true,
            'require_receipt' => false,
            'require_delivery_note' => false,
        ],
    ],
    'feed_purchase' => [
        'enabled' => true,
        'validation_rules' => [
            'require_supplier' => true,
            'require_price' => true,
            'require_quantity' => true,
            'require_unit' => true,
        ],
        'batch_settings' => [
            'enabled' => true,
            'tracking_enabled' => true,
            'history_enabled' => true,
            'require_batch_number' => true,
            'auto_generate_batch' => [
                'enabled' => true,
                'format' => 'YEAR-SEQ',
            ],
        ],
        'document_settings' => [
            'require_do_number' => true,
            'require_invoice' => true,
            'require_receipt' => true,
        ],
    ],
    'supply_purchase' => [
        'enabled' => true,
        'validation_rules' => [
            'require_supplier' => true,
            'require_price' => true,
            'require_quantity' => true,
            'require_unit' => true,
        ],
        'batch_settings' => [
            'enabled' => true,
            'tracking_enabled' => true,
            'history_enabled' => true,
            'require_batch_number' => true,
            'auto_generate_batch' => [
                'enabled' => true,
                'format' => 'YEAR-SEQ',
            ],
        ],
        'document_settings' => [
            'require_do_number' => true,
            'require_invoice' => true,
            'require_receipt' => true,
        ],
    ],
];

<?php

return [
    'livestock_sales' => [
        'enabled' => true,
        'recording_method' => [
            'type' => 'two_stage',
            'description' => 'Two-stage sales: draft recording -> final sales',
            'stages' => [
                'draft' => [
                    'enabled' => true,
                    'description' => 'Operator input temporary sales data',
                    'auto_save' => true,
                    'allow_edit' => true,
                    'require_validation' => false,
                ],
                'final' => [
                    'enabled' => true,
                    'description' => 'Finalized sales with actual stock deduction',
                    'require_approval' => false,
                    'auto_finalize' => false,
                    'finalization_delay_hours' => 24,
                ],
            ],
        ],
        'quantity_calculation' => [
            'mode' => 'virtual',
            'virtual_settings' => [
                'enabled' => true,
                'description' => 'Virtual quantity calculation for recording without affecting real stock',
                'store_in_data_column' => true,
                'data_column_structure' => [
                    'virtual_sales' => [
                        'quantity' => 'int',
                        'weight' => 'decimal',
                        'date' => 'date',
                        'status' => 'string',
                        'metadata' => 'json'
                    ],
                    'virtual_depletion' => [
                        'mortality' => 'int',
                        'culling' => 'int',
                        'date' => 'date',
                        'status' => 'string',
                        'metadata' => 'json'
                    ]
                ],
                'calculation_method' => 'projection',
                'projection_settings' => [
                    'enabled' => true,
                    'base_on_historical_data' => true,
                    'historical_days' => 7,
                    'growth_rate_percentage' => 0,
                    'seasonal_adjustment' => false,
                ],
                'estimate_settings' => [
                    'enabled' => false,
                    'accuracy_threshold' => 95,
                    'confidence_level' => 90,
                ],
                'forecast_settings' => [
                    'enabled' => false,
                    'forecast_period_days' => 30,
                    'trend_analysis' => true,
                ],
            ],
            'real_settings' => [
                'enabled' => false,
                'description' => 'Real quantity calculation affecting actual stock',
                'immediate_stock_reduction' => true,
                'require_confirmation' => true,
            ],
            'hybrid_settings' => [
                'enabled' => false,
                'description' => 'Combination of virtual and real calculation',
                'virtual_for_recording' => true,
                'real_for_finalization' => true,
                'transition_threshold' => 'finalization',
            ],
        ],
        'batch_allocation' => [
            'method' => 'fifo',
            'fifo_settings' => [
                'enabled' => true,
                'prefer_older_batches' => true,
                'min_age_days' => 25,
                'min_weight_kg' => 1.0,
                'max_age_days' => null,
                'max_weight_kg' => 10.0,
                'track_age' => true,
            ],
            'lifo_settings' => [
                'enabled' => false,
                'prefer_newer_batches' => true,
                'track_age' => false,
            ],
            'manual_settings' => [
                'enabled' => false,
                'allow_batch_selection' => true,
                'require_batch_confirmation' => true,
            ],
            'weight_based_settings' => [
                'enabled' => false,
                'prefer_similar_weight' => true,
                'weight_tolerance_percent' => 10,
            ],
        ],
        'validation_rules' => [
            'require_quantity' => true,
            'require_weight' => false,
            'require_price' => false,
            'require_customer' => false,
            'min_quantity' => 1,
            'max_quantity_per_sale' => 10000,
            'weight_validation' => [
                'enabled' => true,
                'min_weight_per_unit' => 0.1,
                'max_weight_per_unit' => 10.0,
                'allow_zero_weight' => true,
            ],
            'price_validation' => [
                'enabled' => false,
                'min_price_per_unit' => 0,
                'max_price_per_unit' => 1000000,
                'require_positive_price' => false,
            ],
        ],
        'customer_management' => [
            'require_customer_info' => false,
            'customer_fields' => [
                'name' => false,
                'phone' => false,
                'address' => false,
                'tax_id' => false,
            ],
            'default_customer' => [
                'name' => 'Cash Customer',
                'id' => null,
            ],
        ],
        'pricing' => [
            'pricing_method' => 'manual',
            'market_price_integration' => [
                'enabled' => false,
                'api_endpoint' => '',
                'update_frequency' => 'daily',
            ],
            'cost_plus_settings' => [
                'enabled' => false,
                'markup_percentage' => 20,
                'include_all_costs' => true,
            ],
            'fixed_price_settings' => [
                'enabled' => false,
                'price_per_kg' => 0,
                'price_per_unit' => 0,
            ],
        ],
        'documentation' => [
            'auto_generate_invoice' => false,
            'invoice_template' => 'default',
            'require_invoice_number' => false,
            'invoice_numbering' => [
                'enabled' => true,
                'format' => 'INV-{YEAR}-{SEQ:4}',
                'reset_rule' => 'yearly',
            ],
            'require_delivery_note' => false,
            'delivery_note_template' => 'default',
        ],
        'workflow' => [
            'approval_required' => false,
            'approval_levels' => 1,
            'approval_roles' => ['admin', 'manager'],
            'auto_approve_small_sales' => true,
            'auto_approve_threshold' => 1000000,
            'notification_settings' => [
                'notify_on_draft' => false,
                'notify_on_finalize' => true,
                'notify_approvers' => true,
                'notify_customers' => false,
            ],
        ],
        'reporting' => [
            'track_sales_performance' => true,
            'track_customer_performance' => false,
            'track_batch_performance' => true,
            'sales_analytics' => [
                'daily_sales_summary' => true,
                'monthly_sales_report' => true,
                'customer_sales_report' => false,
                'batch_sales_report' => true,
            ],
        ],
        'integration' => [
            'accounting_system' => [
                'enabled' => false,
                'auto_create_journal' => false,
                'journal_template' => 'default',
            ],
            'inventory_system' => [
                'enabled' => true,
                'auto_update_stock' => true,
                'stock_reduction_method' => 'fifo',
            ],
        ],
        'performance' => [
            'enable_caching' => true,
            'cache_ttl_seconds' => 3600,
            'batch_processing' => [
                'enabled' => false,
                'batch_size' => 100,
            ],
        ],
    ],
    'sales_recording' => [
        'enabled' => true,
        'recording_method' => [
            'type' => 'integrated',
            'description' => 'Sales recording integrated with daily recording',
            'auto_create_draft' => true,
            'auto_finalize' => false,
        ],
        'data_structure' => [
            'store_batch_breakdown' => true,
            'store_customer_info' => false,
            'store_pricing_info' => false,
            'store_metadata' => true,
        ],
        'validation' => [
            'validate_stock_availability' => true,
            'validate_batch_availability' => true,
            'prevent_over_selling' => true,
            'allow_partial_sales' => false,
        ],
    ],
    'sales_history' => [
        'enabled' => true,
        'retention_policy' => [
            'keep_draft_sales_days' => 30,
            'keep_finalized_sales_years' => 7,
            'archive_old_sales' => true,
            'archive_after_days' => 365,
        ],
        'audit_trail' => [
            'enabled' => true,
            'track_changes' => true,
            'track_user_actions' => true,
            'max_history_entries' => 1000,
        ],
    ],
];

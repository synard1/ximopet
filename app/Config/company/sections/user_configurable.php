<?php

return [
    'livestock' => [
        'user_editable_paths' => [
            'recording_method.batch_settings.depletion_method_default',
            'recording_method.batch_settings.mutation_method_default',
            'recording_method.batch_settings.feed_usage_method_default',
            'lifecycle_management.enabled',
            'health_management.vaccination_tracking.enabled',
            'health_management.disease_tracking.enabled',
            'performance_metrics.enabled',
            'cost_tracking.enabled',
            'reporting.enabled',
            'validation_rules.require_farm',
            'validation_rules.require_coop',
            'validation_rules.min_quantity',
            'validation_rules.max_quantity',
            'feed_usage.methods.manual.validation_rules.require_usage_date',
            'feed_usage.methods.manual.validation_rules.require_usage_purpose',
            'feed_usage.methods.manual.validation_rules.min_quantity',
            'feed_usage.methods.manual.validation_rules.max_quantity',
            'feed_usage.methods.manual.input_restrictions.allow_same_day_repeated_input',
            'feed_usage.methods.manual.input_restrictions.max_usage_per_day_per_batch',
            'feed_usage.methods.manual.workflow_settings.enable_preview_step',
            'feed_usage.methods.manual.workflow_settings.require_confirmation',
        ],
        'user_editable_values' => [
            'recording_method.batch_settings.depletion_method_default' => ['fifo', 'manual'],
            'recording_method.batch_settings.depletion_methods.fifo.quantity_distribution.method' => ['sequential', 'proportional', 'balanced'],
            'recording_method.batch_settings.depletion_methods.fifo.quantity_distribution.max_batches_per_operation' => [1, 2, 3, 4, 5],
            'recording_method.batch_settings.mutation_method_default' => ['fifo'],
            'recording_method.batch_settings.feed_usage_method_default' => ['fifo', 'manual'],
        ]
    ],
    'purchasing' => [
        'user_editable_paths' => [
            'livestock_purchase.validation_rules.require_strain',
            'livestock_purchase.validation_rules.require_initial_weight',
            'livestock_purchase.validation_rules.require_supplier',
            'livestock_purchase.cost_tracking.enabled',
            'livestock_purchase.cost_tracking.include_transport_cost',
            'livestock_purchase.batch_settings.batch_creation.auto_create_batch',
            'livestock_purchase.batch_settings.batch_creation.require_batch_name',
        ]
    ]
];

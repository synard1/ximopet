<?php

return [
    'livestock' => [
        'protected_paths' => [
            'recording_method.batch_settings.depletion_methods.*.enabled',
            'recording_method.batch_settings.depletion_methods.*.status',
            'recording_method.batch_settings.depletion_methods.*.auto_select',
            'recording_method.batch_settings.mutation_methods.*.enabled',
            'recording_method.batch_settings.mutation_methods.*.status',
            'recording_method.batch_settings.mutation_methods.*.auto_select',
            'recording_method.batch_settings.feed_usage_methods.*.enabled',
            'recording_method.batch_settings.feed_usage_methods.*.status',
            'recording_method.batch_settings.feed_usage_methods.*.auto_select',
            'recording_method.batch_settings.depletion_methods.*.batch_selection_criteria',
            'recording_method.batch_settings.depletion_methods.*.quantity_distribution',
            'recording_method.batch_settings.depletion_methods.*.performance_optimization',
            'recording_method.batch_settings.depletion_methods.*.audit_trail',
            'feed_usage.methods.manual.input_restrictions.prevent_duplicate_stocks',
            'feed_usage.methods.manual.input_restrictions.require_stock_availability_check',
            'feed_usage.methods.manual.edit_mode_settings',
            'validation_rules.enabled',
            'recording_method.type',
            'recording_method.allow_multiple_batches',
        ]
    ]
];

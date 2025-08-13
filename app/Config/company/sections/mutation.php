<?php

return [
    'livestock_mutation' => [
        'type' => 'batch',
        'batch_settings' => [
            'tracking_enabled' => true,
            'require_batch_number' => true,
            'auto_generate_batch' => true,
            'batch_number_format' => 'YEAR-SEQ',
            'allow_multiple_batches' => false,
        ],
        'fifo_settings' => [
            'enabled' => true,
            'track_age' => true,
            'min_age_days' => 0,
            'max_age_days' => null,
        ],
        'validation_rules' => [
            'enabled' => false,
            'require_weight' => true,
            'require_quantity' => true,
            'allow_partial_mutation' => true,
            'max_mutation_percentage' => 100,
        ],
        'document_settings' => [
            'require_do_number' => true,
            'require_invoice' => true,
            'require_receipt' => true,
        ],
    ],
    'feed_mutation' => [
        'type' => 'batch',
        'batch_settings' => [
            'tracking_enabled' => true,
            'require_batch_number' => true,
            'auto_generate_batch' => true,
            'batch_number_format' => 'YEAR-SEQ',
        ],
        'validation_rules' => [
            'enabled' => false,
            'require_quantity' => true,
            'allow_partial_mutation' => true,
            'max_mutation_percentage' => 100,
        ],
        'document_settings' => [
            'require_do_number' => true,
            'require_invoice' => true,
            'require_receipt' => true,
        ],
    ],
    'supply_mutation' => [
        'type' => 'batch',
        'batch_settings' => [
            'tracking_enabled' => true,
            'require_batch_number' => true,
            'auto_generate_batch' => true,
            'batch_number_format' => 'YEAR-SEQ',
        ],
        'validation_rules' => [
            'enabled' => false,
            'require_quantity' => true,
            'allow_partial_mutation' => true,
            'max_mutation_percentage' => 100,
        ],
        'document_settings' => [
            'require_do_number' => true,
            'require_invoice' => true,
            'require_receipt' => true,
        ],
    ],
];

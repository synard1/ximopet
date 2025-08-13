<?php

return [
    'livestock_usage' => [
        'enabled' => true,
        'validation_rules' => [
            'enabled' => false,
            'require_farm' => true,
            'require_kandang' => true,
            'require_breed' => true,
            'require_quantity' => true,
            'require_weight' => true,
            'require_unit' => true,
        ],
        'batch_settings' => [
            'enabled' => true,
            'require_batch_number' => true,
            'auto_generate_batch' => true,
            'batch_number_format' => 'YEAR-SEQ',
        ],
        'document_settings' => [
            'require_do_number' => true,
            'require_invoice' => true,
            'require_receipt' => true,
        ],
    ],
    'feed_usage' => [
        'enabled' => true,
        'validation_rules' => [
            'enabled' => false,
            'require_farm' => true,
            'require_kandang' => true,
            'require_feed' => true,
            'require_quantity' => true,
            'require_unit' => true,
        ],
        'batch_settings' => [
            'enabled' => true,
            'require_batch_number' => true,
            'auto_generate_batch' => true,
            'batch_number_format' => 'YEAR-SEQ',
        ],
        'document_settings' => [
            'require_do_number' => true,
            'require_invoice' => true,
            'require_receipt' => true,
        ],
    ],
    'supply_usage' => [
        'enabled' => true,
        'validation_rules' => [
            'enabled' => false,
            'require_farm' => true,
            'require_kandang' => true,
            'require_supply' => true,
            'require_quantity' => true,
            'require_unit' => true,
        ],
        'batch_settings' => [
            'enabled' => true,
            'require_batch_number' => true,
            'auto_generate_batch' => true,
            'batch_number_format' => 'YEAR-SEQ',
        ],
        'document_settings' => [
            'require_do_number' => true,
            'require_invoice' => true,
            'require_receipt' => true,
        ],
    ],
];

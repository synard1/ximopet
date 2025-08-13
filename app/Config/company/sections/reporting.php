<?php

return [
    'default_period' => 'monthly',
    'available_periods' => ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'],
    'export_formats' => ['pdf', 'excel', 'csv'],
    'auto_generate' => [
        'enabled' => false,
        'schedule' => 'monthly',
    ],
    'retention_period' => 365,
    'reports' => [
        'purchase' => [
            'enabled' => true,
            'types' => ['livestock', 'feed', 'supply'],
            'metrics' => ['quantity', 'value', 'frequency'],
        ],
        'mutation' => [
            'enabled' => true,
            'types' => ['livestock', 'feed', 'supply'],
            'metrics' => ['quantity', 'value', 'frequency'],
        ],
        'usage' => [
            'enabled' => true,
            'types' => ['livestock', 'feed', 'supply'],
            'metrics' => ['quantity', 'value', 'frequency'],
        ],
    ],
];

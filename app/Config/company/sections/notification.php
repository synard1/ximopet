<?php

return [
    'channels' => [
        'email' => true,
        'database' => true,
        'broadcast' => true,
    ],
    'events' => [
        'purchase' => [
            'enabled' => true,
            'channels' => ['email', 'database'],
            'notify_on' => ['created', 'updated', 'approved', 'rejected'],
        ],
        'mutation' => [
            'enabled' => true,
            'channels' => ['email', 'database'],
            'notify_on' => ['created', 'updated', 'approved', 'rejected'],
        ],
        'usage' => [
            'enabled' => true,
            'channels' => ['email', 'database'],
            'notify_on' => ['created', 'updated', 'approved', 'rejected'],
        ],
        'batch_completion' => [
            'enabled' => true,
            'channels' => ['email', 'database'],
        ],
        'low_stock' => [
            'enabled' => true,
            'channels' => ['email', 'database'],
        ],
        'age_threshold' => [
            'enabled' => true,
            'channels' => ['email', 'database'],
        ],
    ],
];

<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Chat Templates
    |--------------------------------------------------------------------------
    |
    | Predefined message templates for common queries in the livestock management system.
    |
    */

    'templates' => [
        'livestock' => [
            'title' => 'Livestock Management',
            'templates' => [
                [
                    'name' => 'Check Batch Status',
                    'description' => 'Get information about a specific livestock batch',
                    'template' => 'What is the current status of batch {batch_number}?'
                ],
                [
                    'name' => 'View Batch Details',
                    'description' => 'View detailed information about a livestock batch',
                    'template' => 'Show me detailed information about batch {batch_number} including mortality rate and average weight.'
                ],
                [
                    'name' => 'Batch Mortality Rate',
                    'description' => 'Check mortality rate for a specific batch',
                    'template' => 'What is the mortality rate for batch {batch_number}?'
                ],
                [
                    'name' => 'Batch Feeding Schedule',
                    'description' => 'Get feeding schedule for a specific batch',
                    'template' => 'What is the feeding schedule for batch {batch_number}?'
                ]
            ]
        ],
        'feed' => [
            'title' => 'Feed Management',
            'templates' => [
                [
                    'name' => 'Check Feed Stock',
                    'description' => 'Check current stock levels for a specific feed type',
                    'template' => 'What is the current stock level for {feed_type}?'
                ],
                [
                    'name' => 'Feed Usage Report',
                    'description' => 'Get report on feed usage for a specific period',
                    'template' => 'Show me the feed usage report for {period}.'
                ],
                [
                    'name' => 'Feed Cost Analysis',
                    'description' => 'Analyze feed costs for a specific batch or period',
                    'template' => 'Provide a cost analysis for feed usage in batch {batch_number}.'
                ]
            ]
        ],
        'financial' => [
            'title' => 'Financial Analysis',
            'templates' => [
                [
                    'name' => 'Daily Profit/Loss',
                    'description' => 'Calculate daily profit or loss',
                    'template' => 'What was the profit/loss for {date}?'
                ],
                [
                    'name' => 'Monthly Financial Report',
                    'description' => 'Generate monthly financial report',
                    'template' => 'Generate a financial report for {month} {year}.'
                ],
                [
                    'name' => 'Batch Profitability',
                    'description' => 'Analyze profitability of a specific batch',
                    'template' => 'Analyze the profitability of batch {batch_number}.'
                ]
            ]
        ],
        'health' => [
            'title' => 'Health Monitoring',
            'templates' => [
                [
                    'name' => 'Health Alert Summary',
                    'description' => 'Get summary of current health alerts',
                    'template' => 'What are the current health alerts?'
                ],
                [
                    'name' => 'Vaccination Schedule',
                    'description' => 'Check vaccination schedule for a batch',
                    'template' => 'What is the vaccination schedule for batch {batch_number}?'
                ],
                [
                    'name' => 'Treatment History',
                    'description' => 'View treatment history for a specific batch',
                    'template' => 'Show me the treatment history for batch {batch_number}.'
                ]
            ]
        ]
    ]
];
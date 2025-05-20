<?php

return [
    /*
    |--------------------------------------------------------------------------
    | QA Feature Categories
    |--------------------------------------------------------------------------
    |
    | This configuration file contains all the feature categories and their
    | subcategories used in the QA checklist system. Categories are organized
    | hierarchically to better manage and maintain the QA process.
    |
    */

    'categories' => [
        'Livestock Management' => [
            'Livestock Recording' => [
                'Livestock Registration',
                'Livestock Details',
                'Livestock History',
                'Livestock Status'
            ],
            'Batch Management' => [
                'Batch Creation',
                'Batch Tracking',
                'Batch Reports',
                'Batch Performance'
            ],
            'Livestock Operations' => [
                'Livestock Transfer',
                'Livestock Rollback',
                'Livestock Disposal',
                'Livestock Sales'
            ]
        ],
        'Feed Management' => [
            'Feed Purchases' => [
                'Purchase Orders',
                'Purchase History',
                'Supplier Management',
                'Stock Receiving'
            ],
            'Feed Usage' => [
                'Usage Recording',
                'Consumption Analysis',
                'Waste Management',
                'Usage Reports'
            ],
            'Feed Stock' => [
                'Stock Monitoring',
                'Stock Alerts',
                'Stock Reports',
                'Stock Transfer'
            ],
            'Feed Mutations' => [
                'Mutation Recording',
                'Mutation History',
                'Mutation Reports'
            ]
        ],
        'Supply Management' => [
            'Supply Purchases' => [
                'Purchase Orders',
                'Purchase History',
                'Supplier Management',
                'Stock Receiving'
            ],
            'Supply Usage' => [
                'Usage Recording',
                'Consumption Analysis',
                'Usage Reports'
            ],
            'Supply Stock' => [
                'Stock Monitoring',
                'Stock Alerts',
                'Stock Reports',
                'Stock Transfer'
            ],
            'Supply Mutations' => [
                'Mutation Recording',
                'Mutation History',
                'Mutation Reports'
            ]
        ],
        'Farm Management' => [
            'Farm Operations' => [
                'Farm Registration',
                'Farm Details',
                'Farm Status',
                'Farm Reports'
            ],
            'Cage Management' => [
                'Cage Registration',
                'Cage Details',
                'Cage Status',
                'Cage Reports'
            ],
            'Worker Management' => [
                'Worker Registration',
                'Worker Assignment',
                'Worker Performance',
                'Worker Reports'
            ]
        ],
        'Transaction Management' => [
            'Sales Management' => [
                'Sales Orders',
                'Sales History',
                'Customer Management',
                'Payment Processing'
            ],
            'Purchase Management' => [
                'Purchase Orders',
                'Purchase History',
                'Supplier Management',
                'Payment Tracking'
            ],
            'Daily Transactions' => [
                'Transaction Recording',
                'Transaction History',
                'Transaction Reports'
            ]
        ],
        'Master Data Management' => [
            'Customer Management' => [
                'Customer Profiles',
                'Contact Information',
                'Transaction History'
            ],
            'Supplier Management' => [
                'Supplier Profiles',
                'Contact Information',
                'Purchase History'
            ],
            'Item Management' => [
                'Item Catalog',
                'Pricing Management',
                'Inventory Control'
            ],
            'Expedition Management' => [
                'Shipping Routes',
                'Vehicle Management',
                'Delivery Tracking'
            ],
            'Unit Management' => [
                'Unit Registration',
                'Unit Details',
                'Unit Reports'
            ]
        ],
        'User Management' => [
            'Role Management' => [
                'Role Creation',
                'Permission Assignment',
                'Access Control'
            ],
            'Permission Management' => [
                'Permission Sets',
                'Access Rights',
                'Security Policies'
            ],
            'User Management' => [
                'User Profiles',
                'Authentication',
                'Activity Logging'
            ]
        ],
        'Reporting and Analytics' => [
            'Performance Reports' => [
                'Partner Performance',
                'Farm Performance',
                'Livestock Performance'
            ],
            'Financial Reports' => [
                'Sales Reports',
                'Purchase Reports',
                'Cost Analysis',
                'Profitability Metrics'
            ],
            'Operational Reports' => [
                'Daily Reports',
                'Inventory Reports',
                'Transaction Reports',
                'Resource Utilization'
            ]
        ],
        'System Features' => [
            'Route Management' => [
                'Route Configuration',
                'Access Control',
                'Permission Mapping'
            ],
            'Data Validation' => [
                'Input Validation',
                'Data Integrity',
                'Error Prevention'
            ],
            'Error Handling' => [
                'Error Logging',
                'Exception Management',
                'Recovery Procedures'
            ],
            'API Integration' => [
                'API Authentication',
                'Data Synchronization',
                'Error Handling'
            ]
        ]
    ]
];

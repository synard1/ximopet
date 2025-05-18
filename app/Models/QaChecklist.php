<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QaChecklist extends Model
{
    protected $fillable = [
        'feature_name',
        'feature_category',
        'feature_subcategory',
        'test_case',
        'test_steps',
        'expected_result',
        'test_type',
        'priority',
        'status',
        'notes',
        'error_details',
        'tester_name',
        'test_date',
        'environment',
        'browser',
        'device'
    ];

    protected $casts = [
        'test_date' => 'date'
    ];

    public static function getFeatureCategories()
    {
        return [
            'Livestock Management' => [
                'Batch Management',
                'Livestock Recording',
                'Weight Standards'
            ],
            'Feed Management' => [
                'Feed Purchases',
                'Feed Usage',
                'Feed Stock'
            ],
            'Farm Management' => [
                'Farm Operations',
                'Worker Management'
            ],
            'Transaction Management' => [
                'Sales Management',
                'Purchase Management'
            ],
            'Master Data Management' => [
                'Customer Management',
                'Supplier Management',
                'Item Management',
                'Expedition Management'
            ],
            'User Management' => [
                'Role Management',
                'Permission Management',
                'User Management'
            ],
            'Reporting and Analytics' => [
                'Performance Metrics',
                'Financial Reports',
                'Operational Reports'
            ],
            'System Features' => [
                'Audit Trail',
                'Data Validation',
                'Error Handling'
            ]
        ];
    }
}

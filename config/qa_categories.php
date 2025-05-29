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
            ],

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
        ],
        'Master Data' => [
            'Farm' => [
                'Farm CRUD Operations' => [
                    'test_case' => 'Verify Farm management functionalities',
                    'test_steps' => implode("\n", [
                        '1. Navigate to /master/farms.',
                        '2. Check if the page loads properly and displays all farm data in table format.',
                        '3. Verify pagination works correctly.',
                        '4. Test search functionality to find a specific farm.',
                        '5. Test sorting on all columns.',
                        '6. Click "Add New Farm" button, fill in all required fields, and submit the form to create a new farm.',
                        '7. Verify the new farm appears in the list.',
                        '8. Click edit button on any farm, modify farm details, and save changes.',
                        '9. Verify changes are reflected in the list.',
                        '10. Click delete button on any farm, confirm deletion, and verify the farm is removed from the list.',
                    ]),
                    'expected_result' => 'Farm management functionalities should work correctly, including displaying, creating, editing, and deleting farms.',
                    'test_type' => 'Functional',
                    'priority' => 'High',
                    'status' => 'Not Tested',
                    'notes' => '',
                    'error_details' => '',
                    'tester_name' => 'Auto QA',
                    'test_date' => '2024-01-01',
                    'environment' => 'Development',
                    'browser' => 'Chrome',
                    'device' => 'Desktop',
                    'url' => '/master/farms',
                ],
                'Farm Deletion Validation' => [
                    'test_case' => 'Verify that a farm cannot be deleted if it has associated cages',
                    'test_steps' => implode("\n", [
                        '1. Navigate to /master/farms.',
                        '2. Create a new farm and add associated cages.',
                        '3. Attempt to delete the farm.',
                        '4. Verify that a warning message is displayed indicating that the farm cannot be deleted due to existing cages.',
                        '5. Confirm that the farm still appears in the list after the deletion attempt.',
                    ]),
                    'expected_result' => 'The system should prevent the deletion of the farm and display an appropriate warning message.',
                    'test_type' => 'Data Validation',
                    'priority' => 'High',
                    'status' => 'Not Tested',
                    'notes' => '',
                    'error_details' => '',
                    'tester_name' => 'Auto QA',
                    'test_date' => '2024-01-01',
                    'environment' => 'Development',
                    'browser' => 'Chrome',
                    'device' => 'Desktop',
                    'url' => '/master/farms',
                ],
                'Farm Detail Validation' => [
                    'test_case' => 'Verify that farm details are only visible if the farm has child data',
                    'test_steps' => implode("\n", [
                        '1. Navigate to /master/farms.',
                        '2. Select a farm with child data (e.g., cages, kandangs).',
                        '3. Click on the farm detail button.',
                        '4. Verify that the farm detail modal is displayed with the child data.',
                        '5. Select a farm without child data.',
                        '6. Click on the farm detail button.',
                        '7. Verify that the farm detail modal is not displayed or displays a message indicating no child data.',
                    ]),
                    'expected_result' => 'Farm details should only be visible if the farm has child data.',
                    'test_type' => 'Data Validation',
                    'priority' => 'High',
                    'status' => 'Not Tested',
                    'notes' => '',
                    'error_details' => '',
                    'tester_name' => 'Auto QA',
                    'test_date' => '2024-01-01',
                    'environment' => 'Development',
                    'browser' => 'Chrome',
                    'device' => 'Desktop',
                    'url' => '/master/farms',
                ],
            ],
            'Kandang' => [
                'Kandang CRUD Operations' => [
                    'test_case' => 'Verify Kandang CRUD operations',
                    'test_steps' => implode("\n", [
                        '1. Navigate to /master/kandangs.',
                        '2. Check if the page loads properly and displays all kandang data in table format.',
                        '3. Verify pagination works correctly.',
                        '4. Test search functionality to find a specific kandang.',
                        '5. Test sorting on all columns.',
                        '6. Click "Add New Kandang" button, fill in all required fields, and submit the form to create a new kandang.',
                        '7. Verify the new kandang appears in the list.',
                        '8. Click edit button on any kandang, modify kandang details, and save changes.',
                        '9. Verify changes are reflected in the list.',
                        '10. Click delete button on any kandang, confirm deletion, and verify the kandang is removed from the list.',
                    ]),
                    'expected_result' => 'Kandang CRUD operations should work correctly, including displaying, creating, editing, and deleting kandangs.',
                    'test_type' => 'Functional',
                    'priority' => 'High',
                    'status' => 'Not Tested',
                    'notes' => '',
                    'error_details' => '',
                    'tester_name' => 'Auto QA',
                    'test_date' => '2024-01-01',
                    'environment' => 'Development',
                    'browser' => 'Chrome',
                    'device' => 'Desktop',
                    'url' => '/master/kandangs',
                ],
                'Kandang Deletion Validation' => [
                    'test_case' => 'Verify that a kandang cannot be deleted if it has associated batch of chickens',
                    'test_steps' => implode("\n", [
                        '1. Navigate to /master/kandangs.',
                        '2. Create a new batch of chickens and associate it with an existing kandang.',
                        '3. Attempt to delete the kandang with associated batch of chickens.',
                        '4. Verify that a warning message is displayed indicating that the kandang cannot be deleted due to existing batch of chickens.',
                        '5. Confirm that the kandang still appears in the list after the deletion attempt.',
                    ]),
                    'expected_result' => 'The system should prevent the deletion of the kandang and display an appropriate warning message.',
                    'test_type' => 'Data Validation',
                    'priority' => 'High',
                    'status' => 'Not Tested',
                    'notes' => '',
                    'error_details' => '',
                    'tester_name' => 'Auto QA',
                    'test_date' => '2024-01-01',
                    'environment' => 'Development',
                    'browser' => 'Chrome',
                    'device' => 'Desktop',
                    'url' => '/master/kandangs',
                ],
            ],
            'Supplier' => [
                'Supplier CRUD Operations' => [
                    'test_case' => 'Verify Supplier CRUD operations',
                    'test_steps' => implode("\n", [
                        '1. Navigate to /master/suppliers.',
                        '2. Check if the page loads properly and displays all supplier data in table format.',
                        '3. Verify pagination works correctly.',
                        '4. Test search functionality to find a specific supplier.',
                        '5. Test sorting on all columns.',
                        '6. Click "Add New Supplier" button, fill in all required fields, and submit the form to create a new supplier.',
                        '7. Verify the new supplier appears in the list.',
                        '8. Click edit button on any supplier, modify supplier details, and save changes.',
                        '9. Verify changes are reflected in the list.',
                        '10. Click delete button on any supplier without associated transactions, confirm deletion, and verify the supplier is removed from the list.',
                    ]),
                    'expected_result' => 'Supplier CRUD operations should work correctly, including displaying, creating, editing, and deleting suppliers.',
                    'test_type' => 'Functional',
                    'priority' => 'High',
                    'status' => 'Not Tested',
                    'notes' => '',
                    'error_details' => '',
                    'tester_name' => 'Auto QA',
                    'test_date' => '2024-01-01',
                    'environment' => 'Development',
                    'browser' => 'Chrome',
                    'device' => 'Desktop',
                    'url' => '/master/suppliers',
                ],
                'Supplier Deletion Validation' => [
                    'test_case' => 'Verify that a supplier cannot be deleted if it has associated transactions',
                    'test_steps' => implode("\n", [
                        '1. Navigate to /master/suppliers.',
                        '2. Create a transaction associated with a supplier.',
                        '3. Attempt to delete the supplier with associated transaction.',
                        '4. Verify that a warning message is displayed indicating that the supplier cannot be deleted due to existing transactions.',
                        '5. Confirm that the supplier still appears in the list after the deletion attempt.',
                    ]),
                    'expected_result' => 'The system should prevent the deletion of the supplier and display an appropriate warning message.',
                    'test_type' => 'Data Validation',
                    'priority' => 'High',
                    'status' => 'Not Tested',
                    'notes' => '',
                    'error_details' => '',
                    'tester_name' => 'Auto QA',
                    'test_date' => '2024-01-01',
                    'environment' => 'Development',
                    'browser' => 'Chrome',
                    'device' => 'Desktop',
                    'url' => '/master/suppliers',
                ],
            ],
            'Pembeli' => [
                'Customer CRUD Operations' => [
                    'test_case' => 'Verify Customer CRUD operations',
                    'test_steps' => implode("\n", [
                        '1. Navigate to /master/customers.',
                        '2. Check if the page loads properly and displays all customer data in table format.',
                        '3. Verify pagination works correctly.',
                        '4. Test search functionality to find a specific customer.',
                        '5. Test sorting on all columns.',
                        '6. Click "Add New Customer" button, fill in all required fields, and submit the form to create a new customer.',
                        '7. Verify the new customer appears in the list.',
                        '8. Click edit button on any customer, modify customer details, and save changes.',
                        '9. Verify changes are reflected in the list.',
                        '10. Click delete button on any customer, confirm deletion, and verify the customer is removed from the list.',
                    ]),
                    'expected_result' => 'Customer CRUD operations should work correctly, including displaying, creating, editing, and deleting customers.',
                    'test_type' => 'Functional',
                    'priority' => 'High',
                    'status' => 'Not Tested',
                    'notes' => '',
                    'error_details' => '',
                    'tester_name' => 'Auto QA',
                    'test_date' => '2024-01-01',
                    'environment' => 'Development',
                    'browser' => 'Chrome',
                    'device' => 'Desktop',
                    'url' => '/master/customers',
                ],
                'Customer Deletion Validation' => [
                    'test_case' => 'Verify that a customer cannot be deleted if it has associated transactions',
                    'test_steps' => implode("\n", [
                        '1. Navigate to /master/customers.',
                        '2. Create a transaction associated with a customer.',
                        '3. Attempt to delete the customer with associated transaction.',
                        '4. Verify that a warning message is displayed indicating that the customer cannot be deleted due to existing transactions.',
                        '5. Confirm that the customer still appears in the list after the deletion attempt.',
                    ]),
                    'expected_result' => 'The system should prevent the deletion of the customer and display an appropriate warning message.',
                    'test_type' => 'Data Validation',
                    'priority' => 'High',
                    'status' => 'Not Tested',
                    'notes' => '',
                    'error_details' => '',
                    'tester_name' => 'Auto QA',
                    'test_date' => '2024-01-01',
                    'environment' => 'Development',
                    'browser' => 'Chrome',
                    'device' => 'Desktop',
                    'url' => '/master/customers',
                ],
            ],
            'Ekspedisi' => [
                'Expedition CRUD Operations' => [
                    'test_case' => 'Verify Expedition CRUD operations',
                    'test_steps' => implode("\n", [
                        '1. Navigate to /master/expeditions.',
                        '2. Check if the page loads properly and displays all expedition data in table format.',
                        '3. Verify pagination works correctly.',
                        '4. Test search functionality to find a specific expedition.',
                        '5. Test sorting on all columns.',
                        '6. Click "Add New Expedition" button, fill in all required fields, and submit the form to create a new expedition.',
                        '7. Verify the new expedition appears in the list.',
                        '8. Click edit button on any expedition, modify expedition details, and save changes.',
                        '9. Verify changes are reflected in the list.',
                        '10. Click delete button on any expedition, confirm deletion, and verify the expedition is removed from the list.',
                    ]),
                    'expected_result' => 'Expedition CRUD operations should work correctly, including displaying, creating, editing, and deleting expeditions.',
                    'test_type' => 'Functional',
                    'priority' => 'High',
                    'status' => 'Not Tested',
                    'notes' => '',
                    'error_details' => '',
                    'tester_name' => 'Auto QA',
                    'test_date' => '2024-01-01',
                    'environment' => 'Development',
                    'browser' => 'Chrome',
                    'device' => 'Desktop',
                    'url' => '/master/expeditions',
                ],
                'Expedition Deletion Validation' => [
                    'test_case' => 'Verify that an expedition cannot be deleted if it has associated transactions',
                    'test_steps' => implode("\n", [
                        '1. Navigate to /master/expeditions.',
                        '2. Create a transaction associated with an expedition.',
                        '3. Attempt to delete the expedition with associated transaction.',
                        '4. Verify that a warning message is displayed indicating that the expedition cannot be deleted due to existing transactions.',
                        '5. Confirm that the expedition still appears in the list after the deletion attempt.',
                    ]),
                    'expected_result' => 'The system should prevent the deletion of the expedition and display an appropriate warning message.',
                    'test_type' => 'Data Validation',
                    'priority' => 'High',
                    'status' => 'Not Tested',
                    'notes' => '',
                    'error_details' => '',
                    'tester_name' => 'Auto QA',
                    'test_date' => '2024-01-01',
                    'environment' => 'Development',
                    'browser' => 'Chrome',
                    'device' => 'Desktop',
                    'url' => '/master/expeditions',
                ],
            ],
            'Unit Satuan' => [
                'Unit CRUD Operations' => [
                    'test_case' => 'Verify Unit CRUD operations',
                    'test_steps' => implode("\n", [
                        '1. Navigate to /master/units.',
                        '2. Check if the page loads properly and displays all unit data in table format.',
                        '3. Verify pagination works correctly.',
                        '4. Test search functionality to find a specific unit.',
                        '5. Test sorting on all columns.',
                        '6. Click "Add New Unit" button, fill in all required fields, and submit the form to create a new unit.',
                        '7. Verify the new unit appears in the list.',
                        '8. Click edit button on any unit, modify unit details, and save changes.',
                        '9. Verify changes are reflected in the list.',
                        '10. Click delete button on any unit, confirm deletion, and verify the unit is removed from the list.',
                    ]),
                    'expected_result' => 'Unit CRUD operations should work correctly, including displaying, creating, editing, and deleting units.',
                    'test_type' => 'Functional',
                    'priority' => 'High',
                    'status' => 'Not Tested',
                    'notes' => '',
                    'error_details' => '',
                    'tester_name' => 'Auto QA',
                    'test_date' => '2024-01-01',
                    'environment' => 'Development',
                    'browser' => 'Chrome',
                    'device' => 'Desktop',
                    'url' => '/master/units',
                ],
                'Unit Deletion Validation' => [
                    'test_case' => 'Verify that a unit cannot be deleted if it has associated transactions',
                    'test_steps' => implode("\n", [
                        '1. Navigate to /master/units.',
                        '2. Create a transaction associated with a unit.',
                        '3. Attempt to delete the unit with associated transaction.',
                        '4. Verify that a warning message is displayed indicating that the unit cannot be deleted due to existing transactions.',
                        '5. Confirm that the unit still appears in the list after the deletion attempt.',
                    ]),
                    'expected_result' => 'The system should prevent the deletion of the unit and display an appropriate warning message.',
                    'test_type' => 'Data Validation',
                    'priority' => 'High',
                    'status' => 'Not Tested',
                    'notes' => '',
                    'error_details' => '',
                    'tester_name' => 'Auto QA',
                    'test_date' => '2024-01-01',
                    'environment' => 'Development',
                    'browser' => 'Chrome',
                    'device' => 'Desktop',
                    'url' => '/master/units',
                ],
            ],
            'Pakan' => [
                'Feed CRUD Operations' => [
                    'test_case' => 'Verify Feed CRUD operations',
                    'test_steps' => implode("\n", [
                        '1. Navigate to /master/feeds.',
                        '2. Check if the page loads properly and displays all feed data in table format.',
                        '3. Verify pagination works correctly.',
                        '4. Test search functionality to find a specific feed.',
                        '5. Test sorting on all columns.',
                        '6. Click "Add New Feed" button, fill in all required fields, and submit the form to create a new feed.',
                        '7. Verify the new feed appears in the list.',
                        '8. Click edit button on any feed, modify feed details, and save changes.',
                        '9. Verify changes are reflected in the list.',
                        '10. Click delete button on any feed, confirm deletion, and verify the feed is removed from the list.',
                    ]),
                    'expected_result' => 'Feed CRUD operations should work correctly, including displaying, creating, editing, and deleting feeds.',
                    'test_type' => 'Functional',
                    'priority' => 'High',
                    'status' => 'Not Tested',
                    'notes' => '',
                    'error_details' => '',
                    'tester_name' => 'Auto QA',
                    'test_date' => '2024-01-01',
                    'environment' => 'Development',
                    'browser' => 'Chrome',
                    'device' => 'Desktop',
                    'url' => '/master/feeds',
                ],
                'Feed Deletion Validation' => [
                    'test_case' => 'Verify that a feed cannot be deleted if it has associated transactions',
                    'test_steps' => implode("\n", [
                        '1. Navigate to /master/feeds.',
                        '2. Create a transaction associated with a feed.',
                        '3. Attempt to delete the feed with associated transaction.',
                        '4. Verify that a warning message is displayed indicating that the feed cannot be deleted due to existing transactions.',
                        '5. Confirm that the feed still appears in the list after the deletion attempt.',
                    ]),
                    'expected_result' => 'The system should prevent the deletion of the feed and display an appropriate warning message.',
                    'test_type' => 'Data Validation',
                    'priority' => 'High',
                    'status' => 'Not Tested',
                    'notes' => '',
                    'error_details' => '',
                    'tester_name' => 'Auto QA',
                    'test_date' => '2024-01-01',
                    'environment' => 'Development',
                    'browser' => 'Chrome',
                    'device' => 'Desktop',
                    'url' => '/master/feeds',
                ],
            ],
            'Supply' => [
                'Supply CRUD Operations' => [
                    'test_case' => 'Verify Supply CRUD operations',
                    'test_steps' => implode("\n", [
                        '1. Navigate to /master/supplies.',
                        '2. Check if the page loads properly and displays all supply data in table format.',
                        '3. Verify pagination works correctly.',
                        '4. Test search functionality to find a specific supply.',
                        '5. Test sorting on all columns.',
                        '6. Click "Add New Supply" button, fill in all required fields, and submit the form to create a new supply.',
                        '7. Verify the new supply appears in the list.',
                        '8. Click edit button on any supply, modify supply details, and save changes.',
                        '9. Verify changes are reflected in the list.',
                        '10. Click delete button on any supply, confirm deletion, and verify the supply is removed from the list.',
                    ]),
                    'expected_result' => 'Supply CRUD operations should work correctly, including displaying, creating, editing, and deleting supplies.',
                    'test_type' => 'Functional',
                    'priority' => 'High',
                    'status' => 'Not Tested',
                    'notes' => '',
                    'error_details' => '',
                    'tester_name' => 'Auto QA',
                    'test_date' => '2024-01-01',
                    'environment' => 'Development',
                    'browser' => 'Chrome',
                    'device' => 'Desktop',
                    'url' => '/master/supplies',
                ],
                'Supply Deletion Validation' => [
                    'test_case' => 'Verify that a supply cannot be deleted if it has associated transactions',
                    'test_steps' => implode("\n", [
                        '1. Navigate to /master/supplies.',
                        '2. Create a transaction associated with a supply.',
                        '3. Attempt to delete the supply with associated transaction.',
                        '4. Verify that a warning message is displayed indicating that the supply cannot be deleted due to existing transactions.',
                        '5. Confirm that the supply still appears in the list after the deletion attempt.',
                    ]),
                    'expected_result' => 'The system should prevent the deletion of the supply and display an appropriate warning message.',
                    'test_type' => 'Data Validation',
                    'priority' => 'High',
                    'status' => 'Not Tested',
                    'notes' => '',
                    'error_details' => '',
                    'tester_name' => 'Auto QA',
                    'test_date' => '2024-01-01',
                    'environment' => 'Development',
                    'browser' => 'Chrome',
                    'device' => 'Desktop',
                    'url' => '/master/supplies',
                ],
            ],

            'Pekerja' => [
                'Worker CRUD Operations' => [
                    'test_case' => 'Verify Worker CRUD operations',
                    'test_steps' => implode("\n", [
                        '1. Navigate to /master/workers.',
                        '2. Check if the page loads properly and displays all worker data in table format.',
                        '3. Verify pagination works correctly.',
                        '4. Test search functionality to find a specific worker.',
                        '5. Test sorting on all columns.',
                        '6. Click "Add New Worker" button, fill in all required fields, and submit the form to create a new worker.',
                        '7. Verify the new worker appears in the list.',
                        '8. Click edit button on any worker, modify worker details, and save changes.',
                        '9. Verify changes are reflected in the list.',
                        '10. Click delete button on any worker, confirm deletion, and verify the worker is removed from the list.',
                    ]),
                    'expected_result' => 'Worker CRUD operations should work correctly, including displaying, creating, editing, and deleting workers.',
                    'test_type' => 'Functional',
                    'priority' => 'High',
                    'status' => 'Not Tested',
                    'notes' => '',
                    'error_details' => '',
                    'tester_name' => 'Auto QA',
                    'test_date' => '2024-01-01',
                    'environment' => 'Development',
                    'browser' => 'Chrome',
                    'device' => 'Desktop',
                    'url' => '/master/workers',
                ],
                'Worker Deletion Validation' => [
                    'test_case' => 'Verify that a worker cannot be deleted if it has associated transactions',
                    'test_steps' => implode("\n", [
                        '1. Navigate to /master/workers.',
                        '2. Create a transaction associated with a worker.',
                        '3. Attempt to delete the worker with associated transaction.',
                        '4. Verify that a warning message is displayed indicating that the worker cannot be deleted due to existing transactions.',
                        '5. Confirm that the worker still appears in the list after the deletion attempt.',
                    ]),
                    'expected_result' => 'The system should prevent the deletion of the worker and display an appropriate warning message.',
                    'test_type' => 'Data Validation',
                    'priority' => 'High',
                    'status' => 'Not Tested',
                    'notes' => '',
                    'error_details' => '',
                    'tester_name' => 'Auto QA',
                    'test_date' => '2024-01-01',
                    'environment' => 'Development',
                    'browser' => 'Chrome',
                    'device' => 'Desktop',
                    'url' => '/master/workers',
                ],
            ],
            'Standard Ayam' => [
                'Livestock Standard CRUD Operations' => [
                    'test_case' => 'Verify Livestock Standard CRUD operations',
                    'test_steps' => implode("\n", [
                        '1. Navigate to /master/livestock-standard.',
                        '2. Check if the page loads properly and displays all livestock data in table format.',
                        '3. Verify pagination works correctly.',
                        '4. Test search functionality to find a specific livestock.',
                        '5. Test sorting on all columns.',
                        '6. Click "Add New Livestock" button, fill in all required fields, and submit the form to create a new livestock.',
                        '7. Verify the new livestock appears in the list.',
                        '8. Click edit button on any livestock, modify livestock details, and save changes.',
                        '9. Verify changes are reflected in the list.',
                        '10. Click delete button on any livestock, confirm deletion, and verify the livestock is removed from the list.',
                    ]),
                    'expected_result' => 'Livestock Standard CRUD operations should work correctly, including displaying, creating, editing, and deleting livestock standard.',
                    'test_type' => 'Functional',
                    'priority' => 'High',
                    'status' => 'Not Tested',
                    'notes' => '',
                    'error_details' => '',
                    'tester_name' => 'Auto QA',
                    'test_date' => '2024-01-01',
                    'environment' => 'Development',
                    'browser' => 'Chrome',
                    'device' => 'Desktop',
                    'url' => '/master/livestock-standard',
                ],
                'Livestock Standard Deletion Validation' => [
                    'test_case' => 'Verify that a livestock standard cannot be deleted if it has associated transactions',
                    'test_steps' => implode("\n", [
                        '1. Navigate to /master/livestock-standard.',
                        '2. Create a transaction associated with a livestock standard.',
                        '3. Attempt to delete the livestock standard with associated transaction.',
                        '4. Verify that a warning message is displayed indicating that the livestock standard cannot be deleted due to existing transactions.',
                        '5. Confirm that the livestock standard still appears in the list after the deletion attempt.',
                    ]),
                    'expected_result' => 'The system should prevent the deletion of the livestock standard and display an appropriate warning message.',
                    'test_type' => 'Data Validation',
                    'priority' => 'High',
                    'status' => 'Not Tested',
                    'notes' => '',
                    'error_details' => '',
                    'tester_name' => 'Auto QA',
                    'test_date' => '2024-01-01',
                    'environment' => 'Development',
                    'browser' => 'Chrome',
                    'device' => 'Desktop',
                    'url' => '/master/livestock-standard',
                ],
            ],
            'Jenis Ayam' => [
                'Livestock Strain CRUD Operations' => [
                    'test_case' => 'Verify Livestock Strain CRUD operations',
                    'test_steps' => implode("\n", [
                        '1. Navigate to /master/livestock-strains.',
                        '2. Check if the page loads properly and displays all livestock strain data in table format.',
                        '3. Verify pagination works correctly.',
                        '4. Test search functionality to find a specific livestock strain.',
                        '5. Test sorting on all columns.',
                        '6. Click "Add New Livestock Strain" button, fill in all required fields, and submit the form to create a new livestock strain.',
                        '7. Verify the new livestock strain appears in the list.',
                        '8. Click edit button on any livestock strain, modify livestock strain details, and save changes.',
                        '9. Verify changes are reflected in the list.',
                        '10. Click delete button on any livestock strain, confirm deletion, and verify the livestock strain is removed from the list.',
                    ]),
                    'expected_result' => 'Livestock Strain CRUD operations should work correctly, including displaying, creating, editing, and deleting livestock strains.',
                    'test_type' => 'Functional',
                    'priority' => 'High',
                    'status' => 'Not Tested',
                    'notes' => '',
                    'error_details' => '',
                    'tester_name' => 'Auto QA',
                    'test_date' => '2024-01-01',
                    'environment' => 'Development',
                    'browser' => 'Chrome',
                    'device' => 'Desktop',
                    'url' => '/master/livestock-strains',
                ],
                'Livestock Strain Deletion Validation' => [
                    'test_case' => 'Verify that a livestock strain cannot be deleted if it has associated transactions',
                    'test_steps' => implode("\n", [
                        '1. Navigate to /master/livestock-strains.',
                        '2. Create a transaction associated with a livestock strain.',
                        '3. Attempt to delete the livestock strain with associated transaction.',
                        '4. Verify that a warning message is displayed indicating that the livestock strain cannot be deleted due to existing transactions.',
                        '5. Confirm that the livestock strain still appears in the list after the deletion attempt.',
                    ]),
                    'expected_result' => 'The system should prevent the deletion of the livestock strain and display an appropriate warning message.',
                    'test_type' => 'Data Validation',
                    'priority' => 'High',
                    'status' => 'Not Tested',
                    'notes' => '',
                    'error_details' => '',
                    'tester_name' => 'Auto QA',
                    'test_date' => '2024-01-01',
                    'environment' => 'Development',
                    'browser' => 'Chrome',
                    'device' => 'Desktop',
                    'url' => '/master/livestock-strains',
                ],
            ],
        ]
    ]
];

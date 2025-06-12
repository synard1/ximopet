<?php

return [
    'categories' => [
        'Master Data' => [
            'Farm' => [
                'Farm List View' => [
                    'test_case' => 'Verify Farm list page displays correctly',
                    'test_steps' => '1. Navigate to /master/farms
2. Check if the page loads properly
3. Verify all farm data is displayed in table format
4. Check if pagination works
5. Verify search functionality
6. Check if sorting works on all columns',
                    'expected_result' => 'Farm list page should load properly with all data displayed correctly. Search, sort, and pagination should work as expected.',
                    'test_type' => 'Functional',
                    'priority' => 'High'
                ],
                'Farm Create' => [
                    'test_case' => 'Verify Farm creation functionality',
                    'test_steps' => '1. Click "Add New Farm" button
2. Fill in all required fields
3. Submit the form
4. Verify the new farm appears in the list',
                    'expected_result' => 'New farm should be created successfully and appear in the list',
                    'test_type' => 'Functional',
                    'priority' => 'High'
                ],
                'Farm Edit' => [
                    'test_case' => 'Verify Farm edit functionality',
                    'test_steps' => '1. Click edit button on any farm
2. Modify farm details
3. Save changes
4. Verify changes are reflected in the list',
                    'expected_result' => 'Farm details should be updated successfully',
                    'test_type' => 'Functional',
                    'priority' => 'High'
                ],
                'Farm Delete' => [
                    'test_case' => 'Verify Farm deletion functionality',
                    'test_steps' => '1. Click delete button on any farm
2. Confirm deletion
3. Verify farm is removed from the list',
                    'expected_result' => 'Farm should be deleted successfully',
                    'test_type' => 'Functional',
                    'priority' => 'High'
                ]
            ],
            'Kandang' => [
                'Kandang List View' => [
                    'test_case' => 'Verify Kandang list page displays correctly',
                    'test_steps' => '1. Navigate to /master/kandangs
2. Check if the page loads properly
3. Verify all kandang data is displayed
4. Check pagination functionality
5. Test search feature
6. Verify sorting on columns',
                    'expected_result' => 'Kandang list should display all data correctly with working pagination, search, and sorting',
                    'test_type' => 'Functional',
                    'priority' => 'High'
                ],
                'Kandang Create' => [
                    'test_case' => 'Verify Kandang creation process',
                    'test_steps' => '1. Click "Add New Kandang"
2. Fill required information
3. Submit form
4. Verify new kandang appears in list',
                    'expected_result' => 'New kandang should be created and visible in the list',
                    'test_type' => 'Functional',
                    'priority' => 'High'
                ]
            ],
            'Supplier' => [
                'Supplier List View' => [
                    'test_case' => 'Verify Supplier list page functionality',
                    'test_steps' => '1. Navigate to /master/suppliers
2. Check page loading
3. Verify supplier data display
4. Test search and filter
5. Check pagination',
                    'expected_result' => 'Supplier list should display correctly with all features working',
                    'test_type' => 'Functional',
                    'priority' => 'High'
                ],
                'Supplier CRUD' => [
                    'test_case' => 'Verify Supplier CRUD operations',
                    'test_steps' => '1. Create new supplier
2. Edit existing supplier
3. View supplier details
4. Delete supplier
5. Verify all operations',
                    'expected_result' => 'All CRUD operations should work correctly for suppliers',
                    'test_type' => 'Functional',
                    'priority' => 'High'
                ]
            ],
            'Pembeli' => [
                'Customer List View' => [
                    'test_case' => 'Verify Customer list page functionality',
                    'test_steps' => '1. Navigate to /master/customers
2. Check page loading
3. Verify customer data display
4. Test search functionality
5. Check pagination',
                    'expected_result' => 'Customer list should display correctly with working features',
                    'test_type' => 'Functional',
                    'priority' => 'High'
                ],
                'Customer Management' => [
                    'test_case' => 'Verify Customer management features',
                    'test_steps' => '1. Add new customer
2. Edit customer details
3. View customer information
4. Delete customer
5. Verify all operations',
                    'expected_result' => 'All customer management features should work correctly',
                    'test_type' => 'Functional',
                    'priority' => 'High'
                ]
            ],
            'Ekspedisi' => [
                'Expedition List View' => [
                    'test_case' => 'Verify Expedition list page functionality',
                    'test_steps' => '1. Navigate to /master/expeditions
2. Check page loading
3. Verify expedition data display
4. Test search and filter
5. Check pagination',
                    'expected_result' => 'Expedition list should display correctly with all features working',
                    'test_type' => 'Functional',
                    'priority' => 'High'
                ],
                'Expedition Management' => [
                    'test_case' => 'Verify Expedition management features',
                    'test_steps' => '1. Add new expedition
2. Edit expedition details
3. View expedition information
4. Delete expedition
5. Verify all operations',
                    'expected_result' => 'All expedition management features should work correctly',
                    'test_type' => 'Functional',
                    'priority' => 'High'
                ]
            ],
            'Unit Satuan' => [
                'Unit List View' => [
                    'test_case' => 'Verify Unit list page functionality',
                    'test_steps' => '1. Navigate to /master/units
2. Check page loading
3. Verify unit data display
4. Test search functionality
5. Check pagination',
                    'expected_result' => 'Unit list should display correctly with working features',
                    'test_type' => 'Functional',
                    'priority' => 'High'
                ],
                'Unit Management' => [
                    'test_case' => 'Verify Unit management features',
                    'test_steps' => '1. Add new unit
2. Edit unit details
3. View unit information
4. Delete unit
5. Verify all operations',
                    'expected_result' => 'All unit management features should work correctly',
                    'test_type' => 'Functional',
                    'priority' => 'High'
                ]
            ],
            'Pakan' => [
                'Feed List View' => [
                    'test_case' => 'Verify Feed list page functionality',
                    'test_steps' => '1. Navigate to /master/feeds
2. Check page loading
3. Verify feed data display
4. Test search and filter
5. Check pagination',
                    'expected_result' => 'Feed list should display correctly with all features working',
                    'test_type' => 'Functional',
                    'priority' => 'High'
                ],
                'Feed Management' => [
                    'test_case' => 'Verify Feed management features',
                    'test_steps' => '1. Add new feed
2. Edit feed details
3. View feed information
4. Delete feed
5. Verify all operations',
                    'expected_result' => 'All feed management features should work correctly',
                    'test_type' => 'Functional',
                    'priority' => 'High'
                ]
            ],
            'Supply' => [
                'Supply List View' => [
                    'test_case' => 'Verify Supply list page functionality',
                    'test_steps' => '1. Navigate to /master/supplies
2. Check page loading
3. Verify supply data display
4. Test search functionality
5. Check pagination',
                    'expected_result' => 'Supply list should display correctly with working features',
                    'test_type' => 'Functional',
                    'priority' => 'High'
                ],
                'Supply Management' => [
                    'test_case' => 'Verify Supply management features',
                    'test_steps' => '1. Add new supply
2. Edit supply details
3. View supply information
4. Delete supply
5. Verify all operations',
                    'expected_result' => 'All supply management features should work correctly',
                    'test_type' => 'Functional',
                    'priority' => 'High'
                ]
            ],
            'Pekerja' => [
                'Worker List View' => [
                    'test_case' => 'Verify Worker list page functionality',
                    'test_steps' => '1. Navigate to /master/workers
2. Check page loading
3. Verify worker data display
4. Test search and filter
5. Check pagination',
                    'expected_result' => 'Worker list should display correctly with all features working',
                    'test_type' => 'Functional',
                    'priority' => 'High'
                ],
                'Worker Management' => [
                    'test_case' => 'Verify Worker management features',
                    'test_steps' => '1. Add new worker
2. Edit worker details
3. View worker information
4. Delete worker
5. Verify all operations',
                    'expected_result' => 'All worker management features should work correctly',
                    'test_type' => 'Functional',
                    'priority' => 'High'
                ]
            ],
            'Standard Ayam' => [
                'Livestock Standard List View' => [
                    'test_case' => 'Verify Livestock Standard list page functionality',
                    'test_steps' => '1. Navigate to /master/livestock-standard
2. Check page loading
3. Verify standard data display
4. Test search functionality
5. Check pagination',
                    'expected_result' => 'Livestock Standard list should display correctly with working features',
                    'test_type' => 'Functional',
                    'priority' => 'High'
                ],
                'Standard Management' => [
                    'test_case' => 'Verify Standard management features',
                    'test_steps' => '1. Add new standard
2. Edit standard details
3. View standard information
4. Delete standard
5. Verify all operations',
                    'expected_result' => 'All standard management features should work correctly',
                    'test_type' => 'Functional',
                    'priority' => 'High'
                ]
            ],
            'Jenis Ayam' => [
                'Livestock Strain List View' => [
                    'test_case' => 'Verify Livestock Strain list page functionality',
                    'test_steps' => '1. Navigate to /master/livestock-strains
2. Check page loading
3. Verify strain data display
4. Test search and filter
5. Check pagination',
                    'expected_result' => 'Livestock Strain list should display correctly with all features working',
                    'test_type' => 'Functional',
                    'priority' => 'High'
                ],
                'Strain Management' => [
                    'test_case' => 'Verify Strain management features',
                    'test_steps' => '1. Add new strain
2. Edit strain details
3. View strain information
4. Delete strain
5. Verify all operations',
                    'expected_result' => 'All strain management features should work correctly',
                    'test_type' => 'Functional',
                    'priority' => 'High'
                ]
            ]
        ]
    ]
];

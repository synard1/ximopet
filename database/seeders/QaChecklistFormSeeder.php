<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\QaChecklist;

class QaChecklistFormSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Sample data for QA Checklist
        $qaChecklists = [
            [
                'feature_name' => 'Farm Management',
                'feature_category' => 'Farm',
                'feature_subcategory' => 'Farm Creation',
                'test_case' => 'User can create new farm with valid data',
                'test_steps' => 'Fill farm details form, submit form',
                'expected_result' => 'New farm is created successfully',
                'test_type' => 'Functionality',
                'priority' => 'High',
                'status' => 'Passed',
                'notes' => 'Farm creation working properly',
                'error_details' => null,
                'tester_name' => 'John Doe',
                'test_date' => now(),
                'environment' => 'Development',
                'browser' => 'Chrome',
                'device' => 'Desktop',
                'url' => '/farm/create',
            ],
            [
                'feature_name' => 'Livestock Management',
                'feature_category' => 'Livestock',
                'feature_subcategory' => 'Batch Creation',
                'test_case' => 'User can create new livestock batch',
                'test_steps' => 'Navigate to livestock page, click add batch, fill form',
                'expected_result' => 'New livestock batch is created',
                'test_type' => 'Functionality',
                'priority' => 'High',
                'status' => 'Passed',
                'notes' => 'Batch creation successful',
                'error_details' => null,
                'tester_name' => 'Jane Smith',
                'test_date' => now(),
                'environment' => 'Staging',
                'browser' => 'Firefox',
                'device' => 'Desktop',
                'url' => '/livestock/batch/create',
            ],
            [
                'feature_name' => 'Supply Management',
                'feature_category' => 'Inventory',
                'feature_subcategory' => 'Supply Category',
                'test_case' => 'User can manage supply categories',
                'test_steps' => 'Access supply management, create/edit categories',
                'expected_result' => 'Supply categories are managed correctly',
                'test_type' => 'Functionality',
                'priority' => 'Medium',
                'status' => 'Passed',
                'notes' => 'Category management working as expected',
                'error_details' => null,
                'tester_name' => 'Alice Johnson',
                'test_date' => now(),
                'environment' => 'Development',
                'browser' => 'Safari',
                'device' => 'Desktop',
                'url' => '/supply/categories',
            ],
            [
                'feature_name' => 'Role Management',
                'feature_category' => 'Security',
                'feature_subcategory' => 'Permissions',
                'test_case' => 'Admin can assign roles and permissions',
                'test_steps' => 'Access role management, assign roles to users',
                'expected_result' => 'Roles and permissions are assigned correctly',
                'test_type' => 'Security',
                'priority' => 'High',
                'status' => 'Passed',
                'notes' => 'Role assignment working properly',
                'error_details' => null,
                'tester_name' => 'Bob Brown',
                'test_date' => now(),
                'environment' => 'Staging',
                'browser' => 'Edge',
                'device' => 'Desktop',
                'url' => '/roles',
            ],
            [
                'feature_name' => 'Menu Management',
                'feature_category' => 'System',
                'feature_subcategory' => 'Navigation',
                'test_case' => 'Admin can manage system menus',
                'test_steps' => 'Access menu management, create/edit menu items',
                'expected_result' => 'Menu items are updated correctly',
                'test_type' => 'Functionality',
                'priority' => 'Medium',
                'status' => 'Passed',
                'notes' => 'Menu management working as expected',
                'error_details' => null,
                'tester_name' => 'Charlie Davis',
                'test_date' => now(),
                'environment' => 'Development',
                'browser' => 'Chrome',
                'device' => 'Desktop',
                'url' => '/menu',
            ],
        ];

        // Insert the sample data into the database
        foreach ($qaChecklists as $qaChecklist) {
            QaChecklist::create($qaChecklist);
        }
    }
}

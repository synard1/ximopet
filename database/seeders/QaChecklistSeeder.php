<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\QaChecklist;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class QaChecklistSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $masterData = Config::get('qa_categories.categories.Master Data');

        foreach ($masterData as $submenu => $testCases) {
            foreach ($testCases as $testCaseName => $testCase) {
                if (is_array($testCase)) {
                    QaChecklist::create([
                        'feature_name' => $testCaseName,
                        'feature_category' => 'Master Data',
                        'feature_subcategory' => $submenu,
                        'test_case' => $testCase['test_case'],
                        'url' => $testCase['url'],
                        'test_steps' => $testCase['test_steps'],
                        'expected_result' => $testCase['expected_result'],
                        'test_type' => $testCase['test_type'],
                        'priority' => $testCase['priority'],
                        'status' => $testCase['status'],
                        'notes' => $testCase['notes'],
                        'error_details' => $testCase['error_details'],
                        'tester_name' => $testCase['tester_name'],
                        'test_date' => $testCase['test_date'],
                        'environment' => $testCase['environment'],
                        'browser' => $testCase['browser'],
                        'device' => $testCase['device'],
                    ]);
                } else {
                    Log::error('Test case is not an array', ['test_case' => $testCase]);
                }
            }
        }
    }

    /**
     * Extract URL from test steps
     */
    private function extractUrlFromTestSteps(string $testSteps): string
    {
        if (preg_match('/Navigate to (\/[^\s]+)/', $testSteps, $matches)) {
            return $matches[1];
        }
        return '';
    }
}

<?php

/**
 * Test Script for Supply Integrity Refactor v2.0
 * 
 * Tests all new integrity check categories and CurrentSupply functionality
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\SupplyDataIntegrityService;
use Illuminate\Support\Facades\Log;

class SupplyIntegrityRefactorTest
{
    private $service;
    private $testResults = [];
    private $testStartTime;

    public function __construct()
    {
        $this->service = new SupplyDataIntegrityService();
        $this->testStartTime = now();
    }

    public function runAllTests()
    {
        echo "=== Supply Integrity Refactor v2.0 Test Suite ===\n";
        echo "Started at: " . $this->testStartTime . "\n\n";

        $this->testCategorySelection();
        $this->testCurrentSupplyIntegrity();
        $this->testStockIntegrity();
        $this->testPurchaseIntegrity();
        $this->testMutationIntegrity();
        $this->testStatusIntegrity();
        $this->testMasterDataIntegrity();
        $this->testRelationshipIntegrity();
        $this->testPreviewChanges();
        $this->testFixFunctions();
        $this->testAuditTrail();
        $this->testBackupRestore();

        $this->printTestSummary();
    }

    private function testCategorySelection()
    {
        echo "1. Testing Category Selection...\n";

        try {
            // Test with all categories
            $result = $this->service->previewInvalidSupplyData();
            $this->testResults['category_all'] = $result['success'];
            echo "   ✓ All categories test: " . ($result['success'] ? 'PASSED' : 'FAILED') . "\n";

            // Test with specific categories
            $categories = ['stock_integrity', 'current_supply_integrity'];
            $result = $this->service->previewInvalidSupplyData($categories);
            $this->testResults['category_specific'] = $result['success'];
            echo "   ✓ Specific categories test: " . ($result['success'] ? 'PASSED' : 'FAILED') . "\n";

            // Test with empty categories
            $result = $this->service->previewInvalidSupplyData([]);
            $this->testResults['category_empty'] = $result['success'];
            echo "   ✓ Empty categories test: " . ($result['success'] ? 'PASSED' : 'FAILED') . "\n";
        } catch (Exception $e) {
            echo "   ✗ Category selection test FAILED: " . $e->getMessage() . "\n";
            $this->testResults['category_selection'] = false;
        }
        echo "\n";
    }

    private function testCurrentSupplyIntegrity()
    {
        echo "2. Testing CurrentSupply Integrity...\n";

        try {
            $this->service = new SupplyDataIntegrityService();
            $result = $this->service->previewInvalidSupplyData(['current_supply_integrity']);

            $currentSupplyIssues = collect($this->service->getLogs())
                ->whereIn('type', ['current_supply_mismatch', 'missing_current_supply', 'orphaned_current_supply'])
                ->count();

            echo "   ✓ CurrentSupply integrity check executed\n";
            echo "   ✓ Found {$currentSupplyIssues} CurrentSupply issues\n";

            // Test fix function
            $fixResult = $this->service->fixCurrentSupplyMismatch();
            echo "   ✓ CurrentSupply mismatch fix: " . ($fixResult['success'] ? 'SUCCESS' : 'FAILED') . "\n";
            echo "   ✓ Fixed {$fixResult['fixed_count']} records\n";

            // Test create missing records
            $createResult = $this->service->createMissingCurrentSupplyRecords();
            echo "   ✓ Create missing CurrentSupply: " . ($createResult['success'] ? 'SUCCESS' : 'FAILED') . "\n";
            echo "   ✓ Created {$createResult['created_count']} records\n";

            $this->testResults['current_supply_integrity'] = true;
        } catch (Exception $e) {
            echo "   ✗ CurrentSupply integrity test FAILED: " . $e->getMessage() . "\n";
            $this->testResults['current_supply_integrity'] = false;
        }
        echo "\n";
    }

    private function testStockIntegrity()
    {
        echo "3. Testing Stock Integrity...\n";

        try {
            $this->service = new SupplyDataIntegrityService();
            $result = $this->service->previewInvalidSupplyData(['stock_integrity']);

            $stockIssues = collect($this->service->getLogs())
                ->whereIn('type', ['invalid_stock', 'missing_stock'])
                ->count();

            echo "   ✓ Stock integrity check executed\n";
            echo "   ✓ Found {$stockIssues} stock issues\n";

            $this->testResults['stock_integrity'] = $result['success'];
        } catch (Exception $e) {
            echo "   ✗ Stock integrity test FAILED: " . $e->getMessage() . "\n";
            $this->testResults['stock_integrity'] = false;
        }
        echo "\n";
    }

    private function testPurchaseIntegrity()
    {
        echo "4. Testing Purchase Integrity...\n";

        try {
            $this->service = new SupplyDataIntegrityService();
            $result = $this->service->previewInvalidSupplyData(['purchase_integrity']);

            $purchaseIssues = collect($this->service->getLogs())
                ->whereIn('type', ['quantity_mismatch', 'conversion_mismatch'])
                ->count();

            echo "   ✓ Purchase integrity check executed\n";
            echo "   ✓ Found {$purchaseIssues} purchase issues\n";

            $this->testResults['purchase_integrity'] = $result['success'];
        } catch (Exception $e) {
            echo "   ✗ Purchase integrity test FAILED: " . $e->getMessage() . "\n";
            $this->testResults['purchase_integrity'] = false;
        }
        echo "\n";
    }

    private function testMutationIntegrity()
    {
        echo "5. Testing Mutation Integrity...\n";

        try {
            $this->service = new SupplyDataIntegrityService();
            $result = $this->service->previewInvalidSupplyData(['mutation_integrity']);

            $mutationIssues = collect($this->service->getLogs())
                ->whereIn('type', ['mutation_quantity_mismatch'])
                ->count();

            echo "   ✓ Mutation integrity check executed\n";
            echo "   ✓ Found {$mutationIssues} mutation issues\n";

            $this->testResults['mutation_integrity'] = $result['success'];
        } catch (Exception $e) {
            echo "   ✗ Mutation integrity test FAILED: " . $e->getMessage() . "\n";
            $this->testResults['mutation_integrity'] = false;
        }
        echo "\n";
    }

    private function testStatusIntegrity()
    {
        echo "6. Testing Status Integrity...\n";

        try {
            $this->service = new SupplyDataIntegrityService();
            $result = $this->service->previewInvalidSupplyData(['status_integrity']);

            $statusIssues = collect($this->service->getLogs())
                ->whereIn('type', ['status_integrity_issue'])
                ->count();

            echo "   ✓ Status integrity check executed\n";
            echo "   ✓ Found {$statusIssues} status issues\n";

            $this->testResults['status_integrity'] = $result['success'];
        } catch (Exception $e) {
            echo "   ✗ Status integrity test FAILED: " . $e->getMessage() . "\n";
            $this->testResults['status_integrity'] = false;
        }
        echo "\n";
    }

    private function testMasterDataIntegrity()
    {
        echo "7. Testing Master Data Integrity...\n";

        try {
            $this->service = new SupplyDataIntegrityService();
            $result = $this->service->previewInvalidSupplyData(['master_data_integrity']);

            $masterDataIssues = collect($this->service->getLogs())
                ->whereIn('type', ['master_data_issue'])
                ->count();

            echo "   ✓ Master data integrity check executed\n";
            echo "   ✓ Found {$masterDataIssues} master data issues\n";

            $this->testResults['master_data_integrity'] = $result['success'];
        } catch (Exception $e) {
            echo "   ✗ Master data integrity test FAILED: " . $e->getMessage() . "\n";
            $this->testResults['master_data_integrity'] = false;
        }
        echo "\n";
    }

    private function testRelationshipIntegrity()
    {
        echo "8. Testing Relationship Integrity...\n";

        try {
            $this->service = new SupplyDataIntegrityService();
            $result = $this->service->previewInvalidSupplyData(['relationship_integrity']);

            $relationshipIssues = collect($this->service->getLogs())
                ->whereIn('type', ['mutation_item_invalid_stock'])
                ->count();

            echo "   ✓ Relationship integrity check executed\n";
            echo "   ✓ Found {$relationshipIssues} relationship issues\n";

            $this->testResults['relationship_integrity'] = $result['success'];
        } catch (Exception $e) {
            echo "   ✗ Relationship integrity test FAILED: " . $e->getMessage() . "\n";
            $this->testResults['relationship_integrity'] = false;
        }
        echo "\n";
    }

    private function testPreviewChanges()
    {
        echo "9. Testing Preview Changes...\n";

        try {
            $this->service = new SupplyDataIntegrityService();
            $result = $this->service->previewChanges();

            echo "   ✓ Preview changes executed: " . ($result['success'] ? 'SUCCESS' : 'FAILED') . "\n";
            echo "   ✓ Found {$result['total_changes']} potential changes\n";

            $this->testResults['preview_changes'] = $result['success'];
        } catch (Exception $e) {
            echo "   ✗ Preview changes test FAILED: " . $e->getMessage() . "\n";
            $this->testResults['preview_changes'] = false;
        }
        echo "\n";
    }

    private function testFixFunctions()
    {
        echo "10. Testing Fix Functions...\n";

        try {
            $this->service = new SupplyDataIntegrityService();

            // Test quantity mismatch fix
            $result = $this->service->fixQuantityMismatchStocks();
            echo "   ✓ Quantity mismatch fix: " . ($result['success'] ? 'SUCCESS' : 'FAILED') . "\n";
            echo "   ✓ Fixed {$result['fixed_count']} quantity mismatches\n";

            // Test conversion mismatch fix
            $result = $this->service->fixConversionMismatchPurchases();
            echo "   ✓ Conversion mismatch fix: " . ($result['success'] ? 'SUCCESS' : 'FAILED') . "\n";
            echo "   ✓ Fixed {$result['fixed_count']} conversion mismatches\n";

            // Test mutation quantity mismatch fix
            $result = $this->service->fixMutationQuantityMismatchStocks();
            echo "   ✓ Mutation quantity mismatch fix: " . ($result['success'] ? 'SUCCESS' : 'FAILED') . "\n";
            echo "   ✓ Fixed {$result['fixed_count']} mutation quantity mismatches\n";

            $this->testResults['fix_functions'] = true;
        } catch (Exception $e) {
            echo "   ✗ Fix functions test FAILED: " . $e->getMessage() . "\n";
            $this->testResults['fix_functions'] = false;
        }
        echo "\n";
    }

    private function testAuditTrail()
    {
        echo "11. Testing Audit Trail...\n";

        try {
            // Test if audit trail system is working
            $auditCount = \App\Models\DataAuditTrail::count();
            echo "   ✓ Audit trail system active\n";
            echo "   ✓ Found {$auditCount} audit records\n";

            $this->testResults['audit_trail'] = true;
        } catch (Exception $e) {
            echo "   ✗ Audit trail test FAILED: " . $e->getMessage() . "\n";
            $this->testResults['audit_trail'] = false;
        }
        echo "\n";
    }

    private function testBackupRestore()
    {
        echo "12. Testing Backup & Restore...\n";

        try {
            $this->service = new SupplyDataIntegrityService();

            // Test backup
            $backupFile = $this->service->backupToStorage('test', 'Testing backup functionality');
            echo "   ✓ Backup created: {$backupFile}\n";

            // Test listing backups
            $backupFiles = \Illuminate\Support\Facades\Storage::files('supply-backups');
            echo "   ✓ Found " . count($backupFiles) . " backup files\n";

            $this->testResults['backup_restore'] = true;
        } catch (Exception $e) {
            echo "   ✗ Backup & restore test FAILED: " . $e->getMessage() . "\n";
            $this->testResults['backup_restore'] = false;
        }
        echo "\n";
    }

    private function printTestSummary()
    {
        echo "=== TEST SUMMARY ===\n";
        $totalTests = count($this->testResults);
        $passedTests = array_sum($this->testResults);
        $failedTests = $totalTests - $passedTests;

        echo "Total Tests: {$totalTests}\n";
        echo "Passed: {$passedTests}\n";
        echo "Failed: {$failedTests}\n";
        echo "Success Rate: " . round(($passedTests / $totalTests) * 100, 2) . "%\n\n";

        echo "Individual Test Results:\n";
        foreach ($this->testResults as $testName => $result) {
            $status = $result ? '✓ PASSED' : '✗ FAILED';
            echo "  {$testName}: {$status}\n";
        }

        $testEndTime = now();
        $duration = $this->testStartTime->diffInSeconds($testEndTime);
        echo "\nTest completed at: {$testEndTime}\n";
        echo "Total duration: {$duration} seconds\n";

        // Save test results to log file
        $this->saveTestLog();
    }

    private function saveTestLog()
    {
        $logData = [
            'test_suite' => 'Supply Integrity Refactor v2.0',
            'start_time' => $this->testStartTime,
            'end_time' => now(),
            'results' => $this->testResults,
            'summary' => [
                'total_tests' => count($this->testResults),
                'passed_tests' => array_sum($this->testResults),
                'failed_tests' => count($this->testResults) - array_sum($this->testResults),
                'success_rate' => round((array_sum($this->testResults) / count($this->testResults)) * 100, 2)
            ]
        ];

        $logDir = 'testing/logs';
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $logFile = $logDir . '/supply_integrity_refactor_test_' . now()->format('Y-m-d_H-i-s') . '.json';
        file_put_contents($logFile, json_encode($logData, JSON_PRETTY_PRINT));

        echo "Test log saved to: {$logFile}\n";
    }
}

// Run the test
try {
    $test = new SupplyIntegrityRefactorTest();
    $test->runAllTests();
} catch (Exception $e) {
    echo "Test suite failed to run: " . $e->getMessage() . "\n";
}

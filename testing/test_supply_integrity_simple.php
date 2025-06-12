<?php

/**
 * Simple Validation Test for Supply Integrity Refactor v2.0
 * 
 * Tests file structure and basic functionality without database
 */

class SimpleValidationTest
{
    private $testResults = [];
    private $projectRoot;

    public function __construct()
    {
        $this->projectRoot = dirname(__DIR__);
    }

    public function runValidationTests()
    {
        echo "=== Supply Integrity Refactor v2.0 - Simple Validation ===\n";
        echo "Started at: " . date('Y-m-d H:i:s') . "\n\n";

        $this->testFileExistence();
        $this->testServiceFileStructure();
        $this->testLivewireComponentStructure();
        $this->testBladeViewStructure();
        $this->testDocumentationFiles();

        $this->printTestSummary();
    }

    private function testFileExistence()
    {
        echo "1. Testing File Existence...\n";

        $requiredFiles = [
            'app/Services/SupplyDataIntegrityService.php',
            'app/Livewire/SupplyDataIntegrity.php',
            'resources/views/livewire/supply-data-integrity.blade.php',
            'testing/test_supply_integrity_refactor.php',
            'docs/SUPPLY_INTEGRITY_REFACTOR_V2.md'
        ];

        $allFilesExist = true;
        foreach ($requiredFiles as $file) {
            $fullPath = $this->projectRoot . '/' . $file;
            $exists = file_exists($fullPath);

            if ($exists) {
                echo "   ✓ {$file} exists\n";
            } else {
                echo "   ✗ {$file} MISSING\n";
                $allFilesExist = false;
            }
        }

        $this->testResults['file_existence'] = $allFilesExist;
        echo "   Result: " . ($allFilesExist ? 'PASSED' : 'FAILED') . "\n\n";
    }

    private function testServiceFileStructure()
    {
        echo "2. Testing Service File Structure...\n";

        $serviceFile = $this->projectRoot . '/app/Services/SupplyDataIntegrityService.php';

        if (!file_exists($serviceFile)) {
            echo "   ✗ Service file not found\n";
            $this->testResults['service_structure'] = false;
            return;
        }

        $content = file_get_contents($serviceFile);

        $requiredElements = [
            'class SupplyDataIntegrityService',
            'protected $version = \'2.0.0\'',
            'protected $checkCategories',
            'checkCurrentSupplyIntegrity',
            'calculateActualStock',
            'fixCurrentSupplyMismatch',
            'createMissingCurrentSupplyRecords',
            'runIntegrityCheck'
        ];

        $allElementsFound = true;
        foreach ($requiredElements as $element) {
            if (strpos($content, $element) !== false) {
                echo "   ✓ Found: {$element}\n";
            } else {
                echo "   ✗ Missing: {$element}\n";
                $allElementsFound = false;
            }
        }

        $this->testResults['service_structure'] = $allElementsFound;
        echo "   Result: " . ($allElementsFound ? 'PASSED' : 'FAILED') . "\n\n";
    }

    private function testLivewireComponentStructure()
    {
        echo "3. Testing Livewire Component Structure...\n";

        $componentFile = $this->projectRoot . '/app/Livewire/SupplyDataIntegrity.php';

        if (!file_exists($componentFile)) {
            echo "   ✗ Livewire component file not found\n";
            $this->testResults['livewire_structure'] = false;
            return;
        }

        $content = file_get_contents($componentFile);

        $requiredElements = [
            'class SupplyDataIntegrity extends Component',
            'public $selectedCategories',
            'public $availableCategories',
            'public $showCategorySelector',
            'fixAllCurrentSupplyMismatch',
            'createMissingCurrentSupplyRecords',
            'toggleCategorySelector',
            'selectAllCategories',
            'deselectAllCategories'
        ];

        $allElementsFound = true;
        foreach ($requiredElements as $element) {
            if (strpos($content, $element) !== false) {
                echo "   ✓ Found: {$element}\n";
            } else {
                echo "   ✗ Missing: {$element}\n";
                $allElementsFound = false;
            }
        }

        $this->testResults['livewire_structure'] = $allElementsFound;
        echo "   Result: " . ($allElementsFound ? 'PASSED' : 'FAILED') . "\n\n";
    }

    private function testBladeViewStructure()
    {
        echo "4. Testing Blade View Structure...\n";

        $viewFile = $this->projectRoot . '/resources/views/livewire/supply-data-integrity.blade.php';

        if (!file_exists($viewFile)) {
            echo "   ✗ Blade view file not found\n";
            $this->testResults['blade_structure'] = false;
            return;
        }

        $content = file_get_contents($viewFile);

        $requiredElements = [
            'Supply Data Integrity Check v2.0',
            'Category Selector',
            'toggleCategorySelector',
            'selectedCategories',
            'availableCategories',
            'Quick Fix Actions',
            'fixAllCurrentSupplyMismatch',
            'createMissingCurrentSupplyRecords',
            'current_supply_mismatch',
            'missing_current_supply',
            'orphaned_current_supply'
        ];

        $allElementsFound = true;
        foreach ($requiredElements as $element) {
            if (strpos($content, $element) !== false) {
                echo "   ✓ Found: {$element}\n";
            } else {
                echo "   ✗ Missing: {$element}\n";
                $allElementsFound = false;
            }
        }

        $this->testResults['blade_structure'] = $allElementsFound;
        echo "   Result: " . ($allElementsFound ? 'PASSED' : 'FAILED') . "\n\n";
    }

    private function testDocumentationFiles()
    {
        echo "5. Testing Documentation Files...\n";

        $docFiles = [
            'docs/SUPPLY_INTEGRITY_REFACTOR_V2.md',
            'docs/COMPLETE_IMPLEMENTATION_LOG.md'
        ];

        $allDocsValid = true;
        foreach ($docFiles as $docFile) {
            $fullPath = $this->projectRoot . '/' . $docFile;

            if (!file_exists($fullPath)) {
                echo "   ✗ {$docFile} not found\n";
                $allDocsValid = false;
                continue;
            }

            $content = file_get_contents($fullPath);
            $wordCount = str_word_count($content);

            if ($wordCount > 100) {
                echo "   ✓ {$docFile} exists ({$wordCount} words)\n";
            } else {
                echo "   ✗ {$docFile} too short ({$wordCount} words)\n";
                $allDocsValid = false;
            }
        }

        $this->testResults['documentation'] = $allDocsValid;
        echo "   Result: " . ($allDocsValid ? 'PASSED' : 'FAILED') . "\n\n";
    }

    private function printTestSummary()
    {
        echo "=== VALIDATION SUMMARY ===\n";
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

        echo "\n=== REFACTOR STATUS ===\n";
        if ($passedTests === $totalTests) {
            echo "🎉 Supply Integrity Refactor v2.0 - VALIDATION SUCCESSFUL!\n";
            echo "✅ All files created and structured correctly\n";
            echo "✅ Ready for integration testing\n";
        } else {
            echo "⚠️  Some validation issues found\n";
            echo "❌ Please review failed tests above\n";
        }

        $endTime = date('Y-m-d H:i:s');
        echo "\nValidation completed at: {$endTime}\n";

        // Save validation log
        $this->saveValidationLog();
    }

    private function saveValidationLog()
    {
        $logData = [
            'validation_suite' => 'Supply Integrity Refactor v2.0 - Simple Validation',
            'timestamp' => date('Y-m-d H:i:s'),
            'results' => $this->testResults,
            'summary' => [
                'total_tests' => count($this->testResults),
                'passed_tests' => array_sum($this->testResults),
                'failed_tests' => count($this->testResults) - array_sum($this->testResults),
                'success_rate' => round((array_sum($this->testResults) / count($this->testResults)) * 100, 2)
            ]
        ];

        $logDir = $this->projectRoot . '/testing/logs';
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $logFile = $logDir . '/supply_integrity_validation_' . date('Y-m-d_H-i-s') . '.json';
        file_put_contents($logFile, json_encode($logData, JSON_PRETTY_PRINT));

        echo "Validation log saved to: " . str_replace($this->projectRoot . '/', '', $logFile) . "\n";
    }
}

// Run the validation test
try {
    $test = new SimpleValidationTest();
    $test->runValidationTests();
} catch (Exception $e) {
    echo "Validation suite failed to run: " . $e->getMessage() . "\n";
}

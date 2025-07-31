<?php

/**
 * Simulasi Individual Tests untuk Perhitungan Berat Batch
 * 
 * Script ini memungkinkan menjalankan simulasi individual untuk setiap test case
 * 
 * @author System
 * @version 1.0
 * @since 2025-07-26
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// Bootstrap Laravel application
$app = Application::configure(basePath: __DIR__ . '/..')
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Helper functions (same as main simulation)
function printSection($title)
{
    echo "\n" . str_repeat("=", 50) . "\n";
    echo " $title\n";
    echo str_repeat("=", 50) . "\n";
}

function printInfo($message, $data = null)
{
    echo "ℹ️  $message\n";
    if ($data) {
        echo "   " . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    }
}

function printSuccess($message)
{
    echo "✅ $message\n";
}

function printWarning($message)
{
    echo "⚠️  $message\n";
}

function printError($message)
{
    echo "❌ $message\n";
}

function printCommand($command)
{
    echo "💻 Command: $command\n";
}

function executeCommand($command)
{
    printCommand($command);

    $output = [];
    $returnCode = 0;

    exec($command . " 2>&1", $output, $returnCode);

    echo "📋 Output:\n";
    foreach ($output as $line) {
        echo "   $line\n";
    }

    if ($returnCode !== 0) {
        printError("Command failed with return code: $returnCode");
    } else {
        printSuccess("Command executed successfully");
    }

    return [
        'output' => $output,
        'return_code' => $returnCode
    ];
}

function getSampleData()
{
    return [
        'livestock_id' => '9f6f82e5-5a91-4843-9164-5f547bbf111a',
        'batch_id' => '9f7b1bc4-2509-491f-b7a1-9ff8971fc07c',
        'user_id' => '9f48b44b-2142-4b8b-ade3-97e44ea6599c',
        'start_date' => '2025-06-01',
        'end_date' => '2025-07-26'
    ];
}

// Individual test functions
function testEstimation()
{
    printSection("TEST: ESTIMASI WAKTU PERHITUNGAN");

    $sampleData = getSampleData();

    printInfo("Testing time estimation for single batch");
    $command = "php artisan livestock:calculate-batch-weight --batch-id=\"{$sampleData['batch_id']}\" --estimate";
    executeCommand($command);
}

function testSingleBatchSync()
{
    printSection("TEST: SINGLE BATCH CALCULATION (SYNC)");

    $sampleData = getSampleData();

    printInfo("Calculating weight for single batch synchronously", [
        'batch_id' => $sampleData['batch_id'],
        'mode' => 'sync'
    ]);

    $command = "php artisan livestock:calculate-batch-weight --batch-id=\"{$sampleData['batch_id']}\" --sync --user-id=\"{$sampleData['user_id']}\"";
    $result = executeCommand($command);

    if ($result['return_code'] === 0) {
        printSuccess("Single batch calculation completed successfully");
    }
}

function testSingleLivestockSync()
{
    printSection("TEST: SINGLE LIVESTOCK CALCULATION (SYNC)");

    $sampleData = getSampleData();

    printInfo("Calculating weight for single livestock synchronously", [
        'livestock_id' => $sampleData['livestock_id'],
        'date_range' => "{$sampleData['start_date']} to {$sampleData['end_date']}",
        'mode' => 'sync'
    ]);

    $command = "php artisan livestock:calculate-batch-weight --livestock-id=\"{$sampleData['livestock_id']}\" --start-date=\"{$sampleData['start_date']}\" --end-date=\"{$sampleData['end_date']}\" --sync --user-id=\"{$sampleData['user_id']}\"";
    $result = executeCommand($command);

    if ($result['return_code'] === 0) {
        printSuccess("Single livestock calculation completed successfully");
    }
}

function testQueueDispatch()
{
    printSection("TEST: QUEUE JOB DISPATCH");

    $sampleData = getSampleData();

    printInfo("Dispatching job to queue for single batch", [
        'batch_id' => $sampleData['batch_id'],
        'queue' => 'weight-calculation'
    ]);

    $command = "php artisan livestock:calculate-batch-weight --batch-id=\"{$sampleData['batch_id']}\" --queue=weight-calculation --user-id=\"{$sampleData['user_id']}\"";
    $result = executeCommand($command);

    if ($result['return_code'] === 0) {
        printSuccess("Job dispatched to queue successfully");
        printInfo("To process the job, run:");
        printCommand("php artisan queue:work --queue=weight-calculation --verbose");
    }
}

function testDataValidation()
{
    printSection("TEST: DATA VALIDATION");

    printInfo("Checking database for sample data");

    // Check if livestock exists
    $livestockCheck = "php artisan tinker --execute=\"echo 'Livestock count: ' . App\Models\Livestock::count();\"";
    executeCommand($livestockCheck);

    // Check if batches exist
    $batchCheck = "php artisan tinker --execute=\"echo 'Batch count: ' . App\Models\LivestockBatch::count();\"";
    executeCommand($batchCheck);

    // Check if recordings exist
    $recordingCheck = "php artisan tinker --execute=\"echo 'Recording count: ' . App\Models\Recording::count();\"";
    executeCommand($recordingCheck);
}

function testPerformance()
{
    printSection("TEST: PERFORMANCE TEST");

    printInfo("Running performance test with small dataset");

    // Test with recent data only
    $recentDate = date('Y-m-d', strtotime('-7 days'));
    $command = "php artisan livestock:calculate-batch-weight --start-date=\"$recentDate\" --sync --user-id=\"" . getSampleData()['user_id'] . "\"";

    $startTime = microtime(true);
    $result = executeCommand($command);
    $endTime = microtime(true);

    $executionTime = $endTime - $startTime;

    printInfo("Performance test completed", [
        'execution_time_seconds' => round($executionTime, 2),
        'execution_time_formatted' => formatExecutionTime($executionTime)
    ]);
}

function testLogAnalysis()
{
    printSection("TEST: LOG ANALYSIS");

    printInfo("Analyzing recent bgjob logs");

    // Show recent log entries
    $logCommand = "tail -n 10 storage/logs/bgjob.log";
    executeCommand($logCommand);

    // Count log entries by type
    $countCommand = "grep -c 'calculate_livestock_batch_weight' storage/logs/bgjob.log";
    executeCommand($countCommand);
}

function testDatabaseResults()
{
    printSection("TEST: DATABASE RESULTS");

    printInfo("Checking updated batch weights in database");

    // Check updated batches
    $updatedCheck = "php artisan tinker --execute=\"echo 'Batches with weight: ' . App\Models\LivestockBatch::whereNotNull('weight')->count();\"";
    executeCommand($updatedCheck);

    // Show sample updated batch
    $sampleCheck = "php artisan tinker --execute=\"echo 'Sample batch weight: ' . App\Models\LivestockBatch::whereNotNull('weight')->first()->weight ?? 'None';\"";
    executeCommand($sampleCheck);
}

function formatExecutionTime($seconds)
{
    if ($seconds < 60) {
        return round($seconds, 2) . ' seconds';
    } elseif ($seconds < 3600) {
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;
        return "{$minutes}m {$remainingSeconds}s";
    } else {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $remainingSeconds = $seconds % 60;
        return "{$hours}h {$minutes}m {$remainingSeconds}s";
    }
}

// Main execution
if ($argc < 2) {
    echo "🚀 SIMULASI INDIVIDUAL TESTS - PERHITUNGAN BERAT BATCH\n";
    echo "=====================================================\n\n";
    echo "Usage: php scripts/simulate_individual_tests.php <test_name>\n\n";
    echo "Available tests:\n";
    echo "  1. estimation     - Test time estimation\n";
    echo "  2. single-batch   - Test single batch calculation (sync)\n";
    echo "  3. single-livestock - Test single livestock calculation (sync)\n";
    echo "  4. queue          - Test queue job dispatch\n";
    echo "  5. validation     - Test data validation\n";
    echo "  6. performance    - Test performance with small dataset\n";
    echo "  7. logs           - Test log analysis\n";
    echo "  8. results        - Test database results\n";
    echo "  9. all            - Run all tests\n\n";
    echo "Example:\n";
    echo "  php scripts/simulate_individual_tests.php estimation\n";
    echo "  php scripts/simulate_individual_tests.php single-batch\n";
    echo "  php scripts/simulate_individual_tests.php all\n";
    exit(1);
}

$testName = strtolower($argv[1]);

echo "🚀 SIMULASI INDIVIDUAL TEST: " . strtoupper($testName) . "\n";
echo "=============================================\n\n";

try {
    switch ($testName) {
        case 'estimation':
            testEstimation();
            break;

        case 'single-batch':
            testSingleBatchSync();
            break;

        case 'single-livestock':
            testSingleLivestockSync();
            break;

        case 'queue':
            testQueueDispatch();
            break;

        case 'validation':
            testDataValidation();
            break;

        case 'performance':
            testPerformance();
            break;

        case 'logs':
            testLogAnalysis();
            break;

        case 'results':
            testDatabaseResults();
            break;

        case 'all':
            testEstimation();
            testDataValidation();
            testSingleBatchSync();
            testSingleLivestockSync();
            testQueueDispatch();
            testPerformance();
            testLogAnalysis();
            testDatabaseResults();
            break;

        default:
            printError("Unknown test: $testName");
            echo "Available tests: estimation, single-batch, single-livestock, queue, validation, performance, logs, results, all\n";
            exit(1);
    }

    printSection("TEST SELESAI");
    printSuccess("Test '$testName' completed successfully!");
} catch (Exception $e) {
    printError("Test failed: " . $e->getMessage());
    printError("Stack trace: " . $e->getTraceAsString());
    exit(1);
}

echo "\n" . str_repeat("=", 50) . "\n";
echo " 🎯 TEST SELESAI\n";
echo str_repeat("=", 50) . "\n\n";

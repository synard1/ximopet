<?php

/**
 * Simulasi Perhitungan Berat Batch Livestock
 * 
 * Script ini mensimulasikan perhitungan berat batch berdasarkan data recording historikal
 * menggunakan artisan command yang telah dibuat.
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

echo "🚀 SIMULASI PERHITUNGAN BERAT BATCH LIVESTOCK\n";
echo "=============================================\n\n";

// Helper functions
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

function simulateEstimation()
{
    printSection("SIMULASI 1: ESTIMASI WAKTU PERHITUNGAN");

    $sampleData = getSampleData();

    // Test estimation for single batch
    printInfo("Testing time estimation for single batch");
    $command = "php artisan livestock:calculate-batch-weight --batch-id=\"{$sampleData['batch_id']}\" --estimate";
    executeCommand($command);

    // Test estimation for single livestock
    printInfo("Testing time estimation for single livestock");
    $command = "php artisan livestock:calculate-batch-weight --livestock-id=\"{$sampleData['livestock_id']}\" --estimate";
    executeCommand($command);

    // Test estimation for global calculation
    printInfo("Testing time estimation for global calculation");
    $command = "php artisan livestock:calculate-batch-weight --estimate";
    executeCommand($command);
}

function simulateSingleBatchCalculation()
{
    printSection("SIMULASI 2: PERHITUNGAN SINGLE BATCH (SYNC)");

    $sampleData = getSampleData();

    printInfo("Calculating weight for single batch synchronously", [
        'batch_id' => $sampleData['batch_id'],
        'mode' => 'sync'
    ]);

    $command = "php artisan livestock:calculate-batch-weight --batch-id=\"{$sampleData['batch_id']}\" --sync --user-id=\"{$sampleData['user_id']}\"";
    $result = executeCommand($command);

    if ($result['return_code'] === 0) {
        printSuccess("Single batch calculation completed successfully");

        // Show log entries
        printInfo("Recent log entries for this calculation:");
        $logCommand = "tail -n 10 storage/logs/bgjob.log | grep 'calculate_livestock_batch_weight'";
        executeCommand($logCommand);
    }
}

function simulateSingleLivestockCalculation()
{
    printSection("SIMULASI 3: PERHITUNGAN SINGLE LIVESTOCK (SYNC)");

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

function simulateQueueJob()
{
    printSection("SIMULASI 4: DISPATCH JOB KE QUEUE");

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

function simulateDataValidation()
{
    printSection("SIMULASI 5: VALIDASI DATA");

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

    // Check sample livestock data
    $sampleData = getSampleData();
    $sampleCheck = "php artisan tinker --execute=\"echo 'Sample livestock: ' . App\Models\Livestock::find('{$sampleData['livestock_id']}')->name ?? 'Not found';\"";
    executeCommand($sampleCheck);
}

function simulatePerformanceTest()
{
    printSection("SIMULASI 6: PERFORMANCE TEST");

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

function showLogAnalysis()
{
    printSection("SIMULASI 7: ANALISIS LOG");

    printInfo("Analyzing recent bgjob logs");

    // Show recent log entries
    $logCommand = "tail -n 20 storage/logs/bgjob.log";
    executeCommand($logCommand);

    // Count log entries by type
    $countCommand = "grep -c 'calculate_livestock_batch_weight' storage/logs/bgjob.log";
    executeCommand($countCommand);

    // Show error logs if any
    $errorCommand = "grep -i 'error\|failed\|exception' storage/logs/bgjob.log | tail -n 5";
    executeCommand($errorCommand);
}

function showDatabaseResults()
{
    printSection("SIMULASI 8: HASIL DATABASE");

    printInfo("Checking updated batch weights in database");

    // Check updated batches
    $updatedCheck = "php artisan tinker --execute=\"echo 'Batches with weight: ' . App\Models\LivestockBatch::whereNotNull('weight')->count();\"";
    executeCommand($updatedCheck);

    // Show sample updated batch
    $sampleCheck = "php artisan tinker --execute=\"echo 'Sample batch weight: ' . App\Models\LivestockBatch::whereNotNull('weight')->first()->weight ?? 'None';\"";
    executeCommand($sampleCheck);

    // Show calculation metadata
    $metadataCheck = "php artisan tinker --execute=\"echo 'Batches with metadata: ' . App\Models\LivestockBatch::whereNotNull('weight_calculation_metadata')->count();\"";
    executeCommand($metadataCheck);
}

// Main simulation execution
try {
    printInfo("Starting batch weight calculation simulation");
    printInfo("Sample data being used", getSampleData());

    // Run all simulations
    simulateEstimation();
    simulateDataValidation();
    simulateSingleBatchCalculation();
    simulateSingleLivestockCalculation();
    simulateQueueJob();
    simulatePerformanceTest();
    showLogAnalysis();
    showDatabaseResults();

    printSection("SIMULASI SELESAI");
    printSuccess("All simulations completed successfully!");
    printInfo("Check the logs at: storage/logs/bgjob.log");
    printInfo("Monitor queue with: php artisan queue:work --queue=weight-calculation --verbose");
} catch (Exception $e) {
    printError("Simulation failed: " . $e->getMessage());
    printError("Stack trace: " . $e->getTraceAsString());
}

echo "\n" . str_repeat("=", 50) . "\n";
echo " 🎯 SIMULASI PERHITUNGAN BERAT BATCH SELESAI\n";
echo str_repeat("=", 50) . "\n\n";

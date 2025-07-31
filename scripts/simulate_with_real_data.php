<?php

/**
 * Simulasi dengan Data Real - Perhitungan Berat Batch
 * 
 * Script ini menjalankan simulasi menggunakan data real dari database
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

echo "🚀 SIMULASI DENGAN DATA REAL - PERHITUNGAN BERAT BATCH\n";
echo "=====================================================\n\n";

// Helper functions
function printSection($title)
{
    echo "\n" . str_repeat("=", 60) . "\n";
    echo " $title\n";
    echo str_repeat("=", 60) . "\n";
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

function getRealData()
{
    try {
        // Get real livestock data
        $livestock = \App\Models\Livestock::with('batches')
            ->whereHas('batches')
            ->whereHas('recordings')
            ->first();

        if (!$livestock) {
            throw new Exception("No livestock found with batches and recordings");
        }

        $batch = $livestock->batches->first();
        $user = \App\Models\User::first();

        return [
            'livestock_id' => $livestock->id,
            'livestock_name' => $livestock->name,
            'batch_id' => $batch->id,
            'batch_name' => $batch->name ?? 'Unknown',
            'user_id' => $user->id ?? '9f48b44b-2142-4b8b-ade3-97e44ea6599c',
            'start_date' => $livestock->start_date,
            'end_date' => now()->format('Y-m-d'),
            'recordings_count' => $livestock->recordings()->count(),
            'batches_count' => $livestock->batches()->count()
        ];
    } catch (Exception $e) {
        printError("Failed to get real data: " . $e->getMessage());
        return null;
    }
}

function analyzeDatabaseData()
{
    printSection("ANALISIS DATA DATABASE");

    try {
        // Count livestock
        $livestockCount = \App\Models\Livestock::count();
        printInfo("Total Livestock", ['count' => $livestockCount]);

        // Count livestock with batches
        $livestockWithBatches = \App\Models\Livestock::whereHas('batches')->count();
        printInfo("Livestock with Batches", ['count' => $livestockWithBatches]);

        // Count livestock with recordings
        $livestockWithRecordings = \App\Models\Livestock::whereHas('recordings')->count();
        printInfo("Livestock with Recordings", ['count' => $livestockWithRecordings]);

        // Count total batches
        $batchCount = \App\Models\LivestockBatch::count();
        printInfo("Total Batches", ['count' => $batchCount]);

        // Count total recordings
        $recordingCount = \App\Models\Recording::count();
        printInfo("Total Recordings", ['count' => $recordingCount]);

        // Count batches with weight
        $batchesWithWeight = \App\Models\LivestockBatch::whereNotNull('weight')->count();
        printInfo("Batches with Weight", ['count' => $batchesWithWeight]);

        // Sample livestock data
        $sampleLivestock = \App\Models\Livestock::with(['batches', 'recordings'])
            ->whereHas('batches')
            ->whereHas('recordings')
            ->first();

        if ($sampleLivestock) {
            printInfo("Sample Livestock Data", [
                'id' => $sampleLivestock->id,
                'name' => $sampleLivestock->name,
                'start_date' => $sampleLivestock->start_date,
                'batches_count' => $sampleLivestock->batches->count(),
                'recordings_count' => $sampleLivestock->recordings->count(),
                'first_recording_date' => $sampleLivestock->recordings->min('tanggal'),
                'last_recording_date' => $sampleLivestock->recordings->max('tanggal')
            ]);
        }

        return true;
    } catch (Exception $e) {
        printError("Database analysis failed: " . $e->getMessage());
        return false;
    }
}

function simulateWithRealLivestock()
{
    printSection("SIMULASI DENGAN DATA LIVESTOCK REAL");

    $realData = getRealData();
    if (!$realData) {
        printError("Cannot proceed without real data");
        return false;
    }

    printInfo("Using real livestock data", $realData);

    // Test estimation with real data
    printInfo("Testing estimation with real livestock");
    $command = "php artisan livestock:calculate-batch-weight --livestock-id=\"{$realData['livestock_id']}\" --estimate";
    $result = executeCommand($command);

    if ($result['return_code'] === 0) {
        // Test actual calculation with real data
        printInfo("Testing calculation with real livestock (sync mode)");
        $command = "php artisan livestock:calculate-batch-weight --livestock-id=\"{$realData['livestock_id']}\" --sync --user-id=\"{$realData['user_id']}\"";
        $result = executeCommand($command);

        if ($result['return_code'] === 0) {
            printSuccess("Real livestock calculation completed successfully");
            return true;
        }
    }

    return false;
}

function simulateWithRealBatch()
{
    printSection("SIMULASI DENGAN DATA BATCH REAL");

    $realData = getRealData();
    if (!$realData) {
        printError("Cannot proceed without real data");
        return false;
    }

    printInfo("Using real batch data", [
        'batch_id' => $realData['batch_id'],
        'batch_name' => $realData['batch_name'],
        'livestock_name' => $realData['livestock_name']
    ]);

    // Test estimation with real batch
    printInfo("Testing estimation with real batch");
    $command = "php artisan livestock:calculate-batch-weight --batch-id=\"{$realData['batch_id']}\" --estimate";
    $result = executeCommand($command);

    if ($result['return_code'] === 0) {
        // Test actual calculation with real batch
        printInfo("Testing calculation with real batch (sync mode)");
        $command = "php artisan livestock:calculate-batch-weight --batch-id=\"{$realData['batch_id']}\" --sync --user-id=\"{$realData['user_id']}\"";
        $result = executeCommand($command);

        if ($result['return_code'] === 0) {
            printSuccess("Real batch calculation completed successfully");
            return true;
        }
    }

    return false;
}

function simulateWithRecentData()
{
    printSection("SIMULASI DENGAN DATA TERBARU");

    $realData = getRealData();
    if (!$realData) {
        printError("Cannot proceed without real data");
        return false;
    }

    // Use last 30 days of data
    $startDate = now()->subDays(30)->format('Y-m-d');
    $endDate = now()->format('Y-m-d');

    printInfo("Using recent data range", [
        'start_date' => $startDate,
        'end_date' => $endDate,
        'livestock_id' => $realData['livestock_id'],
        'livestock_name' => $realData['livestock_name']
    ]);

    // Test calculation with recent data
    printInfo("Testing calculation with recent data (sync mode)");
    $command = "php artisan livestock:calculate-batch-weight --livestock-id=\"{$realData['livestock_id']}\" --start-date=\"$startDate\" --end-date=\"$endDate\" --sync --user-id=\"{$realData['user_id']}\"";
    $result = executeCommand($command);

    if ($result['return_code'] === 0) {
        printSuccess("Recent data calculation completed successfully");
        return true;
    }

    return false;
}

function simulateQueueWithRealData()
{
    printSection("SIMULASI QUEUE DENGAN DATA REAL");

    $realData = getRealData();
    if (!$realData) {
        printError("Cannot proceed without real data");
        return false;
    }

    printInfo("Dispatching queue job with real data", [
        'livestock_id' => $realData['livestock_id'],
        'livestock_name' => $realData['livestock_name'],
        'queue' => 'weight-calculation'
    ]);

    // Dispatch job to queue
    $command = "php artisan livestock:calculate-batch-weight --livestock-id=\"{$realData['livestock_id']}\" --queue=weight-calculation --user-id=\"{$realData['user_id']}\"";
    $result = executeCommand($command);

    if ($result['return_code'] === 0) {
        printSuccess("Queue job dispatched successfully");
        printInfo("To process the job, run:");
        printCommand("php artisan queue:work --queue=weight-calculation --verbose");
        return true;
    }

    return false;
}

function checkCalculationResults()
{
    printSection("VERIFIKASI HASIL PERHITUNGAN");

    try {
        // Check updated batches
        $updatedBatches = \App\Models\LivestockBatch::whereNotNull('weight')->count();
        printInfo("Batches with calculated weight", ['count' => $updatedBatches]);

        // Get sample updated batch
        $sampleBatch = \App\Models\LivestockBatch::whereNotNull('weight')
            ->with('livestock')
            ->first();

        if ($sampleBatch) {
            printInfo("Sample updated batch", [
                'batch_id' => $sampleBatch->id,
                'batch_name' => $sampleBatch->name,
                'livestock_name' => $sampleBatch->livestock->name ?? 'Unknown',
                'weight' => $sampleBatch->weight,
                'weight_calculated_at' => $sampleBatch->weight_calculated_at,
                'weight_calculation_method' => $sampleBatch->weight_calculation_method
            ]);

            // Show metadata if available
            if ($sampleBatch->weight_calculation_metadata) {
                $metadata = is_array($sampleBatch->weight_calculation_metadata)
                    ? $sampleBatch->weight_calculation_metadata
                    : json_decode($sampleBatch->weight_calculation_metadata, true);

                printInfo("Calculation metadata", $metadata);
            }
        }

        // Check recent calculations
        $recentCalculations = \App\Models\LivestockBatch::whereNotNull('weight_calculated_at')
            ->where('weight_calculated_at', '>=', now()->subHours(1))
            ->count();

        printInfo("Recent calculations (last hour)", ['count' => $recentCalculations]);

        return true;
    } catch (Exception $e) {
        printError("Failed to check calculation results: " . $e->getMessage());
        return false;
    }
}

function analyzePerformance()
{
    printSection("ANALISIS PERFORMANCE");

    try {
        // Get recent log entries
        $logFile = storage_path('logs/bgjob.log');
        if (file_exists($logFile)) {
            $logContent = file_get_contents($logFile);

            // Count total entries
            $totalEntries = substr_count($logContent, 'calculate_livestock_batch_weight');
            printInfo("Total calculation entries in logs", ['count' => $totalEntries]);

            // Count successful calculations
            $successCount = substr_count($logContent, 'completed successfully');
            printInfo("Successful calculations", ['count' => $successCount]);

            // Count errors
            $errorCount = substr_count($logContent, 'error') + substr_count($logContent, 'failed') + substr_count($logContent, 'exception');
            printInfo("Error entries", ['count' => $errorCount]);

            // Show recent log entries
            $recentLogs = shell_exec("tail -n 5 storage/logs/bgjob.log 2>/dev/null");
            if ($recentLogs) {
                printInfo("Recent log entries:");
                echo "   " . str_replace("\n", "\n   ", trim($recentLogs)) . "\n";
            }
        } else {
            printWarning("Log file not found: storage/logs/bgjob.log");
        }

        return true;
    } catch (Exception $e) {
        printError("Performance analysis failed: " . $e->getMessage());
        return false;
    }
}

// Main execution
try {
    printInfo("Starting simulation with real data");

    // Analyze database data first
    if (!analyzeDatabaseData()) {
        printError("Database analysis failed, cannot proceed");
        exit(1);
    }

    // Run simulations with real data
    $successCount = 0;
    $totalTests = 5;

    if (simulateWithRealLivestock()) $successCount++;
    if (simulateWithRealBatch()) $successCount++;
    if (simulateWithRecentData()) $successCount++;
    if (simulateQueueWithRealData()) $successCount++;
    if (checkCalculationResults()) $successCount++;

    // Analyze performance
    analyzePerformance();

    printSection("SIMULASI SELESAI");
    printSuccess("Simulation completed! Success rate: {$successCount}/{$totalTests}");

    if ($successCount === $totalTests) {
        printSuccess("All tests passed successfully!");
    } else {
        printWarning("Some tests failed. Check the logs for details.");
    }

    printInfo("Next steps:");
    echo "   1. Check logs: storage/logs/bgjob.log\n";
    echo "   2. Monitor queue: php artisan queue:work --queue=weight-calculation --verbose\n";
    echo "   3. View results: php artisan tinker\n";
    echo "   4. Run migration if not done: php artisan migrate\n";
} catch (Exception $e) {
    printError("Simulation failed: " . $e->getMessage());
    printError("Stack trace: " . $e->getTraceAsString());
    exit(1);
}

echo "\n" . str_repeat("=", 60) . "\n";
echo " 🎯 SIMULASI DENGAN DATA REAL SELESAI\n";
echo str_repeat("=", 60) . "\n\n";

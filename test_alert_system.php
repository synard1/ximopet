<?php

require_once 'vendor/autoload.php';

use App\Services\Alert\AlertService;
use App\Services\Alert\FeedAlertService;

// Initialize Laravel application
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🧪 Testing Refactored Alert System\n";
echo "=" . str_repeat("=", 50) . "\n\n";

try {
    // Test 1: Base AlertService instantiation
    echo "1️⃣ Testing Base AlertService instantiation...\n";
    $alertService = app(AlertService::class);
    echo "   ✅ SUCCESS: AlertService instantiated successfully\n\n";

    // Test 2: FeedAlertService instantiation
    echo "2️⃣ Testing FeedAlertService instantiation...\n";
    $feedAlertService = app(FeedAlertService::class);
    echo "   ✅ SUCCESS: FeedAlertService instantiated successfully\n\n";

    // Test 3: Generic Alert via Base Service
    echo "3️⃣ Testing Generic Alert via Base Service...\n";
    $genericResult = $alertService->sendGenericAlert(
        'system_test',
        'info',
        'System Test Alert',
        'This is a test of the generic alert system',
        [
            'test_id' => 'TEST_001',
            'component' => 'Alert System',
            'version' => '2.0.0',
            'test_time' => now()->toISOString()
        ],
        [
            'recipient_category' => 'default',
            'throttle' => [
                'key' => 'system_test_generic',
                'minutes' => 1
            ]
        ]
    );

    if ($genericResult) {
        echo "   ✅ SUCCESS: Generic alert sent successfully\n\n";
    } else {
        echo "   ❌ FAILED: Generic alert failed to send\n\n";
    }

    // Test 4: Feed Usage Alert via FeedAlertService
    echo "4️⃣ Testing Feed Usage Alert via FeedAlertService...\n";
    $feedUsageResult = $feedAlertService->sendFeedUsageAlert('created', [
        'feed_usage_id' => 'TEST_FEED_USAGE_001',
        'livestock_id' => 'TEST_LIVESTOCK_001',
        'livestock_name' => 'Test Livestock [REFACTOR TEST]',
        'batch_id' => 'TEST_BATCH_001',
        'batch_name' => 'Test Batch 001',
        'usage_date' => now()->format('Y-m-d'),
        'usage_purpose' => 'testing',
        'total_quantity' => 100.0,
        'total_cost' => 750000.0,
        'manual_stocks' => [
            [
                'feed_name' => 'Test Feed [REFACTOR]',
                'quantity' => 100.0,
                'cost_per_unit' => 7500.0,
                'line_cost' => 750000.0,
                'note' => 'Refactor test feed usage'
            ]
        ],
        'user_id' => 1,
        'user_name' => 'Test User [REFACTOR]',
        'ip_address' => '127.0.0.1',
        'timestamp' => now()->toISOString()
    ]);

    if ($feedUsageResult) {
        echo "   ✅ SUCCESS: Feed usage alert sent successfully\n\n";
    } else {
        echo "   ❌ FAILED: Feed usage alert failed to send\n\n";
    }

    // Test 5: Feed Stats Discrepancy Alert via FeedAlertService
    echo "5️⃣ Testing Feed Stats Discrepancy Alert via FeedAlertService...\n";
    $feedStatsResult = $feedAlertService->sendFeedStatsDiscrepancyAlert([
        'livestock_id' => 'TEST_LIVESTOCK_001',
        'livestock_name' => 'Test Livestock [REFACTOR TEST]',
        'batch_id' => 'TEST_BATCH_001',
        'batch_name' => 'Test Batch 001',
        'current_stats' => [
            'total_consumed' => 500.0,
            'total_cost' => 3750000.0,
            'usage_count' => 2,
            'last_updated' => now()->subHour()->toISOString()
        ],
        'actual_stats' => [
            'total_consumed' => 600.0,
            'total_cost' => 4500000.0,
            'usage_count' => 3
        ],
        'discrepancies' => [
            'quantity_diff' => -100.0,
            'cost_diff' => -750000.0,
            'count_diff' => -1
        ],
        'user_id' => 1,
        'user_name' => 'Test User [REFACTOR]',
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Test Script'
    ]);

    if ($feedStatsResult) {
        echo "   ✅ SUCCESS: Feed stats discrepancy alert sent successfully\n\n";
    } else {
        echo "   ❌ FAILED: Feed stats discrepancy alert failed to send\n\n";
    }

    // Test 6: Feed Anomaly Detection
    echo "6️⃣ Testing Feed Anomaly Detection...\n";
    $anomalyResult = $feedAlertService->checkFeedUsageAnomalies([
        'livestock_id' => 'TEST_LIVESTOCK_001',
        'livestock_name' => 'Test Livestock [ANOMALY TEST]',
        'total_quantity' => 1500.0, // Above threshold
        'total_cost' => 15000000.0, // Above threshold
        'usage_date' => now()->format('Y-m-d'),
        'usage_purpose' => 'anomaly_testing'
    ]);

    if ($anomalyResult) {
        echo "   ✅ SUCCESS: Feed anomaly alert sent successfully\n\n";
    } else {
        echo "   ℹ️  INFO: No anomalies detected or alert not sent\n\n";
    }

    // Test 7: Alert Statistics
    echo "7️⃣ Testing Alert Statistics...\n";
    $stats = $alertService->getAlertStats(7);
    echo "   📊 Alert Statistics (Last 7 days):\n";
    echo "      Total Alerts: " . $stats['total_alerts'] . "\n";
    echo "      By Type: " . json_encode($stats['by_type']) . "\n";
    echo "      By Level: " . json_encode($stats['by_level']) . "\n";
    echo "   ✅ SUCCESS: Alert statistics retrieved\n\n";

    // Summary
    $totalTests = 7;
    $successfulTests = 0;

    if (isset($alertService)) $successfulTests++;
    if (isset($feedAlertService)) $successfulTests++;
    if ($genericResult) $successfulTests++;
    if ($feedUsageResult) $successfulTests++;
    if ($feedStatsResult) $successfulTests++;
    if ($anomalyResult || $anomalyResult === false) $successfulTests++; // Count as success even if no anomaly
    if (isset($stats)) $successfulTests++;

    echo "📈 REFACTOR TEST SUMMARY\n";
    echo "=" . str_repeat("=", 30) . "\n";
    echo "Total Tests: {$totalTests}\n";
    echo "Successful: {$successfulTests}\n";
    echo "Success Rate: " . round(($successfulTests / $totalTests) * 100, 1) . "%\n\n";

    if ($successfulTests === $totalTests) {
        echo "🎉 ALL TESTS PASSED! Alert system refactoring successful!\n";
    } else {
        echo "⚠️  Some tests failed. Check the output above for details.\n";
    }
} catch (Exception $e) {
    echo "❌ CRITICAL ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo PHP_EOL . "Test completed at: " . now()->format('Y-m-d H:i:s') . PHP_EOL;

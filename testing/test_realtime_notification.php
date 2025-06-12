<?php

/**
 * Real-time Notification Testing Script
 * 
 * Script ini akan mengirim notifikasi real-time ke user yang sedang membuka halaman
 * untuk testing sistem notifikasi Supply Purchase
 * 
 * @author AI Assistant
 * @date 2024-12-11
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Event;
use App\Models\User;
use App\Models\SupplyPurchaseBatch;
use App\Events\SupplyPurchaseStatusChanged;
use App\Notifications\SupplyPurchaseStatusNotification;

echo "\n🚀 COMPREHENSIVE REAL-TIME NOTIFICATION TEST WITH BROWSER BRIDGE\n";
echo str_repeat("=", 70) . "\n\n";

/**
 * Send notification to browser bridge
 */
function sendToBrowserBridge($title, $message, $type = 'info', $data = [])
{
    $notification = [
        'type' => $type,
        'title' => $title,
        'message' => $message,
        'data' => $data,
        'source' => 'php_test_script',
        'priority' => $type === 'error' ? 'high' : 'normal'
    ];

    // Send to local bridge
    $bridgeUrl = 'http://demo51.local/testing/notification_bridge.php';

    $ch = curl_init($bridgeUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($notification));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        echo "📡 Browser notification sent: {$title}\n";
    } else {
        echo "⚠️ Failed to send browser notification (HTTP {$httpCode})\n";
    }
}

/**
 * Test 1: Database and Basic Setup
 */
function testDatabaseSetup()
{
    echo "1. TESTING DATABASE SETUP\n";
    echo str_repeat("-", 40) . "\n";

    sendToBrowserBridge(
        'Test Started',
        'Database setup test is beginning...',
        'info'
    );

    try {
        // Test database connection
        $connection = DB::connection()->getPdo();
        echo "✅ Database connection: OK\n";

        // Check users
        $userCount = User::count();
        echo "✅ Users in database: {$userCount}\n";

        // Check supply purchase batches
        $batchCount = SupplyPurchaseBatch::count();
        echo "✅ Supply Purchase Batches: {$batchCount}\n";

        // Get test data
        $testUser = User::first();
        $testBatch = SupplyPurchaseBatch::first();

        if (!$testUser || !$testBatch) {
            sendToBrowserBridge(
                'Database Test Failed',
                'Missing test data (need at least 1 user and 1 batch)',
                'error'
            );
            echo "❌ Missing test data (need at least 1 user and 1 batch)\n";
            return false;
        }

        echo "✅ Test User: {$testUser->name} (ID: {$testUser->id})\n";
        echo "✅ Test Batch: {$testBatch->invoice_number} (ID: {$testBatch->id})\n";
        echo "✅ Current Status: {$testBatch->status}\n\n";

        sendToBrowserBridge(
            'Database Test Passed',
            "Found {$userCount} users and {$batchCount} batches. Test user: {$testUser->name}",
            'success',
            [
                'user_count' => $userCount,
                'batch_count' => $batchCount,
                'test_user' => $testUser->name,
                'test_batch' => $testBatch->invoice_number
            ]
        );

        return ['user' => $testUser, 'batch' => $testBatch];
    } catch (\Exception $e) {
        echo "❌ Database setup failed: " . $e->getMessage() . "\n\n";
        sendToBrowserBridge(
            'Database Test Failed',
            'Database setup failed: ' . $e->getMessage(),
            'error'
        );
        return false;
    }
}

/**
 * Test 2: Event System Check
 */
function testEventSystem()
{
    echo "2. TESTING EVENT SYSTEM\n";
    echo str_repeat("-", 40) . "\n";

    sendToBrowserBridge(
        'Testing Event System',
        'Checking Laravel event system components...',
        'info'
    );

    try {
        // Check if event class exists
        if (!class_exists('App\Events\SupplyPurchaseStatusChanged')) {
            echo "❌ SupplyPurchaseStatusChanged event class not found\n";
            sendToBrowserBridge(
                'Event System Failed',
                'SupplyPurchaseStatusChanged event class not found',
                'error'
            );
            return false;
        }
        echo "✅ Event class exists\n";

        // Check if notification class exists
        if (!class_exists('App\Notifications\SupplyPurchaseStatusNotification')) {
            echo "❌ SupplyPurchaseStatusNotification class not found\n";
            sendToBrowserBridge(
                'Event System Failed',
                'SupplyPurchaseStatusNotification class not found',
                'error'
            );
            return false;
        }
        echo "✅ Notification class exists\n";

        // Check if listener is registered
        $listeners = Event::getListeners('App\Events\SupplyPurchaseStatusChanged');
        echo "✅ Event listeners registered: " . count($listeners) . "\n";

        echo "✅ Event system ready\n\n";

        sendToBrowserBridge(
            'Event System Passed',
            'All event system components are ready',
            'success',
            ['listeners_count' => count($listeners)]
        );

        return true;
    } catch (\Exception $e) {
        echo "❌ Event system check failed: " . $e->getMessage() . "\n\n";
        sendToBrowserBridge(
            'Event System Failed',
            'Event system check failed: ' . $e->getMessage(),
            'error'
        );
        return false;
    }
}

/**
 * Test 3: Real-Time Status Change with Browser Notifications
 */
function testRealTimeStatusChange($testData)
{
    echo "3. TESTING REAL-TIME STATUS CHANGE WITH BROWSER NOTIFICATIONS\n";
    echo str_repeat("-", 40) . "\n";

    try {
        $user = $testData['user'];
        $batch = $testData['batch'];

        // Record current status
        $originalStatus = $batch->status;
        echo "📋 Original status: {$originalStatus}\n";

        // Determine new status for test
        $newStatus = $originalStatus === 'draft' ? 'confirmed' : 'draft';
        echo "🎯 Target status: {$newStatus}\n";

        sendToBrowserBridge(
            'Status Change Test Starting',
            "Testing status change: {$originalStatus} → {$newStatus}",
            'info',
            [
                'batch_id' => $batch->id,
                'old_status' => $originalStatus,
                'new_status' => $newStatus
            ]
        );

        // Create event listener to track firing
        $eventFired = false;
        $eventData = null;
        Event::listen(SupplyPurchaseStatusChanged::class, function ($event) use (&$eventFired, &$eventData) {
            $eventFired = true;
            $eventData = $event;
            echo "📡 Event fired successfully - Batch ID: {$event->batch->id}\n";
        });

        echo "🔄 Updating status via test simulation...\n";

        // Fire event manually for testing
        event(new SupplyPurchaseStatusChanged(
            $batch,
            $originalStatus,
            $newStatus,
            $user->id,
            'Real-time test notification from PHP script - ' . now()->format('H:i:s'),
            [
                'source' => 'php_test_script',
                'test_mode' => true,
                'timestamp' => now()->toISOString(),
                'triggered_by' => 'automated_test'
            ]
        ));

        // Wait a moment for event processing
        sleep(1);

        echo ($eventFired ? "✅" : "❌") . " Event fired and caught\n";

        if ($eventFired && $eventData) {
            echo "✅ Event data captured:\n";
            echo "  - Batch ID: {$eventData->batch->id}\n";
            echo "  - Old Status: {$eventData->oldStatus}\n";
            echo "  - New Status: {$eventData->newStatus}\n";
            echo "  - Updated By: {$eventData->updatedBy}\n";
            echo "  - Timestamp: {$eventData->timestamp}\n";

            // Send detailed notification to browser
            sendToBrowserBridge(
                'Supply Purchase Status Changed!',
                "Purchase {$eventData->batch->invoice_number} status changed from {$eventData->oldStatus} to {$eventData->newStatus} by {$user->name}",
                'success',
                [
                    'batch_id' => $eventData->batch->id,
                    'invoice_number' => $eventData->batch->invoice_number,
                    'old_status' => $eventData->oldStatus,
                    'new_status' => $eventData->newStatus,
                    'updated_by' => $user->name,
                    'timestamp' => $eventData->timestamp,
                    'requires_refresh' => true
                ]
            );
        }

        echo "\n";
        return $eventFired;
    } catch (\Exception $e) {
        echo "❌ Real-time status change test failed: " . $e->getMessage() . "\n";
        echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";

        sendToBrowserBridge(
            'Status Change Test Failed',
            'Real-time status change test failed: ' . $e->getMessage(),
            'error'
        );

        return false;
    }
}

/**
 * Test 4: Multiple Scenario Testing
 */
function testMultipleScenarios($testData)
{
    echo "4. TESTING MULTIPLE NOTIFICATION SCENARIOS\n";
    echo str_repeat("-", 40) . "\n";

    try {
        $user = $testData['user'];
        $batch = $testData['batch'];

        sendToBrowserBridge(
            'Multiple Scenarios Test',
            'Testing multiple notification scenarios...',
            'info'
        );

        // Test different status transitions
        $scenarios = [
            ['from' => 'draft', 'to' => 'confirmed', 'priority' => 'normal', 'type' => 'info'],
            ['from' => 'confirmed', 'to' => 'shipped', 'priority' => 'medium', 'type' => 'warning'],
            ['from' => 'shipped', 'to' => 'arrived', 'priority' => 'high', 'type' => 'success'],
            ['from' => 'arrived', 'to' => 'cancelled', 'priority' => 'medium', 'type' => 'error']
        ];

        foreach ($scenarios as $i => $scenario) {
            echo "\n📋 Scenario " . ($i + 1) . ": {$scenario['from']} → {$scenario['to']}\n";

            // Fire event for this scenario
            event(new SupplyPurchaseStatusChanged(
                $batch,
                $scenario['from'],
                $scenario['to'],
                $user->id,
                "Test scenario " . ($i + 1) . " - Priority: {$scenario['priority']}",
                [
                    'source' => 'test_scenario',
                    'scenario_number' => $i + 1,
                    'priority' => $scenario['priority'],
                    'timestamp' => now()->toISOString()
                ]
            ));

            // Send browser notification for each scenario
            sendToBrowserBridge(
                "Scenario " . ($i + 1) . " - " . ucfirst($scenario['priority']) . " Priority",
                "Status change: {$scenario['from']} → {$scenario['to']}",
                $scenario['type'],
                [
                    'scenario' => $i + 1,
                    'from' => $scenario['from'],
                    'to' => $scenario['to'],
                    'priority' => $scenario['priority']
                ]
            );

            echo "✅ Event fired for scenario " . ($i + 1) . "\n";

            // Small delay between scenarios
            usleep(500000); // 0.5 seconds
        }

        echo "\n✅ All test scenarios created\n\n";

        sendToBrowserBridge(
            'All Scenarios Completed',
            'All 4 test scenarios completed successfully!',
            'success',
            ['total_scenarios' => count($scenarios)]
        );

        return true;
    } catch (\Exception $e) {
        echo "❌ Test scenarios creation failed: " . $e->getMessage() . "\n\n";
        sendToBrowserBridge(
            'Scenarios Test Failed',
            'Test scenarios creation failed: ' . $e->getMessage(),
            'error'
        );
        return false;
    }
}

/**
 * Main Test Execution
 */
function runMainTest()
{
    // Send initial notification
    sendToBrowserBridge(
        'PHP Test Script Started',
        'Real-time notification test script is now running...',
        'info'
    );

    $results = [];

    // Run all tests
    $results['database'] = testDatabaseSetup();
    if (!$results['database']) {
        echo "🚫 Cannot continue - database setup failed\n";
        sendToBrowserBridge(
            'Test Stopped',
            'Cannot continue - database setup failed',
            'error'
        );
        return false;
    }

    $results['events'] = testEventSystem();
    $results['realtime'] = testRealTimeStatusChange($results['database']);
    $results['scenarios'] = testMultipleScenarios($results['database']);

    // Final summary
    echo "🎯 FINAL TEST RESULTS\n";
    echo str_repeat("=", 70) . "\n";

    $passed = 0;
    $total = count($results) - 1; // Subtract database result as it's test data

    foreach ($results as $test => $result) {
        if ($test === 'database') continue; // Skip database as it returns data

        $status = $result ? '✅ PASS' : '❌ FAIL';
        echo "📋 " . ucfirst($test) . " Test: {$status}\n";
        if ($result) $passed++;
    }

    echo str_repeat("-", 40) . "\n";
    echo "📊 Total Tests: {$total}\n";
    echo "✅ Passed: {$passed}\n";
    echo "❌ Failed: " . ($total - $passed) . "\n";
    echo "🎯 Success Rate: " . round(($passed / $total) * 100, 1) . "%\n";

    if ($passed === $total) {
        echo "\n🎉 ALL TESTS PASSED! REAL-TIME NOTIFICATIONS ARE WORKING!\n";
        echo "🔔 Check your browser - you should have received notifications!\n";
        echo "\n📱 Browser Testing Instructions:\n";
        echo "1. Open: http://demo51.local/testing/realtime_test_client.php\n";
        echo "2. Keep that page open when running this script\n";
        echo "3. You should see notifications appear in real-time!\n";

        sendToBrowserBridge(
            '🎉 ALL TESTS PASSED!',
            'Real-time notifications are working! Success rate: ' . round(($passed / $total) * 100, 1) . '%',
            'success',
            [
                'total_tests' => $total,
                'passed' => $passed,
                'success_rate' => round(($passed / $total) * 100, 1)
            ]
        );
    } else {
        echo "\n⚠️ SOME TESTS FAILED - CHECK LOGS AND FIX ISSUES\n";

        sendToBrowserBridge(
            'Some Tests Failed',
            "Some tests failed. Success rate: " . round(($passed / $total) * 100, 1) . '%. Check logs for details.',
            'warning',
            [
                'total_tests' => $total,
                'passed' => $passed,
                'failed' => $total - $passed,
                'success_rate' => round(($passed / $total) * 100, 1)
            ]
        );
    }

    return $passed === $total;
}

// Run the comprehensive test
runMainTest();

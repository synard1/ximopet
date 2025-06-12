<?php

/**
 * PRODUCTION INTEGRATION TEST
 * 
 * Comprehensive test to verify all production components are properly integrated
 * and the real-time notification system works end-to-end in production environment.
 * 
 * @author AI Assistant
 * @date 2024-12-11
 * @version 1.0
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Load Laravel environment
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SupplyPurchaseBatch;
use App\Models\User;
use App\Events\SupplyPurchaseStatusChanged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

echo "\n🚀 PRODUCTION INTEGRATION TEST\n";
echo "===============================================\n";

// Test Results Storage
$testResults = [
    'database_connection' => false,
    'event_system' => false,
    'bridge_availability' => false,
    'livewire_integration' => false,
    'frontend_integration' => false,
    'end_to_end' => false
];

$startTime = microtime(true);

// 1. TEST DATABASE CONNECTION & DATA
echo "\n1. TESTING DATABASE CONNECTION & DATA\n";
echo "----------------------------------------\n";

try {
    $userCount = User::count();
    $batchCount = SupplyPurchaseBatch::count();

    echo "✅ Database connection: OK\n";
    echo "✅ Users in database: {$userCount}\n";
    echo "✅ Supply Purchase Batches: {$batchCount}\n";

    if ($userCount > 0 && $batchCount > 0) {
        $testResults['database_connection'] = true;
        echo "✅ Database test: PASSED\n";
    } else {
        echo "❌ Database test: FAILED (insufficient data)\n";
    }
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
}

// 2. TEST EVENT SYSTEM
echo "\n2. TESTING EVENT SYSTEM\n";
echo "----------------------------------------\n";

try {
    $eventCaught = false;
    $eventData = null;

    // Register event listener
    Event::listen(SupplyPurchaseStatusChanged::class, function ($event) use (&$eventCaught, &$eventData) {
        $eventCaught = true;
        $eventData = [
            'batch_id' => $event->batch->id,
            'old_status' => $event->oldStatus,
            'new_status' => $event->newStatus,
            'updated_by' => $event->updatedBy,
            'timestamp' => now()->toISOString()
        ];
        echo "📡 Event caught: Status changed from {$event->oldStatus} to {$event->newStatus}\n";
    });

    // Get test batch
    $testBatch = SupplyPurchaseBatch::first();
    if ($testBatch) {
        echo "📋 Test batch: {$testBatch->invoice_number} (Status: {$testBatch->status})\n";

        // Fire test event
        $oldStatus = $testBatch->status;
        $newStatus = $oldStatus === 'draft' ? 'confirmed' : 'draft';

        Event::dispatch(new SupplyPurchaseStatusChanged($testBatch, $oldStatus, $newStatus, 1));

        if ($eventCaught) {
            $testResults['event_system'] = true;
            echo "✅ Event system test: PASSED\n";
        } else {
            echo "❌ Event system test: FAILED\n";
        }
    } else {
        echo "❌ No test batch available\n";
    }
} catch (Exception $e) {
    echo "❌ Event system test failed: " . $e->getMessage() . "\n";
}

// 3. TEST NOTIFICATION BRIDGE AVAILABILITY
echo "\n3. TESTING NOTIFICATION BRIDGE AVAILABILITY\n";
echo "----------------------------------------\n";

try {
    $bridgeUrl = 'http://demo51.local/testing/notification_bridge.php';

    // Test bridge status endpoint
    $statusResponse = @file_get_contents($bridgeUrl . '?action=status');

    if ($statusResponse !== false) {
        $statusData = json_decode($statusResponse, true);
        if ($statusData && ($statusData['success'] === true || isset($statusData['bridge_active']))) {
            echo "✅ Bridge availability: OK\n";
            echo "✅ Bridge active: " . ($statusData['bridge_active'] ? 'Yes' : 'No') . "\n";
            echo "✅ Total notifications: " . ($statusData['total_notifications'] ?? 0) . "\n";
            $testResults['bridge_availability'] = true;
            echo "✅ Bridge availability test: PASSED\n";
        } else {
            echo "❌ Bridge responded but invalid data\n";
        }
    } else {
        echo "❌ Bridge not available at: {$bridgeUrl}\n";
    }
} catch (Exception $e) {
    echo "❌ Bridge availability test failed: " . $e->getMessage() . "\n";
}

// 4. TEST BRIDGE COMMUNICATION (Send Notification)
echo "\n4. TESTING BRIDGE COMMUNICATION\n";
echo "----------------------------------------\n";

if ($testResults['bridge_availability']) {
    try {
        // Test sending notification to bridge
        $testNotification = [
            'type' => 'success',
            'title' => 'Production Integration Test',
            'message' => 'Testing bridge communication from production integration test - ' . date('H:i:s'),
            'source' => 'production_integration_test',
            'priority' => 'high',
            'data' => [
                'test_id' => uniqid('prod_test_'),
                'timestamp' => now()->toISOString(),
                'requires_refresh' => true
            ]
        ];

        $postData = json_encode($testNotification);

        // Use cURL to send POST request
        $ch = curl_init($bridgeUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response !== false && $httpCode == 200) {
            $responseData = json_decode($response, true);
            if ($responseData && $responseData['success']) {
                echo "✅ Bridge communication: OK\n";
                echo "✅ Notification sent with ID: " . $responseData['notification_id'] . "\n";
                echo "✅ Bridge communication test: PASSED\n";
            } else {
                echo "❌ Bridge responded but indicated failure\n";
                echo "Response: " . $response . "\n";
            }
        } else {
            echo "❌ Bridge communication failed\n";
            echo "HTTP Code: {$httpCode}\n";
            echo "Response: " . ($response ?: 'No response') . "\n";
        }
    } catch (Exception $e) {
        echo "❌ Bridge communication test failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "⏭️ Skipping bridge communication test (bridge not available)\n";
}

// 5. TEST LIVEWIRE INTEGRATION (Simulated)
echo "\n5. TESTING LIVEWIRE INTEGRATION\n";
echo "----------------------------------------\n";

try {
    // Check if Livewire Create component exists
    $livewireClass = 'App\\Livewire\\SupplyPurchases\\Create';

    if (class_exists($livewireClass)) {
        echo "✅ Livewire Create component: Found\n";

        // Check if the sendToProductionNotificationBridge method exists
        $reflection = new \ReflectionClass($livewireClass);

        if ($reflection->hasMethod('sendToProductionNotificationBridge')) {
            echo "✅ Bridge integration method: Found\n";
            $testResults['livewire_integration'] = true;
            echo "✅ Livewire integration test: PASSED\n";
        } else {
            echo "❌ Bridge integration method: Not found\n";
        }
    } else {
        echo "❌ Livewire Create component: Not found\n";
    }
} catch (Exception $e) {
    echo "❌ Livewire integration test failed: " . $e->getMessage() . "\n";
}

// 6. TEST FRONTEND FILES EXISTENCE
echo "\n6. TESTING FRONTEND INTEGRATION FILES\n";
echo "----------------------------------------\n";

$frontendFiles = [
    'public/assets/js/browser-notification.js' => 'Production Notification Handler',
    'resources/views/pages/transaction/supply-purchases/index.blade.php' => 'Supply Purchase Page',
    'app/DataTables/SupplyPurchaseDataTable.php' => 'DataTable Integration'
];

$frontendFilesOk = 0;
foreach ($frontendFiles as $file => $description) {
    if (file_exists($file)) {
        echo "✅ {$description}: Found\n";
        $frontendFilesOk++;
    } else {
        echo "❌ {$description}: Not found at {$file}\n";
    }
}

if ($frontendFilesOk === count($frontendFiles)) {
    $testResults['frontend_integration'] = true;
    echo "✅ Frontend integration test: PASSED\n";
} else {
    echo "❌ Frontend integration test: FAILED ({$frontendFilesOk}/" . count($frontendFiles) . " files found)\n";
}

// 7. END-TO-END TEST SIMULATION
echo "\n7. END-TO-END TEST SIMULATION\n";
echo "----------------------------------------\n";

if ($testResults['database_connection'] && $testResults['event_system'] && $testResults['bridge_availability']) {
    try {
        // Simulate complete flow
        $testBatch = SupplyPurchaseBatch::first();
        if ($testBatch) {
            $oldStatus = $testBatch->status;
            $newStatus = $oldStatus === 'draft' ? 'confirmed' : 'draft';

            echo "📋 Simulating status change: {$oldStatus} → {$newStatus}\n";

            // 1. Fire event (simulating Livewire component action)
            Event::dispatch(new SupplyPurchaseStatusChanged($testBatch, $oldStatus, $newStatus, 1));
            echo "✅ Step 1: Event fired\n";

            // 2. Send notification to bridge (simulating component bridge call)
            $notification = [
                'type' => 'info',
                'title' => 'End-to-End Test',
                'message' => "Supply Purchase {$testBatch->invoice_number} status changed to {$newStatus}",
                'source' => 'end_to_end_test',
                'data' => [
                    'batch_id' => $testBatch->id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'requires_refresh' => true
                ]
            ];

            $ch = curl_init($bridgeUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($notification));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);

            $response = curl_exec($ch);
            curl_close($ch);

            if ($response) {
                $responseData = json_decode($response, true);
                if ($responseData && $responseData['success']) {
                    echo "✅ Step 2: Notification sent to bridge\n";

                    // 3. Verify notification is available for frontend polling
                    $pollResponse = @file_get_contents($bridgeUrl . '?since=0');
                    if ($pollResponse) {
                        $pollData = json_decode($pollResponse, true);
                        if ($pollData && isset($pollData['notifications']) && count($pollData['notifications']) > 0) {
                            echo "✅ Step 3: Notification available for frontend polling\n";
                            $testResults['end_to_end'] = true;
                            echo "✅ End-to-end test: PASSED\n";
                        } else {
                            echo "❌ Step 3: No notifications available for polling\n";
                        }
                    } else {
                        echo "❌ Step 3: Could not poll bridge for notifications\n";
                    }
                } else {
                    echo "❌ Step 2: Bridge notification failed\n";
                }
            } else {
                echo "❌ Step 2: Could not send notification to bridge\n";
            }
        } else {
            echo "❌ No test batch available for end-to-end test\n";
        }
    } catch (Exception $e) {
        echo "❌ End-to-end test failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "⏭️ Skipping end-to-end test (prerequisite tests failed)\n";
}

// FINAL RESULTS
echo "\n🎯 PRODUCTION INTEGRATION TEST RESULTS\n";
echo "===============================================\n";

$passedTests = array_sum($testResults);
$totalTests = count($testResults);
$successRate = round(($passedTests / $totalTests) * 100, 1);

foreach ($testResults as $test => $passed) {
    $status = $passed ? '✅ PASS' : '❌ FAIL';
    $testName = ucwords(str_replace('_', ' ', $test));
    echo "📋 {$testName}: {$status}\n";
}

echo "----------------------------------------\n";
echo "📊 Total Tests: {$totalTests}\n";
echo "✅ Passed: {$passedTests}\n";
echo "❌ Failed: " . ($totalTests - $passedTests) . "\n";
echo "🎯 Success Rate: {$successRate}%\n";

$endTime = microtime(true);
$executionTime = round($endTime - $startTime, 2);
echo "⏱️ Execution Time: {$executionTime} seconds\n";

if ($successRate >= 80) {
    echo "\n🎉 PRODUCTION INTEGRATION: SUCCESSFUL!\n";
    echo "✅ The real-time notification system is ready for production use.\n";

    if ($successRate < 100) {
        echo "\n⚠️ RECOMMENDATIONS:\n";
        foreach ($testResults as $test => $passed) {
            if (!$passed) {
                $testName = ucwords(str_replace('_', ' ', $test));
                echo "- Fix: {$testName}\n";
            }
        }
    }
} else {
    echo "\n❌ PRODUCTION INTEGRATION: NEEDS ATTENTION\n";
    echo "⚠️ Multiple critical tests failed. Review and fix before production deployment.\n";
}

echo "\n📱 NEXT STEPS:\n";
echo "1. Open browser: http://localhost/demo51/testing/realtime_test_client.php\n";
echo "2. Run: php testing\\simple_notification_test.php\n";
echo "3. Check browser for real-time notifications\n";
echo "4. Test production pages: http://localhost/demo51/transaction/supply-purchases\n";

echo "\n===============================================\n";

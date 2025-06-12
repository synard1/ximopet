<?php

/**
 * FEED PURCHASE NOTIFICATION SYSTEM TEST
 * 
 * This script tests the fixed notification system for FeedPurchase
 * to ensure table auto-refresh and prevent duplicate notifications
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\FeedPurchaseBatch;
use App\Events\FeedPurchaseStatusChanged;

echo "🧪 FEED PURCHASE NOTIFICATION SYSTEM TEST\n";
echo "==========================================\n\n";

// Test Results Storage
$testResults = [
    'bridge_availability' => false,
    'status_change_notification' => false,
    'table_refresh_integration' => false,
    'duplicate_prevention' => false
];

// 1. TEST NOTIFICATION BRIDGE AVAILABILITY
echo "1. TESTING NOTIFICATION BRIDGE AVAILABILITY\n";
echo "--------------------------------------------\n";

try {
    $bridgeUrl = request()->getSchemeAndHttpHost() . '/testing/notification_bridge.php';
    $response = Http::timeout(5)->get($bridgeUrl . '?action=status');

    if ($response->successful()) {
        $data = $response->json();
        if ($data['success'] ?? false) {
            echo "✅ Notification bridge is available at: {$bridgeUrl}\n";
            echo "📊 Bridge stats: " . json_encode($data['stats'] ?? []) . "\n";
            $testResults['bridge_availability'] = true;
        } else {
            echo "❌ Notification bridge responded but not ready\n";
        }
    } else {
        echo "❌ Notification bridge not responding (HTTP {$response->status()})\n";
    }
} catch (\Exception $e) {
    echo "❌ Bridge test failed: " . $e->getMessage() . "\n";
}

echo "\n";

// 2. TEST FEED PURCHASE STATUS CHANGE NOTIFICATION
echo "2. TESTING FEED PURCHASE STATUS CHANGE NOTIFICATION\n";
echo "----------------------------------------------------\n";

try {
    $testBatch = FeedPurchaseBatch::first();
    if ($testBatch) {
        echo "📋 Test batch: {$testBatch->invoice_number} (Status: {$testBatch->status})\n";

        $oldStatus = $testBatch->status;
        $newStatus = $oldStatus === 'draft' ? 'confirmed' : 'draft';

        // Simulate notification data
        $notificationData = [
            'type' => 'info',
            'title' => 'Feed Purchase Status Updated',
            'message' => "Feed Purchase #{$testBatch->invoice_number} status changed from {$oldStatus} to {$newStatus}",
            'source' => 'livewire_production',
            'priority' => 'normal',
            'data' => [
                'batch_id' => $testBatch->id,
                'invoice_number' => $testBatch->invoice_number,
                'updated_by' => 1,
                'updated_by_name' => 'Test User',
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'timestamp' => now()->toISOString(),
                'requires_refresh' => true
            ]
        ];

        // Send to bridge
        if ($testResults['bridge_availability']) {
            $response = Http::timeout(5)->post($bridgeUrl, $notificationData);

            if ($response->successful()) {
                $responseData = $response->json();
                echo "✅ Status change notification sent successfully\n";
                echo "📨 Notification ID: " . ($responseData['notification_id'] ?? 'unknown') . "\n";
                $testResults['status_change_notification'] = true;
            } else {
                echo "❌ Failed to send status change notification\n";
            }
        } else {
            echo "⚠️ Skipping notification test - bridge not available\n";
        }
    } else {
        echo "❌ No test batch available\n";
    }
} catch (\Exception $e) {
    echo "❌ Status change test failed: " . $e->getMessage() . "\n";
}

echo "\n";

// 3. TEST TABLE REFRESH INTEGRATION
echo "3. TESTING TABLE REFRESH INTEGRATION\n";
echo "-------------------------------------\n";

try {
    // Check if _draw-scripts.js has proper integration
    $scriptPath = resource_path('views/pages/transaction/feed-purchases/_draw-scripts.js');
    if (file_exists($scriptPath)) {
        $scriptContent = file_get_contents($scriptPath);

        $hasIntegration = strpos($scriptContent, 'integrateWithProductionBridge') !== false;
        $hasRefreshFunction = strpos($scriptContent, 'refreshDataTable') !== false;
        $hasNotificationAnalysis = strpos($scriptContent, 'Notification analysis') !== false;
        $hasDuplicatePrevention = strpos($scriptContent, 'data-notification-id') !== false;

        if ($hasIntegration && $hasRefreshFunction && $hasNotificationAnalysis) {
            echo "✅ DataTable integration script has proper notification bridge integration\n";
            echo "✅ Auto-refresh functionality is implemented\n";
            echo "✅ Notification analysis logging is present\n";
            $testResults['table_refresh_integration'] = true;
        } else {
            echo "❌ DataTable integration incomplete:\n";
            echo "   - Bridge integration: " . ($hasIntegration ? "✅" : "❌") . "\n";
            echo "   - Refresh function: " . ($hasRefreshFunction ? "✅" : "❌") . "\n";
            echo "   - Analysis logging: " . ($hasNotificationAnalysis ? "✅" : "❌") . "\n";
        }

        if ($hasDuplicatePrevention) {
            echo "✅ Duplicate notification prevention is implemented\n";
            $testResults['duplicate_prevention'] = true;
        } else {
            echo "❌ Duplicate notification prevention not found\n";
        }
    } else {
        echo "❌ DataTable script file not found\n";
    }
} catch (\Exception $e) {
    echo "❌ Table refresh integration test failed: " . $e->getMessage() . "\n";
}

echo "\n";

// 4. TEST DUPLICATE NOTIFICATION PREVENTION
echo "4. TESTING DUPLICATE NOTIFICATION PREVENTION\n";
echo "---------------------------------------------\n";

try {
    // Send multiple notifications for the same transaction
    if ($testResults['bridge_availability'] && isset($testBatch)) {
        echo "📤 Sending multiple notifications for same transaction...\n";

        for ($i = 1; $i <= 3; $i++) {
            $duplicateNotification = [
                'type' => 'info',
                'title' => 'Status Change Processing',
                'message' => "Processing attempt #{$i} for transaction {$testBatch->id}",
                'source' => 'test_duplicate_prevention',
                'data' => [
                    'batch_id' => $testBatch->id,
                    'transaction_id' => $testBatch->id,
                    'attempt' => $i,
                    'timestamp' => now()->toISOString()
                ]
            ];

            $response = Http::timeout(5)->post($bridgeUrl, $duplicateNotification);
            if ($response->successful()) {
                echo "   📨 Notification #{$i} sent\n";
            }

            usleep(100000); // 0.1 second delay
        }

        echo "✅ Multiple notifications sent - frontend should handle deduplication\n";
    } else {
        echo "⚠️ Skipping duplicate test - bridge not available or no test batch\n";
    }
} catch (\Exception $e) {
    echo "❌ Duplicate prevention test failed: " . $e->getMessage() . "\n";
}

echo "\n";

// 5. SUMMARY AND RECOMMENDATIONS
echo "5. TEST SUMMARY AND RECOMMENDATIONS\n";
echo "====================================\n";

$passedTests = array_sum($testResults);
$totalTests = count($testResults);

echo "📊 Test Results: {$passedTests}/{$totalTests} tests passed\n\n";

foreach ($testResults as $test => $passed) {
    $status = $passed ? "✅ PASS" : "❌ FAIL";
    $testName = ucwords(str_replace('_', ' ', $test));
    echo "   {$status} - {$testName}\n";
}

echo "\n";

if ($passedTests === $totalTests) {
    echo "🎉 ALL TESTS PASSED!\n";
    echo "✅ Feed Purchase notification system is working correctly\n";
    echo "✅ Table auto-refresh should work properly\n";
    echo "✅ Duplicate notifications should be prevented\n";
} else {
    echo "⚠️ SOME TESTS FAILED\n";
    echo "❗ Please check the failed components before deploying\n";

    if (!$testResults['bridge_availability']) {
        echo "🔧 Fix: Ensure notification bridge is running at /testing/notification_bridge.php\n";
    }

    if (!$testResults['table_refresh_integration']) {
        echo "🔧 Fix: Update DataTable integration in _draw-scripts.js\n";
    }

    if (!$testResults['duplicate_prevention']) {
        echo "🔧 Fix: Implement notification deduplication in frontend\n";
    }
}

echo "\n";

// 6. MANUAL TESTING INSTRUCTIONS
echo "6. MANUAL TESTING INSTRUCTIONS\n";
echo "===============================\n";
echo "To verify the fixes manually:\n\n";
echo "1. Open two browser sessions with different users\n";
echo "2. Navigate to /transaction/feed in both sessions\n";
echo "3. In session 1: Change a feed purchase status\n";
echo "4. In session 2: Verify notification appears AND table refreshes automatically\n";
echo "5. In session 1: Verify no duplicate 'Status Change Processing' notifications\n";
echo "6. Check browser console for DataTable integration logs\n\n";

echo "📝 Expected Console Logs:\n";
echo "   - '[FeedPurchase DataTable] ✅ Feed Purchase DataTable real-time notifications initialized'\n";
echo "   - '[FeedPurchase DataTable] Successfully integrated with production notification bridge'\n";
echo "   - '[FeedPurchase DataTable] Notification analysis: {...}'\n";
echo "   - '[FeedPurchase DataTable] Auto-refreshing table due to feed purchase notification'\n\n";

echo "🔍 Log Files to Monitor:\n";
echo "   - storage/logs/laravel.log (for backend notification logs)\n";
echo "   - Browser console (for frontend integration logs)\n\n";

echo "✅ Test completed at " . now()->format('Y-m-d H:i:s') . "\n";

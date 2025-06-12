<?php

/**
 * Comprehensive Notification System Diagnostic Test
 * 
 * This script performs deep analysis of the notification system to identify
 * the exact reason why notifications are not appearing in the browser.
 * 
 * Created: December 12, 2024
 * Purpose: Debug notification integration issues
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../bootstrap/app.php';

use App\Models\User;
use App\Models\SupplyPurchaseBatch;
use App\Models\SupplyPurchase;
use App\Models\Farm;
use App\Models\Partner;
use App\Events\SupplyPurchaseStatusChanged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;

echo "=== COMPREHENSIVE NOTIFICATION DIAGNOSTIC TEST ===\n";
echo "Started at: " . now()->format('Y-m-d H:i:s') . "\n\n";

/**
 * Test 1: Basic System Components
 */
function testSystemComponents()
{
    echo "1. TESTING SYSTEM COMPONENTS\n";
    echo "=" . str_repeat("=", 40) . "\n";

    $checks = [
        'Laravel App' => app() !== null,
        'Database Connection' => DB::connection()->getPdo() !== null,
        'Event System' => class_exists(\Illuminate\Events\Dispatcher::class),
        'Broadcasting' => config('broadcasting.default') !== null,
        'Pusher Config' => config('broadcasting.connections.pusher.key') !== null,
        'Queue Config' => config('queue.default') !== null,
    ];

    foreach ($checks as $component => $status) {
        echo ($status ? "✅" : "❌") . " {$component}\n";
    }

    echo "\n";
    return array_filter($checks);
}

/**
 * Test 2: Event and Listener Registration
 */
function testEventRegistration()
{
    echo "2. TESTING EVENT REGISTRATION\n";
    echo "=" . str_repeat("=", 40) . "\n";

    $eventServiceProvider = app(\App\Providers\EventServiceProvider::class);
    $listeners = $eventServiceProvider->listens();

    $hasStatusChangedEvent = isset($listeners[\App\Events\SupplyPurchaseStatusChanged::class]);
    echo ($hasStatusChangedEvent ? "✅" : "❌") . " SupplyPurchaseStatusChanged event registered\n";

    if ($hasStatusChangedEvent) {
        $eventListeners = $listeners[\App\Events\SupplyPurchaseStatusChanged::class];
        foreach ($eventListeners as $listener) {
            echo "  - Listener: {$listener}\n";
        }
    }

    // Check if Event class exists and implements ShouldBroadcast
    $eventClass = \App\Events\SupplyPurchaseStatusChanged::class;
    $eventExists = class_exists($eventClass);
    echo ($eventExists ? "✅" : "❌") . " Event class exists\n";

    if ($eventExists) {
        $implements = class_implements($eventClass);
        $shouldBroadcast = in_array(\Illuminate\Contracts\Broadcasting\ShouldBroadcast::class, $implements);
        echo ($shouldBroadcast ? "✅" : "❌") . " Event implements ShouldBroadcast\n";
    }

    echo "\n";
    return $hasStatusChangedEvent && $eventExists;
}

/**
 * Test 3: Database Data Availability
 */
function testDataAvailability()
{
    echo "3. TESTING DATA AVAILABILITY\n";
    echo "=" . str_repeat("=", 40) . "\n";

    $userCount = User::count();
    $batchCount = SupplyPurchaseBatch::count();
    $farmCount = Farm::count();
    $partnerCount = Partner::count();

    echo "✅ Users: {$userCount}\n";
    echo "✅ Supply Purchase Batches: {$batchCount}\n";
    echo "✅ Farms: {$farmCount}\n";
    echo "✅ Partners: {$partnerCount}\n";

    if ($batchCount > 0) {
        $batch = SupplyPurchaseBatch::with('supplyPurchases')->first();
        echo "✅ Test batch found: ID {$batch->id}, Status: {$batch->status}\n";
        echo "  - Invoice: {$batch->invoice_number}\n";
        echo "  - Purchases: " . $batch->supplyPurchases->count() . "\n";
    }

    echo "\n";
    return $userCount > 0 && $batchCount > 0;
}

/**
 * Test 4: Event Broadcasting Test
 */
function testEventBroadcasting()
{
    echo "4. TESTING EVENT BROADCASTING\n";
    echo "=" . str_repeat("=", 40) . "\n";

    try {
        $user = User::first();
        $batch = SupplyPurchaseBatch::first();

        if (!$user || !$batch) {
            echo "❌ Missing test data (user or batch)\n";
            return false;
        }

        echo "✅ Test user: {$user->name} (ID: {$user->id})\n";
        echo "✅ Test batch: {$batch->invoice_number} (ID: {$batch->id})\n";

        // Test event creation
        $event = new SupplyPurchaseStatusChanged(
            $batch,
            'draft',
            'confirmed',
            $user->id,
            'Test broadcast from diagnostic script',
            [
                'source' => 'diagnostic_test',
                'timestamp' => now()->toISOString()
            ]
        );

        echo "✅ Event object created successfully\n";

        // Test event methods
        $channels = $event->broadcastOn();
        echo "✅ Broadcast channels: " . count($channels) . "\n";
        foreach ($channels as $channel) {
            if (method_exists($channel, 'name')) {
                echo "  - " . $channel->name . "\n";
            } else {
                echo "  - " . get_class($channel) . "\n";
            }
        }

        $broadcastAs = $event->broadcastAs();
        echo "✅ Broadcast as: {$broadcastAs}\n";

        $broadcastWith = $event->broadcastWith();
        echo "✅ Broadcast data keys: " . implode(', ', array_keys($broadcastWith)) . "\n";

        echo "\n";
        return true;
    } catch (\Exception $e) {
        echo "❌ Event broadcasting test failed: " . $e->getMessage() . "\n";
        echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
        return false;
    }
}

/**
 * Test 5: Live Event Firing Test
 */
function testLiveEventFiring()
{
    echo "5. TESTING LIVE EVENT FIRING\n";
    echo "=" . str_repeat("=", 40) . "\n";

    try {
        $user = User::first();
        $batch = SupplyPurchaseBatch::first();

        if (!$user || !$batch) {
            echo "❌ Missing test data\n";
            return false;
        }

        // Track if event was fired
        $eventFired = false;
        Event::listen(SupplyPurchaseStatusChanged::class, function ($event) use (&$eventFired) {
            $eventFired = true;
            echo "✅ Event listener called: {$event->batch->id}\n";
        });

        echo "📡 Firing SupplyPurchaseStatusChanged event...\n";

        // Fire the event
        $oldStatus = $batch->status ?: 'draft';
        $newStatus = $oldStatus === 'draft' ? 'confirmed' : 'draft';

        event(new SupplyPurchaseStatusChanged(
            $batch,
            $oldStatus,
            $newStatus,
            $user->id,
            'Live test from diagnostic script - ' . now()->format('H:i:s')
        ));

        // Wait a moment for async processing
        sleep(1);

        echo ($eventFired ? "✅" : "❌") . " Event was fired and caught by listener\n";

        // Check if logs were created
        $logPath = storage_path('logs/laravel.log');
        if (file_exists($logPath)) {
            $logContent = file_get_contents($logPath);
            $hasEventLog = strpos($logContent, 'SupplyPurchaseStatusChanged event fired') !== false;
            echo ($hasEventLog ? "✅" : "❌") . " Event logged in laravel.log\n";
        }

        echo "\n";
        return $eventFired;
    } catch (\Exception $e) {
        echo "❌ Live event firing failed: " . $e->getMessage() . "\n";
        echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
        return false;
    }
}

/**
 * Test 6: Livewire Component Analysis
 */
function testLivewireComponent()
{
    echo "6. TESTING LIVEWIRE COMPONENT\n";
    echo "=" . str_repeat("=", 40) . "\n";

    $componentPath = __DIR__ . '/../app/Livewire/SupplyPurchases/Create.php';

    if (!file_exists($componentPath)) {
        echo "❌ Livewire component not found\n";
        return false;
    }

    $componentContent = file_get_contents($componentPath);

    $checks = [
        'Event Import' => strpos($componentContent, 'use App\Events\SupplyPurchaseStatusChanged') !== false,
        'Event Firing' => strpos($componentContent, 'event(new SupplyPurchaseStatusChanged(') !== false,
        'Status Change Handler' => strpos($componentContent, 'public function handleStatusChanged') !== false,
        'User Notification Handler' => strpos($componentContent, 'public function handleUserNotification') !== false,
        'Echo Listeners' => strpos($componentContent, 'echo:supply-purchases,status-changed') !== false,
        'Notification Dispatch' => strpos($componentContent, "dispatch('notify-status-change'") !== false,
    ];

    foreach ($checks as $feature => $exists) {
        echo ($exists ? "✅" : "❌") . " {$feature}\n";
    }

    echo "\n";
    return array_filter($checks);
}

/**
 * Test 7: Frontend Integration Analysis
 */
function testFrontendIntegration()
{
    echo "7. TESTING FRONTEND INTEGRATION\n";
    echo "=" . str_repeat("=", 40) . "\n";

    $files = [
        'Browser Notification JS' => 'public/assets/js/browser-notification.js',
        'Echo Setup JS' => 'public/assets/js/echo-setup.js',
        'Supply Purchase Index' => 'resources/views/pages/transaction/supply-purchases/index.blade.php'
    ];

    foreach ($files as $name => $path) {
        $fullPath = __DIR__ . '/../' . $path;
        $exists = file_exists($fullPath);
        echo ($exists ? "✅" : "❌") . " {$name}\n";

        if ($exists) {
            $content = file_get_contents($fullPath);
            $size = number_format(filesize($fullPath)) . ' bytes';
            echo "  Size: {$size}\n";

            // Check key features
            if (strpos($path, 'browser-notification.js') !== false) {
                echo "  - showNotification function: " . (strpos($content, 'function showNotification') !== false ? "✅" : "❌") . "\n";
                echo "  - Permission request: " . (strpos($content, 'Notification.requestPermission') !== false ? "✅" : "❌") . "\n";
            }

            if (strpos($path, 'echo-setup.js') !== false) {
                echo "  - Echo listeners: " . (strpos($content, 'Echo.channel') !== false ? "✅" : "❌") . "\n";
                echo "  - Test functions: " . (strpos($content, 'testEcho') !== false ? "✅" : "❌") . "\n";
            }

            if (strpos($path, 'index.blade.php') !== false) {
                echo "  - Livewire event handlers: " . (strpos($content, 'Livewire.on') !== false ? "✅" : "❌") . "\n";
                echo "  - Notification handlers: " . (strpos($content, 'notify-status-change') !== false ? "✅" : "❌") . "\n";
            }
        }
    }

    echo "\n";
    return true;
}

/**
 * Test 8: Create Real Test Scenario
 */
function createTestScenario()
{
    echo "8. CREATING REAL TEST SCENARIO\n";
    echo "=" . str_repeat("=", 40) . "\n";

    try {
        $user = User::first();
        $batch = SupplyPurchaseBatch::first();

        if (!$user || !$batch) {
            echo "❌ Missing test data\n";
            return false;
        }

        echo "🎬 Creating realistic test scenario:\n";
        echo "  User: {$user->name}\n";
        echo "  Batch: {$batch->invoice_number}\n";
        echo "  Current Status: {$batch->status}\n";

        // Simulate status change
        $oldStatus = $batch->status ?: 'draft';
        $newStatus = match ($oldStatus) {
            'draft' => 'confirmed',
            'confirmed' => 'shipped',
            'shipped' => 'arrived',
            'arrived' => 'completed',
            default => 'confirmed'
        };

        echo "  Status Change: {$oldStatus} → {$newStatus}\n";

        // Log before firing event
        Log::info('=== DIAGNOSTIC TEST: Starting status change ===', [
            'batch_id' => $batch->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'user_id' => $user->id,
            'timestamp' => now()->toISOString()
        ]);

        echo "📡 Firing event with comprehensive logging...\n";

        // Fire the event
        event(new SupplyPurchaseStatusChanged(
            $batch,
            $oldStatus,
            $newStatus,
            $user->id,
            'DIAGNOSTIC TEST - Real scenario simulation at ' . now()->format('H:i:s'),
            [
                'source' => 'diagnostic_test_scenario',
                'test_type' => 'real_scenario',
                'priority' => 'high',
                'requires_refresh' => true,
                'diagnostic_mode' => true
            ]
        ));

        echo "✅ Event fired successfully\n";
        echo "⏱️  Waiting 3 seconds for processing...\n";
        sleep(3);

        // Check for notifications in database
        $notifications = DB::table('notifications')
            ->where('data', 'like', '%supply_purchase_status_changed%')
            ->where('created_at', '>', now()->subMinutes(1))
            ->count();

        echo "✅ Database notifications created: {$notifications}\n";

        echo "\n";
        echo "🔍 NEXT STEPS FOR MANUAL TESTING:\n";
        echo "1. Open browser to /transaction/supply-purchases\n";
        echo "2. Open browser console (F12)\n";
        echo "3. Look for these log messages:\n";
        echo "   - '📦 Supply Purchase page scripts loaded successfully'\n";
        echo "   - '🚀 Supply Purchase page initialized'\n";
        echo "   - '📢 Livewire notification received:'\n";
        echo "4. Test keyboard shortcuts:\n";
        echo "   - Ctrl+Shift+T: Test browser notification\n";
        echo "   - Ctrl+Shift+P: Test page notification\n";
        echo "5. Try running: testNotificationFromPage() in console\n";

        return true;
    } catch (\Exception $e) {
        echo "❌ Test scenario failed: " . $e->getMessage() . "\n";
        echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
        return false;
    }
}

/**
 * Run all tests
 */
function runAllTests()
{
    $results = [];

    $results['system_components'] = testSystemComponents();
    $results['event_registration'] = testEventRegistration();
    $results['data_availability'] = testDataAvailability();
    $results['event_broadcasting'] = testEventBroadcasting();
    $results['live_event_firing'] = testLiveEventFiring();
    $results['livewire_component'] = testLivewireComponent();
    $results['frontend_integration'] = testFrontendIntegration();
    $results['test_scenario'] = createTestScenario();

    echo "=" . str_repeat("=", 50) . "\n";
    echo "DIAGNOSTIC TEST SUMMARY\n";
    echo "=" . str_repeat("=", 50) . "\n";

    foreach ($results as $test => $result) {
        $status = $result ? "✅ PASS" : "❌ FAIL";
        echo "{$status} " . ucwords(str_replace('_', ' ', $test)) . "\n";
    }

    $passedTests = array_filter($results);
    $totalTests = count($results);
    $passedCount = count($passedTests);

    echo "\n";
    echo "OVERALL RESULT: {$passedCount}/{$totalTests} tests passed\n";

    if ($passedCount === $totalTests) {
        echo "🎉 ALL TESTS PASSED - System should be working\n";
        echo "   If notifications still don't appear, check browser console for JavaScript errors\n";
    } else {
        echo "⚠️  SOME TESTS FAILED - Fix the failing components first\n";
    }

    echo "\nCompleted at: " . now()->format('Y-m-d H:i:s') . "\n";

    return $results;
}

// Run the diagnostic
runAllTests();

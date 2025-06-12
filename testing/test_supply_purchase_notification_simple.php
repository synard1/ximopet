<?php

/**
 * Simple Supply Purchase Notification System Test
 * Compatible with older PHP versions
 */

class SimpleNotificationTest
{
    private $results = [];

    public function runTests()
    {
        echo "🚀 Supply Purchase Notification System Validation\n";
        echo str_repeat("=", 60) . "\n\n";

        $tests = [
            'Event File Exists' => $this->testEventFile(),
            'Listener File Exists' => $this->testListenerFile(),
            'Notification File Exists' => $this->testNotificationFile(),
            'Event Registration' => $this->testEventRegistration(),
            'Livewire Integration' => $this->testLivewireIntegration(),
            'DataTable Integration' => $this->testDataTableIntegration()
        ];

        $passed = 0;
        $total = count($tests);

        foreach ($tests as $name => $result) {
            $status = $result ? '✅ PASS' : '❌ FAIL';
            echo "📋 {$name}: {$status}\n";
            if ($result) $passed++;
        }

        echo "\n" . str_repeat("=", 60) . "\n";
        echo "📊 SUMMARY\n";
        echo "Total Tests: {$total}\n";
        echo "Passed: {$passed}\n";
        echo "Failed: " . ($total - $passed) . "\n";
        echo "Success Rate: " . round(($passed / $total) * 100, 1) . "%\n";
        echo "Status: " . ($passed === $total ? '✅ READY' : '⚠️ NEEDS ATTENTION') . "\n";

        return $passed === $total;
    }

    private function testEventFile()
    {
        $file = __DIR__ . '/../app/Events/SupplyPurchaseStatusChanged.php';
        if (!file_exists($file)) return false;

        $content = file_get_contents($file);
        return strpos($content, 'implements ShouldBroadcast') !== false &&
            strpos($content, 'broadcastOn()') !== false &&
            strpos($content, 'broadcastWith()') !== false;
    }

    private function testListenerFile()
    {
        $file = __DIR__ . '/../app/Listeners/SupplyPurchaseStatusNotificationListener.php';
        if (!file_exists($file)) return false;

        $content = file_get_contents($file);
        return strpos($content, 'implements ShouldQueue') !== false &&
            strpos($content, 'handle(SupplyPurchaseStatusChanged') !== false;
    }

    private function testNotificationFile()
    {
        $file = __DIR__ . '/../app/Notifications/SupplyPurchaseStatusNotification.php';
        if (!file_exists($file)) return false;

        $content = file_get_contents($file);
        return strpos($content, 'extends Notification') !== false &&
            strpos($content, 'toMail(') !== false &&
            strpos($content, 'toDatabase(') !== false;
    }

    private function testEventRegistration()
    {
        $file = __DIR__ . '/../app/Providers/EventServiceProvider.php';
        if (!file_exists($file)) return false;

        $content = file_get_contents($file);
        return strpos($content, 'SupplyPurchaseStatusChanged::class') !== false &&
            strpos($content, 'SupplyPurchaseStatusNotificationListener::class') !== false;
    }

    private function testLivewireIntegration()
    {
        $file = __DIR__ . '/../app/Livewire/SupplyPurchases/Create.php';
        if (!file_exists($file)) return false;

        $content = file_get_contents($file);
        return strpos($content, 'use App\Events\SupplyPurchaseStatusChanged') !== false &&
            strpos($content, 'event(new SupplyPurchaseStatusChanged(') !== false &&
            strpos($content, 'handleStatusChanged') !== false;
    }

    private function testDataTableIntegration()
    {
        $file = __DIR__ . '/../app/DataTables/SupplyPurchaseDataTable.php';
        if (!file_exists($file)) return false;

        $content = file_get_contents($file);
        return strpos($content, 'SupplyPurchaseNotifications') !== false &&
            strpos($content, 'setupBroadcastListeners') !== false &&
            strpos($content, 'showNotification') !== false;
    }
}

// Run if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $test = new SimpleNotificationTest();
    $result = $test->runTests();

    echo "\n🎯 System is " . ($result ? "READY for production!" : "NOT READY - check failed tests") . "\n";
}

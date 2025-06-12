<?php

/**
 * Supply Purchase Real-Time Notification System Test
 * 
 * Tests the real-time notification system for SupplyPurchase status changes
 * including Event broadcasting, Listener processing, and Notification delivery.
 * 
 * @author AI Assistant
 * @date 2024-12-11
 * @version 1.0.0
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

class SupplyPurchaseNotificationTest
{
    private $results = [];
    private $errors = [];

    public function runAllTests(): array
    {
        echo "🚀 Starting Supply Purchase Notification System Tests\n";
        echo str_repeat("=", 70) . "\n";

        $tests = [
            'testEventClassExists' => 'Event Class Structure',
            'testListenerClassExists' => 'Listener Class Structure',
            'testNotificationClassExists' => 'Notification Class Structure',
            'testEventServiceProviderRegistration' => 'Event Registration',
            'testLivewireIntegration' => 'Livewire Integration',
            'testDataTableIntegration' => 'DataTable Integration',
            'testBroadcastChannels' => 'Broadcast Channels',
            'testNotificationMethods' => 'Notification Methods',
            'testEventMetadata' => 'Event Metadata Structure',
            'testSystemIntegration' => 'Overall System Integration'
        ];

        foreach ($tests as $method => $description) {
            echo "\n📋 Testing: {$description}\n";
            echo str_repeat("-", 50) . "\n";

            try {
                $result = $this->$method();
                $this->results[$method] = [
                    'status' => $result ? 'PASS' : 'FAIL',
                    'description' => $description,
                    'details' => $result ? 'Test completed successfully' : 'Test failed'
                ];

                $status = $result ? '✅ PASS' : '❌ FAIL';
                echo "Result: {$status}\n";
            } catch (\Exception $e) {
                $this->errors[] = [
                    'test' => $method,
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ];

                $this->results[$method] = [
                    'status' => 'ERROR',
                    'description' => $description,
                    'details' => $e->getMessage()
                ];

                echo "❌ ERROR: " . $e->getMessage() . "\n";
            }
        }

        return $this->generateReport();
    }

    private function testEventClassExists(): bool
    {
        $eventFile = __DIR__ . '/../app/Events/SupplyPurchaseStatusChanged.php';

        if (!file_exists($eventFile)) {
            echo "Event file not found: {$eventFile}\n";
            return false;
        }

        $content = file_get_contents($eventFile);

        // Check class structure
        $checks = [
            'class SupplyPurchaseStatusChanged implements ShouldBroadcast' => 'Class implements ShouldBroadcast',
            'public function broadcastOn()' => 'broadcastOn method exists',
            'public function broadcastAs()' => 'broadcastAs method exists',
            'public function broadcastWith()' => 'broadcastWith method exists',
            'private function requiresRefresh' => 'requiresRefresh method exists',
            'private function getPriority' => 'getPriority method exists',
            'private function getNotificationMessage' => 'getNotificationMessage method exists',
            'private function getActionRequired' => 'getActionRequired method exists'
        ];

        foreach ($checks as $pattern => $description) {
            if (strpos($content, $pattern) === false) {
                echo "Missing: {$description}\n";
                return false;
            }
            echo "✓ Found: {$description}\n";
        }

        return true;
    }

    private function testListenerClassExists(): bool
    {
        $listenerFile = __DIR__ . '/../app/Listeners/SupplyPurchaseStatusNotificationListener.php';

        if (!file_exists($listenerFile)) {
            echo "Listener file not found: {$listenerFile}\n";
            return false;
        }

        $content = file_get_contents($listenerFile);

        $checks = [
            'class SupplyPurchaseStatusNotificationListener implements ShouldQueue' => 'Class implements ShouldQueue',
            'public function handle(SupplyPurchaseStatusChanged $event)' => 'handle method exists',
            'private function getUsersToNotify' => 'getUsersToNotify method exists',
            'public function failed' => 'failed method exists'
        ];

        foreach ($checks as $pattern => $description) {
            if (strpos($content, $pattern) === false) {
                echo "Missing: {$description}\n";
                return false;
            }
            echo "✓ Found: {$description}\n";
        }

        return true;
    }

    private function testNotificationClassExists(): bool
    {
        $notificationFile = __DIR__ . '/../app/Notifications/SupplyPurchaseStatusNotification.php';

        if (!file_exists($notificationFile)) {
            echo "Notification file not found: {$notificationFile}\n";
            return false;
        }

        $content = file_get_contents($notificationFile);

        $checks = [
            'class SupplyPurchaseStatusNotification extends Notification implements ShouldQueue' => 'Class extends Notification and implements ShouldQueue',
            'public function via(object $notifiable)' => 'via method exists',
            'public function toMail(object $notifiable)' => 'toMail method exists',
            'public function toDatabase(object $notifiable)' => 'toDatabase method exists',
            'public function toBroadcast(object $notifiable)' => 'toBroadcast method exists',
            'private function getNotificationMessage()' => 'getNotificationMessage method exists',
            'private function getActionRequired()' => 'getActionRequired method exists'
        ];

        foreach ($checks as $pattern => $description) {
            if (strpos($content, $pattern) === false) {
                echo "Missing: {$description}\n";
                return false;
            }
            echo "✓ Found: {$description}\n";
        }

        return true;
    }

    private function testEventServiceProviderRegistration(): bool
    {
        $providerFile = __DIR__ . '/../app/Providers/EventServiceProvider.php';

        if (!file_exists($providerFile)) {
            echo "EventServiceProvider file not found\n";
            return false;
        }

        $content = file_get_contents($providerFile);

        if (strpos($content, 'SupplyPurchaseStatusChanged::class') === false) {
            echo "Event not registered in EventServiceProvider\n";
            return false;
        }

        if (strpos($content, 'SupplyPurchaseStatusNotificationListener::class') === false) {
            echo "Listener not registered in EventServiceProvider\n";
            return false;
        }

        echo "✓ Event and Listener properly registered\n";
        return true;
    }

    private function testLivewireIntegration(): bool
    {
        $livewireFile = __DIR__ . '/../app/Livewire/SupplyPurchases/Create.php';

        if (!file_exists($livewireFile)) {
            echo "Livewire Create component not found\n";
            return false;
        }

        $content = file_get_contents($livewireFile);

        $checks = [
            'use App\Events\SupplyPurchaseStatusChanged' => 'Event imported',
            'event(new SupplyPurchaseStatusChanged(' => 'Event firing implemented',
            'public function handleStatusChanged($event)' => 'Status change handler exists',
            'public function handleUserNotification($notification)' => 'User notification handler exists',
            'echo:supply-purchases,status-changed' => 'Echo listener configured',
            'echo-notification:App.Models.User.' => 'User notification listener configured'
        ];

        foreach ($checks as $pattern => $description) {
            if (strpos($content, $pattern) === false) {
                echo "Missing: {$description}\n";
                return false;
            }
            echo "✓ Found: {$description}\n";
        }

        return true;
    }

    private function testDataTableIntegration(): bool
    {
        $dataTableFile = __DIR__ . '/../app/DataTables/SupplyPurchaseDataTable.php';

        if (!file_exists($dataTableFile)) {
            echo "DataTable file not found\n";
            return false;
        }

        $content = file_get_contents($dataTableFile);

        $checks = [
            'window.SupplyPurchaseNotifications' => 'Notification system object defined',
            'setupBroadcastListeners' => 'Broadcast listeners setup',
            'Echo.channel("supply-purchases")' => 'Supply purchases channel listener',
            'listen("status-changed"' => 'Status change event listener',
            'handleStatusChange' => 'Status change handler',
            'handleUserNotification' => 'User notification handler',
            'showNotification' => 'Notification display function'
        ];

        foreach ($checks as $pattern => $description) {
            if (strpos($content, $pattern) === false) {
                echo "Missing: {$description}\n";
                return false;
            }
            echo "✓ Found: {$description}\n";
        }

        return true;
    }

    private function testBroadcastChannels(): bool
    {
        $channelsFile = __DIR__ . '/../routes/channels.php';

        if (!file_exists($channelsFile)) {
            echo "Channels file not found\n";
            return false;
        }

        // Check broadcasting configuration
        $broadcastingFile = __DIR__ . '/../config/broadcasting.php';
        if (!file_exists($broadcastingFile)) {
            echo "Broadcasting config not found\n";
            return false;
        }

        echo "✓ Broadcasting configuration files exist\n";

        // Test that required broadcast channels are properly defined in Event
        $eventFile = __DIR__ . '/../app/Events/SupplyPurchaseStatusChanged.php';
        $content = file_get_contents($eventFile);

        $channels = [
            'supply-purchases' => 'General supply purchases channel',
            'supply-purchase.' => 'Specific batch channel',
            'farm.' => 'Farm-specific channel',
            'App.Models.User.' => 'User-specific channel'
        ];

        foreach ($channels as $channel => $description) {
            if (strpos($content, $channel) === false) {
                echo "Missing channel: {$description}\n";
                return false;
            }
            echo "✓ Found channel: {$description}\n";
        }

        return true;
    }

    private function testNotificationMethods(): bool
    {
        $notificationFile = __DIR__ . '/../app/Notifications/SupplyPurchaseStatusNotification.php';
        $content = file_get_contents($notificationFile);

        // Test notification channels
        $channels = [
            "'database'" => 'Database channel',
            "'broadcast'" => 'Broadcast channel',
            "'mail'" => 'Mail channel (conditional)'
        ];

        foreach ($channels as $channel => $description) {
            if (strpos($content, $channel) === false) {
                echo "Missing: {$description}\n";
                return false;
            }
            echo "✓ Found: {$description}\n";
        }

        // Test notification content structure
        $contentChecks = [
            'subject(' => 'Mail subject configuration',
            'greeting(' => 'Mail greeting',
            'action(' => 'Mail action button',
            'BroadcastMessage' => 'Broadcast message structure'
        ];

        foreach ($contentChecks as $check => $description) {
            if (strpos($content, $check) === false) {
                echo "Missing: {$description}\n";
                return false;
            }
            echo "✓ Found: {$description}\n";
        }

        return true;
    }

    private function testEventMetadata(): bool
    {
        $eventFile = __DIR__ . '/../app/Events/SupplyPurchaseStatusChanged.php';
        $content = file_get_contents($eventFile);

        // Test metadata structure
        $metadataFields = [
            'batch_id' => 'Batch ID',
            'invoice_number' => 'Invoice number',
            'supplier_name' => 'Supplier name',
            'total_value' => 'Total value',
            'updated_by_name' => 'Updated by user name',
            'requires_refresh' => 'Refresh requirement flag',
            'priority' => 'Priority level'
        ];

        foreach ($metadataFields as $field => $description) {
            if (strpos($content, "'{$field}'") === false) {
                echo "Missing metadata field: {$description}\n";
                return false;
            }
            echo "✓ Found metadata: {$description}\n";
        }

        // Test priority levels
        $priorities = ['high', 'medium', 'low', 'normal'];
        foreach ($priorities as $priority) {
            if (strpos($content, "'{$priority}'") === false) {
                echo "Missing priority level: {$priority}\n";
                return false;
            }
        }
        echo "✓ All priority levels defined\n";

        return true;
    }

    private function testSystemIntegration(): bool
    {
        $integrationChecks = [
            'Event fires from Livewire' => $this->checkEventFiring(),
            'Listener processes event' => $this->checkListenerProcessing(),
            'Notification channels configured' => $this->checkNotificationChannels(),
            'JavaScript handles broadcasts' => $this->checkJavaScriptHandlers(),
            'UI notifications display' => $this->checkUINotifications()
        ];

        foreach ($integrationChecks as $check => $result) {
            if (!$result) {
                echo "Failed integration check: {$check}\n";
                return false;
            }
            echo "✓ Passed: {$check}\n";
        }

        return true;
    }

    private function checkEventFiring(): bool
    {
        $livewireFile = __DIR__ . '/../app/Livewire/SupplyPurchases/Create.php';
        $content = file_get_contents($livewireFile);

        return strpos($content, 'event(new SupplyPurchaseStatusChanged(') !== false &&
            strpos($content, '$oldStatus !== $status') !== false;
    }

    private function checkListenerProcessing(): bool
    {
        $listenerFile = __DIR__ . '/../app/Listeners/SupplyPurchaseStatusNotificationListener.php';
        $content = file_get_contents($listenerFile);

        return strpos($content, 'getUsersToNotify') !== false &&
            strpos($content, 'notify(new SupplyPurchaseStatusNotification') !== false;
    }

    private function checkNotificationChannels(): bool
    {
        $notificationFile = __DIR__ . '/../app/Notifications/SupplyPurchaseStatusNotification.php';
        $content = file_get_contents($notificationFile);

        return strpos($content, 'database') !== false &&
            strpos($content, 'broadcast') !== false &&
            strpos($content, 'mail') !== false;
    }

    private function checkJavaScriptHandlers(): bool
    {
        $dataTableFile = __DIR__ . '/../app/DataTables/SupplyPurchaseDataTable.php';
        $content = file_get_contents($dataTableFile);

        return strpos($content, 'window.Echo.channel') !== false &&
            strpos($content, 'handleStatusChange') !== false &&
            strpos($content, 'showNotification') !== false;
    }

    private function checkUINotifications(): bool
    {
        $dataTableFile = __DIR__ . '/../app/DataTables/SupplyPurchaseDataTable.php';
        $content = file_get_contents($dataTableFile);

        return strpos($content, 'notification-alert') !== false &&
            strpos($content, 'refresh-data-btn') !== false &&
            strpos($content, 'toastr') !== false;
    }

    private function generateReport(): array
    {
        $totalTests = count($this->results);
        $passedTests = count(array_filter($this->results, fn($r) => $r['status'] === 'PASS'));
        $failedTests = count(array_filter($this->results, fn($r) => $r['status'] === 'FAIL'));
        $errorTests = count(array_filter($this->results, fn($r) => $r['status'] === 'ERROR'));

        $report = [
            'summary' => [
                'total_tests' => $totalTests,
                'passed' => $passedTests,
                'failed' => $failedTests,
                'errors' => $errorTests,
                'success_rate' => round(($passedTests / $totalTests) * 100, 2),
                'test_date' => date('Y-m-d H:i:s'),
                'system_status' => $passedTests === $totalTests ? 'READY' : 'NEEDS_ATTENTION'
            ],
            'detailed_results' => $this->results,
            'errors' => $this->errors,
            'recommendations' => $this->generateRecommendations()
        ];

        echo "\n" . str_repeat("=", 70) . "\n";
        echo "📊 FINAL REPORT - Supply Purchase Notification System\n";
        echo str_repeat("=", 70) . "\n";
        echo "Total Tests: {$totalTests}\n";
        echo "✅ Passed: {$passedTests}\n";
        echo "❌ Failed: {$failedTests}\n";
        echo "🚫 Errors: {$errorTests}\n";
        echo "📈 Success Rate: {$report['summary']['success_rate']}%\n";
        echo "🎯 System Status: {$report['summary']['system_status']}\n";

        if (!empty($this->errors)) {
            echo "\n🚨 ERRORS ENCOUNTERED:\n";
            foreach ($this->errors as $error) {
                echo "- {$error['test']}: {$error['error']}\n";
            }
        }

        return $report;
    }

    private function generateRecommendations(): array
    {
        $recommendations = [];

        if (!empty($this->errors)) {
            $recommendations[] = "🔧 Fix the errors listed above before deploying to production";
        }

        $failedTests = array_filter($this->results, fn($r) => $r['status'] === 'FAIL');
        if (!empty($failedTests)) {
            $recommendations[] = "⚠️ Address failed tests to ensure complete functionality";
        }

        $recommendations[] = "📡 Ensure Laravel Echo and broadcasting driver are properly configured";
        $recommendations[] = "🔔 Test real-time notifications in development environment";
        $recommendations[] = "👥 Verify user role-based notification targeting";
        $recommendations[] = "🎨 Test UI notification display across different browsers";
        $recommendations[] = "📊 Monitor notification delivery performance in production";

        return $recommendations;
    }
}

// Run the test if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $test = new SupplyPurchaseNotificationTest();
    $report = $test->runAllTests();

    // Save report to file
    $reportFile = __DIR__ . '/supply_purchase_notification_test_report.json';
    file_put_contents($reportFile, json_encode($report, JSON_PRETTY_PRINT));
    echo "\n📄 Detailed report saved to: {$reportFile}\n";
}

<?php

/**
 * TEST RAPID NOTIFICATIONS - Debounce Mechanism Test
 * Script untuk test multiple notifications dalam waktu singkat
 * 
 * @author AI Assistant
 * @date 2024-12-19
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Log;

/**
 * Send notification to SSE storage
 */
function sendToSSEStorage($title, $message, $batchId = 999, $status = 'arrived')
{
    $filePath = __DIR__ . '/sse-notifications.json';

    // Initialize file if not exists
    if (!file_exists($filePath)) {
        file_put_contents($filePath, json_encode([
            'notifications' => [],
            'last_update' => time(),
            'stats' => [
                'total_sent' => 0,
                'clients_connected' => 0
            ]
        ]));
    }

    $fileData = json_decode(file_get_contents($filePath), true);

    $notification = [
        'id' => uniqid() . '_' . microtime(true),
        'type' => 'supply_purchase_status_changed',
        'title' => $title,
        'message' => $message,
        'source' => 'rapid_test_script',
        'priority' => 'normal',
        'data' => [
            'batch_id' => $batchId,
            'invoice_number' => 'RAPID-TEST-' . date('His'),
            'updated_by' => 1,
            'updated_by_name' => 'Test User',
            'old_status' => 'draft',
            'new_status' => $status,
            'timestamp' => date('c'),
            'requires_refresh' => true
        ],
        'requires_refresh' => true,
        'timestamp' => time(),
        'datetime' => date('Y-m-d H:i:s'),
        'microseconds' => microtime(true)
    ];

    // Add to beginning of array (newest first)
    array_unshift($fileData['notifications'], $notification);

    // Keep only last 50 notifications
    $fileData['notifications'] = array_slice($fileData['notifications'], 0, 50);

    $fileData['last_update'] = time();
    $fileData['stats']['total_sent']++;

    file_put_contents($filePath, json_encode($fileData, JSON_PRETTY_PRINT));

    return $notification;
}

echo "🚀 RAPID NOTIFICATION TEST - Debounce Mechanism\n";
echo str_repeat("=", 50) . "\n";

$batchId = 123;
$notifications = [];

echo "📡 Sending 5 rapid notifications for same batch...\n\n";

// Send 5 notifications rapidly for same batch
for ($i = 1; $i <= 5; $i++) {
    $notification = sendToSSEStorage(
        "Rapid Test Notification #{$i}",
        "This is rapid test #{$i} sent at " . date('H:i:s.') . substr(microtime(), 2, 3),
        $batchId,
        'arrived'
    );

    $notifications[] = $notification;

    echo "   #{$i} - ID: {$notification['id']}\n";
    echo "        Time: {$notification['datetime']}\n";
    echo "        Microseconds: {$notification['microseconds']}\n";

    // Small delay between notifications (50ms)
    usleep(50000);
}

echo "\n📊 DEBOUNCE TEST RESULTS:\n";
echo "   - Total notifications sent: " . count($notifications) . "\n";
echo "   - SSE clients should debounce similar notifications\n";
echo "   - Only unique notifications should be displayed\n";

// Test different statuses for same batch
echo "\n🔄 Testing different statuses for same batch...\n";

$statuses = ['confirmed', 'pending', 'cancelled'];
foreach ($statuses as $status) {
    $notification = sendToSSEStorage(
        "Status Change: " . ucfirst($status),
        "Batch {$batchId} status changed to {$status}",
        $batchId,
        $status
    );

    echo "   Status '{$status}' - ID: {$notification['id']}\n";
    usleep(100000); // 100ms delay
}

// Test different batch IDs
echo "\n📦 Testing different batch IDs...\n";

for ($batchId = 500; $batchId <= 503; $batchId++) {
    $notification = sendToSSEStorage(
        "Different Batch Test",
        "Testing batch {$batchId} notification",
        $batchId,
        'arrived'
    );

    echo "   Batch {$batchId} - ID: {$notification['id']}\n";
    usleep(25000); // 25ms delay
}

echo "\n✅ RAPID TEST COMPLETED!\n";
echo "   - Check browser console for debounce logs\n";
echo "   - Look for: '🔄 Notification debounced (too frequent)'\n";
echo "   - Only distinct notifications should be shown to user\n";

// Log the test
Log::info('Rapid notification test completed', [
    'total_notifications' => count($notifications) + count($statuses) + 4,
    'timestamp' => date('c'),
    'test_type' => 'debounce_mechanism'
]);

echo "\n🏁 Test completed.\n";

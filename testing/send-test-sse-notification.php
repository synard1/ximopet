<?php

/**
 * SEND TEST SSE NOTIFICATION
 * Script untuk mengirim test notification ke SSE system
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
function sendToSSEStorage($title, $message, $type = 'info', $data = [])
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
        'id' => uniqid(),
        'type' => 'supply_purchase_status_changed',
        'title' => $title,
        'message' => $message,
        'source' => 'test_script',
        'priority' => $type === 'error' ? 'high' : 'normal',
        'data' => array_merge([
            'batch_id' => 999,
            'invoice_number' => 'TEST-' . date('Ymd-His'),
            'updated_by' => 1,
            'updated_by_name' => 'Test User',
            'old_status' => 'draft',
            'new_status' => 'arrived',
            'timestamp' => date('c'),
            'requires_refresh' => true
        ], $data),
        'requires_refresh' => true,
        'timestamp' => time(),
        'datetime' => date('Y-m-d H:i:s')
    ];

    // Add to beginning of array (newest first)
    array_unshift($fileData['notifications'], $notification);

    // Keep only last 100 notifications
    $fileData['notifications'] = array_slice($fileData['notifications'], 0, 100);

    $fileData['last_update'] = time();
    $fileData['stats']['total_sent']++;

    file_put_contents($filePath, json_encode($fileData, JSON_PRETTY_PRINT));

    return $notification;
}

echo "🧪 SENDING TEST SSE NOTIFICATION\n";
echo str_repeat("=", 40) . "\n";

$notification = sendToSSEStorage(
    'Test Real-time Notification',
    'This is a test notification sent at ' . date('H:i:s') . ' to verify SSE system is working!',
    'info',
    [
        'test' => true,
        'manual_trigger' => true,
        'sent_at' => date('c')
    ]
);

echo "✅ Test notification sent successfully!\n";
echo "   ID: {$notification['id']}\n";
echo "   Title: {$notification['title']}\n";
echo "   Message: {$notification['message']}\n";
echo "   Timestamp: {$notification['datetime']}\n";
echo "\n";
echo "📡 SSE clients should receive this notification within 2 seconds.\n";
echo "   Open Supply Purchase page in browser to see it!\n";
echo "\n";

// Log the test
Log::info('Test SSE notification sent', [
    'notification_id' => $notification['id'],
    'title' => $notification['title'],
    'timestamp' => $notification['datetime']
]);

echo "🏁 Test completed.\n";
 
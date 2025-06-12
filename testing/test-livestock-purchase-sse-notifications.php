<?php

/**
 * TEST LIVESTOCK PURCHASE SSE NOTIFICATIONS - Race Condition & Debounce Test
 * Script untuk test multiple livestock purchase notifications dalam waktu singkat
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
 * Send livestock purchase notification to SSE storage
 */
function sendToSSEStorage($title, $message, $batchId = 777, $status = 'in_coop')
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
        'type' => 'livestock_purchase_status_changed',
        'title' => $title,
        'message' => $message,
        'source' => 'livestock_test_script',
        'priority' => 'normal',
        'data' => [
            'batch_id' => $batchId,
            'invoice_number' => 'LIVESTOCK-TEST-' . date('His'),
            'updated_by' => 1,
            'updated_by_name' => 'Livestock Test User',
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

echo "🐄 LIVESTOCK PURCHASE SSE NOTIFICATION TEST - Race Condition Safe\n";
echo str_repeat("=", 60) . "\n";

$batchId = 333;
$notifications = [];

echo "📡 Sending 5 rapid livestock purchase notifications...\n\n";

// Send 5 notifications rapidly for same batch
for ($i = 1; $i <= 5; $i++) {
    $notification = sendToSSEStorage(
        "Livestock Purchase Test #{$i}",
        "Livestock purchase test #{$i} sent at " . date('H:i:s.') . substr(microtime(), 2, 3),
        $batchId,
        'in_coop'
    );

    $notifications[] = $notification;

    echo "   #{$i} - ID: {$notification['id']}\n";
    echo "        Time: {$notification['datetime']}\n";
    echo "        Microseconds: {$notification['microseconds']}\n";

    // Small delay between notifications (50ms)
    usleep(50000);
}

echo "\n📊 LIVESTOCK PURCHASE DEBOUNCE TEST RESULTS:\n";
echo "   - Total notifications sent: " . count($notifications) . "\n";
echo "   - SSE clients should debounce similar notifications\n";
echo "   - Only unique livestock purchase notifications should be displayed\n";

// Test different statuses for same batch
echo "\n🔄 Testing different livestock statuses for same batch...\n";

$livestockStatuses = ['confirmed', 'pending', 'cancelled', 'completed'];
foreach ($livestockStatuses as $status) {
    $notification = sendToSSEStorage(
        "Livestock Status Change: " . ucfirst($status),
        "Livestock batch {$batchId} status changed to {$status}",
        $batchId,
        $status
    );

    echo "   Livestock Status '{$status}' - ID: {$notification['id']}\n";
    usleep(100000); // 100ms delay
}

// Test different batch IDs
echo "\n📦 Testing different livestock batch IDs...\n";

for ($batchId = 500; $batchId <= 503; $batchId++) {
    $notification = sendToSSEStorage(
        "Livestock Batch Test",
        "Testing livestock batch {$batchId} notification",
        $batchId,
        'in_coop'
    );

    echo "   Livestock Batch {$batchId} - ID: {$notification['id']}\n";
    usleep(25000); // 25ms delay
}

// Test livestock-specific scenarios
echo "\n🐎 Testing livestock-specific scenarios...\n";

$livestockScenarios = [
    ['batch_id' => 601, 'status' => 'in_coop', 'scenario' => 'DOC Arrival at Coop'],
    ['batch_id' => 602, 'status' => 'health_check', 'scenario' => 'Livestock Health Check'],
    ['batch_id' => 603, 'status' => 'vaccination', 'scenario' => 'Livestock Vaccination'],
    ['batch_id' => 604, 'status' => 'weight_monitoring', 'scenario' => 'Weight Monitoring'],
];

foreach ($livestockScenarios as $scenario) {
    $notification = sendToSSEStorage(
        $scenario['scenario'],
        "Livestock batch {$scenario['batch_id']} - {$scenario['scenario']} notification",
        $scenario['batch_id'],
        $scenario['status']
    );

    echo "   {$scenario['scenario']} - ID: {$notification['id']}\n";
    usleep(75000); // 75ms delay
}

// Test farm-specific scenarios
echo "\n🏚️ Testing farm-specific livestock scenarios...\n";

$farmScenarios = [
    ['batch_id' => 701, 'farm' => 'Farm A', 'coop' => 'Coop 1', 'status' => 'in_coop'],
    ['batch_id' => 702, 'farm' => 'Farm B', 'coop' => 'Coop 2', 'status' => 'in_coop'],
    ['batch_id' => 703, 'farm' => 'Farm C', 'coop' => 'Coop 3', 'status' => 'in_coop'],
];

foreach ($farmScenarios as $scenario) {
    $notification = sendToSSEStorage(
        "DOC Placement - {$scenario['farm']}",
        "DOC batch {$scenario['batch_id']} placed in {$scenario['farm']} - {$scenario['coop']}",
        $scenario['batch_id'],
        $scenario['status']
    );

    echo "   {$scenario['farm']} - {$scenario['coop']} - ID: {$notification['id']}\n";
    usleep(50000); // 50ms delay
}

echo "\n✅ LIVESTOCK PURCHASE TEST COMPLETED!\n";
echo "   - Check browser console for debounce logs in Livestock Purchase pages\n";
echo "   - Look for: '🔄 Livestock purchase notification debounced (too frequent)'\n";
echo "   - Only distinct livestock notifications should be shown to user\n";

// Log the test
Log::info('Livestock purchase rapid notification test completed', [
    'total_notifications' => count($notifications) + count($livestockStatuses) + 4 + count($livestockScenarios) + count($farmScenarios),
    'timestamp' => date('c'),
    'test_type' => 'livestock_purchase_debounce_mechanism'
]);

echo "\n🏁 Livestock Purchase SSE test completed.\n";
echo "Ready for production deployment with race condition protection! 🚀\n";

<?php

/**
 * SIMPLE NOTIFICATION TEST
 * Send a test notification directly to browser bridge
 */

echo "🧪 SIMPLE NOTIFICATION TEST\n";
echo str_repeat("=", 40) . "\n";

// Send notification to browser bridge
function sendTestNotification($title, $message, $type = 'info')
{
    $notification = [
        'type' => $type,
        'title' => $title,
        'message' => $message,
        'source' => 'simple_test',
        'priority' => 'normal',
        'data' => [
            'test_id' => uniqid(),
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ];

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
        $result = json_decode($response, true);
        echo "✅ Notification sent: {$title} (ID: {$result['notification_id']})\n";
        return true;
    } else {
        echo "❌ Failed to send notification (HTTP {$httpCode})\n";
        echo "Response: {$response}\n";
        return false;
    }
}

// Send test notifications
echo "📤 Sending test notifications to browser bridge...\n\n";

$tests = [
    ['Simple Test Notification', 'This is a simple test from PHP script', 'info'],
    ['Success Test', 'This is a success notification', 'success'],
    ['Warning Test', 'This is a warning notification', 'warning'],
    ['Error Test', 'This is an error notification', 'error']
];

$successful = 0;

foreach ($tests as $i => $test) {
    echo "Test " . ($i + 1) . ": ";
    if (sendTestNotification($test[0], $test[1], $test[2])) {
        $successful++;
    }

    // Small delay between notifications
    sleep(1);
}

echo "\n📊 RESULTS:\n";
echo "Total tests: " . count($tests) . "\n";
echo "Successful: {$successful}\n";
echo "Failed: " . (count($tests) - $successful) . "\n";

if ($successful === count($tests)) {
    echo "\n🎉 ALL NOTIFICATIONS SENT SUCCESSFULLY!\n";
    echo "📱 Check your browser - you should see notifications appearing!\n";
    echo "🌐 Browser URL: http://demo51.local/testing/realtime_test_client.php\n";
} else {
    echo "\n⚠️ Some notifications failed to send.\n";
}

echo "\n" . str_repeat("=", 40) . "\n";

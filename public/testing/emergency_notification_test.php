<?php

/**
 * EMERGENCY NOTIFICATION TEST - Direct Bridge Test
 * Mengirim test notification langsung ke bridge untuk debugging
 * 
 * @date 2024-12-11
 * @author AI Assistant  
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$action = $_GET['action'] ?? 'test';

switch ($action) {
    case 'test':
        sendTestNotification();
        break;
    case 'status':
        checkBridgeStatus();
        break;
    case 'clear':
        clearNotifications();
        break;
    default:
        echo json_encode(['error' => 'Invalid action']);
}

function sendTestNotification()
{
    $bridgeUrl = 'http://demo51.local/testing/notification_bridge.php';

    $notification = [
        'type' => 'info',
        'title' => 'Emergency Test Notification',
        'message' => 'Test dari emergency script - ' . date('H:i:s'),
        'timestamp' => time(),
        'data' => [
            'test' => true,
            'emergency' => true,
            'updated_by' => 999, // Fake user ID to avoid self-exclusion
            'requires_refresh' => false
        ]
    ];

    $postData = [
        'action' => 'send',
        'notification' => json_encode($notification)
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $bridgeUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        echo json_encode([
            'success' => false,
            'error' => 'CURL Error: ' . curl_error($ch)
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'Test notification sent to bridge',
            'bridge_response' => $response,
            'http_code' => $httpCode,
            'notification_sent' => $notification
        ]);
    }

    curl_close($ch);
}

function checkBridgeStatus()
{
    $bridgeUrl = 'http://demo51.local/testing/notification_bridge.php?action=status';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $bridgeUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        echo json_encode([
            'success' => false,
            'error' => 'CURL Error: ' . curl_error($ch)
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'bridge_status' => json_decode($response, true),
            'http_code' => $httpCode
        ]);
    }

    curl_close($ch);
}

function clearNotifications()
{
    $bridgeUrl = 'http://demo51.local/testing/notification_bridge.php?action=clear';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $bridgeUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    echo json_encode([
        'success' => true,
        'message' => 'Clear command sent',
        'bridge_response' => json_decode($response, true),
        'http_code' => $httpCode
    ]);

    curl_close($ch);
}

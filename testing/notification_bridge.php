<?php

/**
 * NOTIFICATION BRIDGE - FILE-BASED REAL-TIME COMMUNICATION
 * 
 * This script serves two purposes:
 * 1. Accept notifications from PHP test scripts
 * 2. Serve notifications to browser clients via AJAX polling
 */

$bridgeFile = __DIR__ . '/notification_bridge.json';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

/**
 * Initialize bridge file if not exists
 */
function initializeBridge($bridgeFile)
{
    if (!file_exists($bridgeFile)) {
        file_put_contents($bridgeFile, json_encode([
            'notifications' => [],
            'last_update' => time(),
            'stats' => [
                'total_sent' => 0,
                'total_received' => 0
            ]
        ]));
    }
}

/**
 * Add notification to bridge
 */
function addNotification($bridgeFile, $notification)
{
    initializeBridge($bridgeFile);

    $data = json_decode(file_get_contents($bridgeFile), true);

    $notification['id'] = uniqid();
    $notification['timestamp'] = time();
    $notification['datetime'] = date('Y-m-d H:i:s');

    // Add to beginning of array (newest first)
    array_unshift($data['notifications'], $notification);

    // Keep only last 50 notifications
    $data['notifications'] = array_slice($data['notifications'], 0, 50);

    $data['last_update'] = time();
    $data['stats']['total_sent']++;

    file_put_contents($bridgeFile, json_encode($data));

    return $notification['id'];
}

/**
 * Get notifications for browser client
 */
function getNotifications($bridgeFile, $since = 0)
{
    initializeBridge($bridgeFile);

    $data = json_decode(file_get_contents($bridgeFile), true);

    // Filter notifications newer than $since timestamp
    $newNotifications = array_filter($data['notifications'], function ($notification) use ($since) {
        return $notification['timestamp'] > $since;
    });

    // Update stats
    if (!empty($newNotifications)) {
        $data['stats']['total_received'] += count($newNotifications);
        file_put_contents($bridgeFile, json_encode($data));
    }

    return [
        'notifications' => array_values($newNotifications),
        'last_update' => $data['last_update'],
        'stats' => $data['stats'],
        'server_time' => time()
    ];
}

/**
 * Clear all notifications
 */
function clearNotifications($bridgeFile)
{
    $data = [
        'notifications' => [],
        'last_update' => time(),
        'stats' => [
            'total_sent' => 0,
            'total_received' => 0
        ]
    ];

    file_put_contents($bridgeFile, json_encode($data));
    return true;
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Handle notification from PHP script
        $input = file_get_contents('php://input');
        $notification = json_decode($input, true);

        if (!$notification) {
            throw new Exception('Invalid JSON data received');
        }

        // Add notification to bridge
        $notificationId = addNotification($bridgeFile, $notification);

        echo json_encode([
            'success' => true,
            'message' => 'Notification added to bridge',
            'notification_id' => $notificationId,
            'timestamp' => time()
        ]);
    } else {
        // Handle request from browser client
        $since = isset($_GET['since']) ? (int)$_GET['since'] : 0;
        $action = $_GET['action'] ?? 'get';

        switch ($action) {
            case 'get':
                $result = getNotifications($bridgeFile, $since);
                echo json_encode($result);
                break;

            case 'clear':
                clearNotifications($bridgeFile);
                echo json_encode([
                    'success' => true,
                    'message' => 'All notifications cleared'
                ]);
                break;

            case 'status':
                initializeBridge($bridgeFile);
                $data = json_decode(file_get_contents($bridgeFile), true);
                echo json_encode([
                    'success' => true,
                    'bridge_active' => true,
                    'total_notifications' => count($data['notifications']),
                    'last_update' => $data['last_update'],
                    'stats' => $data['stats'],
                    'server_time' => time()
                ]);
                break;

            default:
                throw new Exception('Unknown action: ' . $action);
        }
    }
} catch (\Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => time()
    ]);
}

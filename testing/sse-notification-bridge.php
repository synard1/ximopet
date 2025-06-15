<?php

/**
 * SERVER-SENT EVENTS NOTIFICATION BRIDGE - FIXED VERSION
 * Real-time notification bridge using SSE to replace polling overhead
 * 
 * @author AI Assistant
 * @date 2024-12-19  
 * @version 2.0.1 - Fixed MIME type and connection issues
 */

// Handle CORS and HTTP method check first
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Cache-Control, Last-Event-ID');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow GET requests for SSE
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Method not allowed. Use GET for SSE connection.']);
    exit();
}

// Check if client accepts event-stream
$acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';
if (strpos($acceptHeader, 'text/event-stream') === false && strpos($acceptHeader, '*/*') === false) {
    http_response_code(406);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Client does not accept text/event-stream']);
    exit();
}

// Try to bootstrap Laravel safely
try {
    require_once __DIR__ . '/../vendor/autoload.php';
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Laravel bootstrap failed: ' . $e->getMessage()]);
    exit();
}

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Event;
use App\Events\SupplyPurchaseStatusChanged;
use App\Events\FeedPurchaseStatusChanged;
use App\Events\LivestockPurchaseStatusChanged;

// Check debug mode
$debugMode = config('app.debug', false);

// Custom debug log function that respects APP_DEBUG
function debugLog($message, $context = [])
{
    global $debugMode;
    if ($debugMode) {
        Log::info($message, $context);
    }
}

// Set correct headers for Server-Sent Events
header('Content-Type: text/event-stream; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // Disable nginx buffering

// Prevent timeout and buffer issues
set_time_limit(0);
ignore_user_abort(false);
ini_set('output_buffering', 'off');
ini_set('zlib.output_compression', false);
ini_set('implicit_flush', true);

// Clear any existing output buffers
while (ob_get_level()) {
    ob_end_clean();
}

/**
 * Send Server-Sent Event to browser
 */
function sendSSE($event, $data, $id = null)
{
    if (connection_aborted()) {
        return false;
    }

    if ($id !== null) {
        echo "id: {$id}\n";
    }
    echo "event: {$event}\n";
    echo "data: " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";

    // Force immediate output
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    } else {
        flush();
    }

    return true;
}

/**
 * Get notification file path
 */
function getNotificationFilePath()
{
    return __DIR__ . '/sse-notifications.json';
}

/**
 * Initialize notification storage
 */
function initializeNotificationStorage()
{
    global $debugMode;
    $filePath = getNotificationFilePath();

    if (!file_exists($filePath)) {
        $initialData = [
            'notifications' => [],
            'last_update' => time(),
            'stats' => [
                'total_sent' => 0,
                'clients_connected' => 0
            ]
        ];

        if (file_put_contents($filePath, json_encode($initialData, JSON_PRETTY_PRINT)) === false) {
            if ($debugMode) {
            error_log("Failed to create SSE notification file: {$filePath}");
            }
            return false;
        }
    }

    return $filePath;
}

/**
 * Get new notifications since timestamp
 */
function getNewNotifications($since = 0)
{
    $filePath = getNotificationFilePath();

    if (!file_exists($filePath)) {
        return [];
    }

    $content = file_get_contents($filePath);
    if ($content === false) {
        return [];
    }

    $data = json_decode($content, true);
    if (!$data || !isset($data['notifications'])) {
        return [];
    }

    // Filter notifications newer than $since timestamp
    return array_filter($data['notifications'], function ($notification) use ($since) {
        return ($notification['timestamp'] ?? 0) > $since;
    });
}

/**
 * Add notification to storage
 */
function addNotificationToStorage($notification)
{
    $filePath = initializeNotificationStorage();
    $data = json_decode(file_get_contents($filePath), true);

    $notification['id'] = uniqid();
    $notification['timestamp'] = time();
    $notification['datetime'] = date('Y-m-d H:i:s');

    // Add to beginning of array (newest first)
    array_unshift($data['notifications'], $notification);

    // Keep only last 100 notifications
    $data['notifications'] = array_slice($data['notifications'], 0, 100);

    $data['last_update'] = time();
    $data['stats']['total_sent']++;

    file_put_contents($filePath, json_encode($data));

    return $notification;
}

/**
 * Send initial connection message
 */
if (!sendSSE('connected', [
    'message' => 'SSE notification bridge connected',
    'timestamp' => date('c'),
    'status' => 'ready',
    'server_time' => time(),
    'bridge_version' => '2.0.1'
])) {
    exit();
}

// Log connection start
try {
    debugLog('SSE notification bridge started', [
        'timestamp' => date('c'),
        'client_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]);
} catch (Exception $e) {
    if ($debugMode) {
    error_log('Failed to log SSE start: ' . $e->getMessage());
    }
}

// Initialize notification storage
$storageReady = initializeNotificationStorage();
if (!$storageReady) {
    sendSSE('error', [
        'message' => 'Failed to initialize notification storage',
        'timestamp' => date('c')
    ]);
    exit();
}

// Track connection in stats
try {
    $filePath = getNotificationFilePath();
    $data = json_decode(file_get_contents($filePath), true);
    if ($data) {
        $data['stats']['clients_connected']++;
        file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT));
    }
} catch (Exception $e) {
    if ($debugMode) {
    error_log('Failed to update connection stats: ' . $e->getMessage());
    }
}

// Connection management variables
$startTime = time();
$maxTime = 300; // 5 minutes max connection time
$lastHeartbeat = time();
$heartbeatInterval = 30; // 30 seconds
$lastNotificationCheck = time();
$notificationCheckInterval = 2; // Check for new notifications every 2 seconds

// Send initial status
sendSSE('status', [
    'message' => 'SSE bridge ready for notifications',
    'timestamp' => date('c'),
    'check_interval' => $notificationCheckInterval
]);

// Main event loop
while (true) {
    // Check if client disconnected
    if (connection_aborted()) {
        debugLog('SSE Bridge: Client disconnected');
        break;
    }

    // Check maximum connection time
    if ((time() - $startTime) > $maxTime) {
        sendSSE('timeout', [
            'message' => 'Connection timeout - please reconnect',
            'timestamp' => date('c'),
            'uptime' => time() - $startTime
        ]);
        break;
    }

    // Send heartbeat every 30 seconds
    if ((time() - $lastHeartbeat) >= $heartbeatInterval) {
        if (!sendSSE('heartbeat', [
            'message' => 'Connection alive',
            'uptime' => time() - $startTime,
            'timestamp' => date('c'),
            'server_time' => time()
        ])) {
            break;
        }
        $lastHeartbeat = time();
    }

    // Check for new notifications periodically
    if ((time() - $lastNotificationCheck) >= $notificationCheckInterval) {
        try {
            $newNotifications = getNewNotifications($lastNotificationCheck);

            foreach ($newNotifications as $notification) {
                // Determine event type based on notification type
                $eventType = 'notification';
                if (isset($notification['type'])) {
                    switch ($notification['type']) {
                        case 'supply_purchase_status_changed':
                            $eventType = 'supply_purchase_notification';
                            break;
                        case 'feed_purchase_status_changed':
                            $eventType = 'feed_purchase_notification';
                            break;
                        case 'livestock_purchase_status_changed':
                            $eventType = 'livestock_purchase_notification';
                            break;
                    }
                }

                if (!sendSSE($eventType, $notification, $notification['id'] ?? null)) {
                    break 2; // Break both loops
                }

                // Log notification sent
                debugLog('SSE Bridge: Notification sent', [
                    'event_type' => $eventType,
                    'notification_id' => $notification['id'] ?? 'unknown',
                    'type' => $notification['type'] ?? 'unknown'
                ]);
            }

            $lastNotificationCheck = time();
        } catch (Exception $e) {
            debugLog('SSE Bridge: Error checking notifications', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            sendSSE('error', [
                'message' => 'Error checking notifications',
                'timestamp' => date('c')
            ]);
        }
    }

    // Small sleep to prevent excessive CPU usage
    usleep(500000); // 0.5 seconds
}

// Cleanup on disconnect
try {
    $filePath = getNotificationFilePath();
    if (file_exists($filePath)) {
        $data = json_decode(file_get_contents($filePath), true);
        if ($data) {
            $data['stats']['clients_connected'] = max(0, $data['stats']['clients_connected'] - 1);
            file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT));
        }
    }

    debugLog('SSE Bridge: Connection closed', [
        'uptime' => time() - $startTime,
        'timestamp' => date('c')
    ]);
} catch (Exception $e) {
    if ($debugMode) {
    error_log('SSE cleanup error: ' . $e->getMessage());
    }
}

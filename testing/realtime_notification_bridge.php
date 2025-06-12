<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Event;
use App\Events\SupplyPurchaseStatusChanged;

// Set headers for Server-Sent Events
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Last-Event-ID');

// Prevent timeout
set_time_limit(0);
ignore_user_abort(false);

/**
 * Send Server-Sent Event to browser
 */
function sendSSE($event, $data, $id = null)
{
    if ($id !== null) {
        echo "id: {$id}\n";
    }
    echo "event: {$event}\n";
    echo "data: " . json_encode($data) . "\n\n";

    // Flush output immediately
    if (ob_get_level()) {
        ob_end_flush();
    }
    flush();
}

/**
 * Send initial connection message
 */
sendSSE('connected', [
    'message' => 'Real-time notification bridge connected',
    'timestamp' => now()->toISOString(),
    'status' => 'ready'
]);

Log::info('Real-time notification bridge started', [
    'timestamp' => now()->toISOString(),
    'client_ip' => request()->ip()
]);

// Register event listeners for real-time notifications
Event::listen(SupplyPurchaseStatusChanged::class, function ($event) {
    Log::info('SSE Bridge: SupplyPurchaseStatusChanged event received', [
        'batch_id' => $event->batch->id,
        'old_status' => $event->oldStatus,
        'new_status' => $event->newStatus
    ]);

    // Send notification to browser via SSE
    sendSSE('supply_purchase_notification', [
        'type' => 'supply_purchase_status_changed',
        'title' => 'Supply Purchase Status Updated',
        'message' => "Purchase {$event->batch->invoice_number} status changed from {$event->oldStatus} to {$event->newStatus}",
        'data' => [
            'batch_id' => $event->batch->id,
            'old_status' => $event->oldStatus,
            'new_status' => $event->newStatus,
            'updated_by' => $event->updatedBy,
            'timestamp' => $event->timestamp,
            'invoice_number' => $event->batch->invoice_number,
            'notes' => $event->notes
        ],
        'requires_refresh' => in_array($event->newStatus, ['arrived', 'cancelled']),
        'priority' => $event->newStatus === 'arrived' ? 'high' : 'normal',
        'timestamp' => now()->toISOString()
    ]);
});

// Keep connection alive and listen for events
$startTime = time();
$maxTime = 300; // 5 minutes max connection time

while (true) {
    // Check if client disconnected
    if (connection_aborted()) {
        Log::info('SSE Bridge: Client disconnected');
        break;
    }

    // Check maximum connection time
    if ((time() - $startTime) > $maxTime) {
        sendSSE('timeout', [
            'message' => 'Connection timeout - please reconnect',
            'timestamp' => now()->toISOString()
        ]);
        break;
    }

    // Send heartbeat every 30 seconds
    if ((time() - $startTime) % 30 === 0) {
        sendSSE('heartbeat', [
            'message' => 'Connection alive',
            'uptime' => time() - $startTime,
            'timestamp' => now()->toISOString()
        ]);
    }

    // Check for any pending events or messages
    // This is where Laravel events will be processed

    // Small sleep to prevent excessive CPU usage
    usleep(100000); // 0.1 seconds
}

Log::info('Real-time notification bridge ended', [
    'duration' => time() - $startTime,
    'timestamp' => now()->toISOString()
]);

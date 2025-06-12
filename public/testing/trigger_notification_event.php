<?php

// Determine the correct Laravel root path
$currentDir = __DIR__;
$laravelRoot = null;

// Check if we're in testing directory (CLI access)
if (basename($currentDir) === 'testing') {
    $laravelRoot = dirname($currentDir);
}
// Check if we're accessed from web (public/testing/)
elseif (strpos($_SERVER['SCRIPT_FILENAME'] ?? '', '/public/testing/') !== false) {
    // Go up two levels: public/testing/ -> public/ -> Laravel root
    $laravelRoot = dirname(dirname($_SERVER['SCRIPT_FILENAME']));
}
// Additional check for Windows paths
elseif (strpos($_SERVER['SCRIPT_FILENAME'] ?? '', '\\public\\testing\\') !== false) {
    // Windows path: go up two levels
    $laravelRoot = dirname(dirname($_SERVER['SCRIPT_FILENAME']));
}
// Fallback - traverse up to find Laravel root
else {
    $checkDir = $currentDir;
    for ($i = 0; $i < 5; $i++) {
        if (file_exists($checkDir . '/vendor/autoload.php') || file_exists($checkDir . '\\vendor\\autoload.php')) {
            $laravelRoot = $checkDir;
            break;
        }
        $checkDir = dirname($checkDir);
    }
}

// Final validation and fallback
if (!$laravelRoot || (!file_exists($laravelRoot . '/vendor/autoload.php') && !file_exists($laravelRoot . '\\vendor\\autoload.php'))) {
    // Try direct calculation from known public path
    if (strpos($currentDir, 'public') !== false) {
        $parts = explode(DIRECTORY_SEPARATOR, $currentDir);
        $publicIndex = array_search('public', $parts);
        if ($publicIndex !== false) {
            $laravelRoot = implode(DIRECTORY_SEPARATOR, array_slice($parts, 0, $publicIndex));
        }
    }

    // Last resort fallback
    if (!$laravelRoot || (!file_exists($laravelRoot . '/vendor/autoload.php') && !file_exists($laravelRoot . '\\vendor\\autoload.php'))) {
        die(json_encode([
            'success' => false,
            'error' => 'Laravel root directory not found.',
            'debug' => [
                'current_dir' => $currentDir,
                'script_filename' => $_SERVER['SCRIPT_FILENAME'] ?? 'not set',
                'attempted_laravel_root' => $laravelRoot,
                'autoload_paths_checked' => [
                    $laravelRoot . '/vendor/autoload.php',
                    $laravelRoot . '\\vendor\\autoload.php'
                ]
            ]
        ]));
    }
}

// Use appropriate path separator
$autoloadPath = $laravelRoot . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
$bootstrapPath = $laravelRoot . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'app.php';

require_once $autoloadPath;

// Bootstrap Laravel
$app = require_once $bootstrapPath;
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Event;
use App\Models\User;
use App\Models\SupplyPurchaseBatch;
use App\Events\SupplyPurchaseStatusChanged;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    // Get request data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data) {
        throw new Exception('Invalid JSON data received');
    }

    Log::info('Browser trigger notification request received', [
        'data' => $data,
        'timestamp' => now()->toISOString(),
        'laravel_root' => $laravelRoot
    ]);

    // Get test data
    $user = User::first();
    $batch = SupplyPurchaseBatch::first();

    if (!$user || !$batch) {
        throw new Exception('No test data available (need at least 1 user and 1 batch)');
    }

    $response = ['success' => false, 'message' => '', 'data' => []];

    switch ($data['action']) {
        case 'trigger_status_change':
            // Simulate real status change
            $oldStatus = $batch->status;
            $newStatus = $oldStatus === 'draft' ? 'confirmed' : 'draft';

            Log::info('Triggering status change from browser', [
                'batch_id' => $batch->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'user_id' => $user->id
            ]);

            // Fire the event (this should trigger all listeners)
            event(new SupplyPurchaseStatusChanged(
                $batch,
                $oldStatus,
                $newStatus,
                $user->id,
                'Status change triggered from browser debugger - ' . now()->format('H:i:s'),
                [
                    'source' => 'browser_debugger',
                    'test_mode' => true,
                    'timestamp' => now()->toISOString(),
                    'triggered_by' => 'interactive_debugger'
                ]
            ));

            $response['success'] = true;
            $response['message'] = "Status change event fired: {$oldStatus} → {$newStatus}";
            $response['data'] = [
                'batch_id' => $batch->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'user_id' => $user->id,
                'timestamp' => now()->toISOString()
            ];

            Log::info('Browser-triggered status change event completed', $response['data']);
            break;

        case 'test_notification_system':
            // Test complete notification system
            $testResults = [];

            // Test 1: Event firing
            try {
                event(new SupplyPurchaseStatusChanged(
                    $batch,
                    'draft',
                    'confirmed',
                    $user->id,
                    'Browser system test',
                    ['source' => 'system_test']
                ));
                $testResults['event_firing'] = true;
            } catch (\Exception $e) {
                $testResults['event_firing'] = false;
                $testResults['event_error'] = $e->getMessage();
            }

            // Test 2: Database notifications
            try {
                $notificationCount = DB::table('notifications')->count();
                $testResults['database_notifications'] = $notificationCount;
            } catch (\Exception $e) {
                $testResults['database_error'] = $e->getMessage();
            }

            // Test 3: Broadcasting check
            $testResults['broadcasting_config'] = [
                'driver' => config('broadcasting.default'),
                'pusher_key' => config('broadcasting.connections.pusher.key'),
                'pusher_cluster' => config('broadcasting.connections.pusher.options.cluster')
            ];

            $response['success'] = true;
            $response['message'] = 'Notification system test completed';
            $response['data'] = $testResults;
            break;

        case 'fire_multiple_events':
            // Fire multiple events for testing
            $scenarios = [
                ['from' => 'draft', 'to' => 'confirmed', 'priority' => 'normal'],
                ['from' => 'confirmed', 'to' => 'shipped', 'priority' => 'medium'],
                ['from' => 'shipped', 'to' => 'arrived', 'priority' => 'high'],
                ['from' => 'arrived', 'to' => 'cancelled', 'priority' => 'medium']
            ];

            $eventsFired = 0;
            foreach ($scenarios as $i => $scenario) {
                try {
                    event(new SupplyPurchaseStatusChanged(
                        $batch,
                        $scenario['from'],
                        $scenario['to'],
                        $user->id,
                        "Multiple event test " . ($i + 1) . " - Priority: {$scenario['priority']}",
                        [
                            'source' => 'multiple_test',
                            'scenario' => $i + 1,
                            'priority' => $scenario['priority']
                        ]
                    ));
                    $eventsFired++;

                    // Small delay between events
                    usleep(200000); // 0.2 seconds
                } catch (\Exception $e) {
                    Log::error('Failed to fire event in scenario ' . ($i + 1), [
                        'error' => $e->getMessage(),
                        'scenario' => $scenario
                    ]);
                }
            }

            $response['success'] = true;
            $response['message'] = "Fired {$eventsFired} multiple test events";
            $response['data'] = [
                'events_fired' => $eventsFired,
                'total_scenarios' => count($scenarios),
                'batch_id' => $batch->id
            ];
            break;

        default:
            throw new Exception('Unknown action: ' . $data['action']);
    }
} catch (\Exception $e) {
    Log::error('Browser trigger notification failed', [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'request_data' => $data ?? null
    ]);

    $response = [
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ];
}

echo json_encode($response, JSON_PRETTY_PRINT);

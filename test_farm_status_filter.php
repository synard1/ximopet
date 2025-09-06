<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\AiDatabaseServiceRefactored;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

echo "\n=== TESTING Farm Status Filter ===\n";

// Simulate user login (adjust user ID as needed)
try {
    $user = \App\Models\User::find('9fcbe4b3-b708-4579-a5e7-1f186bf6fb24');
    if ($user) {
        Auth::login($user);
        echo "User logged in: {$user->email}\n";
    } else {
        echo "User not found, continuing without authentication\n";
    }
} catch (Exception $e) {
    echo "Error logging in user: " . $e->getMessage() . "\n";
}

$service = app(AiDatabaseServiceRefactored::class);

// Test queries with different status filters
$testQueries = [
    'ada berapa jumlah farm' => 'Should detect active farms (default)',
    'ada berapa semua farm' => 'Should detect all farms',
    'ada berapa total farm' => 'Should detect all farms',
    'ada berapa farm aktif' => 'Should detect active farms',
    'ada berapa farm tidak aktif' => 'Should detect inactive farms',
    'ada berapa farm nonaktif' => 'Should detect inactive farms'
];

foreach ($testQueries as $query => $expected) {
    echo "\n--- Testing Query: '$query' ---\n";
    echo "Expected: $expected\n";
    
    try {
        $result = $service->searchData($query);
        
        if (isset($result['farms'])) {
            $farmData = $result['farms'];
            echo "✓ Farm data found:\n";
            echo "  - Total farms: " . ($farmData['total_farms'] ?? 'N/A') . "\n";
            echo "  - Active farms: " . ($farmData['active_farms'] ?? 'N/A') . "\n";
            echo "  - Farm list: " . implode(', ', $farmData['farm_list'] ?? []) . "\n";
            
            if (isset($farmData['farm_details'])) {
                echo "  - Farm details:\n";
                foreach ($farmData['farm_details'] as $farm) {
                    echo "    * {$farm['name']} (Status: {$farm['status']})\n";
                }
            }
        } else {
            echo "✗ No farm data found in result\n";
        }
        
    } catch (Exception $e) {
        echo "✗ Error: " . $e->getMessage() . "\n";
    }
    
    echo "\n" . str_repeat('-', 50) . "\n";
}

echo "\n=== Test Completed ===\n";
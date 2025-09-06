<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Simulate user login
$user = \App\Models\User::where('email', 'synard1@gmail.com')->first();
\Illuminate\Support\Facades\Auth::login($user);
echo "User authenticated: " . $user->email . "\n";

// Get AI Chat Service
$aiChatService = app(\App\Services\AiChatService::class);

echo "\n=== Testing AI Farm Queries ===\n";

// Test queries with different farm status
$testQueries = [
    'ada berapa jumlah farm?',
    'ada berapa farm aktif?', 
    'ada berapa farm tidak aktif?',
    'berapa total semua farm?',
    'show me all farms',
    'show me inactive farms only'
];

foreach ($testQueries as $query) {
    echo "\n--- Query: '$query' ---\n";
    
    try {
        $response = $aiChatService->sendMessage($query);
        if ($response['success']) {
            echo "Response: " . $response['assistant_message']->content . "\n";
        } else {
            echo "Error: " . $response['error'] . "\n";
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
    
    echo "\n" . str_repeat('-', 50) . "\n";
}

echo "\n=== AI Farm Query Test Completed ===\n";
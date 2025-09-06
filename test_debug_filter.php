<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Simulate user login
$user = \App\Models\User::where('email', 'synard1@gmail.com')->first();
if (!$user) {
    echo "User not found!\n";
    exit(1);
}

// Set authenticated user
\Illuminate\Support\Facades\Auth::login($user);
echo "User authenticated: " . $user->email . "\n";

// Get the service
$service = app(\App\Services\AiDatabaseServiceRefactored::class);

// Use reflection to access private method
$reflection = new \ReflectionClass($service);
$method = $reflection->getMethod('getFarmDetails');
$method->setAccessible(true);

echo "\n=== Debug Filter Test ===\n";

// Test with 'inactive' status
echo "\n--- Testing 'inactive' filter ---\n";
$result = $method->invoke($service, null, 'inactive');
echo "Result: " . $result['total_farms'] . " farms\n";
echo "Farm details: " . json_encode($result['farm_details'], JSON_PRETTY_PRINT) . "\n";

// Test with 'all' status
echo "\n--- Testing 'all' filter ---\n";
$result = $method->invoke($service, null, 'all');
echo "Result: " . $result['total_farms'] . " farms\n";
echo "Farm details: " . json_encode($result['farm_details'], JSON_PRETTY_PRINT) . "\n";

echo "\n=== Test completed ===\n";
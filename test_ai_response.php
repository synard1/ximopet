<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Authenticate user
$user = \App\Models\User::first();
\Illuminate\Support\Facades\Auth::login($user);

echo "=== Testing AI Response with Increased Max Tokens ===\n";
echo "User: {$user->name} (ID: {$user->id})\n";
echo "Query: tampilkan semua data dari perusahaan Demo Company\n\n";

// Test AI response
$service = app('App\Services\AiChatService');
$result = $service->sendMessage('tampilkan semua data dari perusahaan Demo Company');

echo "=== AI Response Content ===\n";
if (isset($result['assistant_message']['content'])) {
    $content = $result['assistant_message']['content'];
    echo $content . "\n";
    echo "\n=== Response Length: " . strlen($content) . " characters ===\n";
    
    // Check if farm data is included
    $hasFarmData = strpos($content, 'farm') !== false || strpos($content, 'Farm') !== false;
    echo "Contains farm data: " . ($hasFarmData ? 'YES' : 'NO') . "\n";
} else {
    echo "No content found in assistant_message\n";
    echo "Available keys: " . implode(', ', array_keys($result)) . "\n";
}
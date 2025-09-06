<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Authenticate user
$user = \App\Models\User::first();
\Illuminate\Support\Facades\Auth::login($user);

echo "=== Testing Farm Count Query Fix ===\n";
echo "User: {$user->name} (ID: {$user->id})\n";
echo "Query: ada berapa jumlah farm\n\n";

// Test AI response
$service = app('App\Services\AiChatService');
$result = $service->sendMessage('ada berapa jumlah farm');

echo "=== AI Response ===\n";
if (isset($result['assistant_message']['content'])) {
    $content = $result['assistant_message']['content'];
    echo $content . "\n";
    echo "\n=== Analysis ===\n";
    echo "Response length: " . strlen($content) . " characters\n";

    // Check if response follows expected format
    $expectedPattern = '/Saat ini ada \d+ farm aktif/';
    $followsFormat = preg_match($expectedPattern, $content);
    echo "Follows expected format 'Saat ini ada X farm aktif': " . ($followsFormat ? 'YES ✅' : 'NO ❌') . "\n";

    // Check if mentions company unnecessarily
    $mentionsCompany = strpos(strtolower($content), 'perusahaan') !== false || strpos(strtolower($content), 'company') !== false;
    echo "Mentions company (should be NO): " . ($mentionsCompany ? 'YES ❌' : 'NO ✅') . "\n";
} else {
    echo "No content found in assistant_message\n";
    echo "Available keys: " . implode(', ', array_keys($result)) . "\n";
}

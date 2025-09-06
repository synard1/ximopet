<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Test if services are available
echo "Testing AI Chat V2 Services\n";
echo "========================\n";

try {
    // Test if Livewire is available
    if (class_exists('\Livewire\Livewire')) {
        echo "✓ Livewire is available\n";
    } else {
        echo "✗ Livewire is not available\n";
    }
    
    // Test if ChatBubble component exists
    if (class_exists('\App\AiChatV2\Livewire\ChatBubble')) {
        echo "✓ ChatBubble component exists\n";
    } else {
        echo "✗ ChatBubble component does not exist\n";
    }
    
    // Test if SettingsService exists
    if (class_exists('\App\AiChatV2\Services\SettingsService')) {
        echo "✓ SettingsService exists\n";
    } else {
        echo "✗ SettingsService does not exist\n";
    }
    
    // Test if ChatSessionService exists
    if (class_exists('\App\AiChatV2\Services\ChatSessionService')) {
        echo "✓ ChatSessionService exists\n";
    } else {
        echo "✗ ChatSessionService does not exist\n";
    }
    
    // Test if OpenWebUIService exists
    if (class_exists('\App\Services\OpenWebUIService')) {
        echo "✓ OpenWebUIService exists\n";
    } else {
        echo "✗ OpenWebUIService does not exist\n";
    }
    
    // Test if OllamaService exists
    if (class_exists('\App\Services\OllamaService')) {
        echo "✓ OllamaService exists\n";
    } else {
        echo "✗ OllamaService does not exist\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Auth;

echo "Testing Chat Bubble Component\n";
echo "===========================\n";

// Check if we can instantiate the component
try {
    $component = new \App\AiChatV2\Livewire\ChatBubble();
    echo "✓ ChatBubble component instantiated successfully\n";
    
    // Try to call the mount method
    $component->mount();
    echo "✓ ChatBubble mount method called successfully\n";
    
    // Try to render the component
    $view = $component->render();
    echo "✓ ChatBubble render method called successfully\n";
    
    echo "\nTest completed successfully!\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
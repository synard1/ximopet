<?php

/**
 * Test script untuk memverifikasi context tracking di ChatMessage
 * Script ini akan menampilkan metadata dari pesan chat terbaru
 */

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ChatMessage;
use Illuminate\Support\Facades\DB;

try {
    echo "=== Test Context Tracking di ChatMessage ===\n\n";
    
    // Ambil 3 pesan chat terbaru
    $recentMessages = ChatMessage::with(['chatSession', 'user'])
        ->where('message_type', 'assistant')
        ->orderBy('created_at', 'desc')
        ->limit(3)
        ->get();
    
    if ($recentMessages->isEmpty()) {
        echo "Tidak ada pesan assistant yang ditemukan.\n";
        exit;
    }
    
    foreach ($recentMessages as $index => $message) {
        echo "=== Pesan #" . ($index + 1) . " ===\n";
        echo "ID: {$message->id}\n";
        echo "Session ID: {$message->chat_session_id}\n";
        echo "User: " . ($message->user ? $message->user->name : 'Unknown') . "\n";
        echo "Created: {$message->created_at}\n";
        echo "Content Preview: " . substr($message->content, 0, 100) . "...\n";
        
        if ($message->metadata) {
            echo "\nMetadata:\n";
            foreach ($message->metadata as $key => $value) {
                if ($key === 'context_sent_to_llm') {
                    echo "  - {$key}: " . substr($value, 0, 200) . "... (" . strlen($value) . " chars)\n";
                } elseif ($key === 'user_query') {
                    echo "  - {$key}: {$value}\n";
                } else {
                    echo "  - {$key}: " . (is_array($value) ? json_encode($value) : $value) . "\n";
                }
            }
        } else {
            echo "\nTidak ada metadata.\n";
        }
        
        echo "\n" . str_repeat("-", 50) . "\n\n";
    }
    
    // Statistik context tracking
    echo "=== Statistik Context Tracking ===\n";
    $messagesWithContext = ChatMessage::where('message_type', 'assistant')
        ->whereJsonContains('metadata->context_sent_to_llm', null, 'not')
        ->count();
    
    $totalAssistantMessages = ChatMessage::where('message_type', 'assistant')->count();
    
    echo "Total pesan assistant: {$totalAssistantMessages}\n";
    echo "Pesan dengan context tracking: {$messagesWithContext}\n";
    echo "Persentase tracking: " . ($totalAssistantMessages > 0 ? round(($messagesWithContext / $totalAssistantMessages) * 100, 2) : 0) . "%\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AiChatService;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class TestEnhancedTimeout extends Command
{
    protected $signature = 'test:enhanced-timeout {user_id?}';
    protected $description = 'Test enhanced timeout handling and fallback';

    public function handle()
    {
        $userId = $this->argument('user_id');

        if ($userId) {
            $user = User::find($userId);
        } else {
            $user = User::first();
        }

        if (!$user) {
            $this->error('No user found');
            return 1;
        }

        Auth::login($user);

        $this->info("Testing Enhanced Timeout Handling");
        $this->info("User: {$user->name}");
        $this->info("Company ID: " . ($user->company_id ?: 'None'));

        $chatService = app(AiChatService::class);

        // Test the problematic query from the error log
        $this->info("\n=== Testing Problematic Query ===");
        $problemQuery = "tampilkan semua data perusahaan yang ada";

        try {
            $this->line("Sending query: '$problemQuery'");
            $this->line("This may take up to 3 minutes with enhanced retry logic...");

            $startTime = microtime(true);
            $result = $chatService->sendMessage($problemQuery);
            $totalTime = microtime(true) - $startTime;

            if ($result['success']) {
                $this->info("✅ Query successful after " . number_format($totalTime, 2) . "s");

                $assistantMessage = $result['assistant_message'];
                $metadata = $assistantMessage->metadata ?? [];

                // Check if fallback was used
                if (isset($metadata['used_fallback']) && $metadata['used_fallback']) {
                    $this->warn("⚠️ Fallback was used!");
                    $this->line("Original provider: " . ($metadata['original_provider'] ?? 'unknown'));
                    $this->line("Fallback provider: " . ($metadata['fallback_provider'] ?? 'unknown'));
                    $this->line("Fallback reason: " . ($metadata['fallback_reason'] ?? 'unknown'));
                    $this->line("Original error: " . substr($metadata['original_error'] ?? '', 0, 100) . "...");
                } else {
                    $this->info("✅ Primary provider worked correctly");
                }

                $this->line("Processing time: " . number_format($result['processing_time'], 3) . "s");
                $this->line("Response length: " . strlen($assistantMessage->content) . " characters");
                $this->line("Response preview: " . substr($assistantMessage->content, 0, 200) . "...");

            } else {
                $this->error("❌ Query failed: " . $result['error']);
                $this->line("Total attempt time: " . number_format($totalTime, 2) . "s");
            }

        } catch (\Exception $e) {
            $this->error("❌ Exception: " . $e->getMessage());
        }

        // Test simple query to verify system still works for casual conversations
        $this->info("\n=== Testing Simple Query ===");
        $simpleQuery = "hi";

        try {
            $this->line("Sending simple query: '$simpleQuery'");

            $result = $chatService->sendMessage($simpleQuery);

            if ($result['success']) {
                $this->info("✅ Simple query successful");
                $response = $result['assistant_message']->content;
                $this->line("Response: " . $response);
            } else {
                $this->error("❌ Simple query failed: " . $result['error']);
            }

        } catch (\Exception $e) {
            $this->error("❌ Simple query exception: " . $e->getMessage());
        }

        $this->info("\n=== Configuration Status ===");
        $this->line("OpenWebUI timeout: " . config('chat.providers.openwebui.timeout') . "s");
        $this->line("Ollama enabled as fallback: " . (config('chat.providers.ollama.enabled') ? 'Yes' : 'No'));
        $this->line("Ollama timeout: " . config('chat.providers.ollama.timeout') . "s");

        $this->info("\n=== Test Complete ===");

        return 0;
    }
}

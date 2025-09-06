<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\OpenWebUIService;
use App\Services\OllamaChatService;
use App\Services\AiChatService;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Exception;

class TestTimeoutHandling extends Command
{
    protected $signature = 'test:timeout-handling {user_id?}';
    protected $description = 'Test timeout handling and fallback mechanisms in AI chat';

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

        $this->info("Testing timeout handling and fallback mechanisms");
        $this->info("User: {$user->name}");

        // Test OpenWebUI service connectivity
        $this->info("\n=== Testing OpenWebUI Service ===");
        $openWebUIService = app(OpenWebUIService::class);

        try {
            $this->line("Checking OpenWebUI connection...");
            $isConnected = $openWebUIService->validateConnection();

            if ($isConnected) {
                $this->info("✅ OpenWebUI is accessible");

                // Test a simple request
                $this->line("Testing simple request...");
                $response = $openWebUIService->sendChatRequest("hi", null, []);
                $this->info("✅ Simple request successful");
                $this->line("Response: " . substr($response['content'], 0, 100) . "...");

            } else {
                $this->warn("⚠️ OpenWebUI is not accessible");
            }

        } catch (Exception $e) {
            $this->error("❌ OpenWebUI error: " . $e->getMessage());
        }

        // Test Ollama service connectivity
        $this->info("\n=== Testing Ollama Service ===");
        $ollamaService = app(OllamaChatService::class);

        try {
            $this->line("Checking Ollama connection...");
            $isConnected = $ollamaService->validateConnection();

            if ($isConnected) {
                $this->info("✅ Ollama is accessible");

                // Test a simple request
                $this->line("Testing simple request...");
                $response = $ollamaService->sendChatRequest("hi", null, []);
                $this->info("✅ Simple request successful");
                $this->line("Response: " . substr($response['content'], 0, 100) . "...");

            } else {
                $this->warn("⚠️ Ollama is not accessible");
            }

        } catch (Exception $e) {
            $this->error("❌ Ollama error: " . $e->getMessage());
        }

        // Test main chat service with potential fallback
        $this->info("\n=== Testing Main Chat Service with Fallback ===");
        $chatService = app(AiChatService::class);

        try {
            $this->line("Testing business query that might timeout...");
            $result = $chatService->sendMessage("tampilkan semua data perusahaan yang ada");

            if ($result['success']) {
                $this->info("✅ Chat request successful");

                $assistantMessage = $result['assistant_message'];
                $this->line("Processing time: " . number_format($result['processing_time'], 3) . "s");
                $this->line("Response length: " . strlen($assistantMessage->content) . " characters");

                // Check if fallback was used
                $metadata = $assistantMessage->metadata ?? [];
                if (isset($metadata['used_fallback']) && $metadata['used_fallback']) {
                    $this->warn("⚠️ Fallback was used!");
                    $this->line("Original provider: " . ($metadata['original_provider'] ?? 'unknown'));
                    $this->line("Fallback reason: " . ($metadata['fallback_reason'] ?? 'unknown'));
                } else {
                    $this->info("✅ Primary provider worked correctly");
                }

                $this->line("Response preview: " . substr($assistantMessage->content, 0, 200) . "...");

            } else {
                $this->error("❌ Chat request failed: " . $result['error']);
            }

        } catch (Exception $e) {
            $this->error("❌ Chat service error: " . $e->getMessage());
        }

        $this->info("\n=== Configuration Summary ===");
        $this->line("OpenWebUI enabled: " . (config('chat.providers.openwebui.enabled') ? 'Yes' : 'No'));
        $this->line("OpenWebUI timeout: " . config('chat.providers.openwebui.timeout') . "s");
        $this->line("Ollama enabled: " . (config('chat.providers.ollama.enabled') ? 'Yes' : 'No'));
        $this->line("Ollama timeout: " . config('chat.providers.ollama.timeout') . "s");
        $this->line("Default provider: " . config('chat.system.default_provider'));

        $this->info("\n=== Test Complete ===");

        return 0;
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestTimeoutConfig extends Command
{
    protected $signature = 'test:timeout-config';
    protected $description = 'Test timeout configuration values';

    public function handle()
    {
        $this->info("=== Timeout Configuration Test ===");

        // Check environment variables
        $this->info("\n--- Environment Variables ---");
        $this->line("OPENWEBUI_TIMEOUT: " . env('OPENWEBUI_TIMEOUT', 'not set'));
        $this->line("OLLAMA_TIMEOUT: " . env('OLLAMA_TIMEOUT', 'not set'));
        $this->line("OPENWEBUI_ENABLED: " . env('OPENWEBUI_ENABLED', 'not set'));
        $this->line("OLLAMA_ENABLED: " . env('OLLAMA_ENABLED', 'not set'));

        // Check config values
        $this->info("\n--- Config Values ---");
        $this->line("OpenWebUI timeout: " . config('chat.providers.openwebui.timeout'));
        $this->line("Ollama timeout: " . config('chat.providers.ollama.timeout'));
        $this->line("OpenWebUI enabled: " . (config('chat.providers.openwebui.enabled') ? 'true' : 'false'));
        $this->line("Ollama enabled: " . (config('chat.providers.ollama.enabled') ? 'true' : 'false'));
        $this->line("Default provider: " . config('chat.system.default_provider'));

        // Test service initialization
        $this->info("\n--- Service Initialization ---");
        try {
            $openWebUIService = app(\App\Services\OpenWebUIService::class);
            $this->info("✅ OpenWebUIService initialized successfully");

            $ollamaService = app(\App\Services\OllamaChatService::class);
            $this->info("✅ OllamaChatService initialized successfully");

            $aiChatService = app(\App\Services\AiChatService::class);
            $this->info("✅ AiChatService initialized successfully");

        } catch (\Exception $e) {
            $this->error("❌ Service initialization failed: " . $e->getMessage());
        }

        // Check connection without making requests
        $this->info("\n--- Quick Connection Check ---");
        try {
            $openWebUIService = app(\App\Services\OpenWebUIService::class);
            $status = $openWebUIService->getStatus();
            $this->line("OpenWebUI connected: " . ($status['connected'] ? 'Yes' : 'No'));

        } catch (\Exception $e) {
            $this->warn("OpenWebUI status check failed: " . $e->getMessage());
        }

        $this->info("\n=== Configuration Test Complete ===");

        return 0;
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\OllamaChatService;
use App\Services\OpenWebUIService;
use Illuminate\Support\Facades\Http;

class AiChatStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:chat-status
                            {--provider= : Check status of specific provider (ollama|openwebui)}
                            {--models : List available models}
                            {--debug : Enable detailed logging}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check AI chat provider status and configuration';

    /**
     * Execute the console command.
     */
    public function handle(OllamaChatService $ollamaService, OpenWebUIService $openWebUIService)
    {
        $provider = $this->option('provider');
        $listModels = $this->option('models');
        $debug = $this->option('debug');

        $this->info("🔍 AI Chat Provider Status Check");

        // Check all providers if none specified
        if (!$provider) {
            $this->checkOllamaStatus($ollamaService, $listModels, $debug);
            $this->checkOpenWebUIStatus($openWebUIService, $listModels, $debug);
        } elseif ($provider === 'ollama') {
            $this->checkOllamaStatus($ollamaService, $listModels, $debug);
        } elseif ($provider === 'openwebui') {
            $this->checkOpenWebUIStatus($openWebUIService, $listModels, $debug);
        } else {
            $this->error("Invalid provider: {$provider}. Use 'ollama' or 'openwebui'");
            return 1;
        }

        return 0;
    }

    /**
     * Check Ollama provider status
     */
    private function checkOllamaStatus(OllamaChatService $ollamaService, bool $listModels, bool $debug): void
    {
        $this->newLine();
        $this->line("🤖 Ollama Provider Status:");

        // Check if enabled
        $enabled = config('chat.providers.ollama.enabled', false);
        $this->line("  Status: " . ($enabled ? '✅ Enabled' : '❌ Disabled'));

        if (!$enabled) {
            return;
        }

        // Get configuration
        $baseUrl = config('chat.providers.ollama.base_url', 'http://localhost:11434');
        $defaultModel = config('chat.providers.ollama.default_model');
        $timeout = config('chat.providers.ollama.timeout', 60);

        $this->line("  Base URL: {$baseUrl}");
        $this->line("  Default Model: {$defaultModel}");
        $this->line("  Timeout: {$timeout}s");

        // Check connection
        if ($debug) {
            $this->line("  🌐 Testing connection...");
        }

        $connectionOk = $ollamaService->checkServerConnection($baseUrl);
        $this->line("  Connection: " . ($connectionOk ? '✅ Connected' : '❌ Connection Failed'));

        if (!$connectionOk) {
            $this->line("  💡 Make sure Ollama is running and accessible at {$baseUrl}");
            return;
        }

        // List models if requested
        if ($listModels) {
            try {
                $models = $ollamaService->getAvailableModelsArray($baseUrl);
                if (!empty($models)) {
                    $this->line("  🧠 Available Models:");
                    foreach ($models as $model) {
                        $this->line("    • {$model}");
                    }
                } else {
                    $this->line("  🧠 No models found");
                }
            } catch (\Exception $e) {
                $this->line("  ❌ Error listing models: " . $e->getMessage());
            }
        }
    }

    /**
     * Check OpenWebUI provider status
     */
    private function checkOpenWebUIStatus(OpenWebUIService $openWebUIService, bool $listModels, bool $debug): void
    {
        $this->newLine();
        $this->line("🌐 OpenWebUI Provider Status:");

        // Check if enabled
        $enabled = config('chat.providers.openwebui.enabled', false);
        $this->line("  Status: " . ($enabled ? '✅ Enabled' : '❌ Disabled'));

        if (!$enabled) {
            return;
        }

        // Get configuration
        $baseUrl = config('chat.providers.openwebui.base_url');
        $defaultModel = config('chat.providers.openwebui.default_model');
        $timeout = config('chat.providers.openwebui.timeout', 90);

        $this->line("  Base URL: {$baseUrl}");
        $this->line("  Default Model: {$defaultModel}");
        $this->line("  Timeout: {$timeout}s");

        // Check connection
        if ($debug) {
            $this->line("  🌐 Testing connection...");
            $this->line("  📡 Configuration details:");
            $this->line("    - API Key configured: " . (!empty(config('chat.providers.openwebui.api_key')) ? 'Yes' : 'No'));
            $this->line("    - Base URL: {$baseUrl}");
            $this->line("    - Timeout setting: {$timeout}s");
        }

        // Add a timeout to prevent hanging
        $connectionOk = false;
        try {
            $connectionOk = $openWebUIService->checkServerConnection();
        } catch (\Exception $e) {
            Log::error('AiChatStatusCommand: Error checking OpenWebUI connection', [
                'error' => $e->getMessage(),
                'exception' => get_class($e)
            ]);
            $connectionOk = false;
        }

        $this->line("  Connection: " . ($connectionOk ? '✅ Connected' : '❌ Connection Failed'));

        if (!$connectionOk) {
            $this->line("  💡 Make sure OpenWebUI is running and accessible at {$baseUrl}");
            if ($debug) {
                $this->line("  🛠️  Debug suggestions:");
                $this->line("    1. Check if the URL is accessible from your server");
                $this->line("    2. Verify API key is valid");
                $this->line("    3. Check firewall settings");
                $this->line("    4. Verify SSL certificate if using HTTPS");
                $this->line("    5. Check network connectivity");
            }
            return;
        }

        // List models if requested
        if ($listModels) {
            try {
                $models = $openWebUIService->getAvailableModels();
                if (!empty($models)) {
                    $this->line("  🧠 Available Models:");
                    foreach ($models as $model) {
                        $this->line("    • {$model}");
                    }
                } else {
                    $this->line("  🧠 No models found");
                }
            } catch (\Exception $e) {
                $this->line("  ❌ Error listing models: " . $e->getMessage());
            }
        }
    }
}

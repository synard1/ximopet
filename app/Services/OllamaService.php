<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OllamaService
{
    protected string $baseUrl;
    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = config('ai-chat-v2.providers.ollama.base_url', 'http://localhost:11434');
        $this->timeout = config('ai-chat-v2.providers.ollama.timeout', 60);
    }

    /**
     * Send a message to Ollama
     */
    public function sendMessage(string $model, string $prompt, array $options = []): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->post("{$this->baseUrl}/api/generate", [
                    'model' => $model,
                    'prompt' => $prompt,
                    'stream' => false,
                    'options' => $options
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'content' => $data['response'] ?? '',
                    'model' => $model,
                    'done' => $data['done'] ?? true,
                    'context' => $data['context'] ?? null,
                    'total_duration' => $data['total_duration'] ?? null,
                    'load_duration' => $data['load_duration'] ?? null,
                    'prompt_eval_count' => $data['prompt_eval_count'] ?? null,
                    'prompt_eval_duration' => $data['prompt_eval_duration'] ?? null,
                    'eval_count' => $data['eval_count'] ?? null,
                    'eval_duration' => $data['eval_duration'] ?? null,
                ];
            }

            return [
                'content' => 'Error: Unable to get response from Ollama service',
                'model' => $model,
                'error' => 'HTTP ' . $response->status()
            ];
        } catch (\Exception $e) {
            Log::error('OllamaService error', [
                'error' => $e->getMessage(),
                'model' => $model,
                'prompt' => $prompt
            ]);

            return [
                'content' => 'Error: ' . $e->getMessage(),
                'model' => $model,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check if Ollama server is accessible
     */
    public function checkConnection(): bool
    {
        try {
            $response = Http::timeout(10)->get("{$this->baseUrl}/api/tags");
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get available models
     */
    public function getAvailableModels(): array
    {
        try {
            $response = Http::timeout(10)->get("{$this->baseUrl}/api/tags");
            if ($response->successful()) {
                $models = $response->json()['models'] ?? [];
                return array_column($models, 'name');
            }
        } catch (\Exception $e) {
            // Ignore error
        }
        return [];
    }
}
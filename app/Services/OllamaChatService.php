<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\Livestock;
use App\Models\FeedPurchase;
use App\Models\SupplyPurchase;
use Exception;

class OllamaChatService
{
    /**
     * Check if server is accessible
     */
    public function checkServerConnection(string $url): bool
    {
        try {
            $response = Http::timeout(10)->get("{$url}/api/tags");
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if model is available
     */
    public function checkModelAvailability(string $url, string $model): bool
    {
        try {
            $response = Http::timeout(10)->get("{$url}/api/tags");
            if (!$response->successful()) {
                return false;
            }

            $models = $response->json()['models'] ?? [];
            $availableModels = array_column($models, 'name');

            return in_array($model, $availableModels);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get available models as array
     */
    public function getAvailableModelsArray(string $url): array
    {
        try {
            $response = Http::timeout(10)->get("{$url}/api/tags");
            if ($response->successful()) {
                $models = $response->json()['models'] ?? [];
                return array_column($models, 'name');
            }
        } catch (\Exception $e) {
            // Ignore error
        }
        return [];
    }

    /**
     * Suggest similar model based on name similarity
     */
    public function suggestSimilarModel(string $requestedModel, array $availableModels): ?string
    {
        $requestedModel = strtolower($requestedModel);
        $requestedBase = preg_replace('/:latest$|:\d+\.\d+.*$/', '', $requestedModel);

        foreach ($availableModels as $model) {
            $modelLower = strtolower($model);
            $modelBase = preg_replace('/:latest$|:\d+\.\d+.*$/', '', $modelLower);

            // Exact base name match
            if ($modelBase === $requestedBase) {
                return $model;
            }

            // Partial match (e.g., qwen2.5vl -> qwen3)
            if (strpos($modelBase, $requestedBase) !== false || strpos($requestedBase, $modelBase) !== false) {
                return $model;
            }

            // Special case for qwen variants
            if (strpos($requestedBase, 'qwen') !== false && strpos($modelBase, 'qwen') !== false) {
                return $model;
            }
        }

        return null;
    }

    /**
     * Get popular models
     */
    public function getPopularModels(): array
    {
        return [
            'llama2:latest',
            'llama3.1:latest',
            'qwen3:8b',
            'gemma3n:latest',
            'deepseek-coder:latest'
        ];
    }

    /**
     * Detect natural language queries and convert to complex queries
     */
    public function detectNaturalLanguageQuery(string $prompt): ?string
    {
        $promptLower = strtolower($prompt);

        // Enhanced purchase-related queries with specific types
        if (preg_match('/(pembelian|purchase|beli|buy)/i', $promptLower)) {
            // Detect specific purchase types
            if (preg_match('/(pakan|feed|makanan)/i', $promptLower)) {
                return $this->buildPurchaseQuery('feed', $promptLower);
            }

            if (preg_match('/(ternak|livestock|ayam|chicken|sapi|cow)/i', $promptLower)) {
                return $this->buildPurchaseQuery('livestock', $promptLower);
            }

            if (preg_match('/(supply|suplai|perlengkapan|equipment)/i', $promptLower)) {
                return $this->buildPurchaseQuery('supply', $promptLower);
            }

            // Generic purchase query
            return $this->buildPurchaseQuery('all', $promptLower);
        }

        // Detect livestock-related queries (non-purchase)
        if (preg_match('/(ternak|livestock|ayam|chicken|sapi|cow)/i', $promptLower)) {
            // Detect status queries
            if (preg_match('/(aktif|active|berjalan|running)/i', $promptLower)) {
                return "livestock status=active";
            }

            if (preg_match('/(selesai|completed|finished)/i', $promptLower)) {
                return "livestock status=completed";
            }

            // Default to active livestock
            return "livestock status=active";
        }

        // Detect analytics queries
        if (preg_match('/(analisis|analytics|laporan|report|statistik|statistics)/i', $promptLower)) {
            return "analytics overview";
        }

        // Detect performance queries
        if (preg_match('/(performa|performance|kinerja|efficiency)/i', $promptLower)) {
            return "analytics performance";
        }

        return null;
    }

    /**
     * Build purchase query based on type and criteria
     */
    private function buildPurchaseQuery(string $type, string $promptLower): string
    {
        // Detect time-based queries
        if (preg_match('/(bulan|month)\s+(januari|februari|maret|april|mei|juni|juli|agustus|september|oktober|november|desember|jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)\s+(\d{4})/i', $promptLower, $matches)) {
            $month = $this->convertMonthToNumber($matches[2]);
            $year = $matches[3];
            $startDate = "{$year}-{$month}-01";
            $endDate = "{$year}-{$month}-31";

            if ($type === 'all') {
                return "pembelian bulan {$matches[2]} {$year}";
            }
            return "pembelian {$type} bulan {$matches[2]} {$year}";
        }

        // Detect year-based queries
        if (preg_match('/(tahun|year)\s+(\d{4})/i', $promptLower, $matches)) {
            $year = $matches[2];
            if ($type === 'all') {
                return "pembelian tahun {$year}";
            }
            return "pembelian {$type} tahun {$year}";
        }

        // Detect recent queries
        if (preg_match('/(terbaru|recent|latest|terakhir|last)/i', $promptLower)) {
            if ($type === 'all') {
                return "pembelian terbaru";
            }
            return "pembelian {$type} terbaru";
        }

        // Default to recent purchases
        if ($type === 'all') {
            return "pembelian terbaru";
        }
        return "pembelian {$type} terbaru";
    }

    /**
     * Convert month name to number
     */
    public function convertMonthToNumber(string $month): string
    {
        $months = [
            'januari' => '01',
            'jan' => '01',
            'februari' => '02',
            'feb' => '02',
            'maret' => '03',
            'mar' => '03',
            'april' => '04',
            'apr' => '04',
            'mei' => '05',
            'may' => '05',
            'juni' => '06',
            'jun' => '06',
            'juli' => '07',
            'jul' => '07',
            'agustus' => '08',
            'aug' => '08',
            'september' => '09',
            'sep' => '09',
            'oktober' => '10',
            'oct' => '10',
            'november' => '11',
            'nov' => '11',
            'desember' => '12',
            'dec' => '12'
        ];

        $monthLower = strtolower($month);
        return $months[$monthLower] ?? '01';
    }

    /**
     * Check if message is a simple casual conversation
     */
    private function isSimpleCasualMessage(string $message): bool
    {
        $message = strtolower(trim($message));

        // Simple greetings and casual responses
        $casualPatterns = [
            '/^(hi|hello|hai|halo)\s*[!.]*\s*$/',
            '/^(good morning|good afternoon|good evening|selamat pagi|selamat siang|selamat sore)\s*[!.]*\s*$/',
            '/^(how are you|apa kabar|bagaimana kabar)\s*[?!.]*\s*$/',
            '/^(thanks|thank you|terima kasih|makasih)\s*[!.]*\s*$/',
            '/^(bye|goodbye|sampai jumpa|dadah)\s*[!.]*\s*$/',
            // Removed 'ok', 'okay', 'oke', 'baik' patterns to prevent false positives in normal conversation
            '/^(yes|no|ya|tidak|iya)\s*[!.]*\s*$/',
            '/^(help|bantuan|tolong)\s*[!.]*\s*$/'
        ];

        foreach ($casualPatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                return true;
            }
        }

        // Check for business-related keywords first to avoid false positives
        $businessKeywords = ['berapa', 'ternak', 'ayam', 'pakan', 'kandang', 'keuangan', 'financial', 'farm', 'livestock'];
        foreach ($businessKeywords as $keyword) {
            if (strpos($message, $keyword) !== false) {
                return false; // Definitely business-related
            }
        }

        // Simple questions without business context (only if no business keywords found)
        if (strlen($message) < 20 && str_word_count($message) <= 3) {
            return true;
        }

        return false;
    }

    /**
     * Generate cache key
     */
    public function generateCacheKey(string $type, ?string $param1, ?string $param2, int $limit): string
    {
        return "ollama_context_{$type}_" . md5($param1 . $param2 . $limit);
    }

    /**
     * Get data from cache
     */
    public function getFromCache(string $key): ?string
    {
        try {
            return Cache::get($key);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Set data in cache
     */
    public function setCache(string $key, string $data, int $ttl): void
    {
        try {
            Cache::put($key, $data, $ttl);
        } catch (\Exception $e) {
            // Ignore cache errors
        }
    }

    /**
     * Send chat request compatible with new chat system
     */
    public function sendChatRequest(string $message, string $model = null, array $context = []): array
    {
        try {
            $model = $model ?: config('chat.providers.ollama.default_model', 'llama2');
            $baseUrl = config('chat.providers.ollama.base_url', 'http://localhost:11434');
            $startTime = microtime(true);

            // Check if server is accessible
            if (!$this->checkServerConnection($baseUrl)) {
                throw new \Exception('Ollama server is not accessible at ' . $baseUrl);
            }

            // Check if model is available
            if (!$this->checkModelAvailability($baseUrl, $model)) {
                // Try to suggest a similar model
                $availableModels = $this->getAvailableModelsArray($baseUrl);
                $suggestedModel = $this->suggestSimilarModel($model, $availableModels);

                if ($suggestedModel) {
                    $model = $suggestedModel;
                } else {
                    throw new \Exception("Model '{$model}' is not available. Available models: " . implode(', ', $availableModels));
                }
            }

            // Detect if this is a simple casual conversation
            $isSimpleCasual = $this->isSimpleCasualMessage($message);

            // Prepare request options
            $options = [
                'temperature' => config('chat.providers.ollama.temperature', 0.7),
                'num_predict' => config('chat.providers.ollama.max_tokens', 2000)
            ];

            // Add ultra-strict no-think parameter for simple casual conversations
            if ($isSimpleCasual) {
                $options['system'] = 'You are a helpful assistant. Respond directly and naturally without showing your thinking process. Keep responses concise and friendly. '
                    . 'CRITICAL: Never show your thinking process, never use phrases like "Let me", "I need to", "I should", "Okay", "the user", etc. '
                    . 'Never make assumptions about data you don\'t have access to. Just provide direct helpful responses.';
                $options['temperature'] = 0.3; // Lower temperature for more predictable casual responses
            } else {
                // For business queries, add ULTRA STRICT anti-speculation instructions
                $languageInstruction = $this->detectLanguageUltraLight($message)
                    ? 'RESPOND IN INDONESIAN LANGUAGE (Bahasa Indonesia). '
                    : 'RESPOND IN ENGLISH LANGUAGE. ';

                $options['system'] = $languageInstruction . 'You are a helpful assistant for farm management. '
                    . 'ULTRA CRITICAL INSTRUCTIONS - FAILURE TO FOLLOW THESE WILL CAUSE SYSTEM ERRORS: '
                    . '1. ABSOLUTELY NEVER show your thinking process, internal reasoning, or meta-commentary. '
                    . '2. BANNED PHRASES: "Let me think", "I should", "the user said", "Okay, the user", "I need to", "Wait, let me", "First, I", "But I", "Hmm", "<think>", "Let me consider", "I think", "Let me analyze". '
                    . '3. NEVER make assumptions about data you don\'t have access to. '
                    . '4. Only use the provided context data to answer questions accurately. '
                    . '5. If you cannot answer based on the context, provide general guidance or suggest contacting an administrator. '
                    . '6. Be direct, professional, and factual in all responses - start answering immediately. '
                    . '7. NEVER explain what the user is asking or rephrase their question. '
                    . '8. Provide information directly without preambles or explanations of your process.';
            }

            // Send request to Ollama
            $response = Http::timeout(config('chat.providers.ollama.timeout', 60))
                ->post($baseUrl . '/api/generate', [
                    'model' => $model,
                    'prompt' => $message,
                    'stream' => false,
                    'options' => $options
                ]);

            $processingTime = microtime(true) - $startTime;

            if (!$response->successful()) {
                throw new \Exception('Ollama API error: ' . $response->body());
            }

            $data = $response->json();

            return [
                'content' => $data['response'] ?? 'No response generated',
                'model' => $model,
                'processing_time' => $processingTime,
                'token_count' => null, // Ollama doesn't provide token count by default
                'provider' => 'ollama',
                'done' => $data['done'] ?? true
            ];

        } catch (\Exception $e) {
            Log::error('OllamaChatService: Error in chat request', [
                'error' => $e->getMessage(),
                'message' => $message,
                'model' => $model
            ]);

            throw new \Exception('Failed to get response from Ollama: ' . $e->getMessage());
        }
    }

    /**
     * Stream chat request (for future enhancement)
     */
    public function streamChatRequest(string $message, string $model = null, callable $callback = null): array
    {
        try {
            $model = $model ?: config('chat.providers.ollama.default_model', 'llama2');
            $baseUrl = config('chat.providers.ollama.base_url', 'http://localhost:11434');

            // For now, use regular request and simulate streaming
            $response = $this->sendChatRequest($message, $model);

            if ($callback) {
                $callback($response['content']);
            }

            return $response;

        } catch (\Exception $e) {
            Log::error('OllamaChatService: Error in streaming chat request', [
                'error' => $e->getMessage(),
                'message' => $message,
                'model' => $model
            ]);

            throw $e;
        }
    }

    /**
     * Validate connection for chat system
     */
    public function validateConnection(): bool
    {
        $baseUrl = config('chat.providers.ollama.base_url', 'http://localhost:11434');
        return $this->checkServerConnection($baseUrl);
    }

    /**
     * Test the connection with a simple request
     */
    public function testConnection(): array
    {
        try {
            $testMessage = "Hello, please respond with 'Connection successful' to confirm you are working.";
            $response = $this->sendChatRequest($testMessage);

            return [
                'success' => true,
                'response' => $response['content'],
                'processing_time' => $response['processing_time']
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get provider status for chat system
     */
    public function getStatus(): array
    {
        $baseUrl = config('chat.providers.ollama.base_url', 'http://localhost:11434');
        $isConnected = $this->checkServerConnection($baseUrl);
        $models = $isConnected ? $this->getAvailableModelsArray($baseUrl) : [];

        return [
            'connected' => $isConnected,
            'base_url' => $baseUrl,
            'has_api_key' => false, // Ollama doesn't use API keys
            'model_count' => count($models),
            'models' => $models,
            'last_checked' => now()->toISOString()
        ];
    }

    /**
     * Check if model exists in available models
     */
    public function hasModel(string $model): bool
    {
        $baseUrl = config('chat.providers.ollama.base_url', 'http://localhost:11434');
        return $this->checkModelAvailability($baseUrl, $model);
    }

    /**
     * Get model information (limited for Ollama)
     */
    public function getModelInfo(string $model): ?array
    {
        try {
            $baseUrl = config('chat.providers.ollama.base_url', 'http://localhost:11434');
            $response = Http::timeout(10)->get("{$baseUrl}/api/show", [
                'name' => $model
            ]);

            if (!$response->successful()) {
                return null;
            }

            return $response->json();

        } catch (\Exception $e) {
            Log::error('OllamaChatService: Error getting model info', [
                'error' => $e->getMessage(),
                'model' => $model
            ]);
            return null;
        }
    }

    /**
     * Ultra-lightweight language detection for PRR optimization
     */
    private function detectLanguageUltraLight(string $message): bool
    {
        $message = strtolower($message);

        // Ultra-simple detection: check for just 3 most common Indonesian business terms
        return (strpos($message, 'tampilkan') !== false ||
               strpos($message, 'perusahaan') !== false ||
               strpos($message, 'berapa') !== false);
    }

    /**
     * Send optimized chat request with PRR parameters
     */
    public function sendOptimizedChatRequest(string $message, string $model = null, array $optimizedParams = []): array
    {
        try {
            $model = $model ?: config('chat.providers.ollama.default_model', 'llama2');
            $baseUrl = config('chat.providers.ollama.base_url', 'http://localhost:11434');
            $startTime = microtime(true);

            // Use optimized parameters from PRR analysis
            $temperature = $optimizedParams['temperature'] ?? 0.7;
            $maxTokens = $optimizedParams['max_tokens'] ?? 500;
            $timeout = $optimizedParams['timeout'] ?? 30;

            // Ultra-lightweight language detection
            $isIndonesian = $this->detectLanguageUltraLight($message);

            Log::info("OllamaChatService: Sending PRR-optimized request", [
                'model' => $model,
                'optimized_temp' => $temperature,
                'optimized_tokens' => $maxTokens,
                'optimized_timeout' => $timeout,
                'is_indonesian' => $isIndonesian,
                'message_length' => strlen($message)
            ]);

            // Build optimized system prompt
            $systemPrompt = "You are a helpful AI assistant for a farm management system. ";
            if ($isIndonesian) {
                $systemPrompt .= "Respond in Indonesian (Bahasa Indonesia). ";
            }
            $systemPrompt .= "Provide accurate, concise responses based on available data.";

            // Combine system prompt with user message
            $fullPrompt = $systemPrompt . "\n\nUser: " . $message;

            // Prepare options with PRR optimizations
            $options = [
                'temperature' => $temperature,
                'num_predict' => $maxTokens,
                'top_k' => 40,
                'top_p' => 0.9,
                'stop' => ['User:', 'Human:', 'Assistant:'],
            ];

            // Send request to Ollama with optimized parameters
            $response = Http::timeout($timeout)
                ->post($baseUrl . '/api/generate', [
                    'model' => $model,
                    'prompt' => $fullPrompt,
                    'stream' => false,
                    'options' => $options
                ]);

            $processingTime = microtime(true) - $startTime;

            if (!$response->successful()) {
                throw new Exception('Ollama API error: ' . $response->body());
            }

            $data = $response->json();

            Log::info("OllamaChatService: PRR-optimized request successful", [
                'processing_time' => $processingTime,
                'response_length' => strlen($data['response'] ?? '')
            ]);

            return [
                'content' => $data['response'] ?? 'No response generated',
                'model' => $model,
                'processing_time' => $processingTime,
                'token_count' => null, // Ollama doesn't provide token count by default
                'provider' => 'ollama',
                'optimized' => true,
                'done' => $data['done'] ?? true
            ];

        } catch (Exception $e) {
            Log::error('OllamaChatService: PRR-optimized request failed', [
                'error' => $e->getMessage(),
                'message' => substr($message, 0, 100),
                'model' => $model
            ]);

            throw new Exception('Failed to get optimized response from Ollama: ' . $e->getMessage());
        }
    }
}

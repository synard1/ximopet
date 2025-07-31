<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Services\OllamaChatService;
use App\Helpers\OllamaChatQueryHelper;
use App\Helpers\OllamaChatContextHelper;

class OllamaChatCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ollama:chat {prompt} {--model=llama2} {--debug} {--stream} {--context=} {--context-type=} {--context-limit=1000} {--query=} {--query-type=} {--cache} {--cache-ttl=3600} {--no-cache}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a prompt to Ollama with database context and store the response with detailed status feedback.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $prompt = $this->argument('prompt');
        $model = $this->option('model');
        $debug = $this->option('debug');
        $stream = $this->option('stream');
        $context = $this->option('context');
        $contextType = $this->option('context-type');
        $contextLimit = (int) $this->option('context-limit');
        $query = $this->option('query');
        $queryType = $this->option('query-type');
        $useCache = $this->option('cache');
        $cacheTtl = (int) $this->option('cache-ttl');
        $noCache = $this->option('no-cache');
        $ollamaUrl = env('OLLAMA_HOST', 'http://172.16.15.6:11434');

        // Log awal proses
        $this->logInfo("🚀 Starting Ollama Chat Command", $debug);
        $this->logInfo("📝 Prompt: {$prompt}", $debug);
        $this->logInfo("🤖 Model: {$model}", $debug);
        $this->logInfo("🌐 Server URL: {$ollamaUrl}", $debug);

        // Initialize service
        $ollamaService = new OllamaChatService();

        // Handle context data with complex queries and caching
        $enhancedPrompt = $this->buildEnhancedPrompt($prompt, $context, $contextType, $contextLimit, $query, $queryType, $useCache, $cacheTtl, $noCache, $debug);

        // Auto-detect natural language queries and convert to complex queries
        if (empty($query) && empty($queryType) && empty($context) && empty($contextType)) {
            $autoQuery = $ollamaService->detectNaturalLanguageQuery($prompt);
            if (!empty($autoQuery)) {
                $this->logInfo("🔍 Auto-detected query: {$autoQuery}", $debug);
                $enhancedPrompt = $this->buildEnhancedPrompt($prompt, $context, $contextType, $contextLimit, $autoQuery, $queryType, $useCache, $cacheTtl, $noCache, $debug);
            }
        }

        // Validasi koneksi server
        $ollamaService = new OllamaChatService();
        if (!$ollamaService->checkServerConnection($ollamaUrl)) {
            $this->error("❌ Cannot connect to Ollama server at {$ollamaUrl}");
            $this->error("💡 Please check if Ollama is running and the URL is correct");
            return 1;
        }

        // Cek model availability
        if (!$ollamaService->checkModelAvailability($ollamaUrl, $model)) {
            $this->error("❌ Model '{$model}' is not available on the server");
            $availableModels = $ollamaService->getAvailableModelsArray($ollamaUrl);
            $suggestedModel = $ollamaService->suggestSimilarModel($model, $availableModels);

            $this->error("💡 Available models: " . implode(', ', $availableModels));

            if ($suggestedModel) {
                $this->warn("💡 Did you mean: --model={$suggestedModel}");
                $this->info("💡 Or try one of these popular models:");
                $this->suggestPopularModels($availableModels);
            }

            return 1;
        }

        try {
            $this->logInfo("📤 Sending request to Ollama...", $debug);

            if ($stream) {
                $this->info("🌊 Streaming mode enabled - Response will appear in real-time");
                $this->handleStreamingRequest($ollamaUrl, $model, $enhancedPrompt, $debug);
            } else {
                $this->info("⏳ Processing request (this may take a while)...");
                $this->handleNonStreamingRequest($ollamaUrl, $model, $enhancedPrompt, $debug);
            }

            return 0;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $this->error("❌ Connection Error: Cannot connect to Ollama server");
            $this->error("🔍 Details: " . $e->getMessage());
            $this->error("💡 Please check if Ollama is running and accessible");

            // Check if it's a timeout
            if (strpos($e->getMessage(), 'timeout') !== false || strpos($e->getMessage(), 'cURL error 28') !== false) {
                $this->error("⏰ Timeout Error: Request took too long (>300 seconds)");
                $this->error("💡 This might be due to:");
                $this->error("   • Model is too large for the server");
                $this->error("   • Server is overloaded");
                $this->error("   • Network issues");
            }
            return 1;
        } catch (\Illuminate\Http\Client\RequestException $e) {
            $this->handleHttpError($e->response, $debug);
            return 1;
        } catch (\Exception $e) {
            $this->error("❌ Unexpected Error: " . $e->getMessage());
            $this->logInfo("🔍 Stack trace: " . $e->getTraceAsString(), $debug);
            return 1;
        }
    }

    /**
     * Handle streaming request to Ollama
     */
    private function handleStreamingRequest(string $url, string $model, string $prompt, bool $debug): void
    {
        $this->logInfo("📡 Making streaming HTTP request to: {$url}/api/generate", $debug);
        $this->logInfo("📋 Request payload: " . json_encode([
            'model' => $model,
            'prompt' => $prompt,
            'stream' => true,
            'options' => [
                'num_thread' => (int) env('OLLAMA_NUM_THREADS', 16),
                // 'temperature' => (float) env('OLLAMA_TEMPERATURE', 0.7),
                // 'top_p' => (float) env('OLLAMA_TOP_P', 0.9),
            ],
        ], JSON_PRETTY_PRINT), $debug);

        $timeout = $debug ? 120 : 300; // Longer timeout for streaming
        $this->logInfo("⏱️  Using timeout: {$timeout} seconds", $debug);

        $startTime = microtime(true);
        $fullResponse = '';
        $tokenCount = 0;

        try {
            // Use cURL for streaming
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => "{$url}/api/generate",
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode([
                    'model' => $model,
                    'prompt' => $prompt,
                    'stream' => true,
                    'options' => [
                        'num_thread' => (int) env('OLLAMA_NUM_THREADS', 16),
                        // 'temperature' => (float) env('OLLAMA_TEMPERATURE', 0.7),
                        // 'top_p' => (float) env('OLLAMA_TOP_P', 0.9),
                    ],
                ]),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Accept: application/json',
                ],
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_CONNECTTIMEOUT => 30,
                CURLOPT_TCP_KEEPALIVE => 1,
                CURLOPT_TCP_KEEPIDLE => 60,
                CURLOPT_TCP_KEEPINTVL => 60,
                CURLOPT_WRITEFUNCTION => function ($ch, $data) use (&$fullResponse, &$tokenCount) {
                    $lines = explode("\n", $data);
                    foreach ($lines as $line) {
                        $line = trim($line);
                        if (empty($line)) continue;

                        // Parse JSON response
                        $jsonData = json_decode($line, true);
                        if ($jsonData && isset($jsonData['response'])) {
                            $token = $jsonData['response'];
                            $fullResponse .= $token;
                            $tokenCount++;

                            // Print token immediately
                            $this->output->write($token);

                            // Check if response is done
                            if (isset($jsonData['done']) && $jsonData['done']) {
                                $this->newLine();
                                $this->newLine();
                                $this->info("✅ Streaming completed!");
                                return strlen($data);
                            }
                        }
                    }
                    return strlen($data);
                },
            ]);

            $this->newLine();
            $this->info("🤖 Ollama Response (Streaming):");
            $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            $endTime = microtime(true);
            $duration = round($endTime - $startTime, 2);

            if ($result === false) {
                $this->error("❌ cURL Error: " . $error);
                if (strpos($error, 'timeout') !== false) {
                    $this->error("⏰ Streaming timeout - Response was too slow");
                    $this->error("💡 Try using a smaller model or shorter prompt");
                } elseif (strpos($error, 'transfer closed') !== false) {
                    $this->warn("⚠️  Connection closed prematurely");
                    $this->info("💡 This is normal for streaming - response may be complete");
                }
                return;
            }

            if ($httpCode !== 200) {
                $this->error("❌ HTTP Error {$httpCode}");
                return;
            }

            $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->newLine();

            // Display statistics
            $this->logInfo("📊 Streaming Statistics:", $debug);
            $this->logInfo("   • Duration: {$duration} seconds", $debug);
            $this->logInfo("   • Tokens received: {$tokenCount}", $debug);
            $this->logInfo("   • Characters: " . strlen($fullResponse), $debug);
            $this->logInfo("   • Words: " . str_word_count($fullResponse), $debug);
            $this->logInfo("   • Lines: " . substr_count($fullResponse, "\n") + 1, $debug);

            // Show completion message
            if ($tokenCount > 0) {
                $this->info("✅ Streaming completed successfully!");
            }
        } catch (\Exception $e) {
            $this->error("❌ Streaming Error: " . $e->getMessage());
        }
    }

    /**
     * Handle non-streaming request to Ollama
     */
    private function handleNonStreamingRequest(string $url, string $model, string $prompt, bool $debug): void
    {
        $this->logInfo("📡 Making HTTP request to: {$url}/api/generate", $debug);
        $this->logInfo("📋 Request payload: " . json_encode([
            'model' => $model,
            'prompt' => $prompt,
            'stream' => false,
            'options' => [
                'num_thread' => (int) env('OLLAMA_NUM_THREADS', 16),
                // 'temperature' => (float) env('OLLAMA_TEMPERATURE', 0.7),
                // 'top_p' => (float) env('OLLAMA_TOP_P', 0.9),
            ],
        ], JSON_PRETTY_PRINT), $debug);

        $timeout = $debug ? 60 : 300;
        $this->logInfo("⏱️  Using timeout: {$timeout} seconds", $debug);

        $startTime = microtime(true);

        $response = Http::timeout($timeout)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])
            ->post("{$url}/api/generate", [
                'model' => $model,
                'prompt' => $prompt,
                'stream' => false,
                'options' => [
                    'num_thread' => (int) env('OLLAMA_NUM_THREADS', 16),
                    // 'temperature' => (float) env('OLLAMA_TEMPERATURE', 0.7),
                    // 'top_p' => (float) env('OLLAMA_TOP_P', 0.9),
                ],
            ]);

        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);

        $this->line("✅ Request completed in {$duration} seconds");
        $this->logInfo("⏱️  Request completed in {$duration} seconds", $debug);

        if (!$response->successful()) {
            $this->handleHttpError($response, $debug);
            return;
        }

        $responseData = $response->json();

        if (!isset($responseData['response'])) {
            $this->error("❌ Invalid response format from Ollama");
            $this->logInfo("📄 Raw response: " . json_encode($responseData, JSON_PRETTY_PRINT), $debug);
            return;
        }

        $ollamaResponse = $responseData['response'];

        // Display response with formatting
        $this->newLine();
        $this->info("🤖 Ollama Response:");
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->line($ollamaResponse);
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        // Display response statistics
        $this->logInfo("📊 Response Statistics:", $debug);
        $this->logInfo("   • Characters: " . strlen($ollamaResponse), $debug);
        $this->logInfo("   • Words: " . str_word_count($ollamaResponse), $debug);
        $this->logInfo("   • Lines: " . substr_count($ollamaResponse, "\n") + 1, $debug);

        $this->info("✅ Command completed successfully!");
    }



    /**
     * Handle HTTP errors
     */
    private function handleHttpError($response, bool $debug): void
    {
        $statusCode = $response->status();
        $this->error("❌ HTTP Error {$statusCode}: " . $response->reasonPhrase());

        $errorMessages = [
            400 => 'Bad Request - Check your prompt format',
            401 => 'Unauthorized - Check authentication',
            404 => 'Not Found - Check API endpoint',
            500 => 'Internal Server Error - Ollama server error',
            502 => 'Bad Gateway - Ollama service unavailable',
            503 => 'Service Unavailable - Ollama is starting up',
        ];

        if (isset($errorMessages[$statusCode])) {
            $this->error("💡 " . $errorMessages[$statusCode]);
        }

        $this->logInfo("📄 Response Body: " . $response->body(), $debug);
    }

    /**
     * Build enhanced prompt with context and query data
     */
    private function buildEnhancedPrompt(string $prompt, ?string $context, ?string $contextType, int $contextLimit, ?string $query, ?string $queryType, bool $useCache, int $cacheTtl, bool $noCache, bool $debug): string
    {
        $ollamaService = new OllamaChatService();
        $contextData = '';
        $queryData = '';

        // Get context data if specified (either context or context-type)
        if (!empty($context) || !empty($contextType)) {
            $contextParam = !empty($context) ? $context : $contextType;
            $this->logInfo("🔍 Fetching context data for: {$contextParam}", $debug);
            $contextData = $this->getContextDataWithCache($context, $contextType, $contextLimit, $useCache, $cacheTtl, $noCache, $debug);
        }

        // Get query data if specified
        if (!empty($query)) {
            $this->logInfo("🔍 Executing complex query: {$query}", $debug);
            $queryData = $this->getComplexQueryData($query, $queryType, $contextLimit, $useCache, $cacheTtl, $noCache, $debug);
        }

        // Combine context and query data
        $combinedData = $this->combineContextAndQueryData($contextData, $queryData, $debug);

        // Format final prompt
        if (!empty($combinedData)) {
            return OllamaChatContextHelper::formatContextPrompt($prompt, $combinedData);
        }

        return $prompt;
    }

    /**
     * Get context data with caching support
     */
    private function getContextDataWithCache(?string $context, ?string $contextType, int $contextLimit, bool $useCache, int $cacheTtl, bool $noCache, bool $debug): string
    {
        $ollamaService = new OllamaChatService();

        // Determine if we should use contextType or context
        $contextToUse = !empty($contextType) ? $contextType : $context;

        // Determine if context is a type or specific data
        $isContextType = !empty($contextType) || in_array(strtolower($contextToUse), ['livestock', 'feed', 'supply', 'recent', 'latest']);

        if ($noCache) {
            $this->logInfo("🚫 Cache disabled, fetching fresh data", $debug);
            if ($isContextType) {
                return OllamaChatContextHelper::getContextDataByType($contextToUse, $contextLimit);
            }
            return OllamaChatContextHelper::getSpecificContextData($contextToUse, $contextLimit);
        }

        if ($useCache) {
            $cacheKey = $ollamaService->generateCacheKey('context', $context, $contextType, $contextLimit);
            $cachedData = $ollamaService->getFromCache($cacheKey);

            if ($cachedData !== null) {
                $this->logInfo("✅ Using cached context data", $debug);
                return $cachedData;
            }

            $this->logInfo("📥 Cache miss, fetching fresh data", $debug);
            if ($isContextType) {
                $data = OllamaChatContextHelper::getContextDataByType($contextToUse, $contextLimit);
            } else {
                $data = OllamaChatContextHelper::getSpecificContextData($contextToUse, $contextLimit);
            }
            $ollamaService->setCache($cacheKey, $data, $cacheTtl);
            return $data;
        }

        if ($isContextType) {
            return OllamaChatContextHelper::getContextDataByType($contextToUse, $contextLimit);
        }
        return OllamaChatContextHelper::getSpecificContextData($contextToUse, $contextLimit);
    }

    /**
     * Get complex query data with caching support
     */
    private function getComplexQueryData(?string $query, ?string $queryType, int $contextLimit, bool $useCache, int $cacheTtl, bool $noCache, bool $debug): string
    {
        $ollamaService = new OllamaChatService();

        if ($noCache) {
            $this->logInfo("🚫 Cache disabled, executing fresh query", $debug);
            return OllamaChatQueryHelper::executeComplexQuery($query, $contextLimit);
        }

        if ($useCache) {
            $cacheKey = $ollamaService->generateCacheKey('query', $query, $queryType, $contextLimit);
            $cachedData = $ollamaService->getFromCache($cacheKey);

            if ($cachedData !== null) {
                $this->logInfo("✅ Using cached query data", $debug);
                return $cachedData;
            }

            $this->logInfo("📥 Cache miss, executing fresh query", $debug);
            $data = OllamaChatQueryHelper::executeComplexQuery($query, $contextLimit);
            $ollamaService->setCache($cacheKey, $data, $cacheTtl);
            return $data;
        }

        return OllamaChatQueryHelper::executeComplexQuery($query, $contextLimit);
    }

    /**
     * Combine context and query data
     */
    private function combineContextAndQueryData(string $contextData, string $queryData, bool $debug): string
    {
        $combined = '';

        if (!empty($contextData)) {
            $combined .= $contextData;
        }

        if (!empty($queryData)) {
            if (!empty($combined)) {
                $combined .= "\n\n";
            }
            $combined .= $queryData;
        }

        return $combined;
    }

    /**
     * Log info message if debug mode is enabled
     */
    private function logInfo(string $message, bool $debug): void
    {
        if ($debug) {
            $this->info($message);
        }
    }

    /**
     * Suggest popular models
     */
    private function suggestPopularModels(array $availableModels): void
    {
        $ollamaService = new OllamaChatService();
        $popularModels = $ollamaService->getPopularModels();

        $suggestions = [];
        foreach ($popularModels as $popular) {
            if (in_array($popular, $availableModels)) {
                $suggestions[] = $popular;
            }
        }

        if (!empty($suggestions)) {
            foreach ($suggestions as $model) {
                $this->line("   • {$model}");
            }
        }
    }
}

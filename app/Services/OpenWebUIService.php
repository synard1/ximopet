<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Services\LanguageDetectionService;
use Exception;

class OpenWebUIService
{
    protected string $baseUrl;
    protected ?string $apiKey;
    protected int $timeout;
    protected int $maxTokens;
    protected float $temperature;
    protected LanguageDetectionService $languageDetectionService;

    public function __construct(LanguageDetectionService $languageDetectionService)
    {
        $this->baseUrl = config('chat.providers.openwebui.base_url');
        $this->apiKey = config('chat.providers.openwebui.api_key');
        $this->timeout = config('chat.providers.openwebui.timeout', 90); // Increased from 30 to 90 seconds
        $this->maxTokens = config('chat.providers.openwebui.max_tokens', 1000);
        $this->temperature = config('chat.providers.openwebui.temperature', 0.7);
        $this->languageDetectionService = $languageDetectionService;
    }

    /**
     * Check if server is accessible
     */
    public function checkServerConnection(): bool
    {
        try {
            Log::info('OpenWebUIService: Testing server connection', [
                'base_url' => $this->baseUrl,
                'timeout' => 10
            ]);

            $headers = $this->buildHeaders();
            Log::info('OpenWebUIService: Request headers for connection test', [
                'has_auth_header' => isset($headers['Authorization']),
                'header_keys' => array_keys($headers),
                'auth_header_preview' => isset($headers['Authorization']) ? substr($headers['Authorization'], 0, 20) . '...' : null
            ]);

            // Set a reasonable timeout to prevent hanging
            $response = Http::withHeaders($headers)
                ->timeout(10)
                ->connectTimeout(10)
                ->get("{$this->baseUrl}/api/models");

            Log::info('OpenWebUIService: Server connection test response', [
                'status' => $response->status(),
                'successful' => $response->successful(),
                'body_preview' => substr($response->body(), 0, 200),
                'response_headers' => array_keys($response->headers())
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('OpenWebUIService: Server connection test failed', [
                'error' => $e->getMessage(),
                'exception' => get_class($e),
                'base_url' => $this->baseUrl,
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Send chat request to OpenWebUI with enhanced retry mechanism
     */
    public function sendChatRequest(string $message, string $model = null, array $context = []): array
    {
        // Check if this is a simple greeting that can be handled directly
        $greetingResponse = $this->handleGreetingMessage($message);
        if ($greetingResponse !== null) {
            return $greetingResponse;
        }

        $maxRetries = 3; // Increased retries
        $baseRetryDelay = 2; // Increased base delay

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $model = $model ?: config('chat.providers.openwebui.default_model');
                $startTime = microtime(true);

                // Detect if this is a simple casual conversation
                $isSimpleCasual = $this->isSimpleCasualMessage($message);

                // Adjust parameters for casual conversations with more aggressive timeouts
                $temperature = $isSimpleCasual ? 0.3 : $this->temperature;
                $maxTokens = $isSimpleCasual ? 100 : $this->maxTokens;

                // Progressive timeout increase per attempt
                $baseTimeout = $isSimpleCasual ? 45 : $this->timeout; // Increased casual timeout
                $timeout = $baseTimeout + ($attempt - 1) * 30; // Add 30s per retry attempt
                $connectTimeout = 20; // Increased connection timeout

                Log::info("OpenWebUIService: Sending request (attempt {$attempt}/{$maxRetries})", [
                    'model' => $model,
                    'is_casual' => $isSimpleCasual,
                    'timeout' => $timeout,
                    'connect_timeout' => $connectTimeout,
                    'message_length' => strlen($message),
                    'base_url' => $this->baseUrl
                ]);

                // Build HTTP client with progressive timeout and enhanced retry
                $httpClient = Http::withHeaders($this->buildHeaders())
                    ->timeout($timeout)
                    ->connectTimeout($connectTimeout)
                    ->retry(2, 2000) // Enhanced internal HTTP retry: 2 retries with 2s delay
                    ->withOptions([
                        'curl' => [
                            CURLOPT_TIMEOUT => $timeout,
                            CURLOPT_CONNECTTIMEOUT => $connectTimeout,
                            CURLOPT_FOLLOWLOCATION => true,
                            CURLOPT_MAXREDIRS => 3,
                            CURLOPT_SSL_VERIFYPEER => false, // For debugging external SSL issues
                            CURLOPT_SSL_VERIFYHOST => false,
                            CURLOPT_USERAGENT => 'Laravel-OpenWebUI-Client/1.0'
                        ]
                    ]);

                $response = $httpClient->post($this->baseUrl . '/api/chat/completions', [
                    'model' => $model,
                    'messages' => $this->formatMessages($message, $context, $isSimpleCasual),
                    'max_tokens' => $maxTokens,
                    'temperature' => $temperature,
                    'stream' => false
                ]);

                $processingTime = microtime(true) - $startTime;

                if (!$response->successful()) {
                    $errorBody = $response->body();
                    $statusCode = $response->status();

                    Log::warning("OpenWebUIService: API error on attempt {$attempt}", [
                        'status' => $statusCode,
                        'error' => $errorBody,
                        'processing_time' => $processingTime
                    ]);

                    // For server errors (5xx), retry. For client errors (4xx), don't retry
                    if ($statusCode >= 500 && $attempt < $maxRetries) {
                        $retryDelay = $baseRetryDelay * pow(2, $attempt - 1); // Exponential backoff
                        Log::info("OpenWebUIService: Retrying after {$retryDelay}s due to server error");
                        sleep($retryDelay);
                        continue;
                    }

                    throw new Exception("OpenWebUI API error (Status: {$statusCode}): " . $errorBody);
                }

                $data = $response->json();

                Log::info("OpenWebUIService: Request successful", [
                    'processing_time' => $processingTime,
                    'attempt' => $attempt,
                    'response_length' => strlen($data['choices'][0]['message']['content'] ?? '')
                ]);

                return [
                    'content' => $data['choices'][0]['message']['content'] ?? 'No response generated',
                    'model' => $model,
                    'processing_time' => $processingTime,
                    'token_count' => $data['usage']['total_tokens'] ?? null,
                    'provider' => 'openwebui',
                    'attempt' => $attempt,
                    'timeout_used' => $timeout
                ];

            } catch (Exception $e) {
                $errorMessage = $e->getMessage();
                $processingTime = microtime(true) - $startTime;

                Log::error("OpenWebUIService: Error on attempt {$attempt}/{$maxRetries}", [
                    'error' => $errorMessage,
                    'message_preview' => substr($message, 0, 100) . '...',
                    'model' => $model ?? 'unknown',
                    'processing_time' => $processingTime,
                    'timeout_used' => $timeout ?? 'unknown',
                    'is_timeout' => strpos($errorMessage, 'timeout') !== false || strpos($errorMessage, 'timed out') !== false
                ]);

                // Check if this is a non-retryable error
                if ($this->isNonRetryableError($errorMessage)) {
                    Log::warning("OpenWebUIService: Non-retryable error detected, not retrying");
                    throw new Exception("Failed to get response from OpenWebUI (non-retryable): " . $errorMessage);
                }

                // If this is the last attempt, throw
                if ($attempt === $maxRetries) {
                    throw new Exception("Failed to get response from OpenWebUI after {$maxRetries} attempts: " . $errorMessage);
                }

                // Calculate retry delay with exponential backoff
                $retryDelay = $baseRetryDelay * pow(2, $attempt - 1); // 2s, 4s, 8s
                Log::info("OpenWebUIService: Retrying in {$retryDelay}s (attempt {$attempt}/{$maxRetries})");
                sleep($retryDelay);
            }
        }

        throw new Exception("Unexpected error in retry loop - this should not happen");
    }

    /**
     * Get available models from OpenWebUI
     */
    public function getAvailableModels(): array
    {
        try {
            $response = Http::withHeaders($this->buildHeaders())
                ->timeout(10)
                ->get($this->baseUrl . '/api/models');

            if (!$response->successful()) {
                Log::warning('OpenWebUIService: Failed to get models', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return [];
            }

            $data = $response->json();

            // Extract model IDs from the response
            if (isset($data['data']) && is_array($data['data'])) {
                return array_column($data['data'], 'id');
            }

            return [];

        } catch (Exception $e) {
            Log::error('OpenWebUIService: Error getting available models', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Send optimized chat request with PRR parameters
     */
    public function sendOptimizedChatRequest(string $message, string $model = null, array $optimizedParams = []): array
    {
        // Check if this is a simple greeting that can be handled directly
        $greetingResponse = $this->handleGreetingMessage($message);
        if ($greetingResponse !== null) {
            return $greetingResponse;
        }

        $model = $model ?: config('chat.providers.openwebui.default_model');
        $startTime = microtime(true);

        // Use optimized parameters from PRR analysis
        $temperature = $optimizedParams['temperature'] ?? $this->temperature;
        $maxTokens = $optimizedParams['max_tokens'] ?? $this->maxTokens;
        $timeout = $optimizedParams['timeout'] ?? $this->timeout;
        $connectTimeout = min(20, $timeout); // Connection timeout should not exceed main timeout

        Log::info("OpenWebUIService: Sending PRR-optimized request", [
            'model' => $model,
            'optimized_temp' => $temperature,
            'optimized_tokens' => $maxTokens,
            'optimized_timeout' => $timeout,
            'connect_timeout' => $connectTimeout,
            'message_length' => strlen($message)
        ]);

        // Add retry mechanism similar to regular method
        $maxRetries = 2; // Reduced from 3 but still has fallback
        $baseRetryDelay = 1;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $httpClient = Http::withHeaders($this->buildHeaders())
                    ->timeout($timeout)
                    ->connectTimeout($connectTimeout)
                    ->retry(1, 1000) // Internal HTTP retry
                    ->withOptions([
                        'curl' => [
                            CURLOPT_TIMEOUT => $timeout,
                            CURLOPT_CONNECTTIMEOUT => $connectTimeout,
                            CURLOPT_FOLLOWLOCATION => true,
                            CURLOPT_MAXREDIRS => 3,
                            CURLOPT_SSL_VERIFYPEER => false,
                            CURLOPT_SSL_VERIFYHOST => false,
                            CURLOPT_USERAGENT => 'Laravel-OpenWebUI-Client/1.0'
                        ]
                    ]);

                $response = $httpClient->post($this->baseUrl . '/api/chat/completions', [
                    'model' => $model,
                    'messages' => $this->formatOptimizedMessages($message),
                    'max_tokens' => $maxTokens,
                    'temperature' => $temperature,
                    'stream' => false
                ]);

                $processingTime = microtime(true) - $startTime;

                if (!$response->successful()) {
                    $errorBody = $response->body();
                    $statusCode = $response->status();

                    Log::warning("OpenWebUIService: PRR-optimized API error on attempt {$attempt}", [
                        'status' => $statusCode,
                        'error' => $errorBody,
                        'processing_time' => $processingTime
                    ]);

                    // For server errors (5xx), retry. For client errors (4xx), don't retry
                    if ($statusCode >= 500 && $attempt < $maxRetries) {
                        $retryDelay = $baseRetryDelay * pow(2, $attempt - 1); // Exponential backoff
                        Log::info("OpenWebUIService: PRR retrying after {$retryDelay}s due to server error");
                        sleep($retryDelay);
                        continue;
                    }

                    throw new Exception("OpenWebUI API error (Status: {$statusCode}): " . $errorBody);
                }

                $data = $response->json();

                Log::info("OpenWebUIService: PRR-optimized request successful", [
                    'processing_time' => $processingTime,
                    'attempt' => $attempt,
                    'response_length' => strlen($data['choices'][0]['message']['content'] ?? '')
                ]);

                return [
                    'content' => $data['choices'][0]['message']['content'] ?? 'No response generated',
                    'model' => $model,
                    'processing_time' => $processingTime,
                    'token_count' => $data['usage']['total_tokens'] ?? null,
                    'provider' => 'openwebui',
                    'optimized' => true
                ];

            } catch (Exception $e) {
                $processingTime = microtime(true) - $startTime;
                $errorMessage = $e->getMessage();

                Log::warning("OpenWebUIService: PRR-optimized request attempt {$attempt} failed", [
                    'error' => $errorMessage,
                    'processing_time' => $processingTime
                ]);

                // Check if error is retryable
                if ($this->isNonRetryableError($errorMessage) || $attempt >= $maxRetries) {
                    Log::error("OpenWebUIService: PRR-optimized request failed (final)", [
                        'error' => $errorMessage,
                        'model' => $model,
                        'processing_time' => $processingTime,
                        'attempts' => $attempt
                    ]);

                    throw $e;
                }

                // Retry with exponential backoff
                $retryDelay = $baseRetryDelay * pow(2, $attempt - 1);
                Log::info("OpenWebUIService: PRR retrying after {$retryDelay}s (attempt {$attempt}/{$maxRetries})");
                sleep($retryDelay);
            }
        }

        // This should never be reached
        throw new Exception('PRR-optimized request failed after all retry attempts');
    }

    /**
     * Format optimized messages for PRR requests
     */
    private function formatOptimizedMessages(string $message): array
    {
        // Enhanced language detection
        $isIndonesian = $this->languageDetectionService->isIndonesian($message);

        $systemMessage = "You are an AI assistant for XiMoPet, a livestock management system specifically designed for poultry farming operations. ";

        // Add language instruction
        if ($isIndonesian) {
            $systemMessage .= "RESPOND IN INDONESIAN LANGUAGE (Bahasa Indonesia). ";
        } else {
            $systemMessage .= "RESPOND IN ENGLISH LANGUAGE. ";
        }

        // Add livestock management scope
        $systemMessage .= "SCOPE LIMITATION: You can ONLY provide guidance about livestock management, poultry farming, feed management, farm operations, and related agricultural topics. ";
        $systemMessage .= "If users ask about topics outside of livestock/farm management (like Linux servers, IT systems, general technology), ";
        $systemMessage .= "you MUST redirect them to ask about farm-related topics instead. ";

        // Add critical instructions
        $systemMessage .= "CRITICAL INSTRUCTIONS: ";
        $systemMessage .= "1. NEVER show your thinking process or internal reasoning. ";
        $systemMessage .= "2. BANNED PHRASES: 'Let me think', 'I should', 'Okay, the user', etc. ";
        $systemMessage .= "3. Be direct, professional, and factual - start answering immediately.";

        return [
            [
                'role' => 'system',
                'content' => $systemMessage
            ],
            [
                'role' => 'user',
                'content' => $message
            ]
        ];
    }

    /**
     * Ultra-lightweight language hint generation
     */
    private function getLanguageHint(string $message): string
    {
        $message = strtolower($message);

        // Ultra-simple detection: check for just 3 most common Indonesian business terms
        $hasIndonesian = (strpos($message, 'tampilkan') !== false ||
                         strpos($message, 'perusahaan') !== false ||
                         strpos($message, 'berapa') !== false);

        return $hasIndonesian ? "Respond in Indonesian (Bahasa Indonesia). " : "";
    }

    /**
     * Validate connection to OpenWebUI
     */
    public function validateConnection(): bool
    {
        try {
            Log::info('OpenWebUIService: Starting connection validation', [
                'base_url' => $this->baseUrl,
                'has_api_key' => !empty($this->apiKey),
                'timeout' => 10
            ]);

            $headers = $this->buildHeaders();
            Log::info('OpenWebUIService: Request headers', [
                'headers' => array_keys($headers)
            ]);

            $response = Http::withHeaders($headers)
                ->timeout(10)
                ->get($this->baseUrl . '/api/models');

            Log::info('OpenWebUIService: Connection validation response', [
                'status' => $response->status(),
                'successful' => $response->successful(),
                'response_headers' => $response->headers(),
                'body_size' => strlen($response->body()),
                'body_preview' => substr($response->body(), 0, 200)
            ]);

            return $response->successful();

        } catch (\Exception $e) {
            Log::error('OpenWebUIService: Connection validation failed', [
                'error' => $e->getMessage(),
                'exception' => get_class($e),
                'base_url' => $this->baseUrl,
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        } catch (\Throwable $t) {
            Log::error('OpenWebUIService: Connection validation failed with Throwable', [
                'error' => $t->getMessage(),
                'exception' => get_class($t),
                'base_url' => $this->baseUrl,
                'trace' => $t->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Send streaming chat request (for future enhancement)
     */
    public function streamChatRequest(string $message, string $model = null, callable $callback = null): array
    {
        try {
            $model = $model ?: config('chat.providers.openwebui.default_model');

            // For now, we'll use the regular request and simulate streaming
            // In a future version, this can be enhanced for true streaming
            $response = $this->sendChatRequest($message, $model);

            if ($callback) {
                $callback($response['content']);
            }

            return $response;

        } catch (Exception $e) {
            Log::error('OpenWebUIService: Error in streaming chat request', [
                'error' => $e->getMessage(),
                'message' => $message,
                'model' => $model
            ]);

            throw $e;
        }
    }

    /**
     * Check if a specific model is available
     */
    public function hasModel(string $model): bool
    {
        $availableModels = $this->getAvailableModels();
        return in_array($model, $availableModels);
    }

    /**
     * Get model information
     */
    public function getModelInfo(string $model): ?array
    {
        try {
            $response = Http::withHeaders($this->buildHeaders())
                ->timeout(10)
                ->get($this->baseUrl . "/api/models/{$model}");

            if (!$response->successful()) {
                return null;
            }

            return $response->json();

        } catch (Exception $e) {
            Log::error('OpenWebUIService: Error getting model info', [
                'error' => $e->getMessage(),
                'model' => $model
            ]);
            return null;
        }
    }

    /**
     * Test the API with a simple request
     */
    public function testConnection(): array
    {
        try {
            $testMessage = "Hello, please respond with 'Connection successful' to confirm the API is working.";
            $response = $this->sendChatRequest($testMessage);

            return [
                'success' => true,
                'response' => $response['content'],
                'processing_time' => $response['processing_time']
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Build HTTP headers for requests
     */
    protected function buildHeaders(): array
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];

        if ($this->apiKey) {
            $headers['Authorization'] = 'Bearer ' . $this->apiKey;
        }

        return $headers;
    }

    /**
     * Format messages for OpenWebUI API with strict anti-speculation instructions
     */
    protected function formatMessages(string $message, array $context = [], bool $isSimpleCasual = false): array
    {
        $messages = [];

        // Add system message based on conversation type with ULTRA STRICT instructions
        if ($isSimpleCasual) {
            $messages[] = [
                'role' => 'system',
                'content' => 'You are a friendly AI assistant for XiMoPet livestock management system. '
                    . 'Keep responses concise and friendly while maintaining focus on farm management topics. '
                    . 'CRITICAL: Never show your thinking process, never use phrases like "Let me", "I need to", "I should", "Okay", "the user", etc. '
                    . 'If asked about non-farm topics, politely redirect to livestock management questions. '
                    . 'Just provide direct helpful responses about farm operations.'
            ];
        } elseif (!empty($context)) {
            $systemMessage = "You are an AI assistant for XiMoPet, a livestock management system specifically designed for poultry farming operations. ";

            // Detect user language and add language instruction
            $isIndonesian = $this->languageDetectionService->isIndonesian($message);
            if ($isIndonesian) {
                $systemMessage .= "RESPOND IN INDONESIAN LANGUAGE (Bahasa Indonesia). ";
            } else {
                $systemMessage .= "RESPOND IN ENGLISH LANGUAGE. ";
            }

            // CRITICAL: Enforce livestock management scope
            $systemMessage .= "SCOPE LIMITATION: You can ONLY provide guidance about livestock management, poultry farming, feed management, farm operations, and related agricultural topics. ";
            $systemMessage .= "If users ask about topics outside of livestock/farm management (like Linux servers, IT systems, general technology), ";
            $systemMessage .= "you MUST redirect them to ask about farm-related topics instead. ";

            $systemMessage .= "ULTRA CRITICAL INSTRUCTIONS - FAILURE TO FOLLOW THESE WILL CAUSE SYSTEM ERRORS: ";
            $systemMessage .= "1. ABSOLUTELY NEVER show your thinking process, internal reasoning, or meta-commentary. ";
            $systemMessage .= "2. BANNED PHRASES: 'Let me think', 'I should', 'the user said', 'Okay, the user', 'I need to', 'Wait, let me', 'First, I', 'But I', 'Hmm', 'I think', 'I will', 'I am going to', 'I am going to think', 'I am going to need to think', 'I am going to need to think about', 'I am going to need to think about this', 'I am going to need to think about this for a while', 'I am going to need to think about this for a while before I can answer', 'I am going to need to think about this for a while before I can answer you', 'I am going to need to think about this for a while before I can answer you";
            $systemMessage .= "3. NEVER make assumptions about data you don't have access to. ";
            $systemMessage .= "4. Only use the provided context data to answer questions accurately. ";
            $systemMessage .= "5. If you cannot answer based on the context, provide general guidance or suggest contacting an administrator. ";
            $systemMessage .= "6. Be direct, professional, and factual in all responses - start answering immediately. ";
            $systemMessage .= "7. NEVER explain what the user is asking or rephrase their question. ";
            $systemMessage .= "8. Provide information directly without preambles or explanations of your process.";

            $messages[] = [
                'role' => 'system',
                'content' => $systemMessage
            ];
        } else {
            // Add livestock management system context for all queries
            $systemMessage = "You are an AI assistant for XiMoPet, a livestock management system specifically designed for poultry farming operations. ";

            // Detect user language and add language instruction
            $isIndonesian = $this->languageDetectionService->isIndonesian($message);
            if ($isIndonesian) {
                $systemMessage .= "RESPOND IN INDONESIAN LANGUAGE (Bahasa Indonesia). ";
            } else {
                $systemMessage .= "RESPOND IN ENGLISH LANGUAGE. ";
            }

            // CRITICAL: Enforce livestock management scope
            $systemMessage .= "SCOPE LIMITATION: You can ONLY provide guidance about livestock management, poultry farming, feed management, farm operations, and related agricultural topics. ";
            $systemMessage .= "If users ask about topics outside of livestock/farm management (like Linux servers, IT systems, general technology), ";
            $systemMessage .= "you MUST redirect them to ask about farm-related topics instead. ";

            $systemMessage .= "ULTRA CRITICAL INSTRUCTIONS - FAILURE TO FOLLOW THESE WILL CAUSE SYSTEM ERRORS: ";
            $systemMessage .= "1. ABSOLUTELY NEVER show your thinking process, internal reasoning, or meta-commentary. ";
            $systemMessage .= "2. BANNED PHRASES: 'Let me think', 'I should', 'the user said', 'Okay, the user', 'I need to', 'Wait, let me', 'First, I', 'But I', 'Hmm', '<think>', 'Let me consider', 'I think', 'Let me analyze'. ";
            $systemMessage .= "3. NEVER make assumptions about data you don't have access to. ";
            $systemMessage .= "4. Only use the provided context data to answer questions accurately. ";
            $systemMessage .= "5. Be direct, professional, and factual in all responses - start answering immediately. ";
            $systemMessage .= "6. NEVER explain what the user is asking or rephrase their question. ";
            $systemMessage .= "7. Provide information directly without preambles or explanations of your process.";

            $messages[] = [
                'role' => 'system',
                'content' => $systemMessage
            ];
        }

        // Add user message
        $messages[] = [
            'role' => 'user',
            'content' => $message
        ];

        return $messages;
    }

    /**
     * Format request payload for OpenWebUI
     */
    protected function formatRequest(string $message, array $context = []): array
    {
        return [
            'messages' => $this->formatMessages($message, $context),
            'max_tokens' => $this->maxTokens,
            'temperature' => $this->temperature,
            'stream' => false,
            'user' => Auth::id() ?? 'anonymous'
        ];
    }

    /**
     * Handle API errors
     */
    protected function handleApiError($response): string
    {
        $body = $response->json();

        if (isset($body['error']['message'])) {
            return $body['error']['message'];
        }

        if (isset($body['message'])) {
            return $body['message'];
        }

        return 'Unknown API error occurred';
    }

    /**
     * Get provider status
     */
    public function getStatus(): array
    {
        $isConnected = $this->validateConnection();
        $models = $isConnected ? $this->getAvailableModels() : [];

        return [
            'connected' => $isConnected,
            'base_url' => $this->baseUrl,
            'has_api_key' => !empty($this->apiKey),
            'model_count' => count($models),
            'models' => $models,
            'last_checked' => now()->toISOString()
        ];
    }

    /**
     * Check if a message is a greeting
     */
    public function isGreetingMessage(string $message): bool
    {
        return $this->handleGreetingMessage($message) !== null;
    }

    /**
     * Handle greeting messages directly without sending to LLM
     */
    private function handleGreetingMessage(string $message): ?array
    {
        $message = trim($message);
        $lowerMessage = strtolower($message);

        // Check if this is a data query that should NOT be treated as greeting
        $dataQueryPatterns = [
            'tampilkan', 'show', 'lihat', 'view', 'daftar', 'list', 'data', 'informasi', 'info',
            'berapa', 'how many', 'jumlah', 'total', 'statistik', 'laporan', 'report',
            'perusahaan', 'company', 'farm', 'ternak', 'kandang', 'coop', 'pakan', 'feed'
        ];
        
        foreach ($dataQueryPatterns as $pattern) {
            if (strpos($lowerMessage, $pattern) !== false) {
                Log::debug('OpenWebUIService: Skipping greeting detection for data query', [
                    'message' => $message,
                    'detected_pattern' => $pattern
                ]);
                return null; // Not a greeting, should go to LLM
            }
        }

        // Define greeting patterns and responses
        $greetings = [
            // English greetings - only exact matches or at start of message
            'hello' => 'Hello! How can I assist you with your livestock management today?',
            'hi' => 'Hi there! How can I help you with your poultry farming operations?',
            'hey' => 'Hey! What can I do for you regarding livestock management?',
            'good morning' => 'Good morning! How can I assist with your farm operations today?',
            'good afternoon' => 'Good afternoon! How can I help with your livestock management?',
            'good evening' => 'Good evening! What would you like to know about poultry farming?',

            // Indonesian greetings
            'halo' => 'Halo! Bagaimana saya bisa membantu Anda dengan manajemen ternak hari ini?',
            'hai' => 'Hai! Apa yang bisa saya bantu terkait operasi peternakan Anda?',
            'selamat pagi' => 'Selamat pagi! Bagaimana saya bisa membantu operasi pertanian Anda hari ini?',
            'selamat siang' => 'Selamat siang! Apa yang bisa saya bantu dengan manajemen ternak Anda?',
            'selamat sore' => 'Selamat sore! Apa yang ingin Anda ketahui tentang peternakan unggas?',

            // Simple responses
            'thanks' => 'You\'re welcome! Let me know if you need any further assistance with livestock management.',
            'thank you' => 'You\'re welcome! Feel free to ask if you have more questions about poultry farming.',
            'terima kasih' => 'Sama-sama! Beri tahu saya jika Anda memerlukan bantuan lebih lanjut dengan manajemen ternak.',
            'makasih' => 'Sama-sama! Jangan ragu untuk bertanya jika Anda memiliki pertanyaan lain tentang peternakan.',

            // Farewells
            'bye' => 'Goodbye! Feel free to return if you have more questions about livestock management.',
            'goodbye' => 'Goodbye! I\'m here whenever you need assistance with poultry farming operations.',
            'see you' => 'See you later! Don\'t hesitate to come back with more livestock management questions.',
            'sampai jumpa' => 'Sampai jumpa! Saya ada di sini kapan pun Anda membutuhkan bantuan dengan operasi peternakan.',
            'dadah' => 'Dadah! Kembali lagi jika Anda memiliki pertanyaan lebih lanjut tentang manajemen ternak.',
        ];

        // Check for exact matches first
        if (isset($greetings[$lowerMessage])) {
            $metadata = [
                'bypass_llm' => true,
                'greeting_type' => 'exact_match',
                'greeting_pattern' => $lowerMessage
            ];

            Log::info('OpenWebUIService: Detected greeting message', [
                'message' => $message,
                'match_type' => 'exact',
                'pattern' => $lowerMessage
            ]);

            return [
                'content' => $greetings[$lowerMessage],
                'model' => config('chat.providers.openwebui.default_model'),
                'processing_time' => 0.001, // Simulated processing time
                'token_count' => null,
                'provider' => 'openwebui',
                'attempt' => 1,
                'timeout_used' => 0,
                'bypass_llm' => true, // Indicate that LLM was bypassed
                'metadata' => $metadata
            ];
        }

        // Check for partial matches
        foreach ($greetings as $pattern => $response) {
            if (strpos($lowerMessage, $pattern) !== false) {
                // Make sure it's not part of a larger word
                $patternPos = strpos($lowerMessage, $pattern);
                $beforeChar = $patternPos > 0 ? $lowerMessage[$patternPos - 1] : ' ';
                $afterChar = $patternPos + strlen($pattern) < strlen($lowerMessage) ?
                            $lowerMessage[$patternPos + strlen($pattern)] : ' ';

                // Check if surrounded by spaces or punctuation
                if (ctype_space($beforeChar) || ctype_punct($beforeChar) ||
                    ctype_space($afterChar) || ctype_punct($afterChar)) {

                    $metadata = [
                        'bypass_llm' => true,
                        'greeting_type' => 'partial_match',
                        'greeting_pattern' => $pattern
                    ];

                    Log::info('OpenWebUIService: Detected greeting message', [
                        'message' => $message,
                        'match_type' => 'partial',
                        'pattern' => $pattern
                    ]);

                    return [
                        'content' => $response,
                        'model' => config('chat.providers.openwebui.default_model'),
                        'processing_time' => 0.001,
                        'token_count' => null,
                        'provider' => 'openwebui',
                        'attempt' => 1,
                        'timeout_used' => 0,
                        'bypass_llm' => true,
                        'metadata' => $metadata
                    ];
                }
            }
        }

        // No greeting match found
        return null;
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
     * Check if error is retryable - Enhanced Detection
     */
    private function isNonRetryableError(string $errorMessage): bool
    {
        $nonRetryablePatterns = [
            // Authentication and authorization errors (don't retry)
            '/unauthorized/i',
            '/forbidden/i',
            '/invalid.*api.*key/i',
            '/authentication.*failed/i',
            '/access.*denied/i',
            '/insufficient.*permissions/i',

            // Model and request format errors (don't retry)
            '/model.*not.*found/i',
            '/invalid.*model/i',
            '/bad.*request/i',
            '/malformed.*request/i',
            '/invalid.*json/i',
            '/syntax.*error/i',
            '/validation.*failed/i',

            // Rate limiting (don't retry immediately)
            '/rate.*limit.*exceeded/i',
            '/too.*many.*requests/i',
            '/quota.*exceeded/i',

            // 4xx client errors (except 408 Request Timeout)
            '/400.*bad.*request/i',
            '/401.*unauthorized/i',
            '/403.*forbidden/i',
            '/404.*not.*found/i',
            '/405.*method.*not.*allowed/i',
            '/406.*not.*acceptable/i',
            '/409.*conflict/i',
            '/410.*gone/i',
            '/422.*unprocessable.*entity/i',
            '/429.*too.*many.*requests/i'
        ];

        foreach ($nonRetryablePatterns as $pattern) {
            if (preg_match($pattern, $errorMessage)) {
                return true; // Don't retry these errors
            }
        }

        // Check for specific cURL errors that shouldn't be retried
        $nonRetryableCurlErrors = [
            'curl error 3',  // URL malformed
            'curl error 5',  // Proxy resolution failed
            'curl error 1',  // Unsupported protocol
            'curl error 2',  // Failed to initialize
            'curl error 4',  // Feature not supported
        ];

        $errorLower = strtolower($errorMessage);
        foreach ($nonRetryableCurlErrors as $curlError) {
            if (strpos($errorLower, $curlError) !== false) {
                return true;
            }
        }

        return false; // Retry timeouts, connection errors, server errors
    }


}

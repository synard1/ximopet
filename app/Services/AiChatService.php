<?php

namespace App\Services;

use App\Models\ChatSession;
use App\Models\ChatMessage;
use App\Models\AiProvider;
use AppServicesOpenWebUIService;
use AppServicesOllamaChatService;
use App\Services\ChatContextService;
use App\Services\AiDatabaseServiceRefactored;
use App\Services\AiPlanningService;
use App\Services\LanguageDetectionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;

class AiChatService
{
    protected $openWebUIService;
    protected $ollamaChatService;
    protected $contextService;
    protected $databaseService;
    protected $planningService;
    protected $languageDetectionService;

    public function __construct(
        OpenWebUIService $openWebUIService,
        OllamaChatService $ollamaChatService,
        ChatContextService $contextService,
        // AiDatabaseService $databaseService,
        AiDatabaseServiceRefactored $databaseService,
        AiPlanningService $planningService,
        LanguageDetectionService $languageDetectionService
    ) {
        $this->openWebUIService = $openWebUIService;
        $this->ollamaChatService = $ollamaChatService;
        $this->contextService = $contextService;
        $this->databaseService = $databaseService;
        $this->planningService = $planningService;
        $this->languageDetectionService = $languageDetectionService;
    }

    /**
     * Send a message and get AI response
     */
    public function sendMessage(
        string $message,
        string $sessionId = null,
        string $provider = null,
        string $model = null,
        array $contextFilters = [],
        string $contextType = 'general',
        bool $isRegenerate = false
    ): array {
        try {
            $startTime = microtime(true);

            // Get or create session
            $session = $sessionId ? $this->getSession($sessionId) : $this->createSession($provider, $model);

            if (!$session) {
                throw new Exception('Unable to create or retrieve chat session');
            }

            // Check if user message is a greeting
            $isUserGreeting = $this->openWebUIService->isGreetingMessage($message);
            $userMessageMetadata = $isUserGreeting ? ['bypass_llm' => true] : [];

            // Save user message (only if not regenerating)
            $userMessage = null;
            if (!$isRegenerate) {
                $userMessage = $this->saveMessage($session->id, Auth::id(), 'user', $message, $userMessageMetadata);
            } else {
                // For regeneration, find the last user message
                $userMessage = $session->messages()
                    ->where('message_type', 'user')
                    ->latest()
                    ->first();
            }

            // Enhanced context building with real database access
            $context = $this->contextService->buildSecureContext($message, Auth::user(), $contextType, $contextFilters);

            // Fallback to old method if new method not available
            if (empty($context)) {
                $context = $this->contextService->buildContext($contextType, $contextFilters, $session->company_id);
            }

            // OPTIMIZATION: Use Planning-Reasoning-Response (PRR) methodology for better results
            $usePRR = config('chat.features.use_prr', true); // Enable PRR by default

            // Store final context that will be sent to LLM
            $finalContextSentToLLM = $context;
            
            if ($usePRR) {
                // Parse context for PRR analysis
                $parsedContext = [
                    'user_id' => Auth::id(),
                    'company_id' => $session->company_id,
                    'session_id' => $session->id
                ];
                
                if (!empty($context)) {
                    // Try to parse JSON context if it's a string
                    if (is_string($context)) {
                        // Extract JSON data from context string
                        if (preg_match('/Current System Data:\s*({.*})/s', $context, $matches)) {
                            $jsonData = json_decode($matches[1], true);
                            if (json_last_error() === JSON_ERROR_NONE) {
                                // Merge the extracted data into parsed context
                                $parsedContext = array_merge($parsedContext, $jsonData);
                            }
                        }
                    } else {
                        $parsedContext = array_merge($parsedContext, $context);
                    }
                }
                
                // Get comprehensive data directly for PRR analysis
                try {
                    // Get company data
                    $companyData = $this->databaseService->getCompanyData(Auth::user());
                    if (!empty($companyData['companies'])) {
                        $parsedContext['companies'] = $companyData['companies'];
                    }
                    
                    // Get farm/coop data using searchData method to ensure all relevant data is included
                    $farmData = $this->databaseService->searchData($message, []);
                    if (!empty($farmData)) {
                        // Merge farm data into parsed context
                        $parsedContext = array_merge($parsedContext, $farmData);
                        
                        Log::info('AiChatService: Farm/coop data added to PRR context', [
                            'user_id' => Auth::id(),
                            'farm_data_keys' => array_keys($farmData),
                            'has_farms' => isset($farmData['farms']),
                            'farm_count' => isset($farmData['farms']['total_farms']) ? $farmData['farms']['total_farms'] : 0
                        ]);
                    }
                } catch (Exception $e) {
                    Log::warning('AiChatService: Failed to get data for PRR', [
                        'error' => $e->getMessage(),
                        'user_id' => Auth::id()
                    ]);
                }
                
                // Validate context before PRR execution (prevention measure)
                $contextValidation = \App\Services\PrrContextValidator::validateContext(
                    $parsedContext, 
                    'data_company', // Default to company for now, could be dynamic
                    $message
                );
                
                if (!$contextValidation['is_valid']) {
                    Log::warning('AiChatService: PRR context validation failed', [
                        'errors' => $contextValidation['errors'],
                        'session_id' => $session->id
                    ]);
                }
                
                // Execute PRR methodology for optimized processing
                $prrResult = $this->planningService->executePRR($message, $parsedContext, [
                    'session_id' => $session->id,
                    'provider' => $session->ai_provider,
                    'model' => $session->model_name
                ]);

                if ($prrResult['success']) {
                    // Use PRR-optimized processing
                    $aiResponseData = $this->processPRROptimizedRequest($message, $context, $session, $prrResult);
                    $aiResponse = $aiResponseData['response'];
                    $finalContextSentToLLM = $aiResponseData['final_context'] ?? $context;
                } else {
                    // Fallback to traditional processing
                    Log::info('AiChatService: PRR failed, using traditional processing', [
                        'error' => $prrResult['error'] ?? 'unknown',
                        'session_id' => $session->id
                    ]);
                    $aiResponseData = $this->processTraditionalRequest($message, $context, $session);
                    $aiResponse = $aiResponseData['response'];
                    $finalContextSentToLLM = $aiResponseData['final_context'] ?? $context;
                }
            } else {
                // Traditional processing when PRR is disabled
                $aiResponseData = $this->processTraditionalRequest($message, $context, $session);
                $aiResponse = $aiResponseData['response'];
                $finalContextSentToLLM = $aiResponseData['final_context'] ?? $context;
            }

            // Post-process response to remove any thinking processes that slipped through
            $aiResponse['content'] = $this->cleanResponseContent($aiResponse['content']);

            $processingTime = microtime(true) - $startTime;

            // Prepare metadata for saving the message
            $messageMetadata = [
                'provider' => $session->ai_provider,
                'model' => $session->model_name,
                'context_type' => $contextType,
                'context_data_size' => strlen($finalContextSentToLLM),
                'context_sent_to_llm' => $finalContextSentToLLM, // Add full context for debugging
                'user_query' => $message // Add original user query for tracking
            ];

            // Check if this is a greeting response (bypassed LLM)
            $isGreetingResponse = false;
            if (isset($aiResponse['bypass_llm']) && $aiResponse['bypass_llm'] === true) {
                $isGreetingResponse = true;
                // Include greeting metadata
                if (isset($aiResponse['metadata'])) {
                    $messageMetadata = array_merge($messageMetadata, $aiResponse['metadata']);
                }
            }

            // Save AI response
            $assistantMessage = $this->saveMessage(
                $session->id,
                Auth::id(),
                'assistant',
                $aiResponse['content'],
                $messageMetadata,
                $processingTime,
                $aiResponse['token_count'] ?? null
            );

            // Validate response quality and log any issues
            $this->validateResponseQuality($aiResponse['content'], $message, $session);

            // Update session activity
            $session->updateActivity();

            // Update session title if first message
            if ($session->messages()->count() === 2) { // user + assistant message
                $session->update([
                    'title' => $session->generateTitle($message)
                ]);
            }

            return [
                'success' => true,
                'session_id' => $session->id,
                'user_message' => $userMessage,
                'assistant_message' => $assistantMessage,
                'processing_time' => $processingTime,
                'context_type' => $contextType
            ];

        } catch (Exception $e) {
            Log::error('AiChatService: Error sending message', [
                'error' => $e->getMessage(),
                'message' => $message,
                'session_id' => $sessionId,
                'provider' => $provider
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Create a new chat session
     */
    public function createSession(string $provider = null, string $model = null): ?ChatSession
    {
        try {
            $user = Auth::user();
            $provider = $provider ?: config('chat.system.default_provider', 'openwebui');
            $model = $model ?: config("chat.providers.{$provider}.default_model");

            // Check session limits
            $query = ChatSession::where('user_id', $user->id);

            // Handle null company_id case
            if ($user->company_id) {
                $query->where('company_id', $user->company_id);
            } else {
                $query->whereNull('company_id');
            }

            $userSessionCount = $query->active()->count();

            if ($userSessionCount >= config('chat.system.max_sessions_per_user', 10)) {
                // Delete oldest inactive session
                $oldestQuery = ChatSession::where('user_id', $user->id)
                    ->where('is_active', false);

                if ($user->company_id) {
                    $oldestQuery->where('company_id', $user->company_id);
                } else {
                    $oldestQuery->whereNull('company_id');
                }

                $oldestSession = $oldestQuery->oldest('last_activity_at')->first();

                if ($oldestSession) {
                    $oldestSession->delete();
                }
            }

            $session = ChatSession::create([
                'id' => Str::uuid(),
                'user_id' => $user->id,
                'company_id' => $user->company_id, // This can be null
                'ai_provider' => $provider,
                'model_name' => $model,
                'title' => config('chat.sessions.default_title', 'New Chat Session'),
                'is_active' => true,
                'last_activity_at' => Carbon::now()
            ]);

            return $session;

        } catch (Exception $e) {
            Log::error('AiChatService: Error creating session', [
                'error' => $e->getMessage(),
                'provider' => $provider,
                'model' => $model
            ]);
            return null;
        }
    }

    /**
     * Get existing session
     */
    public function getSession(string $sessionId): ?ChatSession
    {
        $user = Auth::user();
        $query = ChatSession::where('id', $sessionId)
            ->where('user_id', Auth::id());

        // Handle null company_id case
        if ($user->company_id) {
            $query->where('company_id', $user->company_id);
        } else {
            $query->whereNull('company_id');
        }

        return $query->first();
    }

    /**
     * Get session history with messages
     */
    public function getSessionHistory(string $sessionId, int $limit = 50): array
    {
        try {
            $session = $this->getSession($sessionId);

            if (!$session) {
                return [
                    'success' => false,
                    'error' => 'Session not found'
                ];
            }

            // Get messages in chronological order (oldest first)
            $messages = $session->messages()
                ->orderBy('created_at', 'asc')
                ->take($limit)
                ->get()
                ->map(function ($message) {
                    // Ensure is_greeting is properly included in the array
                    $data = $message->toArray();
                    // Force is_greeting to be a boolean value (not 0/1)
                    $data['is_greeting'] = $message->is_greeting ? true : false;
                    return $data;
                })
                ->toArray(); // Convert Collection to array

            return [
                'success' => true,
                'session' => $session,
                'messages' => $messages
            ];

        } catch (Exception $e) {
            Log::error('AiChatService: Error getting session history', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get user's chat sessions
     */
    public function getUserSessions(int $limit = 20): array
    {
        try {
            $user = Auth::user();

            $query = ChatSession::where('user_id', $user->id);

            // Handle null company_id case
            if ($user->company_id) {
                $query->where('company_id', $user->company_id);
            } else {
                $query->whereNull('company_id');
            }

            $sessions = $query->with('latestMessage')
                ->withCount('messages')
                ->latest('last_activity_at')
                ->take($limit)
                ->get();

            return [
                'success' => true,
                'sessions' => $sessions
            ];

        } catch (Exception $e) {
            Log::error('AiChatService: Error getting user sessions', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Switch provider for a session
     */
    public function switchProvider(string $sessionId, string $provider, string $model = null): array
    {
        try {
            $session = $this->getSession($sessionId);

            if (!$session) {
                return [
                    'success' => false,
                    'error' => 'Session not found'
                ];
            }

            $model = $model ?: config("chat.providers.{$provider}.default_model");

            $session->update([
                'ai_provider' => $provider,
                'model_name' => $model
            ]);

            // Add system message about provider switch
            $this->saveMessage(
                $session->id,
                Auth::id(),
                'system',
                "Switched to {$provider} ({$model})"
            );

            return [
                'success' => true,
                'session' => $session
            ];

        } catch (Exception $e) {
            Log::error('AiChatService: Error switching provider', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId,
                'provider' => $provider
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Update session model
     */
    public function updateSessionModel(string $sessionId, string $model): array
    {
        try {
            $session = $this->getSession($sessionId);

            if (!$session) {
                return [
                    'success' => false,
                    'error' => 'Session not found'
                ];
            }

            $previousModel = $session->model_name;
            $session->update([
                'model_name' => $model,
                'last_activity_at' => Carbon::now()
            ]);

            Log::info('AiChatService: Session model updated', [
                'session_id' => $sessionId,
                'previous_model' => $previousModel,
                'new_model' => $model,
                'user_id' => Auth::id()
            ]);

            return [
                'success' => true,
                'message' => 'Session model updated successfully',
                'previous_model' => $previousModel,
                'new_model' => $model
            ];

        } catch (Exception $e) {
            Log::error('AiChatService: Error updating session model', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId,
                'model' => $model
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Delete a chat session
     */
    public function deleteSession(string $sessionId): array
    {
        try {
            $session = $this->getSession($sessionId);

            if (!$session) {
                return [
                    'success' => false,
                    'error' => 'Session not found'
                ];
            }

            $session->delete();

            return [
                'success' => true,
                'message' => 'Session deleted successfully'
            ];

        } catch (Exception $e) {
            Log::error('AiChatService: Error deleting session', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Clear all sessions for current user
     */
    public function clearAllUserSessions(): array
    {
        try {
            $user = Auth::user();

            $query = ChatSession::where('user_id', $user->id);

            // Handle null company_id case
            if ($user->company_id) {
                $query->where('company_id', $user->company_id);
            } else {
                $query->whereNull('company_id');
            }

            $deletedCount = $query->count();
            $query->delete();

            Log::info('AiChatService: Cleared all user sessions', [
                'user_id' => $user->id,
                'company_id' => $user->company_id,
                'deleted_count' => $deletedCount
            ]);

            return [
                'success' => true,
                'deleted_count' => $deletedCount,
                'message' => 'All sessions cleared successfully'
            ];

        } catch (Exception $e) {
            Log::error('AiChatService: Error clearing all user sessions', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get available AI providers
     */
    public function getAvailableProviders(): array
    {
        try {
            $providers = [];

            // Check Ollama
            if (config('chat.providers.ollama.enabled')) {
                $providers['ollama'] = [
                    'name' => 'Ollama',
                    'type' => 'ollama',
                    'base_url' => config('chat.providers.ollama.base_url'),
                    'available' => $this->ollamaChatService->checkServerConnection(
                        config('chat.providers.ollama.base_url')
                    ),
                    'models' => $this->ollamaChatService->getAvailableModelsArray(
                        config('chat.providers.ollama.base_url')
                    )
                ];
            }

            // Check OpenWebUI
            if (config('chat.providers.openwebui.enabled')) {
                $providers['openwebui'] = [
                    'name' => 'OpenWebUI',
                    'type' => 'openwebui',
                    'base_url' => config('chat.providers.openwebui.base_url'),
                    'available' => $this->openWebUIService->validateConnection(),
                    'models' => $this->openWebUIService->getAvailableModels()
                ];
            }

            return [
                'success' => true,
                'providers' => $providers
            ];

        } catch (Exception $e) {
            Log::error('AiChatService: Error getting available providers', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Save a chat message
     */
    protected function saveMessage(
        string $sessionId,
        string $userId,
        string $messageType,
        string $content,
        array $metadata = [],
        float $processingTime = null,
        int $tokenCount = null
    ): ChatMessage {
        // Check if this is a greeting message (bypassed LLM)
        $isGreeting = false;

        // Check direct bypass_llm flag
        if (isset($metadata['bypass_llm']) && $metadata['bypass_llm'] === true) {
            $isGreeting = true;

            // Log greeting detection for debugging
            Log::info('AiChatService: Saving greeting message', [
                'message_type' => $messageType,
                'greeting_type' => $metadata['greeting_type'] ?? 'unknown',
                'pattern' => $metadata['greeting_pattern'] ?? 'unknown'
            ]);
        }

        // For backward compatibility, also check metadata
        if (isset($metadata['metadata']) && isset($metadata['metadata']['bypass_llm']) && $metadata['metadata']['bypass_llm'] === true) {
            $isGreeting = true;
        }

        // Remove bypass_llm from metadata as it's now stored in a dedicated column
        unset($metadata['bypass_llm']);
        if (isset($metadata['metadata'])) {
            unset($metadata['metadata']['bypass_llm']);
        }

        // Create the message with the greeting flag
        $message = ChatMessage::create([
            'id' => Str::uuid(),
            'chat_session_id' => $sessionId,
            'user_id' => $userId,
            'message_type' => $messageType,
            'is_greeting' => $isGreeting,
            'content' => $content,
            'metadata' => $metadata,
            'processing_time' => $processingTime,
            'token_count' => $tokenCount
        ]);

        // Additional log for verification
        if ($isGreeting) {
            Log::info('AiChatService: Greeting message saved', [
                'message_id' => $message->id,
                'is_greeting' => $message->is_greeting,
                'message_type' => $message->message_type
            ]);
        }

        return $message;
    }

    /**
     * Build prompt with context data - Enhanced with Access Validation
     */
    protected function buildPromptWithContext(string $message, string $context, ChatSession $session): string
    {
        $isDebug = config('app.debug') && app()->environment(['local', 'development']);

        // For simple casual conversations, return message as-is to avoid verbose AI thinking
        if ($this->isSimpleCasualConversation($message)) {
            if ($isDebug) {
                Log::debug('AiChatService: Building casual conversation prompt', [
                    'message' => $message,
                    'session_id' => $session->id
                ]);
            }

            // Still add basic farm management context for casual conversations
            $casualPrompt = "You are a friendly AI assistant for XiMoPet livestock management system. ";
            $casualPrompt .= "Respond naturally but remember you're helping with farm management. ";

            return $casualPrompt . "\n\nUser: " . $message;
        }

        // Check if this is a data-related query that requires database access
        if ($this->isDataRelatedQuery($message)) {
            // Validate user has access to requested data
            $user = Auth::user();
            $hasValidAccess = $this->validateDatabaseAccess($user, $message);

            if ($isDebug) {
                Log::debug('AiChatService: Data query access validation result', [
                    'has_access' => $hasValidAccess['has_access'],
                    'reason' => $hasValidAccess['reason'] ?? null,
                    'allowed_types' => $hasValidAccess['allowed_types'] ?? [],
                    'message' => $message,
                    'context_length' => strlen($context)
                ]);
            }

            if (!$hasValidAccess['has_access']) {
                // Return direct access denial instead of letting AI speculate
                if ($isDebug) {
                    Log::debug('AiChatService: Building access denial prompt', [
                        'reason' => $hasValidAccess['reason'],
                        'suggestions' => $hasValidAccess['suggestions'] ?? []
                    ]);
                }
                return $this->buildAccessDenialPrompt($message, $hasValidAccess['reason'], $hasValidAccess['suggestions']);
            }

            // CRITICAL FIX: Validate if context actually contains relevant data
            // This prevents inconsistency between "no data" statements and actual data display
            $hasActualData = $this->validateContextHasData($context, $message);

            if (!$hasActualData) {
                if ($isDebug) {
                    Log::debug('AiChatService: No relevant data found in context', [
                        'context_length' => strlen($context),
                        'message' => $message,
                        'context_preview' => substr($context, 0, 100)
                    ]);
                }
                return $this->buildNoDataAvailablePrompt($message);
            }
        }

        // For general conversations, return message as-is for natural flow ONLY if it's farm-related
        if (empty($context) || strlen($context) < 50) {
            if ($isDebug) {
                Log::debug('AiChatService: Building general conversation prompt', [
                    'context_length' => strlen($context)
                ]);
            }

            // Always add farm management context even for general conversations
            $farmPrompt = "You are an AI assistant for XiMoPet livestock management system. ";
            $farmPrompt .= "Focus only on livestock management, poultry farming, and farm operations. ";
            $farmPrompt .= "If users ask about non-farm topics, politely redirect them to farm-related questions. ";

            return $farmPrompt . "\n\nUser: " . $message;
        }

        // Analyze if the message needs context based on keywords
        $needsContext = $this->messageNeedsContext($message);

        if (!$needsContext) {
            if ($isDebug) {
                Log::debug('AiChatService: Message does not need context', ['message' => $message]);
            }
            return $message; // Natural conversation without forced context
        }

        // Build natural prompt with relevant context only
        $prompt = $message;

        // Add context when specifically relevant
        if ($this->isDataRelatedQuery($message)) {
            // Detect language for proper response
            $isIndonesian = $this->detectIndonesianLanguage($message);
            $languageInstruction = $isIndonesian ? "RESPOND IN INDONESIAN (Bahasa Indonesia). " : "RESPOND IN ENGLISH. ";

            $prompt = "SYSTEM INSTRUCTION: You are an AI assistant for XiMoPet, a livestock management system for poultry farming. " . $languageInstruction;
            $prompt .= "SCOPE: Only provide information about livestock management, poultry farming, feed management, and farm operations. ";
            $prompt .= "If asked about non-farm topics, politely redirect them to farm-related questions. ";
            $prompt .= "ULTRA CRITICAL - FAILURE TO FOLLOW WILL CAUSE SYSTEM ERRORS: ";
            $prompt .= "1. ABSOLUTELY NEVER show your thinking process, internal reasoning, or meta-commentary. ";
            $prompt .= "2. BANNED PHRASES: 'Let me think', 'I should', 'the user said', 'Okay, the user', 'I need to', 'Wait, let me', 'First, I', 'But I', 'Hmm', '<think>', 'Let me consider', 'I think', 'Let me analyze', 'Wait, the user', 'Actually, let me', 'But first', 'So the user'. ";
            $prompt .= "3. NEVER explain what the user is asking or rephrase their question - just answer directly. ";
            $prompt .= "4. Do NOT speculate, make assumptions, or provide generic responses. ";
            $prompt .= "5. DATA FILTERING: ONLY show data that DIRECTLY matches the user's request. If user asks about companies, show ONLY company data. If user asks about farms, show ONLY farm data. NEVER mix data types. ";
            $prompt .= "6. ANTI-DUPLICATION: NEVER repeat the same information multiple times. Show each piece of data only once. ";
            $prompt .= "7. NATURAL LANGUAGE: Use conversational, natural language. Avoid technical jargon, system terminology, or administrative details. ";
            $prompt .= "8. HIDE TECHNICAL INFO: NEVER mention access levels, user roles, system permissions, or any technical/administrative information. ";
            $prompt .= "9. CONVERSATIONAL TONE: Write as if you're having a friendly conversation about farm business, not generating a technical report. ";
            $prompt .= "10. SIMPLE FORMATTING: Use simple, clean formatting. Avoid excessive emojis, technical symbols, or complex structures. ";
            $prompt .= "11. CONSISTENCY RULE: If relevant data exists in the context, present it directly. If no relevant data exists, state 'Tidak ada data yang tersedia' (Indonesian) or 'No data available' (English). NEVER mix both statements. NEVER say 'no data' and then show data. ";
            $prompt .= "12. AVOID TECHNICAL COUNTS: NEVER mention 'Total Companies', 'Total Farms', or similar technical summaries unless specifically asked for statistics. Focus on the actual business information. ";
            $prompt .= "13. TERMINOLOGY: Always use 'perusahaan' (not 'kompanyi') when referring to companies in Indonesian. Use proper Indonesian terminology. ";
            $prompt .= "14. Start your response immediately with the factual information in a natural, conversational way.\n\n";
            $prompt .= "Available data:\n" . $context . "\n\n";
            $prompt .= "User question: " . $message . "\n\n";
            $prompt .= "CRITICAL: Analyze the available data first. If it contains relevant information for the query, present it in a clear, structured format. For company queries specifically, if company data exists in the context, ALWAYS show it regardless of other data availability. Only state 'no data available' if absolutely no relevant data exists for the specific query type.";

            if ($isDebug) {
                Log::debug('AiChatService: Built ultra-strict data query prompt', [
                    'prompt_length' => strlen($prompt),
                    'context_length' => strlen($context),
                    'language' => $isIndonesian ? 'indonesian' : 'english',
                    'banned_phrases_count' => 17
                ]);
            }
        } else {
            // For general business queries, add livestock management context with proper scope limitation
            $prompt .= "\n\n[System Context: You are assisting with XiMoPet livestock management system. " . $this->summarizeContext($context) . "]";
            $prompt .= "\n\nScope: Focus on livestock management, poultry farming, feed management, and farm operations only. ";
            $prompt .= "If the user asks about non-farm topics (like general IT, servers, etc.), politely redirect them to farm-related questions.";

            if ($isDebug) {
                Log::debug('AiChatService: Built business query prompt', [
                    'prompt_length' => strlen($prompt)
                ]);
            }
        }

        return $prompt;
    }

    /**
     * Check if message needs context based on content analysis - Enhanced Logic
     */
    private function messageNeedsContext(string $message): bool
    {
        $message = strtolower(trim($message));

        // Skip context for casual greetings and general conversation
        $casualPatterns = [
            '/^(hi|hello|hai|halo|selamat)\b/',
            '/^(terima kasih|thank you|thanks)\b/',
            '/^(bye|goodbye|sampai jumpa)\b/',
            '/^(bagaimana kabar|how are you)\b/'
        ];

        foreach ($casualPatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                return false; // No context needed for casual conversation
            }
        }

        // Definitely needs context for data-related queries - EXPANDED PATTERNS
        $dataPatterns = [
            '/\b(berapa|how many|jumlah|total|count|sum)\b/',
            '/\b(buatkan|tampilkan|show|list|daftar).*\b(perusahaan|company|ternak|livestock|kandang|farm|pakan|feed)\b/',
            '/\b(keuangan|financial|profit|untung|rugi|pendapatan|biaya|expense|revenue|income)\b/',
            '/\b(laporan|report|analisis|analysis|statistik|statistic|data)\b/',
            '/\b(stok|stock|pakan|feed|supply|inventory|gudang|warehouse)\b/',
            '/\b(kandang|farm|lokasi|location|fasilitas|facility|coop|pen)\b/',
            '/\b(ternak|livestock|ayam|chicken|bebek|duck|kambing|goat|sapi|cattle)\b/',
            '/\b(batch|periode|period|musim|season|siklus|cycle)\b/',
            '/\b(mortality|kematian|mati|mortalitas|depletion)\b/',
            '/\b(penjualan|sales|selling|jual|revenue|pendapatan)\b/',
            '/\b(pembelian|purchase|purchasing|beli|expense|pengeluaran)\b/',
            '/\b(konsumsi|consumption|usage|pemakaian|feed.*usage)\b/'
        ];

        foreach ($dataPatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                return true; // Definitely needs data context
            }
        }

        // Check for question patterns that might need data
        $questionPatterns = [
            '/\b(apa|what|dimana|where|kapan|when|siapa|who|mengapa|why|bagaimana|how)\b.*\b(ternak|livestock|perusahaan|company|kandang|farm|pakan|feed|keuangan|financial)\b/'
        ];

        foreach ($questionPatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                return true; // Question about farm-related topics needs context
            }
        }

        // Check message length and complexity - More liberal for business context
        if (strlen($message) > 50 && str_word_count($message) > 8) {
            // Business-related complex queries likely need context
            return true;
        }

        // Default to no context for simple general conversation
        return false;
    }

    /**
     * Validate if context actually contains relevant data for the query
     * This prevents inconsistency between "no data" statements and actual data display
     */
    private function validateContextHasData(string $context, string $message): bool
    {
        // If context is empty or too short, no data available
        if (empty($context) || strlen(trim($context)) < 20) {
            return false;
        }

        // Clean and normalize context for analysis
        $cleanContext = strtolower(trim($context));
        $cleanMessage = strtolower(trim($message));

        // Check for company-related queries
        if (preg_match('/\b(perusahaan|company|daftar.*perusahaan|list.*company)\b/', $cleanMessage)) {
            // Look for actual company data indicators
            $companyIndicators = [
                'company_name', 'nama_perusahaan', 'company_id',
                'status', 'active', 'inactive', 'created_at',
                'system template', 'template', 'farm_name',
                'total companies:', 'companies:', 'company details:',
                'registered:', 'created:', 'company information',
                '"companies"', '"total_companies"', 'system template'
            ];

            foreach ($companyIndicators as $indicator) {
                if (strpos($cleanContext, strtolower($indicator)) !== false) {
                    return true;
                }
            }

            // Check for company count indicators
            if (preg_match('/total companies?:\s*[1-9]\d*/', $cleanContext) ||
                preg_match('/companies?:\s*\d+/', $cleanContext) ||
                preg_match('/\b[1-9]\d*\s+compan(y|ies)/', $cleanContext)) {
                return true;
            }

            // Check for JSON-like structure indicating actual data
            if (preg_match('/\{.*\}|\[.*\]/', $context) &&
                (strpos($cleanContext, 'id') !== false || strpos($cleanContext, 'name') !== false)) {
                return true;
            }

            // Check for bullet points or structured company data
            if (preg_match('/•\s*\w+|\-\s*\w+.*company|company.*\-/', $cleanContext)) {
                return true;
            }

            // NEW: Check for the specific company data that we know exists from the logs
            // The logs show "System Template" company data is present
            if (strpos($cleanContext, 'system template') !== false) {
                return true;
            }

            return false;
        }

        // Check for livestock/farm-related queries
        if (preg_match('/\b(ternak|livestock|kandang|farm|ayam|chicken)\b/', $cleanMessage)) {
            $livestockIndicators = [
                'livestock_id', 'farm_id', 'batch_id', 'coop_id',
                'population', 'mortality', 'feed_consumption',
                'chicken', 'duck', 'cattle', 'goat'
            ];

            foreach ($livestockIndicators as $indicator) {
                if (strpos($cleanContext, strtolower($indicator)) !== false) {
                    return true;
                }
            }
        }

        // Check for financial data queries
        if (preg_match('/\b(keuangan|financial|profit|revenue|expense|biaya)\b/', $cleanMessage)) {
            $financialIndicators = [
                'amount', 'price', 'cost', 'revenue', 'profit',
                'expense', 'total', 'balance', 'transaction'
            ];

            foreach ($financialIndicators as $indicator) {
                if (strpos($cleanContext, strtolower($indicator)) !== false) {
                    return true;
                }
            }
        }

        // Generic data structure check
        // If context contains structured data (JSON, arrays, or database-like content)
        if (preg_match('/\{.*\}|\[.*\]/', $context) ||
            (substr_count($context, ':') > 2 && substr_count($context, ',') > 1)) {
            return true;
        }

        // If context contains meaningful data (not just error messages or empty responses)
        $meaninglessPatterns = [
            'no data', 'tidak ada data', 'empty', 'null', 'undefined',
            'error', 'failed', 'gagal', 'kosong'
        ];

        foreach ($meaninglessPatterns as $pattern) {
            if (strpos($cleanContext, $pattern) !== false) {
                return false;
            }
        }

        // If we reach here and context has substantial content, assume it has data
        return strlen(trim($context)) > 50;
    }

    /**
     * Validate database access for user with enhanced logging
     */
    private function validateDatabaseAccess($user, string $message): array
    {
        try {
            $isDebug = config('app.debug') && app()->environment(['local', 'development']);

            if ($isDebug) {
                Log::debug('AiChatService: Starting database access validation', [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'user_roles' => $user->getRoleNames()->toArray(),
                    'company_id' => $user->company_id,
                    'message' => $message,
                    'message_length' => strlen($message)
                ]);
            }

            // Get permission checker service
            $permissionChecker = app(\App\Services\PermissionChecker::class);

            // Determine what data types are being requested
            $requestedDataTypes = $this->extractDataTypesFromQuery($message);

            if ($isDebug) {
                Log::debug('AiChatService: Extracted data types from query', [
                    'requested_data_types' => $requestedDataTypes,
                    'query' => $message
                ]);
            }

            if (empty($requestedDataTypes)) {
                if ($isDebug) {
                    Log::debug('AiChatService: No specific data types requested, allowing access');
                }
                return ['has_access' => true, 'reason' => '', 'suggestions' => []];
            }

            // Check if user has access to any of the requested data types
            $allowedTypes = $permissionChecker->getAllowedDataTypes($user, $requestedDataTypes);

            if ($isDebug) {
                Log::debug('AiChatService: Permission check results', [
                    'requested_types' => $requestedDataTypes,
                    'allowed_types' => $allowedTypes,
                    'access_granted' => !empty($allowedTypes)
                ]);
            }

            if (empty($allowedTypes)) {
                $userSummary = $permissionChecker->getUserAccessSummary($user);

                if ($isDebug) {
                    Log::debug('AiChatService: Access denied - generating denial response', [
                        'user_summary' => $userSummary,
                        'requested_types' => $requestedDataTypes
                    ]);
                }

                return [
                    'has_access' => false,
                    'reason' => 'Insufficient permissions to access requested data',
                    'requested_types' => $requestedDataTypes,
                    'user_roles' => $userSummary['roles'],
                    'access_level' => $userSummary['access_level'],
                    'suggestions' => $this->getAccessSuggestions($requestedDataTypes, $userSummary)
                ];
            }

            if ($isDebug) {
                Log::debug('AiChatService: Access validation successful', [
                    'allowed_types' => $allowedTypes
                ]);
            }

            return ['has_access' => true, 'allowed_types' => $allowedTypes];

        } catch (Exception $e) {
            Log::error('AiChatService: Error validating database access', [
                'error' => $e->getMessage(),
                'message' => $message,
                'user_id' => $user->id ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'has_access' => false,
                'reason' => 'Unable to validate data access permissions',
                'suggestions' => ['Please contact your administrator for assistance.']
            ];
        }
    }

    /**
     * Extract data types being requested from the query
     */
    private function extractDataTypesFromQuery(string $message): array
    {
        $message = strtolower($message);
        $dataTypes = [];

        // Company data patterns
        if (preg_match('/\b(perusahaan|company|semua.*perusahaan|all.*compan)\b/i', $message)) {
            $dataTypes[] = 'company_list';
        }

        // Livestock data patterns
        if (preg_match('/\b(ternak|livestock|ayam|chicken|bebek|duck|sapi|cattle)\b/i', $message)) {
            $dataTypes[] = 'livestock_data';
        }

        // Financial data patterns
        if (preg_match('/\b(keuangan|financial|untung|profit|rugi|loss|biaya|cost|pendapatan|revenue)\b/i', $message)) {
            $dataTypes[] = 'financial_data';
        }

        // Farm data patterns
        if (preg_match('/\b(kandang|farm|lokasi|location|facility|fasilitas)\b/i', $message)) {
            $dataTypes[] = 'farm_data';
        }

        // Supply/inventory patterns
        if (preg_match('/\b(pakan|feed|supply|suplai|inventory|stok|gudang)\b/i', $message)) {
            $dataTypes[] = 'supply_data';
        }

        return array_unique($dataTypes);
    }

    /**
     * Build access denial prompt that prevents AI speculation
     */
    private function buildAccessDenialPrompt(string $originalMessage, string $reason, array $suggestions): string
    {
        $prompt = "SYSTEM INSTRUCTION: The user has requested data that they don't have permission to access. ";
        $prompt .= "Do NOT speculate or provide generic responses. Instead, provide this exact response:\n\n";

        $prompt .= "❌ **Access Denied**\n\n";
        $prompt .= "I cannot provide the requested data because: {$reason}\n\n";

        if (!empty($suggestions)) {
            $prompt .= "**Suggestions:**\n";
            foreach ($suggestions as $suggestion) {
                $prompt .= "• {$suggestion}\n";
            }
            $prompt .= "\n";
        }

        $prompt .= "Please contact your administrator if you need access to this information, or ask about data you have permission to view.";

        return $prompt;
    }

    /**
     * Build no data available prompt
     */
    private function buildNoDataAvailablePrompt(string $originalMessage): string
    {
        $prompt = "SYSTEM INSTRUCTION: The user is asking for specific data, but no data is currently available in the system. ";
        $prompt .= "Do NOT make assumptions or provide speculative responses. Instead, provide this exact response:\n\n";

        $prompt .= "⚠️ **No Data Available**\n\n";
        $prompt .= "I cannot find the specific data you requested in the system. This could be because:\n\n";
        $prompt .= "• The data hasn't been entered yet\n";
        $prompt .= "• You don't have access to this information\n";
        $prompt .= "• The system is currently experiencing technical issues\n\n";
        $prompt .= "Please check with your administrator or try again later.";

        return $prompt;
    }

    /**
     * Get access suggestions based on requested data types and user roles
     */
    private function getAccessSuggestions(array $requestedTypes, array $userSummary): array
    {
        $suggestions = [];

        if (in_array('company_list', $requestedTypes)) {
            if (empty($userSummary['roles']) || !in_array('superadmin', $userSummary['roles'])) {
                $suggestions[] = 'Company data access requires SuperAdmin or Company Admin role';
                $suggestions[] = 'You can view your own company information in Company Settings';
            }
        }

        if (in_array('financial_data', $requestedTypes)) {
            $suggestions[] = 'Financial data access requires Farm Manager role or higher';
            $suggestions[] = 'Contact your administrator to request financial reporting permissions';
        }

        if (in_array('livestock_data', $requestedTypes)) {
            $suggestions[] = 'Try asking about livestock data for your specific farm or company';
        }

        if (empty($suggestions)) {
            $suggestions[] = 'Contact your administrator to request appropriate data access permissions';
            $suggestions[] = 'Check the User Guide for information about available features for your role';
        }

        return $suggestions;
    }

    /**
     * Validate response quality and log any problematic patterns - Enhanced Debug Logging
     */
    private function validateResponseQuality(string $response, string $originalMessage, $session): void
    {
        try {
            $isDebug = config('app.debug') && app()->environment(['local', 'development']);
            $issues = [];

            if ($isDebug) {
                Log::debug('AiChatService: Starting response quality validation', [
                    'user_id' => Auth::id(),
                    'session_id' => $session->id,
                    'provider' => $session->ai_provider,
                    'model' => $session->model_name,
                    'original_message' => $originalMessage,
                    'response_length' => strlen($response),
                    'response_preview' => substr($response, 0, 150) . '...',
                    'validation_timestamp' => now()->toISOString()
                ]);
            }

            // Check for thinking process patterns - EXPANDED LIST
            $thinkingPatterns = [
                'Let me think' => '/let\s+me\s+think/i',
                'I should' => '/I\s+should/i',
                'the user said' => '/the\s+user\s+said/i',
                'Wait, the user' => '/wait,?\s+the\s+user/i',
                'But I need to check' => '/but\s+I\s+need\s+to\s+check/i',
                'Let me consider' => '/let\s+me\s+consider/i',
                'I think' => '/I\s+think/i',
                'Let me analyze' => '/let\s+me\s+analyze/i',
                'Okay, the user' => '/okay,?\s+the\s+user/i',
                'First, I should' => '/first,?\s+I\s+should/i',
                'I need to' => '/I\s+need\s+to/i',
                'Wait, let me' => '/wait,?\s+let\s+me/i',
                'Hmm, let me' => '/hmm,?\s+let\s+me/i',
                '<think>' => '/<think>/i',
                'Actually, let me' => '/actually,?\s+let\s+me/i',
                'But first' => '/but\s+first/i',
                'So the user' => '/so\s+the\s+user/i',
                'Based on what I think' => '/based\s+on\s+what\s+I\s+think/i',
                'Let me see' => '/let\s+me\s+see/i',
                'I can see that' => '/I\s+can\s+see\s+that/i',
                'Looking at this' => '/looking\s+at\s+this/i'
            ];

            foreach ($thinkingPatterns as $pattern => $regex) {
                if (preg_match($regex, $response)) {
                    $issues[] = "Thinking process detected: '{$pattern}'";
                    if ($isDebug) {
                        $matchPosition = stripos($response, $pattern);
                        Log::debug('AiChatService: Thinking pattern detected', [
                            'pattern' => $pattern,
                            'regex' => $regex,
                            'match_position' => $matchPosition,
                            'surrounding_text' => substr($response, max(0, $matchPosition - 50), 100)
                        ]);
                    }
                }
            }

            // Check for speculation patterns
            $speculationPatterns = [
                'Maybe they can' => '/maybe\s+they\s+can/i',
                'Perhaps they' => '/perhaps\s+they/i',
                'I assume' => '/I\s+assume/i',
                'I believe' => '/I\s+believe/i',
                'It\'s likely' => '/it\'?s\s+likely/i',
                'Probably' => '/\bprobably\b/i',
                'I would guess' => '/I\s+would\s+guess/i',
                'It seems like' => '/it\s+seems\s+like/i',
                'Based on what I think' => '/based\s+on\s+what\s+I\s+think/i',
                'I imagine' => '/I\s+imagine/i',
                'Most likely' => '/most\s+likely/i',
                'It appears' => '/it\s+appears/i'
            ];

            foreach ($speculationPatterns as $pattern => $regex) {
                if (preg_match($regex, $response)) {
                    $issues[] = "Speculation detected: '{$pattern}'";
                    if ($isDebug) {
                        Log::debug('AiChatService: Speculation pattern detected', [
                            'pattern' => $pattern,
                            'regex' => $regex
                        ]);
                    }
                }
            }

            // Check for assumption patterns when user asked for specific data
            if ($this->isDataRelatedQuery($originalMessage)) {
                $assumptionPatterns = [
                    'based on typical' => '/based\s+on\s+typical/i',
                    'generally' => '/\bgenerally\b/i',
                    'usually' => '/\busually\b/i',
                    'in most cases' => '/in\s+most\s+cases/i',
                    'typically' => '/\btypically\b/i',
                    'often' => '/\boften\b/i',
                    'common practice' => '/common\s+practice/i',
                    'standard approach' => '/standard\s+approach/i',
                    'normally' => '/\bnormally\b/i',
                    'as expected' => '/as\s+expected/i'
                ];

                foreach ($assumptionPatterns as $pattern => $regex) {
                    if (preg_match($regex, $response)) {
                        $issues[] = "Assumption detected in data query: '{$pattern}'";
                        if ($isDebug) {
                            Log::debug('AiChatService: Assumption pattern detected in data query', [
                                'pattern' => $pattern,
                                'original_message' => $originalMessage
                            ]);
                        }
                    }
                }
            }

            // Enhanced debug logging with comprehensive details
            if ($isDebug) {
                Log::debug('AiChatService: Response quality validation completed', [
                    'user_id' => Auth::id(),
                    'session_id' => $session->id,
                    'provider' => $session->ai_provider,
                    'model' => $session->model_name,
                    'original_message' => $originalMessage,
                    'response_length' => strlen($response),
                    'word_count' => str_word_count($response),
                    'issues_found' => count($issues),
                    'issues' => $issues,
                    'is_data_query' => $this->isDataRelatedQuery($originalMessage),
                    'is_casual' => $this->isSimpleCasualConversation($originalMessage),
                    'thinking_patterns_checked' => count($thinkingPatterns),
                    'speculation_patterns_checked' => count($speculationPatterns),
                    'validation_passed' => empty($issues),
                    'response_first_50_chars' => substr($response, 0, 50),
                    'contains_banned_phrases' => !empty($issues),
                    'environment' => app()->environment(),
                    'debug_enabled' => config('app.debug')
                ]);
            }

            // Log issues if found
            if (!empty($issues)) {
                Log::warning('AiChatService: Problematic response patterns detected', [
                    'user_id' => Auth::id(),
                    'session_id' => $session->id,
                    'provider' => $session->ai_provider,
                    'model' => $session->model_name,
                    'original_message' => $originalMessage,
                    'response_preview' => substr($response, 0, 200) . '...',
                    'issues' => $issues,
                    'response_length' => strlen($response),
                    'total_issues' => count($issues)
                ]);
            } else if ($isDebug) {
                Log::debug('AiChatService: Response quality validation passed - no issues detected');
            }

        } catch (Exception $e) {
            Log::error('AiChatService: Error validating response quality', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Check if query is specifically asking for data insights - Enhanced Detection
     */
    private function isDataRelatedQuery(string $message): bool
    {
        $dataQueries = [
            'show', 'tampilkan', 'berapa', 'how many', 'total', 'jumlah', 'count',
            'list', 'daftar', 'summary', 'ringkasan', 'report', 'laporan',
            'analysis', 'analisis', 'performance', 'performa', 'data',
            'statistik', 'statistic', 'overview', 'gambaran', 'informasi',
            'status', 'kondisi', 'situation', 'keadaan', 'update',
            'ternak', 'livestock', 'ayam', 'chicken', 'pakan', 'feed',
            'kandang', 'farm', 'keuangan', 'financial', 'profit', 'untung',
            'rugi', 'loss', 'pendapatan', 'revenue', 'biaya', 'cost',
            'pengeluaran', 'expense', 'penjualan', 'sales', 'batch',
            'periode', 'period', 'bulan', 'month', 'minggu', 'week'
        ];

        $messageLower = strtolower($message);

        foreach ($dataQueries as $query) {
            if (strpos($messageLower, $query) !== false) {
                return true;
            }
        }

        // Check for question words combined with business terms
        $questionPatterns = [
            '/\b(berapa|how many|what is|apa)\b.*\b(ternak|livestock|pakan|feed|kandang|farm|keuangan|financial)\b/i',
            '/\b(show me|tampilkan|lihat)\b.*\b(data|informasi|laporan|report)\b/i',
            '/\b(status|kondisi)\b.*\b(ternak|livestock|pakan|feed|keuangan|financial)\b/i'
        ];

        foreach ($questionPatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if message is a simple casual conversation that doesn't need thinking mode
     */
    private function isSimpleCasualConversation(string $message): bool
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
     * Summarize context to key insights only
     */
    private function summarizeContext(string $context): string
    {
        // If context is too long, provide a summary instead of full dump
        if (strlen($context) > 1000) {
            return "[User has access to comprehensive farm management data including livestock, feed, supplies, and financial records. AI can provide specific insights when requested.]";
        }

        return $context;
    }

    /**
     * Get AI response from the appropriate provider
     */
    protected function getAiResponse(string $provider, string $model, string $prompt): array
    {
        switch ($provider) {
            case 'ollama':
                return $this->ollamaChatService->sendChatRequest($prompt, $model);

            case 'openwebui':
                return $this->openWebUIService->sendChatRequest($prompt, $model);

            default:
                throw new Exception("Unsupported AI provider: {$provider}");
        }
    }

    /**
     * Get AI response from the appropriate provider with enhanced fallback
     */
    protected function getAiResponseWithFallback(string $provider, string $model, string $prompt): array
    {
        try {
            // Try primary provider first
            Log::info('AiChatService: Attempting primary provider', [
                'provider' => $provider,
                'model' => $model,
                'prompt_length' => strlen($prompt)
            ]);

            $response = $this->getAiResponse($provider, $model, $prompt);

            Log::info('AiChatService: Primary provider succeeded', [
                'provider' => $provider,
                'processing_time' => $response['processing_time'] ?? 'unknown'
            ]);

            return $response;

        } catch (Exception $e) {
            $errorMessage = $e->getMessage();

            Log::warning('AiChatService: Primary provider failed', [
                'primary_provider' => $provider,
                'error' => $errorMessage,
                'should_fallback' => $this->shouldFallback($errorMessage, $provider)
            ]);

            // Check if this is a timeout or connection error that warrants fallback
            if ($this->shouldFallback($errorMessage, $provider)) {
                Log::warning('AiChatService: Attempting fallback due to primary provider failure', [
                    'primary_provider' => $provider,
                    'error_type' => $this->categorizeError($errorMessage)
                ]);

                // Determine fallback provider
                $fallbackProvider = $this->getFallbackProvider($provider);

                if ($fallbackProvider) {
                    try {
                        // Get fallback model
                        $fallbackModel = $this->getFallbackModel($fallbackProvider);

                        Log::info('AiChatService: Starting fallback attempt', [
                            'fallback_provider' => $fallbackProvider,
                            'fallback_model' => $fallbackModel,
                            'original_error' => substr($errorMessage, 0, 200)
                        ]);

                        $response = $this->getAiResponse($fallbackProvider, $fallbackModel, $prompt);

                        // Mark that we used fallback
                        $response['used_fallback'] = true;
                        $response['original_provider'] = $provider;
                        $response['original_model'] = $model;
                        $response['fallback_provider'] = $fallbackProvider;
                        $response['fallback_model'] = $fallbackModel;
                        $response['fallback_reason'] = $this->categorizeError($errorMessage);
                        $response['original_error'] = $errorMessage;

                        Log::info('AiChatService: Fallback successful', [
                            'fallback_provider' => $fallbackProvider,
                            'processing_time' => $response['processing_time'] ?? 'unknown'
                        ]);

                        return $response;

                    } catch (Exception $fallbackError) {
                        Log::error('AiChatService: Fallback also failed', [
                            'fallback_provider' => $fallbackProvider,
                            'fallback_error' => $fallbackError->getMessage(),
                            'original_error' => $errorMessage
                        ]);

                        // If fallback also fails, throw a comprehensive error
                        throw new Exception(
                            "Both primary provider ({$provider}) and fallback ({$fallbackProvider}) failed. "
                            . "Primary error: {$errorMessage}. "
                            . "Fallback error: " . $fallbackError->getMessage()
                        );
                    }
                } else {
                    Log::warning('AiChatService: No fallback provider available', [
                        'primary_provider' => $provider,
                        'error' => $errorMessage
                    ]);
                }
            } else {
                Log::info('AiChatService: Error does not warrant fallback', [
                    'provider' => $provider,
                    'error_type' => $this->categorizeError($errorMessage)
                ]);
            }

            // Re-throw original error if no fallback or fallback not warranted
            throw $e;
        }
    }

    /**
     * Check if error warrants fallback to another provider - Enhanced Detection
     */
    private function shouldFallback(string $errorMessage, string $provider): bool
    {
        // More comprehensive patterns for timeout and connection errors
        $fallbackPatterns = [
            // Timeout patterns
            '/timeout/i',
            '/timed.*out/i',
            '/operation.*timed.*out/i',
            '/connection.*timeout/i',
            '/read.*timeout/i',
            '/response.*timeout/i',

            // Connection patterns
            '/connection.*failed/i',
            '/connection.*refused/i',
            '/connection.*reset/i',
            '/connection.*closed/i',
            '/could.*not.*connect/i',
            '/unable.*to.*connect/i',
            '/failed.*to.*connect/i',

            // Network patterns
            '/network.*error/i',
            '/network.*unreachable/i',
            '/host.*unreachable/i',
            '/no.*route.*to.*host/i',

            // cURL specific patterns
            '/curl.*error.*28/i', // Timeout
            '/curl.*error.*7/i',  // Connection failed
            '/curl.*error.*6/i',  // Host resolution
            '/curl.*error.*35/i', // SSL connection error
            '/curl.*error.*52/i', // Empty response
            '/curl.*error.*56/i', // Network receive error

            // Server error patterns (retryable)
            '/server.*not.*responding/i',
            '/service.*unavailable/i',
            '/502.*bad.*gateway/i',
            '/503.*service.*unavailable/i',
            '/504.*gateway.*timeout/i',
            '/500.*internal.*server.*error/i',

            // DNS and resolution patterns
            '/dns.*resolution.*failed/i',
            '/name.*resolution.*failed/i',
            '/host.*not.*found/i'
        ];

        foreach ($fallbackPatterns as $pattern) {
            if (preg_match($pattern, $errorMessage)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Categorize error for better logging and debugging
     */
    private function categorizeError(string $errorMessage): string
    {
        $errorMessage = strtolower($errorMessage);

        if (strpos($errorMessage, 'timeout') !== false || strpos($errorMessage, 'timed out') !== false) {
            return 'timeout';
        }

        if (strpos($errorMessage, 'connection') !== false) {
            return 'connection';
        }

        if (strpos($errorMessage, 'curl error') !== false) {
            return 'curl_error';
        }

        if (strpos($errorMessage, 'unauthorized') !== false || strpos($errorMessage, 'forbidden') !== false) {
            return 'authentication';
        }

        if (strpos($errorMessage, '50') !== false) {
            return 'server_error';
        }

        if (strpos($errorMessage, '40') !== false) {
            return 'client_error';
        }

        return 'unknown';
    }

    /**
     * Get fallback provider
     */
    private function getFallbackProvider(string $primaryProvider): ?string
    {
        $fallbackMap = [
            'openwebui' => 'ollama',
            'ollama' => 'openwebui'
        ];

        $fallbackProvider = $fallbackMap[$primaryProvider] ?? null;

        // Check if fallback provider is enabled
        if ($fallbackProvider && config("chat.providers.{$fallbackProvider}.enabled", false)) {
            return $fallbackProvider;
        }

        return null;
    }

    /**
     * Get fallback model for provider
     */
    private function getFallbackModel(string $provider): string
    {
        $fallbackModels = [
            'ollama' => config('chat.providers.ollama.default_model', 'llama2'),
            'openwebui' => config('chat.providers.openwebui.default_model', 'qwen3:1.7b')
        ];

        return $fallbackModels[$provider] ?? 'llama2';
    }

    /**
     * Clean up expired sessions
     */
    public function cleanupExpiredSessions(): int
    {
        try {
            $expiredDays = config('chat.sessions.auto_delete_sessions_after_days', 30);
            $cutoffDate = Carbon::now()->subDays($expiredDays);

            $deletedCount = ChatSession::where('last_activity_at', '<', $cutoffDate)
                ->orWhere(function ($query) use ($cutoffDate) {
                    $query->whereNull('last_activity_at')
                        ->where('created_at', '<', $cutoffDate);
                })
                ->delete();

            Log::info('AiChatService: Cleaned up expired sessions', [
                'deleted_count' => $deletedCount,
                'cutoff_date' => $cutoffDate
            ]);

            return $deletedCount;

        } catch (Exception $e) {
            Log::error('AiChatService: Error cleaning up expired sessions', [
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Clean response content to remove thinking processes and problematic patterns
     */
    private function cleanResponseContent(string $content): string
    {
        $isDebug = config('app.debug') && app()->environment(['local', 'development']);
        $originalContent = $content;

        if ($isDebug) {
            Log::debug('AiChatService: Starting response content cleaning', [
                'original_length' => strlen($content),
                'original_preview' => substr($content, 0, 100) . '...'
            ]);
        }

        // Remove thinking tags and their content completely
        $content = preg_replace('/<think>.*?<\/think>/is', '', $content);
        $content = preg_replace('/<thinking>.*?<\/thinking>/is', '', $content);

        // Remove standalone thinking tags
        $content = preg_replace('/<\/?think>/i', '', $content);
        $content = preg_replace('/<\/?thinking>/i', '', $content);

        // Remove entire sentences that contain thinking processes - be very aggressive
        $thinkingSentences = [
            '/[^.]*\bokay,?\s+the\s+user\s+is\s+asking[^.]*/i',
            '/[^.]*\blet\s+me\s+think[^.]*/i',
            '/[^.]*\bi\s+should[^.]*/i',
            '/[^.]*\bfirst,?\s+i[^.]*/i',
            '/[^.]*\bwait,?\s+let\s+me[^.]*/i',
            '/[^.]*\bi\s+need\s+to[^.]*/i',
            '/[^.]*\bbut\s+i[^.]*/i',
            '/[^.]*\bhmm,?[^.]*/i',
            '/[^.]*\bactually,?\s+let\s+me[^.]*/i',
            '/[^.]*\bso\s+the\s+user[^.]*/i',
            '/[^.]*\blet\s+me\s+consider[^.]*/i',
            '/[^.]*\blet\s+me\s+analyze[^.]*/i',
            '/[^.]*\bi\s+think[^.]*/i',
            '/[^.]*\bthe\s+user\s+said[^.]*/i',
            '/[^.]*\bthe\s+user\s+is\s+asking[^.]*/i',
            '/[^.]*\bbased\s+on\s+what\s+i\s+think[^.]*/i'
        ];

        foreach ($thinkingSentences as $pattern) {
            $content = preg_replace($pattern, '', $content);
        }

        // Clean up extra whitespace, dots, and newlines
        $content = preg_replace('/\n\s*\n\s*\n/', "\n\n", $content);
        $content = preg_replace('/\.\s*\.+/', '.', $content); // Remove multiple dots
        $content = preg_replace('/^\s*\.+\s*/', '', $content); // Remove leading dots
        $content = trim($content);

        // If the response is now empty or too short, provide a fallback
        if (strlen(trim($content)) < 10) {
            $content = 'No data found.';

            if ($isDebug) {
                Log::warning('AiChatService: Response became too short after cleaning, using fallback', [
                    'original_content' => $originalContent,
                    'cleaned_content' => $content
                ]);
            }
        }

        if ($isDebug && $content !== $originalContent) {
            Log::info('AiChatService: Response content cleaned', [
                'original_length' => strlen($originalContent),
                'cleaned_length' => strlen($content),
                'removed_characters' => strlen($originalContent) - strlen($content),
                'cleaned_preview' => substr($content, 0, 100) . '...'
            ]);
        }

        return $content;
    }

    /**
     * Process request using PRR (Planning-Reasoning-Response) optimization
     */
    private function processPRROptimizedRequest(string $message, string $context, ChatSession $session, array $prrResult): array
    {
        $planning = $prrResult['planning'];
        $reasoning = $prrResult['reasoning'];
        $response = $prrResult['response'];

        Log::info('AiChatService: Using PRR optimization', [
            'session_id' => $session->id,
            'language' => $planning['language'],
            'query_type' => $planning['query_type'],
            'complexity' => $planning['complexity'],
            'processing_time' => $prrResult['processing_time']
        ]);

        // Get database data if needed based on PRR analysis
        $databaseData = [];
        if ($planning['data_needs']['database_access'] && $reasoning['context_needed']) {
            $databaseData = $this->databaseService->searchData($message, []);
            if (!empty($databaseData)) {
                // REFACTOR NOTE: formatDataForAI() method was removed in AiDatabaseServiceRefactored
                // Current implementation uses direct JSON encoding
                // To rollback to old method, uncomment the line below and comment the JSON encoding
                // $formattedData = $this->databaseService->formatDataForAI($databaseData, $message);
                
                // Convert raw data to JSON format for AI consumption (new approach)
                $formattedData = json_encode($databaseData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                $context .= "\n\nCurrent System Data:\n" . $formattedData;
            }
        }

        // Use optimized prompt from PRR analysis
        $optimizedPrompt = $response['optimized_prompt'];

        // CRITICAL FIX: Don't add context again if optimized prompt already contains data
        // PRR already includes relevant data in optimized prompt for confident_detailed_response
        if ($reasoning['context_needed'] && !empty($context) && $reasoning['response_approach'] !== 'confident_detailed_response') {
            $optimizedPrompt .= "\n\nContext: " . $context;
        }

        // Use optimized model parameters
        $modelParams = $response['model_parameters'];

        // Get AI response with PRR optimizations
        $aiResponse = $this->getAiResponseWithOptimizedParams(
            $session->ai_provider,
            $session->model_name,
            $optimizedPrompt,
            $modelParams
        );

        // Return both response and final context for tracking
        return [
            'response' => $aiResponse,
            'final_context' => $optimizedPrompt
        ];
    }

    /**
     * Process request using traditional method (fallback)
     */
    private function processTraditionalRequest(string $message, string $context, ChatSession $session): array
    {
        // Check if message needs database context - enhanced logic
        $needsContext = $this->messageNeedsContext($message);
        $isSimpleCasual = $this->isSimpleCasualConversation($message);

        // Add real database data if needed and user has access (but not for simple casual conversations)
        $databaseData = [];
        if ($needsContext && !$isSimpleCasual) {
            $databaseData = $this->databaseService->searchData($message, []);
            if (!empty($databaseData)) {
                // REFACTOR NOTE: formatDataForAI() method was removed in AiDatabaseServiceRefactored
                // Current implementation uses direct JSON encoding
                // To rollback to old method, uncomment the line below and comment the JSON encoding
                // $formattedData = $this->databaseService->formatDataForAI($databaseData, $message);
                
                // Convert raw data to JSON format for AI consumption (new approach)
                $formattedData = json_encode($databaseData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                $context .= "\n\nCurrent System Data:\n" . $formattedData;
            }
        }

        // Build enhanced prompt with context (but keep it simple for casual conversations)
        $enhancedPrompt = $this->buildPromptWithContext($message, $context, $session);

        // Get AI response based on provider with fallback
        $aiResponse = $this->getAiResponseWithFallback($session->ai_provider, $session->model_name, $enhancedPrompt);
        
        // Return both response and final context for tracking
        return [
            'response' => $aiResponse,
            'final_context' => $enhancedPrompt
        ];
    }

    /**
     * Get AI response with optimized parameters from PRR analysis
     */
    private function getAiResponseWithOptimizedParams(string $provider, string $model, string $prompt, array $params): array
    {
        try {
            switch ($provider) {
                case 'openwebui':
                    return $this->openWebUIService->sendOptimizedChatRequest($prompt, $model, $params);
                case 'ollama':
                    return $this->ollamaChatService->sendOptimizedChatRequest($prompt, $model, $params);
                default:
                    // Fallback to regular method
                    return $this->getAiResponseWithFallback($provider, $model, $prompt);
            }
        } catch (Exception $e) {
            Log::warning('AiChatService: Optimized request failed, falling back', [
                'provider' => $provider,
                'error' => $e->getMessage()
            ]);

            // Fallback to regular method
            return $this->getAiResponseWithFallback($provider, $model, $prompt);
        }
    }

    /**
     * Detect Indonesian language in user message
     */
    private function detectIndonesianLanguage(string $message): bool
    {
        return $this->languageDetectionService->isIndonesian($message);
    }

    /**
     * Get detected language code for the message
     */
    private function getDetectedLanguage(string $message): string
    {
        return $this->languageDetectionService->getLanguage($message);
    }

    /**
     * Get language detection confidence
     */
    private function getLanguageConfidence(string $message): float
    {
        return $this->languageDetectionService->getConfidence($message);
    }
}

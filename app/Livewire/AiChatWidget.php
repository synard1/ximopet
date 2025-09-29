<?php

namespace App\Livewire;

use App\Models\ChatMessage;
use App\Services\AiChatService;
use App\Services\ChatContextService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\Attributes\On;
use Exception;

class AiChatWidget extends Component
{
    // Component state - explicitly typed properties
    public bool $isOpen = false; // Explicitly false - bubble should show first
    public bool $isMinimized = false;
    public bool $isLoading = false;
    public bool $isTyping = false;
    public bool $showSettings = false;
    public bool $showSessions = false;

    // Chat session data
    public ?string $currentSessionId = null;
    public array $sessions = [];
    public array $messages = [];
    public array $ratedMessages = []; // Track which messages have been rated

    // Form inputs
    public string $newMessage = '';
    public string $selectedProvider = 'ollama';
    public string $selectedModel = '';
    public string $contextType = 'general';
    public array $contextFilters = [];

    // Search functionality
    public string $searchQuery = '';
    public bool $showSearch = false;

    // Export functionality
    public string $exportFormat = 'json';
    public array $exportFormats = [
        'json' => 'JSON',
        'csv' => 'CSV',
        'txt' => 'Plain Text',
        'pdf' => 'PDF'
    ];

    // UI settings
    public string $chatPosition = 'bottom-right';
    public string $chatSize = 'medium';
    public string $theme = 'auto';

    // Available options
    public array $availableProviders = [];
    public array $availableModels = [];
    public array $contextTypes = [];
    public array $chatTemplates = [];
    public bool $showTemplates = false;

    // Error handling
    public ?string $errorMessage = null;
    public ?string $successMessage = null;

    protected AiChatService $chatService;
    protected ChatContextService $contextService;

    protected $rules = [
        'newMessage' => 'required|string|max:5000',
        'selectedProvider' => 'required|string|in:ollama,openwebui',
        'selectedModel' => 'sometimes|string|max:100',
        'contextType' => 'required|string',
    ];

    public function boot(AiChatService $chatService, ChatContextService $contextService)
    {
        $this->chatService = $chatService;
        $this->contextService = $contextService;
    }

    public function mount()
    {
        // Initialize the component with chat closed to show the bubble first
        $this->isOpen = false;
        $this->initializeComponent();
    }

    /**
     * Initialize component with default settings
     */
    private function initializeComponent()
    {
        try {
            // Load UI settings from config
            $this->chatPosition = config('chat.ui.default_position', 'bottom-right');
            $this->chatSize = 'medium'; // Fixed to medium size
            $this->theme = config('chat.ui.theme', 'auto');

            // Initialize context types
            $this->contextTypes = config('chat.context.context_types', []);
            $this->contextType = config('chat.context.default_context_type', 'livestock_management');

            // Set default provider based on configuration and availability
            $defaultProvider = config('chat.system.default_provider', 'openwebui');
            $this->selectedProvider = $defaultProvider;

            // If default provider is not available, fall back to available ones
            if (!config("chat.providers.{$defaultProvider}.enabled", false)) {
                $this->selectedProvider = config('chat.providers.ollama.enabled') ? 'ollama' : 'openwebui';
            }

            // Load available providers and models FIRST
            $this->loadAvailableProviders();

            // Load chat templates
            $this->loadChatTemplates();

            // Load recent sessions ALWAYS (not just when opened)
            $this->loadRecentSessions();

            // Load rated messages from the database
            $this->loadRatedMessages();

            // Don't auto-open chat on mount to ensure bubble is shown first
            $this->isOpen = false;

        } catch (Exception $e) {
            Log::error('AiChatWidget: Error initializing component', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            $this->setError('Failed to initialize chat widget');
        }
    }

    /**
     * Load messages that have been rated from the database
     */
    private function loadRatedMessages()
    {
        try {
            // Query the database to find all messages that have been rated by the current user
            $ratedMessageIds = \App\Models\ChatRating::where('user_id', Auth::id())
                ->pluck('chat_message_id')
                ->toArray();

            // Initialize the ratedMessages array with the message IDs
            foreach ($ratedMessageIds as $messageId) {
                $this->ratedMessages[$messageId] = true;
            }

            Log::debug('AiChatWidget: Loaded rated messages', [
                'count' => count($this->ratedMessages),
                'user_id' => Auth::id()
            ]);
        } catch (\Exception $e) {
            Log::error('AiChatWidget: Error loading rated messages', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);
            // Don't fail initialization if loading rated messages fails
        }
    }

    /**
     * Restore the last active session
     */
    private function restoreLastSession()
    {
        try {
            if (!empty($this->sessions)) {
                // Check if we should create a new session based on date
                if ($this->shouldCreateNewSession()) {
                    $this->createNewSession();
                    return;
                }

                // Get the most recent session
                $lastSession = $this->sessions[0] ?? null;

                if ($lastSession) {
                    $this->currentSessionId = $lastSession['id'];
                    $this->loadSessionMessages($lastSession['id']);

                    Log::debug('AiChatWidget: Restored last session', [
                        'session_id' => $lastSession['id'],
                        'user_id' => Auth::id()
                    ]);
                }
            }
        } catch (Exception $e) {
            Log::warning('AiChatWidget: Could not restore last session', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);
            // Don't fail initialization if session restore fails
        }
    }

    /**
     * Open chat widget
     */
    public function openChat()
    {
        // Critical - set isOpen to true first
        $this->isOpen = true;
        $this->isMinimized = false;

        // Load recent sessions if none are loaded
        if (empty($this->sessions)) {
            $this->loadRecentSessions();
        }

        // Check if we need to create a new session (different day)
        $shouldCreateNewSession = $this->shouldCreateNewSession();

        // If no current session and no existing sessions, create new session
        if (!$this->currentSessionId && empty($this->sessions)) {
            $this->createNewSession();
        } else if (!$this->currentSessionId && !empty($this->sessions)) {
            // If we have sessions but no current session
            if ($shouldCreateNewSession) {
                // Create a new session if it's a new day
                $this->createNewSession();
            } else {
                // Restore the most recent one
                $this->restoreLastSession();
            }
        } else if ($this->currentSessionId && $shouldCreateNewSession) {
            // If we have a current session but it's a new day, create a new session
            $this->createNewSession();
        }

        // Clear any existing messages to prepare for display
        $this->clearMessages();
        
        // Notify JavaScript that chat is opened
        $this->dispatch('chat-opened');
        
        // Log the action
        Log::debug('AiChatWidget: Chat opened', [
            'user_id' => Auth::id(),
            'isOpen' => $this->isOpen
        ]);
    }

    /**
     * Check if we should create a new session based on date
     */
    private function shouldCreateNewSession(): bool
    {
        // If no sessions exist, we should create a new one
        if (empty($this->sessions)) {
            return true;
        }

        // Get the most recent session
        $lastSession = $this->sessions[0] ?? null;

        if (!$lastSession) {
            return true;
        }

        // Check if the last session was created today
        $lastActivity = Carbon::parse($lastSession['last_activity_at'] ?? $lastSession['created_at']);
        $today = Carbon::today();

        // If the last session was not today, create a new session
        return !$lastActivity->isSameDay($today);
    }

    /**
     * Close chat widget and show bubble
     */
    public function closeChat()
    {
        $this->isOpen = false;
        $this->isMinimized = false;
        $this->clearMessages();
        $this->dispatch('chat-closed');
    }

    /**
     * Toggle chat widget open/close
     */
    public function toggleChat()
    {
        if ($this->isOpen) {
            $this->closeChat();
        } else {
            $this->openChat();
        }
    }

    /**
     * Send message to AI
     */
    public function sendMessage()
    {
        Log::debug('AiChatWidget: sendMessage called', [
            'newMessage' => $this->newMessage,
            'user_id' => Auth::id(),
            'currentSessionId' => $this->currentSessionId
        ]);
        
        try {
            $this->validate();
            Log::debug('AiChatWidget: Validation passed');
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('AiChatWidget: Validation failed', [
                'errors' => $e->errors(),
                'newMessage' => $this->newMessage
            ]);
            
            $this->setError('Validation failed: ' . json_encode($e->errors()));
            return;
        }

        if (empty($this->newMessage)) {
            Log::warning('AiChatWidget: Empty message');
            $this->setError('Message cannot be empty');
            return;
        }

        try {
            $this->isLoading = true;
            $this->isTyping = true;
            $this->clearMessages();

            // Store message text before processing
            $messageText = $this->newMessage;

            // Process natural language to detect context
            $nlpResult = $this->contextService->processNaturalLanguageQuery($this->newMessage);
            if ($nlpResult['context_type'] !== 'general') {
                $this->contextType = $nlpResult['context_type'];
                $this->contextFilters = $nlpResult['criteria'];
            }

            // If no current session, create one before proceeding
            if (!$this->currentSessionId) {
                $this->createNewSession();
            }

            // Immediately add user message to UI for instant feedback
            $userMessageData = [
                'id' => uniqid('temp_'),
                'message_type' => 'user',
                'content' => $messageText,
                'created_at' => now()->toDateTimeString(),
                'user_id' => Auth::id(),
                'is_greeting' => false
            ];
            $this->addMessage($userMessageData);

            // Clear input immediately for better user experience
            $this->newMessage = '';
            
            // Force UI to refresh
            $this->dispatch('ui-refresh');

            // Now process with the AI service
            $result = $this->chatService->sendMessage(
                $messageText,
                $this->currentSessionId,
                $this->selectedProvider,
                $this->selectedModel,
                $this->contextFilters,
                $this->contextType
            );

            if ($result['success']) {
                // Update current session ID if new session was created
                if (!$this->currentSessionId) {
                    $this->currentSessionId = $result['session_id'];
                }

                // Only add AI message as user message was already added
                $this->addMessage($result['assistant_message']);

                // Refresh sessions list
                $this->loadRecentSessions();

                $this->setSuccess('Message sent successfully');

                $this->dispatch('message-sent', [
                    'processing_time' => $result['processing_time'],
                    'context_type' => $result['context_type']
                ]);
                
                // Force UI to refresh again after AI response
                $this->dispatch('ui-refresh');
            } else {
                $this->setError($result['error'] ?? 'Failed to send message');
            }

        } catch (Exception $e) {
            Log::error('AiChatWidget: Error sending message', [
                'error' => $e->getMessage(),
                'message' => $this->newMessage,
                'user_id' => Auth::id()
            ]);

            $this->setError('An error occurred while sending the message');
        } finally {
            $this->isLoading = false;
            $this->isTyping = false;
        }
    }

    /**
     * Regenerate the last AI message
     */
    public function regenerateMessage($messageId = null)
    {
        try {
            $this->isLoading = true;
            $this->isTyping = true;
            $this->clearMessages();

            // Find the last user message and remove the subsequent AI response
            $messages = collect($this->messages);
            $lastUserMessageIndex = null;

            // Find the last user message
            for ($i = count($this->messages) - 1; $i >= 0; $i--) {
                if ($this->messages[$i]['message_type'] === 'user') {
                    $lastUserMessageIndex = $i;
                    break;
                }
            }

            if ($lastUserMessageIndex === null) {
                $this->setError('No user message found to regenerate response');
                return;
            }

            $lastUserMessage = $this->messages[$lastUserMessageIndex]['content'];

            // Remove AI messages after the last user message
            $this->messages = array_slice($this->messages, 0, $lastUserMessageIndex + 1);

            // Process the query again
            $nlpResult = $this->contextService->processNaturalLanguageQuery($lastUserMessage);
            if ($nlpResult['context_type'] !== 'general') {
                $this->contextType = $nlpResult['context_type'];
                $this->contextFilters = $nlpResult['criteria'];
            }

            $result = $this->chatService->sendMessage(
                $lastUserMessage,
                $this->currentSessionId,
                $this->selectedProvider,
                $this->selectedModel,
                $this->contextFilters,
                $this->contextType,
                true // isRegenerate flag
            );

            if ($result['success']) {
                // Add the new AI response
                $this->addMessage($result['assistant_message']);
                $this->setSuccess('Response regenerated successfully');

                $this->dispatch('message-regenerated', [
                    'processing_time' => $result['processing_time'],
                    'context_type' => $result['context_type']
                ]);
            } else {
                $this->setError($result['error'] ?? 'Failed to regenerate message');
            }

        } catch (Exception $e) {
            Log::error('AiChatWidget: Error regenerating message', [
                'error' => $e->getMessage(),
                'message_id' => $messageId,
                'user_id' => Auth::id()
            ]);

            $this->setError('An error occurred while regenerating the message');
        } finally {
            $this->isLoading = false;
            $this->isTyping = false;
        }
    }

    /**
     * Create new chat session
     */
    public function createNewSession()
    {
        try {
            $session = $this->chatService->createSession($this->selectedProvider, $this->selectedModel);

            if ($session) {
                $this->currentSessionId = $session->id;
                $this->messages = [];
                $this->loadRecentSessions();
                $this->setSuccess('New chat session created');

                $this->dispatch('session-created', $session->id);
            } else {
                $this->setError('Failed to create new session');
            }
        } catch (Exception $e) {
            Log::error('AiChatWidget: Error creating session', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            $this->setError('Failed to create new session');
        }
    }

    /**
     * Switch to different chat session
     */
    public function switchSession(string $sessionId)
    {
        try {
            $this->currentSessionId = $sessionId;
            $this->loadSessionMessages($sessionId);
            $this->clearMessages(); // Clear any error/success messages

            Log::debug('AiChatWidget: Switched to session', [
                'session_id' => $sessionId,
                'user_id' => Auth::id(),
                'message_count' => count($this->messages)
            ]);

            $this->dispatch('session-switched', $sessionId);
        } catch (Exception $e) {
            Log::error('AiChatWidget: Error switching session', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId,
                'user_id' => Auth::id()
            ]);

            $this->setError('Failed to switch session');
        }
    }

    /**
     * Delete chat session
     */
    public function deleteSession(string $sessionId)
    {
        try {
            $result = $this->chatService->deleteSession($sessionId);

            if ($result['success']) {
                // If deleted session was current, create new one
                if ($this->currentSessionId === $sessionId) {
                    $this->currentSessionId = null;
                    $this->messages = [];
                    $this->createNewSession();
                }

                $this->loadRecentSessions();
                $this->setSuccess('Session deleted successfully');

                $this->dispatch('session-deleted', $sessionId);
            } else {
                $this->setError($result['error'] ?? 'Failed to delete session');
            }
        } catch (Exception $e) {
            Log::error('AiChatWidget: Error deleting session', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId,
                'user_id' => Auth::id()
            ]);

            $this->setError('Failed to delete session');
        }
    }

    /**
     * Clear all sessions for current user
     */
    public function clearAllSessions()
    {
        try {
            $result = $this->chatService->clearAllUserSessions();

            if ($result['success']) {
                $this->sessions = [];
                $this->currentSessionId = null;
                $this->messages = [];

                // Create new session
                $this->createNewSession();

                $this->setSuccess('All sessions cleared successfully');

                Log::info('AiChatWidget: All sessions cleared', [
                    'user_id' => Auth::id(),
                    'deleted_count' => $result['deleted_count'] ?? 0
                ]);

                $this->dispatch('sessions-cleared');
            } else {
                $this->setError($result['error'] ?? 'Failed to clear sessions');
            }
        } catch (Exception $e) {
            Log::error('AiChatWidget: Error clearing all sessions', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            $this->setError('An error occurred while clearing sessions');
        }
    }

    /**
     * Switch AI provider
     */
    public function switchProvider()
    {
        try {
            if ($this->currentSessionId) {
                $result = $this->chatService->switchProvider(
                    $this->currentSessionId,
                    $this->selectedProvider,
                    $this->selectedModel
                );

                if ($result['success']) {
                    $this->setSuccess("Switched to {$this->selectedProvider}");
                    $this->loadSessionMessages($this->currentSessionId);

                    $this->dispatch('provider-switched', $this->selectedProvider);
                } else {
                    $this->setError($result['error'] ?? 'Failed to switch provider');
                }
            }

            // Update available models for new provider
            $this->loadAvailableModels();

        } catch (Exception $e) {
            Log::error('AiChatWidget: Error switching provider', [
                'error' => $e->getMessage(),
                'provider' => $this->selectedProvider,
                'user_id' => Auth::id()
            ]);

            $this->setError('Failed to switch provider');
        }
    }

    /**
     * Load available providers
     */
    private function loadAvailableProviders()
    {
        try {
            $result = $this->chatService->getAvailableProviders();

            if ($result['success']) {
                $this->availableProviders = $result['providers'];
                $this->loadAvailableModels();

                Log::debug('AiChatWidget: Providers loaded', [
                    'provider_count' => count($this->availableProviders),
                    'selected_provider' => $this->selectedProvider,
                    'selected_model' => $this->selectedModel,
                    'user_id' => Auth::id()
                ]);
            } else {
                Log::warning('AiChatWidget: Failed to load providers', [
                    'error' => $result['error'] ?? 'Unknown error',
                    'user_id' => Auth::id()
                ]);
            }
        } catch (Exception $e) {
            Log::error('AiChatWidget: Error loading providers', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Load chat templates
     */
    private function loadChatTemplates()
    {
        try {
            $this->chatTemplates = config('chat_templates.templates', []);

            Log::debug('AiChatWidget: Chat templates loaded', [
                'template_count' => count($this->chatTemplates),
                'user_id' => Auth::id()
            ]);
        } catch (Exception $e) {
            Log::error('AiChatWidget: Error loading chat templates', [
                'error' => $e->getMessage()
            ]);
            $this->chatTemplates = [];
        }
    }

    /**
     * Load available models for current provider
     */
    private function loadAvailableModels()
    {
        try {
            if (isset($this->availableProviders[$this->selectedProvider])) {
                $provider = $this->availableProviders[$this->selectedProvider];
                $this->availableModels = $provider['models'] ?? [];

                // If no models are available from API, use fallback defaults
                if (empty($this->availableModels)) {
                    $this->availableModels = $this->getFallbackModels($this->selectedProvider);
                    Log::warning('AiChatWidget: Using fallback models for provider', [
                        'provider' => $this->selectedProvider,
                        'fallback_models' => $this->availableModels
                    ]);
                }

                // Set default model from configuration or use first available
                if (empty($this->selectedModel) && !empty($this->availableModels)) {
                    $configDefaultModel = config("chat.providers.{$this->selectedProvider}.default_model");

                    // Check if the configured default model is available
                    if ($configDefaultModel && in_array($configDefaultModel, $this->availableModels)) {
                        $this->selectedModel = $configDefaultModel;
                    } else {
                        // Fallback to first available model
                        $this->selectedModel = $this->availableModels[0] ?? '';
                    }

                    Log::debug('AiChatWidget: Model selected', [
                        'provider' => $this->selectedProvider,
                        'selected_model' => $this->selectedModel,
                        'config_default' => $configDefaultModel,
                        'available_models' => $this->availableModels
                    ]);
                }
            }
        } catch (Exception $e) {
            Log::error('AiChatWidget: Error loading models', [
                'error' => $e->getMessage(),
                'provider' => $this->selectedProvider
            ]);

            // Use fallback models in case of error
            $this->availableModels = $this->getFallbackModels($this->selectedProvider);
            if (empty($this->selectedModel) && !empty($this->availableModels)) {
                $this->selectedModel = $this->availableModels[0];
            }
        }
    }

    /**
     * Get fallback models when API is not available
     */
    private function getFallbackModels(string $provider): array
    {
        $fallbacks = [
            'openwebui' => [
                'qwen2.5:3b',
                'llama3.1:8b',
                'mistral:7b',
                'phi3:mini',
                'gemma2:2b'
            ],
            'ollama' => [
                'llama3.1:8b',
                'llama2:7b',
                'mistral:7b',
                'phi3:mini',
                'gemma2:2b'
            ]
        ];

        return $fallbacks[$provider] ?? ['default-model'];
    }

    /**
     * Load recent chat sessions
     */
    public function loadRecentSessions()
    {
        try {
            $maxSessions = config('chat.ui.max_sessions_display', 15);
            $result = $this->chatService->getUserSessions($maxSessions);

            if ($result['success']) {
                $this->sessions = $result['sessions']->toArray();

                Log::debug('AiChatWidget: Sessions loaded', [
                    'user_id' => Auth::id(),
                    'session_count' => count($this->sessions)
                ]);

                $this->setSuccess('Sessions refreshed successfully');
            } else {
                Log::warning('AiChatWidget: Failed to load sessions', [
                    'error' => $result['error'] ?? 'Unknown error',
                    'user_id' => Auth::id()
                ]);
                $this->sessions = [];
                $this->setError('Failed to load sessions');
            }
        } catch (Exception $e) {
            Log::error('AiChatWidget: Error loading sessions', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);
            $this->sessions = [];
            $this->setError('Error loading sessions');
        }
    }

    /**
     * Load messages for a session
     */
    private function loadSessionMessages(string $sessionId)
    {
        try {
            $result = $this->chatService->getSessionHistory($sessionId, 50);

            if ($result['success']) {
                // Process messages to ensure correct boolean values
                $this->messages = array_map(function($message) {
                    // Ensure is_greeting is explicitly a boolean
                    if (isset($message['is_greeting'])) {
                        $message['is_greeting'] = (bool)$message['is_greeting'];
                    }
                    return $message;
                }, $result['messages']);

                // Refresh rated messages data
                $this->loadRatedMessages();

                // Debug logging for greeting messages
                $greetingCount = count(array_filter($this->messages, function($msg) {
                    return isset($msg['is_greeting']) && $msg['is_greeting'] === true;
                }));

                Log::debug('AiChatWidget: Loaded messages with greeting status', [
                    'session_id' => $sessionId,
                    'total_messages' => count($this->messages),
                    'greeting_messages' => $greetingCount
                ]);
            }
        } catch (Exception $e) {
            Log::error('AiChatWidget: Error loading messages', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId
            ]);
        }
    }

    /**
     * Add message to UI
     */
    private function addMessage($message)
    {
        if (is_array($message)) {
            // Ensure is_greeting is explicitly a boolean
            if (isset($message['is_greeting'])) {
                $message['is_greeting'] = (bool)$message['is_greeting'];
            }
            $this->messages[] = $message;
        } else {
            // Ensure is_greeting is properly included for Eloquent models
            $data = $message->toArray();
            $data['is_greeting'] = $message->is_greeting ? true : false;
            $this->messages[] = $data;
        }
        
        // Force UI refresh after adding a message
        $this->dispatch('ui-refresh');
    }

    /**
     * Force UI refresh for all Livewire components
     */
    #[On('force-refresh')]
    public function forceRefresh()
    {
        // This method will force a refresh of the component
        Log::debug('AiChatWidget: Force refresh triggered');
        
        // Make sure messages are loaded if we have a session
        if ($this->currentSessionId && empty($this->messages)) {
            $this->loadSessionMessages($this->currentSessionId);
        }
        
        // Dispatch event for JavaScript to handle
        $this->dispatch('ui-refresh');
    }

    /**
     * Clear error and success messages
     */
    private function clearMessages()
    {
        $this->errorMessage = null;
        $this->successMessage = null;
    }

    /**
     * Set error message
     */
    private function setError(string $message)
    {
        $this->errorMessage = $message;
        $this->successMessage = null;

        $this->dispatch('chat-error', $message);
    }

    /**
     * Set success message
     */
    private function setSuccess(string $message)
    {
        $this->successMessage = $message;
        $this->errorMessage = null;

        // Auto-clear success message after 3 seconds
        $this->dispatch('chat-success', $message);
    }

    /**
     * Export chat session
     */
    public function exportChat()
    {
        try {
            if (!$this->currentSessionId) {
                $this->setError('No active session to export');
                return;
            }

            $result = $this->chatService->getSessionHistory($this->currentSessionId);

            if ($result['success']) {
                // Dispatch event with export format
                $this->dispatch('export-chat', [
                    'session' => $result['session'],
                    'messages' => $result['messages'],
                    'format' => $this->exportFormat
                ]);
            } else {
                $this->setError('Failed to export chat');
            }
        } catch (Exception $e) {
            Log::error('AiChatWidget: Error exporting chat', [
                'error' => $e->getMessage(),
                'session_id' => $this->currentSessionId
            ]);

            $this->setError('Failed to export chat');
        }
    }

    /**
     * Export chat session in specific format
     */
    public function exportChatAs($format)
    {
        $this->exportFormat = $format;
        $this->exportChat();
    }

    /**
     * Toggle template panel visibility
     */
    public function toggleTemplates()
    {
        $this->showTemplates = !$this->showTemplates;
        // Hide other panels when showing templates
        if ($this->showTemplates) {
            $this->showSettings = false;
            $this->showSessions = false;
        }
    }

    /**
     * Use a chat template
     */
    public function useTemplate($template)
    {
        $this->newMessage = $template;
        $this->showTemplates = false;
        // Focus on the textarea
        $this->dispatch('focus-input');
    }

    /**
     * Toggle search panel visibility
     */
    public function toggleSearch()
    {
        $this->showSearch = !$this->showSearch;
        // Hide other panels when showing search
        if ($this->showSearch) {
            $this->showSettings = false;
            $this->showSessions = false;
            $this->showTemplates = false;
        }
    }

    /**
     * Search chat history
     */
    public function searchChatHistory()
    {
        try {
            if (empty($this->searchQuery)) {
                // Load all sessions if search query is empty
                $this->loadRecentSessions();
                return;
            }

            // Search sessions by title or message content
            $searchTerm = strtolower($this->searchQuery);

            // Get all user sessions
            $allSessions = $this->chatService->getUserSessions(100)['sessions'] ?? collect();

            // Filter sessions based on search term
            $filteredSessions = $allSessions->filter(function ($session) use ($searchTerm) {
                // Check if search term is in session title
                if (stripos($session['title'] ?? '', $searchTerm) !== false) {
                    return true;
                }

                // Check if search term is in any message content
                $messages = $this->chatService->getSessionHistory($session['id'], 100)['messages'] ?? [];
                foreach ($messages as $message) {
                    if (stripos($message['content'] ?? '', $searchTerm) !== false) {
                        return true;
                    }
                }

                return false;
            });

            $this->sessions = $filteredSessions->toArray();

            $this->setSuccess('Found ' . count($this->sessions) . ' matching sessions');

        } catch (Exception $e) {
            Log::error('AiChatWidget: Error searching chat history', [
                'error' => $e->getMessage(),
                'search_query' => $this->searchQuery
            ]);

            $this->setError('Error searching chat history');
        }
    }

    /**
     * Clear search and reload all sessions
     */
    public function clearSearch()
    {
        $this->searchQuery = '';
        $this->loadRecentSessions();
        $this->showSearch = false;
    }

    /**
     * Listen for external events
     */
    #[On('chat-widget-open')]
    public function handleOpenEvent()
    {
        $this->openChat();
    }

    #[On('chat-widget-close')]
    public function handleCloseEvent()
    {
        $this->closeChat();
    }

    #[On('chat-send-message')]
    public function handleSendMessageEvent($message)
    {
        $this->newMessage = $message;
        $this->sendMessage();
    }

    #[On('rating-submitted')]
    public function handleRatingSubmitted($data)
    {
        // When a rating is submitted, add the message ID to the ratedMessages array
        if (isset($data['messageId'])) {
            $this->ratedMessages[$data['messageId']] = true;

            // Log the rated message for debugging
            Log::debug('AiChatWidget: Message rated', [
                'message_id' => $data['messageId'],
                'rating' => $data['rating'] ?? 'unknown'
            ]);
        }
    }

    #[On('send-message')]
    public function handleSendMessage($data = null)
    {
        Log::debug('AiChatWidget: handleSendMessage called', [
            'data' => $data,
            'currentMessage' => $this->newMessage
        ]);
        
        // If message is provided in data, use it
        if (is_array($data) && isset($data['message'])) {
            $this->newMessage = $data['message'];
        }
        // If message is provided directly as string
        elseif (is_string($data) && !empty($data)) {
            $this->newMessage = $data;
        }
        
        $this->sendMessage();
    }
    
    #[On('clear-errors')]
    public function clearErrors()
    {
        Log::debug('AiChatWidget: clearErrors called');
        $this->errorMessage = null;
        $this->successMessage = null;
    }

    #[On('show-sessions')]
    public function handleShowSessions()
    {
        // This will be handled in the frontend
    }

    /**
     * Computed properties
     */
    public function getCurrentSessionProperty()
    {
        if (!$this->currentSessionId) {
            return null;
        }

        return collect($this->sessions)->firstWhere('id', $this->currentSessionId);
    }

    public function getMessageCountProperty()
    {
        return count($this->messages);
    }

    public function getProviderStatusProperty()
    {
        return $this->availableProviders[$this->selectedProvider]['available'] ?? false;
    }

    public function render()
    {
        // Check if chat is enabled in configuration
        if (!config('chat.system.enabled', false)) {
            return view('livewire.empty-component');
        }

        return view('livewire.ai-chat-widget', [
            'currentSession' => $this->getCurrentSessionProperty(),
            'messageCount' => $this->getMessageCountProperty(),
            'providerStatus' => $this->getProviderStatusProperty()
        ]);
    }

    /**
     * Debug method to check current state
     */
    public function debugState()
    {
        Log::info('AiChatWidget Debug State', [
            'user_id' => Auth::id(),
            'company_id' => Auth::user()->company_id ?? null,
            'selected_provider' => $this->selectedProvider,
            'selected_model' => $this->selectedModel,
            'available_providers' => array_keys($this->availableProviders),
            'available_models' => $this->availableModels,
            'sessions_count' => count($this->sessions),
            'current_session_id' => $this->currentSessionId,
            'messages_count' => count($this->messages),
            'config_default_model' => config('chat.providers.openwebui.default_model'),
            'config_default_provider' => config('chat.system.default_provider'),
            'sessions_data' => $this->sessions,
            'messages_sample' => array_slice($this->messages, 0, 5), // First 5 messages
            'last_session_data' => !empty($this->sessions) ? $this->sessions[0] : null
        ]);

        // Also check database directly
        $dbSessionCount = \App\Models\ChatSession::where('user_id', Auth::id())->count();
        $dbMessageCount = \App\Models\ChatMessage::where('user_id', Auth::id())->count();

        Log::info('Database counts', [
            'db_session_count' => $dbSessionCount,
            'db_message_count' => $dbMessageCount
        ]);

        // Check if current session exists in database
        if ($this->currentSessionId) {
            $dbSession = \App\Models\ChatSession::find($this->currentSessionId);
            $dbMessages = \App\Models\ChatMessage::where('chat_session_id', $this->currentSessionId)
                ->orderBy('created_at', 'asc')
                ->get(['id', 'message_type', 'content', 'created_at'])
                ->toArray();

            Log::info('Current session database check', [
                'session_exists' => $dbSession ? true : false,
                'session_data' => $dbSession ? $dbSession->toArray() : null,
                'db_messages_count' => count($dbMessages),
                'db_messages_sample' => array_slice($dbMessages, 0, 3)
            ]);
        }

        $this->setSuccess('Debug info logged - check application logs at storage/logs/laravel.log');
    }
}

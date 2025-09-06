<?php

namespace App\AiChatV2\Livewire;

use Livewire\Component;
use App\AiChatV2\Services\ChatSessionService;
use App\AiChatV2\Services\SettingsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class ChatInterface extends Component
{
    public $currentSessionId = null;
    public $messages = [];
    public $message = '';
    public $isLoading = false;
    public $isTyping = false;
    public $error = null;
    public $settings = [];
    
    protected ChatSessionService $chatSessionService;
    protected SettingsService $settingsService;

    public function boot(
        ChatSessionService $chatSessionService,
        SettingsService $settingsService
    ) {
        $this->chatSessionService = $chatSessionService;
        $this->settingsService = $settingsService;
    }

    public function mount($sessionId = null)
    {
        try {
            $this->loadSettings();
            
            if ($sessionId) {
                $this->currentSessionId = $sessionId;
                $this->loadSessionMessages();
            } else {
                $this->loadCurrentSession();
            }
        } catch (Exception $e) {
            Log::error('Error mounting ChatInterface component', [
                'user_id' => Auth::id(),
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->error = 'Failed to load chat interface';
        }
    }

    public function loadCurrentSession()
    {
        try {
            $session = $this->chatSessionService->getCurrentSession(Auth::id());
            if ($session) {
                $this->currentSessionId = $session['id'];
                $this->loadSessionMessages();
            } else {
                $this->createNewSession();
            }
        } catch (Exception $e) {
            Log::warning('Failed to load current session in ChatInterface', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            $this->createNewSession();
        }
    }

    public function createNewSession()
    {
        try {
            $this->currentSessionId = $this->chatSessionService->createNewSession(
                Auth::id(),
                'New Chat Session'
            );
            $this->messages = [];
        } catch (Exception $e) {
            $this->handleError('Failed to create new session', $e);
        }
    }

    public function loadSessionMessages()
    {
        if (!$this->currentSessionId) {
            return;
        }

        try {
            $this->messages = $this->chatSessionService->getSessionMessages($this->currentSessionId);
        } catch (Exception $e) {
            Log::warning('Failed to load session messages in ChatInterface', [
                'user_id' => Auth::id(),
                'session_id' => $this->currentSessionId,
                'error' => $e->getMessage()
            ]);
            $this->messages = [];
        }
    }

    public function sendMessage()
    {
        $this->error = null;
        
        if (empty(trim($this->message))) {
            return;
        }

        $this->isLoading = true;
        $this->isTyping = true;
        
        // Disable to prevent multiple submissions
        $this->dispatch('disable-input');
        
        try {
            // Add user message to UI immediately
            $userMessage = [
                'role' => 'user',
                'content' => $this->message,
                'created_at' => now()->toISOString()
            ];
            $this->messages[] = $userMessage;
            
            // Clear input
            $messageToSend = $this->message;
            $this->message = '';
            
            // Scroll to bottom
            $this->dispatch('scroll-to-bottom');
            
            // Process AI response
            $messageData = [
                'sessionId' => $this->currentSessionId,
                'userId' => Auth::id(),
                'message' => $messageToSend,
                'provider' => $this->settings['provider'] ?? config('ai-chat-v2.default_provider', 'openwebui'),
                'model' => $this->settings['model'] ?? config('ai-chat-v2.default_model', 'llama3.2:3b')
            ];
            
            $response = $this->chatSessionService->sendMessage($messageData);
            
            // Add AI response to UI
            $assistantMessage = [
                'role' => 'assistant',
                'content' => $response['content'],
                'created_at' => now()->toISOString(),
                'metadata' => [
                    'provider' => $response['provider'],
                    'model' => $response['model'],
                    'processing_time' => $response['processing_time'],
                    'token_count' => $response['token_count'],
                ]
            ];
            $this->messages[] = $assistantMessage;
            
            // Scroll to bottom again after response
            $this->dispatch('scroll-to-bottom');
            
        } catch (Exception $e) {
            $this->handleError('Failed to send message', $e);
            
            // Add error message to UI
            $errorMessage = [
                'role' => 'system',
                'content' => 'Sorry, I encountered an error processing your request. Please try again.',
                'created_at' => now()->toISOString()
            ];
            $this->messages[] = $errorMessage;
        } finally {
            $this->isLoading = false;
            $this->isTyping = false;
            $this->dispatch('enable-input');
        }
    }

    public function sendQuickPrompt($prompt)
    {
        $this->message = $prompt;
        $this->sendMessage();
    }

    public function clearChat()
    {
        try {
            if ($this->currentSessionId) {
                $this->chatSessionService->deleteSession($this->currentSessionId, Auth::id());
            }
            $this->createNewSession();
        } catch (Exception $e) {
            $this->handleError('Failed to clear chat', $e);
        }
    }

    public function exportChat()
    {
        // TODO: Implement chat export functionality
        $this->dispatch('notify', message: 'Chat export feature coming soon!', type: 'info');
    }

    protected function loadSettings()
    {
        try {
            $this->settings = $this->settingsService->getUserSettings(Auth::id());
        } catch (Exception $e) {
            Log::warning('Failed to load user settings in ChatInterface', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            
            // Fallback to default settings
            $this->settings = config('ai-chat-v2.defaults', []);
        }
    }

    protected function handleError(string $message, Exception $e)
    {
        Log::error($message, [
            'user_id' => Auth::id(),
            'session_id' => $this->currentSessionId,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        $this->error = $message . ': ' . $e->getMessage();
        $this->isLoading = false;
        $this->isTyping = false;
        $this->dispatch('enable-input');
    }

    public function render()
    {
        return view('ai-chat-v2.livewire.chat-interface');
    }
}
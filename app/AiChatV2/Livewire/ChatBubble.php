<?php

namespace App\AiChatV2\Livewire;

use Livewire\Component;
use App\AiChatV2\Services\ChatSessionService;
use App\AiChatV2\Services\SettingsService;
use App\AiChatV2\DTOs\ChatMessageDTO;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class ChatBubble extends Component
{
    public $isOpen = false;
    public $isMinimized = false;
    public $currentMessage = '';
    public $messages = [];
    public $currentSessionId = null;
    public $isLoading = false;
    public $error = null;
    public $settings = [];
    
    protected $chatSessionService;
    protected $settingsService;
    
    public function boot(
        ChatSessionService $chatSessionService,
        SettingsService $settingsService
    ) {
        $this->chatSessionService = $chatSessionService;
        $this->settingsService = $settingsService;
    }
    
    public function mount()
    {
        // Log that the component is being mounted
        Log::info('ChatBubble component mounting', [
            'user_id' => Auth::check() ? Auth::id() : 'unauthenticated',
            'auth_check' => Auth::check()
        ]);
        
        try {
            // Check if user is authenticated
            if (!Auth::check()) {
                Log::warning('ChatBubble component mounted for unauthenticated user');
                return;
            }
            
            $this->loadSettings();
            $this->loadCurrentSession();
            
            Log::info('ChatBubble component mounted successfully', [
                'user_id' => Auth::id(),
                'settings' => $this->settings,
                'current_session_id' => $this->currentSessionId
            ]);
        } catch (Exception $e) {
            Log::error('Error mounting ChatBubble component', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Set default settings to prevent complete failure
            $this->settings = [
                'provider' => 'ollama',
                'model' => 'llama2'
            ];
        }
    }
    
    public function toggleBubble()
    {
        Log::info('ChatBubble toggleBubble called', [
            'user_id' => Auth::id(),
            'is_open' => $this->isOpen,
            'is_minimized' => $this->isMinimized
        ]);
        
        try {
            $this->isOpen = !$this->isOpen;
            $this->isMinimized = false;
            
            if ($this->isOpen && !$this->currentSessionId) {
                $this->createNewSession();
            }
        } catch (Exception $e) {
            $this->handleError('Failed to toggle chat bubble', $e);
        }
    }
    
    public function minimizeBubble()
    {
        Log::info('ChatBubble minimizeBubble called', [
            'user_id' => Auth::id(),
            'is_open' => $this->isOpen,
            'is_minimized' => $this->isMinimized
        ]);
        
        try {
            $this->isMinimized = true;
            $this->isOpen = false;
        } catch (Exception $e) {
            $this->handleError('Failed to minimize chat bubble', $e);
        }
    }
    
    public function openInNewWindow()
    {
        Log::info('ChatBubble openInNewWindow called', [
            'user_id' => Auth::id()
        ]);
        
        try {
            $this->dispatch('open-chat-window', url: route('ai-chat-v2.index'));
            $this->isOpen = false;
        } catch (Exception $e) {
            $this->handleError('Failed to open chat in new window', $e);
        }
    }
    
    public function openFullChat()
    {
        Log::info('ChatBubble openFullChat called', [
            'user_id' => Auth::id()
        ]);
        
        try {
            return redirect()->route('ai-chat-v2.index');
        } catch (Exception $e) {
            $this->handleError('Failed to open full chat', $e);
        }
    }
    
    public function sendMessage()
    {
        Log::info('ChatBubble sendMessage called', [
            'user_id' => Auth::id(),
            'message' => $this->currentMessage
        ]);
        
        if (empty(trim($this->currentMessage))) {
            return;
        }
        
        $this->isLoading = true;
        $this->error = null;
        
        try {
            if (!$this->currentSessionId) {
                $this->createNewSession();
            }
            
            // Add user message to display
            $this->messages[] = [
                'role' => 'user',
                'content' => $this->currentMessage,
                'created_at' => now()->toISOString()
            ];
            
            $messageDTO = new ChatMessageDTO([
                'sessionId' => $this->currentSessionId,
                'userId' => Auth::id(),
                'message' => $this->currentMessage,
                'provider' => $this->settings['provider'] ?? 'ollama',
                'model' => $this->settings['model'] ?? 'llama2'
            ]);
            
            $this->currentMessage = '';
            
            // Send message
            $response = $this->chatSessionService->sendMessage($messageDTO);
            
            // Add AI response (simplified for bubble)
            $this->messages[] = [
                'role' => 'assistant',
                'content' => 'Response received. Open full chat for complete conversation.',
                'created_at' => now()->toISOString()
            ];
            
            // Dispatch event for auto-scroll
            $this->dispatch('message-sent');
            
        } catch (Exception $e) {
            $this->handleError('Failed to send message', $e);
        } finally {
            $this->isLoading = false;
        }
    }
    
    public function createNewSession()
    {
        Log::info('ChatBubble createNewSession called', [
            'user_id' => Auth::id()
        ]);
        
        try {
            $this->currentSessionId = $this->chatSessionService->createNewSession(
                Auth::id(),
                'Chat Bubble Session'
            );
            $this->messages = [];
        } catch (Exception $e) {
            $this->handleError('Failed to create session', $e);
        }
    }
    
    private function loadSettings()
    {
        Log::info('ChatBubble loadSettings called', [
            'user_id' => Auth::id()
        ]);
        
        try {
            $this->settings = $this->settingsService->getUserSettings(Auth::id());
        } catch (Exception $e) {
            Log::warning('Failed to load user settings in ChatBubble', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            
            // Fallback to default settings
            $this->settings = [
                'provider' => 'ollama',
                'model' => 'llama2'
            ];
        }
    }
    
    private function loadCurrentSession()
    {
        Log::info('ChatBubble loadCurrentSession called', [
            'user_id' => Auth::id()
        ]);
        
        try {
            $session = $this->chatSessionService->getCurrentSession(Auth::id());
            if ($session) {
                $this->currentSessionId = $session['id'];
                $this->loadRecentMessages();
            }
        } catch (Exception $e) {
            Log::warning('Failed to load current session in ChatBubble', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            // No current session, will create new one when needed
        }
    }
    
    private function loadRecentMessages()
    {
        Log::info('ChatBubble loadRecentMessages called', [
            'user_id' => Auth::id(),
            'session_id' => $this->currentSessionId
        ]);
        
        if (!$this->currentSessionId) {
            return;
        }
        
        try {
            $messages = $this->chatSessionService->getSessionMessages($this->currentSessionId, 5);
            $this->messages = array_slice($messages, -5); // Last 5 messages for bubble
        } catch (Exception $e) {
            Log::warning('Failed to load recent messages in ChatBubble', [
                'user_id' => Auth::id(),
                'session_id' => $this->currentSessionId,
                'error' => $e->getMessage()
            ]);
            $this->messages = [];
        }
    }
    
    private function handleError(string $message, Exception $e)
    {
        Log::error($message, [
            'user_id' => Auth::id(),
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        $this->error = $message . ': ' . $e->getMessage();
    }
    
    public function render()
    {
        // Log that the component is being rendered
        Log::info('ChatBubble component rendering', [
            'user_id' => Auth::check() ? Auth::id() : 'unauthenticated',
            'auth_check' => Auth::check()
        ]);
        
        // Ensure component only renders for authenticated users
        if (!Auth::check()) {
            Log::info('ChatBubble component not rendering for unauthenticated user');
            return '';
        }
        
        Log::info('ChatBubble component rendered successfully', [
            'user_id' => Auth::id()
        ]);
        
        return view('livewire.ai-chat-v2.chat-bubble');
    }
}
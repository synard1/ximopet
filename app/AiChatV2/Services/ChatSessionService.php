<?php

namespace App\AiChatV2\Services;

use App\Models\ChatSession;
use App\Models\ChatMessage;
use App\Services\OpenWebUIService;
use App\Services\OllamaChatService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class ChatSessionService
{
    protected OpenWebUIService $openWebUIService;
    protected OllamaChatService $ollamaService;

    public function __construct(
        OpenWebUIService $openWebUIService,
        OllamaChatService $ollamaService
    ) {
        $this->openWebUIService = $openWebUIService;
        $this->ollamaService = $ollamaService;
    }

    /**
     * Create a new chat session
     */
    public function createNewSession(string $userId, string $title = null, string $provider = null, string $model = null): string
    {
        try {
            $session = new ChatSession();
            $session->user_id = $userId;
            $session->company_id = Auth::user()->company_id ?? null;
            $session->title = $title ?? config('chat.sessions.default_title', 'New Chat Session');
            $session->ai_provider = $provider ?? config('chat.system.default_provider', 'openwebui');
            $session->model_name = $model ?? $this->getDefaultModelForProvider($session->ai_provider);
            $session->is_active = true;
            $session->last_activity_at = now();
            $session->save();

            return $session->id;
        } catch (Exception $e) {
            Log::error('Failed to create new chat session', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Send a message and get AI response
     */
    public function sendMessage(array $messageData): array
    {
        try {
            $sessionId = $messageData['sessionId'];
            $userId = $messageData['userId'];
            $message = $messageData['message'];
            $provider = $messageData['provider'] ?? config('chat.system.default_provider', 'openwebui');
            $model = $messageData['model'] ?? null;

            // Save user message
            $userMessage = new ChatMessage();
            $userMessage->chat_session_id = $sessionId;
            $userMessage->user_id = $userId;
            $userMessage->message_type = 'user';
            $userMessage->content = $message;
            $userMessage->save();

            // Update session activity
            $session = ChatSession::find($sessionId);
            if ($session) {
                $session->updateActivity();
                // Generate title if this is the first message
                if ($session->messages()->count() == 1) {
                    $session->title = $session->generateTitle($message);
                    $session->save();
                }
            }

            // Get AI response
            $aiResponse = $this->getAIResponse($message, $provider, $model, $sessionId);

            // Save AI response
            $assistantMessage = new ChatMessage();
            $assistantMessage->chat_session_id = $sessionId;
            $assistantMessage->user_id = $userId;
            $assistantMessage->message_type = 'assistant';
            $assistantMessage->content = $aiResponse['content'];
            $assistantMessage->processing_time = $aiResponse['processing_time'] ?? null;
            $assistantMessage->token_count = $aiResponse['token_count'] ?? null;
            $assistantMessage->metadata = [
                'provider' => $aiResponse['provider'] ?? $provider,
                'model' => $aiResponse['model'] ?? $model,
                'attempt' => $aiResponse['attempt'] ?? null,
            ];
            $assistantMessage->save();

            return [
                'content' => $aiResponse['content'],
                'processing_time' => $aiResponse['processing_time'] ?? null,
                'token_count' => $aiResponse['token_count'] ?? null,
                'provider' => $aiResponse['provider'] ?? $provider,
                'model' => $aiResponse['model'] ?? $model,
            ];
        } catch (Exception $e) {
            Log::error('Failed to send message', [
                'session_id' => $sessionId ?? 'unknown',
                'user_id' => $userId ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            
            // Save error message
            if (isset($sessionId) && isset($userId)) {
                $errorMessage = new ChatMessage();
                $errorMessage->chat_session_id = $sessionId;
                $errorMessage->user_id = $userId;
                $errorMessage->message_type = 'system';
                $errorMessage->content = 'Sorry, I encountered an error processing your request. Please try again.';
                $errorMessage->save();
            }
            
            throw $e;
        }
    }

    /**
     * Get session messages
     */
    public function getSessionMessages(string $sessionId, int $limit = 50): array
    {
        $messages = ChatMessage::where('chat_session_id', $sessionId)
            ->orderBy('created_at')
            ->limit($limit)
            ->get();

        return $messages->map(function ($message) {
            return [
                'id' => $message->id,
                'role' => $message->message_type,
                'content' => $message->content,
                'created_at' => $message->created_at->toISOString(),
                'is_greeting' => $message->is_greeting,
                'metadata' => $message->metadata,
                'processing_time' => $message->processing_time,
                'token_count' => $message->token_count,
            ];
        })->toArray();
    }

    /**
     * Get user sessions
     */
    public function getUserSessions(string $userId, string $searchTerm = null, bool $showArchived = false): array
    {
        $query = ChatSession::where('user_id', $userId)
            ->orderBy('last_activity_at', 'desc')
            ->limit(config('ai-chat-v2.history.default_limit', 50));

        if (!$showArchived) {
            $query->where('is_active', true);
        }

        if ($searchTerm) {
            $query->where('title', 'like', '%' . $searchTerm . '%');
        }

        $sessions = $query->get();

        return $sessions->map(function ($session) {
            $latestMessage = $session->latestMessage;
            return [
                'id' => $session->id,
                'title' => $session->title,
                'provider' => $session->ai_provider,
                'model' => $session->model_name,
                'is_archived' => !$session->is_active,
                'message_count' => $session->message_count,
                'summary' => $latestMessage ? $latestMessage->preview : null,
                'updated_at' => $session->last_activity_at?->toISOString() ?? $session->updated_at->toISOString(),
            ];
        })->toArray();
    }

    /**
     * Get current active session for user
     */
    public function getCurrentSession(string $userId): ?array
    {
        $session = ChatSession::where('user_id', $userId)
            ->where('is_active', true)
            ->orderBy('last_activity_at', 'desc')
            ->first();

        if (!$session) {
            return null;
        }

        return [
            'id' => $session->id,
            'title' => $session->title,
            'provider' => $session->ai_provider,
            'model' => $session->model_name,
        ];
    }

    /**
     * Archive a session
     */
    public function archiveSession(string $sessionId, string $userId): bool
    {
        $session = ChatSession::where('id', $sessionId)
            ->where('user_id', $userId)
            ->first();

        if (!$session) {
            return false;
        }

        $session->is_active = false;
        return $session->save();
    }

    /**
     * Restore a session
     */
    public function restoreSession(string $sessionId, string $userId): bool
    {
        $session = ChatSession::where('id', $sessionId)
            ->where('user_id', $userId)
            ->first();

        if (!$session) {
            return false;
        }

        $session->is_active = true;
        return $session->save();
    }

    /**
     * Delete a session
     */
    public function deleteSession(string $sessionId, string $userId): bool
    {
        $session = ChatSession::where('id', $sessionId)
            ->where('user_id', $userId)
            ->first();

        if (!$session) {
            return false;
        }

        return $session->delete();
    }

    /**
     * Get default model for provider
     */
    protected function getDefaultModelForProvider(string $provider): string
    {
        return config("ai-chat-v2.providers.{$provider}.default_model", 'llama3.2:3b');
    }

    /**
     * Get AI response from the appropriate service
     */
    protected function getAIResponse(string $message, string $provider, string $model = null, string $sessionId = null): array
    {
        $context = $this->buildContext($sessionId);
        
        switch ($provider) {
            case 'openwebui':
                return $this->openWebUIService->sendChatRequest($message, $model, $context);
            case 'ollama':
                return $this->ollamaService->sendChatRequest($message, $model, $context);
            default:
                // Fallback to default provider
                $defaultProvider = config('chat.system.default_provider', 'openwebui');
                if ($defaultProvider === 'openwebui') {
                    return $this->openWebUIService->sendChatRequest($message, $model, $context);
                } else {
                    return $this->ollamaService->sendChatRequest($message, $model, $context);
                }
        }
    }

    /**
     * Build context for the AI request
     */
    protected function buildContext(string $sessionId = null): array
    {
        if (!$sessionId) {
            return [];
        }

        $session = ChatSession::find($sessionId);
        if (!$session) {
            return [];
        }

        // Get recent messages as context
        $contextMessages = $session->messages()
            ->orderBy('created_at', 'desc')
            ->limit(config('chat.system.context_size_limit', 10))
            ->get()
            ->reverse() // Reverse to chronological order
            ->values();

        $context = [];
        foreach ($contextMessages as $msg) {
            $context[] = [
                'role' => $msg->message_type,
                'content' => $msg->content
            ];
        }

        return $context;
    }
}
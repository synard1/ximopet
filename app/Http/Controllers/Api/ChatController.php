<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AiChatService;
use App\Services\ChatContextService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Exception;

class ChatController extends Controller
{
    protected AiChatService $chatService;
    protected ChatContextService $contextService;

    public function __construct(AiChatService $chatService, ChatContextService $contextService)
    {
        $this->chatService = $chatService;
        $this->contextService = $contextService;
    }

    /**
     * Get user's chat sessions
     */
    public function getSessions(Request $request): JsonResponse
    {
        try {
            $limit = min((int) $request->input('limit', 20), 50); // Max 50 sessions
            $result = $this->chatService->getUserSessions($limit);
            
            return response()->json($result);
        } catch (Exception $e) {
            Log::error('ChatController: Error getting sessions', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve chat sessions'
            ], 500);
        }
    }

    /**
     * Create a new chat session
     */
    public function createSession(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'provider' => 'sometimes|string|in:ollama,openwebui',
                'model' => 'sometimes|string|max:100',
                'title' => 'sometimes|string|max:100'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'details' => $validator->errors()
                ], 400);
            }

            $session = $this->chatService->createSession(
                $request->input('provider'),
                $request->input('model')
            );

            if (!$session) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to create chat session'
                ], 500);
            }

            // Update title if provided
            if ($request->has('title')) {
                $session->update(['title' => $request->input('title')]);
            }

            return response()->json([
                'success' => true,
                'session' => $session
            ], 201);
        } catch (Exception $e) {
            Log::error('ChatController: Error creating session', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to create chat session'
            ], 500);
        }
    }

    /**
     * Get session details with messages
     */
    public function getSession(Request $request, string $sessionId): JsonResponse
    {
        try {
            $limit = min((int) $request->input('limit', 50), 100); // Max 100 messages
            $result = $this->chatService->getSessionHistory($sessionId, $limit);
            
            return response()->json($result);
        } catch (Exception $e) {
            Log::error('ChatController: Error getting session', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId,
                'user_id' => Auth::id()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve session'
            ], 500);
        }
    }

    /**
     * Delete a chat session
     */
    public function deleteSession(string $sessionId): JsonResponse
    {
        try {
            $result = $this->chatService->deleteSession($sessionId);
            return response()->json($result);
        } catch (Exception $e) {
            Log::error('ChatController: Error deleting session', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId,
                'user_id' => Auth::id()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to delete session'
            ], 500);
        }
    }

    /**
     * Send a message to AI
     */
    public function sendMessage(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'message' => 'required|string|max:' . config('chat.security.max_message_length', 5000),
                'session_id' => 'sometimes|uuid|exists:chat_sessions,id',
                'provider' => 'sometimes|string|in:ollama,openwebui',
                'model' => 'sometimes|string|max:100',
                'context_type' => 'sometimes|string|in:' . implode(',', array_keys(config('chat.context.context_types', []))),
                'context_filters' => 'sometimes|array',
                'stream' => 'sometimes|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'details' => $validator->errors()
                ], 400);
            }

            $result = $this->chatService->sendMessage(
                $request->input('message'),
                $request->input('session_id'),
                $request->input('provider'),
                $request->input('model'),
                $request->input('context_filters', []),
                $request->input('context_type', 'general')
            );

            if (!$result['success']) {
                return response()->json($result, 500);
            }

            return response()->json($result);
        } catch (Exception $e) {
            Log::error('ChatController: Error sending message', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to send message'
            ], 500);
        }
    }

    /**
     * Get message history for a session
     */
    public function getMessages(Request $request, string $sessionId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'limit' => 'sometimes|integer|min:1|max:100',
                'offset' => 'sometimes|integer|min:0'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'details' => $validator->errors()
                ], 400);
            }

            $session = $this->chatService->getSession($sessionId);
            if (!$session) {
                return response()->json([
                    'success' => false,
                    'error' => 'Session not found'
                ], 404);
            }

            $limit = $request->input('limit', 50);
            $offset = $request->input('offset', 0);

            $messages = $session->messages()
                ->offset($offset)
                ->limit($limit)
                ->orderBy('created_at')
                ->get();

            return response()->json([
                'success' => true,
                'messages' => $messages,
                'pagination' => [
                    'limit' => $limit,
                    'offset' => $offset,
                    'total' => $session->messages()->count()
                ]
            ]);
        } catch (Exception $e) {
            Log::error('ChatController: Error getting messages', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId,
                'user_id' => Auth::id()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve messages'
            ], 500);
        }
    }

    /**
     * Switch AI provider for a session
     */
    public function switchProvider(Request $request, string $sessionId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'provider' => 'required|string|in:ollama,openwebui',
                'model' => 'sometimes|string|max:100'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'details' => $validator->errors()
                ], 400);
            }

            $result = $this->chatService->switchProvider(
                $sessionId,
                $request->input('provider'),
                $request->input('model')
            );

            return response()->json($result);
        } catch (Exception $e) {
            Log::error('ChatController: Error switching provider', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId,
                'user_id' => Auth::id()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to switch provider'
            ], 500);
        }
    }

    /**
     * Get available AI providers
     */
    public function getProviders(): JsonResponse
    {
        try {
            $result = $this->chatService->getAvailableProviders();
            return response()->json($result);
        } catch (Exception $e) {
            Log::error('ChatController: Error getting providers', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve providers'
            ], 500);
        }
    }

    /**
     * Get available models for a provider
     */
    public function getProviderModels(string $provider): JsonResponse
    {
        try {
            if (!in_array($provider, ['ollama', 'openwebui'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid provider'
                ], 400);
            }

            $result = $this->chatService->getAvailableProviders();
            
            if (!$result['success'] || !isset($result['providers'][$provider])) {
                return response()->json([
                    'success' => false,
                    'error' => 'Provider not available'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'provider' => $provider,
                'models' => $result['providers'][$provider]['models'] ?? [],
                'available' => $result['providers'][$provider]['available'] ?? false
            ]);
        } catch (Exception $e) {
            Log::error('ChatController: Error getting provider models', [
                'error' => $e->getMessage(),
                'provider' => $provider,
                'user_id' => Auth::id()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve provider models'
            ], 500);
        }
    }

    /**
     * Test provider connection
     */
    public function testProvider(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'provider' => 'required|string|in:ollama,openwebui',
                'base_url' => 'sometimes|url',
                'api_key' => 'sometimes|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'details' => $validator->errors()
                ], 400);
            }

            $provider = $request->input('provider');
            
            // This is a basic test - in production you might want more sophisticated testing
            $result = $this->chatService->getAvailableProviders();
            
            if ($result['success'] && isset($result['providers'][$provider])) {
                $providerData = $result['providers'][$provider];
                return response()->json([
                    'success' => true,
                    'provider' => $provider,
                    'available' => $providerData['available'],
                    'model_count' => count($providerData['models']),
                    'test_result' => $providerData['available'] ? 'Connection successful' : 'Connection failed'
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => 'Provider test failed'
            ], 500);
        } catch (Exception $e) {
            Log::error('ChatController: Error testing provider', [
                'error' => $e->getMessage(),
                'provider' => $request->input('provider'),
                'user_id' => Auth::id()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Provider test failed'
            ], 500);
        }
    }

    /**
     * Get available context types
     */
    public function getContextTypes(): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'context_types' => $this->contextService->getContextTypes()
            ]);
        } catch (Exception $e) {
            Log::error('ChatController: Error getting context types', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve context types'
            ], 500);
        }
    }

    /**
     * Clear context cache for current company
     */
    public function clearContextCache(): JsonResponse
    {
        try {
            $user = Auth::user();
            $result = $this->contextService->clearContextCache($user->company_id);
            
            return response()->json([
                'success' => $result,
                'message' => $result ? 'Context cache cleared successfully' : 'Failed to clear context cache'
            ]);
        } catch (Exception $e) {
            Log::error('ChatController: Error clearing context cache', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to clear context cache'
            ], 500);
        }
    }
}

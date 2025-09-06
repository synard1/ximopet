<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AiChatService;
use App\Services\ChatContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ChatMessageController extends Controller
{
    protected $chatService;
    protected $contextService;

    public function __construct(AiChatService $chatService, ChatContextService $contextService)
    {
        $this->chatService = $chatService;
        $this->contextService = $contextService;
    }

    /**
     * Send a message directly via API endpoint
     * This is a fallback for when Livewire communication fails
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendMessage(Request $request)
    {
        try {
            // Validate the request
            $validated = $request->validate([
                'message' => 'required|string|max:5000',
                'session_id' => 'nullable|string',
                'provider' => 'nullable|string|in:ollama,openwebui',
                'model' => 'nullable|string',
                'context_type' => 'nullable|string',
            ]);

            Log::debug('API ChatMessageController: sendMessage called', [
                'message' => $validated['message'],
                'user_id' => Auth::id(),
                'session_id' => $validated['session_id'] ?? null
            ]);

            // Process natural language to detect context
            $nlpResult = $this->contextService->processNaturalLanguageQuery($validated['message']);
            $contextType = $validated['context_type'] ?? $nlpResult['context_type'];
            $contextFilters = $nlpResult['criteria'] ?? [];

            // Get session ID - create a new one if needed
            $sessionId = $validated['session_id'] ?? null;
            if (!$sessionId) {
                // Use the configured default provider instead of hardcoding 'ollama'
                $provider = $validated['provider'] ?? config('chat.system.default_provider', 'openwebui');
                $model = $validated['model'] ?? null;

                $session = $this->chatService->createSession($provider, $model);
                $sessionId = $session->id;
            }

            // Send the message
            // Use the configured default provider instead of hardcoding 'ollama'
            $result = $this->chatService->sendMessage(
                $validated['message'],
                $sessionId,
                $validated['provider'] ?? config('chat.system.default_provider', 'openwebui'),
                $validated['model'] ?? null,
                $contextFilters,
                $contextType
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'session_id' => $result['session_id'] ?? $sessionId,
                    'user_message' => $result['user_message'],
                    'assistant_message' => $result['assistant_message'],
                    'processing_time' => $result['processing_time'],
                    'context_type' => $result['context_type']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'] ?? 'Failed to send message'
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('API ChatMessageController: Error sending message', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'An error occurred while sending the message: ' . $e->getMessage()
            ], 500);
        }
    }
}

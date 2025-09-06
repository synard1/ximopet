<?php

namespace App\Http\Controllers;

use App\Services\AiChatService;
use App\Services\ChatContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    protected $chatService;
    protected $contextService;

    public function __construct(AiChatService $chatService, ChatContextService $contextService)
    {
        $this->chatService = $chatService;
        $this->contextService = $contextService;
    }

    /**
     * Submit a message via standard form submission
     * This is a fallback for when Livewire communication fails
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function submit(Request $request)
    {
        try {
            // Validate the request
            $validated = $request->validate([
                'newMessage' => 'required|string|max:5000',
                'session_id' => 'nullable|string',
            ]);

            Log::debug('ChatController: submit called', [
                'message' => $validated['newMessage'],
                'user_id' => Auth::id()
            ]);

            // Process natural language to detect context
            $nlpResult = $this->contextService->processNaturalLanguageQuery($validated['newMessage']);
            $contextType = $nlpResult['context_type'];
            $contextFilters = $nlpResult['criteria'] ?? [];

            // Get or create session
            $sessionId = $validated['session_id'] ?? null;
            if (!$sessionId) {
                $session = $this->chatService->createSession('ollama', null);
                $sessionId = $session->id;
            }

            // Send the message
            $result = $this->chatService->sendMessage(
                $validated['newMessage'],
                $sessionId,
                'ollama', // Default provider
                null, // Default model
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
            Log::error('ChatController: Error submitting message', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'An error occurred while sending the message: ' . $e->getMessage()
            ], 500);
        }
    }
}
<?php

namespace App\AiChatV2\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class ChatController extends Controller
{
    /**
     * Display the main chat interface
     */
    public function index()
    {
        try {
            return view('ai-chat-v2.index');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Failed to load chat interface');
        }
    }

    /**
     * Display a specific chat session
     */
    public function show($sessionId)
    {
        try {
            // Validate that the session belongs to the user
            $session = \App\Models\ChatSession::where('id', $sessionId)
                ->where('user_id', Auth::id())
                ->firstOrFail();

            return view('ai-chat-v2.index', compact('sessionId'));
        } catch (Exception $e) {
            return redirect()->route('ai-chat-v2.index')->with('error', 'Chat session not found');
        }
    }

    /**
     * Create a new chat session
     */
    public function create()
    {
        try {
            // The actual session creation will happen in the Livewire component
            return redirect()->route('ai-chat-v2.index');
        } catch (Exception $e) {
            return redirect()->route('ai-chat-v2.index')->with('error', 'Failed to create new chat');
        }
    }
}
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AiChatTestController extends Controller
{
    public function testChatBubble()
    {
        Log::info('AiChatTestController: testChatBubble method called', [
            'user_id' => Auth::id(),
            'auth_check' => Auth::check()
        ]);
        
        if (!Auth::check()) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }
        
        // Try to instantiate the ChatBubble component
        try {
            $component = new \App\AiChatV2\Livewire\ChatBubble();
            Log::info('AiChatTestController: ChatBubble component instantiated successfully');
            
            return response()->json([
                'success' => true,
                'message' => 'ChatBubble component instantiated successfully',
                'user_id' => Auth::id()
            ]);
        } catch (\Exception $e) {
            Log::error('AiChatTestController: Error instantiating ChatBubble component', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
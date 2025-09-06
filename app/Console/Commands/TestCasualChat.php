<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AiChatService;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class TestCasualChat extends Command
{
    protected $signature = 'test:casual-chat {user_id?}';
    protected $description = 'Test casual conversation optimization in AI chat';

    public function handle()
    {
        $userId = $this->argument('user_id');

        if ($userId) {
            $user = User::find($userId);
        } else {
            $user = User::first();
        }

        if (!$user) {
            $this->error('No user found');
            return 1;
        }

        Auth::login($user);

        $this->info("Testing casual chat optimization with user: {$user->name}");

        $chatService = app(AiChatService::class);

        // Test simple casual conversations
        $casualMessages = [
            'hi',
            'hello',
            'how are you?',
            'thanks',
            'bye'
            // Removed 'ok' as it's too common in normal conversation and should not be treated as casual greeting
        ];

        foreach ($casualMessages as $message) {
            $this->info("\n=== Testing casual message: '$message' ===");

            try {
                $result = $chatService->sendMessage($message);

                if ($result['success']) {
                    $response = $result['assistant_message']->content;
                    $this->line("Response: " . $response);
                    $this->line("Processing time: " . number_format($result['processing_time'], 3) . "s");

                    // Check if response contains thinking patterns
                    if (strpos($response, '<think>') !== false ||
                        strpos($response, 'I should respond') !== false ||
                        strpos($response, 'Let me think') !== false ||
                        strpos($response, 'the user said') !== false) {
                        $this->warn("⚠️ Response still contains thinking patterns!");
                    } else {
                        $this->info("✅ Clean response - no thinking patterns detected");
                    }
                } else {
                    $this->error("Failed: " . $result['error']);
                }

            } catch (\Exception $e) {
                $this->error("Error: " . $e->getMessage());
            }
        }

        // Test data-related query to ensure it still works
        $this->info("\n=== Testing data query: 'berapa ternak?' ===");

        try {
            $result = $chatService->sendMessage('berapa ternak?');

            if ($result['success']) {
                $response = $result['assistant_message']->content;
                $this->line("Response: " . $response);
                $this->info("✅ Data query works correctly");
            } else {
                $this->error("Data query failed: " . $result['error']);
            }

        } catch (\Exception $e) {
            $this->error("Data query error: " . $e->getMessage());
        }

        $this->info("\n=== Test Complete ===");

        return 0;
    }
}

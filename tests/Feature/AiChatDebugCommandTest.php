<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\ChatSession;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;

class AiChatDebugCommandTest extends TestCase
{
    /**
     * Test the ai:chat-debug command basic functionality.
     *
     * @return void
     */
    public function test_ai_chat_debug_command()
    {
        // Create a user for testing
        $user = User::factory()->create();
        
        // Test the command with a simple message
        $this->artisan('ai:chat-debug', [
            'message' => 'Hello, test message',
            '--user' => $user->id,
            '--debug' => true
        ])
        ->expectsOutputToContain('Starting AI Chat Debug Command')
        ->expectsOutputToContain('Hello, test message')
        ->expectsOutputToContain('Authenticated as')
        ->assertExitCode(0);
    }

    /**
     * Test the ai:chat-status command.
     *
     * @return void
     */
    public function test_ai_chat_status_command()
    {
        $this->artisan('ai:chat-status', [
            '--debug' => true
        ])
        ->expectsOutputToContain('AI Chat Provider Status Check')
        ->expectsOutputToContain('Provider Status')
        ->assertExitCode(0);
    }

    /**
     * Test the ai:chat-sessions command.
     *
     * @return void
     */
    public function test_ai_chat_sessions_command()
    {
        // Create a user for testing
        $user = User::factory()->create();
        
        // Create a chat session for testing
        $session = ChatSession::factory()->create([
            'user_id' => $user->id,
            'title' => 'Test Session',
            'ai_provider' => 'ollama',
            'model_name' => 'llama2'
        ]);
        
        $this->artisan('ai:chat-sessions', [
            '--user' => $user->id,
            '--list' => true,
            '--debug' => true
        ])
        ->expectsOutputToContain('AI Chat Sessions')
        ->expectsOutputToContain('Test Session')
        ->assertExitCode(0);
    }

    /**
     * Test the ai:chat-benchmark command.
     *
     * @return void
     */
    public function test_ai_chat_benchmark_command()
    {
        $user = User::factory()->create();
        
        $this->artisan('ai:chat-benchmark', [
            '--user' => $user->id,
            '--requests' => 1,
            '--debug' => true
        ])
        ->expectsOutputToContain('AI Chat Performance Benchmark')
        ->expectsOutputToContain('Starting benchmark')
        ->assertExitCode(0);
    }
}
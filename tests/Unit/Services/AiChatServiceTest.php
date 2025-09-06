<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\AiChatService;
use App\Services\OllamaChatService;
use App\Services\OpenWebUIService;
use App\Services\ChatContextService;
use App\Models\ChatSession;
use App\Models\ChatMessage;
use App\Models\AiProvider;
use App\Models\User;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Mockery;

class AiChatServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AiChatService $aiChatService;
    protected $ollamaChatService;
    protected $openWebUIService;
    protected $chatContextService;
    protected User $user;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test company and user
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);

        // Mock services
        $this->ollamaChatService = Mockery::mock(OllamaChatService::class);
        $this->openWebUIService = Mockery::mock(OpenWebUIService::class);
        $this->chatContextService = Mockery::mock(ChatContextService::class);

        // Create AiChatService instance with mocked dependencies
        $this->aiChatService = new AiChatService(
            $this->ollamaChatService,
            $this->openWebUIService,
            $this->chatContextService
        );

        // Set authenticated user
        Auth::login($this->user);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_can_create_chat_session()
    {
        // Create AI provider
        $provider = AiProvider::factory()->create([
            'company_id' => $this->company->id,
            'type' => 'ollama',
            'is_active' => true
        ]);

        $sessionData = [
            'title' => 'Test Session',
            'ai_provider_id' => $provider->id
        ];

        $session = $this->aiChatService->createSession($sessionData);

        $this->assertInstanceOf(ChatSession::class, $session);
        $this->assertEquals('Test Session', $session->title);
        $this->assertEquals($this->user->id, $session->user_id);
        $this->assertEquals($this->company->id, $session->company_id);
        $this->assertEquals($provider->id, $session->ai_provider_id);
    }

    public function test_can_send_message_with_ollama_provider()
    {
        // Create AI provider
        $provider = AiProvider::factory()->create([
            'company_id' => $this->company->id,
            'type' => 'ollama',
            'is_active' => true
        ]);

        // Create chat session
        $session = ChatSession::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'ai_provider_id' => $provider->id
        ]);

        // Mock context service
        $this->chatContextService
            ->shouldReceive('buildContext')
            ->once()
            ->with($this->user->id, $this->company->id)
            ->andReturn('Farm context data');

        // Mock Ollama service
        $this->ollamaChatService
            ->shouldReceive('sendMessage')
            ->once()
            ->with('Hello AI', 'Farm context data')
            ->andReturn([
                'response' => 'Hello! How can I help you with your farm?',
                'processing_time' => 1.5,
                'tokens_used' => 25
            ]);

        $response = $this->aiChatService->sendMessage($session->id, 'Hello AI');

        $this->assertIsArray($response);
        $this->assertArrayHasKey('user_message', $response);
        $this->assertArrayHasKey('ai_response', $response);
        $this->assertEquals('Hello AI', $response['user_message']->content);
        $this->assertEquals('Hello! How can I help you with your farm?', $response['ai_response']->content);
    }

    public function test_can_send_message_with_openwebui_provider()
    {
        // Create AI provider
        $provider = AiProvider::factory()->create([
            'company_id' => $this->company->id,
            'type' => 'openwebui',
            'is_active' => true
        ]);

        // Create chat session
        $session = ChatSession::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'ai_provider_id' => $provider->id
        ]);

        // Mock context service
        $this->chatContextService
            ->shouldReceive('buildContext')
            ->once()
            ->with($this->user->id, $this->company->id)
            ->andReturn('Farm context data');

        // Mock OpenWebUI service
        $this->openWebUIService
            ->shouldReceive('sendMessage')
            ->once()
            ->andReturn([
                'response' => 'Hello! How can I help you with your farm?',
                'processing_time' => 2.1,
                'tokens_used' => 30
            ]);

        $response = $this->aiChatService->sendMessage($session->id, 'Hello AI');

        $this->assertIsArray($response);
        $this->assertArrayHasKey('user_message', $response);
        $this->assertArrayHasKey('ai_response', $response);
    }

    public function test_can_get_user_sessions()
    {
        // Create AI provider
        $provider = AiProvider::factory()->create([
            'company_id' => $this->company->id,
            'type' => 'ollama',
            'is_active' => true
        ]);

        // Create multiple sessions
        ChatSession::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'ai_provider_id' => $provider->id
        ]);

        // Create session for different user
        $otherUser = User::factory()->create(['company_id' => $this->company->id]);
        ChatSession::factory()->create([
            'user_id' => $otherUser->id,
            'company_id' => $this->company->id,
            'ai_provider_id' => $provider->id
        ]);

        $sessions = $this->aiChatService->getUserSessions();

        $this->assertCount(3, $sessions);
        foreach ($sessions as $session) {
            $this->assertEquals($this->user->id, $session->user_id);
        }
    }

    public function test_can_get_session_messages()
    {
        // Create AI provider
        $provider = AiProvider::factory()->create([
            'company_id' => $this->company->id,
            'type' => 'ollama',
            'is_active' => true
        ]);

        // Create chat session
        $session = ChatSession::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'ai_provider_id' => $provider->id
        ]);

        // Create messages
        ChatMessage::factory()->count(5)->create([
            'chat_session_id' => $session->id
        ]);

        $messages = $this->aiChatService->getSessionMessages($session->id);

        $this->assertCount(5, $messages);
    }

    public function test_can_switch_provider()
    {
        // Create AI providers
        $ollamaProvider = AiProvider::factory()->create([
            'company_id' => $this->company->id,
            'type' => 'ollama',
            'is_active' => true
        ]);

        $openwebuiProvider = AiProvider::factory()->create([
            'company_id' => $this->company->id,
            'type' => 'openwebui',
            'is_active' => true
        ]);

        // Create chat session with Ollama
        $session = ChatSession::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'ai_provider_id' => $ollamaProvider->id
        ]);

        // Switch to OpenWebUI
        $updatedSession = $this->aiChatService->switchProvider($session->id, $openwebuiProvider->id);

        $this->assertEquals($openwebuiProvider->id, $updatedSession->ai_provider_id);
    }

    public function test_throws_exception_for_invalid_session()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Chat session not found');

        $this->aiChatService->sendMessage('invalid-session-id', 'Hello');
    }

    public function test_throws_exception_for_unauthorized_session_access()
    {
        // Create different user and company
        $otherCompany = Company::factory()->create();
        $otherUser = User::factory()->create(['company_id' => $otherCompany->id]);

        $provider = AiProvider::factory()->create([
            'company_id' => $otherCompany->id,
            'type' => 'ollama',
            'is_active' => true
        ]);

        // Create session for other user
        $session = ChatSession::factory()->create([
            'user_id' => $otherUser->id,
            'company_id' => $otherCompany->id,
            'ai_provider_id' => $provider->id
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unauthorized access to chat session');

        $this->aiChatService->sendMessage($session->id, 'Hello');
    }

    public function test_handles_ai_service_failure_gracefully()
    {
        // Create AI provider
        $provider = AiProvider::factory()->create([
            'company_id' => $this->company->id,
            'type' => 'ollama',
            'is_active' => true
        ]);

        // Create chat session
        $session = ChatSession::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'ai_provider_id' => $provider->id
        ]);

        // Mock context service
        $this->chatContextService
            ->shouldReceive('buildContext')
            ->once()
            ->andReturn('Farm context data');

        // Mock Ollama service to throw exception
        $this->ollamaChatService
            ->shouldReceive('sendMessage')
            ->once()
            ->andThrow(new \Exception('AI service unavailable'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('AI service unavailable');

        $this->aiChatService->sendMessage($session->id, 'Hello AI');
    }

    public function test_caches_expensive_operations()
    {
        Cache::shouldReceive('remember')
            ->once()
            ->with(
                Mockery::pattern('/user_sessions_' . $this->user->id . '/'),
                Mockery::any(),
                Mockery::type('callable')
            )
            ->andReturn(collect());

        $this->aiChatService->getUserSessions();
    }

    public function test_validates_message_length()
    {
        $provider = AiProvider::factory()->create([
            'company_id' => $this->company->id,
            'type' => 'ollama',
            'is_active' => true
        ]);

        $session = ChatSession::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'ai_provider_id' => $provider->id
        ]);

        // Set max message length in config
        Config::set('chat.limits.max_message_length', 100);

        $longMessage = str_repeat('a', 101);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Message too long');

        $this->aiChatService->sendMessage($session->id, $longMessage);
    }
}
<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\AiProvider;
use App\Models\ChatSession;
use App\Models\ChatMessage;
use App\Services\AiChatService;
use App\Services\OllamaChatService;
use App\Services\OpenWebUIService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Mockery;

class ChatApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;
    protected AiProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        
        $this->provider = AiProvider::factory()->create([
            'company_id' => $this->company->id,
            'type' => 'ollama',
            'is_active' => true
        ]);

        // Authenticate user
        Sanctum::actingAs($this->user);
    }

    public function test_can_get_user_chat_sessions()
    {
        // Create chat sessions
        ChatSession::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'ai_provider_id' => $this->provider->id
        ]);

        $response = $this->getJson('/api/chat/sessions');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'created_at',
                        'updated_at',
                        'last_activity',
                        'ai_provider' => [
                            'id',
                            'name',
                            'type'
                        ]
                    ]
                ]
            ])
            ->assertJson([
                'success' => true
            ]);

        $this->assertCount(3, $response->json('data'));
    }

    public function test_can_create_new_chat_session()
    {
        $sessionData = [
            'title' => 'Test Chat Session',
            'ai_provider_id' => $this->provider->id
        ];

        $response = $this->postJson('/api/chat/sessions', $sessionData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'title',
                    'user_id',
                    'company_id',
                    'ai_provider_id'
                ]
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'title' => 'Test Chat Session',
                    'user_id' => $this->user->id,
                    'company_id' => $this->company->id
                ]
            ]);

        $this->assertDatabaseHas('chat_sessions', [
            'title' => 'Test Chat Session',
            'user_id' => $this->user->id,
            'company_id' => $this->company->id
        ]);
    }

    public function test_cannot_create_session_with_invalid_provider()
    {
        $sessionData = [
            'title' => 'Test Chat Session',
            'ai_provider_id' => 'invalid-provider-id'
        ];

        $response = $this->postJson('/api/chat/sessions', $sessionData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ai_provider_id']);
    }

    public function test_can_send_message_to_chat_session()
    {
        // Mock AI service response
        $mockAiService = Mockery::mock(AiChatService::class);
        $mockAiService->shouldReceive('sendMessage')
            ->once()
            ->andReturn([
                'user_message' => (object) [
                    'id' => 'user-msg-id',
                    'content' => 'Hello AI',
                    'type' => 'user',
                    'created_at' => now()
                ],
                'ai_response' => (object) [
                    'id' => 'ai-msg-id',
                    'content' => 'Hello! How can I help you?',
                    'type' => 'assistant',
                    'created_at' => now(),
                    'processing_time' => 1.5,
                    'tokens_used' => 25
                ]
            ]);

        $this->app->instance(AiChatService::class, $mockAiService);

        $session = ChatSession::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'ai_provider_id' => $this->provider->id
        ]);

        $messageData = [
            'message' => 'Hello AI'
        ];

        $response = $this->postJson("/api/chat/sessions/{$session->id}/messages", $messageData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user_message' => [
                        'id',
                        'content',
                        'type',
                        'created_at'
                    ],
                    'ai_response' => [
                        'id',
                        'content',
                        'type',
                        'created_at',
                        'processing_time',
                        'tokens_used'
                    ]
                ]
            ]);
    }

    public function test_cannot_send_empty_message()
    {
        $session = ChatSession::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'ai_provider_id' => $this->provider->id
        ]);

        $response = $this->postJson("/api/chat/sessions/{$session->id}/messages", [
            'message' => ''
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    }

    public function test_cannot_send_message_to_unauthorized_session()
    {
        // Create session for different user
        $otherUser = User::factory()->create(['company_id' => $this->company->id]);
        $session = ChatSession::factory()->create([
            'user_id' => $otherUser->id,
            'company_id' => $this->company->id,
            'ai_provider_id' => $this->provider->id
        ]);

        $response = $this->postJson("/api/chat/sessions/{$session->id}/messages", [
            'message' => 'Hello'
        ]);

        $response->assertStatus(403);
    }

    public function test_can_get_session_messages()
    {
        $session = ChatSession::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'ai_provider_id' => $this->provider->id
        ]);

        // Create messages
        ChatMessage::factory()->count(5)->create([
            'chat_session_id' => $session->id
        ]);

        $response = $this->getJson("/api/chat/sessions/{$session->id}/messages");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'content',
                        'type',
                        'created_at',
                        'processing_time',
                        'tokens_used'
                    ]
                ]
            ]);

        $this->assertCount(5, $response->json('data'));
    }

    public function test_can_switch_ai_provider()
    {
        $newProvider = AiProvider::factory()->create([
            'company_id' => $this->company->id,
            'type' => 'openwebui',
            'is_active' => true
        ]);

        $session = ChatSession::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'ai_provider_id' => $this->provider->id
        ]);

        $response = $this->patchJson("/api/chat/sessions/{$session->id}/provider", [
            'ai_provider_id' => $newProvider->id
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'ai_provider_id' => $newProvider->id
                ]
            ]);

        $this->assertDatabaseHas('chat_sessions', [
            'id' => $session->id,
            'ai_provider_id' => $newProvider->id
        ]);
    }

    public function test_can_delete_chat_session()
    {
        $session = ChatSession::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'ai_provider_id' => $this->provider->id
        ]);

        // Create some messages
        ChatMessage::factory()->count(3)->create([
            'chat_session_id' => $session->id
        ]);

        $response = $this->deleteJson("/api/chat/sessions/{$session->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Chat session deleted successfully'
            ]);

        $this->assertSoftDeleted('chat_sessions', [
            'id' => $session->id
        ]);
    }

    public function test_can_get_available_ai_providers()
    {
        // Create multiple providers
        AiProvider::factory()->create([
            'company_id' => $this->company->id,
            'type' => 'openwebui',
            'is_active' => true
        ]);

        $response = $this->getJson('/api/chat/providers');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'type',
                        'is_active'
                    ]
                ]
            ]);

        $this->assertCount(2, $response->json('data')); // Including the one from setUp
    }

    public function test_can_test_ai_provider_connection()
    {
        // Mock successful connection test
        $mockOllamaService = Mockery::mock(OllamaChatService::class);
        $mockOllamaService->shouldReceive('testConnection')
            ->once()
            ->andReturn([
                'success' => true,
                'message' => 'Connection successful'
            ]);

        $this->app->instance(OllamaChatService::class, $mockOllamaService);

        $response = $this->postJson("/api/chat/providers/{$this->provider->id}/test");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Connection successful'
            ]);
    }

    public function test_handles_ai_provider_connection_failure()
    {
        // Mock failed connection test
        $mockOllamaService = Mockery::mock(OllamaChatService::class);
        $mockOllamaService->shouldReceive('testConnection')
            ->once()
            ->andReturn([
                'success' => false,
                'message' => 'Connection failed'
            ]);

        $this->app->instance(OllamaChatService::class, $mockOllamaService);

        $response = $this->postJson("/api/chat/providers/{$this->provider->id}/test");

        $response->assertStatus(200)
            ->assertJson([
                'success' => false,
                'message' => 'Connection failed'
            ]);
    }

    public function test_rate_limiting_middleware_blocks_excessive_requests()
    {
        $session = ChatSession::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'ai_provider_id' => $this->provider->id
        ]);

        // Mock the AI service to avoid actual API calls
        $mockAiService = Mockery::mock(AiChatService::class);
        $mockAiService->shouldReceive('sendMessage')->andReturn([
            'user_message' => (object) ['content' => 'test'],
            'ai_response' => (object) ['content' => 'test response']
        ]);

        $this->app->instance(AiChatService::class, $mockAiService);

        // Send many requests quickly
        for ($i = 0; $i < 15; $i++) {
            $response = $this->postJson("/api/chat/sessions/{$session->id}/messages", [
                'message' => "Test message {$i}"
            ]);

            if ($i >= 10) { // Assuming rate limit is 10 per minute
                $response->assertStatus(429); // Too Many Requests
                break;
            }
        }
    }

    public function test_requires_authentication()
    {
        // Remove authentication
        $this->app['auth']->guard()->logout();

        $response = $this->getJson('/api/chat/sessions');

        $response->assertStatus(401);
    }

    public function test_validates_message_length()
    {
        $session = ChatSession::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'ai_provider_id' => $this->provider->id
        ]);

        // Send very long message
        $longMessage = str_repeat('a', 5001); // Assuming max is 5000

        $response = $this->postJson("/api/chat/sessions/{$session->id}/messages", [
            'message' => $longMessage
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    }

    public function test_prevents_xss_in_messages()
    {
        $session = ChatSession::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'ai_provider_id' => $this->provider->id
        ]);

        $maliciousMessage = '<script>alert("xss")</script>Hello';

        // Mock AI service to see what it receives
        $mockAiService = Mockery::mock(AiChatService::class);
        $mockAiService->shouldReceive('sendMessage')
            ->once()
            ->with($session->id, Mockery::pattern('/^(?!.*<script>).*$/'))
            ->andReturn([
                'user_message' => (object) ['content' => 'Sanitized message'],
                'ai_response' => (object) ['content' => 'Response']
            ]);

        $this->app->instance(AiChatService::class, $mockAiService);

        $response = $this->postJson("/api/chat/sessions/{$session->id}/messages", [
            'message' => $maliciousMessage
        ]);

        $response->assertStatus(200);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
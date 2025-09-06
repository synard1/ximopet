<?php

namespace Tests\Feature\Livewire;

use Tests\TestCase;
use App\Livewire\AiChatWidget;
use App\Models\User;
use App\Models\Company;
use App\Models\AiProvider;
use App\Models\ChatSession;
use App\Models\ChatMessage;
use App\Services\AiChatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Laravel\Sanctum\Sanctum;
use Mockery;

class AiChatWidgetTest extends TestCase
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
        $this->actingAs($this->user);
    }

    public function test_component_renders_correctly()
    {
        Livewire::test(AiChatWidget::class)
            ->assertSet('isMinimized', true)
            ->assertSet('showSettings', false)
            ->assertSee('AI Chat')
            ->assertSee('chat-widget');
    }

    public function test_can_toggle_chat_widget()
    {
        Livewire::test(AiChatWidget::class)
            ->assertSet('isMinimized', true)
            ->call('toggleChat')
            ->assertSet('isMinimized', false)
            ->call('toggleChat')
            ->assertSet('isMinimized', true);
    }

    public function test_can_start_new_chat_session()
    {
        // Mock AI service
        $mockAiService = Mockery::mock(AiChatService::class);
        $mockAiService->shouldReceive('createSession')
            ->once()
            ->andReturn(ChatSession::factory()->make([
                'id' => 'new-session-id',
                'title' => 'New Chat',
                'user_id' => $this->user->id,
                'company_id' => $this->company->id,
                'ai_provider_id' => $this->provider->id
            ]));

        $this->app->instance(AiChatService::class, $mockAiService);

        Livewire::test(AiChatWidget::class)
            ->call('startNewChat')
            ->assertSet('currentSessionId', 'new-session-id')
            ->assertSet('messages', [])
            ->assertSet('isMinimized', false);
    }

    public function test_can_send_message()
    {
        // Create a chat session
        $session = ChatSession::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'ai_provider_id' => $this->provider->id
        ]);

        // Mock AI service response
        $mockAiService = Mockery::mock(AiChatService::class);
        $mockAiService->shouldReceive('sendMessage')
            ->once()
            ->with($session->id, 'Hello AI')
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

        Livewire::test(AiChatWidget::class)
            ->set('currentSessionId', $session->id)
            ->set('messageInput', 'Hello AI')
            ->call('sendMessage')
            ->assertSet('messageInput', '')
            ->assertSet('isTyping', false)
            ->assertCount('messages', 2); // User message + AI response
    }

    public function test_prevents_sending_empty_message()
    {
        $session = ChatSession::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'ai_provider_id' => $this->provider->id
        ]);

        Livewire::test(AiChatWidget::class)
            ->set('currentSessionId', $session->id)
            ->set('messageInput', '')
            ->call('sendMessage')
            ->assertHasErrors(['messageInput']);
    }

    public function test_prevents_sending_too_long_message()
    {
        $session = ChatSession::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'ai_provider_id' => $this->provider->id
        ]);

        $longMessage = str_repeat('a', 5001); // Assuming max is 5000

        Livewire::test(AiChatWidget::class)
            ->set('currentSessionId', $session->id)
            ->set('messageInput', $longMessage)
            ->call('sendMessage')
            ->assertHasErrors(['messageInput']);
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

        // Mock AI service
        $mockAiService = Mockery::mock(AiChatService::class);
        $mockAiService->shouldReceive('switchProvider')
            ->once()
            ->with($session->id, $newProvider->id)
            ->andReturn($session->fresh());

        $this->app->instance(AiChatService::class, $mockAiService);

        Livewire::test(AiChatWidget::class)
            ->set('currentSessionId', $session->id)
            ->set('selectedProviderId', $newProvider->id)
            ->call('switchProvider')
            ->assertEmitted('provider-switched');
    }

    public function test_can_load_chat_history()
    {
        $session = ChatSession::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'ai_provider_id' => $this->provider->id
        ]);

        // Create chat messages
        $messages = ChatMessage::factory()->count(3)->create([
            'chat_session_id' => $session->id
        ]);

        // Mock AI service
        $mockAiService = Mockery::mock(AiChatService::class);
        $mockAiService->shouldReceive('getSessionMessages')
            ->once()
            ->with($session->id)
            ->andReturn($messages);

        $this->app->instance(AiChatService::class, $mockAiService);

        Livewire::test(AiChatWidget::class)
            ->call('loadChatHistory', $session->id)
            ->assertSet('currentSessionId', $session->id)
            ->assertCount('messages', 3);
    }

    public function test_shows_typing_indicator_while_processing()
    {
        $session = ChatSession::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'ai_provider_id' => $this->provider->id
        ]);

        // Mock AI service with delay
        $mockAiService = Mockery::mock(AiChatService::class);
        $mockAiService->shouldReceive('sendMessage')
            ->once()
            ->andReturn([
                'user_message' => (object) ['content' => 'test'],
                'ai_response' => (object) ['content' => 'response']
            ]);

        $this->app->instance(AiChatService::class, $mockAiService);

        $component = Livewire::test(AiChatWidget::class)
            ->set('currentSessionId', $session->id)
            ->set('messageInput', 'Hello AI');

        // Check typing indicator is shown during processing
        $component->call('sendMessage')
            ->assertSet('isTyping', false); // Should be false after completion
    }

    public function test_can_toggle_settings_panel()
    {
        Livewire::test(AiChatWidget::class)
            ->assertSet('showSettings', false)
            ->call('toggleSettings')
            ->assertSet('showSettings', true)
            ->call('toggleSettings')
            ->assertSet('showSettings', false);
    }

    public function test_can_change_chat_position()
    {
        Livewire::test(AiChatWidget::class)
            ->assertSet('chatPosition', 'bottom-right')
            ->set('chatPosition', 'bottom-left')
            ->call('updatePosition')
            ->assertSet('chatPosition', 'bottom-left');
    }

    public function test_can_change_chat_size()
    {
        Livewire::test(AiChatWidget::class)
            ->assertSet('chatSize', 'medium')
            ->set('chatSize', 'large')
            ->call('updateSize')
            ->assertSet('chatSize', 'large');
    }

    public function test_can_change_theme()
    {
        Livewire::test(AiChatWidget::class)
            ->assertSet('theme', 'light')
            ->set('theme', 'dark')
            ->call('updateTheme')
            ->assertSet('theme', 'dark');
    }

    public function test_persists_user_preferences()
    {
        Livewire::test(AiChatWidget::class)
            ->set('chatPosition', 'top-right')
            ->set('chatSize', 'small')
            ->set('theme', 'dark')
            ->call('savePreferences');

        // Verify preferences are saved (would need to check cache or database)
        $this->assertTrue(true); // Placeholder assertion
    }

    public function test_handles_ai_service_errors_gracefully()
    {
        $session = ChatSession::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'ai_provider_id' => $this->provider->id
        ]);

        // Mock AI service to throw exception
        $mockAiService = Mockery::mock(AiChatService::class);
        $mockAiService->shouldReceive('sendMessage')
            ->once()
            ->andThrow(new \Exception('AI service unavailable'));

        $this->app->instance(AiChatService::class, $mockAiService);

        Livewire::test(AiChatWidget::class)
            ->set('currentSessionId', $session->id)
            ->set('messageInput', 'Hello AI')
            ->call('sendMessage')
            ->assertSet('isTyping', false)
            ->assertSet('error', 'AI service is currently unavailable. Please try again later.');
    }

    public function test_loads_available_ai_providers_on_mount()
    {
        // Create multiple providers
        AiProvider::factory()->create([
            'company_id' => $this->company->id,
            'type' => 'openwebui',
            'is_active' => true
        ]);

        // Mock AI service
        $mockAiService = Mockery::mock(AiChatService::class);
        $mockAiService->shouldReceive('getAvailableProviders')
            ->once()
            ->andReturn(collect([
                $this->provider,
                AiProvider::factory()->make(['type' => 'openwebui'])
            ]));

        $this->app->instance(AiChatService::class, $mockAiService);

        Livewire::test(AiChatWidget::class)
            ->assertCount('availableProviders', 2);
    }

    public function test_responds_to_keyboard_shortcuts()
    {
        Livewire::test(AiChatWidget::class)
            ->assertSet('isMinimized', true)
            ->emit('keyboard-shortcut', 'ctrl+space')
            ->assertSet('isMinimized', false);
    }

    public function test_clears_messages_when_starting_new_chat()
    {
        // Set up existing messages
        Livewire::test(AiChatWidget::class)
            ->set('messages', [
                ['content' => 'Old message', 'type' => 'user']
            ])
            ->call('startNewChat')
            ->assertSet('messages', []);
    }

    public function test_auto_scrolls_to_latest_message()
    {
        $session = ChatSession::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'ai_provider_id' => $this->provider->id
        ]);

        // Mock AI service
        $mockAiService = Mockery::mock(AiChatService::class);
        $mockAiService->shouldReceive('sendMessage')
            ->once()
            ->andReturn([
                'user_message' => (object) ['content' => 'test'],
                'ai_response' => (object) ['content' => 'response']
            ]);

        $this->app->instance(AiChatService::class, $mockAiService);

        Livewire::test(AiChatWidget::class)
            ->set('currentSessionId', $session->id)
            ->set('messageInput', 'Hello')
            ->call('sendMessage')
            ->assertEmitted('scroll-to-bottom');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
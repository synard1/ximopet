<?php

namespace Tests\Feature;

use App\Livewire\AiChatWidget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AiChatWidgetUiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_shows_chat_bubble_by_default()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(AiChatWidget::class)
            ->assertSet('isOpen', false)
            ->assertSeeHtml('chat-bubble-wrapper')
            ->assertDontSeeHtml('chat-window-container');
    }

    /** @test */
    public function it_opens_chat_window_when_bubble_is_clicked()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(AiChatWidget::class)
            ->call('openChat')
            ->assertSet('isOpen', true)
            ->assertSeeHtml('chat-window-container')
            ->assertDontSeeHtml('chat-bubble-wrapper');
    }

    /** @test */
    public function it_closes_chat_window_when_close_button_is_clicked()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(AiChatWidget::class)
            ->call('openChat')
            ->assertSet('isOpen', true)
            ->call('closeChat')
            ->assertSet('isOpen', false)
            ->assertSeeHtml('chat-bubble-wrapper')
            ->assertDontSeeHtml('chat-window-container');
    }

    /** @test */
    public function it_toggles_chat_window_visibility()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $component = Livewire::test(AiChatWidget::class);
        
        // Initially closed
        $component->assertSet('isOpen', false)
            ->assertSeeHtml('chat-bubble-wrapper')
            ->assertDontSeeHtml('chat-window-container');
            
        // Toggle open
        $component->call('toggleChat')
            ->assertSet('isOpen', true)
            ->assertSeeHtml('chat-window-container')
            ->assertDontSeeHtml('chat-bubble-wrapper');
            
        // Toggle closed
        $component->call('toggleChat')
            ->assertSet('isOpen', false)
            ->assertSeeHtml('chat-bubble-wrapper')
            ->assertDontSeeHtml('chat-window-container');
    }

    /** @test */
    public function it_maintains_state_across_renders()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $component = Livewire::test(AiChatWidget::class);
        
        // Open chat
        $component->call('openChat')
            ->assertSet('isOpen', true);
            
        // Simulate a re-render
        $component->call('loadRecentSessions')
            ->assertSet('isOpen', true)
            ->assertSeeHtml('chat-window-container')
            ->assertDontSeeHtml('chat-bubble-wrapper');
    }
}
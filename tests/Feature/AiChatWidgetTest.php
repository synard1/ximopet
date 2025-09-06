<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

class AiChatWidgetTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $company;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a company
        $this->company = Company::factory()->create();

        // Create a user
        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);

        // Authenticate the user
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_can_open_and_close_chat()
    {
        Livewire::test('ai-chat-widget')
            ->assertSet('isOpen', false)
            ->call('openChat')
            ->assertSet('isOpen', true)
            ->call('closeChat')
            ->assertSet('isOpen', false);
    }

    /** @test */
    public function it_can_toggle_minimize()
    {
        Livewire::test('ai-chat-widget')
            ->call('openChat')
            ->assertSet('isMinimized', false)
            ->call('toggleMinimize')
            ->assertSet('isMinimized', true)
            ->call('toggleMinimize')
            ->assertSet('isMinimized', false);
    }

    /** @test */
    public function it_can_show_and_hide_history()
    {
        Livewire::test('ai-chat-widget')
            ->call('openChat')
            ->call('loadRecentSessions')
            ->assertSet('sessions', [])
            ->assertSee('No Chat History');
    }

    /** @test */
    public function it_can_show_and_hide_settings()
    {
        Livewire::test('ai-chat-widget')
            ->call('openChat')
            ->assertSet('showSettings', false)
            ->call('switchProvider')
            ->assertSet('showSettings', false);
    }

    /** @test */
    public function it_can_show_and_hide_templates()
    {
        Livewire::test('ai-chat-widget')
            ->call('openChat')
            ->assertSet('showTemplates', false)
            ->call('toggleTemplates')
            ->assertSet('showTemplates', true)
            ->call('toggleTemplates')
            ->assertSet('showTemplates', false);
    }

    /** @test */
    public function it_can_show_and_hide_search()
    {
        Livewire::test('ai-chat-widget')
            ->call('openChat')
            ->assertSet('showSearch', false)
            ->call('toggleSearch')
            ->assertSet('showSearch', true)
            ->call('toggleSearch')
            ->assertSet('showSearch', false);
    }

    /** @test */
    public function it_can_use_chat_templates()
    {
        Livewire::test('ai-chat-widget')
            ->call('openChat')
            ->call('useTemplate', 'What is the current status of batch {batch_number}?')
            ->assertSet('newMessage', 'What is the current status of batch {batch_number}?')
            ->assertSet('showTemplates', false);
    }

    /** @test */
    public function it_can_export_chat_in_different_formats()
    {
        Livewire::test('ai-chat-widget')
            ->call('openChat')
            ->assertSet('exportFormat', 'json')
            ->call('exportChatAs', 'csv')
            ->assertSet('exportFormat', 'csv')
            ->call('exportChatAs', 'pdf')
            ->assertSet('exportFormat', 'pdf');
    }

    /** @test */
    public function it_loads_chat_templates()
    {
        Livewire::test('ai-chat-widget')
            ->call('openChat')
            ->assertSet('chatTemplates', function ($templates) {
                return is_array($templates) && count($templates) > 0;
            });
    }
}
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatWidgetTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function chat_widget_can_send_message()
    {
        // Create a user
        $user = User::factory()->create();
        
        // Acting as the user
        $this->actingAs($user);
        
        // Test that the chat widget loads
        $response = $this->get('/');
        $response->assertStatus(200);
        
        // Test that we can send a message
        $response = $this->post('/livewire/message/ai-chat-widget', [
            'newMessage' => 'Hello, this is a test message',
            'action' => 'sendMessage'
        ]);
        
        // Assert that the response is successful
        $response->assertStatus(200);
    }
}
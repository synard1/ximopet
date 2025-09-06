<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\ChatSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class AiChatV2Test extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a user for testing
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_can_create_a_new_chat_session()
    {
        $this->actingAs($this->user);
        
        $response = $this->post(route('ai-chat-v2.create'));
        
        $response->assertStatus(302); // Redirect back to index
    }

    /** @test */
    public function it_can_access_the_chat_interface()
    {
        $this->actingAs($this->user);
        
        $response = $this->get(route('ai-chat-v2.index'));
        
        $response->assertStatus(200);
        $response->assertViewIs('ai-chat-v2.index');
    }

    /** @test */
    public function it_can_access_the_test_page()
    {
        $this->actingAs($this->user);
        
        $response = $this->get(route('ai-chat-v2.test'));
        
        $response->assertStatus(200);
        $response->assertSee('AI Chat V2 Test');
    }

    /** @test */
    public function it_can_access_the_demo_page()
    {
        $this->actingAs($this->user);
        
        $response = $this->get(route('ai-chat-v2.demo'));
        
        $response->assertStatus(200);
        $response->assertSee('AI Chat V2 Demo');
    }

    /** @test */
    public function it_requires_authentication()
    {
        $response = $this->get(route('ai-chat-v2.index'));
        
        $response->assertRedirect('/login');
    }

    /** @test */
    public function it_can_load_user_settings()
    {
        $this->actingAs($this->user);
        
        // Create user settings
        \DB::table('user_chat_settings_v2')->insert([
            'user_id' => $this->user->id,
            'settings' => json_encode([
                'provider' => 'openwebui',
                'model' => 'llama3.2:3b'
            ]),
            'version' => '2.0',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $response = $this->get(route('ai-chat-v2.index'));
        
        $response->assertStatus(200);
    }
}
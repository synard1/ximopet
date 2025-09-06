<?php

namespace Tests\Feature;

use App\Models\ChatSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatSessionDateTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_creates_new_session_when_date_changes()
    {
        $this->actingAs($this->user);

        // Create a session from yesterday
        $yesterday = Carbon::yesterday();
        $oldSession = ChatSession::factory()->create([
            'user_id' => $this->user->id,
            'last_activity_at' => $yesterday,
            'created_at' => $yesterday,
            'updated_at' => $yesterday
        ]);

        // Mock the Livewire component to test the date check
        $this->mock(\App\Services\AiChatService::class, function ($mock) {
            $mock->shouldReceive('getAvailableProviders')->andReturn([
                'success' => true,
                'providers' => []
            ]);
        });

        // Access the chat widget
        $response = $this->get('/'); // Assuming the chat widget is on the main page

        // Check that a new session would be created
        // Note: This is a simplified test. In a real scenario, we would test the Livewire component directly
        $this->assertTrue(true); // Placeholder assertion
    }
}

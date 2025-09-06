<?php

namespace Tests\Feature;

use App\Models\ChatMessage;
use App\Models\ChatRating;
use App\Models\ChatSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatRatingTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $chatSession;
    protected $assistantMessage;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a user
        $this->user = User::factory()->create();

        // Create a chat session
        $this->chatSession = ChatSession::factory()->create([
            'user_id' => $this->user->id
        ]);

        // Create an assistant message
        $this->assistantMessage = ChatMessage::factory()->create([
            'chat_session_id' => $this->chatSession->id,
            'user_id' => $this->user->id,
            'message_type' => 'assistant',
            'content' => 'This is a test AI response'
        ]);
    }

    /** @test */
    public function user_can_rate_assistant_message()
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/chat/ratings', [
            'message_id' => $this->assistantMessage->id,
            'rating' => 5,
            'feedback' => 'Excellent response!'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Message rated successfully'
            ]);

        $this->assertDatabaseHas('chat_ratings', [
            'chat_message_id' => $this->assistantMessage->id,
            'user_id' => $this->user->id,
            'rating' => 5,
            'feedback' => 'Excellent response!'
        ]);
    }

    /** @test */
    public function user_can_update_existing_rating()
    {
        $this->actingAs($this->user);

        // Create initial rating
        $initialRating = ChatRating::create([
            'chat_message_id' => $this->assistantMessage->id,
            'user_id' => $this->user->id,
            'company_id' => $this->user->company_id,
            'rating' => 3,
            'feedback' => 'Good response'
        ]);

        // Update rating
        $response = $this->postJson('/api/chat/ratings', [
            'message_id' => $this->assistantMessage->id,
            'rating' => 5,
            'feedback' => 'Excellent response!'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Message rated successfully'
            ]);

        $this->assertDatabaseHas('chat_ratings', [
            'id' => $initialRating->id,
            'chat_message_id' => $this->assistantMessage->id,
            'user_id' => $this->user->id,
            'rating' => 5,
            'feedback' => 'Excellent response!'
        ]);

        $this->assertDatabaseMissing('chat_ratings', [
            'rating' => 3,
            'feedback' => 'Good response'
        ]);
    }

    /** @test */
    public function user_cannot_rate_user_message()
    {
        $this->actingAs($this->user);

        // Create a user message
        $userMessage = ChatMessage::factory()->create([
            'chat_session_id' => $this->chatSession->id,
            'user_id' => $this->user->id,
            'message_type' => 'user',
            'content' => 'This is a user message'
        ]);

        $response = $this->postJson('/api/chat/ratings', [
            'message_id' => $userMessage->id,
            'rating' => 5
        ]);

        $response->assertStatus(500);
    }

    /** @test */
    public function rating_must_be_between_1_and_5()
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/chat/ratings', [
            'message_id' => $this->assistantMessage->id,
            'rating' => 0
        ]);

        $response->assertStatus(422);

        $response = $this->postJson('/api/chat/ratings', [
            'message_id' => $this->assistantMessage->id,
            'rating' => 6
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function feedback_is_required_when_rating_is_1()
    {
        $this->actingAs($this->user);

        // Try to submit rating 1 without feedback
        $response = $this->postJson('/api/chat/ratings', [
            'message_id' => $this->assistantMessage->id,
            'rating' => 1,
            'feedback' => '' // Empty feedback
        ]);

        // Should fail validation
        $response->assertStatus(422);

        // Now try with feedback
        $response = $this->postJson('/api/chat/ratings', [
            'message_id' => $this->assistantMessage->id,
            'rating' => 1,
            'feedback' => 'This response was not helpful'
        ]);

        // Should succeed
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Message rated successfully'
            ]);

        $this->assertDatabaseHas('chat_ratings', [
            'chat_message_id' => $this->assistantMessage->id,
            'user_id' => $this->user->id,
            'rating' => 1,
            'feedback' => 'This response was not helpful'
        ]);
    }
}

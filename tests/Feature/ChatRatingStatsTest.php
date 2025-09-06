<?php

namespace Tests\Feature;

use App\Models\ChatMessage;
use App\Models\ChatRating;
use App\Models\ChatSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ChatRatingStatsTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles if they don't exist
        if (!Role::where('name', 'SuperAdmin')->exists()) {
            Role::create(['name' => 'SuperAdmin']);
        }

        if (!Role::where('name', 'Administrator')->exists()) {
            Role::create(['name' => 'Administrator']);
        }

        // Create users
        $this->regularUser = User::factory()->create();
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('SuperAdmin');
    }

    /** @test */
    public function admin_can_view_rating_statistics()
    {
        $this->actingAs($this->adminUser);

        // Create some ratings
        $chatSession = ChatSession::factory()->create([
            'user_id' => $this->regularUser->id
        ]);

        $assistantMessage = ChatMessage::factory()->create([
            'chat_session_id' => $chatSession->id,
            'user_id' => $this->regularUser->id,
            'message_type' => 'assistant'
        ]);

        ChatRating::create([
            'chat_message_id' => $assistantMessage->id,
            'user_id' => $this->regularUser->id,
            'company_id' => $this->regularUser->company_id,
            'rating' => 5,
            'feedback' => 'Excellent!'
        ]);

        ChatRating::create([
            'chat_message_id' => $assistantMessage->id,
            'user_id' => $this->regularUser->id,
            'company_id' => $this->regularUser->company_id,
            'rating' => 3,
            'feedback' => 'Good'
        ]);

        $response = $this->getJson('/api/chat/ratings/stats');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ])
            ->assertJsonStructure([
                'data' => [
                    'total_ratings',
                    'average_rating',
                    'rating_distribution',
                    'recent_feedback',
                    'period'
                ]
            ]);
    }

    /** @test */
    public function regular_user_cannot_view_rating_statistics()
    {
        $this->actingAs($this->regularUser);

        $response = $this->getJson('/api/chat/ratings/stats');

        $response->assertStatus(403);
    }
}

<?php

namespace Tests\Feature\Livewire;

use Tests\TestCase;
use App\Livewire\ChatRating;
use App\Models\User;
use App\Models\ChatSession;
use App\Models\ChatMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

class ChatRatingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected ChatSession $chatSession;
    protected ChatMessage $assistantMessage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->chatSession = ChatSession::factory()->create([
            'user_id' => $this->user->id
        ]);

        $this->assistantMessage = ChatMessage::factory()->create([
            'chat_session_id' => $this->chatSession->id,
            'user_id' => $this->user->id,
            'message_type' => 'assistant',
            'content' => 'This is a test AI response'
        ]);
    }

    /** @test */
    public function it_shows_feedback_form_when_rating_is_1()
    {
        Livewire::test(ChatRating::class, ['messageId' => $this->assistantMessage->id])
            ->call('setRating', 1)
            ->assertSet('showFeedbackForm', true)
            ->assertSet('rating', 1);
    }

    /** @test */
    public function it_shows_feedback_form_when_rating_is_2()
    {
        Livewire::test(ChatRating::class, ['messageId' => $this->assistantMessage->id])
            ->call('setRating', 2)
            ->assertSet('showFeedbackForm', true)
            ->assertSet('rating', 2);
    }

    /** @test */
    public function it_submits_immediately_when_rating_is_3()
    {
        Livewire::test(ChatRating::class, ['messageId' => $this->assistantMessage->id])
            ->call('setRating', 3)
            ->assertSet('showFeedbackForm', false)
            ->assertSet('ratingSubmitted', true);
    }

    /** @test */
    public function it_submits_immediately_when_rating_is_4()
    {
        Livewire::test(ChatRating::class, ['messageId' => $this->assistantMessage->id])
            ->call('setRating', 4)
            ->assertSet('showFeedbackForm', false)
            ->assertSet('ratingSubmitted', true);
    }

    /** @test */
    public function it_shows_feedback_form_when_rating_is_5()
    {
        Livewire::test(ChatRating::class, ['messageId' => $this->assistantMessage->id])
            ->call('setRating', 5)
            ->assertSet('showFeedbackForm', true)
            ->assertSet('rating', 5);
    }

    /** @test */
    public function it_requires_feedback_when_rating_is_1()
    {
        $component = Livewire::test(ChatRating::class, ['messageId' => $this->assistantMessage->id])
            ->set('rating', 1)
            ->set('feedback', '') // Empty feedback
            ->call('submitRating');

        // Check that validation error is present
        $component->assertHasErrors(['feedback']);
    }

    /** @test */
    public function it_allows_submission_when_rating_is_1_with_feedback()
    {
        $component = Livewire::test(ChatRating::class, ['messageId' => $this->assistantMessage->id])
            ->set('rating', 1)
            ->set('feedback', 'This response was not helpful')
            ->call('submitRating');

        // Check that validation passes
        $component->assertHasNoErrors(['feedback']);
    }

    /** @test */
    public function it_does_not_require_feedback_when_rating_is_not_1()
    {
        // Test with rating 2
        $component = Livewire::test(ChatRating::class, ['messageId' => $this->assistantMessage->id])
            ->set('rating', 2)
            ->set('feedback', '') // Empty feedback
            ->call('submitRating');

        // Check that validation passes
        $component->assertHasNoErrors(['feedback']);

        // Test with rating 3
        $component = Livewire::test(ChatRating::class, ['messageId' => $this->assistantMessage->id])
            ->set('rating', 3)
            ->set('feedback', '') // Empty feedback
            ->call('submitRating');

        // Check that validation passes
        $component->assertHasNoErrors(['feedback']);

        // Test with rating 4
        $component = Livewire::test(ChatRating::class, ['messageId' => $this->assistantMessage->id])
            ->set('rating', 4)
            ->set('feedback', '') // Empty feedback
            ->call('submitRating');

        // Check that validation passes
        $component->assertHasNoErrors(['feedback']);

        // Test with rating 5
        $component = Livewire::test(ChatRating::class, ['messageId' => $this->assistantMessage->id])
            ->set('rating', 5)
            ->set('feedback', '') // Empty feedback
            ->call('submitRating');

        // Check that validation passes
        $component->assertHasNoErrors(['feedback']);
    }
}

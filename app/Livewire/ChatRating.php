<?php

namespace App\Livewire;

use App\Models\ChatMessage;
use App\Services\ChatRatingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class ChatRating extends Component
{
    public $messageId;
    public $rating = 0;
    public $feedback = '';
    public $notes = ''; // New field for optional notes
    public $showFeedbackForm = false;
    public $ratingSubmitted = false;
    public $errorMessage = '';
    public $isRegenerating = false; // Track regenerate status from parent

    protected $rules = [
        'rating' => 'required|integer|min:1|max:5',
        'feedback' => 'nullable|string|max:1000',
        'notes' => 'nullable|string|max:500' // Validation for notes
    ];

    public function mount($messageId)
    {
        $this->messageId = $messageId;

        $this->loadRating();
    }

    /**
     * Load rating data for the message
     */
    protected function loadRating()
    {
        // Check if message already has a rating
        $message = ChatMessage::with('rating')->find($this->messageId);

        if ($message && $message->rating) {
            $this->rating = (int) $message->rating->rating;
            $this->feedback = $message->rating->feedback;
            $this->notes = $message->rating->metadata['notes'] ?? ''; // Load notes if exists
            $this->ratingSubmitted = true;
        } else {
            $this->rating = 0;
            $this->feedback = '';
            $this->notes = '';
            $this->ratingSubmitted = false;
        }
    }

    public function setRating($value)
    {
        $this->rating = (int) $value;

        // If this is a low rating (1-2) or high rating (5), show the feedback form
        // For rating 1, feedback will be mandatory
        if ($value <= 2 || $value == 5) {
            $this->showFeedbackForm = true;
        } else {
            // For average ratings (3-4), submit immediately without feedback
            $this->submitRating();
        }
    }

    /**
     * Override the standard $set to handle special cases
     */
    public function handleEdit()
    {
        $this->ratingSubmitted = false;
        // Keep the existing rating value for editing
        // No need to reset other values since we want to edit the existing rating
    }

    public function toggleFeedbackForm()
    {
        $this->showFeedbackForm = !$this->showFeedbackForm;
    }

    public function submitRating()
    {
        try {
            // Update validation rules based on rating value
            $this->updateValidationRules();

            $this->validate();

            $ratingService = app(ChatRatingService::class);
            // Pass notes as additional parameter
            $ratingService->rateMessage($this->messageId, $this->rating, $this->feedback, $this->notes);

            $this->ratingSubmitted = true;
            $this->showFeedbackForm = false;

            // Dispatch event to notify AiChatWidget and other components
            $this->dispatch('rating-submitted', [
                'messageId' => $this->messageId,
                'rating' => $this->rating
            ]);

            // Dispatch to parent component to update UI
            $this->dispatch('rating-submitted-' . $this->messageId, [
                'messageId' => $this->messageId,
                'rating' => $this->rating
            ], true); // Set to true to bubble up to parent

            // Show toast notification with rating value
            $ratingText = $this->getRatingText($this->rating);
            $this->dispatch('show-toast', [
                'title' => 'Thank You!',
                'message' => "You rated this response $this->rating/5 ($ratingText). Your feedback helps us improve.",
                'type' => 'success'
            ]);

        } catch (\Exception $e) {
            Log::error('Error submitting rating', [
                'error' => $e->getMessage(),
                'message_id' => $this->messageId,
                'user_id' => Auth::id()
            ]);

            $this->errorMessage = 'Error submitting rating: ' . $e->getMessage();

            $this->dispatch('show-toast', [
                'title' => 'Error',
                'message' => 'Could not submit your rating. Please try again.',
                'type' => 'error'
            ]);
        }
    }

    /**
     * Update validation rules based on rating value
     */
    protected function updateValidationRules()
    {
        // If rating is 1, make feedback mandatory
        if ($this->rating == 1) {
            $this->rules['feedback'] = 'required|string|max:1000';
        } else {
            $this->rules['feedback'] = 'nullable|string|max:1000';
        }
    }

    /**
     * Get text description for rating value
     */
    protected function getRatingText($rating)
    {
        return match((int)$rating) {
            1 => 'Poor',
            2 => 'Fair',
            3 => 'Good',
            4 => 'Very Good',
            5 => 'Excellent',
            default => ''
        };
    }

    public function render()
    {
        return view('livewire.ai-chat.chat-rating');
    }
}

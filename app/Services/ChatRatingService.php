<?php

namespace App\Services;

use App\Models\ChatMessage;
use App\Models\ChatRating;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class ChatRatingService
{
    /**
     * Rate a chat message
     */
    public function rateMessage(string $messageId, int $rating, ?string $feedback = null, ?string $notes = null): ?ChatRating // Added notes parameter
    {
        try {
            // Validate rating
            if ($rating < 1 || $rating > 5) {
                throw new Exception('Rating must be between 1 and 5');
            }

            // Get the message
            $message = ChatMessage::findOrFail($messageId);

            // Check that the message is an assistant message (we only rate AI responses)
            if (!$message->isAssistantMessage()) {
                throw new Exception('Only assistant messages can be rated');
            }

            // Get current user
            $user = Auth::user();

            // Check if the message already has a rating
            $existingRating = $message->rating;

            if ($existingRating) {
                // Update existing rating
                $existingRating->update([
                    'rating' => $rating,
                    'feedback' => $feedback,
                    'metadata' => $this->buildMetadata($message, $rating, $notes) // Pass notes to metadata
                ]);

                Log::info('Chat message rating updated', [
                    'message_id' => $messageId,
                    'user_id' => $user->id,
                    'rating' => $rating
                ]);

                return $existingRating->fresh();
            }

            // Create new rating
            $chatRating = ChatRating::create([
                'chat_message_id' => $messageId,
                'user_id' => $user->id,
                'company_id' => $user->company_id,
                'rating' => $rating,
                'feedback' => $feedback,
                'metadata' => $this->buildMetadata($message, $rating, $notes) // Pass notes to metadata
            ]);

            Log::info('Chat message rated', [
                'message_id' => $messageId,
                'user_id' => $user->id,
                'rating' => $rating
            ]);

            return $chatRating;

        } catch (Exception $e) {
            Log::error('Error rating chat message', [
                'message_id' => $messageId,
                'rating' => $rating,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Get rating statistics for admin dashboard
     */
    public function getRatingStats(?string $companyId = null, ?string $period = 'week'): array
    {
        $query = ChatRating::query();

        // Apply company filter if needed
        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        // Apply date filter
        switch($period) {
            case 'day':
                $query->where('created_at', '>=', Carbon::now()->subDay());
                break;

            case 'week':
                $query->where('created_at', '>=', Carbon::now()->subWeek());
                break;

            case 'month':
                $query->where('created_at', '>=', Carbon::now()->subMonth());
                break;

            case 'year':
                $query->where('created_at', '>=', Carbon::now()->subYear());
                break;
        }

        // Get count by rating
        $ratingCounts = $query->selectRaw('rating, count(*) as count')
            ->groupBy('rating')
            ->pluck('count', 'rating')
            ->toArray();

        // Calculate average rating
        $totalRatings = array_sum($ratingCounts);
        $weightedSum = 0;

        foreach ($ratingCounts as $rating => $count) {
            $weightedSum += $rating * $count;
        }

        $averageRating = $totalRatings > 0 ? round($weightedSum / $totalRatings, 2) : 0;

        // Get recent feedback
        $recentFeedback = ChatRating::with(['user', 'message'])
            ->whereNotNull('feedback')
            ->where('feedback', '!=', '')
            ->latest()
            ->take(10)
            ->get();

        return [
            'total_ratings' => $totalRatings,
            'average_rating' => $averageRating,
            'rating_distribution' => $ratingCounts,
            'recent_feedback' => $recentFeedback,
            'period' => $period
        ];
    }

    /**
     * Build metadata for analytics
     */
    private function buildMetadata(ChatMessage $message, int $rating, ?string $notes = null): array // Added notes parameter
    {
        return [
            'chat_session_id' => $message->chat_session_id,
            'processing_time' => $message->processing_time,
            'token_count' => $message->token_count,
            'model' => $message->metadata['model'] ?? null,
            'provider' => $message->metadata['provider'] ?? null,
            'context_type' => $message->metadata['context_type'] ?? null,
            'timestamp' => now()->toIso8601String(),
            'message_age' => now()->diffInSeconds($message->created_at),
            'is_update' => $message->rating !== null,
            'notes' => $notes // Include notes in metadata
        ];
    }
}

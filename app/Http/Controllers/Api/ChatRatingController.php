<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ChatRatingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ChatRatingController extends Controller
{
    protected $ratingService;

    public function __construct(ChatRatingService $ratingService)
    {
        $this->ratingService = $ratingService;
    }

    /**
     * Rate a chat message
     */
    public function rateMessage(Request $request)
    {
        try {
            $request->validate([
                'message_id' => 'required|uuid|exists:chat_messages,id',
                'rating' => 'required|integer|min:1|max:5',
                'feedback' => 'nullable|string|max:1000'
            ]);

            $rating = $this->ratingService->rateMessage(
                $request->message_id,
                $request->rating,
                $request->feedback
            );

            return response()->json([
                'success' => true,
                'message' => 'Message rated successfully',
                'data' => $rating
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error in rating message', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error rating message: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get rating statistics (for admin)
     */
    public function getStats(Request $request)
    {
        try {
            $request->validate([
                'company_id' => 'nullable|uuid|exists:companies,id',
                'period' => 'nullable|string|in:day,week,month,year,all'
            ]);

            // Check if user has permission to view stats
            if (!auth()->user()->can('read report') && !auth()->user()->hasRole(['SuperAdmin', 'Administrator'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to view these statistics'
                ], 403);
            }

            $stats = $this->ratingService->getRatingStats(
                $request->company_id,
                $request->period ?? 'week'
            );

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting rating stats', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error getting rating statistics: ' . $e->getMessage()
            ], 500);
        }
    }
}

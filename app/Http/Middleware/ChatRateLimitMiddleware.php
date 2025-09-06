<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;

class ChatRateLimitMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if rate limiting is enabled
        if (!config('chat.rate_limit.enabled', true)) {
            return $next($request);
        }

        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => 'Authentication required for rate limiting',
                'code' => 'CHAT_AUTH_REQUIRED'
            ], 401);
        }

        $userId = $user->id;
        $now = Carbon::now();

        // Check per-minute rate limit
        $minuteLimit = config('chat.rate_limit.requests_per_minute', 20);
        $minuteKey = "chat_rate_limit:{$userId}:minute:" . $now->format('Y-m-d-H-i');
        $minuteCount = Cache::get($minuteKey, 0);

        if ($minuteCount >= $minuteLimit) {
            $this->logRateLimitViolation($user, 'minute', $minuteCount, $minuteLimit);
            
            return response()->json([
                'success' => false,
                'error' => 'Rate limit exceeded: too many requests per minute',
                'code' => 'CHAT_RATE_LIMIT_MINUTE',
                'retry_after' => 60,
                'limit' => $minuteLimit,
                'remaining' => 0
            ], 429);
        }

        // Check per-hour rate limit
        $hourLimit = config('chat.rate_limit.requests_per_hour', 200);
        $hourKey = "chat_rate_limit:{$userId}:hour:" . $now->format('Y-m-d-H');
        $hourCount = Cache::get($hourKey, 0);

        if ($hourCount >= $hourLimit) {
            $this->logRateLimitViolation($user, 'hour', $hourCount, $hourLimit);
            
            return response()->json([
                'success' => false,
                'error' => 'Rate limit exceeded: too many requests per hour',
                'code' => 'CHAT_RATE_LIMIT_HOUR',
                'retry_after' => 3600,
                'limit' => $hourLimit,
                'remaining' => 0
            ], 429);
        }

        // Check per-day rate limit
        $dayLimit = config('chat.rate_limit.requests_per_day', 1000);
        $dayKey = "chat_rate_limit:{$userId}:day:" . $now->format('Y-m-d');
        $dayCount = Cache::get($dayKey, 0);

        if ($dayCount >= $dayLimit) {
            $this->logRateLimitViolation($user, 'day', $dayCount, $dayLimit);
            
            return response()->json([
                'success' => false,
                'error' => 'Rate limit exceeded: too many requests per day',
                'code' => 'CHAT_RATE_LIMIT_DAY',
                'retry_after' => 86400,
                'limit' => $dayLimit,
                'remaining' => 0
            ], 429);
        }

        // Check burst limit (rapid consecutive requests)
        $burstLimit = config('chat.rate_limit.burst_limit', 5);
        $burstWindow = 10; // seconds
        $burstKey = "chat_rate_limit:{$userId}:burst:" . floor($now->timestamp / $burstWindow);
        $burstCount = Cache::get($burstKey, 0);

        if ($burstCount >= $burstLimit) {
            $this->logRateLimitViolation($user, 'burst', $burstCount, $burstLimit);
            
            return response()->json([
                'success' => false,
                'error' => 'Rate limit exceeded: too many rapid requests',
                'code' => 'CHAT_RATE_LIMIT_BURST',
                'retry_after' => $burstWindow,
                'limit' => $burstLimit,
                'remaining' => 0
            ], 429);
        }

        // Increment counters
        $this->incrementCounter($minuteKey, 60);
        $this->incrementCounter($hourKey, 3600);
        $this->incrementCounter($dayKey, 86400);
        $this->incrementCounter($burstKey, $burstWindow);

        // Add rate limit headers to response
        $response = $next($request);
        
        $response->headers->set('X-Chat-RateLimit-Minute-Limit', $minuteLimit);
        $response->headers->set('X-Chat-RateLimit-Minute-Remaining', max(0, $minuteLimit - $minuteCount - 1));
        $response->headers->set('X-Chat-RateLimit-Hour-Limit', $hourLimit);
        $response->headers->set('X-Chat-RateLimit-Hour-Remaining', max(0, $hourLimit - $hourCount - 1));
        $response->headers->set('X-Chat-RateLimit-Day-Limit', $dayLimit);
        $response->headers->set('X-Chat-RateLimit-Day-Remaining', max(0, $dayLimit - $dayCount - 1));

        return $response;
    }

    /**
     * Increment rate limit counter
     */
    private function incrementCounter(string $key, int $ttl): void
    {
        try {
            $current = Cache::get($key, 0);
            Cache::put($key, $current + 1, $ttl);
        } catch (\Exception $e) {
            Log::error('ChatRateLimitMiddleware: Failed to increment counter', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Log rate limit violation
     */
    private function logRateLimitViolation($user, string $type, int $count, int $limit): void
    {
        Log::warning('ChatRateLimitMiddleware: Rate limit exceeded', [
            'user_id' => $user->id,
            'company_id' => $user->company_id,
            'limit_type' => $type,
            'current_count' => $count,
            'limit' => $limit,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);
    }
}

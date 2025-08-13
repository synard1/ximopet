<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\DatabasePerformanceTrackerService;
use Symfony\Component\HttpFoundation\Response;

class PerformanceTrackingMiddleware
{
    protected $tracker;

    public function __construct(DatabasePerformanceTrackerService $tracker)
    {
        $this->tracker = $tracker;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Skip tracking for certain routes
        if ($this->shouldSkipTracking($request)) {
            return $next($request);
        }

        // Start tracking
        $this->tracker->startTracking();

        // Process the request
        $response = $next($request);

        // Stop tracking and log performance
        $this->tracker->stopTracking(
            'http_request',
            'HTTP_Request',
            null,
            1,
            $response->getStatusCode() >= 400 ? 'HTTP Error: ' . $response->getStatusCode() : null
        );

        return $response;
    }

    /**
     * Check if tracking should be skipped for this request
     */
    protected function shouldSkipTracking(Request $request): bool
    {
        // Skip tracking for certain routes
        $skipRoutes = [
            'admin/performance-logs*',
            'admin/monitoring*',
            'telescope*',
            'horizon*',
            'api/health*',
        ];

        foreach ($skipRoutes as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        // Skip tracking for certain methods
        $skipMethods = ['OPTIONS', 'HEAD'];
        if (in_array($request->method(), $skipMethods)) {
            return true;
        }

        // Skip tracking for certain user agents
        $skipUserAgents = [
            'bot',
            'crawler',
            'spider',
            'monitoring',
        ];

        $userAgent = strtolower($request->userAgent() ?? '');
        foreach ($skipUserAgents as $agent) {
            if (str_contains($userAgent, $agent)) {
                return true;
            }
        }

        return false;
    }
}

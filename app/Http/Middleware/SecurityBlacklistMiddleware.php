<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\SecurityBlacklist;
use App\Models\SecurityViolation;

class SecurityBlacklistMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Only check in production environment
        if (!app()->environment('production')) {
            return $next($request);
        }

        $clientIp = $this->getClientIp($request);

        // Check if IP is blacklisted
        if ($this->isIpBlacklisted($clientIp)) {
            $this->logSecurityEvent($clientIp, 'blocked_access_attempt');

            // Allow access to login page and auth routes, but show blacklist notification
            if ($this->isAuthRoute($request)) {
                // Add blacklist info to session for frontend notification
                session()->flash('security_blacklisted', [
                    'message' => 'Your IP address has been temporarily blocked due to security violations.',
                    'expires_at' => $this->getBlacklistExpiry($clientIp),
                    'reason' => $this->getBlacklistReason($clientIp)
                ]);

                return $next($request);
            }

            // For API requests, return JSON
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'error' => 'Access denied',
                    'message' => 'Your IP address has been temporarily blocked due to security violations.',
                    'code' => 'IP_BLACKLISTED'
                ], 403);
            }

            // For web requests, redirect to login with blacklist notification
            return redirect()->route('login')->with('security_blacklisted', [
                'message' => 'Your IP address has been temporarily blocked due to security violations.',
                'expires_at' => $this->getBlacklistExpiry($clientIp),
                'reason' => $this->getBlacklistReason($clientIp)
            ]);
        }

        return $next($request);
    }

    /**
     * Get client IP address
     */
    private function getClientIp(Request $request): string
    {
        // Check for various headers that might contain the real IP
        $headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',            // Proxy
            'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',            // Proxy
            'REMOTE_ADDR'                // Standard
        ];

        foreach ($headers as $header) {
            $ip = $request->server($header);
            if (!empty($ip) && $ip !== 'unknown') {
                // Handle comma-separated IPs (X-Forwarded-For can contain multiple IPs)
                $ips = explode(',', $ip);
                $ip = trim($ips[0]);

                // Validate IP
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $request->ip();
    }

    /**
     * Check if IP is blacklisted
     */
    private function isIpBlacklisted(string $ip): bool
    {
        // Check cache first for performance
        $cacheKey = "security_blacklist_{$ip}";

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Check using model
        $isBlacklisted = SecurityBlacklist::isBlacklisted($ip);

        // Cache result for 5 minutes
        Cache::put($cacheKey, $isBlacklisted, 300);

        return $isBlacklisted;
    }

    /**
     * Add IP to blacklist
     */
    public static function addToBlacklist(string $ip, string $reason = 'security_violation', int $durationHours = 72): void
    {
        // Use model method
        SecurityBlacklist::addToBlacklist($ip, $reason, $durationHours);

        // Clear cache
        Cache::forget("security_blacklist_{$ip}");

        // Log the blacklist action
        if (config('app.debug')) {
            Log::warning('IP added to security blacklist', [
                'ip_address' => $ip,
                'reason' => $reason,
                'duration_hours' => $durationHours
            ]);
        }
    }

    /**
     * Remove IP from blacklist
     */
    public static function removeFromBlacklist(string $ip): void
    {
        SecurityBlacklist::removeFromBlacklist($ip);

        // Clear cache
        Cache::forget("security_blacklist_{$ip}");

        if (config('app.debug')) {
            Log::info('IP removed from security blacklist', [
                'ip_address' => $ip
            ]);
        }
    }

    /**
     * Clean expired blacklist entries
     */
    public static function cleanExpiredEntries(): int
    {
        $deletedCount = SecurityBlacklist::cleanExpired();

        if ($deletedCount > 0 && config('app.debug')) {
            Log::info('Cleaned expired blacklist entries', [
                'deleted_count' => $deletedCount
            ]);
        }

        return $deletedCount;
    }

    /**
     * Get violation count for IP
     */
    public static function getViolationCount(string $ip): int
    {
        return SecurityViolation::getViolationCount($ip, 24);
    }

    /**
     * Record security violation
     */
    public static function recordViolation(string $ip, string $reason, array $metadata = []): void
    {
        SecurityViolation::recordViolation($ip, $reason, $metadata);

        // Check if IP should be blacklisted
        $violationCount = self::getViolationCount($ip);

        if ($violationCount >= 3) {
            self::addToBlacklist($ip, 'multiple_security_violations', 72);
        }

        if (config('app.debug')) {
            Log::warning('Security violation recorded', [
                'ip_address' => $ip,
                'reason' => $reason,
                'violation_count' => $violationCount,
                'metadata' => $metadata
            ]);
        }
    }

    /**
     * Check if request is for authentication routes
     */
    private function isAuthRoute(Request $request): bool
    {
        $authRoutes = [
            'login',
            'register',
            'password/*',
            'auth/*',
            'logout'
        ];

        foreach ($authRoutes as $route) {
            if ($request->is($route)) {
                return true;
            }
        }

        // Check named routes
        $routeName = $request->route()?->getName();
        $authRouteNames = ['login', 'register', 'password.request', 'password.reset', 'logout'];

        return in_array($routeName, $authRouteNames);
    }

    /**
     * Get blacklist expiry time for IP
     */
    private function getBlacklistExpiry(string $ip): ?string
    {
        $blacklistEntry = SecurityBlacklist::where('ip_address', $ip)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        return $blacklistEntry ? $blacklistEntry->expires_at->toDateTimeString() : null;
    }

    /**
     * Get blacklist reason for IP
     */
    private function getBlacklistReason(string $ip): ?string
    {
        $blacklistEntry = SecurityBlacklist::where('ip_address', $ip)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        return $blacklistEntry ? $blacklistEntry->reason : null;
    }

    /**
     * Log security event
     */
    private function logSecurityEvent(string $ip, string $event): void
    {
        if (config('app.debug')) {
            Log::info('Security event', [
                'ip_address' => $ip,
                'event' => $event,
                'user_agent' => request()->userAgent(),
                'timestamp' => Carbon::now()->toDateTimeString()
            ]);
        }
    }
}

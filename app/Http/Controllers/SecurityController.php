<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Http\Middleware\SecurityBlacklistMiddleware;
use App\Models\SecurityBlacklist;
use Carbon\Carbon;

class SecurityController extends Controller
{
    /**
     * Record security violation from frontend
     */
    public function recordViolation(Request $request): JsonResponse
    {
        // Only process in production environment
        if (!app()->environment('production')) {
            return response()->json([
                'status' => 'ignored',
                'message' => 'Security monitoring disabled in development'
            ]);
        }

        $request->validate([
            'reason' => 'required|string|max:255',
            'metadata' => 'nullable|array'
        ]);

        $clientIp = $this->getClientIp($request);
        $reason = $request->input('reason');
        $metadata = $request->input('metadata', []);

        // Add additional metadata
        $metadata = array_merge($metadata, [
            'user_id' => Auth::id(),
            'user_agent' => $request->userAgent(),
            'url' => $request->header('referer'),
            'timestamp' => Carbon::now()->toDateTimeString()
        ]);

        // Record the violation
        SecurityBlacklistMiddleware::recordViolation($clientIp, $reason, $metadata);

        // Get current violation count
        $violationCount = SecurityBlacklistMiddleware::getViolationCount($clientIp);

        // Log the violation
        if (config('app.debug')) {
            Log::warning('Frontend security violation reported', [
                'ip_address' => $clientIp,
                'reason' => $reason,
                'violation_count' => $violationCount,
                'user_id' => Auth::id(),
                'metadata' => $metadata
            ]);
        }

        // Logout user if authenticated
        if (Auth::check()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json([
            'status' => 'recorded',
            'violation_count' => $violationCount,
            'message' => $violationCount >= 3
                ? 'IP has been blacklisted due to multiple violations'
                : 'Security violation recorded'
        ]);
    }

    /**
     * Handle logout with security reason
     */
    public function securityLogout(Request $request): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:255',
            'timestamp' => 'nullable|string'
        ]);

        $clientIp = $this->getClientIp($request);
        $reason = $request->input('reason');

        // Record violation
        SecurityBlacklistMiddleware::recordViolation($clientIp, $reason, [
            'user_id' => Auth::id(),
            'logout_type' => 'security_forced',
            'timestamp' => $request->input('timestamp', Carbon::now()->toDateTimeString())
        ]);

        // Logout user
        if (Auth::check()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json([
            'status' => 'logged_out',
            'message' => 'User logged out due to security violation'
        ]);
    }

    /**
     * Get security status for current IP
     */
    public function getSecurityStatus(Request $request): JsonResponse
    {
        $clientIp = $this->getClientIp($request);
        $violationCount = SecurityBlacklistMiddleware::getViolationCount($clientIp);

        return response()->json([
            'ip_address' => $clientIp,
            'violation_count' => $violationCount,
            'is_production' => app()->environment('production'),
            'max_violations' => 3
        ]);
    }

    /**
     * Admin: Get blacklist entries
     */
    public function getBlacklistEntries(Request $request): JsonResponse
    {
        // Check admin permission
        if (!Auth::check() || !Auth::user()->hasRole(['Admin', 'Super Admin'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $entries = SecurityBlacklist::select([
            'ip_address',
            'reason',
            'violation_count',
            'expires_at',
            'created_at',
            'updated_at'
        ])
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json($entries);
    }

    /**
     * Admin: Remove IP from blacklist
     */
    public function removeFromBlacklist(Request $request): JsonResponse
    {
        // Check admin permission
        if (!Auth::check() || !Auth::user()->hasRole(['Admin', 'Super Admin'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'ip_address' => 'required|ip'
        ]);

        $ipAddress = $request->input('ip_address');
        SecurityBlacklistMiddleware::removeFromBlacklist($ipAddress);

        return response()->json([
            'status' => 'removed',
            'message' => "IP {$ipAddress} removed from blacklist"
        ]);
    }

    /**
     * Admin: Clean expired entries
     */
    public function cleanExpiredEntries(Request $request): JsonResponse
    {
        // Check admin permission
        if (!Auth::check() || !Auth::user()->hasRole(['Admin', 'Super Admin'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $deletedCount = SecurityBlacklistMiddleware::cleanExpiredEntries();

        return response()->json([
            'status' => 'cleaned',
            'deleted_count' => $deletedCount,
            'message' => "Cleaned {$deletedCount} expired blacklist entries"
        ]);
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
}
 
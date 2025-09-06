<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use App\Models\ChatSession;

class ChatAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if chat authentication is required
        if (!config('chat.security.require_authentication', true)) {
            return $next($request);
        }

        // Verify user authentication
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'error' => 'Authentication required',
                'code' => 'CHAT_AUTH_REQUIRED'
            ], 401);
        }

        $user = Auth::user();

        // Verify user has company access
        if (!$user->company_id) {
            return response()->json([
                'success' => false,
                'error' => 'Company access required',
                'code' => 'CHAT_COMPANY_REQUIRED'
            ], 403);
        }

        // Check if user has chat permissions (if using permission system)
        if (class_exists('Spatie\\Permission\\Models\\Permission')) {
            if (!$user->can('use-ai-chat')) {
                // Check for alternative permissions
                $hasAnyPermission = $user->can('manage-chat') || 
                                   $user->can('admin-access') ||
                                   $user->hasRole('admin') ||
                                   $user->hasRole('super-admin');
                
                if (!$hasAnyPermission) {
                    Log::warning('ChatAuthMiddleware: User denied chat access', [
                        'user_id' => $user->id,
                        'company_id' => $user->company_id,
                        'permissions' => $user->permissions->pluck('name')->toArray(),
                        'roles' => $user->roles->pluck('name')->toArray()
                    ]);

                    return response()->json([
                        'success' => false,
                        'error' => 'Insufficient permissions for chat access',
                        'code' => 'CHAT_PERMISSION_DENIED'
                    ], 403);
                }
            }
        }

        // Validate session ownership for session-specific routes
        $sessionId = $request->route('session') ?? $request->input('session_id');
        if ($sessionId) {
            $session = ChatSession::where('id', $sessionId)
                ->where('user_id', $user->id)
                ->where('company_id', $user->company_id)
                ->first();

            if (!$session) {
                return response()->json([
                    'success' => false,
                    'error' => 'Session not found or access denied',
                    'code' => 'CHAT_SESSION_ACCESS_DENIED'
                ], 404);
            }

            // Add session to request for later use
            $request->attributes->set('chat_session', $session);
        }

        // Log chat access for security auditing
        if (config('chat.security.log_all_interactions', true)) {
            Log::info('ChatAuthMiddleware: Chat access granted', [
                'user_id' => $user->id,
                'company_id' => $user->company_id,
                'route' => $request->route()?->getName(),
                'method' => $request->method(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);
        }

        return $next($request);
    }
}

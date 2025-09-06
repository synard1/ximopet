<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use App\Services\ChatContextService;

class ChatContextMiddleware
{
    protected $contextService;

    public function __construct(ChatContextService $contextService)
    {
        $this->contextService = $contextService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Validate and sanitize context data if present
        if ($request->has('context_type') || $request->has('context_filters')) {
            $validationResult = $this->validateContextRequest($request);
            
            if (!$validationResult['valid']) {
                return response()->json([
                    'success' => false,
                    'error' => $validationResult['error'],
                    'code' => 'CHAT_CONTEXT_INVALID'
                ], 400);
            }
        }

        // Sanitize user input if enabled
        if (config('chat.security.sanitize_user_input', true)) {
            $this->sanitizeUserInput($request);
        }

        // Validate message length
        if ($request->has('message')) {
            $maxLength = config('chat.security.max_message_length', 5000);
            if (strlen($request->input('message')) > $maxLength) {
                return response()->json([
                    'success' => false,
                    'error' => "Message too long. Maximum {$maxLength} characters allowed.",
                    'code' => 'CHAT_MESSAGE_TOO_LONG'
                ], 400);
            }
        }

        // Validate context access for the user's company
        if (config('chat.security.validate_context_access', true)) {
            $contextType = $request->input('context_type', 'general');
            $user = Auth::user();
            
            if ($contextType !== 'general' && !$this->contextService->validateContextAccess($contextType, $user->company_id)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Access denied to requested context type',
                    'code' => 'CHAT_CONTEXT_ACCESS_DENIED'
                ], 403);
            }
        }

        // Log context usage for analytics
        if ($request->has('context_type') && config('chat.debug.log_context_data', false)) {
            Log::info('ChatContextMiddleware: Context request', [
                'user_id' => Auth::id(),
                'company_id' => Auth::user()->company_id,
                'context_type' => $request->input('context_type'),
                'context_filters' => $request->input('context_filters', []),
                'route' => $request->route()?->getName()
            ]);
        }

        return $next($request);
    }

    /**
     * Validate context request parameters
     */
    private function validateContextRequest(Request $request): array
    {
        try {
            $validator = Validator::make($request->all(), [
                'context_type' => 'sometimes|string|in:' . implode(',', array_keys(config('chat.context.context_types', []))),
                'context_filters' => 'sometimes|array',
                'context_filters.*' => 'string|max:500',
            ]);

            if ($validator->fails()) {
                return [
                    'valid' => false,
                    'error' => 'Invalid context parameters: ' . $validator->errors()->first()
                ];
            }

            // Additional validation for context filters
            $contextFilters = $request->input('context_filters', []);
            if (!empty($contextFilters)) {
                // Validate filter structure
                foreach ($contextFilters as $key => $value) {
                    if (!is_string($key) || !is_scalar($value)) {
                        return [
                            'valid' => false,
                            'error' => 'Invalid context filter format'
                        ];
                    }
                    
                    // Check for potential SQL injection patterns
                    if ($this->containsSuspiciousPatterns($value)) {
                        return [
                            'valid' => false,
                            'error' => 'Invalid characters in context filter'
                        ];
                    }
                }
            }

            return ['valid' => true];

        } catch (\Exception $e) {
            Log::error('ChatContextMiddleware: Error validating context request', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return [
                'valid' => false,
                'error' => 'Context validation failed'
            ];
        }
    }

    /**
     * Sanitize user input to prevent XSS and injection attacks
     */
    private function sanitizeUserInput(Request $request): void
    {
        try {
            // Sanitize message content
            if ($request->has('message')) {
                $message = $request->input('message');
                $sanitized = $this->sanitizeText($message);
                $request->merge(['message' => $sanitized]);
            }

            // Sanitize context filters
            if ($request->has('context_filters')) {
                $filters = $request->input('context_filters');
                $sanitizedFilters = [];
                
                foreach ($filters as $key => $value) {
                    $sanitizedKey = $this->sanitizeText($key);
                    $sanitizedValue = $this->sanitizeText($value);
                    $sanitizedFilters[$sanitizedKey] = $sanitizedValue;
                }
                
                $request->merge(['context_filters' => $sanitizedFilters]);
            }

        } catch (\Exception $e) {
            Log::error('ChatContextMiddleware: Error sanitizing input', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Sanitize text input
     */
    private function sanitizeText(string $text): string
    {
        // Remove potentially dangerous HTML/script tags
        $text = strip_tags($text);
        
        // Convert special characters to HTML entities
        $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        
        // Remove control characters except newlines and tabs
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);
        
        return trim($text);
    }

    /**
     * Check for suspicious patterns that might indicate injection attempts
     */
    private function containsSuspiciousPatterns(string $value): bool
    {
        $suspiciousPatterns = [
            '/SELECT.*FROM/i',
            '/INSERT.*INTO/i',
            '/UPDATE.*SET/i',
            '/DELETE.*FROM/i',
            '/DROP.*TABLE/i',
            '/ALTER.*TABLE/i',
            '/CREATE.*TABLE/i',
            '/UNION.*SELECT/i',
            '/<script/i',
            '/javascript:/i',
            '/vbscript:/i',
            '/onload=/i',
            '/onerror=/i',
            '/onclick=/i'
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $value)) {
                Log::warning('ChatContextMiddleware: Suspicious pattern detected', [
                    'pattern' => $pattern,
                    'value' => $value,
                    'user_id' => Auth::id(),
                    'ip' => request()->ip()
                ]);
                return true;
            }
        }

        return false;
    }
}

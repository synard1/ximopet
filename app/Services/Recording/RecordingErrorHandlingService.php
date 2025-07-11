<?php

namespace App\Services\Recording;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

/**
 * Recording Error Handling Service
 * 
 * Provides comprehensive error handling for recording operations
 * Implements structured error responses, retry mechanisms, and error categorization
 */
class RecordingErrorHandlingService
{
    private const MAX_RETRIES = 3;
    private const RETRY_DELAY = 1000; // milliseconds

    /**
     * Error categories for better handling
     */
    public enum ErrorType: string
    {
        case VALIDATION = 'validation';
        case DATABASE = 'database';
        case BUSINESS_LOGIC = 'business_logic';
        case EXTERNAL_SERVICE = 'external_service';
        case PERMISSION = 'permission';
        case SYSTEM = 'system';
    }

    /**
     * Error severity levels
     */
    public enum ErrorSeverity: string
    {
        case LOW = 'low';
        case MEDIUM = 'medium';
        case HIGH = 'high';
        case CRITICAL = 'critical';
    }

    /**
     * Process operation with retry mechanism
     */
    public function processWithRetry(callable $operation, array $context = []): array
    {
        $attempts = 0;
        $lastError = null;

        while ($attempts < self::MAX_RETRIES) {
            try {
                $startTime = microtime(true);
                
                $result = $operation();
                
                $executionTime = microtime(true) - $startTime;
                
                // Log successful operation
                $this->logSuccess($context, $executionTime);
                
                return [
                    'success' => true,
                    'data' => $result,
                    'attempts' => $attempts + 1,
                    'execution_time' => $executionTime
                ];
                
            } catch (\Exception $e) {
                $attempts++;
                $lastError = $e;
                
                $errorContext = $this->analyzeError($e, $context);
                
                // Log error attempt
                $this->logErrorAttempt($errorContext, $attempts);
                
                // Check if error is retryable
                if (!$this->isRetryableError($e) || $attempts >= self::MAX_RETRIES) {
                    break;
                }
                
                // Wait before retry
                usleep(self::RETRY_DELAY * 1000);
            }
        }

        // All retries failed
        return $this->createErrorResponse($lastError, $context, $attempts);
    }

    /**
     * Create structured error response
     */
    public function createErrorResponse(\Exception $error, array $context = [], int $attempts = 1): array
    {
        $errorAnalysis = $this->analyzeError($error, $context);
        
        $response = [
            'success' => false,
            'error' => [
                'type' => $errorAnalysis['type']->value,
                'severity' => $errorAnalysis['severity']->value,
                'message' => $this->getUserFriendlyMessage($errorAnalysis),
                'code' => $errorAnalysis['code'],
                'details' => $errorAnalysis['details'],
                'timestamp' => now()->toISOString(),
                'attempts' => $attempts,
                'context' => $this->sanitizeContext($context)
            ],
            'suggestions' => $this->getErrorSuggestions($errorAnalysis),
            'support_info' => $this->getSupportInfo($errorAnalysis)
        ];

        // Log final error
        $this->logFinalError($errorAnalysis, $attempts);

        return $response;
    }

    /**
     * Analyze error and categorize it
     */
    private function analyzeError(\Exception $error, array $context = []): array
    {
        $errorType = $this->categorizeError($error);
        $severity = $this->determineSeverity($error, $context);
        
        return [
            'type' => $errorType,
            'severity' => $severity,
            'code' => $error->getCode(),
            'message' => $error->getMessage(),
            'file' => $error->getFile(),
            'line' => $error->getLine(),
            'trace' => $error->getTraceAsString(),
            'context' => $context,
            'timestamp' => now()
        ];
    }

    /**
     * Categorize error type
     */
    private function categorizeError(\Exception $error): ErrorType
    {
        if ($error instanceof ValidationException) {
            return ErrorType::VALIDATION;
        }
        
        if ($error instanceof \Illuminate\Database\QueryException) {
            return ErrorType::DATABASE;
        }
        
        if ($error instanceof \Illuminate\Auth\Access\AuthorizationException) {
            return ErrorType::PERMISSION;
        }
        
        if ($error instanceof \Illuminate\Http\Client\RequestException) {
            return ErrorType::EXTERNAL_SERVICE;
        }
        
        // Check error message for business logic indicators
        $message = strtolower($error->getMessage());
        if (str_contains($message, 'business') || str_contains($message, 'validation')) {
            return ErrorType::BUSINESS_LOGIC;
        }
        
        return ErrorType::SYSTEM;
    }

    /**
     * Determine error severity
     */
    private function determineSeverity(\Exception $error, array $context = []): ErrorSeverity
    {
        // Critical errors
        if ($error instanceof \Illuminate\Database\QueryException && 
            str_contains($error->getMessage(), 'connection')) {
            return ErrorSeverity::CRITICAL;
        }
        
        // High severity errors
        if ($error instanceof \Illuminate\Database\QueryException ||
            $error instanceof \Illuminate\Auth\Access\AuthorizationException) {
            return ErrorSeverity::HIGH;
        }
        
        // Medium severity errors
        if ($error instanceof ValidationException) {
            return ErrorSeverity::MEDIUM;
        }
        
        // Check context for severity indicators
        if (isset($context['critical_operation']) && $context['critical_operation']) {
            return ErrorSeverity::HIGH;
        }
        
        return ErrorSeverity::MEDIUM;
    }

    /**
     * Check if error is retryable
     */
    private function isRetryableError(\Exception $error): bool
    {
        // Don't retry validation errors
        if ($error instanceof ValidationException) {
            return false;
        }
        
        // Don't retry permission errors
        if ($error instanceof \Illuminate\Auth\Access\AuthorizationException) {
            return false;
        }
        
        // Retry database connection errors
        if ($error instanceof \Illuminate\Database\QueryException) {
            $message = strtolower($error->getMessage());
            return str_contains($message, 'connection') || 
                   str_contains($message, 'timeout') ||
                   str_contains($message, 'deadlock');
        }
        
        // Retry external service errors
        if ($error instanceof \Illuminate\Http\Client\RequestException) {
            return true;
        }
        
        return false;
    }

    /**
     * Get user-friendly error message
     */
    private function getUserFriendlyMessage(array $errorAnalysis): string
    {
        $type = $errorAnalysis['type'];
        $severity = $errorAnalysis['severity'];
        
        $messages = [
            ErrorType::VALIDATION->value => 'Data validation failed. Please check your input and try again.',
            ErrorType::DATABASE->value => 'Database operation failed. Please try again in a moment.',
            ErrorType::BUSINESS_LOGIC->value => 'Business rule validation failed. Please review your data.',
            ErrorType::EXTERNAL_SERVICE->value => 'External service is temporarily unavailable. Please try again later.',
            ErrorType::PERMISSION->value => 'You do not have permission to perform this operation.',
            ErrorType::SYSTEM->value => 'A system error occurred. Please try again or contact support.'
        ];
        
        $baseMessage = $messages[$type->value] ?? 'An unexpected error occurred. Please try again.';
        
        if ($severity === ErrorSeverity::CRITICAL) {
            $baseMessage .= ' This is a critical error that requires immediate attention.';
        }
        
        return $baseMessage;
    }

    /**
     * Get error suggestions
     */
    private function getErrorSuggestions(array $errorAnalysis): array
    {
        $type = $errorAnalysis['type'];
        
        $suggestions = [
            ErrorType::VALIDATION->value => [
                'Check all required fields are filled',
                'Ensure data format is correct',
                'Verify input values are within acceptable ranges'
            ],
            ErrorType::DATABASE->value => [
                'Try again in a few moments',
                'Check your internet connection',
                'Contact support if problem persists'
            ],
            ErrorType::BUSINESS_LOGIC->value => [
                'Review business rules and constraints',
                'Check if data meets business requirements',
                'Verify permissions and access rights'
            ],
            ErrorType::EXTERNAL_SERVICE->value => [
                'Try again later',
                'Check if external service is available',
                'Contact support if issue persists'
            ],
            ErrorType::PERMISSION->value => [
                'Contact your administrator for access',
                'Verify your user permissions',
                'Check if your account is active'
            ],
            ErrorType::SYSTEM->value => [
                'Try again in a few moments',
                'Clear your browser cache',
                'Contact support if problem continues'
            ]
        ];
        
        return $suggestions[$type->value] ?? ['Try again later', 'Contact support if problem persists'];
    }

    /**
     * Get support information
     */
    private function getSupportInfo(array $errorAnalysis): array
    {
        return [
            'error_id' => $this->generateErrorId($errorAnalysis),
            'timestamp' => $errorAnalysis['timestamp']->toISOString(),
            'severity' => $errorAnalysis['severity']->value,
            'contact_support' => $errorAnalysis['severity'] === ErrorSeverity::CRITICAL || 
                                $errorAnalysis['severity'] === ErrorSeverity::HIGH,
            'support_email' => config('app.support_email', 'support@example.com'),
            'documentation_url' => config('app.docs_url', 'https://docs.example.com')
        ];
    }

    /**
     * Generate unique error ID
     */
    private function generateErrorId(array $errorAnalysis): string
    {
        $data = $errorAnalysis['timestamp']->format('Y-m-d-H-i-s') . 
                $errorAnalysis['type']->value . 
                $errorAnalysis['severity']->value;
        
        return 'ERR-' . strtoupper(substr(md5($data), 0, 8));
    }

    /**
     * Sanitize context for logging
     */
    private function sanitizeContext(array $context): array
    {
        $sensitiveFields = ['password', 'token', 'secret', 'key'];
        
        foreach ($sensitiveFields as $field) {
            if (isset($context[$field])) {
                $context[$field] = '***REDACTED***';
            }
        }
        
        return $context;
    }

    /**
     * Log successful operation
     */
    private function logSuccess(array $context, float $executionTime): void
    {
        Log::info('Recording operation successful', [
            'execution_time' => $executionTime,
            'context' => $this->sanitizeContext($context),
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Log error attempt
     */
    private function logErrorAttempt(array $errorAnalysis, int $attempt): void
    {
        $logLevel = $errorAnalysis['severity'] === ErrorSeverity::CRITICAL ? 'critical' : 'warning';
        
        Log::$logLevel('Recording operation failed (attempt ' . $attempt . ')', [
            'error_type' => $errorAnalysis['type']->value,
            'severity' => $errorAnalysis['severity']->value,
            'message' => $errorAnalysis['message'],
            'attempt' => $attempt,
            'context' => $this->sanitizeContext($errorAnalysis['context']),
            'timestamp' => $errorAnalysis['timestamp']->toISOString()
        ]);
    }

    /**
     * Log final error
     */
    private function logFinalError(array $errorAnalysis, int $attempts): void
    {
        $logLevel = $errorAnalysis['severity'] === ErrorSeverity::CRITICAL ? 'critical' : 'error';
        
        Log::$logLevel('Recording operation failed after ' . $attempts . ' attempts', [
            'error_type' => $errorAnalysis['type']->value,
            'severity' => $errorAnalysis['severity']->value,
            'message' => $errorAnalysis['message'],
            'code' => $errorAnalysis['code'],
            'file' => $errorAnalysis['file'],
            'line' => $errorAnalysis['line'],
            'attempts' => $attempts,
            'context' => $this->sanitizeContext($errorAnalysis['context']),
            'timestamp' => $errorAnalysis['timestamp']->toISOString()
        ]);
    }

    /**
     * Handle validation errors specifically
     */
    public function handleValidationError(ValidationException $error, array $context = []): array
    {
        $errors = $error->errors();
        $firstError = reset($errors);
        
        return [
            'success' => false,
            'error' => [
                'type' => ErrorType::VALIDATION->value,
                'severity' => ErrorSeverity::MEDIUM->value,
                'message' => 'Validation failed: ' . ($firstError[0] ?? 'Invalid data provided'),
                'code' => 'VALIDATION_ERROR',
                'details' => $errors,
                'timestamp' => now()->toISOString(),
                'context' => $this->sanitizeContext($context)
            ],
            'suggestions' => [
                'Check all required fields',
                'Ensure data format is correct',
                'Verify input values'
            ]
        ];
    }

    /**
     * Handle database errors specifically
     */
    public function handleDatabaseError(\Exception $error, array $context = []): array
    {
        $isConnectionError = str_contains(strtolower($error->getMessage()), 'connection');
        
        return [
            'success' => false,
            'error' => [
                'type' => ErrorType::DATABASE->value,
                'severity' => $isConnectionError ? ErrorSeverity::CRITICAL->value : ErrorSeverity::HIGH->value,
                'message' => $isConnectionError 
                    ? 'Database connection failed. Please try again later.'
                    : 'Database operation failed. Please try again.',
                'code' => 'DATABASE_ERROR',
                'details' => [
                    'original_message' => $error->getMessage(),
                    'is_connection_error' => $isConnectionError
                ],
                'timestamp' => now()->toISOString(),
                'context' => $this->sanitizeContext($context)
            ],
            'suggestions' => $isConnectionError 
                ? ['Try again in a few moments', 'Check your internet connection']
                : ['Try again', 'Contact support if problem persists']
        ];
    }
} 
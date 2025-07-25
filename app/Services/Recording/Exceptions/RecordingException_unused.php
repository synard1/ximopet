<?php

declare(strict_types=1);

namespace App\Services\Recording\Exceptions;

use Exception;

/**
 * RecordingException
 * 
 * Base exception class for all recording-related exceptions.
 * Provides common functionality and structure for recording service errors.
 */
class RecordingException extends Exception
{
    protected array $context = [];
    protected string $errorCode = 'RECORDING_ERROR';

    public function __construct(
        string $message = '',
        int $code = 0,
        ?Exception $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * Get the error context
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Get the error code
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * Get formatted error details
     */
    public function getErrorDetails(): array
    {
        return [
            'error_code' => $this->errorCode,
            'message' => $this->getMessage(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'context' => $this->context,
            'trace' => $this->getTraceAsString()
        ];
    }

    /**
     * Create a formatted error response
     */
    public function toResponse(): array
    {
        return [
            'error' => true,
            'code' => $this->errorCode,
            'message' => $this->getMessage(),
            'context' => $this->context
        ];
    }
}

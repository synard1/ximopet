<?php

declare(strict_types=1);

namespace App\Services\Recording\Exceptions;

/**
 * RecordingValidationException
 * 
 * Exception thrown when recording validation fails.
 * Contains detailed validation errors and context.
 */
class RecordingValidationException extends RecordingException
{
    protected string $errorCode = 'RECORDING_VALIDATION_ERROR';
    protected array $validationErrors = [];

    public function __construct(
        string $message = 'Recording validation failed',
        array $validationErrors = [],
        array $context = []
    ) {
        parent::__construct($message, 422, null, $context);
        $this->validationErrors = $validationErrors;
    }

    /**
     * Get validation errors
     */
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    /**
     * Check if has validation errors
     */
    public function hasValidationErrors(): bool
    {
        return !empty($this->validationErrors);
    }

    /**
     * Get formatted validation response
     */
    public function getValidationResponse(): array
    {
        return [
            'error' => true,
            'code' => $this->errorCode,
            'message' => $this->getMessage(),
            'validation_errors' => $this->validationErrors,
            'context' => $this->context
        ];
    }
}

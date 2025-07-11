<?php

declare(strict_types=1);

namespace App\Services\Recording\DTOs;

use Illuminate\Support\Facades\Log;

/**
 * ValidationResult DTO
 * 
 * Handles validation results with comprehensive error handling, warnings, and metadata.
 * Used across all recording validation services for consistent result handling.
 */
class ValidationResult
{
    /**
     * Create a new validation result instance.
     *
     * @param bool $isValid Whether the validation passed
     * @param array $errors Array of error messages
     * @param array $warnings Array of warning messages
     * @param ?string $message Optional message about the validation
     * @param array $metadata Additional metadata about the validation
     */
    public function __construct(
        public readonly bool $isValid,
        public readonly array $errors = [],
        public readonly array $warnings = [],
        public readonly ?string $message = null,
        public readonly array $metadata = []
    ) {
        // Log validation results for debugging (only in debug mode)
        try {
            if (config('app.debug') && (!$this->isValid || !empty($this->warnings))) {
                Log::debug('ValidationResult created', [
                    'is_valid' => $this->isValid,
                    'error_count' => count($this->errors),
                    'warning_count' => count($this->warnings),
                    'has_metadata' => !empty($this->metadata)
                ]);
            }
        } catch (\Exception $e) {
            // Silently ignore logging errors in test environment
        }
    }

    /**
     * Check if validation has any errors.
     *
     * @return bool
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Check if validation has any warnings.
     *
     * @return bool
     */
    public function hasWarnings(): bool
    {
        return !empty($this->warnings);
    }

    /**
     * Get the first error message.
     *
     * @return string|null
     */
    public function getFirstError(): ?string
    {
        return $this->errors[0] ?? null;
    }

    /**
     * Get the first warning message.
     *
     * @return string|null
     */
    public function getFirstWarning(): ?string
    {
        return $this->warnings[0] ?? null;
    }

    /**
     * Get all error and warning messages combined.
     *
     * @return array
     */
    public function getAllMessages(): array
    {
        return array_merge($this->errors, $this->warnings);
    }

    /**
     * Get formatted error messages for display.
     *
     * @return string
     */
    public function getFormattedErrors(): string
    {
        if (empty($this->errors)) {
            return '';
        }

        return implode('; ', $this->errors);
    }

    /**
     * Get formatted warning messages for display.
     *
     * @return string
     */
    public function getFormattedWarnings(): string
    {
        if (empty($this->warnings)) {
            return '';
        }

        return implode('; ', $this->warnings);
    }

    /**
     * Get specific metadata value.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getMetadata(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Check if metadata exists.
     *
     * @param string $key
     * @return bool
     */
    public function hasMetadata(string $key): bool
    {
        return isset($this->metadata[$key]);
    }

    /**
     * Convert to array format.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'is_valid' => $this->isValid,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'message' => $this->message,
            'metadata' => $this->metadata,
            'has_errors' => $this->hasErrors(),
            'has_warnings' => $this->hasWarnings(),
            'first_error' => $this->getFirstError(),
            'first_warning' => $this->getFirstWarning(),
        ];
    }

    /**
     * Convert to JSON string.
     *
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT);
    }

    /**
     * Create a successful validation result.
     *
     * @param ?string $message Optional message about the validation
     * @param array $metadata Additional metadata
     * @return self
     */
    public static function success(?string $message = null, array $metadata = []): self
    {
        return new self(
            isValid: true,
            message: $message,
            metadata: $metadata
        );
    }

    /**
     * Create a successful validation result with warnings.
     *
     * @param array $warnings Warning messages
     * @param array $metadata Additional metadata
     * @return self
     */
    public static function successWithWarnings(array $warnings, array $metadata = []): self
    {
        return new self(
            isValid: true,
            warnings: $warnings,
            metadata: $metadata
        );
    }

    /**
     * Create a failed validation result.
     *
     * @param array $errors Error messages
     * @param ?string $message Optional message about the validation
     * @param array $warnings Warning messages
     * @param array $metadata Additional metadata
     * @return self
     */
    public static function failure(array $errors, ?string $message = null, array $warnings = [], array $metadata = []): self
    {
        return new self(
            isValid: false,
            errors: $errors,
            warnings: $warnings,
            message: $message,
            metadata: $metadata
        );
    }

    /**
     * Create a validation result from array.
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            isValid: $data['is_valid'] ?? false,
            errors: $data['errors'] ?? [],
            warnings: $data['warnings'] ?? [],
            message: $data['message'] ?? null,
            metadata: $data['metadata'] ?? []
        );
    }

    /**
     * Merge multiple validation results.
     *
     * @param ValidationResult ...$results
     * @return self
     */
    public static function merge(ValidationResult ...$results): self
    {
        $isValid = true;
        $errors = [];
        $warnings = [];
        $metadata = [];

        foreach ($results as $result) {
            if (!$result->isValid) {
                $isValid = false;
            }
            $errors = array_merge($errors, $result->errors);
            $warnings = array_merge($warnings, $result->warnings);
            $metadata = array_merge($metadata, $result->metadata);
        }

        return new self($isValid, $errors, $warnings, null, $metadata);
    }

    /**
     * Create validation result from exception.
     *
     * @param \Throwable $exception
     * @return self
     */
    public static function fromException(\Throwable $exception): self
    {
        return new self(
            isValid: false,
            errors: [$exception->getMessage()],
            message: 'Validation failed due to exception',
            metadata: ['exception_class' => get_class($exception)]
        );
    }

    /**
     * Get validation summary
     */
    public function getSummary(): string
    {
        $summary = $this->isValid ? 'VALID' : 'INVALID';

        if ($this->hasErrors()) {
            $summary .= ' - ' . count($this->errors) . ' error(s)';
        }

        if ($this->hasWarnings()) {
            $summary .= ' - ' . count($this->warnings) . ' warning(s)';
        }

        return $summary;
    }

    /**
     * Get formatted error messages as array
     */
    public function getFormattedErrorsArray(): array
    {
        return array_map(function ($error, $index) {
            return sprintf('[%d] %s', $index + 1, $error);
        }, $this->errors, array_keys($this->errors));
    }

    /**
     * Get formatted warning messages as array
     */
    public function getFormattedWarningsArray(): array
    {
        return array_map(function ($warning, $index) {
            return sprintf('[%d] %s', $index + 1, $warning);
        }, $this->warnings, array_keys($this->warnings));
    }

    /**
     * Check if validation is valid and has no warnings
     */
    public function isPerfect(): bool
    {
        return $this->isValid && !$this->hasWarnings();
    }

    /**
     * Get validation score (0-100)
     */
    public function getScore(): int
    {
        if ($this->isValid) {
            return $this->hasWarnings() ? 80 : 100;
        }

        return count($this->errors) > 5 ? 0 : (5 - count($this->errors)) * 10;
    }

    /**
     * Get error count
     */
    public function getErrorCount(): int
    {
        return count($this->errors);
    }

    /**
     * Get warning count
     */
    public function getWarningCount(): int
    {
        return count($this->warnings);
    }

    /**
     * Create validation result for specific field
     */
    public static function forField(string $field, bool $isValid, array $errors = [], array $warnings = []): self
    {
        return new self(
            isValid: $isValid,
            errors: $errors,
            warnings: $warnings,
            metadata: ['field' => $field]
        );
    }

    /**
     * Create validation result with multiple fields
     */
    public static function forFields(array $fieldResults): self
    {
        $isValid = true;
        $errors = [];
        $warnings = [];
        $metadata = ['fields' => []];

        foreach ($fieldResults as $field => $result) {
            if ($result instanceof self) {
                $isValid = $isValid && $result->isValid;
                $errors = array_merge($errors, $result->errors);
                $warnings = array_merge($warnings, $result->warnings);
                $metadata['fields'][$field] = $result->toArray();
            }
        }

        return new self(
            isValid: $isValid,
            errors: $errors,
            warnings: $warnings,
            metadata: $metadata
        );
    }

    /**
     * Debug representation
     */
    public function __toString(): string
    {
        return $this->getSummary();
    }
}

<?php

declare(strict_types=1);

namespace App\Services\Recording\DTOs;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * ProcessingResult DTO
 * 
 * Handles service processing results with success/failure status, data, and comprehensive error handling.
 * Used across all recording services for consistent result handling.
 */
class ProcessingResult
{
    /**
     * Create a new processing result instance.
     *
     * @param bool $success Whether the processing was successful
     * @param mixed $data The result data
     * @param ?string $message Optional message
     * @param array $errors Array of error messages
     * @param array $warnings Array of warning messages
     * @param array $metadata Additional metadata about the processing
     */
    public function __construct(
        public readonly bool $success,
        public readonly mixed $data = null,
        public readonly ?string $message = null,
        public readonly array $errors = [],
        public readonly array $warnings = [],
        public readonly array $metadata = []
    ) {
        // Log processing results for debugging (only in debug mode)
        try {
            if (config('app.debug') && (!$this->success || !empty($this->warnings))) {
                Log::debug('ProcessingResult created', [
                    'success' => $this->success,
                    'has_data' => !is_null($this->data),
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
     * Check if processing was successful.
     *
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Check if processing failed.
     *
     * @return bool
     */
    public function isFailure(): bool
    {
        return !$this->success;
    }

    /**
     * Check if processing has any errors.
     *
     * @return bool
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Check if processing has any warnings.
     *
     * @return bool
     */
    public function hasWarnings(): bool
    {
        return !empty($this->warnings);
    }

    /**
     * Check if processing has data.
     *
     * @return bool
     */
    public function hasData(): bool
    {
        return !is_null($this->data);
    }

    /**
     * Get the processing data.
     *
     * @param mixed $default Default value if no data
     * @return mixed
     */
    public function getData(mixed $default = null): mixed
    {
        return $this->data ?? $default;
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
     * Get all error messages.
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get all warning messages.
     *
     * @return array
     */
    public function getWarnings(): array
    {
        return $this->warnings;
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
     * Get all metadata.
     *
     * @return array
     */
    public function getAllMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Get processing duration from metadata.
     *
     * @return float|null Duration in milliseconds
     */
    public function getProcessingDuration(): ?float
    {
        return $this->getMetadata('processing_duration');
    }

    /**
     * Get memory usage from metadata.
     *
     * @return float|null Memory usage in MB
     */
    public function getMemoryUsage(): ?float
    {
        return $this->getMetadata('memory_usage');
    }

    /**
     * Get affected records count from metadata.
     *
     * @return int|null
     */
    public function getAffectedRecords(): ?int
    {
        return $this->getMetadata('affected_records');
    }

    /**
     * Get operation type from metadata.
     *
     * @return ?string
     */
    public function getOperationType(): ?string
    {
        return $this->getMetadata('operation_type');
    }

    /**
     * Get processing time from metadata.
     *
     * @return float|null Processing time in milliseconds
     */
    public function getProcessingTime(): ?float
    {
        return $this->getMetadata('processing_time');
    }

    /**
     * Get affected rows count from metadata.
     *
     * @return int|null
     */
    public function getAffectedRows(): ?int
    {
        return $this->getMetadata('affected_rows');
    }

    /**
     * Get operation performance.
     *
     * @return array
     */
    public function getPerformance(): array
    {
        return [
            'processing_time' => $this->getProcessingTime(),
            'memory_usage' => $this->getMemoryUsage(),
            'affected_rows' => $this->getAffectedRows(),
            'operation_type' => $this->getOperationType(),
            'timestamp' => $this->getMetadata('timestamp', Carbon::now()->toISOString()),
        ];
    }

    /**
     * Check if operation was fast.
     *
     * @param float $thresholdMs Threshold in milliseconds
     * @return bool
     */
    public function isFast(float $thresholdMs = 100): bool
    {
        $processingTime = $this->getProcessingTime();
        return $processingTime !== null && $processingTime <= $thresholdMs;
    }

    /**
     * Check if operation used low memory.
     *
     * @param int $thresholdBytes Threshold in bytes
     * @return bool
     */
    public function isLowMemory(int $thresholdBytes = 1024 * 1024): bool
    {
        $memoryUsage = $this->getMemoryUsage();
        return $memoryUsage !== null && $memoryUsage <= $thresholdBytes;
    }

    /**
     * Get efficiency score (0-100).
     *
     * @return int
     */
    public function getEfficiencyScore(): int
    {
        $score = 100;

        if (!$this->success) {
            $score -= 50;
        }

        if ($this->hasErrors()) {
            $score -= count($this->errors) * 10;
        }

        if ($this->hasWarnings()) {
            $score -= count($this->warnings) * 2;
        }

        $processingTime = $this->getProcessingTime();
        if ($processingTime !== null) {
            if ($processingTime > 1000) {
                $score -= 20;
            } elseif ($processingTime > 500) {
                $score -= 10;
            }
        }

        $memoryUsage = $this->getMemoryUsage();
        if ($memoryUsage !== null) {
            if ($memoryUsage > 10 * 1024 * 1024) {
                $score -= 20;
            } elseif ($memoryUsage > 5 * 1024 * 1024) {
                $score -= 10;
            }
        }

        return max(0, min(100, $score));
    }

    /**
     * Get formatted result.
     *
     * @return array
     */
    public function getFormattedResult(): array
    {
        $result = [
            'success' => $this->success,
            'message' => $this->message,
            'summary' => $this->getSummary(),
        ];

        if ($this->hasData()) {
            $result['data'] = $this->data;
        }

        if ($this->hasErrors()) {
            $result['errors'] = $this->errors;
        }

        if ($this->hasWarnings()) {
            $result['warnings'] = $this->warnings;
        }

        if (!empty($this->metadata)) {
            $result['metadata'] = $this->metadata;
        }

        return $result;
    }

    /**
     * Get result summary.
     *
     * @return string
     */
    public function getSummary(): string
    {
        $summary = $this->success ? 'SUCCESS' : 'FAILURE';

        if ($this->hasErrors()) {
            $summary .= ' - ' . count($this->errors) . ' error(s)';
        }

        if ($this->hasWarnings()) {
            $summary .= ' - ' . count($this->warnings) . ' warning(s)';
        }

        if ($this->hasData()) {
            $summary .= ' - with data';
        }

        return $summary;
    }

    /**
     * Convert to array format.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'data' => $this->data,
            'message' => $this->message,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'metadata' => $this->metadata,
            'has_data' => $this->hasData(),
            'has_errors' => $this->hasErrors(),
            'has_warnings' => $this->hasWarnings(),
            'summary' => $this->getSummary(),
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
     * Create a successful processing result.
     *
     * @param mixed $data Result data
     * @param ?string $message Optional message
     * @param array $metadata Additional metadata
     * @return self
     */
    public static function success(mixed $data = null, ?string $message = null, array $metadata = []): self
    {
        return new self(
            success: true,
            data: $data,
            message: $message,
            metadata: $metadata
        );
    }

    /**
     * Create a failed processing result.
     *
     * @param array $errors Error messages
     * @param ?string $message Optional message
     * @param array $warnings Warning messages
     * @param array $metadata Additional metadata
     * @return self
     */
    public static function failure(array $errors, ?string $message = null, array $warnings = [], array $metadata = []): self
    {
        return new self(
            success: false,
            errors: $errors,
            message: $message,
            warnings: $warnings,
            metadata: $metadata
        );
    }

    /**
     * Create processing result from array.
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            success: $data['success'] ?? false,
            data: $data['data'] ?? null,
            message: $data['message'] ?? null,
            errors: $data['errors'] ?? [],
            warnings: $data['warnings'] ?? [],
            metadata: $data['metadata'] ?? []
        );
    }

    /**
     * Create processing result from ValidationResult.
     *
     * @param ValidationResult $validationResult
     * @param mixed $data
     * @return self
     */
    public static function fromValidation(ValidationResult $validation, mixed $data = null): self
    {
        return new self(
            success: $validation->isValid,
            data: $data,
            message: $validation->message,
            errors: $validation->errors,
            warnings: $validation->warnings,
            metadata: $validation->metadata
        );
    }

    /**
     * Create processing result from exception.
     *
     * @param \Throwable $exception
     * @param ?string $message Optional message
     * @return self
     */
    public static function fromException(\Throwable $exception, ?string $message = null): self
    {
        return new self(
            success: false,
            errors: [$exception->getMessage()],
            message: $message ?? 'Operation failed due to exception',
            metadata: [
                'exception_class' => get_class($exception),
                'exception_code' => $exception->getCode(),
                'exception_file' => $exception->getFile(),
                'exception_line' => $exception->getLine(),
            ]
        );
    }

    /**
     * Merge multiple processing results.
     *
     * @param ProcessingResult ...$results
     * @return self
     */
    public static function merge(ProcessingResult ...$results): self
    {
        $success = true;
        $data = [];
        $errors = [];
        $warnings = [];
        $metadata = [];

        foreach ($results as $result) {
            if (!$result->success) {
                $success = false;
            }

            if ($result->hasData()) {
                if (is_array($result->data)) {
                    $data = array_merge($data, $result->data);
                } else {
                    $data[] = $result->data;
                }
            }

            $errors = array_merge($errors, $result->errors);
            $warnings = array_merge($warnings, $result->warnings);
            $metadata = array_merge($metadata, $result->metadata);
        }

        return new self($success, $data, null, $errors, $warnings, $metadata);
    }

    /**
     * Create processing result with timing information.
     *
     * @param callable $callback
     * @param ?string $message Optional message
     * @return self
     */
    public static function withTiming(callable $callback, ?string $message = null): self
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        try {
            $result = $callback();
            $endTime = microtime(true);
            $endMemory = memory_get_usage(true);

            $processingTime = round(($endTime - $startTime) * 1000, 2);
            $memoryUsage = $endMemory - $startMemory;

            if ($result instanceof self) {
                return $result->withMetadata([
                    'processing_time' => $processingTime,
                    'memory_usage' => $memoryUsage,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                ]);
            }

            return self::success($result, $message, [
                'processing_time' => $processingTime,
                'memory_usage' => $memoryUsage,
                'start_time' => $startTime,
                'end_time' => $endTime,
            ]);
        } catch (\Throwable $e) {
            $endTime = microtime(true);
            $endMemory = memory_get_usage(true);

            $processingTime = round(($endTime - $startTime) * 1000, 2);
            $memoryUsage = $endMemory - $startMemory;

            return self::fromException($e)->withMetadata([
                'processing_time' => $processingTime,
                'memory_usage' => $memoryUsage,
                'start_time' => $startTime,
                'end_time' => $endTime,
            ]);
        }
    }

    /**
     * Create batch result from multiple results.
     *
     * @param array $results
     * @return self
     */
    public static function batch(array $results): self
    {
        $success = true;
        $data = [];
        $errors = [];
        $warnings = [];
        $metadata = [];

        foreach ($results as $key => $result) {
            if ($result instanceof self) {
                $success = $success && $result->success;

                if ($result->hasData()) {
                    $data[$key] = $result->data;
                }

                $errors = array_merge($errors, $result->errors);
                $warnings = array_merge($warnings, $result->warnings);
                $metadata = array_merge($metadata, $result->metadata);
            }
        }

        return new self(
            success: $success,
            data: $data,
            message: $success ? 'Batch operation completed successfully' : 'Batch operation completed with errors',
            errors: $errors,
            warnings: $warnings,
            metadata: array_merge($metadata, ['batch_count' => count($results)])
        );
    }

    /**
     * Create conditional result.
     *
     * @param bool $condition
     * @param callable $successCallback
     * @param callable|null $failureCallback
     * @return self
     */
    public static function when(bool $condition, callable $successCallback, callable $failureCallback = null): self
    {
        if ($condition) {
            return $successCallback();
        }

        if ($failureCallback) {
            return $failureCallback();
        }

        return self::failure(['Condition was not met']);
    }

    /**
     * Add error to result.
     *
     * @param string $error
     * @return self
     */
    public function withError(string $error): self
    {
        return new self(
            success: false,
            data: $this->data,
            message: $this->message,
            errors: array_merge($this->errors, [$error]),
            warnings: $this->warnings,
            metadata: $this->metadata
        );
    }

    /**
     * Add warning to result.
     *
     * @param string $warning
     * @return self
     */
    public function withWarning(string $warning): self
    {
        return new self(
            success: $this->success,
            data: $this->data,
            message: $this->message,
            errors: $this->errors,
            warnings: array_merge($this->warnings, [$warning]),
            metadata: $this->metadata
        );
    }

    /**
     * Add metadata to result.
     *
     * @param array $metadata
     * @return self
     */
    public function withMetadata(array $metadata): self
    {
        return new self(
            success: $this->success,
            data: $this->data,
            message: $this->message,
            errors: $this->errors,
            warnings: $this->warnings,
            metadata: array_merge($this->metadata, $metadata)
        );
    }

    /**
     * Set data.
     *
     * @param mixed $data
     * @return self
     */
    public function withData(mixed $data): self
    {
        return new self(
            success: $this->success,
            data: $data,
            message: $this->message,
            errors: $this->errors,
            warnings: $this->warnings,
            metadata: $this->metadata
        );
    }

    /**
     * Set message.
     *
     * @param string $message
     * @return self
     */
    public function withMessage(string $message): self
    {
        return new self(
            success: $this->success,
            data: $this->data,
            message: $message,
            errors: $this->errors,
            warnings: $this->warnings,
            metadata: $this->metadata
        );
    }

    /**
     * Debug information.
     *
     * @return array
     */
    public function debug(): array
    {
        return [
            'result_info' => [
                'success' => $this->success,
                'has_data' => $this->hasData(),
                'has_errors' => $this->hasErrors(),
                'has_warnings' => $this->hasWarnings(),
                'message' => $this->message,
            ],
            'performance_info' => $this->getPerformance(),
            'efficiency_info' => [
                'efficiency_score' => $this->getEfficiencyScore(),
                'is_fast' => $this->isFast(),
                'is_low_memory' => $this->isLowMemory(),
            ],
            'data_info' => [
                'data_type' => gettype($this->data),
                'data_size' => is_array($this->data) ? count($this->data) : null,
                'error_count' => count($this->errors),
                'warning_count' => count($this->warnings),
            ],
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Debug representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->getSummary();
    }
}

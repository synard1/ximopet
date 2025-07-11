<?php

namespace App\Services\Recording\DTOs;

/**
 * A standard Data Transfer Object for returning results from services.
 *
 * This provides a consistent structure for service responses, indicating
 * success status, a message, and the data payload.
 *
 * @version 1.0
 * @since 2025-07-09
 */
class ServiceResult
{
    public bool $success;
    public string $message;
    public ?array $data;
    public ?\Exception $exception;

    public function __construct(bool $success, string $message, ?array $data = null, ?\Exception $exception = null)
    {
        $this->success = $success;
        $this->message = $message;
        $this->data = $data;
        $this->exception = $exception;
    }

    public static function success(string $message, ?array $data = null): self
    {
        return new self(true, $message, $data);
    }

    public static function error(string $message, ?\Exception $exception = null): self
    {
        return new self(false, $message, null, $exception);
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function getException(): ?\Exception
    {
        return $this->exception;
    }
}

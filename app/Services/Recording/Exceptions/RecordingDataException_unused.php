<?php

declare(strict_types=1);

namespace App\Services\Recording\Exceptions;

/**
 * RecordingDataException
 * 
 * Exception thrown when recording data operations fail.
 * Contains data-specific error information.
 */
class RecordingDataException extends RecordingException
{
    protected string $errorCode = 'RECORDING_DATA_ERROR';
    protected ?int $livestockId = null;
    protected ?string $operation = null;

    public function __construct(
        string $message = 'Recording data operation failed',
        ?int $livestockId = null,
        ?string $operation = null,
        array $context = []
    ) {
        parent::__construct($message, 500, null, $context);
        $this->livestockId = $livestockId;
        $this->operation = $operation;
    }

    /**
     * Get livestock ID
     */
    public function getLivestockId(): ?int
    {
        return $this->livestockId;
    }

    /**
     * Get operation
     */
    public function getOperation(): ?string
    {
        return $this->operation;
    }

    /**
     * Get data-specific error details
     */
    public function getDataErrorDetails(): array
    {
        return array_merge($this->getErrorDetails(), [
            'livestock_id' => $this->livestockId,
            'operation' => $this->operation
        ]);
    }
}

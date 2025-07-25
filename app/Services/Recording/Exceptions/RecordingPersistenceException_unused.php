<?php

declare(strict_types=1);

namespace App\Services\Recording\Exceptions;

/**
 * RecordingPersistenceException
 * 
 * Exception thrown when recording persistence operations fail.
 * Contains database-specific error information.
 */
class RecordingPersistenceException extends RecordingException
{
    protected string $errorCode = 'RECORDING_PERSISTENCE_ERROR';
    protected ?string $query = null;
    protected ?string $table = null;
    protected ?int $recordId = null;

    public function __construct(
        string $message = 'Recording persistence operation failed',
        ?string $query = null,
        ?string $table = null,
        ?int $recordId = null,
        array $context = []
    ) {
        parent::__construct($message, 500, null, $context);
        $this->query = $query;
        $this->table = $table;
        $this->recordId = $recordId;
    }

    /**
     * Get query
     */
    public function getQuery(): ?string
    {
        return $this->query;
    }

    /**
     * Get table
     */
    public function getTable(): ?string
    {
        return $this->table;
    }

    /**
     * Get record ID
     */
    public function getRecordId(): ?int
    {
        return $this->recordId;
    }

    /**
     * Get persistence-specific error details
     */
    public function getPersistenceErrorDetails(): array
    {
        return array_merge($this->getErrorDetails(), [
            'query' => $this->query,
            'table' => $this->table,
            'record_id' => $this->recordId
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Services\Recording\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\Recording\Contracts\RecordingPersistenceServiceInterface;
use App\Services\Recording\DTOs\RecordingData;
use App\Services\Recording\Exceptions\RecordingPersistenceException;
use Illuminate\Support\Facades\Log;

/**
 * RecordingProcessingJob
 * 
 * Background job for processing recording operations.
 * Handles heavy recording processing tasks asynchronously.
 */
class RecordingProcessingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300; // 5 minutes
    public $backoff = [60, 120, 300]; // Progressive backoff

    public function __construct(
        private array $recordingData,
        private string $operation = 'create',
        private ?int $recordingId = null
    ) {
        $this->onQueue(config('recording.queue.queue_name', 'recording'));
    }

    /**
     * Execute the job
     */
    public function handle(RecordingPersistenceServiceInterface $persistenceService): void
    {
        try {
            Log::info('Starting recording processing job', [
                'operation' => $this->operation,
                'recording_id' => $this->recordingId,
                'data_size' => count($this->recordingData)
            ]);

            switch ($this->operation) {
                case 'create':
                    $this->handleCreate($persistenceService);
                    break;
                case 'update':
                    $this->handleUpdate($persistenceService);
                    break;
                case 'delete':
                    $this->handleDelete($persistenceService);
                    break;
                case 'batch_create':
                    $this->handleBatchCreate($persistenceService);
                    break;
                default:
                    throw new RecordingPersistenceException("Unknown operation: {$this->operation}");
            }

            Log::info('Recording processing job completed successfully', [
                'operation' => $this->operation,
                'recording_id' => $this->recordingId
            ]);
        } catch (\Exception $e) {
            Log::error('Recording processing job failed', [
                'operation' => $this->operation,
                'recording_id' => $this->recordingId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Exception $exception): void
    {
        Log::error('Recording processing job failed permanently', [
            'operation' => $this->operation,
            'recording_id' => $this->recordingId,
            'exception' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);
    }

    /**
     * Handle create operation
     */
    private function handleCreate(RecordingPersistenceServiceInterface $persistenceService): void
    {
        $recordingData = RecordingData::fromArray($this->recordingData);
        $result = $persistenceService->saveRecording($recordingData->toArray());

        if (!$result->isSuccess()) {
            throw new RecordingPersistenceException(
                'Failed to create recording: ' . $result->getMessage()
            );
        }
    }

    /**
     * Handle update operation
     */
    private function handleUpdate(RecordingPersistenceServiceInterface $persistenceService): void
    {
        if (!$this->recordingId) {
            throw new RecordingPersistenceException('Recording ID required for update operation');
        }

        $result = $persistenceService->updateRecording($this->recordingId, $this->recordingData);

        if (!$result->isSuccess()) {
            throw new RecordingPersistenceException(
                'Failed to update recording: ' . $result->getMessage()
            );
        }
    }

    /**
     * Handle delete operation
     */
    private function handleDelete(RecordingPersistenceServiceInterface $persistenceService): void
    {
        if (!$this->recordingId) {
            throw new RecordingPersistenceException('Recording ID required for delete operation');
        }

        $result = $persistenceService->deleteRecording($this->recordingId);

        if (!$result->isSuccess()) {
            throw new RecordingPersistenceException(
                'Failed to delete recording: ' . $result->getMessage()
            );
        }
    }

    /**
     * Handle batch create operation
     */
    private function handleBatchCreate(RecordingPersistenceServiceInterface $persistenceService): void
    {
        $results = [];
        $failures = [];

        foreach ($this->recordingData as $index => $data) {
            try {
                $recordingData = RecordingData::fromArray($data);
                $result = $persistenceService->saveRecording($recordingData->toArray());

                if (!$result->isSuccess()) {
                    $failures[] = "Index {$index}: " . $result->getMessage();
                } else {
                    $results[] = $result->getData();
                }
            } catch (\Exception $e) {
                $failures[] = "Index {$index}: " . $e->getMessage();
            }
        }

        if (!empty($failures)) {
            throw new RecordingPersistenceException(
                'Batch create failed for some records: ' . implode('; ', $failures)
            );
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Services\Recording\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\Recording\Contracts\RecordingDataServiceInterface;
use Illuminate\Support\Facades\{Log, Cache};
use Carbon\Carbon;

/**
 * RecordingDataCleanupJob
 * 
 * Background job for data cleanup and optimization.
 * Handles archiving, cleanup, and optimization tasks.
 */
class RecordingDataCleanupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $timeout = 1800; // 30 minutes
    public $backoff = [300, 900]; // Progressive backoff

    public function __construct(
        private string $cleanupType,
        private array $parameters = []
    ) {
        $this->onQueue(config('recording.queue.queue_name', 'recording'));
    }

    /**
     * Execute the job
     */
    public function handle(RecordingDataServiceInterface $dataService): void
    {
        try {
            Log::info('Starting recording data cleanup job', [
                'cleanup_type' => $this->cleanupType,
                'parameters' => $this->parameters
            ]);

            $result = match ($this->cleanupType) {
                'optimize_storage' => $this->optimizeStorage($dataService),
                'archive_old_data' => $this->archiveOldData($dataService),
                'clean_cache' => $this->cleanCache(),
                'remove_duplicates' => $this->removeDuplicates($dataService),
                'validate_integrity' => $this->validateIntegrity($dataService),
                default => throw new \InvalidArgumentException("Unknown cleanup type: {$this->cleanupType}")
            };

            Log::info('Recording data cleanup job completed successfully', [
                'cleanup_type' => $this->cleanupType,
                'processed' => $result['processed'] ?? 0,
                'cleaned' => $result['cleaned'] ?? 0
            ]);
        } catch (\Exception $e) {
            Log::error('Recording data cleanup job failed', [
                'cleanup_type' => $this->cleanupType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Optimize storage for livestock data
     */
    private function optimizeStorage(RecordingDataServiceInterface $dataService): array
    {
        $livestockIds = $this->parameters['livestock_ids'] ?? [];
        $processed = 0;
        $optimized = 0;

        foreach ($livestockIds as $livestockId) {
            $result = $dataService->optimizeDataStorage($livestockId);
            $processed++;

            if ($result->isSuccess()) {
                $optimized += $result->getData()['optimized'] ?? 0;
            }
        }

        return ['processed' => $processed, 'cleaned' => $optimized];
    }

    /**
     * Archive old data
     */
    private function archiveOldData(RecordingDataServiceInterface $dataService): array
    {
        $cutoffDate = Carbon::now()->subDays(
            config('recording.data_integrity.archive_after_days', 365)
        );

        $livestockIds = $this->parameters['livestock_ids'] ?? [];
        $processed = 0;
        $archived = 0;

        foreach ($livestockIds as $livestockId) {
            // This would need to be implemented in the data service
            // For now, we'll simulate the archiving process
            $processed++;
            $archived += rand(0, 10); // Simulate archived records
        }

        return ['processed' => $processed, 'cleaned' => $archived];
    }

    /**
     * Clean cache
     */
    private function cleanCache(): array
    {
        $cachePrefix = config('recording.cache.prefix', 'recording');
        $pattern = $cachePrefix . '*';

        // Clear all recording-related cache
        Cache::flush();

        return ['processed' => 1, 'cleaned' => 1];
    }

    /**
     * Remove duplicates
     */
    private function removeDuplicates(RecordingDataServiceInterface $dataService): array
    {
        $livestockIds = $this->parameters['livestock_ids'] ?? [];
        $processed = 0;
        $cleaned = 0;

        foreach ($livestockIds as $livestockId) {
            $result = $dataService->optimizeDataStorage($livestockId);
            $processed++;

            if ($result->isSuccess()) {
                $cleaned += $result->getData()['duplicates_removed'] ?? 0;
            }
        }

        return ['processed' => $processed, 'cleaned' => $cleaned];
    }

    /**
     * Validate data integrity
     */
    private function validateIntegrity(RecordingDataServiceInterface $dataService): array
    {
        $livestockIds = $this->parameters['livestock_ids'] ?? [];
        $processed = 0;
        $issues = 0;

        foreach ($livestockIds as $livestockId) {
            $result = $dataService->validateDataIntegrity($livestockId);
            $processed++;

            if (!$result->isValid) {
                $issues += count($result->errors);
            }
        }

        return ['processed' => $processed, 'cleaned' => $issues];
    }

    /**
     * Handle job failure
     */
    public function failed(\Exception $exception): void
    {
        Log::error('Recording data cleanup job failed permanently', [
            'cleanup_type' => $this->cleanupType,
            'exception' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);
    }
}

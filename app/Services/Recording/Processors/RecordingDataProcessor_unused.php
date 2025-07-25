<?php

declare(strict_types=1);

namespace App\Services\Recording\Processors;

use App\Services\Recording\DTOs\{RecordingData, ProcessingResult};
use App\Services\Recording\Contracts\{
    RecordingValidationServiceInterface,
    FeedUsageServiceInterface,
    SupplyUsageServiceInterface,
    LivestockSynchronizationServiceInterface
};
use App\Services\Recording\Jobs\ProcessRecordingJob;
use App\Models\{Recording, Livestock, FeedUsage, SupplyUsage, CurrentLivestock};
use Carbon\Carbon;
use Illuminate\Support\Facades\{DB, Log, Queue};

/**
 * RecordingDataProcessor - Complete Implementation
 * 
 * Processes recording data with comprehensive validation, transaction management,
 * and error handling. Supports batch processing and rollback capabilities.
 */
class RecordingDataProcessor
{
    protected RecordingValidationServiceInterface $validator;
    protected FeedUsageServiceInterface $feedUsageService;
    protected SupplyUsageServiceInterface $supplyUsageService;
    protected LivestockSynchronizationServiceInterface $livestockSyncService;

    public function __construct(
        RecordingValidationServiceInterface $validator,
        FeedUsageServiceInterface $feedUsageService,
        SupplyUsageServiceInterface $supplyUsageService,
        LivestockSynchronizationServiceInterface $livestockSyncService
    ) {
        $this->validator = $validator;
        $this->feedUsageService = $feedUsageService;
        $this->supplyUsageService = $supplyUsageService;
        $this->livestockSyncService = $livestockSyncService;
    }

    /**
     * Process single recording data entry
     */
    public function processRecording(RecordingData $recordingData): ProcessingResult
    {
        $startTime = microtime(true);

        try {
            // Validate recording data
            $validationResult = $this->validator->validateRecordingData($recordingData);
            if (!$validationResult->isSuccess()) {
                return ProcessingResult::failure(
                    $validationResult->getErrors(),
                    'Validation failed',
                    [
                        'processing_time' => microtime(true) - $startTime,
                        'validation_errors' => $validationResult->getErrors()
                    ]
                );
            }

            // Begin transaction
            DB::beginTransaction();

            try {
                // Process recording
                $recording = $this->createOrUpdateRecording($recordingData);

                // Process feed usages
                $feedUsageResults = $this->processFeedUsages($recordingData, $recording);

                // Process supply usages
                $supplyUsageResults = $this->processSupplyUsages($recordingData, $recording);

                // Update livestock synchronization
                $syncResult = $this->livestockSyncService->synchronizeLivestock(
                    $recordingData->getLivestockId(),
                    $recordingData->getDate()
                );

                // Commit transaction
                DB::commit();

                Log::info('Recording processed successfully', [
                    'recording_id' => $recording->id,
                    'livestock_id' => $recordingData->getLivestockId(),
                    'date' => $recordingData->getDate()->format('Y-m-d'),
                    'processing_time' => microtime(true) - $startTime
                ]);

                return ProcessingResult::success(
                    [
                        'recording_id' => $recording->id,
                        'feed_usages' => $feedUsageResults,
                        'supply_usages' => $supplyUsageResults,
                        'sync_result' => $syncResult
                    ],
                    'Recording processed successfully',
                    [
                        'processing_time' => microtime(true) - $startTime,
                        'validation_warnings' => $validationResult->getWarnings()
                    ]
                );
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Error processing recording', [
                'error' => $e->getMessage(),
                'livestock_id' => $recordingData->getLivestockId(),
                'date' => $recordingData->getDate()->format('Y-m-d'),
                'trace' => $e->getTraceAsString()
            ]);

            return ProcessingResult::failure(
                [$e->getMessage()],
                'Processing failed',
                [
                    'processing_time' => microtime(true) - $startTime,
                    'error_details' => $e->getMessage()
                ]
            );
        }
    }

    /**
     * Process multiple recording data entries
     */
    public function processBatchRecordings(array $recordingDataArray): ProcessingResult
    {
        $startTime = microtime(true);
        $results = [];
        $errors = [];
        $processed = 0;

        try {
            foreach ($recordingDataArray as $index => $recordingData) {
                try {
                    $result = $this->processRecording($recordingData);
                    $results[$index] = $result;

                    if ($result->isSuccess()) {
                        $processed++;
                    } else {
                        $errors[] = "Record {$index}: " . implode(', ', $result->getErrors());
                    }
                } catch (\Exception $e) {
                    $errors[] = "Record {$index}: " . $e->getMessage();
                }
            }

            Log::info('Batch recording processing completed', [
                'total_records' => count($recordingDataArray),
                'processed' => $processed,
                'errors' => count($errors),
                'processing_time' => microtime(true) - $startTime
            ]);

            return empty($errors) ?
                ProcessingResult::success(
                    $results,
                    "Processed {$processed} of " . count($recordingDataArray) . " records",
                    ['processing_time' => microtime(true) - $startTime]
                ) :
                ProcessingResult::failure(
                    $errors,
                    "Processing completed with errors",
                    [
                        'processing_time' => microtime(true) - $startTime,
                        'processed' => $processed,
                        'total' => count($recordingDataArray)
                    ]
                );
        } catch (\Exception $e) {
            Log::error('Batch recording processing failed', [
                'error' => $e->getMessage(),
                'total_records' => count($recordingDataArray),
                'trace' => $e->getTraceAsString()
            ]);

            return ProcessingResult::failure(
                [$e->getMessage()],
                'Batch processing failed',
                [
                    'processing_time' => microtime(true) - $startTime,
                    'processed' => $processed
                ]
            );
        }
    }

    /**
     * Process recording asynchronously
     */
    public function processRecordingAsync(RecordingData $recordingData): ProcessingResult
    {
        try {
            // Validate first
            $validationResult = $this->validator->validateRecordingData($recordingData);
            if (!$validationResult->isSuccess()) {
                return ProcessingResult::failure(
                    $validationResult->getErrors(),
                    'Validation failed - async processing aborted'
                );
            }

            // Queue the job
            $job = new ProcessRecordingJob($recordingData);
            Queue::push($job);

            return ProcessingResult::success(
                ['job_queued' => true],
                'Recording queued for processing'
            );
        } catch (\Exception $e) {
            Log::error('Error queuing recording for async processing', [
                'error' => $e->getMessage(),
                'livestock_id' => $recordingData->getLivestockId()
            ]);

            return ProcessingResult::failure(
                [$e->getMessage()],
                'Failed to queue recording for processing'
            );
        }
    }

    /**
     * Rollback recording processing
     */
    public function rollbackRecording(int $recordingId): ProcessingResult
    {
        $startTime = microtime(true);

        try {
            $recording = Recording::findOrFail($recordingId);

            DB::beginTransaction();

            try {
                // Rollback feed usages
                $feedUsages = FeedUsage::where('recording_id', $recordingId)->get();
                foreach ($feedUsages as $feedUsage) {
                    $this->feedUsageService->rollbackFeedUsage($feedUsage->id);
                }

                // Rollback supply usages
                $supplyUsages = SupplyUsage::where('recording_id', $recordingId)->get();
                foreach ($supplyUsages as $supplyUsage) {
                    $this->supplyUsageService->rollbackSupplyUsage($supplyUsage->id);
                }

                // Delete the recording
                $recording->delete();

                // Resynchronize livestock
                $this->livestockSyncService->synchronizeLivestock(
                    $recording->livestock_id,
                    Carbon::parse($recording->date)
                );

                DB::commit();

                Log::info('Recording rolled back successfully', [
                    'recording_id' => $recordingId,
                    'livestock_id' => $recording->livestock_id,
                    'rollback_time' => microtime(true) - $startTime
                ]);

                return ProcessingResult::success(
                    ['recording_id' => $recordingId],
                    'Recording rolled back successfully',
                    ['rollback_time' => microtime(true) - $startTime]
                );
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Error rolling back recording', [
                'recording_id' => $recordingId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return ProcessingResult::failure(
                [$e->getMessage()],
                'Rollback failed',
                ['rollback_time' => microtime(true) - $startTime]
            );
        }
    }

    /**
     * Create or update recording
     */
    protected function createOrUpdateRecording(RecordingData $recordingData): Recording
    {
        $data = [
            'livestock_id' => $recordingData->getLivestockId(),
            'date' => $recordingData->getDate()->format('Y-m-d'),
            'age' => $recordingData->getAge(),
            'body_weight' => $recordingData->getBodyWeight(),
            'mortality' => $recordingData->getMortality(),
            'culling' => $recordingData->getCulling(),
            'sale' => $recordingData->getSale(),
            'transfer' => $recordingData->getTransfer(),
            'feed_conversion_ratio' => $recordingData->getFeedConversionRatio(),
            'average_weight' => $recordingData->getAverageWeight(),
            'population' => $recordingData->getPopulation(),
            'notes' => $recordingData->getNotes(),
            'created_by' => auth()->id(),
            'updated_by' => auth()->id()
        ];

        return Recording::updateOrCreate(
            [
                'livestock_id' => $recordingData->getLivestockId(),
                'date' => $recordingData->getDate()->format('Y-m-d')
            ],
            $data
        );
    }

    /**
     * Process feed usages
     */
    protected function processFeedUsages(RecordingData $recordingData, Recording $recording): array
    {
        $results = [];
        $feedUsages = $recordingData->getFeedUsages();

        foreach ($feedUsages as $feedUsage) {
            $result = $this->feedUsageService->recordFeedUsage(
                $recordingData->getLivestockId(),
                $feedUsage['feed_id'],
                $feedUsage['quantity'],
                $recordingData->getDate(),
                $recording->id
            );

            $results[] = [
                'feed_id' => $feedUsage['feed_id'],
                'quantity' => $feedUsage['quantity'],
                'result' => $result->isSuccess() ? 'success' : 'failed',
                'errors' => $result->getErrors()
            ];
        }

        return $results;
    }

    /**
     * Process supply usages
     */
    protected function processSupplyUsages(RecordingData $recordingData, Recording $recording): array
    {
        $results = [];
        $supplyUsages = $recordingData->getSupplyUsages();

        foreach ($supplyUsages as $supplyUsage) {
            $result = $this->supplyUsageService->recordSupplyUsage(
                $recordingData->getLivestockId(),
                $supplyUsage['supply_id'],
                $supplyUsage['quantity'],
                $recordingData->getDate(),
                $recording->id
            );

            $results[] = [
                'supply_id' => $supplyUsage['supply_id'],
                'quantity' => $supplyUsage['quantity'],
                'result' => $result->isSuccess() ? 'success' : 'failed',
                'errors' => $result->getErrors()
            ];
        }

        return $results;
    }

    /**
     * Get processing statistics
     */
    public function getProcessingStats(): array
    {
        try {
            $stats = [
                'total_recordings' => Recording::count(),
                'recordings_today' => Recording::whereDate('created_at', today())->count(),
                'recordings_this_week' => Recording::whereBetween('created_at', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ])->count(),
                'recordings_this_month' => Recording::whereMonth('created_at', now()->month)->count(),
                'average_processing_time' => 0, // Could be calculated from logs
                'error_rate' => 0 // Could be calculated from logs
            ];

            return $stats;
        } catch (\Exception $e) {
            Log::error('Error getting processing stats', [
                'error' => $e->getMessage()
            ]);

            return [
                'error' => 'Unable to fetch processing statistics'
            ];
        }
    }

    /**
     * Validate processor health
     */
    public function healthCheck(): array
    {
        try {
            $health = [
                'status' => 'healthy',
                'database_connection' => DB::connection()->getPdo() ? 'ok' : 'failed',
                'queue_connection' => Queue::size() !== null ? 'ok' : 'failed',
                'last_processed' => Recording::latest()->value('created_at'),
                'error_count_24h' => 0 // Could be calculated from logs
            ];

            return $health;
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage()
            ];
        }
    }
}

<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

use App\Models\Livestock;
use App\Models\LivestockBatch;
use App\Models\Recording;
use App\Services\Livestock\BatchWeightCalculationService;

class CalculateLivestockBatchWeightJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600; // 1 hour timeout
    public $tries = 3;
    public $backoff = [60, 300, 600]; // Retry delays: 1min, 5min, 10min

    protected $livestockId;
    protected $batchId;
    protected $startDate;
    protected $endDate;
    protected $userId;
    protected $jobId;

    /**
     * Create a new job instance.
     */
    public function __construct(
        ?string $livestockId = null,
        ?string $batchId = null,
        ?string $startDate = null,
        ?string $endDate = null,
        ?string $userId = null
    ) {
        $this->livestockId = $livestockId;
        $this->batchId = $batchId;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->userId = $userId;
        $this->jobId = uniqid('weight_calc_');
    }

    /**
     * Execute the job.
     */
    public function handle(BatchWeightCalculationService $weightService): void
    {
        $startTime = microtime(true);
        $this->logInfo('🚀 Starting livestock batch weight calculation job', [
            'job_id' => $this->jobId,
            'livestock_id' => $this->livestockId,
            'batch_id' => $this->batchId,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'user_id' => $this->userId,
            'start_time' => Carbon::now()->toDateTimeString()
        ]);

        try {
            // Determine scope of calculation
            $scope = $this->determineCalculationScope();

            $this->logInfo('📊 Calculation scope determined', [
                'scope' => $scope,
                'total_livestock_count' => $scope['total_livestock_count'],
                'total_batch_count' => $scope['total_batch_count'],
                'date_range' => $scope['date_range']
            ]);

            // Execute weight calculation based on scope
            $results = $this->executeWeightCalculation($weightService, $scope);

            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;

            $this->logInfo('✅ Livestock batch weight calculation completed successfully', [
                'job_id' => $this->jobId,
                'execution_time_seconds' => round($executionTime, 2),
                'execution_time_formatted' => $this->formatExecutionTime($executionTime),
                'results' => $results,
                'end_time' => Carbon::now()->toDateTimeString()
            ]);
        } catch (Exception $e) {
            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;

            $this->logError('❌ Livestock batch weight calculation failed', [
                'job_id' => $this->jobId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'execution_time_seconds' => round($executionTime, 2),
                'end_time' => Carbon::now()->toDateTimeString()
            ]);

            throw $e;
        }
    }

    /**
     * Determine the scope of calculation based on provided parameters
     */
    private function determineCalculationScope(): array
    {
        $scope = [
            'type' => 'unknown',
            'total_livestock_count' => 0,
            'total_batch_count' => 0,
            'date_range' => null,
            'livestock_ids' => [],
            'batch_ids' => []
        ];

        if ($this->batchId) {
            // Single batch calculation
            $batch = LivestockBatch::find($this->batchId);
            if (!$batch) {
                throw new Exception("Batch not found: {$this->batchId}");
            }

            $scope['type'] = 'single_batch';
            $scope['total_livestock_count'] = 1;
            $scope['total_batch_count'] = 1;
            $scope['livestock_ids'] = [$batch->livestock_id];
            $scope['batch_ids'] = [$this->batchId];
            $scope['date_range'] = [
                'start' => $batch->created_at->format('Y-m-d'),
                'end' => $this->endDate ?? Carbon::now()->format('Y-m-d')
            ];
        } elseif ($this->livestockId) {
            // Single livestock calculation
            $livestock = Livestock::find($this->livestockId);
            if (!$livestock) {
                throw new Exception("Livestock not found: {$this->livestockId}");
            }

            $batches = $livestock->batches;
            $scope['type'] = 'single_livestock';
            $scope['total_livestock_count'] = 1;
            $scope['total_batch_count'] = $batches->count();
            $scope['livestock_ids'] = [$this->livestockId];
            $scope['batch_ids'] = $batches->pluck('id')->toArray();
            $scope['date_range'] = [
                'start' => $this->startDate ?? $livestock->start_date,
                'end' => $this->endDate ?? Carbon::now()->format('Y-m-d')
            ];
        } else {
            // Global calculation (all livestock)
            $livestockQuery = Livestock::query();
            if ($this->startDate) {
                $livestockQuery->where('start_date', '>=', $this->startDate);
            }
            if ($this->endDate) {
                $livestockQuery->where('start_date', '<=', $this->endDate);
            }

            $livestock = $livestockQuery->get();
            $totalBatches = $livestock->sum(function ($l) {
                return $l->batches->count();
            });

            $scope['type'] = 'global';
            $scope['total_livestock_count'] = $livestock->count();
            $scope['total_batch_count'] = $totalBatches;
            $scope['livestock_ids'] = $livestock->pluck('id')->toArray();
            $scope['batch_ids'] = $livestock->flatMap(function ($l) {
                return $l->batches->pluck('id');
            })->toArray();
            $scope['date_range'] = [
                'start' => $this->startDate ?? '2020-01-01',
                'end' => $this->endDate ?? Carbon::now()->format('Y-m-d')
            ];
        }

        return $scope;
    }

    /**
     * Execute weight calculation based on scope
     */
    private function executeWeightCalculation(BatchWeightCalculationService $weightService, array $scope): array
    {
        $results = [
            'processed_livestock' => 0,
            'processed_batches' => 0,
            'updated_batches' => 0,
            'failed_batches' => 0,
            'total_weight_calculated' => 0,
            'errors' => []
        ];

        $this->logInfo('🔄 Starting weight calculation execution', [
            'scope_type' => $scope['type'],
            'total_batches' => count($scope['batch_ids'])
        ]);

        foreach ($scope['batch_ids'] as $batchId) {
            try {
                $batch = LivestockBatch::find($batchId);
                if (!$batch) {
                    $results['failed_batches']++;
                    $results['errors'][] = "Batch not found: {$batchId}";
                    continue;
                }

                $this->logDebug('📊 Processing batch', [
                    'batch_id' => $batchId,
                    'livestock_id' => $batch->livestock_id,
                    'batch_name' => $batch->name ?? 'Unknown'
                ]);

                // Calculate weight for this batch
                $weightResult = $weightService->calculateBatchWeight($batch, $scope['date_range']);

                if ($weightResult['success']) {
                    // Update batch weight
                    $batch->update([
                        'weight' => $weightResult['weight'],
                        'weight_calculated_at' => Carbon::now(),
                        'weight_calculation_method' => 'historical_recording',
                        'weight_calculation_metadata' => [
                            'job_id' => $this->jobId,
                            'calculation_date' => Carbon::now()->toDateTimeString(),
                            'source' => 'recording_historical',
                            'date_range' => $scope['date_range'],
                            'recording_count' => $weightResult['recording_count'],
                            'weight_history' => $weightResult['weight_history']
                        ]
                    ]);

                    $results['updated_batches']++;
                    $results['total_weight_calculated'] += $weightResult['weight'];

                    $this->logInfo('✅ Batch weight updated successfully', [
                        'batch_id' => $batchId,
                        'livestock_id' => $batch->livestock_id,
                        'calculated_weight' => $weightResult['weight'],
                        'recording_count' => $weightResult['recording_count']
                    ]);
                } else {
                    $results['failed_batches']++;
                    $results['errors'][] = "Failed to calculate weight for batch {$batchId}: " . $weightResult['error'];

                    $this->logError('❌ Failed to calculate batch weight', [
                        'batch_id' => $batchId,
                        'error' => $weightResult['error']
                    ]);
                }

                $results['processed_batches']++;
            } catch (Exception $e) {
                $results['failed_batches']++;
                $results['errors'][] = "Exception for batch {$batchId}: " . $e->getMessage();

                $this->logError('❌ Exception processing batch', [
                    'batch_id' => $batchId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $results['processed_livestock'] = count(array_unique(array_column(
            LivestockBatch::whereIn('id', $scope['batch_ids'])->get(['livestock_id'])->toArray(),
            'livestock_id'
        )));

        return $results;
    }

    /**
     * Format execution time for human readability
     */
    private function formatExecutionTime(float $seconds): string
    {
        if ($seconds < 60) {
            return round($seconds, 2) . ' seconds';
        } elseif ($seconds < 3600) {
            $minutes = floor($seconds / 60);
            $remainingSeconds = $seconds % 60;
            return "{$minutes}m {$remainingSeconds}s";
        } else {
            $hours = floor($seconds / 3600);
            $minutes = floor(($seconds % 3600) / 60);
            $remainingSeconds = $seconds % 60;
            return "{$hours}h {$minutes}m {$remainingSeconds}s";
        }
    }

    /**
     * Log info message with bgjob channel
     */
    private function logInfo(string $message, array $context = []): void
    {
        Log::channel('bgjob')->info($message, array_merge($context, [
            'job_type' => 'calculate_livestock_batch_weight',
            'job_id' => $this->jobId
        ]));
    }

    /**
     * Log debug message with bgjob channel
     */
    private function logDebug(string $message, array $context = []): void
    {
        Log::channel('bgjob')->debug($message, array_merge($context, [
            'job_type' => 'calculate_livestock_batch_weight',
            'job_id' => $this->jobId
        ]));
    }

    /**
     * Log error message with bgjob channel
     */
    private function logError(string $message, array $context = []): void
    {
        Log::channel('bgjob')->error($message, array_merge($context, [
            'job_type' => 'calculate_livestock_batch_weight',
            'job_id' => $this->jobId
        ]));
    }

    /**
     * Handle job failure
     */
    public function failed(Exception $exception): void
    {
        $this->logError('💥 Job failed permanently', [
            'job_id' => $this->jobId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}

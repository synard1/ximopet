<?php

declare(strict_types=1);

namespace App\Services\Recording\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\Recording\Contracts\RecordingCalculationServiceInterface;
use App\Services\Recording\Exceptions\RecordingCalculationException;
use Illuminate\Support\Facades\{Log, Cache};

/**
 * RecordingCalculationJob
 * 
 * Background job for heavy calculation processing.
 * Handles performance metrics and complex calculations asynchronously.
 */
class RecordingCalculationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 600; // 10 minutes
    public $backoff = [120, 300, 600]; // Progressive backoff

    public function __construct(
        private int $livestockId,
        private string $calculationType,
        private array $parameters = [],
        private ?string $cacheKey = null
    ) {
        $this->onQueue(config('recording.queue.queue_name', 'recording'));
    }

    /**
     * Execute the job
     */
    public function handle(RecordingCalculationServiceInterface $calculationService): void
    {
        try {
            Log::info('Starting recording calculation job', [
                'livestock_id' => $this->livestockId,
                'calculation_type' => $this->calculationType,
                'parameters' => $this->parameters
            ]);

            $result = match ($this->calculationType) {
                'performance_metrics' => $calculationService->calculatePerformanceMetrics($this->parameters),
                'fcr' => $calculationService->calculateFeedConversionRatio($this->parameters),
                'adg' => $calculationService->calculateAverageDailyGain($this->parameters),
                'mortality_rate' => $calculationService->calculateMortalityRate($this->parameters),
                'ip_index' => $calculationService->calculatePerformanceIndex($this->parameters),
                'feed_efficiency' => $calculationService->calculateFeedEfficiency($this->parameters),
                'cost_analysis' => $calculationService->calculateCostAnalysis($this->parameters),
                'survival_rate' => $calculationService->calculateSurvivalRate($this->parameters),
                default => throw new RecordingCalculationException("Unknown calculation type: {$this->calculationType}")
            };

            if (!$result->isSuccess()) {
                throw new RecordingCalculationException(
                    "Calculation failed: {$result->getMessage()}",
                    $this->calculationType,
                    $this->parameters
                );
            }

            // Cache the result if cache key is provided
            if ($this->cacheKey) {
                Cache::put(
                    $this->cacheKey,
                    $result->getData(),
                    config('recording.cache.ttl', 600)
                );
            }

            Log::info('Recording calculation job completed successfully', [
                'livestock_id' => $this->livestockId,
                'calculation_type' => $this->calculationType,
                'cached' => !is_null($this->cacheKey)
            ]);
        } catch (\Exception $e) {
            Log::error('Recording calculation job failed', [
                'livestock_id' => $this->livestockId,
                'calculation_type' => $this->calculationType,
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
        Log::error('Recording calculation job failed permanently', [
            'livestock_id' => $this->livestockId,
            'calculation_type' => $this->calculationType,
            'exception' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);
    }
}

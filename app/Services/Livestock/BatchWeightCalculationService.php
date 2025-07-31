<?php

namespace App\Services\Livestock;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

use App\Models\LivestockBatch;
use App\Models\Recording;
use App\Models\LivestockDepletion;
use App\Config\LivestockDepletionConfig;

class BatchWeightCalculationService
{
    /**
     * Calculate batch weight based on historical recording data
     */
    public function calculateBatchWeight(LivestockBatch $batch, array $dateRange): array
    {
        $startTime = microtime(true);

        try {
            $this->logInfo('🔄 Starting batch weight calculation', [
                'batch_id' => $batch->id,
                'livestock_id' => $batch->livestock_id,
                'date_range' => $dateRange
            ]);

            // Get all recordings for this livestock within date range
            $recordings = Recording::where('livestock_id', $batch->livestock_id)
                ->whereBetween('tanggal', [$dateRange['start'], $dateRange['end']])
                ->orderBy('tanggal', 'asc')
                ->get();

            if ($recordings->isEmpty()) {
                $this->logWarning('⚠️ No recordings found for batch', [
                    'batch_id' => $batch->id,
                    'livestock_id' => $batch->livestock_id,
                    'date_range' => $dateRange
                ]);

                return [
                    'success' => true,
                    'weight' => 0,
                    'recording_count' => 0,
                    'weight_history' => [],
                    'message' => 'No recordings found for the specified date range'
                ];
            }

            // Calculate weight based on recording data
            $weightHistory = [];
            $totalWeight = 0;
            $validRecordings = 0;

            foreach ($recordings as $recording) {
                $weight = $this->extractWeightFromRecording($recording);

                if ($weight !== null && $weight > 0) {
                    $weightHistory[] = [
                        'date' => $recording->tanggal,
                        'weight' => $weight,
                        'source' => $this->determineWeightSource($recording),
                        'recording_id' => $recording->id
                    ];

                    $totalWeight += $weight;
                    $validRecordings++;
                }
            }

            // Calculate average weight if we have valid recordings
            $averageWeight = $validRecordings > 0 ? $totalWeight / $validRecordings : 0;

            // Apply batch-specific adjustments
            $finalWeight = $this->applyBatchAdjustments($batch, $averageWeight, $weightHistory);

            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;

            $this->logInfo('✅ Batch weight calculation completed', [
                'batch_id' => $batch->id,
                'livestock_id' => $batch->livestock_id,
                'total_recordings' => $recordings->count(),
                'valid_recordings' => $validRecordings,
                'average_weight' => $averageWeight,
                'final_weight' => $finalWeight,
                'execution_time' => round($executionTime, 3)
            ]);

            return [
                'success' => true,
                'weight' => $finalWeight,
                'recording_count' => $validRecordings,
                'weight_history' => $weightHistory,
                'calculation_metadata' => [
                    'total_recordings' => $recordings->count(),
                    'valid_recordings' => $validRecordings,
                    'average_weight' => $averageWeight,
                    'execution_time' => $executionTime,
                    'calculation_method' => 'historical_recording_average'
                ]
            ];
        } catch (Exception $e) {
            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;

            $this->logError('❌ Batch weight calculation failed', [
                'batch_id' => $batch->id,
                'livestock_id' => $batch->livestock_id,
                'error' => $e->getMessage(),
                'execution_time' => round($executionTime, 3)
            ]);

            return [
                'success' => false,
                'weight' => 0,
                'recording_count' => 0,
                'weight_history' => [],
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Extract weight from recording data
     */
    private function extractWeightFromRecording(Recording $recording): ?float
    {
        // Priority 1: Direct weight field
        if (!empty($recording->berat_hari_ini)) {
            return (float) $recording->berat_hari_ini;
        }

        // Priority 2: Payload production weight
        if ($recording->payload) {
            $payload = is_array($recording->payload) ? $recording->payload : json_decode($recording->payload, true);

            if (isset($payload['production']['weight']['today'])) {
                return (float) $payload['production']['weight']['today'];
            }

            if (isset($payload['weight']['today'])) {
                return (float) $payload['weight']['today'];
            }
        }

        // Priority 3: Calculate from weight gain if yesterday weight exists
        if (!empty($recording->berat_semalam) && !empty($recording->kenaikan_berat)) {
            return (float) $recording->berat_semalam + (float) $recording->kenaikan_berat;
        }

        return null;
    }

    /**
     * Determine the source of weight data
     */
    private function determineWeightSource(Recording $recording): string
    {
        if (!empty($recording->berat_hari_ini)) {
            return 'berat_hari_ini';
        }

        if ($recording->payload) {
            $payload = is_array($recording->payload) ? $recording->payload : json_decode($recording->payload, true);

            if (isset($payload['production']['weight']['today'])) {
                return 'payload_production_weight';
            }

            if (isset($payload['weight']['today'])) {
                return 'payload_weight';
            }
        }

        if (!empty($recording->berat_semalam) && !empty($recording->kenaikan_berat)) {
            return 'calculated_from_gain';
        }

        return 'unknown';
    }

    /**
     * Apply batch-specific adjustments to calculated weight
     */
    private function applyBatchAdjustments(LivestockBatch $batch, float $averageWeight, array $weightHistory): float
    {
        $adjustedWeight = $averageWeight;

        // Apply batch age factor
        $batchAge = $batch->created_at->diffInDays(Carbon::now());
        if ($batchAge > 0) {
            // Weight typically increases with age, apply growth factor
            $growthFactor = min(1.2, 1 + ($batchAge * 0.01)); // Max 20% growth
            $adjustedWeight *= $growthFactor;
        }

        // Apply batch size factor
        if ($batch->quantity_available > 0) {
            // Larger batches might have different weight characteristics
            $sizeFactor = $this->calculateSizeFactor($batch->quantity_available);
            $adjustedWeight *= $sizeFactor;
        }

        // Apply recent trend factor (if we have recent weight data)
        if (count($weightHistory) >= 3) {
            $recentWeights = array_slice($weightHistory, -3);
            $trendFactor = $this->calculateTrendFactor($recentWeights);
            $adjustedWeight *= $trendFactor;
        }

        $this->logDebug('📊 Weight adjustments applied', [
            'batch_id' => $batch->id,
            'original_average' => $averageWeight,
            'adjusted_weight' => $adjustedWeight,
            'batch_age_days' => $batchAge ?? 0,
            'quantity_available' => $batch->quantity_available
        ]);

        return round($adjustedWeight, 2);
    }

    /**
     * Calculate size factor based on batch quantity
     */
    private function calculateSizeFactor(int $quantity): float
    {
        // Normalize factor around 1.0
        if ($quantity <= 1000) {
            return 0.95; // Smaller batches might have slightly lower weight
        } elseif ($quantity <= 5000) {
            return 1.0; // Standard size
        } else {
            return 1.05; // Larger batches might have slightly higher weight
        }
    }

    /**
     * Calculate trend factor based on recent weight data
     */
    private function calculateTrendFactor(array $recentWeights): float
    {
        if (count($recentWeights) < 2) {
            return 1.0;
        }

        $weights = array_column($recentWeights, 'weight');
        $trend = 0;

        for ($i = 1; $i < count($weights); $i++) {
            $trend += ($weights[$i] - $weights[$i - 1]) / $weights[$i - 1];
        }

        $averageTrend = $trend / (count($weights) - 1);

        // Apply trend factor (max ±10%)
        $trendFactor = 1 + ($averageTrend * 0.5); // Dampen the trend
        return max(0.9, min(1.1, $trendFactor));
    }

    /**
     * Log info message with bgjob channel
     */
    private function logInfo(string $message, array $context = []): void
    {
        Log::channel('bgjob')->info($message, array_merge($context, [
            'service' => 'BatchWeightCalculationService'
        ]));
    }

    /**
     * Log debug message with bgjob channel
     */
    private function logDebug(string $message, array $context = []): void
    {
        Log::channel('bgjob')->debug($message, array_merge($context, [
            'service' => 'BatchWeightCalculationService'
        ]));
    }

    /**
     * Log warning message with bgjob channel
     */
    private function logWarning(string $message, array $context = []): void
    {
        Log::channel('bgjob')->warning($message, array_merge($context, [
            'service' => 'BatchWeightCalculationService'
        ]));
    }

    /**
     * Log error message with bgjob channel
     */
    private function logError(string $message, array $context = []): void
    {
        Log::channel('bgjob')->error($message, array_merge($context, [
            'service' => 'BatchWeightCalculationService'
        ]));
    }
}

<?php

namespace App\Services\Livestock;

use App\Models\Livestock;
use App\Models\LivestockBatch;
use App\Models\LivestockDepletion;
use App\Models\CurrentLivestock;
use App\Models\Recording;
use App\Config\CompanyConfig;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use Carbon\Carbon;

/**
 * FIFO Livestock Depletion Service
 * 
 * Comprehensive service for handling livestock batch depletion using FIFO method
 * based on CompanyConfig configuration system.
 * 
 * Features:
 * - FIFO batch selection (oldest batch first)
 * - Automatic batch quantity calculation
 * - Configuration-driven behavior
 * - Real-time batch tracking
 * - Audit trail and logging
 * - Transaction safety
 * - Future-proof extensible design
 * 
 * @author System
 * @version 2.0
 */
class FIFODepletionService
{
    /**
     * Supported depletion types
     */
    const DEPLETION_TYPES = [
        'mortality' => 'Kematian',
        'culling' => 'Afkir',
        'sales' => 'Penjualan',
        'mutation' => 'Mutasi',
        'other' => 'Lainnya'
    ];

    /**
     * Process livestock depletion using FIFO method
     *
     * @param array $depletionData
     * @return array
     * @throws Exception
     */
    public function processDepletion(array $depletionData): array
    {
        Log::info('🔄 FIFO Depletion Service: Starting depletion process', [
            'livestock_id' => $depletionData['livestock_id'] ?? null,
            'depletion_type' => $depletionData['depletion_type'] ?? null,
            'total_quantity' => $depletionData['total_quantity'] ?? null
        ]);

        try {
            // Validate input data
            $this->validateDepletionData($depletionData);

            // Get livestock and configuration
            $livestock = Livestock::findOrFail($depletionData['livestock_id']);
            $config = $this->getDepletionConfig($livestock);

            // Check if FIFO is enabled
            if (!$this->isFifoEnabled($config)) {
                throw new Exception('FIFO depletion method is not enabled for this livestock');
            }

            // Process depletion in transaction
            return DB::transaction(function () use ($depletionData, $livestock, $config) {
                return $this->processFifoDepletion($depletionData, $livestock, $config);
            });
        } catch (Exception $e) {
            Log::error('❌ FIFO Depletion Service: Depletion failed', [
                'livestock_id' => $depletionData['livestock_id'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Get available batches for FIFO depletion
     *
     * @param Livestock $livestock
     * @param array $config
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAvailableBatchesForFifo(Livestock $livestock, array $config = null): \Illuminate\Database\Eloquent\Collection
    {
        if (!$config) {
            $config = $this->getDepletionConfig($livestock);
        }

        $fifoConfig = $config['depletion_methods']['fifo'] ?? [];
        $minAge = $fifoConfig['min_age_days'] ?? 0;
        $maxAge = $fifoConfig['max_age_days'] ?? null;
        $batchLimit = $fifoConfig['performance_optimization']['batch_query_limit'] ?? 100;
        $useIndexedQueries = $fifoConfig['performance_optimization']['use_indexed_queries'] ?? true;
        $requireActiveOnly = $fifoConfig['validation_rules']['require_active_batches_only'] ?? true;

        // Build optimized query
        $query = $livestock->batches();

        // Apply status filter
        if ($requireActiveOnly) {
            $query->where('status', 'active');
        }

        // Apply quantity filter (only batches with available quantity)
        $query->whereRaw('(initial_quantity - COALESCE(quantity_depletion, 0) - COALESCE(quantity_sales, 0) - COALESCE(quantity_mutated, 0)) > 0');

        // Apply age restrictions if configured
        if ($minAge > 0) {
            $minDate = Carbon::now()->subDays($minAge);
            $query->where('start_date', '<=', $minDate);
        }

        if ($maxAge) {
            $maxDate = Carbon::now()->subDays($maxAge);
            $query->where('start_date', '>=', $maxDate);
        }

        // Apply batch selection criteria
        $criteria = $fifoConfig['batch_selection_criteria'] ?? [];
        $primaryCriteria = $criteria['primary'] ?? 'age';
        $secondaryCriteria = $criteria['secondary'] ?? 'quantity';

        // Order by criteria
        switch ($primaryCriteria) {
            case 'age':
                $query->orderBy('start_date', 'asc'); // Oldest first for FIFO
                break;
            case 'quantity':
                $query->orderByRaw('(initial_quantity - COALESCE(quantity_depletion, 0) - COALESCE(quantity_sales, 0) - COALESCE(quantity_mutated, 0)) desc');
                break;
            case 'health_status':
                $query->orderBy('health_status', 'desc');
                break;
        }

        // Apply secondary sorting
        if ($secondaryCriteria !== $primaryCriteria) {
            switch ($secondaryCriteria) {
                case 'age':
                    $query->orderBy('start_date', 'asc');
                    break;
                case 'quantity':
                    $query->orderByRaw('(initial_quantity - COALESCE(quantity_depletion, 0) - COALESCE(quantity_sales, 0) - COALESCE(quantity_mutated, 0)) desc');
                    break;
                case 'health_status':
                    $query->orderBy('health_status', 'desc');
                    break;
            }
        }

        // Apply limit for performance
        $query->limit($batchLimit);

        // Use indexed queries if enabled
        if ($useIndexedQueries) {
            $query->with(['livestock:id,name']); // Eager load only needed fields
        }

        $batches = $query->get();

        Log::info('📊 FIFO Depletion Service: Available batches loaded with advanced criteria', [
            'livestock_id' => $livestock->id,
            'batch_count' => $batches->count(),
            'min_age_days' => $minAge,
            'max_age_days' => $maxAge,
            'primary_criteria' => $primaryCriteria,
            'secondary_criteria' => $secondaryCriteria,
            'query_limit' => $batchLimit,
            'config_used' => $fifoConfig
        ]);

        return $batches;
    }

    /**
     * Calculate FIFO depletion distribution
     *
     * @param Livestock $livestock
     * @param int $totalQuantity
     * @param array $config
     * @return array
     */
    public function calculateFifoDistribution(Livestock $livestock, int $totalQuantity, array $config = null): array
    {
        if (!$config) {
            $config = $this->getDepletionConfig($livestock);
        }

        $availableBatches = $this->getAvailableBatchesForFifo($livestock, $config);

        if ($availableBatches->isEmpty()) {
            throw new Exception('No available batches for FIFO depletion');
        }

        // Get distribution configuration
        $fifoConfig = $config['depletion_methods']['fifo'] ?? [];
        $distributionConfig = $fifoConfig['quantity_distribution'] ?? [];
        $distributionMethod = $distributionConfig['method'] ?? 'sequential';
        $allowPartialDepletion = $distributionConfig['allow_partial_batch_depletion'] ?? true;
        $minBatchRemaining = $distributionConfig['min_batch_remaining'] ?? 0;
        $preserveBatchIntegrity = $distributionConfig['preserve_batch_integrity'] ?? false;

        $distribution = [];
        $remainingQuantity = $totalQuantity;

        // Calculate distribution based on method
        switch ($distributionMethod) {
            case 'sequential':
                $distribution = $this->calculateSequentialDistribution(
                    $availableBatches,
                    $remainingQuantity,
                    $allowPartialDepletion,
                    $minBatchRemaining
                );
                break;

            case 'proportional':
                $distribution = $this->calculateProportionalDistributionWithRemainder(
                    $availableBatches,
                    $totalQuantity,
                    $preserveBatchIntegrity
                );
                break;

            case 'balanced':
                $distribution = $this->calculateBalancedDistribution(
                    $availableBatches,
                    $totalQuantity,
                    $minBatchRemaining
                );
                break;

            default:
                $distribution = $this->calculateSequentialDistribution(
                    $availableBatches,
                    $remainingQuantity,
                    $allowPartialDepletion,
                    $minBatchRemaining
                );
        }

        // Validate distribution
        $totalDistributed = array_sum(array_column($distribution, 'depletion_quantity'));
        $remainingAfterDistribution = $totalQuantity - $totalDistributed;

        if ($remainingAfterDistribution > 0 && !$allowPartialDepletion) {
            throw new Exception("Insufficient livestock quantity. Missing {$remainingAfterDistribution} units for depletion");
        }

        Log::info('📈 FIFO Depletion Service: Advanced distribution calculated', [
            'livestock_id' => $livestock->id,
            'total_quantity' => $totalQuantity,
            'distribution_method' => $distributionMethod,
            'batches_affected' => count($distribution),
            'total_distributed' => $totalDistributed,
            'remaining' => $remainingAfterDistribution,
            'config_used' => $distributionConfig
        ]);

        return [
            'total_quantity' => $totalQuantity,
            'distribution_method' => $distributionMethod,
            'batches_affected' => count($distribution),
            'distribution' => $distribution,
            'validation' => [
                'total_distributed' => $totalDistributed,
                'remaining' => $remainingAfterDistribution,
                'is_complete' => $remainingAfterDistribution === 0,
                'is_partial_allowed' => $allowPartialDepletion
            ],
            'config_snapshot' => $distributionConfig
        ];
    }

    /**
     * Calculate sequential distribution (original FIFO method)
     *
     * @param \Illuminate\Database\Eloquent\Collection $batches
     * @param int $remainingQuantity
     * @param bool $allowPartialDepletion
     * @param int $minBatchRemaining
     * @return array
     */
    private function calculateSequentialDistribution($batches, int $remainingQuantity, bool $allowPartialDepletion, int $minBatchRemaining): array
    {
        $distribution = [];
        $originalQuantity = $remainingQuantity;

        // Get configuration to check if single batch mode is enabled
        $config = CompanyConfig::getDefaultLivestockConfig()['recording_method']['batch_settings']['depletion_methods']['fifo'] ?? [];
        $forceSingleBatch = $config['quantity_distribution']['force_single_batch'] ?? true;
        $maxBatchesPerOperation = $config['quantity_distribution']['max_batches_per_operation'] ?? 1;

        $batchesProcessed = 0;

        foreach ($batches as $batch) {
            if ($remainingQuantity <= 0) {
                break;
            }

            // Enforce single batch limit
            if ($forceSingleBatch && $batchesProcessed >= 1) {
                break;
            }

            if ($batchesProcessed >= $maxBatchesPerOperation) {
                break;
            }

            $currentQuantity = $batch->initial_quantity - ($batch->quantity_depletion ?? 0) - ($batch->quantity_sales ?? 0) - ($batch->quantity_mutated ?? 0);
            $maxDepletable = $currentQuantity - $minBatchRemaining;

            if ($maxDepletable <= 0) {
                continue;
            }

            $depletionQuantity = min($maxDepletable, $remainingQuantity);

            if ($depletionQuantity > 0) {
                $distribution[] = [
                    'batch_id' => $batch->id,
                    'batch_name' => $batch->name ?? 'Unknown',
                    'start_date' => $batch->start_date ?? now(),
                    'age_days' => $batch->start_date ? $batch->start_date->diffInDays(now()) : 0,
                    'current_quantity' => $currentQuantity,
                    'depletion_quantity' => $depletionQuantity,
                    'remaining_after_depletion' => $currentQuantity - $depletionQuantity,
                    'percentage' => 100, // Always 100% since single batch
                    'distribution_method' => 'sequential_single_batch'
                ];

                $remainingQuantity -= $depletionQuantity;
                $batchesProcessed++;

                // For single batch mode, break after first successful allocation
                if ($forceSingleBatch) {
                    break;
                }
            }
        }

        return $distribution;
    }

    /**
     * Calculate proportional distribution across all batches
     *
     * @param \Illuminate\Database\Eloquent\Collection $batches
     * @param int $totalQuantity
     * @param bool $preserveBatchIntegrity
     * @return array
     */
    private function calculateProportionalDistribution($batches, int $totalQuantity, bool $preserveBatchIntegrity): array
    {
        $distribution = [];
        $totalAvailable = 0;

        // Calculate total available quantity and sort by age (oldest first for FIFO)
        $batchData = [];
        foreach ($batches as $batch) {
            $currentQuantity = $batch->initial_quantity - ($batch->quantity_depletion ?? 0) - ($batch->quantity_sales ?? 0) - ($batch->quantity_mutated ?? 0);
            if ($currentQuantity > 0) {
                $batchData[] = [
                    'batch' => $batch,
                    'available_quantity' => $currentQuantity,
                    'age_days' => $batch->start_date->diffInDays(now())
                ];
                $totalAvailable += $currentQuantity;
            }
        }

        if ($totalAvailable === 0) {
            return [];
        }

        // Sort by age (oldest first) to prioritize older batches for remainder
        usort($batchData, function ($a, $b) {
            return $b['age_days'] <=> $a['age_days'];
        });

        $remainingToDistribute = $totalQuantity;
        $tempDistribution = [];

        // First pass: Calculate base proportional distribution using floor
        foreach ($batchData as $data) {
            $batch = $data['batch'];
            $availableQuantity = $data['available_quantity'];

            $proportion = $availableQuantity / $totalAvailable;
            $baseDepletionQuantity = min(
                floor($totalQuantity * $proportion),
                $availableQuantity,
                $remainingToDistribute
            );

            $tempDistribution[] = [
                'batch' => $batch,
                'available_quantity' => $availableQuantity,
                'base_depletion' => $baseDepletionQuantity,
                'proportion' => $proportion,
                'age_days' => $data['age_days'],
                'exact_proportion' => $totalQuantity * $proportion, // Exact calculation without floor
                'can_take_more' => $availableQuantity > $baseDepletionQuantity
            ];

            $remainingToDistribute -= $baseDepletionQuantity;
        }

        // Second pass: Distribute remainder to oldest batches first (FIFO principle)
        while ($remainingToDistribute > 0) {
            $distributed = false;

            foreach ($tempDistribution as &$item) {
                if ($remainingToDistribute <= 0) break;

                if ($item['can_take_more'] && $item['base_depletion'] < $item['available_quantity']) {
                    $item['base_depletion']++;
                    $remainingToDistribute--;
                    $distributed = true;

                    // Update can_take_more flag
                    $item['can_take_more'] = $item['base_depletion'] < $item['available_quantity'];
                }
            }

            // If no batch can take more, break to avoid infinite loop
            if (!$distributed) {
                break;
            }
        }

        // Build final distribution
        foreach ($tempDistribution as $item) {
            if ($item['base_depletion'] > 0) {
                $batch = $item['batch'];
                $distribution[] = [
                    'batch_id' => $batch->id,
                    'batch_name' => $batch->name ?? 'Unknown',
                    'start_date' => $batch->start_date ?? now(),
                    'age_days' => $item['age_days'],
                    'current_quantity' => $item['available_quantity'],
                    'depletion_quantity' => $item['base_depletion'],
                    'remaining_after_depletion' => $item['available_quantity'] - $item['base_depletion'],
                    'percentage' => round(($item['base_depletion'] / $totalQuantity) * 100, 2),
                    'proportion_used' => round($item['proportion'] * 100, 2),
                    'distribution_method' => 'proportional_fifo'
                ];
            }
        }

        return $distribution;
    }

    /**
     * Calculate proportional distribution with remainder handling for FIFO
     *
     * @param \Illuminate\Database\Eloquent\Collection $batches
     * @param int $totalQuantity
     * @param bool $preserveBatchIntegrity
     * @return array
     */
    private function calculateProportionalDistributionWithRemainder($batches, int $totalQuantity, bool $preserveBatchIntegrity): array
    {
        $distribution = [];
        $totalAvailable = 0;

        // Calculate total available quantity and prepare batch data
        $batchData = [];
        foreach ($batches as $batch) {
            $currentQuantity = $batch->initial_quantity - ($batch->quantity_depletion ?? 0) - ($batch->quantity_sales ?? 0) - ($batch->quantity_mutated ?? 0);
            if ($currentQuantity > 0) {
                $batchData[] = [
                    'batch' => $batch,
                    'available_quantity' => $currentQuantity,
                    'age_days' => $batch->start_date ? $batch->start_date->diffInDays(now()) : 0
                ];
                $totalAvailable += $currentQuantity;
            }
        }

        if ($totalAvailable === 0 || empty($batchData)) {
            return [];
        }

        // Sort by age (oldest first) for FIFO principle
        usort($batchData, function ($a, $b) {
            return $b['age_days'] <=> $a['age_days'];
        });

        $remainingToDistribute = $totalQuantity;

        // Calculate proportional distribution
        foreach ($batchData as $index => $data) {
            if ($remainingToDistribute <= 0) break;

            $batch = $data['batch'];
            $availableQuantity = $data['available_quantity'];

            $proportion = $availableQuantity / $totalAvailable;
            $baseAllocation = floor($totalQuantity * $proportion);

            // For the oldest batch, add any remainder
            if ($index === 0) {
                $totalAllocated = 0;
                foreach ($batchData as $tempData) {
                    $tempProportion = $tempData['available_quantity'] / $totalAvailable;
                    $totalAllocated += floor($totalQuantity * $tempProportion);
                }
                $remainder = $totalQuantity - $totalAllocated;
                $baseAllocation += $remainder;
            }

            $depletionQuantity = min($baseAllocation, $availableQuantity, $remainingToDistribute);

            if ($depletionQuantity > 0) {
                $distribution[] = [
                    'batch_id' => $batch->id,
                    'batch_name' => $batch->name ?? 'Unknown',
                    'start_date' => $batch->start_date ?? now(),
                    'age_days' => $data['age_days'],
                    'current_quantity' => $availableQuantity,
                    'depletion_quantity' => $depletionQuantity,
                    'remaining_after_depletion' => $availableQuantity - $depletionQuantity,
                    'percentage' => round(($depletionQuantity / $totalQuantity) * 100, 2),
                    'proportion_used' => round($proportion * 100, 2),
                    'distribution_method' => 'proportional_fifo'
                ];

                $remainingToDistribute -= $depletionQuantity;
            }
        }

        return $distribution;
    }

    /**
     * Calculate balanced distribution to maintain batch equilibrium
     *
     * @param \Illuminate\Database\Eloquent\Collection $batches
     * @param int $totalQuantity
     * @param int $minBatchRemaining
     * @return array
     */
    private function calculateBalancedDistribution($batches, int $totalQuantity, int $minBatchRemaining): array
    {
        $distribution = [];
        $activeBatches = [];

        // Prepare active batches
        foreach ($batches as $batch) {
            $currentQuantity = $batch->initial_quantity - ($batch->quantity_depletion ?? 0) - ($batch->quantity_sales ?? 0) - ($batch->quantity_mutated ?? 0);
            $maxDepletable = $currentQuantity - $minBatchRemaining;

            if ($maxDepletable > 0) {
                $activeBatches[] = [
                    'batch' => $batch,
                    'current_quantity' => $currentQuantity,
                    'max_depletable' => $maxDepletable,
                    'depletion_so_far' => 0
                ];
            }
        }

        if (empty($activeBatches)) {
            return [];
        }

        $remainingQuantity = $totalQuantity;
        $batchCount = count($activeBatches);

        // Distribute evenly across batches in rounds
        while ($remainingQuantity > 0 && $batchCount > 0) {
            $quantityPerBatch = max(1, floor($remainingQuantity / $batchCount));
            $batchesProcessed = 0;

            foreach ($activeBatches as $key => &$batchData) {
                if ($remainingQuantity <= 0) {
                    break;
                }

                $canTake = min(
                    $quantityPerBatch,
                    $batchData['max_depletable'] - $batchData['depletion_so_far'],
                    $remainingQuantity
                );

                if ($canTake > 0) {
                    $batchData['depletion_so_far'] += $canTake;
                    $remainingQuantity -= $canTake;
                    $batchesProcessed++;
                }

                // Remove batch if it's fully depleted
                if ($batchData['depletion_so_far'] >= $batchData['max_depletable']) {
                    unset($activeBatches[$key]);
                    $batchCount--;
                }
            }

            // Break if no progress made
            if ($batchesProcessed === 0) {
                break;
            }

            // Re-index array
            $activeBatches = array_values($activeBatches);
        }

        // Build distribution result
        foreach ($activeBatches as $batchData) {
            if ($batchData['depletion_so_far'] > 0) {
                $batch = $batchData['batch'];
                $distribution[] = [
                    'batch_id' => $batch->id,
                    'batch_name' => $batch->name,
                    'start_date' => $batch->start_date,
                    'age_days' => $batch->start_date->diffInDays(now()),
                    'current_quantity' => $batchData['current_quantity'],
                    'depletion_quantity' => $batchData['depletion_so_far'],
                    'remaining_after_depletion' => $batchData['current_quantity'] - $batchData['depletion_so_far'],
                    'percentage' => round(($batchData['depletion_so_far'] / $totalQuantity) * 100, 2),
                    'distribution_method' => 'balanced'
                ];
            }
        }

        return $distribution;
    }

    /**
     * Preview FIFO depletion without executing
     *
     * @param array $depletionData
     * @return array
     */
    public function previewFifoDepletion(array $depletionData): array
    {
        $this->validateDepletionData($depletionData);

        $livestock = Livestock::findOrFail($depletionData['livestock_id']);
        $config = $this->getDepletionConfig($livestock);

        $distribution = $this->calculateFifoDistribution(
            $livestock,
            $depletionData['total_quantity'],
            $config
        );

        return [
            'livestock_id' => $livestock->id,
            'livestock_name' => $livestock->name,
            'depletion_type' => $depletionData['depletion_type'],
            'depletion_date' => $depletionData['depletion_date'] ?? now()->format('Y-m-d'),
            'method' => 'fifo',
            'preview' => true,
            'distribution' => $distribution,
            'summary' => [
                'total_quantity' => $depletionData['total_quantity'],
                'batches_affected' => $distribution['batches_affected'],
                'oldest_batch_age' => $distribution['distribution'][0]['age_days'] ?? 0,
                'newest_batch_age' => end($distribution['distribution'])['age_days'] ?? 0,
                'estimated_cost_impact' => $this->estimateCostImpact($distribution['distribution'])
            ]
        ];
    }

    /**
     * Process FIFO depletion (internal method)
     *
     * @param array $depletionData
     * @param Livestock $livestock
     * @param array $config
     * @return array
     */
    private function processFifoDepletion(array $depletionData, Livestock $livestock, array $config): array
    {
        // Calculate distribution
        $distribution = $this->calculateFifoDistribution(
            $livestock,
            $depletionData['total_quantity'],
            $config
        );

        $depletionRecords = [];
        $updatedBatches = [];

        // Process each batch in the distribution
        foreach ($distribution['distribution'] as $batchDistribution) {
            $batch = LivestockBatch::findOrFail($batchDistribution['batch_id']);

            // Create depletion record with correct field mapping
            $depletionRecord = LivestockDepletion::create([
                'livestock_id' => $livestock->id,
                'jenis' => $depletionData['depletion_type'],
                'tanggal' => $depletionData['depletion_date'] ?? now()->format('Y-m-d'),
                'jumlah' => $batchDistribution['depletion_quantity'], // Field 'jumlah' instead of 'quantity'
                'recording_id' => $depletionData['recording_id'] ?? null,
                'created_by' => auth()->id(),
                'data' => [
                    'method' => 'fifo',
                    'reason' => $depletionData['reason'] ?? 'FIFO automatic depletion',
                    'notes' => $depletionData['notes'] ?? null,
                    'batch_id' => $batch->id,
                    'batch_name' => $batch->name
                ],
                'metadata' => [
                    'batch_age_days' => $batchDistribution['age_days'],
                    'batch_percentage' => $batchDistribution['percentage'],
                    'fifo_order' => array_search($batchDistribution, $distribution['distribution']) + 1,
                    'config_used' => $config
                ]
            ]);

            // Update batch quantities
            switch ($depletionData['depletion_type']) {
                case 'mortality':
                    $batch->quantity_depletion += $batchDistribution['depletion_quantity'];
                    break;
                case 'culling':
                    $batch->quantity_depletion += $batchDistribution['depletion_quantity'];
                    break;
                case 'sales':
                    $batch->quantity_sales += $batchDistribution['depletion_quantity'];
                    break;
                case 'mutation':
                    $batch->quantity_mutated += $batchDistribution['depletion_quantity'];
                    break;
                default:
                    $batch->quantity_depletion += $batchDistribution['depletion_quantity'];
            }

            $batch->save();

            $depletionRecords[] = $depletionRecord;
            $updatedBatches[] = $batch;

            Log::info('✅ FIFO Depletion Service: Batch processed', [
                'batch_id' => $batch->id,
                'batch_name' => $batch->name,
                'depletion_quantity' => $batchDistribution['depletion_quantity'],
                'remaining_quantity' => $batchDistribution['remaining_after_depletion']
            ]);
        }

        // Update current livestock totals
        $this->updateCurrentLivestockTotals($livestock, $depletionData);

        $result = [
            'success' => true,
            'livestock_id' => $livestock->id,
            'depletion_type' => $depletionData['depletion_type'],
            'total_quantity' => $depletionData['total_quantity'],
            'method' => 'fifo',
            'batches_affected' => count($updatedBatches),
            'depletion_records' => collect($depletionRecords)->map(fn($record) => $record->id)->toArray(),
            'updated_batches' => collect($updatedBatches)->map(fn($batch) => [
                'id' => $batch->id,
                'name' => $batch->name,
                'remaining_quantity' => $batch->initial_quantity - $batch->quantity_depletion - $batch->quantity_sales - $batch->quantity_mutated
            ])->toArray(),
            'distribution_summary' => $distribution,
            'processed_at' => now()->toDateTimeString()
        ];

        Log::info('🎉 FIFO Depletion Service: Depletion completed successfully', [
            'livestock_id' => $livestock->id,
            'total_quantity' => $depletionData['total_quantity'],
            'batches_affected' => count($updatedBatches),
            'depletion_records_created' => count($depletionRecords)
        ]);

        return $result;
    }

    /**
     * Update livestock depletion totals
     *
     * @param Livestock $livestock
     * @param array $depletionData
     * @return void
     */
    private function updateCurrentLivestockTotals(Livestock $livestock, array $depletionData): void
    {
        // Update main Livestock table quantity_depletion for all depletion types
        $livestock->quantity_depletion = ($livestock->quantity_depletion ?? 0) + $depletionData['total_quantity'];
        $livestock->save();

        // Update CurrentLivestock current_quantity (recalculate from all batches)
        $currentLivestock = CurrentLivestock::where('livestock_id', $livestock->id)->first();

        if ($currentLivestock) {
            // Calculate current quantity from all batches
            $totalCurrentQuantity = $livestock->batches()
                ->where('status', 'active')
                ->get()
                ->sum(function ($batch) {
                    return $batch->initial_quantity
                        - ($batch->quantity_depletion ?? 0)
                        - ($batch->quantity_sales ?? 0)
                        - ($batch->quantity_mutated ?? 0);
                });

            $currentLivestock->quantity = $totalCurrentQuantity;
            $currentLivestock->save();

            Log::info('📊 FIFO Depletion Service: Livestock totals updated', [
                'livestock_id' => $livestock->id,
                'livestock_quantity_depletion' => $livestock->quantity_depletion,
                'current_quantity' => $currentLivestock->current_quantity,
                'depletion_type' => $depletionData['depletion_type'],
                'depletion_quantity' => $depletionData['total_quantity']
            ]);
        }
    }

    /**
     * Validate depletion data
     *
     * @param array $depletionData
     * @return void
     * @throws Exception
     */
    private function validateDepletionData(array $depletionData): void
    {
        $required = ['livestock_id', 'depletion_type', 'total_quantity'];

        foreach ($required as $field) {
            if (!isset($depletionData[$field]) || empty($depletionData[$field])) {
                throw new Exception("Field '{$field}' is required for FIFO depletion");
            }
        }

        if (!in_array($depletionData['depletion_type'], array_keys(self::DEPLETION_TYPES))) {
            throw new Exception("Invalid depletion type: {$depletionData['depletion_type']}");
        }

        if ($depletionData['total_quantity'] <= 0) {
            throw new Exception("Total quantity must be greater than 0");
        }

        if (!is_int($depletionData['total_quantity'])) {
            throw new Exception("Total quantity must be an integer");
        }
    }

    /**
     * Get depletion configuration from livestock
     *
     * @param Livestock $livestock
     * @return array
     */
    private function getDepletionConfig(Livestock $livestock): array
    {
        // Always use CompanyConfig for consistent single batch behavior
        return CompanyConfig::getDefaultLivestockConfig()['recording_method']['batch_settings'];
    }

    /**
     * Check if FIFO is enabled
     *
     * @param array $config
     * @return bool
     */
    private function isFifoEnabled(array $config): bool
    {
        return ($config['depletion_methods']['fifo']['enabled'] ?? false) === true;
    }

    /**
     * Estimate cost impact of depletion
     *
     * @param array $distribution
     * @return array
     */
    private function estimateCostImpact(array $distribution): array
    {
        // This is a placeholder for cost calculation
        // In a real implementation, you would calculate based on batch costs
        return [
            'estimated_total_cost' => 0,
            'average_cost_per_unit' => 0,
            'cost_by_batch' => []
        ];
    }

    /**
     * Get FIFO depletion statistics
     *
     * @param Livestock $livestock
     * @param string $period
     * @return array
     */
    public function getFifoDepletionStats(Livestock $livestock, string $period = '30_days'): array
    {
        $startDate = match ($period) {
            '7_days' => Carbon::now()->subDays(7),
            '30_days' => Carbon::now()->subDays(30),
            '90_days' => Carbon::now()->subDays(90),
            default => Carbon::now()->subDays(30)
        };

        $depletions = LivestockDepletion::where('livestock_id', $livestock->id)
            ->where('method', 'fifo')
            ->where('depletion_date', '>=', $startDate)
            ->with(['livestockBatch'])
            ->get();

        return [
            'period' => $period,
            'total_depletions' => $depletions->count(),
            'total_quantity' => $depletions->sum('quantity'),
            'by_type' => $depletions->groupBy('depletion_type')->map(fn($group) => [
                'count' => $group->count(),
                'quantity' => $group->sum('quantity')
            ]),
            'batches_affected' => $depletions->pluck('livestock_batch_id')->unique()->count(),
            'average_batch_age' => $depletions->avg(function ($depletion) {
                $metadata = json_decode($depletion->metadata, true);
                return $metadata['batch_age_days'] ?? 0;
            })
        ];
    }

    /**
     * Get cached batch data for performance optimization
     *
     * @param Livestock $livestock
     * @param array $config
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getCachedBatchData(Livestock $livestock, array $config): \Illuminate\Database\Eloquent\Collection
    {
        $cacheConfig = $config['depletion_methods']['fifo']['performance_optimization'] ?? [];
        $useCaching = $cacheConfig['cache_batch_queries'] ?? false;

        if (!$useCaching) {
            return $this->getAvailableBatchesForFifo($livestock, $config);
        }

        $cacheKey = "fifo_batches_{$livestock->id}_" . md5(json_encode($config));
        $cacheTtl = $cacheConfig['cache_ttl_minutes'] ?? 5;

        return cache()->remember($cacheKey, $cacheTtl * 60, function () use ($livestock, $config) {
            return $this->getAvailableBatchesForFifo($livestock, $config);
        });
    }

    /**
     * Clear batch cache for livestock
     *
     * @param Livestock $livestock
     * @return bool
     */
    public function clearBatchCache(Livestock $livestock): bool
    {
        try {
            $pattern = "fifo_batches_{$livestock->id}_*";

            // Clear all cache entries matching the pattern
            $keys = cache()->getRedis()->keys($pattern);
            if (!empty($keys)) {
                cache()->getRedis()->del($keys);
            }

            Log::info('🧹 FIFO Depletion Service: Cache cleared', [
                'livestock_id' => $livestock->id,
                'keys_cleared' => count($keys)
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('❌ FIFO Depletion Service: Failed to clear cache', [
                'livestock_id' => $livestock->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get performance metrics for FIFO operations
     *
     * @param Livestock $livestock
     * @param string $period
     * @return array
     */
    public function getPerformanceMetrics(Livestock $livestock, string $period = '30_days'): array
    {
        $startTime = microtime(true);

        try {
            $stats = $this->getFifoDepletionStats($livestock, $period);
            $config = $this->getDepletionConfig($livestock);
            $batches = $this->getAvailableBatchesForFifo($livestock, $config);

            $endTime = microtime(true);
            $executionTime = round(($endTime - $startTime) * 1000, 2); // milliseconds

            return [
                'execution_time_ms' => $executionTime,
                'available_batches' => $batches->count(),
                'total_operations' => $stats['total_depletions'],
                'average_operations_per_day' => round($stats['total_depletions'] / (Carbon::parse($period)->diffInDays(Carbon::now()) ?: 1), 2),
                'efficiency_score' => $this->calculateEfficiencyScore($stats, $batches->count()),
                'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                'cache_hit_ratio' => $this->getCacheHitRatio($livestock),
                'performance_grade' => $this->getPerformanceGrade($executionTime, $batches->count())
            ];
        } catch (Exception $e) {
            $endTime = microtime(true);
            $executionTime = round(($endTime - $startTime) * 1000, 2);

            return [
                'execution_time_ms' => $executionTime,
                'error' => $e->getMessage(),
                'performance_grade' => 'F'
            ];
        }
    }

    /**
     * Calculate efficiency score based on operations and batch utilization
     *
     * @param array $stats
     * @param int $availableBatches
     * @return float
     */
    private function calculateEfficiencyScore(array $stats, int $availableBatches): float
    {
        if ($availableBatches === 0 || $stats['total_depletions'] === 0) {
            return 0.0;
        }

        $batchUtilization = $stats['batches_affected'] / $availableBatches;
        $operationFrequency = min($stats['total_depletions'] / 30, 1.0); // Normalize to 30 days
        $typeDistribution = count($stats['by_type']) / 4; // Assuming 4 types max

        $score = ($batchUtilization * 0.4) + ($operationFrequency * 0.4) + ($typeDistribution * 0.2);

        return round($score * 100, 2);
    }

    /**
     * Get cache hit ratio for performance monitoring
     *
     * @param Livestock $livestock
     * @return float
     */
    private function getCacheHitRatio(Livestock $livestock): float
    {
        // This would typically integrate with your caching system's metrics
        // For now, return a placeholder
        return 0.0;
    }

    /**
     * Get performance grade based on execution time and complexity
     *
     * @param float $executionTime
     * @param int $batchCount
     * @return string
     */
    private function getPerformanceGrade(float $executionTime, int $batchCount): string
    {
        $complexity = $batchCount * 0.1; // Simple complexity factor
        $adjustedTime = $executionTime - $complexity;

        return match (true) {
            $adjustedTime < 10 => 'A+',
            $adjustedTime < 25 => 'A',
            $adjustedTime < 50 => 'B+',
            $adjustedTime < 100 => 'B',
            $adjustedTime < 200 => 'C',
            $adjustedTime < 500 => 'D',
            default => 'F'
        };
    }

    /**
     * Optimize FIFO configuration based on usage patterns
     *
     * @param Livestock $livestock
     * @return array
     */
    public function optimizeConfiguration(Livestock $livestock): array
    {
        $stats = $this->getFifoDepletionStats($livestock, '90_days');
        $currentConfig = $this->getDepletionConfig($livestock);
        $optimizedConfig = $currentConfig;

        // Optimize based on usage patterns
        if ($stats['total_depletions'] > 50) {
            // High usage - enable caching
            $optimizedConfig['depletion_methods']['fifo']['performance_optimization']['cache_batch_queries'] = true;
            $optimizedConfig['depletion_methods']['fifo']['performance_optimization']['batch_query_limit'] = 50;
        }

        if ($stats['batches_affected'] > 10) {
            // Many batches affected - use proportional distribution
            $optimizedConfig['depletion_methods']['fifo']['quantity_distribution']['method'] = 'proportional';
        }

        $recommendations = [
            'current_efficiency' => $this->calculateEfficiencyScore($stats, 10),
            'optimizations_applied' => [],
            'estimated_improvement' => '15-25%',
            'config_changes' => array_diff_assoc($optimizedConfig, $currentConfig)
        ];

        if (!empty($recommendations['config_changes'])) {
            $recommendations['optimizations_applied'][] = 'Performance caching enabled';
            $recommendations['optimizations_applied'][] = 'Distribution method optimized';
        }

        Log::info('🔧 FIFO Depletion Service: Configuration optimized', [
            'livestock_id' => $livestock->id,
            'optimizations' => $recommendations['optimizations_applied'],
            'efficiency_improvement' => $recommendations['estimated_improvement']
        ]);

        return [
            'optimized_config' => $optimizedConfig,
            'recommendations' => $recommendations
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Services\Recording;

use App\Models\{LivestockDepletion, Livestock, CurrentLivestock};
use App\Services\Recording\DTOs\ProcessingResult;
use App\Services\Recording\Exceptions\RecordingException;
use App\Config\LivestockDepletionConfig;
use App\Services\Livestock\FIFODepletionService;
use Illuminate\Support\Facades\{DB, Log, Auth};
use Carbon\Carbon;

/**
 * DepletionProcessingService
 * 
 * Handles all livestock depletion processing operations
 * with FIFO support, standardization, and comprehensive tracking.
 */
class DepletionProcessingService
{
    private FIFODepletionService $fifoDepletionService;

    public function __construct(FIFODepletionService $fifoDepletionService)
    {
        $this->fifoDepletionService = $fifoDepletionService;
    }

    /**
     * Store depletion with detailed tracking and FIFO support
     * 
     * @param string $type Type of depletion (mortality/culling)
     * @param int $quantity Quantity
     * @param int $recordingId Recording ID for relation
     * @param int $livestockId Livestock ID
     * @param string $date Depletion date
     * @return ProcessingResult
     */
    public function storeDepletionWithTracking(
        string $type,
        int $quantity,
        string $recordingId,
        string $livestockId,
        string $date
    ): ProcessingResult {
        try {
            DB::beginTransaction();

            if ($quantity <= 0) {
                DB::rollBack();
                return ProcessingResult::success(null, 'Zero quantity, skipping depletion');
            }

            $livestock = Livestock::find($livestockId);
            if (!$livestock) {
                DB::rollBack();
                return ProcessingResult::failure(['Livestock not found'], 'Livestock not found');
            }

            // Normalize depletion type using config
            $normalizedType = LivestockDepletionConfig::normalize($type);
            $legacyType = LivestockDepletionConfig::toLegacy($normalizedType);

            Log::info('DepletionProcessing: Starting depletion processing', [
                'original_type' => $type,
                'normalized_type' => $normalizedType,
                'legacy_type' => $legacyType,
                'quantity' => $quantity,
                'recording_id' => $recordingId,
                'livestock_id' => $livestockId
            ]);

            // Check if FIFO depletion should be used
            if ($this->shouldUseFifoDepletion($livestock, $normalizedType)) {
                $result = $this->processWithFifo($normalizedType, $quantity, $recordingId, $livestock, $date);
                
                if ($result && ($result['success'] ?? false)) {
                    // Standardize FIFO depletion records
                    $this->standardizeFifoDepletionRecords($result, $livestock, $normalizedType, $recordingId, $date);
                    
                    DB::commit();
                    return ProcessingResult::success($result, 'FIFO depletion processed successfully');
                }

                Log::warning('FIFO depletion failed, falling back to traditional method', [
                    'livestock_id' => $livestock->id,
                    'depletion_type' => $normalizedType,
                    'quantity' => $quantity
                ]);
            }

            // Traditional depletion processing
            $result = $this->processTraditionalDepletion($normalizedType, $quantity, $recordingId, $livestock, $date);

            DB::commit();
            return ProcessingResult::success($result, 'Traditional depletion processed successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error processing depletion', [
                'type' => $type,
                'quantity' => $quantity,
                'livestock_id' => $livestockId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new RecordingException("Failed to process depletion: " . $e->getMessage());
        }
    }

    /**
     * Process depletion with FIFO method
     */
    private function processWithFifo(
        string $normalizedType,
        int $quantity,
        string $recordingId,
        Livestock $livestock,
        string $date
    ): ?array {
        try {
            $options = [
                'date' => $date,
                'reason' => "Depletion via Records component",
                'notes' => "Recorded by " . (Auth::user()->name ?? 'System'),
                'original_type' => $normalizedType,
            ];

            return $this->storeDeplesiWithFifo($normalizedType, $quantity, $recordingId, $livestock, $options);
        } catch (\Exception $e) {
            Log::error('FIFO depletion processing failed', [
                'livestock_id' => $livestock->id,
                'type' => $normalizedType,
                'quantity' => $quantity,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Process traditional depletion
     */
    private function processTraditionalDepletion(
        string $normalizedType,
        int $quantity,
        string $recordingId,
        Livestock $livestock,
        string $date
    ): LivestockDepletion {
        $currentDate = Carbon::parse($date);
        $age = $livestock ? $currentDate->diffInDays(Carbon::parse($livestock->start_date)) : null;

        // Create or update depletion record
        $depletion = LivestockDepletion::updateOrCreate(
            [
                'livestock_id' => $livestock->id,
                'tanggal' => $date,
                'jenis' => $normalizedType,
            ],
            [
                'jumlah' => $quantity,
                'recording_id' => $recordingId,
                'method' => 'traditional',
                'metadata' => $this->buildDepletionMetadata($livestock, $age, $recordingId, 'traditional', $normalizedType),
                'data' => $this->buildDepletionData($quantity, 'traditional'),
                'created_by' => Auth::id(),
                'updated_by' => Auth::id()
            ]
        );

        Log::info("Traditional livestock depletion recorded", [
            'livestock_id' => $livestock->id,
            'date' => $date,
            'type' => $normalizedType,
            'quantity' => $quantity,
            'recording_id' => $recordingId,
            'depletion_id' => $depletion->id
        ]);

        return $depletion;
    }

    /**
     * Check if FIFO depletion should be used
     */
    private function shouldUseFifoDepletion(Livestock $livestock, string $depletionType): bool
    {
        try {
            // Check livestock configuration
            $config = $livestock->getConfiguration();
            $depletionMethod = $config['depletion_method'] ?? 'traditional';

            // Check if FIFO is enabled and service is available
            return $depletionMethod === 'fifo' && 
                   $this->fifoDepletionService && 
                   $livestock->getActiveBatchesCount() > 0;
        } catch (\Exception $e) {
            Log::error('Error checking FIFO depletion availability', [
                'livestock_id' => $livestock->id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Store depletion with FIFO method
     */
    private function storeDeplesiWithFifo(
        string $depletionType,
        int $quantity,
        string $recordingId,
        Livestock $livestock,
        array $options
    ): ?array {
        try {
            return $this->fifoDepletionService->processFifoDepletion(
                $livestock,
                $depletionType,
                $quantity,
                $options
            );
        } catch (\Exception $e) {
            Log::error('FIFO depletion service failed', [
                'livestock_id' => $livestock->id,
                'depletion_type' => $depletionType,
                'quantity' => $quantity,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Standardize FIFO depletion records to match traditional format
     */
    private function standardizeFifoDepletionRecords(
        array $fifoResult,
        Livestock $livestock,
        string $normalizedType,
        string $recordingId,
        string $date
    ): void {
        try {
            $age = $livestock ? Carbon::parse($date)->diffInDays(Carbon::parse($livestock->start_date)) : null;
            $depletionRecords = $fifoResult['depletion_records'] ?? [];

            foreach ($depletionRecords as $recordData) {
                $depletionRecord = null;

                if (isset($recordData['depletion_id'])) {
                    $depletionRecord = LivestockDepletion::find($recordData['depletion_id']);
                } elseif (isset($recordData['livestock_depletion_id'])) {
                    $depletionRecord = LivestockDepletion::find($recordData['livestock_depletion_id']);
                }

                if (!$depletionRecord) {
                    continue;
                }

                // Prepare standardized metadata
                $standardizedMetadata = $this->buildDepletionMetadata(
                    $livestock,
                    $age,
                    $recordingId,
                    'fifo',
                    $normalizedType,
                    $recordData
                );

                // Prepare standardized data
                $standardizedData = $this->buildDepletionData(
                    $recordData['quantity'] ?? 0,
                    'fifo',
                    $recordData,
                    $fifoResult
                );

                // Update the depletion record
                $depletionRecord->update([
                    'jenis' => $normalizedType,
                    'recording_id' => $recordingId,
                    'method' => 'fifo',
                    'metadata' => $standardizedMetadata,
                    'data' => $standardizedData,
                    'updated_by' => Auth::id()
                ]);

                Log::info('FIFO depletion record standardized', [
                    'depletion_id' => $depletionRecord->id,
                    'livestock_id' => $livestock->id,
                    'type' => $normalizedType,
                    'quantity' => $depletionRecord->jumlah,
                    'batch_id' => $recordData['batch_id'] ?? null
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to standardize FIFO depletion records', [
                'livestock_id' => $livestock->id,
                'depletion_type' => $normalizedType,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Build depletion metadata
     */
    private function buildDepletionMetadata(
        Livestock $livestock,
        ?int $age,
        string $recordingId,
        string $method,
        string $normalizedType,
        ?array $fifoData = null
    ): array {
        $metadata = [
            // Basic livestock information
            'livestock_name' => $livestock->name ?? 'Unknown',
            'farm_id' => $livestock->farm_id ?? null,
            'farm_name' => $livestock->farm->name ?? 'Unknown',
            'coop_id' => $livestock->coop_id ?? null,
            'kandang_name' => $livestock->kandang->name ?? 'Unknown',
            'age_days' => $age,

            // Recording information
            'recording_id' => $recordingId,
            'updated_at' => now()->toIso8601String(),
            'updated_by' => Auth::id(),
            'updated_by_name' => Auth::user()->name ?? 'Unknown User',

            // Method information
            'depletion_method' => $method,
            'processing_method' => $method === 'fifo' ? 'fifo_depletion_service' : 'records_component',
            'source_component' => 'Records',

            // Config-related metadata
            'depletion_config' => [
                'original_type' => $normalizedType,
                'normalized_type' => $normalizedType,
                'legacy_type' => LivestockDepletionConfig::toLegacy($normalizedType),
                'config_version' => '1.0',
                'display_name' => LivestockDepletionConfig::getDisplayName($normalizedType),
                'category' => LivestockDepletionConfig::getCategory($normalizedType)
            ]
        ];

        // Add FIFO-specific metadata if available
        if ($method === 'fifo' && $fifoData) {
            $metadata['fifo_metadata'] = [
                'batch_id' => $fifoData['batch_id'] ?? null,
                'batch_name' => $fifoData['batch_name'] ?? null,
                'batch_start_date' => $fifoData['batch_start_date'] ?? null,
                'quantity_depleted' => $fifoData['quantity'] ?? 0,
                'remaining_in_batch' => $fifoData['remaining_quantity'] ?? 0,
                'batch_sequence' => $fifoData['sequence'] ?? 1
            ];
        }

        return $metadata;
    }

    /**
     * Build depletion data
     */
    private function buildDepletionData(
        int $quantity,
        string $method,
        ?array $fifoData = null,
        ?array $fifoResult = null
    ): array {
        $data = [
            'depletion_method' => $method,
            'original_request' => $quantity,
            'processing_source' => 'Records Component',
            'batch_processing' => false,
            'single_record' => true
        ];

        if ($method === 'fifo' && $fifoData) {
            $data = array_merge($data, [
                'batch_id' => $fifoData['batch_id'] ?? null,
                'batch_name' => $fifoData['batch_name'] ?? null,
                'batch_start_date' => $fifoData['batch_start_date'] ?? null,
                'available_in_batch' => $fifoData['remaining_quantity'] ?? 0,
                'fifo_sequence' => $fifoData['sequence'] ?? 1,
                'total_batches_affected' => $fifoResult['batches_affected'] ?? 1,
                'distribution_summary' => $fifoResult['distribution_summary'] ?? []
            ]);
        }

        return $data;
    }

    /**
     * Update current livestock quantity with history tracking
     */
    public function updateCurrentLivestockQuantity(int $livestockId): ProcessingResult
    {
        try {
            DB::beginTransaction();

            $livestock = Livestock::find($livestockId);
            $currentLivestock = CurrentLivestock::where('livestock_id', $livestockId)->first();

            if (!$livestock || !$currentLivestock) {
                DB::rollBack();
                return ProcessingResult::failure(['Livestock or CurrentLivestock not found'], 'Update failed');
            }

            // Calculate total depletion from LivestockDepletion records
            $totalDepletion = LivestockDepletion::where('livestock_id', $livestockId)->sum('jumlah');

            // Get all sales records if exists
            $totalSales = 0;
            if (class_exists('App\Models\LivestockSalesItem')) {
                $totalSales = \App\Models\LivestockSalesItem::where('livestock_id', $livestockId)->sum('quantity');
            }

            // Update quantity_depletion in Livestock table
            $oldLivestockQuantityDepletion = $livestock->quantity_depletion ?? 0;
            $livestock->update([
                'quantity_depletion' => $totalDepletion,
                'quantity_sales' => $totalSales,
                'updated_by' => Auth::id()
            ]);

            // Calculate real-time quantity using consistent formula
            $calculatedQuantity = $livestock->initial_quantity
                - $totalDepletion
                - $totalSales
                - ($livestock->quantity_mutated ?? 0);

            // Ensure quantity doesn't go negative
            $calculatedQuantity = max(0, $calculatedQuantity);

            // Store the old quantity for history
            $oldQuantity = $currentLivestock->quantity;

            // Update CurrentLivestock
            $currentLivestock->update([
                'quantity' => $calculatedQuantity,
                'metadata' => array_merge($currentLivestock->metadata ?? [], [
                    'last_updated' => now()->toIso8601String(),
                    'updated_by' => Auth::id(),
                    'updated_by_name' => Auth::user()->name ?? 'Unknown User',
                    'previous_quantity' => $oldQuantity,
                    'quantity_change' => $calculatedQuantity - $oldQuantity,
                    'calculation_source' => 'depletion_processing_service',
                    'formula_breakdown' => [
                        'initial_quantity' => $livestock->initial_quantity,
                        'quantity_depletion' => $totalDepletion,
                        'quantity_sales' => $totalSales,
                        'quantity_mutated' => $livestock->quantity_mutated ?? 0,
                        'calculated_quantity' => $calculatedQuantity
                    ]
                ]),
                'updated_by' => Auth::id()
            ]);

            DB::commit();

            Log::info("Livestock quantities updated via depletion processing", [
                'livestock_id' => $livestockId,
                'old_depletion_total' => $oldLivestockQuantityDepletion,
                'new_depletion_total' => $totalDepletion,
                'old_current_quantity' => $oldQuantity,
                'new_current_quantity' => $calculatedQuantity,
                'quantity_change' => $calculatedQuantity - $oldQuantity
            ]);

            return ProcessingResult::success([
                'old_quantity' => $oldQuantity,
                'new_quantity' => $calculatedQuantity,
                'change' => $calculatedQuantity - $oldQuantity
            ], 'Livestock quantities updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating livestock quantities', [
                'livestock_id' => $livestockId,
                'error' => $e->getMessage()
            ]);

            return ProcessingResult::failure(['Update failed'], 'Livestock quantity update failed');
        }
    }

    /**
     * Get depletion statistics
     */
    public function getDepletionStatistics(int $livestockId, ?Carbon $startDate = null, ?Carbon $endDate = null): ProcessingResult
    {
        try {
            $query = LivestockDepletion::where('livestock_id', $livestockId);

            if ($startDate) {
                $query->where('tanggal', '>=', $startDate);
            }

            if ($endDate) {
                $query->where('tanggal', '<=', $endDate);
            }

            $depletions = $query->get();

            $statistics = [
                'total_depletions' => $depletions->count(),
                'total_quantity' => $depletions->sum('jumlah'),
                'by_type' => $depletions->groupBy('jenis')->map(function ($group) {
                    return [
                        'count' => $group->count(),
                        'quantity' => $group->sum('jumlah'),
                        'percentage' => 0 // Will be calculated below
                    ];
                }),
                'by_method' => $depletions->groupBy('method')->map(function ($group) {
                    return [
                        'count' => $group->count(),
                        'quantity' => $group->sum('jumlah'),
                    ];
                }),
                'date_range' => [
                    'start' => $depletions->min('tanggal'),
                    'end' => $depletions->max('tanggal')
                ],
                'average_daily' => 0
            ];

            // Calculate percentages
            $totalQuantity = $statistics['total_quantity'];
            if ($totalQuantity > 0) {
                foreach ($statistics['by_type'] as $type => &$data) {
                    $data['percentage'] = round(($data['quantity'] / $totalQuantity) * 100, 2);
                }
            }

            // Calculate average daily depletion
            if ($startDate && $endDate) {
                $days = $startDate->diffInDays($endDate) + 1;
                $statistics['average_daily'] = $days > 0 ? round($totalQuantity / $days, 2) : 0;
            }

            return ProcessingResult::success($statistics, 'Depletion statistics calculated successfully');
        } catch (\Exception $e) {
            Log::error('Error calculating depletion statistics', [
                'livestock_id' => $livestockId,
                'error' => $e->getMessage()
            ]);

            throw new RecordingException("Failed to calculate depletion statistics: " . $e->getMessage());
        }
    }

    /**
     * Process depletion data (wrapper for storeDepletionWithTracking)
     * 
     * @param array $data Depletion data array
     * @return ProcessingResult
     */
    public function processDepletion(array $data): ProcessingResult
    {
        try {
            $type = $data['type'] ?? null;
            $quantity = $data['quantity'] ?? 0;
            $recordingId = $data['recording_id'] ?? null;
            $livestockId = $data['livestock_id'] ?? null;
            $date = $data['date'] ?? null;

            if (!$type || !$recordingId || !$livestockId || !$date) {
                return ProcessingResult::failure(['Missing required parameters'], 'Invalid depletion data');
            }

            return $this->storeDepletionWithTracking(
                $type,
                $quantity,
                $recordingId,
                $livestockId,
                $date
            );
        } catch (\Exception $e) {
            Log::error('Error processing depletion', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);

            return ProcessingResult::failure(
                ['Failed to process depletion: ' . $e->getMessage()],
                'Depletion processing failed'
            );
        }
    }
}

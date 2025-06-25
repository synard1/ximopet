<?php

namespace App\Services\Livestock;

use App\Models\Livestock;
use App\Models\Recording;
use App\Config\LivestockDepletionConfig;
use App\Services\Livestock\FIFODepletionService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;
use Carbon\Carbon;

/**
 * FIFO Depletion Manager Service
 * 
 * Modular, reusable service for handling FIFO depletion operations
 * across controllers, services, and components.
 * 
 * Usage Examples:
 * 
 * 1. Simple usage:
 *    $result = FIFODepletionManagerService::store('mortality', 10, $recordingId, $livestock);
 * 
 * 2. With options:
 *    $result = FIFODepletionManagerService::store('mortality', 10, $recordingId, $livestock, [
 *        'date' => '2025-01-22',
 *        'reason' => 'Disease outbreak',
 *        'notes' => 'Emergency depletion'
 *    ]);
 * 
 * 3. Dependency injection usage:
 *    $fifoManager = app(FIFODepletionManagerService::class);
 *    $result = $fifoManager->storeDeplesiWithFifo('mortality', 10, $recordingId, $livestock);
 * 
 * 4. In controllers/services:
 *    $this->fifoManager = app(FIFODepletionManagerService::class);
 *    $result = $this->fifoManager->quickStore($jenis, $jumlah, $livestock);
 * 
 * @author System
 * @version 1.0
 */
class FIFODepletionManagerService
{
    protected FIFODepletionService $fifoService;

    /**
     * Depletion type mapping
     */
    const TYPE_MAPPING = [
        // Indonesian legacy types
        'Mati' => 'mortality',
        'Afkir' => 'culling',
        'Jual' => 'sales',
        'Mutasi' => 'mutation',

        // English standard types
        'mortality' => 'mortality',
        'culling' => 'culling',
        'sales' => 'sales',
        'mutation' => 'mutation',

        // Alternative names
        'kematian' => 'mortality',
        'death' => 'mortality',
        'cull' => 'culling',
        'sell' => 'sales',
        'transfer' => 'mutation',
        'move' => 'mutation'
    ];

    public function __construct(FIFODepletionService $fifoService)
    {
        $this->fifoService = $fifoService;
    }

    /**
     * Static method for quick FIFO depletion storage
     * 
     * @param string $jenis Depletion type
     * @param int $jumlah Quantity
     * @param int|null $recordingId Recording ID (optional)
     * @param Livestock $livestock Livestock instance
     * @param array $options Additional options
     * @return array Result array
     */
    public static function store(string $jenis, int $jumlah, ?int $recordingId, Livestock $livestock, array $options = []): array
    {
        $service = app(static::class);
        return $service->storeDeplesiWithFifo($jenis, $jumlah, $recordingId, $livestock, $options);
    }

    /**
     * Main method for storing FIFO depletion
     * 
     * @param string $jenis Depletion type ('Mati', 'Afkir', 'mortality', 'culling', etc.)
     * @param int $jumlah Quantity to deplete
     * @param string|null $recordingId Recording ID for relation (optional)
     * @param Livestock $livestock Livestock instance
     * @param array $options Additional options (date, reason, notes, etc.)
     * @return array Result with success status and details
     */
    public function storeDeplesiWithFifo(string $jenis, int $jumlah, ?string $recordingId, Livestock $livestock, array $options = []): array
    {
        try {
            Log::info('🚀 FIFO Manager: Starting modular FIFO depletion', [
                'livestock_id' => $livestock->id,
                'livestock_name' => $livestock->name,
                'depletion_type' => $jenis,
                'quantity' => $jumlah,
                'recording_id' => $recordingId,
                'options' => $options
            ]);

            // Validate inputs
            $this->validateInputs($jenis, $jumlah, $livestock);

            // Check if FIFO is enabled for this livestock
            if (!$this->shouldUseFifoDepletion($livestock, $jenis)) {
                return $this->createErrorResult('FIFO depletion not enabled for this livestock or depletion type', [
                    'reason' => 'fifo_not_enabled',
                    'livestock_id' => $livestock->id,
                    'depletion_type' => $jenis
                ]);
            }

            // Normalize depletion type
            $normalizedType = $this->normalizeDepletionType($jenis);

            // Prepare depletion data
            $depletionData = $this->prepareDepletionData($normalizedType, $jumlah, $recordingId, $livestock, $options);

            // Process FIFO depletion
            $result = $this->fifoService->processDepletion($depletionData);

            if ($result && isset($result['success']) && $result['success']) {
                Log::info('✅ FIFO Manager: Depletion successful', [
                    'livestock_id' => $livestock->id,
                    'total_quantity' => $result['total_quantity'],
                    'batches_affected' => $result['batches_affected']
                ]);

                return $this->createSuccessResult($result, $livestock, $normalizedType, $jumlah);
            } else {
                $errorMsg = $result['error'] ?? 'FIFO depletion process failed';
                Log::error('❌ FIFO Manager: Depletion failed', [
                    'livestock_id' => $livestock->id,
                    'error' => $errorMsg
                ]);

                return $this->createErrorResult($errorMsg, [
                    'reason' => 'processing_failed',
                    'details' => $result
                ]);
            }
        } catch (Exception $e) {
            Log::error('💥 FIFO Manager: Exception occurred', [
                'livestock_id' => $livestock->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->createErrorResult($e->getMessage(), [
                'reason' => 'exception',
                'exception_type' => get_class($e)
            ]);
        }
    }

    /**
     * Quick store method without recording ID (auto-finds or creates recording)
     * 
     * @param string $jenis Depletion type
     * @param int $jumlah Quantity
     * @param Livestock $livestock Livestock instance
     * @param array $options Additional options
     * @return array Result array
     */
    public function quickStore(string $jenis, int $jumlah, Livestock $livestock, array $options = []): array
    {
        $date = $options['date'] ?? now()->format('Y-m-d');
        $recordingId = $this->findOrCreateRecording($livestock, $date);

        return $this->storeDeplesiWithFifo($jenis, $jumlah, $recordingId, $livestock, $options);
    }

    /**
     * Batch store multiple depletions
     * 
     * @param array $depletions Array of depletion data
     * @param Livestock $livestock Livestock instance
     * @param array $globalOptions Global options applied to all depletions
     * @return array Results array
     */
    public function batchStore(array $depletions, Livestock $livestock, array $globalOptions = []): array
    {
        $results = [];
        $successCount = 0;
        $failureCount = 0;

        DB::beginTransaction();

        try {
            foreach ($depletions as $index => $depletion) {
                $jenis = $depletion['jenis'] ?? $depletion['type'];
                $jumlah = $depletion['jumlah'] ?? $depletion['quantity'];
                $recordingId = $depletion['recording_id'] ?? null;
                $options = array_merge($globalOptions, $depletion['options'] ?? []);

                $result = $this->storeDeplesiWithFifo($jenis, $jumlah, $recordingId, $livestock, $options);

                $results[] = [
                    'index' => $index,
                    'depletion' => $depletion,
                    'result' => $result
                ];

                if ($result['success']) {
                    $successCount++;
                } else {
                    $failureCount++;
                }
            }

            if ($failureCount === 0) {
                DB::commit();
                Log::info('✅ FIFO Manager: Batch processing completed successfully', [
                    'livestock_id' => $livestock->id,
                    'total_depletions' => count($depletions),
                    'success_count' => $successCount
                ]);
            } else {
                DB::rollback();
                Log::warning('⚠️ FIFO Manager: Batch processing had failures, rolling back', [
                    'livestock_id' => $livestock->id,
                    'success_count' => $successCount,
                    'failure_count' => $failureCount
                ]);
            }
        } catch (Exception $e) {
            DB::rollback();
            Log::error('💥 FIFO Manager: Batch processing exception', [
                'livestock_id' => $livestock->id,
                'error' => $e->getMessage()
            ]);

            return $this->createErrorResult('Batch processing failed: ' . $e->getMessage(), [
                'reason' => 'batch_exception',
                'results' => $results
            ]);
        }

        return [
            'success' => $failureCount === 0,
            'total_depletions' => count($depletions),
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'results' => $results,
            'message' => $failureCount === 0
                ? "All {$successCount} depletions processed successfully"
                : "{$failureCount} out of " . count($depletions) . " depletions failed"
        ];
    }

    /**
     * Preview FIFO depletion without executing
     * 
     * @param string $jenis Depletion type
     * @param int $jumlah Quantity
     * @param Livestock $livestock Livestock instance
     * @param array $options Additional options
     * @return array Preview result
     */
    public function previewDepletion(string $jenis, int $jumlah, Livestock $livestock, array $options = []): array
    {
        try {
            $this->validateInputs($jenis, $jumlah, $livestock);

            if (!$this->shouldUseFifoDepletion($livestock, $jenis)) {
                return $this->createErrorResult('FIFO depletion not enabled for this livestock or depletion type');
            }

            $normalizedType = $this->normalizeDepletionType($jenis);
            $depletionData = $this->prepareDepletionData($normalizedType, $jumlah, null, $livestock, $options);

            $preview = $this->fifoService->previewFifoDepletion($depletionData);

            return [
                'success' => true,
                'preview' => true,
                'data' => $preview,
                'can_process' => $preview['distribution']['validation']['is_complete'] ?? false,
                'message' => 'Preview generated successfully'
            ];
        } catch (Exception $e) {
            return $this->createErrorResult('Preview failed: ' . $e->getMessage());
        }
    }

    /**
     * Check if livestock supports FIFO depletion
     * 
     * @param Livestock $livestock Livestock instance
     * @param string|null $depletionType Specific depletion type to check
     * @return bool
     */
    public function canUseFifo(Livestock $livestock, ?string $depletionType = null): bool
    {
        return $this->shouldUseFifoDepletion($livestock, $depletionType);
    }

    /**
     * Get FIFO statistics for livestock
     * 
     * @param Livestock $livestock Livestock instance
     * @param string $period Period ('7_days', '30_days', '90_days')
     * @return array Statistics
     */
    public function getStats(Livestock $livestock, string $period = '30_days'): array
    {
        return $this->fifoService->getFifoDepletionStats($livestock, $period);
    }

    /**
     * Validate inputs
     * 
     * @param string $jenis Depletion type
     * @param int $jumlah Quantity
     * @param Livestock $livestock Livestock instance
     * @throws Exception
     */
    private function validateInputs(string $jenis, int $jumlah, Livestock $livestock): void
    {
        if (empty($jenis)) {
            throw new Exception('Depletion type (jenis) is required');
        }

        if ($jumlah <= 0) {
            throw new Exception('Quantity (jumlah) must be greater than 0');
        }

        if (!$livestock || !$livestock->id) {
            throw new Exception('Valid livestock instance is required');
        }

        if (!isset(self::TYPE_MAPPING[$jenis])) {
            throw new Exception("Unsupported depletion type: {$jenis}");
        }
    }

    /**
     * Check if FIFO depletion should be used
     * 
     * @param Livestock $livestock Livestock instance
     * @param string|null $depletionType Depletion type
     * @return bool
     */
    private function shouldUseFifoDepletion(Livestock $livestock, ?string $depletionType = null): bool
    {
        try {
            // Check livestock configuration
            $config = $livestock->getConfiguration();
            $depletionMethod = $config['depletion_method'] ?? 'manual';

            if ($depletionMethod !== 'fifo') {
                return false;
            }

            // Check if livestock has active batches
            $activeBatchesCount = $livestock->getActiveBatchesCount();
            if ($activeBatchesCount < 1) {
                return false;
            }

            // Check if depletion type is supported
            if ($depletionType && !isset(self::TYPE_MAPPING[$depletionType])) {
                return false;
            }

            return true;
        } catch (Exception $e) {
            Log::error('Error checking FIFO depletion eligibility', [
                'livestock_id' => $livestock->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Normalize depletion type
     * 
     * @param string $jenis Depletion type
     * @return string Normalized type
     */
    private function normalizeDepletionType(string $jenis): string
    {
        return self::TYPE_MAPPING[$jenis] ?? 'mortality';
    }

    /**
     * Prepare depletion data for FIFO service
     * 
     * @param string $normalizedType Normalized depletion type
     * @param int $jumlah Quantity
     * @param string|null $recordingId Recording ID
     * @param Livestock $livestock Livestock instance
     * @param array $options Additional options
     * @return array Prepared data
     */
    private function prepareDepletionData(string $normalizedType, int $jumlah, ?string $recordingId, Livestock $livestock, array $options): array
    {
        $date = $options['date'] ?? now()->format('Y-m-d');
        $reason = $options['reason'] ?? "FIFO depletion via FIFODepletionManagerService";
        $notes = $options['notes'] ?? "Depletion recorded on " . now()->format('Y-m-d H:i:s') . " by " . (auth()->user()->name ?? 'System');

        return [
            'livestock_id' => $livestock->id,
            'depletion_type' => $normalizedType,
            'total_quantity' => $jumlah,
            'depletion_date' => $date,
            'recording_id' => $recordingId,
            'reason' => $reason,
            'notes' => $notes,
            'config_metadata' => [
                'original_type' => $options['original_type'] ?? $normalizedType,
                'normalized_type' => $normalizedType,
                'display_name' => LivestockDepletionConfig::getDisplayName($normalizedType),
                'category' => LivestockDepletionConfig::getCategory($normalizedType),
                'manager_service_version' => '1.0',
                'processed_via' => 'FIFODepletionManagerService'
            ]
        ];
    }

    /**
     * Find or create recording for the given date
     * 
     * @param Livestock $livestock Livestock instance
     * @param string $date Date
     * @return int|null Recording ID
     */
    private function findOrCreateRecording(Livestock $livestock, string $date): ?int
    {
        try {
            $recording = Recording::where('livestock_id', $livestock->id)
                ->whereDate('tanggal', $date)
                ->first();

            if (!$recording) {
                // Create basic recording
                $recording = Recording::create([
                    'livestock_id' => $livestock->id,
                    'tanggal' => $date,
                    'payload' => [
                        'created_by' => 'FIFODepletionManagerService',
                        'auto_created' => true,
                        'created_at' => now()->toDateTimeString()
                    ]
                ]);

                Log::info('📝 FIFO Manager: Auto-created recording', [
                    'livestock_id' => $livestock->id,
                    'recording_id' => $recording->id,
                    'date' => $date
                ]);
            }

            return $recording->id;
        } catch (Exception $e) {
            Log::error('Error finding/creating recording', [
                'livestock_id' => $livestock->id,
                'date' => $date,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Create success result
     * 
     * @param array $result FIFO service result
     * @param Livestock $livestock Livestock instance
     * @param string $normalizedType Normalized depletion type
     * @param int $jumlah Quantity
     * @return array Success result
     */
    private function createSuccessResult(array $result, Livestock $livestock, string $normalizedType, int $jumlah): array
    {
        return [
            'success' => true,
            'method' => 'fifo',
            'livestock_id' => $livestock->id,
            'livestock_name' => $livestock->name,
            'depletion_type' => $normalizedType,
            'quantity' => $jumlah,
            'total_quantity' => $result['total_quantity'], // Fixed: Use total_quantity from FIFODepletionService result
            'batches_affected' => $result['batches_affected'],
            'depletion_records' => $result['depletion_records'],
            'updated_batches' => $result['updated_batches'],
            'processed_at' => $result['processed_at'],
            'message' => "FIFO depletion successful: {$jumlah} units depleted across {$result['batches_affected']} batches",
            'details' => $result
        ];
    }

    /**
     * Create error result
     * 
     * @param string $message Error message
     * @param array $details Additional details
     * @return array Error result
     */
    private function createErrorResult(string $message, array $details = []): array
    {
        return [
            'success' => false,
            'method' => 'fifo',
            'error' => $message,
            'details' => $details,
            'processed_at' => now()->toDateTimeString()
        ];
    }
}

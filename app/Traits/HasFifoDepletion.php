<?php

namespace App\Traits;

use App\Models\Livestock;
use App\Services\Livestock\FIFODepletionManagerService;
use Illuminate\Support\Facades\Log;

/**
 * HasFifoDepletion Trait
 * 
 * Provides easy access to FIFO depletion functionality for controllers,
 * services, and components.
 * 
 * Usage:
 * 1. Add trait to your class: use HasFifoDepletion;
 * 2. Use the methods: $result = $this->storeDeplesiWithFifo($jenis, $jumlah, $recordingId, $livestock);
 * 
 * @author System
 * @version 1.0
 */
trait HasFifoDepletion
{
    /**
     * FIFO Depletion Manager Service instance
     */
    protected ?FIFODepletionManagerService $fifoDepletionManager = null;

    /**
     * Get FIFO Depletion Manager Service instance
     * 
     * @return FIFODepletionManagerService
     */
    protected function getFifoDepletionManager(): FIFODepletionManagerService
    {
        if (!$this->fifoDepletionManager) {
            $this->fifoDepletionManager = app(FIFODepletionManagerService::class);
        }

        return $this->fifoDepletionManager;
    }

    /**
     * Store FIFO depletion (main method matching user's requested signature)
     * 
     * @param string $jenis Depletion type ('Mati', 'Afkir', 'mortality', 'culling', etc.)
     * @param int $jumlah Quantity to deplete
     * @param string|null $recordingId Recording ID using UUID (optional)
     * @param Livestock $livestock Livestock instance
     * @param array $options Additional options (date, reason, notes, etc.)
     * @return array Result with success status and details
     */
    protected function storeDeplesiWithFifo(string $jenis, int $jumlah, ?string $recordingId, Livestock $livestock, array $options = []): array
    {
        return $this->getFifoDepletionManager()->storeDeplesiWithFifo($jenis, $jumlah, $recordingId, $livestock, $options);
    }

    /**
     * Quick store FIFO depletion without recording ID (auto-creates if needed)
     * 
     * @param string $jenis Depletion type
     * @param int $jumlah Quantity
     * @param Livestock $livestock Livestock instance
     * @param array $options Additional options
     * @return array Result
     */
    protected function quickStoreFifoDepletion(string $jenis, int $jumlah, Livestock $livestock, array $options = []): array
    {
        return $this->getFifoDepletionManager()->quickStore($jenis, $jumlah, $livestock, $options);
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
    protected function previewFifoDepletion(string $jenis, int $jumlah, Livestock $livestock, array $options = []): array
    {
        return $this->getFifoDepletionManager()->previewDepletion($jenis, $jumlah, $livestock, $options);
    }

    /**
     * Check if livestock can use FIFO depletion
     * 
     * @param Livestock $livestock Livestock instance
     * @param string|null $depletionType Specific depletion type to check
     * @return bool
     */
    protected function canUseFifoDepletion(Livestock $livestock, ?string $depletionType = null): bool
    {
        return $this->getFifoDepletionManager()->canUseFifo($livestock, $depletionType);
    }

    /**
     * Get FIFO depletion statistics
     * 
     * @param Livestock $livestock Livestock instance
     * @param string $period Period ('7_days', '30_days', '90_days')
     * @return array Statistics
     */
    protected function getFifoDepletionStats(Livestock $livestock, string $period = '30_days'): array
    {
        return $this->getFifoDepletionManager()->getStats($livestock, $period);
    }

    /**
     * Batch store multiple FIFO depletions
     * 
     * @param array $depletions Array of depletion data
     * @param Livestock $livestock Livestock instance
     * @param array $globalOptions Global options applied to all depletions
     * @return array Results
     */
    protected function batchStoreFifoDepletion(array $depletions, Livestock $livestock, array $globalOptions = []): array
    {
        return $this->getFifoDepletionManager()->batchStore($depletions, $livestock, $globalOptions);
    }

    /**
     * Store FIFO depletion with automatic fallback to manual method
     * 
     * @param string $jenis Depletion type
     * @param int $jumlah Quantity
     * @param int|null $recordingId Recording ID
     * @param Livestock $livestock Livestock instance
     * @param array $options Additional options
     * @param callable|null $fallbackMethod Fallback method if FIFO fails
     * @return array Result
     */
    protected function storeDeplesiWithFifoFallback(
        string $jenis,
        int $jumlah,
        ?int $recordingId,
        Livestock $livestock,
        array $options = [],
        ?callable $fallbackMethod = null
    ): array {
        try {
            // Try FIFO first
            $result = $this->storeDeplesiWithFifo($jenis, $jumlah, $recordingId, $livestock, $options);

            if ($result['success']) {
                return $result;
            }

            // If FIFO failed and fallback provided, use fallback
            if ($fallbackMethod && is_callable($fallbackMethod)) {
                Log::info('🔄 FIFO depletion failed, using fallback method', [
                    'livestock_id' => $livestock->id,
                    'depletion_type' => $jenis,
                    'quantity' => $jumlah,
                    'fifo_error' => $result['error'] ?? 'Unknown error'
                ]);

                $fallbackResult = $fallbackMethod($jenis, $jumlah, $recordingId, $livestock, $options);

                // Add fallback indicator to result
                if (is_array($fallbackResult)) {
                    $fallbackResult['method'] = 'manual_fallback';
                    $fallbackResult['fifo_attempted'] = true;
                    $fallbackResult['fifo_error'] = $result['error'] ?? 'FIFO failed';
                }

                return $fallbackResult;
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Error in FIFO depletion with fallback', [
                'livestock_id' => $livestock->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'method' => 'fifo_with_fallback',
                'error' => $e->getMessage(),
                'processed_at' => now()->toDateTimeString()
            ];
        }
    }

    /**
     * Smart depletion method - automatically chooses FIFO or manual based on livestock configuration
     * 
     * @param string $jenis Depletion type
     * @param int $jumlah Quantity
     * @param int|null $recordingId Recording ID
     * @param Livestock $livestock Livestock instance
     * @param array $options Additional options
     * @param callable|null $manualMethod Manual depletion method
     * @return array Result
     */
    protected function smartDepletion(
        string $jenis,
        int $jumlah,
        ?int $recordingId,
        Livestock $livestock,
        array $options = [],
        ?callable $manualMethod = null
    ): array {
        // Check if FIFO should be used
        if ($this->canUseFifoDepletion($livestock, $jenis)) {
            Log::info('📊 Smart depletion: Using FIFO method', [
                'livestock_id' => $livestock->id,
                'depletion_type' => $jenis,
                'quantity' => $jumlah
            ]);

            return $this->storeDeplesiWithFifo($jenis, $jumlah, $recordingId, $livestock, $options);
        }

        // Use manual method if provided
        if ($manualMethod && is_callable($manualMethod)) {
            Log::info('📊 Smart depletion: Using manual method', [
                'livestock_id' => $livestock->id,
                'depletion_type' => $jenis,
                'quantity' => $jumlah
            ]);

            $result = $manualMethod($jenis, $jumlah, $recordingId, $livestock, $options);

            if (is_array($result)) {
                $result['method'] = 'manual_smart';
                $result['fifo_available'] = false;
            }

            return $result;
        }

        // No method available
        return [
            'success' => false,
            'method' => 'smart_depletion',
            'error' => 'No suitable depletion method available',
            'fifo_available' => false,
            'manual_method_provided' => false,
            'processed_at' => now()->toDateTimeString()
        ];
    }
}

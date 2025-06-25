<?php

namespace App\Http\Controllers\Examples;

use App\Http\Controllers\Controller;
use App\Models\Livestock;
use App\Traits\HasFifoDepletion;
use App\Services\Livestock\FIFODepletionManagerService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * FIFO Depletion Example Controller
 * 
 * Demonstrates various ways to use the modular FIFO depletion system
 * across different scenarios and use cases.
 * 
 * @author System
 * @version 1.0
 */
class FifoDepletionExampleController extends Controller
{
    use HasFifoDepletion;

    protected FIFODepletionManagerService $fifoManager;

    public function __construct(FIFODepletionManagerService $fifoManager)
    {
        $this->fifoManager = $fifoManager;
    }

    /**
     * Example 1: Basic FIFO depletion using trait (user's requested signature)
     */
    public function basicDepletionWithTrait(Request $request): JsonResponse
    {
        $livestock = Livestock::findOrFail($request->livestock_id);

        // User's exact requested signature
        $fifoResult = $this->storeDeplesiWithFifo(
            $request->jenis,      // 'Mati', 'Afkir', 'mortality', 'culling'
            $request->jumlah,     // quantity
            $request->recording_id, // optional
            $livestock
        );

        return response()->json([
            'method' => 'trait_basic',
            'result' => $fifoResult
        ]);
    }

    /**
     * Example 2: FIFO depletion with options using trait
     */
    public function depletionWithOptions(Request $request): JsonResponse
    {
        $livestock = Livestock::findOrFail($request->livestock_id);

        $fifoResult = $this->storeDeplesiWithFifo(
            $request->jenis,
            $request->jumlah,
            $request->recording_id,
            $livestock,
            [
                'date' => $request->date ?? now()->format('Y-m-d'),
                'reason' => $request->reason ?? 'API depletion',
                'notes' => $request->notes ?? 'Processed via API'
            ]
        );

        return response()->json([
            'method' => 'trait_with_options',
            'result' => $fifoResult
        ]);
    }

    /**
     * Example 3: Quick depletion (auto-creates recording)
     */
    public function quickDepletion(Request $request): JsonResponse
    {
        $livestock = Livestock::findOrFail($request->livestock_id);

        $result = $this->quickStoreFifoDepletion(
            $request->jenis,
            $request->jumlah,
            $livestock,
            ['reason' => $request->reason ?? 'Quick depletion']
        );

        return response()->json([
            'method' => 'quick_store',
            'result' => $result
        ]);
    }

    /**
     * Example 4: Using dependency injection
     */
    public function depletionWithDI(Request $request): JsonResponse
    {
        $livestock = Livestock::findOrFail($request->livestock_id);

        $fifoResult = $this->fifoManager->storeDeplesiWithFifo(
            $request->jenis,
            $request->jumlah,
            $request->recording_id,
            $livestock
        );

        return response()->json([
            'method' => 'dependency_injection',
            'result' => $fifoResult
        ]);
    }

    /**
     * Example 5: Static method usage
     */
    public function staticMethodDepletion(Request $request): JsonResponse
    {
        $livestock = Livestock::findOrFail($request->livestock_id);

        $result = FIFODepletionManagerService::store(
            $request->jenis,
            $request->jumlah,
            $request->recording_id,
            $livestock,
            [
                'reason' => $request->reason ?? 'Static method depletion',
                'notes' => 'Using static method'
            ]
        );

        return response()->json([
            'method' => 'static_method',
            'result' => $result
        ]);
    }

    /**
     * Example 6: Preview before processing
     */
    public function previewDepletion(Request $request): JsonResponse
    {
        $livestock = Livestock::findOrFail($request->livestock_id);

        $preview = $this->previewFifoDepletion(
            $request->jenis,
            $request->jumlah,
            $livestock
        );

        return response()->json([
            'method' => 'preview',
            'preview' => $preview,
            'can_process' => $preview['can_process'] ?? false
        ]);
    }

    /**
     * Example 7: Check FIFO availability
     */
    public function checkFifoAvailability(Request $request): JsonResponse
    {
        $livestock = Livestock::findOrFail($request->livestock_id);

        $canUseFifo = $this->canUseFifoDepletion($livestock, $request->jenis);

        $stats = [];
        if ($canUseFifo) {
            $stats = $this->getFifoDepletionStats($livestock, '30_days');
        }

        return response()->json([
            'livestock_id' => $livestock->id,
            'livestock_name' => $livestock->name,
            'can_use_fifo' => $canUseFifo,
            'depletion_type' => $request->jenis,
            'stats' => $stats
        ]);
    }

    /**
     * Example 8: Batch processing
     */
    public function batchDepletion(Request $request): JsonResponse
    {
        $livestock = Livestock::findOrFail($request->livestock_id);

        $depletions = $request->depletions; // Array of depletion data

        $result = $this->batchStoreFifoDepletion($depletions, $livestock, [
            'reason' => 'Batch processing via API',
            'date' => now()->format('Y-m-d')
        ]);

        return response()->json([
            'method' => 'batch_processing',
            'result' => $result
        ]);
    }

    /**
     * Example 9: FIFO with automatic fallback
     */
    public function depletionWithFallback(Request $request): JsonResponse
    {
        $livestock = Livestock::findOrFail($request->livestock_id);

        $result = $this->storeDeplesiWithFifoFallback(
            $request->jenis,
            $request->jumlah,
            $request->recording_id,
            $livestock,
            ['reason' => $request->reason ?? 'With fallback'],
            function ($jenis, $jumlah, $recordingId, $livestock, $options) {
                // Manual fallback method
                return [
                    'success' => true,
                    'method' => 'manual_fallback',
                    'livestock_id' => $livestock->id,
                    'depletion_type' => $jenis,
                    'quantity' => $jumlah,
                    'message' => 'Processed using manual fallback method',
                    'processed_at' => now()->toDateTimeString()
                ];
            }
        );

        return response()->json([
            'method' => 'fifo_with_fallback',
            'result' => $result
        ]);
    }

    /**
     * Example 10: Smart depletion (auto-chooses method)
     */
    public function smartDepletion(Request $request): JsonResponse
    {
        $livestock = Livestock::findOrFail($request->livestock_id);

        $result = $this->smartDepletion(
            $request->jenis,
            $request->jumlah,
            $request->recording_id,
            $livestock,
            ['reason' => $request->reason ?? 'Smart depletion'],
            function ($jenis, $jumlah, $recordingId, $livestock, $options) {
                // Manual method if FIFO not available
                return [
                    'success' => true,
                    'method' => 'manual_smart',
                    'livestock_id' => $livestock->id,
                    'depletion_type' => $jenis,
                    'quantity' => $jumlah,
                    'message' => 'Processed using smart manual method',
                    'processed_at' => now()->toDateTimeString()
                ];
            }
        );

        return response()->json([
            'method' => 'smart_depletion',
            'result' => $result
        ]);
    }

    /**
     * Example 11: Complete workflow with validation
     */
    public function completeWorkflow(Request $request): JsonResponse
    {
        $livestock = Livestock::findOrFail($request->livestock_id);

        // Step 1: Check if FIFO is available
        if (!$this->canUseFifoDepletion($livestock, $request->jenis)) {
            return response()->json([
                'success' => false,
                'error' => 'FIFO depletion not available for this livestock',
                'livestock_id' => $livestock->id,
                'depletion_type' => $request->jenis
            ], 400);
        }

        // Step 2: Preview the depletion
        $preview = $this->previewFifoDepletion(
            $request->jenis,
            $request->jumlah,
            $livestock
        );

        if (!($preview['can_process'] ?? false)) {
            return response()->json([
                'success' => false,
                'error' => 'Cannot process depletion',
                'preview' => $preview
            ], 400);
        }

        // Step 3: Process the depletion
        $result = $this->storeDeplesiWithFifo(
            $request->jenis,
            $request->jumlah,
            $request->recording_id,
            $livestock,
            [
                'reason' => $request->reason ?? 'Complete workflow',
                'notes' => 'Processed via complete workflow'
            ]
        );

        return response()->json([
            'method' => 'complete_workflow',
            'preview' => $preview,
            'result' => $result
        ]);
    }

    /**
     * Example 12: Error handling demonstration
     */
    public function errorHandlingExample(Request $request): JsonResponse
    {
        try {
            $livestock = Livestock::findOrFail($request->livestock_id);

            // Attempt FIFO depletion with invalid data to show error handling
            $result = $this->storeDeplesiWithFifo(
                'invalid_type', // This will trigger error
                $request->jumlah,
                $request->recording_id,
                $livestock
            );

            return response()->json([
                'method' => 'error_handling',
                'result' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'method' => 'error_handling'
            ], 500);
        }
    }
}

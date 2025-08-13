<?php

namespace App\Http\Controllers\Expedition;

use App\Http\Controllers\Controller;
use App\Services\ExpeditionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class ExpeditionTransactionController extends Controller
{
    protected ExpeditionService $expeditionService;

    public function __construct(ExpeditionService $expeditionService)
    {
        $this->expeditionService = $expeditionService;
    }

    /**
     * Get expedition cost summary
     */
    public function getCostSummary(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'start_date',
                'end_date',
                'expedition_id',
                'transaction_type',
                'zone',
                'company_id'
            ]);

            $summary = $this->expeditionService->getExpeditionCostSummary($filters);

            Log::info('Expedition cost summary retrieved', [
                'user_id' => Auth::id(),
                'filters' => $filters,
                'total_transactions' => $summary['total_transactions']
            ]);

            return response()->json([
                'success' => true,
                'data' => $summary
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting expedition cost summary', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil ringkasan biaya ekspedisi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get expedition transactions with pagination
     */
    public function getTransactions(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'expedition_id',
                'transaction_type',
                'zone',
                'status',
                'start_date',
                'end_date',
                'company_id'
            ]);

            $perPage = $request->get('per_page', 15);
            $transactions = $this->expeditionService->getExpeditionTransactions($filters, $perPage);

            Log::info('Expedition transactions retrieved', [
                'user_id' => Auth::id(),
                'filters' => $filters,
                'total' => $transactions['pagination']['total']
            ]);

            return response()->json([
                'success' => true,
                'data' => $transactions
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting expedition transactions', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data transaksi ekspedisi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get estimated cost for a route
     */
    public function getEstimatedCost(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'expedition_id' => 'required|string',
                'destination_zone' => 'required|string',
                'weight' => 'required|numeric|min:0.1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $estimatedCost = $this->expeditionService->getEstimatedCost(
                $request->expedition_id,
                $request->destination_zone,
                $request->weight
            );

            Log::info('Expedition cost estimation retrieved', [
                'user_id' => Auth::id(),
                'expedition_id' => $request->expedition_id,
                'zone' => $request->destination_zone,
                'weight' => $request->weight,
                'estimated_cost' => $estimatedCost['estimated_cost'] ?? null
            ]);

            return response()->json([
                'success' => true,
                'data' => $estimatedCost
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting estimated expedition cost', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mendapatkan estimasi biaya ekspedisi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get expedition performance metrics
     */
    public function getPerformance(Request $request, string $expeditionId): JsonResponse
    {
        try {
            $filters = $request->only(['start_date', 'end_date']);

            $performance = $this->expeditionService->getExpeditionPerformance($expeditionId, $filters);

            Log::info('Expedition performance retrieved', [
                'user_id' => Auth::id(),
                'expedition_id' => $expeditionId,
                'filters' => $filters,
                'total_transactions' => $performance['total_transactions']
            ]);

            return response()->json([
                'success' => true,
                'data' => $performance
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting expedition performance', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'expedition_id' => $expeditionId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil performa ekspedisi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get expedition cost comparison
     */
    public function getCostComparison(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'expedition_ids' => 'required|array|min:2',
                'expedition_ids.*' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $filters = $request->only(['start_date', 'end_date', 'zone']);

            $comparison = $this->expeditionService->getExpeditionCostComparison(
                $request->expedition_ids,
                $filters
            );

            Log::info('Expedition cost comparison retrieved', [
                'user_id' => Auth::id(),
                'expedition_ids' => $request->expedition_ids,
                'filters' => $filters,
                'comparison_count' => count($comparison)
            ]);

            return response()->json([
                'success' => true,
                'data' => $comparison
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting expedition cost comparison', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil perbandingan biaya ekspedisi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update expedition transaction status
     */
    public function updateStatus(Request $request, string $transactionId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'required|string|in:pending,in_transit,delivered,failed',
                'tracking_number' => 'nullable|string',
                'notes' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $additionalData = $request->only(['tracking_number', 'notes']);

            $success = $this->expeditionService->updateTransactionStatus(
                $transactionId,
                $request->status,
                $additionalData
            );

            if ($success) {
                Log::info('Expedition transaction status updated', [
                    'user_id' => Auth::id(),
                    'transaction_id' => $transactionId,
                    'status' => $request->status,
                    'additional_data' => $additionalData
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Status transaksi ekspedisi berhasil diperbarui'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui status transaksi ekspedisi'
            ], 500);
        } catch (\Exception $e) {
            Log::error('Error updating expedition transaction status', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'transaction_id' => $transactionId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui status transaksi ekspedisi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available expeditions
     */
    public function getExpeditions(): JsonResponse
    {
        try {
            $expeditions = $this->expeditionService->getActiveExpeditions();

            Log::info('Active expeditions retrieved', [
                'user_id' => Auth::id(),
                'count' => $expeditions->count()
            ]);

            return response()->json([
                'success' => true,
                'data' => $expeditions
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting active expeditions', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data ekspedisi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available zones for an expedition
     */
    public function getZones(Request $request, string $expeditionId): JsonResponse
    {
        try {
            $zones = $this->expeditionService->getAvailableZones($expeditionId);

            Log::info('Available zones retrieved', [
                'user_id' => Auth::id(),
                'expedition_id' => $expeditionId,
                'zone_count' => count($zones)
            ]);

            return response()->json([
                'success' => true,
                'data' => $zones
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting available zones', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'expedition_id' => $expeditionId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil zona yang tersedia',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function exportReport(Request $request)
    {
        $filters = $request->only([
            'start_date',
            'end_date',
            'expedition_id',
            'transaction_type',
            'zone',
            'status',
            'company_id'
        ]);

        $includeHeader = (bool) ($request->boolean('include_header', true));

        try {
            $summary = $this->expeditionService->getExpeditionCostSummary($filters);

            // Ambil transaksi dalam jumlah besar untuk keperluan export HTML
            $txPage = $this->expeditionService->getExpeditionTransactions($filters, 100000);
            $transactions = collect($txPage['data']);

            $expedition = null;
            if (!empty($filters['expedition_id'])) {
                $expedition = $this->expeditionService->getExpedition($filters['expedition_id']);
            }

            return view('pages.reports.expedition', [
                'filters' => $filters,
                'includeHeader' => $includeHeader,
                'summary' => $summary,
                'transactions' => $transactions,
                'expeditionName' => $expedition->name ?? 'Semua Ekspedisi',
            ]);
        } catch (\Exception $e) {
            Log::error('Error exporting expedition report', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'filters' => $filters
            ]);

            return response()->view('pages.reports.export_info', [
                'message' => 'Gagal menyiapkan laporan ekspedisi: ' . $e->getMessage()
            ], 500);
        }
    }
}

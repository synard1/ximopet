<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Services\Livestock\LivestockTrackingService;
use App\Models\Livestock;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class LivestockTrackingController extends Controller
{
    protected LivestockTrackingService $trackingService;

    public function __construct(LivestockTrackingService $trackingService)
    {
        $this->trackingService = $trackingService;
    }

    /**
     * Get complete tracking information for a livestock
     * 
     * @param Request $request
     * @param string $livestockId
     * @return JsonResponse
     */
    public function getTrackingInfo(Request $request, string $livestockId): JsonResponse
    {
        try {
            // Validate livestock exists
            $livestock = Livestock::find($livestockId);
            if (!$livestock) {
                return response()->json([
                    'success' => false,
                    'message' => 'Livestock not found',
                    'data' => null
                ], 404);
            }

            // Check permission
            if (!Auth::user()->can('read livestock tracking')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access',
                    'data' => null
                ], 403);
            }

            $trackingData = $this->trackingService->getCompleteTrackingInfo($livestockId);

            Log::info('API: Livestock tracking data retrieved', [
                'livestock_id' => $livestockId,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tracking data retrieved successfully',
                'data' => $trackingData
            ]);
        } catch (\Exception $e) {
            Log::error('API: Error retrieving livestock tracking data', [
                'livestock_id' => $livestockId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve tracking data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get tracking summary for multiple livestock
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getTrackingSummary(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'livestock_ids' => 'required|array',
                'livestock_ids.*' => 'string|exists:livestock,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check permission
            if (!Auth::user()->can('read livestock tracking')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access',
                    'data' => null
                ], 403);
            }

            $livestockIds = $request->input('livestock_ids');
            $summaryData = $this->trackingService->getTrackingSummaryForMultiple($livestockIds);

            Log::info('API: Livestock tracking summary retrieved', [
                'livestock_count' => count($livestockIds),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tracking summary retrieved successfully',
                'data' => $summaryData
            ]);
        } catch (\Exception $e) {
            Log::error('API: Error retrieving livestock tracking summary', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve tracking summary',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export tracking data
     * 
     * @param Request $request
     * @param string $livestockId
     * @return JsonResponse
     */
    public function exportTrackingData(Request $request, string $livestockId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'format' => 'string|in:json,csv'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Validate livestock exists
            $livestock = Livestock::find($livestockId);
            if (!$livestock) {
                return response()->json([
                    'success' => false,
                    'message' => 'Livestock not found',
                    'data' => null
                ], 404);
            }

            // Check permission
            if (!Auth::user()->can('export livestock tracking')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access',
                    'data' => null
                ], 403);
            }

            $format = $request->input('format', 'json');
            $exportData = $this->trackingService->exportTrackingData($livestockId, $format);

            Log::info('API: Livestock tracking data exported', [
                'livestock_id' => $livestockId,
                'format' => $format,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tracking data exported successfully',
                'data' => $exportData,
                'format' => $format
            ]);
        } catch (\Exception $e) {
            Log::error('API: Error exporting livestock tracking data', [
                'livestock_id' => $livestockId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to export tracking data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get tracking statistics for dashboard
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getTrackingStatistics(Request $request): JsonResponse
    {
        try {
            // Check permission
            if (!Auth::user()->can('view livestock tracking')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access',
                    'data' => null
                ], 403);
            }

            $companyId = Auth::user()->company_id;
            $statistics = $this->trackingService->getTrackingStatistics($companyId);

            Log::info('API: Livestock tracking statistics retrieved', [
                'company_id' => $companyId,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tracking statistics retrieved successfully',
                'data' => $statistics
            ]);
        } catch (\Exception $e) {
            Log::error('API: Error retrieving livestock tracking statistics', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve tracking statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search livestock for tracking
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function searchLivestock(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'search' => 'string|max:255',
                'status' => 'string|in:draft,pending,confirmed,in_transit,in_use,arrived,cancelled,completed,ready',
                'source_type' => 'string|in:purchase,mutation,mixed',
                'per_page' => 'integer|min:1|max:100'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check permission
            if (!Auth::user()->can('read livestock tracking')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access',
                    'data' => null
                ], 403);
            }

            $query = Livestock::query()
                ->with(['farm', 'coop', 'livestockStrain'])
                ->when($request->input('search'), function ($query, $search) {
                    $query->where('name', 'like', '%' . $search . '%')
                        ->orWhereHas('farm', function ($q) use ($search) {
                            $q->where('name', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('coop', function ($q) use ($search) {
                            $q->where('name', 'like', '%' . $search . '%');
                        });
                })
                ->when($request->input('status'), function ($query, $status) {
                    $query->where('status', $status);
                })
                ->when($request->input('source_type'), function ($query, $sourceType) {
                    if ($sourceType === 'purchase') {
                        $query->whereHas('batches', function ($q) {
                            $q->where('source_type', 'purchase');
                        });
                    } elseif ($sourceType === 'mutation') {
                        $query->whereHas('batches', function ($q) {
                            $q->where('source_type', 'mutation');
                        });
                    }
                });

            $perPage = $request->input('per_page', 10);
            $livestock = $query->paginate($perPage);

            Log::info('API: Livestock search for tracking', [
                'search' => $request->input('search'),
                'status' => $request->input('status'),
                'source_type' => $request->input('source_type'),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Livestock search completed successfully',
                'data' => $livestock
            ]);
        } catch (\Exception $e) {
            Log::error('API: Error searching livestock for tracking', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to search livestock',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

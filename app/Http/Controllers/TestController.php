<?php

namespace App\Http\Controllers;

use App\Services\Report\PerformanceReportService;
use App\Services\Report\ReportCalculationService;
use App\Models\Livestock;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class TestController extends Controller
{
    /**
     * Test performance report with specific livestock and date
     */
    public function testPerformanceReport(Request $request, $livestockId = null)
    {
        try {
            // Use provided livestock ID or default to the one specified
            $livestockId = $livestockId ?? '9f577ca2-20b9-4038-ae75-19014c120d80';
            $date = $request->get('date', '2025-07-07');

            // Check if livestock exists
            $livestock = Livestock::find($livestockId);
            if (!$livestock) {
                return response()->json([
                    'success' => false,
                    'message' => "Livestock with ID {$livestockId} not found"
                ], 404);
            }

            Log::info('Testing Performance Report', [
                'livestock_id' => $livestockId,
                'livestock_name' => $livestock->name,
                'test_date' => $date,
                'user_id' => Auth::id() ?? 'guest'
            ]);

            $service = new PerformanceReportService(new ReportCalculationService());

            // Test the performance report
            $result = $service->testPerformanceReport($livestockId, $date);

            // return response()->json([
            //     'success' => true,
            //     'message' => 'Test completed successfully',
            //     'livestock' => [
            //         'id' => $livestock->id,
            //         'name' => $livestock->name,
            //         'coop' => $livestock->coop->name ?? 'Unknown',
            //         'farm' => $livestock->farm->name ?? 'Unknown',
            //         'start_date' => $livestock->start_date,
            //         'initial_quantity' => $livestock->initial_quantity
            //     ],
            //     'test_date' => $date,
            //     'data' => $result
            // ]);
        } catch (\Exception $e) {
            Log::error('Test Performance Report Error', [
                'livestock_id' => $livestockId ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Test failed: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * Debug supply usage data for specific livestock
     */
    public function debugSupplyUsage(Request $request, $livestockId = null)
    {
        try {
            $livestockId = $livestockId ?? '9f577ca2-20b9-4038-ae75-19014c120d80';
            $startDate = $request->get('start_date', '2025-01-01');
            $endDate = $request->get('end_date', '2025-07-07');

            $livestock = Livestock::find($livestockId);
            if (!$livestock) {
                return response()->json([
                    'success' => false,
                    'message' => "Livestock with ID {$livestockId} not found"
                ], 404);
            }

            // Check supply usage records
            $supplyUsages = \App\Models\SupplyUsage::where('livestock_id', $livestockId)
                ->whereBetween('usage_date', [$startDate, $endDate])
                ->with(['details.supply', 'details.unit'])
                ->get();

            $supplyUsageDetails = \App\Models\SupplyUsageDetail::whereHas('supplyUsage', function ($query) use ($livestockId, $startDate, $endDate) {
                $query->where('livestock_id', $livestockId)
                    ->whereBetween('usage_date', [$startDate, $endDate]);
            })
                ->with(['supplyUsage', 'supply', 'unit'])
                ->get();

            $debugData = [
                'livestock' => [
                    'id' => $livestock->id,
                    'name' => $livestock->name,
                    'coop' => $livestock->coop->name ?? 'Unknown',
                    'farm' => $livestock->farm->name ?? 'Unknown'
                ],
                'date_range' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ],
                'supply_usages' => [
                    'count' => $supplyUsages->count(),
                    'records' => $supplyUsages->map(function ($usage) {
                        return [
                            'id' => $usage->id,
                            'date' => $usage->usage_date->format('Y-m-d'),
                            'status' => $usage->status,
                            'details_count' => $usage->details->count(),
                            'details' => $usage->details->map(function ($detail) {
                                return [
                                    'supply_name' => $detail->supply->name ?? 'Unknown',
                                    'unit_name' => $detail->unit->name ?? 'pcs',
                                    'quantity' => $detail->quantity_taken,
                                    'price_per_unit' => $detail->price_per_unit,
                                    'supply_price' => $detail->supply->price ?? 0,
                                    'calculated_cost' => $detail->quantity_taken * ($detail->price_per_unit ?? $detail->supply->price ?? 0)
                                ];
                            })
                        ];
                    })
                ],
                'supply_usage_details' => [
                    'count' => $supplyUsageDetails->count(),
                    'records' => $supplyUsageDetails->map(function ($detail) {
                        return [
                            'supply_name' => $detail->supply->name ?? 'Unknown',
                            'unit_name' => $detail->unit->name ?? 'pcs',
                            'quantity' => $detail->quantity_taken,
                            'price_per_unit' => $detail->price_per_unit,
                            'supply_price' => $detail->supply->price ?? 0,
                            'usage_date' => $detail->supplyUsage->usage_date->format('Y-m-d'),
                            'status' => $detail->supplyUsage->status,
                            'calculated_cost' => $detail->quantity_taken * ($detail->price_per_unit ?? $detail->supply->price ?? 0)
                        ];
                    })
                ]
            ];

            return response()->json([
                'success' => true,
                'message' => 'Supply usage debug data retrieved successfully',
                'data' => $debugData
            ]);
        } catch (\Exception $e) {
            Log::error('Debug Supply Usage Error', [
                'livestock_id' => $livestockId ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Debug failed: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
}

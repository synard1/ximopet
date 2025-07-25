<?php

namespace App\Services\Report;

use App\Models\Farm;
use App\Models\Livestock;
use App\Models\LivestockCost;
use App\Models\LivestockPurchaseItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class CostReportService
{
    /**
     * Generate daily cost report
     * Extracted from ReportsController::exportCostHarian()
     * 
     * @param array $params
     * @return array
     */
    public function generateDailyCostReport(array $params): array
    {
        $farm = Farm::findOrFail($params['farm']);
        $livestock = Livestock::findOrFail($params['periode']);
        $tanggal = Carbon::parse($params['tanggal']);
        $reportType = $params['report_type'];

        Log::info("📊 Generating livestock cost report", [
            'farm_id' => $farm->id,
            'livestock_id' => $livestock->id,
            'date' => $tanggal->format('Y-m-d'),
            'report_type' => $reportType,
            'user_id' => Auth::id()
        ]);

        // dd($farm, $livestock, $tanggal, $reportType);

        // Get cost data for the specified date
        $costData = $this->getCostData($livestock, $tanggal);
        if (!$costData) {
            throw new \Exception('Tidak ada data biaya harian untuk tanggal dan batch ini.');
        }

        // Get initial purchase data
        $initialPurchaseData = $this->getInitialPurchaseData($livestock);

        // Process cost data based on report type
        $processedData = $this->processCostData($costData, $initialPurchaseData, $livestock, $tanggal, $reportType);

        Log::info("📊 Daily cost report generated successfully", [
            'farm_id' => $farm->id,
            'livestock_id' => $livestock->id,
            'total_cost' => $processedData['totals']['total_cost'],
            'cost_per_ayam' => $processedData['totals']['total_cost_per_ayam']
        ]);

        return [
            'farm' => $farm,
            'livestock' => $livestock,
            'tanggal' => $tanggal,
            'reportType' => $reportType,
            'costData' => $costData,
            'initialPurchaseData' => $initialPurchaseData,
            'costs' => $processedData['costs'],
            'totals' => $processedData['totals'],
            'prev_cost_data' => $processedData['prev_cost_data'],
            'summary_data' => $processedData['summary_data'],
            'total_cumulative_cost_calculated' => $processedData['total_cumulative_cost_calculated'],
        ];
    }

    /**
     * Get cost data for livestock and date
     * 
     * @param Livestock $livestock
     * @param Carbon $tanggal
     * @return LivestockCost|null
     */
    private function getCostData(Livestock $livestock, Carbon $tanggal)
    {
        $costData = LivestockCost::where('livestock_id', $livestock->id)
            ->whereDate('tanggal', $tanggal)
            ->first();

        if (!$costData) {
            try {
                $costService = app(\App\Services\Livestock\LivestockCostService::class);
                $costData = $costService->calculateForDate($livestock->id, $tanggal);
            } catch (\Exception $e) {
                // Jika tidak ada data, return null
                return null;
            }
        }
        return $costData;
    }

    /**
     * Get initial purchase data for livestock
     * 
     * @param Livestock $livestock
     * @return array
     */
    private function getInitialPurchaseData(Livestock $livestock): array
    {
        $initialPurchaseItem = LivestockPurchaseItem::where('livestock_id', $livestock->id)
            ->orderBy('created_at', 'asc')
            ->first();

        $data = [
            'price_per_unit' => optional($initialPurchaseItem)->price_per_unit ?? 0,
            'quantity' => optional($initialPurchaseItem)->quantity ?? $livestock->initial_quantity ?? 0,
            'date' => optional($initialPurchaseItem)->start_date ?? $livestock->start_date ?? null,
            'found' => $initialPurchaseItem !== null,
        ];

        $data['total_cost'] = $data['price_per_unit'] * $data['quantity'];

        Log::info("📦 Initial purchase data for report", [
            'price_per_unit' => $data['price_per_unit'],
            'quantity' => $data['quantity'],
            'total_cost' => $data['total_cost'],
            'date' => $data['date'] ? ($data['date'] instanceof \Carbon\Carbon ? $data['date']->format('Y-m-d') : ($data['date'] ? Carbon::parse($data['date'])->format('Y-m-d') : '-')) : '-',
        ]);

        return $data;
    }

    /**
     * Process cost data based on report type
     * 
     * @param LivestockCost|null $costData
     * @param array $initialPurchaseData
     * @param Livestock $livestock
     * @param Carbon $tanggal
     * @param string $reportType
     * @return array
     */
    private function processCostData($costData, array $initialPurchaseData, Livestock $livestock, Carbon $tanggal, string $reportType): array
    {
        $breakdown = $costData->cost_breakdown ?? [];
        $summary = $breakdown['summary'] ?? [];

        $age = $livestock->start_date ? Carbon::parse($livestock->start_date)->diffInDays($tanggal) : 0;
        $stockAwal = $breakdown['stock_awal'] ?? $livestock->initial_quantity ?? 0;
        $stockAkhir = $breakdown['stock_akhir'] ?? $stockAwal;
        $totalCost = $costData->total_cost ?? 0;
        $costPerAyam = $costData->cost_per_ayam ?? 0;

        $costs = [];
        $totals = [
            'total_cost' => $totalCost,
            'total_ayam' => $stockAkhir,
            'daily_cost_per_ayam' => $summary['daily_added_cost_per_chicken'] ?? 0,
            'total_cost_per_ayam' => $stockAkhir > 0 ? round($totalCost / $stockAkhir, 2) : 0,
        ];

        // Main cost entry
        $mainCost = [
            'kandang' => optional($livestock->coop)->name ?? '-',
            'livestock' => $livestock->name,
            'umur' => $age,
            'total_cost' => $totalCost,
            'daily_cost_per_ayam' => $summary['daily_added_cost_per_chicken'] ?? 0,
            'cost_per_ayam' => $costPerAyam,
            'breakdown' => [],
        ];

        if ($reportType === 'detail') {
            $mainCost['breakdown'] = $this->processDetailBreakdown($breakdown, $initialPurchaseData, $tanggal);
        } else {
            $mainCost['breakdown'] = $this->processSimpleBreakdown($breakdown);
        }

        $costs[] = $mainCost;

        // Calculate total cumulative cost for display
        // OPTIMIZED: Calculate ALL costs from start date to current date for true cumulative total
        $totalCumulativeCostCalculated = $this->calculateTotalCumulativeCost($livestock, $tanggal, $initialPurchaseData, $totalCost);

        return [
            'costs' => $costs,
            'totals' => $totals,
            'prev_cost_data' => $breakdown['prev_cost'] ?? [],
            'summary_data' => $summary,
            'total_cumulative_cost_calculated' => $totalCumulativeCostCalculated,
        ];
    }

    /**
     * Calculate total cumulative cost from start date to current date
     * 
     * @param Livestock $livestock
     * @param Carbon $tanggal
     * @param array $initialPurchaseData
     * @param float $currentDayCost
     * @return float
     */
    private function calculateTotalCumulativeCost(Livestock $livestock, Carbon $tanggal, array $initialPurchaseData, float $currentDayCost): float
    {
        try {
            // Get start date from livestock or fallback to 30 days ago
            $startDate = $livestock->start_date
                ? Carbon::parse($livestock->start_date)
                : $tanggal->copy()->subDays(30);

            $yesterday = $tanggal->copy()->subDay();

            // Ensure start date is not after yesterday
            if ($startDate->gt($yesterday)) {
                $startDate = $yesterday->copy();
            }

            // Get all cost records from start date to yesterday
            $allPreviousCosts = LivestockCost::where('livestock_id', $livestock->id)
                ->whereBetween('tanggal', [$startDate->format('Y-m-d'), $yesterday->format('Y-m-d')])
                ->sum('total_cost');

            $totalCumulativeCost = $initialPurchaseData['total_cost'] + $allPreviousCosts + $currentDayCost;

            Log::debug('Cumulative cost calculation optimized', [
                'livestock_id' => $livestock->id,
                'start_date' => $startDate->format('Y-m-d'),
                'yesterday' => $yesterday->format('Y-m-d'),
                'initial_purchase_cost' => $initialPurchaseData['total_cost'],
                'all_previous_costs' => $allPreviousCosts,
                'current_day_cost' => $currentDayCost,
                'total_cumulative_cost' => $totalCumulativeCost,
                'calculation_breakdown' => [
                    'initial' => $initialPurchaseData['total_cost'],
                    'all_previous_costs' => $allPreviousCosts,
                    'today' => $currentDayCost,
                    'total' => $totalCumulativeCost
                ],
                'performance_metrics' => [
                    'calculation_method' => 'all_costs_from_start',
                    'date_range_days' => $startDate->diffInDays($yesterday) + 1,
                    'cache_key' => "cumulative_cost_{$livestock->id}_{$tanggal->format('Y-m-d')}"
                ]
            ]);

            return $totalCumulativeCost;
        } catch (\Exception $e) {
            Log::error('Error calculating cumulative cost', [
                'livestock_id' => $livestock->id,
                'tanggal' => $tanggal->format('Y-m-d'),
                'error' => $e->getMessage(),
                'fallback_to_simple_calculation' => true
            ]);

            // Fallback to simple calculation if error occurs
            return $initialPurchaseData['total_cost'] + $currentDayCost;
        }
    }

    /**
     * Process detailed breakdown for detail report type
     * 
     * @param array $breakdown
     * @param array $initialPurchaseData
     * @param Carbon $tanggal
     * @return array
     */
    private function processDetailBreakdown(array $breakdown, array $initialPurchaseData, Carbon $tanggal): array
    {
        $detailedBreakdown = [];

        // Add Initial Purchase Cost entry (for context)
        if ($initialPurchaseData['price_per_unit'] > 0) {
            $detailedBreakdown[] = [
                'kategori' => 'Harga Awal DOC',
                'jumlah' => $initialPurchaseData['quantity'],
                'satuan' => 'Ekor',
                'harga_satuan' => $initialPurchaseData['price_per_unit'],
                'subtotal' => $initialPurchaseData['total_cost'],
                'tanggal' => $initialPurchaseData['date'] ? $initialPurchaseData['date']->format('d/m/Y') : '-',
                'is_initial_purchase' => true,
            ];
        }

        // Add Feed Details
        $feedDetails = $breakdown['feed_detail'] ?? [];
        foreach ($feedDetails as $feedKey => $feedItem) {
            $detailedBreakdown[] = [
                'kategori' => $feedItem['feed_name'] ?? 'Pakan',
                'jumlah' => $feedItem['jumlah_purchase_unit'] ?? 0,
                'satuan' => $feedItem['purchase_unit'] ?? '-',
                'harga_satuan' => $feedItem['price_per_purchase_unit'] ?? 0,
                'subtotal' => $feedItem['subtotal'] ?? 0,
                'tanggal' => $tanggal->format('d/m/Y'),
                'is_initial_purchase' => false,
            ];
        }

        // Add OVK Details
        $ovkDetails = $breakdown['ovk_detail'] ?? [];
        foreach ($ovkDetails as $ovkKey => $ovkItem) {
            $detailedBreakdown[] = [
                'kategori' => $ovkItem['supply_name'] ?? 'OVK',
                'jumlah' => $ovkItem['quantity'] ?? 0,
                'satuan' => $ovkItem['unit'] ?? '-',
                'harga_satuan' => $ovkItem['price_per_unit'] ?? 0,
                'subtotal' => $ovkItem['subtotal'] ?? 0,
                'tanggal' => $tanggal->format('d/m/Y'),
                'is_initial_purchase' => false,
            ];
        }

        // Add Supply Usage Cost
        $supplyUsageDetail = $breakdown['supply_usage_detail'] ?? [];
        foreach ($supplyUsageDetail as $supplyKey => $supplyItem) {
            $detailedBreakdown[] = [
                'kategori' => $supplyItem['supply_name'] ?? 'Supply',
                'jumlah' => $supplyItem['jumlah_purchase_unit'] ?? 0,
                'satuan' => $supplyItem['purchase_unit'] ?? '-',
                'harga_satuan' => $supplyItem['price_per_purchase_unit'] ?? 0,
                'subtotal' => $supplyItem['subtotal'] ?? 0,
                'tanggal' => isset($supplyItem['usage_date']) && $supplyItem['usage_date'] ? (\Carbon\Carbon::parse($supplyItem['usage_date'])->format('d/m/Y')) : $tanggal->format('d/m/Y'),
                'is_initial_purchase' => false,
            ];
        }
        Log::debug('Detail breakdown processed (after supply usage)', [
            'breakdown_count' => count($detailedBreakdown),
            'categories' => collect($detailedBreakdown)->pluck('kategori')->toArray()
        ]);

        // Add Deplesi Cost
        $deplesiCost = $breakdown['deplesi'] ?? 0;
        $deplesiEkor = $breakdown['deplesi_ekor'] ?? 0;
        if ($deplesiCost > 0) {
            $prevCostData = $breakdown['prev_cost'] ?? [];
            $cumulativeCostPerChicken = $prevCostData['cumulative_cost_per_chicken'] ?? $initialPurchaseData['price_per_unit'];

            $detailedBreakdown[] = [
                'kategori' => 'Deplesi (Harga Kumulatif)',
                'jumlah' => $deplesiEkor,
                'satuan' => 'Ekor',
                'harga_satuan' => $cumulativeCostPerChicken,
                'subtotal' => $deplesiCost,
                'tanggal' => $tanggal->format('d/m/Y'),
                'is_initial_purchase' => false,
                'calculation_note' => 'Harga kumulatif per ayam x jumlah deplesi'
            ];
        }

        return $detailedBreakdown;
    }

    /**
     * Process simple breakdown for simple report type
     * 
     * @param array $breakdown
     * @return array
     */
    private function processSimpleBreakdown(array $breakdown): array
    {
        $simpleBreakdown = [];

        $pakanCost = $breakdown['pakan'] ?? 0;
        if ($pakanCost > 0) {
            $simpleBreakdown[] = [
                'kategori' => 'Pakan',
                'subtotal' => $pakanCost,
            ];
        }

        $ovkCost = $breakdown['ovk'] ?? 0;
        $supplyUsageCost = $breakdown['supply_usage'] ?? 0;
        $totalSupplyCost = $ovkCost + $supplyUsageCost;

        if ($totalSupplyCost > 0) {
            $simpleBreakdown[] = [
                'kategori' => 'OVK & Supply',
                'subtotal' => $totalSupplyCost,
            ];
        }

        $deplesiCost = $breakdown['deplesi'] ?? 0;
        if ($deplesiCost > 0) {
            $simpleBreakdown[] = [
                'kategori' => 'Deplesi',
                'subtotal' => $deplesiCost,
            ];
        }

        Log::debug('Simple breakdown processed', [
            'breakdown_count' => count($simpleBreakdown),
            'categories' => collect($simpleBreakdown)->pluck('kategori')->toArray()
        ]);

        return $simpleBreakdown;
    }

    /**
     * Generate livestock cost summary report
     * 
     * @param array $params
     * @return array
     */
    public function generateLivestockCostSummary(array $params): array
    {
        $livestock = Livestock::findOrFail($params['livestock_id']);
        $startDate = Carbon::parse($params['start_date']);
        $endDate = Carbon::parse($params['end_date']);

        Log::info("📊 Generating livestock cost summary", [
            'livestock_id' => $livestock->id,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'user_id' => Auth::id()
        ]);

        // Get cost data for the period
        $costRecords = LivestockCost::where('livestock_id', $livestock->id)
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->orderBy('tanggal')
            ->get();

        // Process summary data
        $summaryData = $this->processCostSummary($costRecords, $livestock, $startDate, $endDate);

        Log::info("📊 Livestock cost summary generated successfully", [
            'livestock_id' => $livestock->id,
            'records_count' => $costRecords->count(),
            'total_cost' => $summaryData['total_cost'],
            'average_daily_cost' => $summaryData['average_daily_cost']
        ]);

        return [
            'livestock' => $livestock,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'costRecords' => $costRecords,
            'summaryData' => $summaryData
        ];
    }

    /**
     * Process cost summary data
     * 
     * @param \Illuminate\Database\Eloquent\Collection $costRecords
     * @param Livestock $livestock
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    private function processCostSummary($costRecords, Livestock $livestock, Carbon $startDate, Carbon $endDate): array
    {
        $totalCost = $costRecords->sum('total_cost');
        $totalDays = $startDate->diffInDays($endDate) + 1;
        $averageDailyCost = $totalDays > 0 ? $totalCost / $totalDays : 0;

        // Get initial purchase data for context
        $initialPurchaseData = $this->getInitialPurchaseData($livestock);

        // Calculate cost breakdown by category
        $categoryBreakdown = [
            'pakan' => 0,
            'ovk' => 0,
            'supply_usage' => 0,
            'deplesi' => 0,
        ];

        foreach ($costRecords as $record) {
            $breakdown = $record->cost_breakdown ?? [];
            $categoryBreakdown['pakan'] += $breakdown['pakan'] ?? 0;
            $categoryBreakdown['ovk'] += $breakdown['ovk'] ?? 0;
            $categoryBreakdown['supply_usage'] += $breakdown['supply_usage'] ?? 0;
            $categoryBreakdown['deplesi'] += $breakdown['deplesi'] ?? 0;
        }

        // Calculate total cumulative cost including initial purchase
        $totalCumulativeCost = $initialPurchaseData['total_cost'] + $totalCost;

        $summaryData = [
            'total_cost' => $totalCost,
            'total_days' => $totalDays,
            'average_daily_cost' => $averageDailyCost,
            'initial_purchase_cost' => $initialPurchaseData['total_cost'],
            'total_cumulative_cost' => $totalCumulativeCost,
            'category_breakdown' => $categoryBreakdown,
            'cost_per_day' => $costRecords->pluck('total_cost', 'tanggal')->toArray()
        ];

        Log::debug('Cost summary processed', [
            'total_cost' => $totalCost,
            'total_days' => $totalDays,
            'average_daily_cost' => $averageDailyCost,
            'category_breakdown' => $categoryBreakdown
        ]);

        return $summaryData;
    }

    /**
     * Validate cost report parameters
     * 
     * @param array $params
     * @return array
     */
    public function validateParams(array $params): array
    {
        $rules = [
            'farm' => 'required|uuid|exists:farms,id',
            'kandang' => 'required|uuid|exists:coops,id',
            'tahun' => 'required|integer',
            'periode' => 'required|uuid|exists:livestocks,id',
            'tanggal' => 'required|date',
            'report_type' => 'required|in:detail,simple',
        ];

        $validator = validator($params, $rules);

        if ($validator->fails()) {
            Log::warning('Cost report validation failed', [
                'errors' => $validator->errors()->toArray(),
                'params' => $params
            ]);
            throw new \InvalidArgumentException('Invalid parameters: ' . implode(', ', $validator->errors()->all()));
        }

        return $params;
    }

    /**
     * Get cost statistics for a livestock
     * 
     * @param Livestock $livestock
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    public function getCostStatistics(Livestock $livestock, Carbon $startDate, Carbon $endDate): array
    {
        $costRecords = LivestockCost::where('livestock_id', $livestock->id)
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->get();

        $stats = [
            'total_records' => $costRecords->count(),
            'total_cost' => $costRecords->sum('total_cost'),
            'average_cost' => $costRecords->avg('total_cost'),
            'min_cost' => $costRecords->min('total_cost'),
            'max_cost' => $costRecords->max('total_cost'),
            'cost_per_chicken' => $costRecords->avg('cost_per_ayam'),
        ];

        Log::debug('Cost statistics calculated', $stats);

        return $stats;
    }

    /**
     * Validate cumulative cost calculation
     * 
     * @param Livestock $livestock
     * @param Carbon $tanggal
     * @param float $calculatedTotal
     * @return array
     */
    public function validateCumulativeCostCalculation(Livestock $livestock, Carbon $tanggal, float $calculatedTotal): array
    {
        try {
            // Get initial purchase data
            $initialPurchaseData = $this->getInitialPurchaseData($livestock);

            // Get current day cost
            $currentDayCost = LivestockCost::where('livestock_id', $livestock->id)
                ->whereDate('tanggal', $tanggal)
                ->value('total_cost') ?? 0;

            // Get all previous costs
            $startDate = $livestock->start_date
                ? Carbon::parse($livestock->start_date)
                : $tanggal->copy()->subDays(30);
            $yesterday = $tanggal->copy()->subDay();

            if ($startDate->gt($yesterday)) {
                $startDate = $yesterday->copy();
            }

            $allPreviousCosts = LivestockCost::where('livestock_id', $livestock->id)
                ->whereBetween('tanggal', [$startDate->format('Y-m-d'), $yesterday->format('Y-m-d')])
                ->sum('total_cost');

            // Expected calculation
            $expectedTotal = $initialPurchaseData['total_cost'] + $allPreviousCosts + $currentDayCost;

            // Validation result
            $isValid = abs($calculatedTotal - $expectedTotal) < 0.01; // Allow small floating point differences

            $validationResult = [
                'is_valid' => $isValid,
                'calculated_total' => $calculatedTotal,
                'expected_total' => $expectedTotal,
                'difference' => $calculatedTotal - $expectedTotal,
                'breakdown' => [
                    'initial_purchase' => $initialPurchaseData['total_cost'],
                    'all_previous_costs' => $allPreviousCosts,
                    'current_day_cost' => $currentDayCost,
                    'expected_total' => $expectedTotal
                ],
                'date_range' => [
                    'start_date' => $startDate->format('Y-m-d'),
                    'yesterday' => $yesterday->format('Y-m-d'),
                    'current_date' => $tanggal->format('Y-m-d')
                ]
            ];

            Log::info('Cumulative cost validation', [
                'livestock_id' => $livestock->id,
                'tanggal' => $tanggal->format('Y-m-d'),
                'validation_result' => $validationResult
            ]);

            return $validationResult;
        } catch (\Exception $e) {
            Log::error('Error validating cumulative cost', [
                'livestock_id' => $livestock->id,
                'tanggal' => $tanggal->format('Y-m-d'),
                'error' => $e->getMessage()
            ]);

            return [
                'is_valid' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get cumulative cost statistics for monitoring
     * 
     * @param Livestock $livestock
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    public function getCumulativeCostStatistics(Livestock $livestock, Carbon $startDate, Carbon $endDate): array
    {
        try {
            $costRecords = LivestockCost::where('livestock_id', $livestock->id)
                ->whereBetween('tanggal', [$startDate, $endDate])
                ->orderBy('tanggal')
                ->get();

            $initialPurchaseData = $this->getInitialPurchaseData($livestock);

            $statistics = [
                'total_records' => $costRecords->count(),
                'total_cost_period' => $costRecords->sum('total_cost'),
                'average_daily_cost' => $costRecords->avg('total_cost'),
                'min_daily_cost' => $costRecords->min('total_cost'),
                'max_daily_cost' => $costRecords->max('total_cost'),
                'initial_purchase_cost' => $initialPurchaseData['total_cost'],
                'cumulative_cost_at_end' => $initialPurchaseData['total_cost'] + $costRecords->sum('total_cost'),
                'cost_trend' => $costRecords->pluck('total_cost', 'tanggal')->toArray(),
                'calculation_accuracy' => [
                    'total_days' => $startDate->diffInDays($endDate) + 1,
                    'days_with_data' => $costRecords->count(),
                    'data_coverage_percentage' => ($costRecords->count() / ($startDate->diffInDays($endDate) + 1)) * 100
                ]
            ];

            Log::info('Cumulative cost statistics generated', [
                'livestock_id' => $livestock->id,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'statistics' => $statistics
            ]);

            return $statistics;
        } catch (\Exception $e) {
            Log::error('Error generating cumulative cost statistics', [
                'livestock_id' => $livestock->id,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'error' => $e->getMessage()
            ]);

            return [
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Export cost report data to various formats
     * 
     * @param array $data
     * @param string $format
     * @return mixed
     */
    public function export(array $data, string $format)
    {
        switch ($format) {
            case 'excel':
                return $this->exportToExcel($data);
            case 'pdf':
                return $this->exportToPdf($data);
            case 'csv':
                return $this->exportToCsv($data);
            case 'html':
            default:
                return $this->exportToHtml($data);
        }
    }

    /**
     * Export to Excel format
     * 
     * @param array $data
     * @return mixed
     */
    private function exportToExcel(array $data)
    {
        Log::info('Exporting cost report to Excel');
        return response()->json(['message' => 'Excel export not implemented yet']);
    }

    /**
     * Export to PDF format
     * 
     * @param array $data
     * @return mixed
     */
    private function exportToPdf(array $data)
    {
        Log::info('Exporting cost report to PDF');
        return response()->json(['message' => 'PDF export not implemented yet']);
    }

    /**
     * Export to CSV format
     * 
     * @param array $data
     * @return mixed
     */
    private function exportToCsv(array $data)
    {
        return response()->json(['message' => 'CSV export not implemented yet']);
    }

    /**
     * Export to HTML format
     * 
     * @param array $data
     * @return \Illuminate\View\View
     */
    private function exportToHtml(array $data)
    {
        return view('pages.reports.livestock-cost', [
            'farm' => $data['farm']->name,
            'tanggal' => $data['tanggal']->format('d M Y'),
            'report_type' => $data['reportType'],
            'costs' => $data['costs'],
            'totals' => $data['totals'],
            'prev_cost_data' => $data['prev_cost_data'],
            'summary_data' => $data['summary_data'],
            'total_cumulative_cost_calculated' => $data['total_cumulative_cost_calculated'],
            'initial_purchase_data' => $data['initialPurchaseData'],
        ]);
    }
}

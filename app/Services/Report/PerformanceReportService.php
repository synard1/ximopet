<?php

namespace App\Services\Report;

use App\Models\CurrentLivestock;
use App\Models\Livestock;
use App\Models\Recording;
use App\Models\FeedUsageDetail;
use App\Models\LivestockSalesItem;
use App\Models\LivestockDepletion;
use App\Models\SupplyUsageDetail;
use App\Config\LivestockDepletionConfig;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;

class PerformanceReportService
{
    protected $calculationService;

    public function __construct(ReportCalculationService $calculationService)
    {
        $this->calculationService = $calculationService;
    }

    /**
     * Generate enhanced performance report with dynamic feed data and accurate calculations
     * Extracted from ReportsController::exportPerformanceEnhanced()
     * 
     * @param array $livestockIds
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    public function generateEnhancedPerformanceReport(array $livestockIds, Carbon $startDate, Carbon $endDate): array
    {
        Log::info('Generating Enhanced Performance Report', [
            'livestock_ids' => $livestockIds,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'user_id' => Auth::id()
        ]);

        $livestocks = Livestock::whereIn('id', $livestockIds)
            ->with(['coop', 'farm'])
            ->get();

        $report = [];
        $overallTotals = $this->initializeOverallTotals();
        $allFeedNamesAcrossReport = collect();

        foreach ($livestocks as $livestock) {
            $livestockData = $this->processLivestockPerformance($livestock, $startDate, $endDate);

            if ($livestockData) {
                $report[] = $livestockData;
                $this->updateOverallTotals($overallTotals, $livestockData);

                // Collect feed names from this livestock's daily records
                $feedsForThisLivestock = collect($livestockData['daily_records'])->pluck('feed_consumption_by_type')->flatMap(fn($item) => array_keys($item));
                $allFeedNamesAcrossReport = $allFeedNamesAcrossReport->merge($feedsForThisLivestock);
            }
        }

        // Calculate overall averages and percentages
        $this->finalizeOverallTotals($overallTotals, count($report));

        $uniqueFeedNames = $allFeedNamesAcrossReport->unique()->sort()->values();

        Log::info('Enhanced Performance Report generated successfully', [
            'livestock_count' => count($report),
            'overall_fcr' => $overallTotals['overall_fcr'],
            'overall_survival_rate' => $overallTotals['overall_survival_rate'],
            'overall_ip' => $overallTotals['overall_ip'],
            'total_supply_cost' => $overallTotals['total_supply_cost'],
            'supply_cost_per_head' => $overallTotals['supply_cost_per_head']
        ]);

        // Collect supply names from this livestock's daily records
        $allSupplyNamesAcrossReport = collect();
        foreach ($report as $livestockData) {
            $suppliesForThisLivestock = collect($livestockData['daily_records'])->pluck('supply_usage_by_type')->flatMap(fn($item) => array_keys($item));
            $allSupplyNamesAcrossReport = $allSupplyNamesAcrossReport->merge($suppliesForThisLivestock);
        }
        $uniqueSupplyNames = $allSupplyNamesAcrossReport->unique()->sort()->values();

        return [
            'report' => $report,
            'overall_totals' => $overallTotals,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'livestock_count' => count($report),
            'all_feed_names' => $uniqueFeedNames,
            'all_supply_names' => $uniqueSupplyNames
        ];
    }

    /**
     * Process individual livestock performance
     */
    private function processLivestockPerformance(Livestock $livestock, Carbon $startDate, Carbon $endDate): ?array
    {
        $dailyRecords = [];
        $currentDate = $startDate->copy();

        $initialQuantity = (int) $livestock->initial_quantity;
        $cumulativeFeedConsumption = 0;
        $stock = $initialQuantity;

        while ($currentDate->lte($endDate)) {
            $age = $startDate->diffInDays($currentDate);

            // Daily Depletion
            $dailyDepletionQuery = LivestockDepletion::where('livestock_id', $livestock->id)->whereDate('tanggal', $currentDate);
            $dailyMortality = (clone $dailyDepletionQuery)->whereIn('jenis', [LivestockDepletionConfig::TYPE_MORTALITY, LivestockDepletionConfig::LEGACY_TYPE_MATI])->sum('jumlah');
            $dailyCulling = (clone $dailyDepletionQuery)->whereIn('jenis', [LivestockDepletionConfig::TYPE_CULLING, LivestockDepletionConfig::LEGACY_TYPE_AFKIR])->sum('jumlah');
            $totalDailyDepletion = $dailyMortality + $dailyCulling;

            // Daily Sales
            $dailySales = LivestockSalesItem::where('livestock_id', $livestock->id)->whereHas('livestockSale', fn($q) => $q->whereDate('tanggal', $currentDate))->first();
            $dailySalesQty = $dailySales->quantity ?? 0;
            $dailySalesWeight = $dailySales->weight ?? 0;

            // Daily Feed
            $feedUsageData = $this->getFeedUsageData($livestock, $currentDate, $currentDate); // For a single day
            $cumulativeFeedConsumption += $feedUsageData['total_consumption'];

            // Daily Supply Usage
            $supplyUsageData = $this->getSupplyUsageData($livestock, $currentDate, $currentDate); // For a single day

            // Daily Weight
            $recording = Recording::where('livestock_id', $livestock->id)->whereDate('tanggal', $currentDate)->first();
            $dailyWeight = $recording->berat_hari_ini ?? 0;

            // Calculations
            $stockAwalHari = $stock;
            $stock -= ($totalDailyDepletion + $dailySalesQty);
            $stockAkhirHari = $stock;

            $totalLiveWeight = $stockAkhirHari > 0 ? ($stockAkhirHari * $dailyWeight / 1000) : 0;
            $fcrActual = $totalLiveWeight > 0 ? round($cumulativeFeedConsumption / $totalLiveWeight, 3) : 0;
            $survivalRate = $initialQuantity > 0 ? ($stockAkhirHari / $initialQuantity) * 100 : 0;
            $ipActual = ($fcrActual > 0 && $age > 0 && $dailyWeight > 0) ? round(($survivalRate * ($dailyWeight / 1000)) / ($fcrActual * $age) * 100, 0) : 0;

            $dailyRecords[] = [
                'date' => $currentDate->copy(),
                'age' => $age,
                'stock_awal' => $stockAwalHari,
                'mati' => $dailyMortality,
                'afkir' => $dailyCulling,
                'total_deplesi' => $totalDailyDepletion,
                'deplesi_percentage' => $initialQuantity > 0 ? round(($totalDailyDepletion / $stockAwalHari) * 100, 2) : 0,
                'jual_ekor' => $dailySalesQty,
                'jual_kg' => $dailySalesWeight,
                'jual_rata' => $dailySalesQty > 0 ? round($dailySalesWeight / $dailySalesQty, 2) * 1000 : 0,
                'stock_akhir' => $stockAkhirHari,
                'feed_consumption_by_type' => $feedUsageData['by_type'],
                'feed_total' => $feedUsageData['total_consumption'],
                'supply_usage_by_type' => $supplyUsageData['by_type'],
                'supply_total_cost' => $supplyUsageData['total_cost'],
                'bw_actual' => $dailyWeight,
                'fcr_actual' => $fcrActual,
                'ip_actual' => $ipActual,
            ];

            $currentDate->addDay();
        }

        // Calculate summary for this livestock
        $summary = $this->summarizeDailyRecords($dailyRecords);

        return [
            'livestock_id' => $livestock->id,
            'livestock_name' => $livestock->name,
            'coop_name' => $livestock->coop->name ?? 'Unknown',
            'farm_name' => $livestock->farm->name ?? 'Unknown',
            'strain' => $livestock->strain ?? 'Unknown',
            'start_date' => $livestock->start_date,
            'initial_quantity' => $initialQuantity,
            'initial_weight' => (float) $livestock->initial_weight,
            'daily_records' => $dailyRecords,
            'summary' => $summary,
        ];
    }

    /**
     * Get feed usage data with dynamic feed types
     */
    private function getFeedUsageData(Livestock $livestock, Carbon $startDate, Carbon $endDate): array
    {
        $feedUsageDetails = FeedUsageDetail::whereHas('feedUsage', function ($query) use ($livestock, $startDate, $endDate) {
            $query->where('livestock_id', $livestock->id)
                ->whereBetween('usage_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);
        })
            ->with('feed')
            ->get();

        $totalConsumption = $feedUsageDetails->sum('quantity_taken');
        $byType = [];

        foreach ($feedUsageDetails as $detail) {
            $feedName = $detail->feed->name ?? 'Unknown';
            $byType[$feedName] = ($byType[$feedName] ?? 0) + $detail->quantity_taken;
        }

        return [
            'total_consumption' => (float) $totalConsumption,
            'by_type' => $byType
        ];
    }

    /**
     * Get supply usage data with detailed cost calculation
     */
    private function getSupplyUsageData(Livestock $livestock, Carbon $startDate, Carbon $endDate): array
    {
        Log::debug('getSupplyUsageData: Query params', [
            'livestock_id' => $livestock->id,
            'start' => $startDate->format('Y-m-d'),
            'end' => $endDate->format('Y-m-d')
        ]);

        $supplyUsageDetails = SupplyUsageDetail::whereHas('supplyUsage', function ($query) use ($livestock, $startDate, $endDate) {
            $query->where('livestock_id', $livestock->id)
                ->whereDate('usage_date', '>=', $startDate->format('Y-m-d'))
                ->whereDate('usage_date', '<=', $endDate->format('Y-m-d'))
                ->whereIn('status', ['pending', 'in_process', 'completed']);
        })
            ->with(['supply', 'unit', 'supplyUsage'])
            ->get();

        Log::debug('getSupplyUsageData: Found', ['count' => $supplyUsageDetails->count()]);

        $byType = [];
        $totalCost = 0;
        $totalQuantity = 0;

        foreach ($supplyUsageDetails as $detail) {
            $supplyName = $detail->supply->name ?? 'Unknown';
            $quantity = (float) $detail->quantity_taken;
            $unitName = $detail->unit->name ?? 'pcs';

            // Calculate cost using price_per_unit if available, otherwise use supply price
            $unitCost = $detail->price_per_unit ?? $detail->supply->price ?? 0;
            $cost = $quantity * $unitCost;

            if (!isset($byType[$supplyName])) {
                $byType[$supplyName] = [
                    'quantity' => 0,
                    'cost' => 0,
                    'unit' => $unitName,
                    'unit_cost' => $unitCost
                ];
            }

            $byType[$supplyName]['quantity'] += $quantity;
            $byType[$supplyName]['cost'] += $cost;
            $totalCost += $cost;
            $totalQuantity += $quantity;
        }

        return [
            'by_type' => $byType,
            'total_cost' => $totalCost,
            'total_quantity' => $totalQuantity
        ];
    }

    /**
     * Initialize overall totals
     */
    private function initializeOverallTotals(): array
    {
        return [
            'total_livestock' => 0,
            'total_initial_quantity' => 0,
            'total_current_quantity' => 0,
            'total_initial_weight' => 0,
            'total_current_weight' => 0,
            'total_mortality' => 0,
            'total_culling' => 0,
            'total_depletion' => 0,
            'total_sales_quantity' => 0,
            'total_sales_weight' => 0,
            'total_feed_consumption' => 0,
            'total_supply_cost' => 0,
            'average_age' => 0,
            'average_survival_rate' => 0,
            'average_depletion_percentage' => 0,
            'average_weight' => 0,
            'fcr_actual' => 0,
            'fcr_standard' => 0,
            'ip_actual' => 0,
            'feed_per_head' => 0,
            'supply_cost_per_head' => 0,
            'feed_by_type' => [],
            'supply_by_type' => [],
            'overall_fcr' => 0,
            'overall_survival_rate' => 0,
            'overall_ip' => 0
        ];
    }

    /**
     * Update overall totals with livestock data
     */
    private function updateOverallTotals(array &$totals, array $livestockData): void
    {
        $lastDailyRecord = end($livestockData['daily_records']);
        if (!$lastDailyRecord) {
            return; // Skip if no daily records
        }

        $totals['total_livestock']++;
        $totals['total_initial_quantity'] += $livestockData['initial_quantity'];
        $totals['total_current_quantity'] += $lastDailyRecord['stock_akhir'];
        $totals['total_initial_weight'] += $livestockData['initial_weight'];

        // Sum up totals from the summary of daily records
        $dailySummary = $livestockData['summary'] ?? $this->summarizeDailyRecords($livestockData['daily_records']);
        $totals['total_mortality'] += $dailySummary['total_mortality'];
        $totals['total_culling'] += $dailySummary['total_culling'];
        $totals['total_depletion'] += $dailySummary['total_depletion'];
        $totals['total_sales_quantity'] += $dailySummary['total_sales_quantity'];
        $totals['total_sales_weight'] += $dailySummary['total_sales_weight'];
        $totals['total_feed_consumption'] += $dailySummary['total_feed_consumption'];
        $totals['total_supply_cost'] += $dailySummary['total_supply_cost'];

        // Accumulate for averages from the final daily record or summary
        $totals['average_age'] += $lastDailyRecord['age'];
        $totals['average_survival_rate'] += $lastDailyRecord['stock_akhir'] > 0 ? ($lastDailyRecord['stock_akhir'] / $livestockData['initial_quantity']) * 100 : 0;
        $totals['average_weight'] += $lastDailyRecord['bw_actual'];
        $totals['fcr_actual'] += $lastDailyRecord['fcr_actual'] ?? 0;
        $totals['ip_actual'] += $lastDailyRecord['ip_actual'] ?? 0;
        $totals['feed_per_head'] += $lastDailyRecord['stock_akhir'] > 0 ? ($dailySummary['total_feed_consumption'] / $lastDailyRecord['stock_akhir']) : 0;
        $totals['supply_cost_per_head'] += $lastDailyRecord['stock_akhir'] > 0 ? ($dailySummary['total_supply_cost'] / $lastDailyRecord['stock_akhir']) : 0;

        // Aggregate feed by type
        foreach ($dailySummary['feed_by_type'] as $feedType => $consumption) {
            $totals['feed_by_type'][$feedType] = ($totals['feed_by_type'][$feedType] ?? 0) + $consumption;
        }

        // Aggregate supply by type
        foreach ($dailySummary['supply_by_type'] as $supplyType => $data) {
            if (!isset($totals['supply_by_type'][$supplyType])) {
                $totals['supply_by_type'][$supplyType] = [
                    'quantity' => 0,
                    'cost' => 0,
                    'unit' => $data['unit'] ?? 'pcs'
                ];
            }
            $totals['supply_by_type'][$supplyType]['quantity'] += $data['quantity'];
            $totals['supply_by_type'][$supplyType]['cost'] += $data['cost'];
        }
    }

    /**
     * Helper to summarize daily records for one livestock
     */
    private function summarizeDailyRecords(array $dailyRecords): array
    {
        $summary = [
            'total_mortality' => 0,
            'total_culling' => 0,
            'total_depletion' => 0,
            'total_sales_quantity' => 0,
            'total_sales_weight' => 0,
            'total_feed_consumption' => 0,
            'total_supply_cost' => 0,
            'feed_by_type' => [],
            'supply_by_type' => [],
        ];

        foreach ($dailyRecords as $daily) {
            $summary['total_mortality'] += $daily['mati'];
            $summary['total_culling'] += $daily['afkir'];
            $summary['total_depletion'] += $daily['total_deplesi'];
            $summary['total_sales_quantity'] += $daily['jual_ekor'];
            $summary['total_sales_weight'] += $daily['jual_kg'];
            $summary['total_feed_consumption'] += $daily['feed_total'];
            $summary['total_supply_cost'] += $daily['supply_total_cost'];

            // Aggregate feed by type
            foreach ($daily['feed_consumption_by_type'] as $feedType => $consumption) {
                $summary['feed_by_type'][$feedType] = ($summary['feed_by_type'][$feedType] ?? 0) + $consumption;
            }

            // Aggregate supply by type
            foreach ($daily['supply_usage_by_type'] as $supplyType => $data) {
                if (!isset($summary['supply_by_type'][$supplyType])) {
                    $summary['supply_by_type'][$supplyType] = [
                        'quantity' => 0,
                        'cost' => 0,
                        'unit' => $data['unit'] ?? 'pcs'
                    ];
                }
                $summary['supply_by_type'][$supplyType]['quantity'] += $data['quantity'];
                $summary['supply_by_type'][$supplyType]['cost'] += $data['cost'];
            }
        }
        return $summary;
    }

    /**
     * Finalize overall totals with averages
     */
    private function finalizeOverallTotals(array &$totals, int $livestockCount): void
    {
        if ($livestockCount > 0) {
            $totals['average_age'] = round($totals['average_age'] / $livestockCount, 1);
            $totals['average_survival_rate'] = round($totals['average_survival_rate'] / $livestockCount, 2);
            $totals['average_depletion_percentage'] = round($totals['average_depletion_percentage'] / $livestockCount, 2);
            $totals['average_weight'] = round($totals['average_weight'] / $livestockCount, 2);
            $totals['fcr_actual'] = round($totals['fcr_actual'] / $livestockCount, 3);
            $totals['fcr_standard'] = round($totals['fcr_standard'] / $livestockCount, 3);
            $totals['ip_actual'] = round($totals['ip_actual'] / $livestockCount, 0);
            $totals['feed_per_head'] = round($totals['feed_per_head'] / $livestockCount, 0);
            $totals['supply_cost_per_head'] = round($totals['supply_cost_per_head'] / $livestockCount, 2);
        }

        // Calculate overall FCR and IP from totals
        $totals['overall_fcr'] = $this->calculationService->calculateFCR(
            $totals['total_feed_consumption'],
            $totals['total_current_weight']
        );

        $totals['overall_survival_rate'] = $this->calculationService->calculateSurvivalRate(
            $totals['total_current_quantity'],
            $totals['total_initial_quantity']
        );

        $totals['overall_ip'] = $this->calculationService->calculateIP(
            $totals['overall_survival_rate'],
            $totals['average_weight'],
            $totals['average_age'],
            $totals['overall_fcr'] ?? 0
        );

        Log::debug('Overall totals finalized', [
            'livestock_count' => $livestockCount,
            'overall_fcr' => $totals['overall_fcr'],
            'overall_survival_rate' => $totals['overall_survival_rate'],
            'overall_ip' => $totals['overall_ip']
        ]);
    }

    /**
     * Get supply usage summary for a specific livestock
     * 
     * @param Livestock $livestock
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    public function getSupplyUsageSummary(Livestock $livestock, Carbon $startDate, Carbon $endDate): array
    {
        $supplyUsageData = $this->getSupplyUsageData($livestock, $startDate, $endDate);

        $summary = [
            'total_cost' => $supplyUsageData['total_cost'],
            'total_quantity' => $supplyUsageData['total_quantity'],
            'by_type' => $supplyUsageData['by_type'],
            'cost_per_head' => 0,
            'quantity_per_head' => 0
        ];

        // Calculate per head metrics if livestock has current quantity
        $currentQuantity = $livestock->current_quantity ?? $livestock->initial_quantity ?? 0;
        if ($currentQuantity > 0) {
            $summary['cost_per_head'] = round($summary['total_cost'] / $currentQuantity, 2);
            $summary['quantity_per_head'] = round($summary['total_quantity'] / $currentQuantity, 2);
        }

        return $summary;
    }

    /**
     * Export performance report in requested format
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function exportPerformanceReport($request)
    {
        try {
            // Validate input
            $request->validate([
                'periode' => 'required|exists:livestocks,id'
            ]);

            $livestockId = $request->periode;
            $livestock = Livestock::findOrFail($livestockId);
            $startDate = Carbon::parse($livestock->start_date);
            $endDate = Carbon::today();

            Log::info('Export Performance Report', [
                'livestock_id' => $livestockId,
                'livestock_name' => $livestock->name,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'user_id' => Auth::id()
            ]);

            // Generate report data
            $reportData = $this->generateEnhancedPerformanceReport([$livestockId], $startDate, $endDate);
            // dd($reportData);

            // Debug mode for testing specific livestock
            if (request()->has('debug') && request()->debug == 1) {
                $this->debugReportData($reportData, $livestockId);
            }

            // Export in requested format
            $format = $request->format ?? 'html';
            return $this->exportToFormat($reportData, $format);
        } catch (\Exception $e) {
            Log::error('Error exporting performance report: ' . $e->getMessage());
            Log::debug('Stack trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Test method for specific livestock and date
     * 
     * @param string $livestockId
     * @param string $date
     * @return array
     */
    public function testPerformanceReport(string $livestockId, string $date = null): array
    {
        try {
            $livestock = Livestock::findOrFail($livestockId);
            $startDate = Carbon::parse($livestock->start_date);
            $endDate = $date ? Carbon::parse($date) : Carbon::today();

            Log::info('Testing Performance Report', [
                'livestock_id' => $livestockId,
                'livestock_name' => $livestock->name,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'test_mode' => true
            ]);

            // Generate report data
            $reportData = $this->generateEnhancedPerformanceReport([$livestockId], $startDate, $endDate);

            // Debug the data
            $this->debugReportData($reportData, $livestockId, true);

            return $reportData;
        } catch (\Exception $e) {
            Log::error('Error testing performance report: ' . $e->getMessage());
            Log::debug('Stack trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Debug report data with detailed analysis
     * 
     * @param array $reportData
     * @param string $livestockId
     * @param bool $isTestMode
     */
    private function debugReportData(array $reportData, string $livestockId, bool $isTestMode = false): void
    {
        $mode = $isTestMode ? 'TEST' : 'DEBUG';

        echo "<div style='background:#1a1a1a;color:#00ff00;padding:20px;border-radius:8px;max-width:95vw;overflow:auto;font-family:monospace;font-size:12px;margin:20px;'>";
        echo "<h2 style='color:#ffff00;'>🔍 {$mode} MODE - Performance Report Analysis</h2>";
        echo "<h3 style='color:#00ffff;'>📊 Report Overview</h3>";
        echo "<pre>";
        echo "Livestock Count: " . $reportData['livestock_count'] . "\n";
        echo "Start Date: " . $reportData['start_date']->format('Y-m-d') . "\n";
        echo "End Date: " . $reportData['end_date']->format('Y-m-d') . "\n";
        echo "All Feed Names: " . implode(', ', $reportData['all_feed_names']->toArray()) . "\n";
        echo "All Supply Names: " . implode(', ', $reportData['all_supply_names']->toArray()) . "\n";
        echo "</pre>";

        echo "<h3 style='color:#00ffff;'>💰 Overall Totals</h3>";
        echo "<pre>";
        $totals = $reportData['overall_totals'];
        echo "Total Supply Cost: Rp " . number_format($totals['total_supply_cost']) . "\n";
        echo "Supply Cost per Head: Rp " . number_format($totals['supply_cost_per_head']) . "\n";
        echo "Total Feed Consumption: " . number_format($totals['total_feed_consumption']) . "\n";
        echo "Feed per Head: " . number_format($totals['feed_per_head']) . "\n";
        echo "</pre>";

        if (!empty($totals['supply_by_type'])) {
            echo "<h3 style='color:#00ffff;'>📦 Supply by Type (Overall)</h3>";
            echo "<pre>";
            foreach ($totals['supply_by_type'] as $supplyName => $data) {
                echo "{$supplyName}: {$data['quantity']} {$data['unit']} - Rp " . number_format($data['cost']) . "\n";
            }
            echo "</pre>";
        }

        if (!empty($reportData['report'])) {
            $livestockData = $reportData['report'][0];
            echo "<h3 style='color:#00ffff;'>🐄 Livestock Details: {$livestockData['livestock_name']}</h3>";
            echo "<pre>";
            echo "Livestock ID: {$livestockData['livestock_id']}\n";
            echo "Coop: {$livestockData['coop_name']}\n";
            echo "Farm: {$livestockData['farm_name']}\n";
            echo "Initial Quantity: {$livestockData['initial_quantity']}\n";
            echo "Initial Weight: {$livestockData['initial_weight']}\n";
            echo "</pre>";

            if (!empty($livestockData['summary'])) {
                $summary = $livestockData['summary'];
                echo "<h3 style='color:#00ffff;'>📈 Livestock Summary</h3>";
                echo "<pre>";
                echo "Total Supply Cost: Rp " . number_format($summary['total_supply_cost']) . "\n";
                echo "Total Feed Consumption: " . number_format($summary['total_feed_consumption']) . "\n";
                echo "Total Mortality: {$summary['total_mortality']}\n";
                echo "Total Culling: {$summary['total_culling']}\n";
                echo "</pre>";

                if (!empty($summary['supply_by_type'])) {
                    echo "<h3 style='color:#00ffff;'>📦 Supply by Type (Livestock)</h3>";
                    echo "<pre>";
                    foreach ($summary['supply_by_type'] as $supplyName => $data) {
                        echo "{$supplyName}: {$data['quantity']} {$data['unit']} - Rp " . number_format($data['cost']) . "\n";
                    }
                    echo "</pre>";
                }
            }

            // Show first few daily records
            if (!empty($livestockData['daily_records'])) {
                echo "<h3 style='color:#00ffff;'>📅 Sample Daily Records (First 5)</h3>";
                $sampleRecords = array_slice($livestockData['daily_records'], 0, 5);
                foreach ($sampleRecords as $index => $record) {
                    $dayNumber = $index + 1;
                    $dateStr = $record['date']->format('Y-m-d');
                    echo "<h4 style='color:#ffff00;'>Day {$dayNumber}: {$dateStr}</h4>";
                    echo "<pre>";
                    echo "Age: {$record['age']} days\n";
                    echo "Stock: {$record['stock_awal']} → {$record['stock_akhir']}\n";
                    echo "Feed Total: {$record['feed_total']}\n";
                    echo "Supply Total Cost: Rp " . number_format($record['supply_total_cost']) . "\n";

                    if (!empty($record['supply_usage_by_type'])) {
                        echo "Supply Usage:\n";
                        foreach ($record['supply_usage_by_type'] as $supplyName => $data) {
                            echo "  - {$supplyName}: {$data['quantity']} {$data['unit']} (Rp " . number_format($data['cost']) . ")\n";
                        }
                    } else {
                        echo "Supply Usage: No data\n";
                    }
                    echo "</pre>";
                }
            }
        }

        echo "<h3 style='color:#00ffff;'>🔍 Supply Usage Analysis</h3>";
        $this->debugSupplyUsageData($livestockId, $reportData['start_date'], $reportData['end_date']);

        echo "</div>";

        if ($isTestMode) {
            exit;
        }
    }

    /**
     * Debug supply usage data specifically
     * 
     * @param string $livestockId
     * @param Carbon $startDate
     * @param Carbon $endDate
     */
    private function debugSupplyUsageData(string $livestockId, Carbon $startDate, Carbon $endDate): void
    {
        echo "<h4 style='color:#ffff00;'>🔍 Supply Usage Database Query Analysis</h4>";

        // Check supply usage records
        $supplyUsages = \App\Models\SupplyUsage::where('livestock_id', $livestockId)
            ->whereBetween('usage_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->with(['details.supply', 'details.unit'])
            ->get();

        echo "<pre>";
        echo "📊 Supply Usage Records Found: {$supplyUsages->count()}\n\n";

        foreach ($supplyUsages as $usage) {
            echo "Supply Usage ID: {$usage->id}\n";
            echo "Date: {$usage->usage_date->format('Y-m-d')}\n";
            echo "Status: {$usage->status}\n";
            echo "Details Count: {$usage->details->count()}\n";

            foreach ($usage->details as $detail) {
                $supplyName = $detail->supply->name ?? 'Unknown';
                $unitName = $detail->unit->name ?? 'pcs';
                $quantity = $detail->quantity_taken;
                $pricePerUnit = $detail->price_per_unit ?? $detail->supply->price ?? 0;
                $cost = $quantity * $pricePerUnit;

                echo "  - {$supplyName}: {$quantity} {$unitName} @ Rp " . number_format($pricePerUnit) . " = Rp " . number_format($cost) . "\n";
            }
            echo "\n";
        }

        // Check supply usage details directly
        $supplyUsageDetails = SupplyUsageDetail::whereHas('supplyUsage', function ($query) use ($livestockId, $startDate, $endDate) {
            $query->where('livestock_id', $livestockId)
                ->whereBetween('usage_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);
        })
            ->with(['supplyUsage', 'supply', 'unit'])
            ->get();

        echo "📋 Supply Usage Details Found: {$supplyUsageDetails->count()}\n\n";

        foreach ($supplyUsageDetails as $detail) {
            Log::debug('SupplyUsageDetail', [
                'id' => $detail->id,
                'supplyUsage_id' => $detail->supply_usage_id,
                'usage_date' => $detail->supplyUsage->usage_date ?? null,
                'status' => $detail->supplyUsage->status ?? null,
                'quantity' => $detail->quantity_taken,
            ]);
            $supplyName = $detail->supply->name ?? 'Unknown';
            $unitName = $detail->unit->name ?? 'pcs';
            $quantity = $detail->quantity_taken;
            $pricePerUnit = $detail->price_per_unit ?? $detail->supply->price ?? 0;
            $cost = $quantity * $pricePerUnit;
            $usageDate = $detail->supplyUsage->usage_date->format('Y-m-d');
            $status = $detail->supplyUsage->status;

            echo "Date: {$usageDate} | Status: {$status} | {$supplyName}: {$quantity} {$unitName} @ Rp " . number_format($pricePerUnit) . " = Rp " . number_format($cost) . "\n";
        }

        echo "</pre>";
    }

    /**
     * Export to requested format
     */
    private function exportToFormat($reportData, $format)
    {
        switch ($format) {
            case 'excel':
                return $this->exportToExcel($reportData);
            case 'pdf':
                return $this->exportToPdf($reportData);
            case 'csv':
                return $this->exportToCsv($reportData);
            default:
                return $this->exportToHtml($reportData);
        }
    }

    /**
     * Export to HTML format
     */
    private function exportToHtml($reportData)
    {
        return view('pages.reports.performance', [
            'records' => $reportData['report'],
            'overall_totals' => $reportData['overall_totals'],
            'start_date' => $reportData['start_date'],
            'end_date' => $reportData['end_date'],
            'livestock_count' => $reportData['livestock_count'],
            'allFeedNames' => $reportData['all_feed_names']
        ]);
    }

    /**
     * Export to Excel format
     */
    private function exportToExcel($reportData)
    {
        // Implement Excel export logic
        return response()->json(['message' => 'Excel export not implemented yet'], 501);
    }

    /**
     * Export to PDF format
     */
    private function exportToPdf($reportData)
    {
        // Implement PDF export logic
        return response()->json(['message' => 'PDF export not implemented yet'], 501);
    }

    /**
     * Export to CSV format
     */
    private function exportToCsv($reportData)
    {
        // Implement CSV export logic
        return response()->json(['message' => 'CSV export not implemented yet'], 501);
    }
}

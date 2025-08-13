<?php

namespace App\Services\Report;

use App\Models\ExpeditionTransaction;

use App\Services\ExpeditionTrackingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class ExpeditionReportService
{
    protected ExpeditionTrackingService $trackingService;

    public function __construct(ExpeditionTrackingService $trackingService)
    {
        $this->trackingService = $trackingService;
    }

    /**
     * Generate expedition cost dashboard data
     * 
     * @param array $filters
     * @return array
     */
    public function getDashboardData(array $filters = []): array
    {
        $startDate = Carbon::parse($filters['start_date'] ?? Carbon::now()->startOfMonth());
        $endDate = Carbon::parse($filters['end_date'] ?? Carbon::now()->endOfMonth());
        $user = Auth::user();
        $companyId = $filters['company_id'] ?? ($user ? $user->company_id : null);

        Log::info('Generating expedition dashboard data', [
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'company_id' => $companyId
        ]);

        return [
            'summary' => $this->getSummaryMetrics($startDate, $endDate, $companyId),
            'cost_trend' => $this->getCostTrend($startDate, $endDate, $companyId),
            'expedition_comparison' => $this->getExpeditionComparison($startDate, $endDate, $companyId),
            'zone_analysis' => $this->getZoneAnalysis($startDate, $endDate, $companyId),
            'efficiency_metrics' => $this->getEfficiencyMetrics($startDate, $endDate, $companyId),
        ];
    }

    /**
     * Get summary metrics
     */
    protected function getSummaryMetrics(Carbon $startDate, Carbon $endDate, ?string $companyId): array
    {
        $query = ExpeditionTransaction::query()
            ->dateRange($startDate, $endDate);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $transactions = $query->get();
        $previousPeriodStart = $startDate->copy()->subDays($startDate->diffInDays($endDate) + 1);
        $previousQuery = ExpeditionTransaction::query()
            ->dateRange($previousPeriodStart, $startDate->copy()->subDay());

        if ($companyId) {
            $previousQuery->where('company_id', $companyId);
        }

        $previousTransactions = $previousQuery->get();

        return [
            'total_shipments' => $transactions->count(),
            'total_cost' => $transactions->sum('expedition_cost'),
            'total_weight' => $transactions->sum('total_weight'),
            'average_cost_per_kg' => $transactions->avg('cost_per_kg') ?: 0,
            'average_cost_per_shipment' => $transactions->avg('expedition_cost') ?: 0,
            'growth_metrics' => [
                'shipments_growth' => $this->calculateGrowth(
                    $previousTransactions->count(),
                    $transactions->count()
                ),
                'cost_growth' => $this->calculateGrowth(
                    $previousTransactions->sum('expedition_cost'),
                    $transactions->sum('expedition_cost')
                ),
                'efficiency_change' => $this->calculateGrowth(
                    $previousTransactions->avg('cost_per_kg') ?: 0,
                    $transactions->avg('cost_per_kg') ?: 0
                ),
            ]
        ];
    }

    /**
     * Get cost trend over time
     */
    protected function getCostTrend(Carbon $startDate, Carbon $endDate, ?string $companyId): array
    {
        $query = ExpeditionTransaction::query()
            ->select(
                DB::raw('DATE(shipping_date) as date'),
                DB::raw('COUNT(*) as shipment_count'),
                DB::raw('SUM(expedition_cost) as total_cost'),
                DB::raw('SUM(total_weight) as total_weight'),
                DB::raw('AVG(expedition_cost / total_weight) as avg_cost_per_kg')
            )
            ->dateRange($startDate, $endDate)
            ->groupBy('date')
            ->orderBy('date');

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        return $query->get()->map(function ($item) {
            return [
                'date' => $item->date,
                'shipment_count' => (int) $item->shipment_count,
                'total_cost' => (float) $item->total_cost,
                'total_weight' => (float) $item->total_weight,
                'avg_cost_per_kg' => (float) $item->avg_cost_per_kg,
            ];
        })->toArray();
    }

    /**
     * Get expedition comparison
     */
    protected function getExpeditionComparison(Carbon $startDate, Carbon $endDate, ?string $companyId): array
    {
        $query = ExpeditionTransaction::query()
            ->with('expedition')
            ->select(
                'expedition_id',
                DB::raw('COUNT(*) as shipment_count'),
                DB::raw('SUM(expedition_cost) as total_cost'),
                DB::raw('SUM(total_weight) as total_weight'),
                DB::raw('AVG(expedition_cost / total_weight) as avg_cost_per_kg'),
                DB::raw('MIN(expedition_cost / total_weight) as min_cost_per_kg'),
                DB::raw('MAX(expedition_cost / total_weight) as max_cost_per_kg')
            )
            ->dateRange($startDate, $endDate)
            ->groupBy('expedition_id')
            ->orderBy('total_cost', 'desc');

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        return $query->get()->map(function ($item) {
            return [
                'expedition_id' => $item->expedition_id,
                'expedition_name' => $item->expedition->name ?? 'Unknown',
                'shipment_count' => (int) $item->shipment_count,
                'total_cost' => (float) $item->total_cost,
                'total_weight' => (float) $item->total_weight,
                'avg_cost_per_kg' => (float) $item->avg_cost_per_kg,
                'min_cost_per_kg' => (float) $item->min_cost_per_kg,
                'max_cost_per_kg' => (float) $item->max_cost_per_kg,
            ];
        })->toArray();
    }

    /**
     * Get zone analysis
     */
    protected function getZoneAnalysis(Carbon $startDate, Carbon $endDate, ?string $companyId): array
    {
        $query = ExpeditionTransaction::query()
            ->select(
                'destination_zone',
                DB::raw('COUNT(*) as shipment_count'),
                DB::raw('SUM(expedition_cost) as total_cost'),
                DB::raw('SUM(total_weight) as total_weight'),
                DB::raw('AVG(expedition_cost / total_weight) as avg_cost_per_kg')
            )
            ->dateRange($startDate, $endDate)
            ->whereNotNull('destination_zone')
            ->groupBy('destination_zone')
            ->orderBy('total_cost', 'desc');

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        return $query->get()->map(function ($item) {
            return [
                'zone' => $item->destination_zone,
                'shipment_count' => (int) $item->shipment_count,
                'total_cost' => (float) $item->total_cost,
                'total_weight' => (float) $item->total_weight,
                'avg_cost_per_kg' => (float) $item->avg_cost_per_kg,
            ];
        })->toArray();
    }

    /**
     * Get efficiency metrics
     */
    protected function getEfficiencyMetrics(Carbon $startDate, Carbon $endDate, ?string $companyId): array
    {
        $query = ExpeditionTransaction::query()->dateRange($startDate, $endDate);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $transactions = $query->get();

        if ($transactions->isEmpty()) {
            return [
                'cost_efficiency_score' => 0,
                'average_cost_per_kg' => 0,
                'delivery_performance' => 0,
                'cost_variance' => 0,
            ];
        }

        $costPerKg = $transactions->pluck('cost_per_kg')->filter();
        $avgCostPerKg = $costPerKg->avg();
        $costVariance = $costPerKg->count() > 1 ?
            $costPerKg->reduce(function ($carry, $item) use ($avgCostPerKg) {
                return $carry + pow($item - $avgCostPerKg, 2);
            }, 0) / ($costPerKg->count() - 1) : 0;

        $deliveredCount = $transactions->where('status', ExpeditionTransaction::STATUS_DELIVERED)->count();
        $deliveryRate = $transactions->count() > 0 ? ($deliveredCount / $transactions->count()) * 100 : 0;

        return [
            'cost_efficiency_score' => $this->calculateCostEfficiencyScore($avgCostPerKg),
            'average_cost_per_kg' => round($avgCostPerKg, 2),
            'delivery_performance' => round($deliveryRate, 2),
            'cost_variance' => round(sqrt($costVariance), 2),
        ];
    }

    /**
     * Calculate growth percentage
     */
    protected function calculateGrowth(float $previous, float $current): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }

    /**
     * Calculate cost efficiency score (0-100)
     */
    protected function calculateCostEfficiencyScore(float $avgCostPerKg): float
    {
        if ($avgCostPerKg <= 0) {
            return 0;
        }

        // Score based on cost per kg compared to industry standard
        $industryStandard = 3000; // Rp 3000 per kg as standard

        // Lower cost = higher efficiency score
        $efficiencyScore = max(0, min(100, (($industryStandard - $avgCostPerKg) / $industryStandard) * 100 + 50));

        return round($efficiencyScore, 2);
    }

    /**
     * Export expedition report to Excel/CSV
     * 
     * @param array $filters
     * @param string $format
     * @return array
     */
    public function exportReport(array $filters = [], string $format = 'excel'): array
    {
        $startDate = Carbon::parse($filters['start_date'] ?? Carbon::now()->startOfMonth());
        $endDate = Carbon::parse($filters['end_date'] ?? Carbon::now()->endOfMonth());
        $user = Auth::user();
        $companyId = $filters['company_id'] ?? ($user ? $user->company_id : null);

        $query = ExpeditionTransaction::query()
            ->with(['expedition'])
            ->dateRange($startDate, $endDate);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        if (isset($filters['expedition_id'])) {
            $query->forExpedition($filters['expedition_id']);
        }

        if (isset($filters['zone'])) {
            $query->inZone($filters['zone']);
        }

        $transactions = $query->orderBy('shipping_date', 'desc')->get();

        return [
            'filename' => 'expedition_report_' . $startDate->format('Y-m-d') . '_to_' . $endDate->format('Y-m-d'),
            'data' => $transactions->map(function ($transaction) {
                return [
                    'shipping_date' => $transaction->shipping_date->format('Y-m-d'),
                    'invoice_number' => $transaction->invoice_number,
                    'expedition_name' => $transaction->expedition->name ?? '',
                    'destination_zone' => $transaction->destination_zone,
                    'destination_address' => $transaction->destination_address,
                    'total_weight' => $transaction->total_weight,
                    'expedition_cost' => $transaction->expedition_cost,
                    'cost_per_kg' => $transaction->cost_per_kg,
                    'status' => $transaction->status,
                    'notes' => $transaction->notes,
                ];
            })->toArray(),
            'summary' => $this->getSummaryMetrics($startDate, $endDate, $companyId),
        ];
    }
}

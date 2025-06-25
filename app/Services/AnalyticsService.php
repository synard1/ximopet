<?php

namespace App\Services;

use App\Models\DailyAnalytics;
use App\Models\PeriodAnalytics;
use App\Models\AnalyticsAlert;
use App\Models\Livestock;
use App\Models\Farm;
use App\Models\Coop;
use App\Models\Recording;
use App\Models\LivestockDepletion;
use App\Models\SalesTransaction;
use App\Models\FeedUsage;
use App\Config\LivestockDepletionConfig;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class AnalyticsService
{
    /**
     * Calculate daily analytics for all active livestock
     */
    public function calculateDailyAnalytics($date = null): void
    {
        $date = $date ? Carbon::parse($date) : Carbon::today();

        // Log process start
        logger()->info("Starting daily analytics calculation for date: {$date->format('Y-m-d')}");

        $activeLivestock = Livestock::where('status', 'active')
            ->whereDate('start_date', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $date);
            })
            ->get();

        foreach ($activeLivestock as $livestock) {
            $this->calculateLivestockDailyAnalytics($livestock, $date);
        }

        // Generate alerts after daily calculations
        $this->generateDailyAlerts($date);

        logger()->info("Completed daily analytics calculation for {$activeLivestock->count()} livestock");
    }

    /**
     * Calculate daily analytics with detailed results for command line interface
     */
    public function calculateDailyAnalyticsWithResults(Carbon $date, bool $force = false): array
    {
        $startTime = microtime(true);
        $analyticsCreated = 0;
        $alertsCreated = 0;
        $insights = [];

        // Log process start
        logger()->info("Starting daily analytics calculation for date: {$date->format('Y-m-d')}");

        $activeLivestock = Livestock::where('status', 'active')
            ->whereDate('start_date', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $date);
            })
            ->get();

        foreach ($activeLivestock as $livestock) {
            // Skip if already calculated and not forced
            if (
                !$force && DailyAnalytics::where('livestock_id', $livestock->id)
                ->whereDate('date', $date)
                ->exists()
            ) {
                continue;
            }

            $this->calculateLivestockDailyAnalytics($livestock, $date);
            $analyticsCreated++;

            // Check for insights based on calculated data
            $analytic = DailyAnalytics::where('livestock_id', $livestock->id)
                ->whereDate('date', $date)
                ->first();

            if ($analytic) {
                if ($analytic->mortality_count > 50) {
                    $insights[] = "High mortality detected in {$livestock->name}: {$analytic->mortality_count} deaths";
                }
                if ($analytic->efficiency_score < 60) {
                    $insights[] = "Low efficiency in {$livestock->name}: {$analytic->efficiency_score}% efficiency score";
                }
            }
        }

        // Generate alerts after daily calculations
        $alertsGenerated = $this->generateDailyAlertsWithCount($date);
        $alertsCreated = $alertsGenerated;

        $processingTime = round(microtime(true) - $startTime, 2);

        logger()->info("Completed daily analytics calculation for {$activeLivestock->count()} livestock");

        return [
            'analytics_created' => $analyticsCreated,
            'alerts_created' => $alertsCreated,
            'livestock_processed' => $activeLivestock->count(),
            'processing_time' => $processingTime,
            'insights' => $insights
        ];
    }

    /**
     * Calculate daily analytics for specific livestock
     */
    private function calculateLivestockDailyAnalytics(Livestock $livestock, Carbon $date): void
    {
        // Get mortality data for the day
        $mortalityData = $this->getMortalityMetrics($livestock, $date);

        // Get sales data for the day
        $salesData = $this->getSalesMetrics($livestock, $date);

        // Get feed consumption data
        $feedData = $this->getFeedMetrics($livestock, $date);

        // Get production data (weight, population)
        $productionData = $this->getProductionMetrics($livestock, $date);

        // Calculate efficiency score
        $efficiencyScore = $this->calculateEfficiencyScore([
            'mortality_rate' => $mortalityData['mortality_rate'],
            'fcr' => $feedData['fcr'],
            'daily_gain' => $productionData['daily_weight_gain'],
            'production_index' => $productionData['production_index']
        ]);

        // Create or update daily analytics record
        DailyAnalytics::updateOrCreate(
            [
                'date' => $date,
                'livestock_id' => $livestock->id,
            ],
            array_merge($mortalityData, $salesData, $feedData, $productionData, [
                'farm_id' => $livestock->farm_id,
                'coop_id' => $livestock->coop_id,
                'efficiency_score' => $efficiencyScore,
                'created_by' => auth()->id() ?? 1,
                'updated_by' => auth()->id() ?? 1,
            ])
        );
    }

    /**
     * Get mortality metrics for specific livestock and date
     */
    private function getMortalityMetrics(Livestock $livestock, Carbon $date): array
    {
        // Use config normalization for backward compatibility
        $mortalityTypes = [
            LivestockDepletionConfig::LEGACY_TYPE_MATI,
            LivestockDepletionConfig::TYPE_MORTALITY
        ];

        $dailyMortality = LivestockDepletion::where('livestock_id', $livestock->id)
            ->whereDate('tanggal', $date)
            ->whereIn('jenis', $mortalityTypes)
            ->sum('jumlah');

        $currentPopulation = $this->getCurrentPopulation($livestock, $date);
        $cumulativeMortality = $this->getCumulativeMortality($livestock, $date);

        $mortalityRate = $currentPopulation > 0 ? ($dailyMortality / $currentPopulation) * 100 : 0;

        return [
            'mortality_count' => $dailyMortality,
            'mortality_rate' => round($mortalityRate, 2),
            'cumulative_mortality' => $cumulativeMortality,
            'current_population' => $currentPopulation,
        ];
    }

    /**
     * Get sales metrics for specific livestock and date
     */
    private function getSalesMetrics(Livestock $livestock, Carbon $date): array
    {
        $salesData = \App\Models\LivestockSalesItem::where('livestock_id', $livestock->id)
            ->whereDate('tanggal', $date)
            ->selectRaw('
                COUNT(*) as sales_count,
                SUM(jumlah) as sales_quantity,
                SUM(berat_total) as sales_weight,
                SUM(jumlah * harga_satuan) as sales_revenue
            ')
            ->first();

        return [
            'sales_count' => $salesData->sales_count ?? 0,
            'sales_weight' => $salesData->sales_weight ?? 0,
            'sales_revenue' => $salesData->sales_revenue ?? 0,
        ];
    }

    /**
     * Get feed consumption metrics
     */
    private function getFeedMetrics(Livestock $livestock, Carbon $date): array
    {
        // Get daily feed consumption from FeedUsageDetail through FeedUsage
        $feedConsumption = \App\Models\FeedUsageDetail::whereHas('feedUsage', function ($query) use ($livestock, $date) {
            $query->where('livestock_id', $livestock->id)
                ->whereDate('usage_date', $date);
        })->sum('quantity_taken') ?? 0;

        $currentPopulation = $this->getCurrentPopulation($livestock, $date);
        $avgWeight = $this->getAverageWeight($livestock, $date);

        // Calculate FCR (Feed Conversion Ratio) - Total Feed consumed / Total Live Weight
        $ageDays = $this->getAgeDays($livestock, $date);
        $totalFeedConsumed = \App\Models\FeedUsageDetail::whereHas('feedUsage', function ($query) use ($livestock, $date) {
            $query->where('livestock_id', $livestock->id)
                ->whereDate('usage_date', '<=', $date);
        })->sum('quantity_taken') ?? 0;

        // Calculate FCR = Total Feed / Total Live Weight (kg)
        $totalLiveWeight = ($currentPopulation * $avgWeight) / 1000; // Convert to kg

        $fcr = ($totalLiveWeight > 0 && $totalFeedConsumed > 0)
            ? $totalFeedConsumed / $totalLiveWeight
            : 0;

        // Cap FCR at reasonable maximum (10.0)
        $fcr = min($fcr, 10.0);

        return [
            'feed_consumption' => $feedConsumption,
            'fcr' => round($fcr, 3),
        ];
    }

    /**
     * Get production metrics
     */
    private function getProductionMetrics(Livestock $livestock, Carbon $date): array
    {
        $avgWeight = $this->getAverageWeight($livestock, $date);
        $dailyGain = $this->getDailyWeightGain($livestock, $date);
        $ageDays = $this->getAgeDays($livestock, $date);

        // Production Index calculation (simplified)
        $productionIndex = ($avgWeight * 100) / ($ageDays * 1.5); // Simplified formula

        return [
            'average_weight' => $avgWeight,
            'daily_weight_gain' => $dailyGain,
            'age_days' => $ageDays,
            'production_index' => round($productionIndex, 2),
        ];
    }

    /**
     * Calculate efficiency score (0-100)
     */
    private function calculateEfficiencyScore(array $metrics): float
    {
        $score = 0;

        // Mortality score (40% weight) - lower is better
        if ($metrics['mortality_rate'] <= 1) $score += 40;
        elseif ($metrics['mortality_rate'] <= 3) $score += 30;
        elseif ($metrics['mortality_rate'] <= 5) $score += 20;
        elseif ($metrics['mortality_rate'] <= 8) $score += 10;
        else $score += 0;

        // FCR score (30% weight) - lower is better
        if ($metrics['fcr'] <= 1.5) $score += 30;
        elseif ($metrics['fcr'] <= 1.8) $score += 25;
        elseif ($metrics['fcr'] <= 2.0) $score += 20;
        elseif ($metrics['fcr'] <= 2.5) $score += 10;
        else $score += 0;

        // Daily gain score (20% weight)
        if ($metrics['daily_gain'] >= 50) $score += 20;
        elseif ($metrics['daily_gain'] >= 40) $score += 15;
        elseif ($metrics['daily_gain'] >= 30) $score += 10;
        elseif ($metrics['daily_gain'] >= 20) $score += 5;
        else $score += 0;

        // Production index score (10% weight)
        if ($metrics['production_index'] >= 300) $score += 10;
        elseif ($metrics['production_index'] >= 250) $score += 8;
        elseif ($metrics['production_index'] >= 200) $score += 5;
        else $score += 0;

        return round($score, 2);
    }

    /**
     * Generate daily alerts based on analytics
     */
    private function generateDailyAlerts(Carbon $date): void
    {
        $analytics = DailyAnalytics::where('date', $date)->get();

        foreach ($analytics as $analytic) {
            // High mortality alert
            if ($analytic->mortality_count > 100) {
                $this->createAlert(
                    $analytic,
                    'high_mortality',
                    'critical',
                    'Critical Mortality Alert',
                    "Extremely high mortality detected: {$analytic->mortality_count} deaths today",
                    'Immediate health management evaluation required. Check for disease outbreak, environmental stress, or feed contamination.'
                );
            } elseif ($analytic->mortality_count > 50) {
                $this->createAlert(
                    $analytic,
                    'high_mortality',
                    'high',
                    'High Mortality Alert',
                    "High mortality detected: {$analytic->mortality_count} deaths today",
                    'Monitor health conditions closely and consider veterinary consultation.'
                );
            }

            // Poor growth alert
            if ($analytic->daily_weight_gain < 30) {
                $this->createAlert(
                    $analytic,
                    'poor_growth',
                    'medium',
                    'Poor Growth Performance',
                    "Low daily weight gain: {$analytic->daily_weight_gain}g/day",
                    'Review feed quality, quantity, and environmental conditions. Consider nutritional supplements.'
                );
            }

            // Low efficiency alert
            if ($analytic->efficiency_score < 60) {
                $this->createAlert(
                    $analytic,
                    'low_efficiency',
                    'high',
                    'Low Efficiency Score',
                    "Efficiency score below threshold: {$analytic->efficiency_score}%",
                    'Comprehensive review of management practices, feed program, and health protocols needed.'
                );
            }

            // High FCR alert
            if ($analytic->fcr > 2.5) {
                $this->createAlert(
                    $analytic,
                    'high_fcr',
                    'medium',
                    'High Feed Conversion Ratio',
                    "FCR above optimal range: {$analytic->fcr}",
                    'Optimize feed formulation and feeding schedule. Check for feed wastage.'
                );
            }
        }
    }

    /**
     * Generate daily alerts based on analytics and return count
     */
    private function generateDailyAlertsWithCount(Carbon $date): int
    {
        $analytics = DailyAnalytics::where('date', $date)->get();
        $alertsCreated = 0;

        foreach ($analytics as $analytic) {
            // High mortality alert
            if ($analytic->mortality_count > 100) {
                $this->createAlert(
                    $analytic,
                    'high_mortality',
                    'critical',
                    'Critical Mortality Alert',
                    "Extremely high mortality detected: {$analytic->mortality_count} deaths today",
                    'Immediate health management evaluation required. Check for disease outbreak, environmental stress, or feed contamination.'
                );
                $alertsCreated++;
            } elseif ($analytic->mortality_count > 50) {
                $this->createAlert(
                    $analytic,
                    'high_mortality',
                    'high',
                    'High Mortality Alert',
                    "High mortality detected: {$analytic->mortality_count} deaths today",
                    'Monitor health conditions closely and consider veterinary consultation.'
                );
                $alertsCreated++;
            }

            // Poor growth alert
            if ($analytic->daily_weight_gain < 30) {
                $this->createAlert(
                    $analytic,
                    'poor_growth',
                    'medium',
                    'Poor Growth Performance',
                    "Low daily weight gain: {$analytic->daily_weight_gain}g/day",
                    'Review feed quality, quantity, and environmental conditions. Consider nutritional supplements.'
                );
                $alertsCreated++;
            }

            // Low efficiency alert
            if ($analytic->efficiency_score < 60) {
                $this->createAlert(
                    $analytic,
                    'low_efficiency',
                    'high',
                    'Low Efficiency Score',
                    "Efficiency score below threshold: {$analytic->efficiency_score}%",
                    'Comprehensive review of management practices, feed program, and health protocols needed.'
                );
                $alertsCreated++;
            }

            // High FCR alert
            if ($analytic->fcr > 2.5) {
                $this->createAlert(
                    $analytic,
                    'high_fcr',
                    'medium',
                    'High Feed Conversion Ratio',
                    "FCR above optimal range: {$analytic->fcr}",
                    'Optimize feed formulation and feeding schedule. Check for feed wastage.'
                );
                $alertsCreated++;
            }
        }

        return $alertsCreated;
    }

    /**
     * Create analytics alert
     */
    private function createAlert(DailyAnalytics $analytic, string $type, string $severity, string $title, string $description, string $recommendation): void
    {
        // Check if similar alert exists and is unresolved
        $existingAlert = AnalyticsAlert::where('livestock_id', $analytic->livestock_id)
            ->where('alert_type', $type)
            ->where('is_resolved', false)
            ->whereDate('created_at', '>=', Carbon::today()->subDays(3))
            ->first();

        if (!$existingAlert) {
            AnalyticsAlert::create([
                'livestock_id' => $analytic->livestock_id,
                'farm_id' => $analytic->farm_id,
                'coop_id' => $analytic->coop_id,
                'alert_type' => $type,
                'severity' => $severity,
                'title' => $title,
                'description' => $description,
                'recommendation' => $recommendation,
                'metrics' => [
                    'mortality_count' => $analytic->mortality_count,
                    'mortality_rate' => $analytic->mortality_rate,
                    'efficiency_score' => $analytic->efficiency_score,
                    'fcr' => $analytic->fcr,
                    'daily_weight_gain' => $analytic->daily_weight_gain,
                ],
                'created_by' => auth()->id() ?? 1,
            ]);
        }
    }

    /**
     * Get smart insights for dashboard
     */
    public function getSmartInsights(array $filters = []): array
    {
        try {
            $query = $this->buildAnalyticsQuery($filters);

            $insights = [
                'overview' => $this->getOverviewInsights($query),
                'mortality_analysis' => $this->getMortalityAnalysis($filters),
                'sales_analysis' => $this->getSalesAnalysis($filters),
                'production_analysis' => $this->getProductionAnalysis($filters),
                'coop_rankings' => $this->getCoopPerformanceRankings($filters),
                'alerts' => $this->getActiveAlerts($filters),
                'trends' => $this->getTrendAnalysis($filters),
            ];

            logger()->info("Generated smart insights successfully", [
                'filters' => $filters,
                'overview_total_livestock' => $insights['overview']['total_livestock'] ?? 0,
                'mortality_analysis_count' => $insights['mortality_analysis']->count() ?? 0,
                'alerts_count' => $insights['alerts']->count() ?? 0
            ]);

            return $insights;
        } catch (\Exception $e) {
            logger()->error("Failed to generate smart insights: " . $e->getMessage(), [
                'filters' => $filters,
                'exception' => $e
            ]);

            // Return safe default structure
            return [
                'overview' => [
                    'total_livestock' => 0,
                    'avg_mortality_rate' => 0,
                    'avg_efficiency_score' => 0,
                    'avg_fcr' => 0,
                    'total_revenue' => 0,
                    'problematic_coops' => 0,
                    'high_performers' => 0,
                ],
                'mortality_analysis' => collect(),
                'sales_analysis' => collect(),
                'production_analysis' => collect(),
                'coop_rankings' => collect(),
                'alerts' => collect(),
                'trends' => [
                    'mortality_trend' => [],
                    'efficiency_trend' => [],
                    'fcr_trend' => [],
                    'revenue_trend' => [],
                ],
            ];
        }
    }

    /**
     * Get overview insights
     */
    private function getOverviewInsights($query): array
    {
        $analytics = $query->get();

        // If no analytics data or data seems incorrect, use CurrentLivestock directly
        if ($analytics->isEmpty() || $analytics->sum('current_population') > 100000) {
            logger()->info('[Analytics Debug] Using CurrentLivestock data as fallback');

            $currentLivestock = \App\Models\CurrentLivestock::all();
            $totalLivestock = $currentLivestock->sum('quantity');
            $coopCount = \App\Models\Coop::count();

            return [
                'total_livestock' => $totalLivestock,
                'avg_mortality_rate' => 0.0004, // 0.04% default
                'avg_efficiency_score' => 40.0,
                'avg_fcr' => 10.0,
                'total_revenue' => 0,
                'problematic_coops' => $coopCount, // All coops need attention by default
                'high_performers' => 0,
            ];
        }

        // Calculate total current livestock population (sum of current_population)
        $totalLivestock = $analytics->sum('current_population');

        // Calculate mortality rate with better precision for small values
        $avgMortalityRate = $analytics->avg('mortality_rate');
        $mortalityRatePrecision = $avgMortalityRate < 0.01 ? 4 : 2; // 4 decimal places for very small values

        return [
            'total_livestock' => $totalLivestock,
            'avg_mortality_rate' => round($avgMortalityRate, $mortalityRatePrecision),
            'avg_efficiency_score' => round($analytics->avg('efficiency_score'), 2),
            'avg_fcr' => round($analytics->avg('fcr'), 3),
            'total_revenue' => round($analytics->sum('sales_revenue'), 2),
            'problematic_coops' => $analytics->where('efficiency_score', '<', 60)->count(),
            'high_performers' => $analytics->where('efficiency_score', '>', 80)->count(),
        ];
    }

    /**
     * Get mortality analysis by coop
     */
    private function getMortalityAnalysis(array $filters): Collection
    {
        try {
            logger()->info('[Analytics Debug] Getting mortality analysis with filters', $filters);

            $query = DailyAnalytics::query()
                ->when($filters['farm_id'] ?? null, fn($q, $farm) => $q->where('farm_id', $farm))
                ->when($filters['coop_id'] ?? null, fn($q, $coop) => $q->where('coop_id', $coop))
                ->when($filters['livestock_id'] ?? null, fn($q, $livestock) => $q->where('livestock_id', $livestock))
                ->when($filters['date_from'] ?? null, fn($q, $date) => $q->where('date', '>=', $date))
                ->when($filters['date_to'] ?? null, fn($q, $date) => $q->where('date', '<=', $date))
                ->select([
                    'coop_id',
                    DB::raw('AVG(mortality_rate) as avg_mortality_rate'),
                    DB::raw('SUM(mortality_count) as total_mortality'),
                    DB::raw('AVG(current_population) as avg_population'),
                    DB::raw('COUNT(*) as days_recorded')
                ])
                ->groupBy('coop_id')
                ->orderBy('avg_mortality_rate', 'desc');

            $results = $query->get();

            logger()->info('[Analytics Debug] Mortality analysis raw results count: ' . $results->count());

            // Load coop and farm relations manually for each result
            $results->each(function ($item) {
                $coop = \App\Models\Coop::with('farm')->find($item->coop_id);
                if ($coop) {
                    $item->coop = $coop;
                    $item->farm = $coop->farm;
                } else {
                    // Create placeholder if coop not found
                    $item->coop = (object) ['id' => $item->coop_id, 'name' => 'Unknown Coop'];
                    $item->farm = (object) ['id' => null, 'name' => 'N/A'];
                }
            });

            logger()->info('[Analytics Debug] Mortality analysis processed results count: ' . $results->count());

            return $results;
        } catch (\Exception $e) {
            logger()->error('[Analytics Debug] Error in mortality analysis: ' . $e->getMessage(), [
                'filters' => $filters,
                'trace' => $e->getTraceAsString()
            ]);

            return collect();
        }
    }

    /**
     * Get sales analysis by coop
     */
    private function getSalesAnalysis(array $filters): Collection
    {
        return DailyAnalytics::query()
            ->when($filters['farm_id'] ?? null, fn($q, $farm) => $q->where('farm_id', $farm))
            ->when($filters['coop_id'] ?? null, fn($q, $coop) => $q->where('coop_id', $coop))
            ->when($filters['livestock_id'] ?? null, fn($q, $livestock) => $q->where('livestock_id', $livestock))
            ->when($filters['date_from'] ?? null, fn($q, $date) => $q->where('date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn($q, $date) => $q->where('date', '<=', $date))
            ->with(['coop', 'farm'])
            ->select([
                'coop_id',
                DB::raw('SUM(sales_count) as total_sales'),
                DB::raw('SUM(sales_weight) as total_weight'),
                DB::raw('SUM(sales_revenue) as total_revenue'),
                DB::raw('AVG(sales_revenue/NULLIF(sales_count,0)) as avg_price_per_bird'),
                DB::raw('COUNT(*) as sales_days')
            ])
            ->groupBy('coop_id')
            ->orderBy('total_revenue', 'desc')
            ->get();
    }

    /**
     * Get production analysis by coop
     */
    private function getProductionAnalysis(array $filters): Collection
    {
        try {
            logger()->info('[Analytics Debug] Getting production analysis with filters', $filters);

            $query = DailyAnalytics::query()
                ->when($filters['farm_id'] ?? null, fn($q, $farm) => $q->where('farm_id', $farm))
                ->when($filters['coop_id'] ?? null, fn($q, $coop) => $q->where('coop_id', $coop))
                ->when($filters['livestock_id'] ?? null, fn($q, $livestock) => $q->where('livestock_id', $livestock))
                ->when($filters['date_from'] ?? null, fn($q, $date) => $q->where('date', '>=', $date))
                ->when($filters['date_to'] ?? null, fn($q, $date) => $q->where('date', '<=', $date))
                ->select([
                    'coop_id',
                    DB::raw('AVG(daily_weight_gain) as avg_daily_gain'),
                    DB::raw('AVG(fcr) as avg_fcr'),
                    DB::raw('AVG(production_index) as avg_production_index'),
                    DB::raw('AVG(efficiency_score) as avg_efficiency_score'),
                    DB::raw('COUNT(*) as days_recorded')
                ])
                ->groupBy('coop_id')
                ->orderBy('avg_efficiency_score', 'desc');

            $results = $query->get();

            logger()->info('[Analytics Debug] Production analysis raw results count: ' . $results->count());

            // Load coop and farm relations manually for each result
            $results->each(function ($item) {
                $coop = \App\Models\Coop::with('farm')->find($item->coop_id);
                if ($coop) {
                    $item->coop = $coop;
                    $item->farm = $coop->farm;
                } else {
                    // Create placeholder if coop not found
                    $item->coop = (object) ['id' => $item->coop_id, 'name' => 'Unknown Coop'];
                    $item->farm = (object) ['id' => null, 'name' => 'N/A'];
                }
            });

            logger()->info('[Analytics Debug] Production analysis processed results count: ' . $results->count());

            return $results;
        } catch (\Exception $e) {
            logger()->error('[Analytics Debug] Error in production analysis: ' . $e->getMessage(), [
                'filters' => $filters,
                'trace' => $e->getTraceAsString()
            ]);

            return collect();
        }
    }

    /**
     * Get coop performance rankings
     */
    private function getCoopPerformanceRankings(array $filters): Collection
    {
        try {
            logger()->info('[Analytics Debug] Getting coop performance rankings with filters', $filters);

            $query = DailyAnalytics::query()
                ->when($filters['farm_id'] ?? null, fn($q, $farm) => $q->where('farm_id', $farm))
                ->when($filters['livestock_id'] ?? null, fn($q, $livestock) => $q->where('livestock_id', $livestock))
                ->when($filters['date_from'] ?? null, fn($q, $date) => $q->where('date', '>=', $date))
                ->when($filters['date_to'] ?? null, fn($q, $date) => $q->where('date', '<=', $date))
                ->select([
                    'coop_id',
                    DB::raw('AVG(efficiency_score) as overall_score'),
                    DB::raw('AVG(mortality_rate) as avg_mortality'),
                    DB::raw('AVG(fcr) as avg_fcr'),
                    DB::raw('SUM(sales_revenue) as total_revenue'),
                    DB::raw('COUNT(*) as days_active')
                ])
                ->groupBy('coop_id')
                ->orderBy('overall_score', 'desc');
            // Remove limit to show all coops, not just top 20

            $results = $query->get();

            logger()->info('[Analytics Debug] Coop rankings raw results count: ' . $results->count());

            // Load coop and farm relations manually for each result
            $results->each(function ($item) {
                $coop = \App\Models\Coop::with('farm')->find($item->coop_id);
                if ($coop) {
                    $item->coop = $coop;
                    $item->farm = $coop->farm;
                } else {
                    // Create placeholder if coop not found
                    $item->coop = (object) ['id' => $item->coop_id, 'name' => 'Unknown Coop'];
                    $item->farm = (object) ['id' => null, 'name' => 'N/A'];
                }
            });

            logger()->info('[Analytics Debug] Coop rankings processed results count: ' . $results->count());

            return $results;
        } catch (\Exception $e) {
            logger()->error('[Analytics Debug] Error in coop rankings: ' . $e->getMessage(), [
                'filters' => $filters,
                'trace' => $e->getTraceAsString()
            ]);

            return collect();
        }
    }

    /**
     * Get active alerts
     */
    private function getActiveAlerts(array $filters): Collection
    {
        return AnalyticsAlert::query()
            ->when($filters['farm_id'] ?? null, fn($q, $farm) => $q->where('farm_id', $farm))
            ->when($filters['coop_id'] ?? null, fn($q, $coop) => $q->where('coop_id', $coop))
            ->when($filters['livestock_id'] ?? null, fn($q, $livestock) => $q->where('livestock_id', $livestock))
            ->where('is_resolved', false)
            ->with(['coop', 'farm', 'livestock'])
            ->orderBy('severity')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get trend analysis
     */
    private function getTrendAnalysis(array $filters): array
    {
        try {
            $days = 30; // Last 30 days
            $startDate = Carbon::now()->subDays($days);

            $trends = DailyAnalytics::query()
                ->when($filters['farm_id'] ?? null, fn($q, $farm) => $q->where('farm_id', $farm))
                ->when($filters['coop_id'] ?? null, fn($q, $coop) => $q->where('coop_id', $coop))
                ->where('date', '>=', $startDate)
                ->select([
                    'date',
                    DB::raw('AVG(mortality_rate) as avg_mortality'),
                    DB::raw('AVG(efficiency_score) as avg_efficiency'),
                    DB::raw('AVG(fcr) as avg_fcr'),
                    DB::raw('SUM(sales_revenue) as daily_revenue')
                ])
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            return [
                'mortality_trend' => $trends->pluck('avg_mortality', 'date')->toArray(),
                'efficiency_trend' => $trends->pluck('avg_efficiency', 'date')->toArray(),
                'fcr_trend' => $trends->pluck('avg_fcr', 'date')->toArray(),
                'revenue_trend' => $trends->pluck('daily_revenue', 'date')->toArray(),
            ];
        } catch (\Exception $e) {
            logger()->error("Failed to get trend analysis: " . $e->getMessage(), [
                'filters' => $filters,
                'exception' => $e
            ]);

            // Return safe default structure
            return [
                'mortality_trend' => [],
                'efficiency_trend' => [],
                'fcr_trend' => [],
                'revenue_trend' => [],
            ];
        }
    }

    // Helper methods
    private function buildAnalyticsQuery(array $filters)
    {
        return DailyAnalytics::query()
            ->when($filters['farm_id'] ?? null, fn($q, $farm) => $q->where('farm_id', $farm))
            ->when($filters['coop_id'] ?? null, fn($q, $coop) => $q->where('coop_id', $coop))
            ->when($filters['livestock_id'] ?? null, fn($q, $livestock) => $q->where('livestock_id', $livestock))
            ->when($filters['date_from'] ?? null, fn($q, $date) => $q->where('date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn($q, $date) => $q->where('date', '<=', $date));
    }

    private function getCurrentPopulation(Livestock $livestock, Carbon $date): int
    {
        // Calculate current population = initial_quantity - depletion - sales - mutation
        $initialQuantity = $livestock->initial_quantity ?? 0;

        // Get total depletion up to the date
        $totalDepletion = LivestockDepletion::where('livestock_id', $livestock->id)
            ->whereDate('tanggal', '<=', $date)
            ->sum('jumlah');

        // Get total sales up to the date from LivestockSalesItem
        $totalSales = \App\Models\LivestockSalesItem::where('livestock_id', $livestock->id)
            ->whereDate('tanggal', '<=', $date)
            ->sum('jumlah') ?? 0;

        // Calculate current population
        $currentPopulation = $initialQuantity - $totalDepletion - $totalSales;

        return max(0, $currentPopulation); // Ensure non-negative
    }

    private function getCumulativeMortality(Livestock $livestock, Carbon $date): int
    {
        // Use config normalization for backward compatibility
        $mortalityTypes = [
            LivestockDepletionConfig::LEGACY_TYPE_MATI,
            LivestockDepletionConfig::TYPE_MORTALITY
        ];

        return LivestockDepletion::where('livestock_id', $livestock->id)
            ->whereIn('jenis', $mortalityTypes)
            ->whereDate('tanggal', '<=', $date)
            ->sum('jumlah');
    }

    private function getAverageWeight(Livestock $livestock, Carbon $date): float
    {
        $recording = Recording::where('livestock_id', $livestock->id)
            ->whereDate('tanggal', '<=', $date)
            ->latest('tanggal')
            ->first();

        // Try different weight fields from Recording model
        if ($recording) {
            return $recording->average_weight ?? $recording->weight ?? $recording->berat_hari_ini ?? 0;
        }

        return $livestock->initial_weight ?? 40; // Default DOC weight
    }

    private function getDailyWeightGain(Livestock $livestock, Carbon $date): float
    {
        $currentWeight = $this->getAverageWeight($livestock, $date);
        $previousWeight = $this->getAverageWeight($livestock, $date->copy()->subDay());

        return max(0, $currentWeight - $previousWeight);
    }

    private function getAgeDays(Livestock $livestock, Carbon $date): int
    {
        return $livestock->start_date->diffInDays($date);
    }

    /**
     * Get mortality chart data with JSON/array format (refactored for simplicity)
     */
    public function getMortalityChartData(array $filters): array
    {
        try {
            logger()->info('[AnalyticsService] Starting getMortalityChartData (refactored)', [
                'filters' => $filters
            ]);

            $dateFrom = $filters['date_from'] ?? Carbon::now()->subDays(30);
            $dateTo = $filters['date_to'] ?? Carbon::now();
            $chartType = $filters['chart_type'] ?? 'auto';
            $viewType = $filters['view_type'] ?? 'livestock';

            // Build mortality data query with config normalization
            $mortalityTypes = [
                LivestockDepletionConfig::LEGACY_TYPE_MATI,
                LivestockDepletionConfig::TYPE_MORTALITY
            ];

            $mortalityQuery = LivestockDepletion::whereIn('jenis', $mortalityTypes)
                ->whereBetween('tanggal', [$dateFrom, $dateTo]);

            // Apply filters
            if ($filters['farm_id'] ?? null) {
                $mortalityQuery->whereHas('livestock', function ($q) use ($filters) {
                    $q->where('farm_id', $filters['farm_id']);
                });
            }

            if ($filters['coop_id'] ?? null) {
                $mortalityQuery->whereHas('livestock', function ($q) use ($filters) {
                    $q->where('coop_id', $filters['coop_id']);
                });
            }

            if ($filters['livestock_id'] ?? null) {
                $mortalityQuery->where('livestock_id', $filters['livestock_id']);
            }

            // Get raw mortality data in JSON array format
            $mortalityData = $mortalityQuery->with(['livestock.farm', 'livestock.coop'])
                ->get(['id', 'livestock_id', 'tanggal', 'jumlah', 'jenis'])
                ->map(function ($record) {
                    return [
                        'date' => $record->tanggal->format('Y-m-d'),
                        'farm' => $record->livestock->farm->name ?? 'N/A',
                        'coop' => $record->livestock->coop->name ?? 'N/A',
                        'livestock' => $record->livestock->name ?? 'N/A',
                        'deaths' => (int) $record->jumlah,
                    ];
                })
                ->toArray();

            logger()->info('[AnalyticsService] Raw mortality data count: ' . count($mortalityData));

            // Process data for chart based on view type and filters
            if ($filters['livestock_id'] ?? null) {
                return $this->buildSingleLivestockChart($mortalityData, $dateFrom, $dateTo, $viewType);
            } elseif ($filters['coop_id'] ?? null) {
                return $this->buildSingleCoopChart($mortalityData, $dateFrom, $dateTo, $viewType);
            } elseif ($filters['farm_id'] ?? null) {
                return $this->buildSingleFarmChart($mortalityData, $dateFrom, $dateTo);
            } else {
                return $this->buildAllFarmsChart($mortalityData, $dateFrom, $dateTo);
            }
        } catch (\Exception $e) {
            logger()->error('[AnalyticsService] Failed to get mortality chart data (refactored)', [
                'filters' => $filters,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'type' => 'line',
                'title' => 'Chart Error',
                'labels' => [],
                'datasets' => [],
                'options' => [
                    'responsive' => true,
                    'plugins' => [
                        'title' => [
                            'display' => true,
                            'text' => 'Unable to load mortality chart data: ' . $e->getMessage()
                        ]
                    ]
                ]
            ];
        }
    }

    /**
     * Build chart for single livestock (line chart with daily deaths)
     */
    private function buildSingleLivestockChart(array $mortalityData, $dateFrom, $dateTo, $viewType): array
    {
        $livestockName = $mortalityData[0]['livestock'] ?? 'Unknown Livestock';

        // Group by date and sum deaths
        $dailyDeaths = [];
        foreach ($mortalityData as $item) {
            $date = $item['date'];
            $dailyDeaths[$date] = ($dailyDeaths[$date] ?? 0) + $item['deaths'];
        }

        // Generate complete date range
        $labels = [];
        $deathsData = [];
        $dateRange = Carbon::parse($dateFrom)->daysUntil(Carbon::parse($dateTo));

        foreach ($dateRange as $date) {
            $dateStr = $date->format('Y-m-d');
            $labels[] = $date->format('M d');
            $deathsData[] = $dailyDeaths[$dateStr] ?? 0;
        }

        return [
            'type' => 'line',
            'title' => "Daily Mortality - $livestockName",
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Daily Deaths',
                    'data' => $deathsData,
                    'borderColor' => 'rgba(239, 68, 68, 1)',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.2)',
                    'borderWidth' => 3,
                    'fill' => false,
                    'tension' => 0.4,
                    'pointBackgroundColor' => 'rgba(239, 68, 68, 1)',
                    'pointBorderColor' => '#fff',
                    'pointBorderWidth' => 2,
                    'pointRadius' => 6,
                    'pointHoverRadius' => 8
                ]
            ],
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'title' => [
                        'display' => true,
                        'text' => "Daily Mortality Trend - $livestockName",
                        'font' => [
                            'size' => 18,
                            'weight' => 'bold'
                        ],
                        'color' => '#333',
                        'padding' => 20
                    ],
                    'legend' => [
                        'display' => true,
                        'position' => 'top',
                        'labels' => [
                            'usePointStyle' => true,
                            'padding' => 20,
                            'font' => [
                                'size' => 14
                            ]
                        ]
                    ],
                    'tooltip' => [
                        'backgroundColor' => 'rgba(0, 0, 0, 0.8)',
                        'titleColor' => '#fff',
                        'bodyColor' => '#fff',
                        'borderColor' => '#fff',
                        'borderWidth' => 1,
                        'cornerRadius' => 10,
                        'displayColors' => true
                    ]
                ],
                'scales' => [
                    'x' => [
                        'display' => true,
                        'title' => [
                            'display' => true,
                            'text' => 'Date',
                            'font' => [
                                'size' => 14,
                                'weight' => 'bold'
                            ]
                        ],
                        'grid' => [
                            'color' => 'rgba(0, 0, 0, 0.1)'
                        ]
                    ],
                    'y' => [
                        'display' => true,
                        'title' => [
                            'display' => true,
                            'text' => 'Deaths Count',
                            'font' => [
                                'size' => 14,
                                'weight' => 'bold'
                            ]
                        ],
                        'beginAtZero' => true,
                        'grid' => [
                            'color' => 'rgba(0, 0, 0, 0.1)'
                        ],
                        'ticks' => [
                            'stepSize' => 1
                        ]
                    ]
                ],
                'interaction' => [
                    'intersect' => false,
                    'mode' => 'index'
                ],
                'animation' => [
                    'duration' => 1000,
                    'easing' => 'easeInOutQuart'
                ]
            ]
        ];
    }

    /**
     * Build chart for single coop (line chart by farm within coop)
     */
    private function buildSingleCoopChart(array $mortalityData, $dateFrom, $dateTo, $viewType): array
    {
        $coopName = $mortalityData[0]['coop'] ?? 'Unknown Coop';

        if ($viewType === 'daily') {
            // Daily aggregate view
            $dailyDeaths = [];
            foreach ($mortalityData as $item) {
                $date = $item['date'];
                $dailyDeaths[$date] = ($dailyDeaths[$date] ?? 0) + $item['deaths'];
            }

            $labels = [];
            $deathsData = [];
            $dateRange = Carbon::parse($dateFrom)->daysUntil(Carbon::parse($dateTo));

            foreach ($dateRange as $date) {
                $dateStr = $date->format('Y-m-d');
                $labels[] = $date->format('M d');
                $deathsData[] = $dailyDeaths[$dateStr] ?? 0;
            }

            return [
                'type' => 'line',
                'title' => "Daily Mortality - $coopName",
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => 'Daily Deaths',
                        'data' => $deathsData,
                        'borderColor' => 'rgba(239, 68, 68, 1)',
                        'backgroundColor' => 'rgba(239, 68, 68, 0.2)',
                        'borderWidth' => 3,
                        'fill' => false,
                        'tension' => 0.4,
                        'pointBackgroundColor' => 'rgba(239, 68, 68, 1)',
                        'pointBorderColor' => '#fff',
                        'pointBorderWidth' => 2,
                        'pointRadius' => 6,
                        'pointHoverRadius' => 8
                    ]
                ],
                'options' => $this->getLineChartOptions("Daily Mortality - $coopName", 'Date', 'Deaths Count')
            ];
        } else {
            // Per livestock view
            $livestockGroups = [];
            foreach ($mortalityData as $item) {
                $livestock = $item['livestock'];
                if (!isset($livestockGroups[$livestock])) {
                    $livestockGroups[$livestock] = [];
                }
                $date = $item['date'];
                $livestockGroups[$livestock][$date] = ($livestockGroups[$livestock][$date] ?? 0) + $item['deaths'];
            }

            $labels = [];
            $dateRange = Carbon::parse($dateFrom)->daysUntil(Carbon::parse($dateTo));
            foreach ($dateRange as $date) {
                $labels[] = $date->format('M d');
            }

            $datasets = [];
            $colors = ['#EF4444', '#3B82F6', '#10B981', '#F59E0B', '#8B5CF6', '#06B6D4'];
            $colorIndex = 0;

            foreach ($livestockGroups as $livestock => $dailyData) {
                $data = [];
                $dateRange = Carbon::parse($dateFrom)->daysUntil(Carbon::parse($dateTo));
                foreach ($dateRange as $date) {
                    $dateStr = $date->format('Y-m-d');
                    $data[] = $dailyData[$dateStr] ?? 0;
                }

                $color = $colors[$colorIndex % count($colors)];
                $datasets[] = [
                    'label' => substr($livestock, 0, 20) . (strlen($livestock) > 20 ? '...' : ''),
                    'data' => $data,
                    'borderColor' => $color,
                    'backgroundColor' => $color . '20',
                    'borderWidth' => 3,
                    'fill' => false,
                    'tension' => 0.4,
                    'pointBackgroundColor' => $color,
                    'pointBorderColor' => '#fff',
                    'pointBorderWidth' => 2,
                    'pointRadius' => 6,
                    'pointHoverRadius' => 8
                ];
                $colorIndex++;
            }

            return [
                'type' => 'line',
                'title' => "Livestock Mortality Comparison - $coopName",
                'labels' => $labels,
                'datasets' => $datasets,
                'options' => $this->getLineChartOptions("Livestock Mortality Comparison - $coopName", 'Date', 'Deaths Count')
            ];
        }
    }

    /**
     * Build chart for single farm (line chart by coop)
     */
    private function buildSingleFarmChart(array $mortalityData, $dateFrom, $dateTo): array
    {
        $farmName = $mortalityData[0]['farm'] ?? 'Unknown Farm';

        // Group by coop and date
        $coopGroups = [];
        foreach ($mortalityData as $item) {
            $coop = $item['coop'];
            if (!isset($coopGroups[$coop])) {
                $coopGroups[$coop] = [];
            }
            $date = $item['date'];
            $coopGroups[$coop][$date] = ($coopGroups[$coop][$date] ?? 0) + $item['deaths'];
        }

        $labels = [];
        $dateRange = Carbon::parse($dateFrom)->daysUntil(Carbon::parse($dateTo));
        foreach ($dateRange as $date) {
            $labels[] = $date->format('M d');
        }

        $datasets = [];
        $colors = ['#EF4444', '#3B82F6', '#10B981', '#F59E0B', '#8B5CF6', '#06B6D4'];
        $colorIndex = 0;

        foreach ($coopGroups as $coop => $dailyData) {
            $data = [];
            $dateRange = Carbon::parse($dateFrom)->daysUntil(Carbon::parse($dateTo));
            foreach ($dateRange as $date) {
                $dateStr = $date->format('Y-m-d');
                $data[] = $dailyData[$dateStr] ?? 0;
            }

            $color = $colors[$colorIndex % count($colors)];
            $datasets[] = [
                'label' => $coop,
                'data' => $data,
                'borderColor' => $color,
                'backgroundColor' => $color . '20',
                'borderWidth' => 3,
                'fill' => false,
                'tension' => 0.4,
                'pointBackgroundColor' => $color,
                'pointBorderColor' => '#fff',
                'pointBorderWidth' => 2,
                'pointRadius' => 6,
                'pointHoverRadius' => 8
            ];
            $colorIndex++;
        }

        return [
            'type' => 'line',
            'title' => "Coop Mortality Comparison - $farmName",
            'labels' => $labels,
            'datasets' => $datasets,
            'options' => $this->getLineChartOptions("Coop Mortality Comparison - $farmName", 'Date', 'Deaths Count')
        ];
    }

    /**
     * Build chart for all farms (line chart by farm)
     */
    private function buildAllFarmsChart(array $mortalityData, $dateFrom, $dateTo): array
    {
        // Group by farm and date
        $farmGroups = [];
        foreach ($mortalityData as $item) {
            $farm = $item['farm'];
            if (!isset($farmGroups[$farm])) {
                $farmGroups[$farm] = [];
            }
            $date = $item['date'];
            $farmGroups[$farm][$date] = ($farmGroups[$farm][$date] ?? 0) + $item['deaths'];
        }

        $labels = [];
        $dateRange = Carbon::parse($dateFrom)->daysUntil(Carbon::parse($dateTo));
        foreach ($dateRange as $date) {
            $labels[] = $date->format('M d');
        }

        $datasets = [];
        $colors = ['#EF4444', '#3B82F6', '#10B981', '#F59E0B', '#8B5CF6', '#06B6D4'];
        $colorIndex = 0;

        foreach ($farmGroups as $farm => $dailyData) {
            $data = [];
            $dateRange = Carbon::parse($dateFrom)->daysUntil(Carbon::parse($dateTo));
            foreach ($dateRange as $date) {
                $dateStr = $date->format('Y-m-d');
                $data[] = $dailyData[$dateStr] ?? 0;
            }

            $color = $colors[$colorIndex % count($colors)];
            $datasets[] = [
                'label' => $farm,
                'data' => $data,
                'borderColor' => $color,
                'backgroundColor' => $color . '20',
                'borderWidth' => 3,
                'fill' => false,
                'tension' => 0.4,
                'pointBackgroundColor' => $color,
                'pointBorderColor' => '#fff',
                'pointBorderWidth' => 2,
                'pointRadius' => 6,
                'pointHoverRadius' => 8
            ];
            $colorIndex++;
        }

        return [
            'type' => 'line',
            'title' => 'Farm Mortality Comparison',
            'labels' => $labels,
            'datasets' => $datasets,
            'options' => $this->getLineChartOptions('Farm Mortality Comparison', 'Date', 'Deaths Count')
        ];
    }

    /**
     * Get standardized line chart options similar to chart.blade.php
     */
    private function getLineChartOptions($title, $xAxisTitle, $yAxisTitle): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'title' => [
                    'display' => true,
                    'text' => $title,
                    'font' => [
                        'size' => 18,
                        'weight' => 'bold'
                    ],
                    'color' => '#333',
                    'padding' => 20
                ],
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                    'labels' => [
                        'usePointStyle' => true,
                        'padding' => 20,
                        'font' => [
                            'size' => 14
                        ]
                    ]
                ],
                'tooltip' => [
                    'backgroundColor' => 'rgba(0, 0, 0, 0.8)',
                    'titleColor' => '#fff',
                    'bodyColor' => '#fff',
                    'borderColor' => '#fff',
                    'borderWidth' => 1,
                    'cornerRadius' => 10,
                    'displayColors' => true,
                    'callbacks' => [
                        'title' => "function(tooltipItems) { return 'Date: ' + tooltipItems[0].label; }",
                        'label' => "function(context) { return context.dataset.label + ': ' + context.parsed.y + ' deaths'; }"
                    ]
                ]
            ],
            'scales' => [
                'x' => [
                    'display' => true,
                    'title' => [
                        'display' => true,
                        'text' => $xAxisTitle,
                        'font' => [
                            'size' => 14,
                            'weight' => 'bold'
                        ]
                    ],
                    'grid' => [
                        'color' => 'rgba(0, 0, 0, 0.1)'
                    ]
                ],
                'y' => [
                    'display' => true,
                    'title' => [
                        'display' => true,
                        'text' => $yAxisTitle,
                        'font' => [
                            'size' => 14,
                            'weight' => 'bold'
                        ]
                    ],
                    'beginAtZero' => true,
                    'grid' => [
                        'color' => 'rgba(0, 0, 0, 0.1)'
                    ],
                    'ticks' => [
                        'stepSize' => 1
                    ]
                ]
            ],
            'interaction' => [
                'intersect' => false,
                'mode' => 'index'
            ],
            'animation' => [
                'duration' => 1000,
                'easing' => 'easeInOutQuart'
            ]
        ];
    }
}

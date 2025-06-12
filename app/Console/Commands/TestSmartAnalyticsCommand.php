<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AnalyticsService;
use App\Models\DailyAnalytics;
use App\Models\Farm;
use App\Models\Coop;
use App\Models\Livestock;
use Carbon\Carbon;

class TestSmartAnalyticsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'analytics:test
                            {--farm= : Test specific farm ID}
                            {--coop= : Test specific coop ID}
                            {--livestock= : Test specific livestock ID}
                            {--days= : Number of days to analyze (default: all data)}
                            {--date-from= : Start date (YYYY-MM-DD)}
                            {--date-to= : End date (YYYY-MM-DD)}
                            {--calculate : Calculate daily analytics before testing}
                            {--detailed : Show detailed output}
                            {--list-livestock : Show available livestock and exit}
                            {--only-mortality : Test only mortality analysis}
                            {--only-production : Test only production analysis}
                            {--only-rankings : Test only rankings analysis}
                            {--only-sales : Test only sales analysis}
                            {--only-alerts : Test only alerts analysis}
                            {--only-overview : Test only overview data}
                            {--only-trends : Test only trends data}';

    /**
     * The console command description.
     */
    protected $description = 'Test comprehensive Smart Analytics data retrieval and calculations';

    private $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        parent::__construct();
        $this->analyticsService = $analyticsService;
    }

    public function handle()
    {
        $startTime = microtime(true);

        // Handle list-livestock option
        if ($this->option('list-livestock')) {
            $this->listAvailableLivestock();
            return;
        }

        $this->info("🚀 Smart Analytics Comprehensive Test");
        $this->info("=====================================");
        $this->newLine();

        // Show testing scope
        $this->showTestingScope();

        // Step 1: Database overview
        $this->testDatabaseOverview();

        // Step 2: Calculate daily analytics if requested
        if ($this->option('calculate')) {
            $this->calculateDailyAnalytics();
        }

        // Step 3: Test with different filter scenarios
        $this->testFilterScenarios();

        // Step 4: Performance tests (only if no selective testing)
        if (!$this->hasSelectiveOptions()) {
            $this->testPerformance();
        }

        // Step 5: Data integrity checks (only if no selective testing)
        if (!$this->hasSelectiveOptions()) {
            $this->testDataIntegrity();
        }

        $executionTime = round((microtime(true) - $startTime) * 1000, 2);
        $this->newLine();
        $this->info("✅ Test completed in {$executionTime}ms");
    }

    private function testDatabaseOverview()
    {
        $this->info("📊 Database Overview");
        $this->info("--------------------");

        $farmCount = Farm::count();
        $coopCount = Coop::count();
        $livestockCount = Livestock::count();
        $analyticsCount = DailyAnalytics::count();

        $this->line("• Farms: {$farmCount}");
        $this->line("• Coops: {$coopCount}");
        $this->line("• Livestock: {$livestockCount}");
        $this->line("• Daily Analytics Records: {$analyticsCount}");

        if ($analyticsCount > 0) {
            $dateRange = DailyAnalytics::selectRaw('MIN(date) as min_date, MAX(date) as max_date')->first();
            $this->line("• Date Range: {$dateRange->min_date} to {$dateRange->max_date}");

            $coopsWithData = DailyAnalytics::distinct('coop_id')->count('coop_id');
            $livestockWithData = DailyAnalytics::distinct('livestock_id')->count('livestock_id');
            $this->line("• Coops with Analytics Data: {$coopsWithData}");
            $this->line("• Livestock with Analytics Data: {$livestockWithData}");
        }

        // Show selected filters if any
        if ($this->option('farm') || $this->option('coop') || $this->option('livestock')) {
            $this->line("📋 Selected Filters:");

            if ($this->option('farm')) {
                $farm = Farm::find($this->option('farm'));
                $this->line("  • Farm: " . ($farm ? $farm->name . " (ID: {$farm->id})" : 'Not found'));
            }

            if ($this->option('coop')) {
                $coop = Coop::with('farm')->find($this->option('coop'));
                $this->line("  • Coop: " . ($coop ? $coop->name . " (Farm: {$coop->farm->name})" : 'Not found'));
            }

            if ($this->option('livestock')) {
                $livestock = Livestock::with(['farm', 'coop'])->find($this->option('livestock'));
                if ($livestock) {
                    $this->line("  • Livestock: {$livestock->name}");
                    $this->line("    - Farm: " . ($livestock->farm->name ?? 'N/A'));
                    $this->line("    - Coop: " . ($livestock->coop->name ?? 'N/A'));
                    $this->line("    - Status: {$livestock->status}");
                    $this->line("    - Start Date: {$livestock->start_date}");
                    $this->line("    - Population: " . ($livestock->initial_quantity ?? 'N/A'));
                } else {
                    $this->line("  • Livestock: Not found");
                }
            }
        }

        $this->newLine();
    }

    private function calculateDailyAnalytics()
    {
        $this->info("⚙️  Calculating Daily Analytics");
        $this->info("------------------------------");

        try {
            $startTime = microtime(true);
            $result = $this->analyticsService->calculateDailyAnalytics();
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            $this->line("✅ Daily analytics calculated successfully");
            $this->line("⏱️  Execution time: {$executionTime}ms");

            if (is_array($result)) {
                $this->line("📈 Analytics created: " . ($result['analytics_created'] ?? 'N/A'));
                $this->line("🚨 Alerts generated: " . ($result['alerts_generated'] ?? 'N/A'));
            }
        } catch (\Exception $e) {
            $this->error("❌ Failed to calculate analytics: " . $e->getMessage());
        }

        $this->newLine();
    }

    private function testFilterScenarios()
    {
        $this->info("🔍 Testing Filter Scenarios");
        $this->info("----------------------------");

        // Get available data for realistic testing
        $availableDates = DailyAnalytics::selectRaw('MIN(date) as min_date, MAX(date) as max_date')->first();

        // Calculate date range based on options
        $dateFrom = $this->calculateDateFrom($availableDates);
        $dateTo = $this->calculateDateTo($availableDates);

        // Show calculated date range
        $this->line("📅 Calculated Date Range: {$dateFrom} to {$dateTo}");
        if ($this->option('days')) {
            $this->line("📊 Analyzing last {$this->option('days')} days of data");
        }
        $this->newLine();

        $scenarios = [];

        // Add custom scenario if specific options are provided
        if ($this->option('farm') || $this->option('coop') || $this->option('livestock')) {
            $scenarios['Custom Filters'] = [
                'farm_id' => $this->option('farm'),
                'coop_id' => $this->option('coop'),
                'livestock_id' => $this->option('livestock'),
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ];
        } else {
            // Add default scenarios only if no specific filters
            $scenarios = [
                'All Data' => [
                    'farm_id' => null,
                    'coop_id' => null,
                    'livestock_id' => null,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                ],
                'Single Farm' => [
                    'farm_id' => Farm::first()->id ?? null,
                    'coop_id' => null,
                    'livestock_id' => null,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                ],
                'Single Coop' => [
                    'farm_id' => null,
                    'coop_id' => Coop::first()->id ?? null,
                    'livestock_id' => null,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                ],
                'Single Livestock' => [
                    'farm_id' => null,
                    'coop_id' => null,
                    'livestock_id' => Livestock::first()->id ?? null,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                ],
                'Recent Week' => [
                    'farm_id' => null,
                    'coop_id' => null,
                    'livestock_id' => null,
                    'date_from' => Carbon::parse($dateTo)->subDays(7)->format('Y-m-d'),
                    'date_to' => $dateTo,
                ],
            ];
        }

        foreach ($scenarios as $scenarioName => $filters) {
            $this->testSingleScenario($scenarioName, $filters);
        }
    }

    private function calculateDateFrom($availableDates)
    {
        // Priority: explicit date-from > days option > all available data
        if ($this->option('date-from')) {
            return $this->option('date-from');
        }

        if ($this->option('days')) {
            $days = (int) $this->option('days');
            $maxDate = $this->option('date-to') ?? $availableDates->max_date;
            return Carbon::parse($maxDate)->subDays($days - 1)->format('Y-m-d');
        }

        return $availableDates->min_date;
    }

    private function calculateDateTo($availableDates)
    {
        // Priority: explicit date-to > all available data
        if ($this->option('date-to')) {
            return $this->option('date-to');
        }

        return $availableDates->max_date;
    }

    private function testSingleScenario($scenarioName, $filters)
    {
        $this->line("🧪 Testing: {$scenarioName}");

        try {
            $startTime = microtime(true);
            $insights = $this->analyticsService->getSmartInsights($filters);
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            // Test specific components based on selective options
            if ($this->option('only-overview') || !$this->hasSelectiveOptions()) {
                $this->testOverviewData($insights);
            }

            if ($this->option('only-mortality') || !$this->hasSelectiveOptions()) {
                $this->testMortalityData($insights);
            }

            if ($this->option('only-production') || !$this->hasSelectiveOptions()) {
                $this->testProductionData($insights);
            }

            if ($this->option('only-rankings') || !$this->hasSelectiveOptions()) {
                $this->testRankingsData($insights);
            }

            if ($this->option('only-sales') || !$this->hasSelectiveOptions()) {
                $this->testSalesData($insights);
            }

            if ($this->option('only-alerts') || !$this->hasSelectiveOptions()) {
                $this->testAlertsData($insights);
            }

            if ($this->option('only-trends') || !$this->hasSelectiveOptions()) {
                $this->testTrendsData($insights);
            }

            $this->line("  ⏱️  Execution time: {$executionTime}ms");

            // Detailed output if requested
            if ($this->option('detailed')) {
                $this->showDetailedResults($insights);
            }

            $this->info("  ✅ {$scenarioName} - SUCCESS");
        } catch (\Exception $e) {
            $this->error("  ❌ {$scenarioName} - FAILED: " . $e->getMessage());
            if ($this->option('detailed')) {
                $this->line("  📝 Stack trace:");
                $this->line("    " . str_replace("\n", "\n    ", $e->getTraceAsString()));
            }
        }

        $this->newLine();
    }

    private function testOverviewData($insights)
    {
        $overview = $insights['overview'] ?? [];
        $this->line("  📊 Overview:");
        $this->line("    • Total Livestock: " . ($overview['total_livestock'] ?? 0));
        $this->line("    • Avg Mortality Rate: " . round(($overview['avg_mortality_rate'] ?? 0) * 100, 4) . "%");
        $this->line("    • Avg Efficiency Score: " . round($overview['avg_efficiency_score'] ?? 0, 2));
        $this->line("    • Avg FCR: " . round($overview['avg_fcr'] ?? 0, 2));
        $this->line("    • Total Revenue: Rp " . number_format($overview['total_revenue'] ?? 0));
    }

    private function testMortalityData($insights)
    {
        $mortalityCount = count($insights['mortality_analysis'] ?? []);
        $this->line("  🪦 Mortality Analysis: {$mortalityCount} records");
    }

    private function testProductionData($insights)
    {
        $productionCount = count($insights['production_analysis'] ?? []);
        $this->line("  🏭 Production Analysis: {$productionCount} records");
    }

    private function testRankingsData($insights)
    {
        $rankingsCount = count($insights['coop_rankings'] ?? []);
        $this->line("  🏆 Coop Rankings: {$rankingsCount} records");
    }

    private function testSalesData($insights)
    {
        $salesCount = count($insights['sales_analysis'] ?? []);
        $this->line("  💰 Sales Analysis: {$salesCount} records");
    }

    private function testAlertsData($insights)
    {
        $alertsCount = count($insights['alerts'] ?? []);
        $this->line("  🚨 Alerts: {$alertsCount} active");
    }

    private function testTrendsData($insights)
    {
        $trends = $insights['trends'] ?? [];
        $this->line("  📊 Trends Data:");
        $this->line("    • Mortality Trend: " . count($trends['mortality_trend'] ?? []) . " points");
        $this->line("    • Efficiency Trend: " . count($trends['efficiency_trend'] ?? []) . " points");
        $this->line("    • FCR Trend: " . count($trends['fcr_trend'] ?? []) . " points");
        $this->line("    • Revenue Trend: " . count($trends['revenue_trend'] ?? []) . " points");
    }

    private function showDetailedResults($insights)
    {
        $this->line("  📋 Detailed Results:");

        // Show detailed mortality analysis
        if (!empty($insights['mortality_analysis'])) {
            $mortality = collect($insights['mortality_analysis'])->take(3);
            $this->line("    🪦 Mortality Analysis (Top 3):");
            foreach ($mortality as $index => $item) {
                $coopName = $item->coop->name ?? 'Unknown';
                $farmName = $item->farm->name ?? 'Unknown';
                $mortalityRate = round($item->avg_mortality_rate, 4);
                $totalMortality = $item->total_mortality ?? 0;
                $avgPopulation = round($item->avg_population ?? 0);
                $daysRecorded = $item->days_recorded ?? 0;

                $this->line("      " . ($index + 1) . ". {$coopName} ({$farmName})");
                $this->line("         - Mortality Rate: {$mortalityRate}%");
                $this->line("         - Total Deaths: {$totalMortality} birds");
                $this->line("         - Avg Population: {$avgPopulation} birds");
                $this->line("         - Days Recorded: {$daysRecorded} days");
            }
        }

        // Show detailed production analysis
        if (!empty($insights['production_analysis'])) {
            $production = collect($insights['production_analysis'])->take(3);
            $this->line("    🏭 Production Analysis (Top 3):");
            foreach ($production as $index => $item) {
                $coopName = $item->coop->name ?? 'Unknown';
                $farmName = $item->farm->name ?? 'Unknown';
                $efficiencyScore = round($item->avg_efficiency_score ?? 0, 2);
                $avgDailyGain = round($item->avg_daily_gain ?? 0, 2);
                $avgFcr = round($item->avg_fcr ?? 0, 3);
                $daysRecorded = $item->days_recorded ?? 0;

                $this->line("      " . ($index + 1) . ". {$coopName} ({$farmName})");
                $this->line("         - Efficiency Score: {$efficiencyScore}");
                $this->line("         - Avg Daily Gain: {$avgDailyGain}g/day");
                $this->line("         - Avg FCR: {$avgFcr}");
                $this->line("         - Days Recorded: {$daysRecorded} days");
            }
        }

        // Show detailed rankings
        if (!empty($insights['coop_rankings'])) {
            $rankings = collect($insights['coop_rankings'])->take(3);
            $this->line("    🏆 Coop Rankings (Top 3):");
            foreach ($rankings as $index => $item) {
                $coopName = $item->coop->name ?? 'Unknown';
                $farmName = $item->farm->name ?? 'Unknown';
                $overallScore = round($item->overall_score ?? 0, 2);
                $avgMortality = round($item->avg_mortality ?? 0, 4);
                $totalRevenue = number_format($item->total_revenue ?? 0);
                $daysActive = $item->days_active ?? 0;

                $this->line("      " . ($index + 1) . ". {$coopName} ({$farmName})");
                $this->line("         - Overall Score: {$overallScore}");
                $this->line("         - Mortality Rate: {$avgMortality}%");
                $this->line("         - Total Revenue: Rp {$totalRevenue}");
                $this->line("         - Days Active: {$daysActive} days");
            }
        }

        // Show recent daily data if available
        $this->showRecentDailyData();
    }

    private function showRecentDailyData()
    {
        try {
            // Determine how many records to show based on days option
            $recordsToShow = $this->option('days') ? min((int)$this->option('days'), 10) : 5;

            // Get recent daily analytics data for more detailed view
            $recentData = \App\Models\DailyAnalytics::with(['coop', 'farm', 'livestock'])
                ->when($this->option('livestock'), fn($q, $livestock) => $q->where('livestock_id', $livestock))
                ->when($this->option('farm'), fn($q, $farm) => $q->where('farm_id', $farm))
                ->when($this->option('coop'), fn($q, $coop) => $q->where('coop_id', $coop))
                ->when($this->option('days'), function ($query) {
                    $days = (int) $this->option('days');
                    $endDate = $this->option('date-to') ?? DailyAnalytics::max('date');
                    $startDate = Carbon::parse($endDate)->subDays($days - 1)->format('Y-m-d');
                    return $query->whereBetween('date', [$startDate, $endDate]);
                })
                ->orderBy('date', 'desc')
                ->take($recordsToShow)
                ->get();

            if ($recentData->isNotEmpty()) {
                $this->line("    📅 Recent Daily Data (Last {$recordsToShow} records):");
                foreach ($recentData as $index => $record) {
                    $date = $record->date->format('Y-m-d');
                    $coopName = $record->coop->name ?? 'Unknown';
                    $livestockName = $record->livestock->name ?? 'Unknown';
                    $mortalityCount = $record->mortality_count ?? 0;
                    $currentPopulation = $record->current_population ?? 0;
                    $efficiencyScore = round($record->efficiency_score ?? 0, 2);

                    $this->line("      " . ($index + 1) . ". {$date} - {$coopName}");
                    $this->line("         - Livestock: {$livestockName}");
                    $this->line("         - Deaths Today: {$mortalityCount} birds");
                    $this->line("         - Current Population: {$currentPopulation} birds");
                    $this->line("         - Efficiency: {$efficiencyScore}%");
                }
            }

            // Show mortality trend by date if livestock is specified
            if ($this->option('livestock')) {
                $this->showLivestockMortalityTrend();
            }
        } catch (\Exception $e) {
            $this->line("    ⚠️  Could not retrieve recent daily data: " . $e->getMessage());
        }
    }

    private function showLivestockMortalityTrend()
    {
        try {
            $livestockId = $this->option('livestock');
            $livestock = \App\Models\Livestock::find($livestockId);

            if (!$livestock) {
                return;
            }

            // Determine the number of days and date range for mortality trend
            $daysToShow = $this->option('days') ? (int) $this->option('days') : 10;
            $daysToShow = min($daysToShow, 30); // Cap at 30 days max for readability

            $mortalityQuery = \App\Models\DailyAnalytics::where('livestock_id', $livestockId)
                ->where('mortality_count', '>', 0);

            // Apply date range if days option is specified
            if ($this->option('days')) {
                $endDate = $this->option('date-to') ?? DailyAnalytics::where('livestock_id', $livestockId)->max('date');
                $startDate = Carbon::parse($endDate)->subDays($daysToShow - 1)->format('Y-m-d');
                $mortalityQuery->whereBetween('date', [$startDate, $endDate]);
            }

            // Get mortality trend for this livestock
            $mortalityTrend = $mortalityQuery
                ->orderBy('date', 'desc')
                ->take($daysToShow)
                ->get(['date', 'mortality_count', 'current_population', 'mortality_rate']);

            if ($mortalityTrend->isNotEmpty()) {
                $periodText = $this->option('days') ? "last {$daysToShow} days" : "last {$daysToShow} days with deaths";
                $this->line("    💀 Mortality Trend for {$livestock->name} ({$periodText}):");
                $totalDeaths = 0;
                foreach ($mortalityTrend as $index => $day) {
                    $date = $day->date->format('Y-m-d');
                    $deaths = $day->mortality_count;
                    $population = $day->current_population;
                    $rate = round($day->mortality_rate, 4);
                    $totalDeaths += $deaths;

                    $this->line("      " . ($index + 1) . ". {$date}: {$deaths} deaths ({$rate}%) - Pop: {$population}");
                }
                $this->line("         📊 Total Deaths in Period: {$totalDeaths} birds");

                // Show additional statistics if days option is used
                if ($this->option('days')) {
                    $avgDeathsPerDay = round($totalDeaths / $daysToShow, 2);
                    $this->line("         📈 Average Deaths per Day: {$avgDeathsPerDay} birds");
                }
            } else {
                $periodText = $this->option('days') ? "in the last {$daysToShow} days" : "in recent days";
                $this->line("    ✅ No mortality recorded for {$livestock->name} {$periodText}");
            }
        } catch (\Exception $e) {
            $this->line("    ⚠️  Could not retrieve mortality trend: " . $e->getMessage());
        }
    }

    private function testPerformance()
    {
        $this->info("⚡ Performance Tests");
        $this->info("-------------------");

        $filters = [
            'farm_id' => null,
            'coop_id' => null,
            'date_from' => $this->option('date-from') ?? DailyAnalytics::min('date'),
            'date_to' => $this->option('date-to') ?? DailyAnalytics::max('date'),
        ];

        $iterations = 5;
        $times = [];

        $this->line("Running {$iterations} iterations...");

        for ($i = 1; $i <= $iterations; $i++) {
            $startTime = microtime(true);
            $this->analyticsService->getSmartInsights($filters);
            $executionTime = (microtime(true) - $startTime) * 1000;
            $times[] = $executionTime;

            $this->line("  Iteration {$i}: " . round($executionTime, 2) . "ms");
        }

        $avgTime = round(array_sum($times) / count($times), 2);
        $minTime = round(min($times), 2);
        $maxTime = round(max($times), 2);

        $this->line("📊 Performance Summary:");
        $this->line("  • Average: {$avgTime}ms");
        $this->line("  • Minimum: {$minTime}ms");
        $this->line("  • Maximum: {$maxTime}ms");

        // Performance rating
        if ($avgTime < 100) {
            $this->info("  🚀 Excellent performance (< 100ms)");
        } elseif ($avgTime < 500) {
            $this->line("  ✅ Good performance (< 500ms)");
        } elseif ($avgTime < 1000) {
            $this->comment("  ⚠️  Acceptable performance (< 1s)");
        } else {
            $this->error("  ❌ Slow performance (> 1s)");
        }

        $this->newLine();
    }

    private function testDataIntegrity()
    {
        $this->info("🔍 Data Integrity Checks");
        $this->info("------------------------");

        $issues = [];

        // Check for coops without analytics data
        $coopsWithoutData = Coop::whereNotIn('id', DailyAnalytics::select('coop_id')->distinct())->count();
        if ($coopsWithoutData > 0) {
            $issues[] = "{$coopsWithoutData} coops have no analytics data";
        }

        // Check for orphaned analytics data
        $orphanedAnalytics = DailyAnalytics::whereNotIn('coop_id', Coop::select('id'))->count();
        if ($orphanedAnalytics > 0) {
            $issues[] = "{$orphanedAnalytics} analytics records reference non-existent coops";
        }

        // Check for missing farm relations
        $coopsWithoutFarms = Coop::whereNull('farm_id')->count();
        if ($coopsWithoutFarms > 0) {
            $issues[] = "{$coopsWithoutFarms} coops have no farm assignment";
        }

        // Check for data consistency
        $nullEfficiencyScores = DailyAnalytics::whereNull('efficiency_score')->count();
        if ($nullEfficiencyScores > 0) {
            $issues[] = "{$nullEfficiencyScores} analytics records have null efficiency scores";
        }

        if (empty($issues)) {
            $this->info("✅ No data integrity issues found");
        } else {
            $this->comment("⚠️  Data integrity issues found:");
            foreach ($issues as $issue) {
                $this->line("  • {$issue}");
            }
        }

        $this->newLine();
    }

    private function listAvailableLivestock()
    {
        $this->info("🐄 Available Livestock");
        $this->info("-------------------");

        $livestock = Livestock::all();

        if ($livestock->isEmpty()) {
            $this->line("No livestock found.");
        } else {
            $this->line("Available livestock:");
            foreach ($livestock as $item) {
                $this->line("• {$item->name} (ID: {$item->id})");
            }
        }

        $this->newLine();
    }

    private function showTestingScope()
    {
        $this->info("🔍 Testing Scope");
        $this->info("-------------------");

        $this->line("Selected Options:");
        $this->line("• Farm ID: " . ($this->option('farm') ?? 'All'));
        $this->line("• Coop ID: " . ($this->option('coop') ?? 'All'));
        $this->line("• Livestock ID: " . ($this->option('livestock') ?? 'All'));
        $this->line("• Days to Analyze: " . ($this->option('days') ?? 'All available data'));
        $this->line("• Date Range: " . ($this->option('date-from') ?? 'Auto') . " to " . ($this->option('date-to') ?? 'Auto'));
        $this->line("• Calculate Daily Analytics: " . ($this->option('calculate') ? 'Yes' : 'No'));
        $this->line("• Show Detailed Output: " . ($this->option('detailed') ? 'Yes' : 'No'));
        $this->line("• List Livestock: " . ($this->option('list-livestock') ? 'Yes' : 'No'));
        $this->line("• Only Mortality Analysis: " . ($this->option('only-mortality') ? 'Yes' : 'No'));
        $this->line("• Only Production Analysis: " . ($this->option('only-production') ? 'Yes' : 'No'));
        $this->line("• Only Rankings Analysis: " . ($this->option('only-rankings') ? 'Yes' : 'No'));
        $this->line("• Only Sales Analysis: " . ($this->option('only-sales') ? 'Yes' : 'No'));
        $this->line("• Only Alerts Analysis: " . ($this->option('only-alerts') ? 'Yes' : 'No'));
        $this->line("• Only Overview Data: " . ($this->option('only-overview') ? 'Yes' : 'No'));
        $this->line("• Only Trends Data: " . ($this->option('only-trends') ? 'Yes' : 'No'));

        $this->newLine();
    }

    private function hasSelectiveOptions()
    {
        return $this->option('only-mortality') || $this->option('only-production') || $this->option('only-rankings') || $this->option('only-sales') || $this->option('only-alerts') || $this->option('only-overview') || $this->option('only-trends');
    }
}

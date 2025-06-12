<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AnalyticsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CalculateDailyAnalytics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analytics:daily-calculate 
                            {--date= : Specific date to calculate (Y-m-d format)}
                            {--days=7 : Number of days to calculate (default: 7 days back)}
                            {--force : Force calculation even if data exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate daily analytics for livestock farms';

    /**
     * Analytics service instance
     *
     * @var AnalyticsService
     */
    protected $analyticsService;

    /**
     * Create a new command instance.
     */
    public function __construct(AnalyticsService $analyticsService)
    {
        parent::__construct();
        $this->analyticsService = $analyticsService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🚀 Starting Daily Analytics Calculation...');

        // Get options
        $specificDate = $this->option('date');
        $days = (int) $this->option('days');
        $force = $this->option('force');

        try {
            if ($specificDate) {
                // Calculate for specific date
                $date = Carbon::parse($specificDate);
                $this->calculateForDate($date, $force);
            } else {
                // Calculate for range of days
                $this->calculateForDateRange($days, $force);
            }

            $this->info('✅ Daily Analytics Calculation completed successfully!');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Error calculating daily analytics: ' . $e->getMessage());
            Log::error('Daily Analytics Calculation Failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'specific_date' => $specificDate,
                'days' => $days,
                'force' => $force
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Calculate analytics for a specific date
     */
    private function calculateForDate(Carbon $date, bool $force = false)
    {
        $this->line("📅 Calculating analytics for: {$date->toDateString()}");

        $result = $this->analyticsService->calculateDailyAnalyticsWithResults($date, $force);

        $this->displayResults($date, $result ?? []);
    }

    /**
     * Calculate analytics for a range of dates
     */
    private function calculateForDateRange(int $days, bool $force = false)
    {
        $endDate = Carbon::yesterday(); // Default to yesterday to ensure complete data
        $startDate = $endDate->copy()->subDays($days - 1);

        $this->line("📊 Calculating analytics from {$startDate->toDateString()} to {$endDate->toDateString()}");

        $progressBar = $this->output->createProgressBar($days);
        $progressBar->start();

        $totalProcessed = 0;
        $totalAlerts = 0;
        $totalErrors = 0;

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            try {
                $result = $this->analyticsService->calculateDailyAnalyticsWithResults($date, $force);
                $totalProcessed += $result['analytics_created'] ?? 0;
                $totalAlerts += $result['alerts_created'] ?? 0;

                $progressBar->advance();
            } catch (\Exception $e) {
                $totalErrors++;
                Log::error('Daily Analytics Error', [
                    'date' => $date->toDateString(),
                    'error' => $e->getMessage()
                ]);
                $progressBar->advance();
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        // Display summary
        $this->displaySummary($totalProcessed, $totalAlerts, $totalErrors, $days);
    }

    /**
     * Display results for a single date
     */
    private function displayResults(Carbon $date, array $result)
    {
        $this->table(
            ['Metric', 'Value'],
            [
                ['Analytics Created', $result['analytics_created'] ?? 0],
                ['Alerts Generated', $result['alerts_created'] ?? 0],
                ['Livestock Processed', $result['livestock_processed'] ?? 0],
                ['Processing Time', ($result['processing_time'] ?? 0) . ' seconds'],
            ]
        );

        if (!empty($result['insights'])) {
            $this->info('🔍 Key Insights:');
            foreach ($result['insights'] as $insight) {
                $this->line("   • {$insight}");
            }
        }
    }

    /**
     * Display summary for date range calculation
     */
    private function displaySummary(int $totalProcessed, int $totalAlerts, int $totalErrors, int $days)
    {
        $this->info('📈 Calculation Summary:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Days Processed', $days],
                ['Total Analytics Created', $totalProcessed],
                ['Total Alerts Generated', $totalAlerts],
                ['Errors Encountered', $totalErrors],
                ['Success Rate', round((($days - $totalErrors) / $days) * 100, 2) . '%'],
            ]
        );

        if ($totalErrors > 0) {
            $this->warn("⚠️  {$totalErrors} errors encountered. Check logs for details.");
        }

        if ($totalAlerts > 0) {
            $this->info("🚨 {$totalAlerts} alerts generated. Review Smart Analytics dashboard.");
        }
    }
}

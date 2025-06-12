<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AnalyticsAlert;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CleanupAnalyticsAlerts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analytics:cleanup-alerts 
                            {--days=30 : Number of days to keep resolved alerts}
                            {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup old resolved analytics alerts';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🧹 Starting Analytics Alerts Cleanup...');

        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');
        $cutoffDate = Carbon::now()->subDays($days);

        try {
            // Get alerts to be cleaned up
            $alertsQuery = AnalyticsAlert::where('is_resolved', true)
                ->where('resolved_at', '<', $cutoffDate);

            $alertsCount = $alertsQuery->count();

            if ($alertsCount === 0) {
                $this->info('✅ No alerts found for cleanup.');
                return Command::SUCCESS;
            }

            $this->line("📊 Found {$alertsCount} resolved alerts older than {$days} days");

            if ($dryRun) {
                $this->warn('🔍 DRY RUN MODE - No alerts will be deleted');
                $this->showAlertsSummary($alertsQuery);
                return Command::SUCCESS;
            }

            // Confirm deletion
            if (!$this->confirm("Are you sure you want to delete {$alertsCount} old alerts?")) {
                $this->info('❌ Cleanup cancelled by user.');
                return Command::SUCCESS;
            }

            // Show summary before deletion
            $this->showAlertsSummary($alertsQuery);

            // Perform deletion
            $deletedCount = $alertsQuery->delete();

            $this->info("✅ Successfully deleted {$deletedCount} old alerts");

            // Log the cleanup
            Log::info('Analytics alerts cleanup completed', [
                'deleted_count' => $deletedCount,
                'cutoff_date' => $cutoffDate->toDateString(),
                'days_kept' => $days
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Error during alerts cleanup: ' . $e->getMessage());
            Log::error('Analytics alerts cleanup failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'days' => $days,
                'cutoff_date' => $cutoffDate->toDateString()
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Show summary of alerts to be cleaned up
     */
    private function showAlertsSummary($alertsQuery)
    {
        $summary = $alertsQuery->selectRaw('
                alert_type,
                severity,
                COUNT(*) as count
            ')
            ->groupBy('alert_type', 'severity')
            ->orderBy('severity')
            ->orderBy('count', 'desc')
            ->get();

        if ($summary->isNotEmpty()) {
            $this->info('📋 Alerts Summary:');

            $tableData = [];
            foreach ($summary as $item) {
                $tableData[] = [
                    'Type' => $item->alert_type,
                    'Severity' => ucfirst($item->severity),
                    'Count' => $item->count
                ];
            }

            $this->table(['Type', 'Severity', 'Count'], $tableData);
        }

        // Show date range
        $oldestAlert = $alertsQuery->orderBy('resolved_at')->first();
        $newestAlert = $alertsQuery->orderBy('resolved_at', 'desc')->first();

        if ($oldestAlert && $newestAlert) {
            $this->line("📅 Date range: {$oldestAlert->resolved_at->format('Y-m-d')} to {$newestAlert->resolved_at->format('Y-m-d')}");
        }
    }
}

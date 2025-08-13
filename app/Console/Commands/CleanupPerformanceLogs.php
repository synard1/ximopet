<?php

namespace App\Console\Commands;

use App\Models\DatabasePerformanceLog;
use Illuminate\Console\Command;
use Carbon\Carbon;

class CleanupPerformanceLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'performance:cleanup {--days=90 : Number of days to keep logs} {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old database performance logs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $daysToKeep = (int) $this->option('days');
        $dryRun = $this->option('dry-run');
        $cutoffDate = Carbon::now()->subDays($daysToKeep);

        $this->info("🔍 Analyzing performance logs older than {$daysToKeep} days...");
        $this->info("📅 Cutoff date: {$cutoffDate->format('Y-m-d H:i:s')}");

        // Get count of logs that would be deleted
        $logsToDelete = DatabasePerformanceLog::where('created_at', '<', $cutoffDate);
        $count = $logsToDelete->count();

        if ($count === 0) {
            $this->info("✅ No old performance logs found. Nothing to clean up.");
            return 0;
        }

        $this->warn("⚠️  Found {$count} performance logs to delete");

        if ($dryRun) {
            $this->info("🔍 DRY RUN - No logs will be deleted");

            // Show sample of logs that would be deleted
            $sampleLogs = $logsToDelete->limit(5)->get();
            $this->table(
                ['ID', 'Model', 'Operation', 'Status', 'Created At', 'Execution Time (ms)'],
                $sampleLogs->map(function ($log) {
                    return [
                        $log->id,
                        class_basename($log->model_class),
                        $log->operation_type,
                        $log->status,
                        $log->created_at->format('Y-m-d H:i:s'),
                        $log->execution_time_ms
                    ];
                })
            );

            $this->info("💡 Run without --dry-run to actually delete these logs");
            return 0;
        }

        // Confirm deletion
        if (!$this->confirm("Are you sure you want to delete {$count} performance logs?")) {
            $this->info("❌ Operation cancelled");
            return 0;
        }

        // Get some statistics before deletion
        $this->info("📊 Collecting statistics before deletion...");

        $stats = DatabasePerformanceLog::where('created_at', '<', $cutoffDate)
            ->selectRaw('
                COUNT(*) as total_logs,
                COUNT(DISTINCT model_class) as unique_models,
                COUNT(DISTINCT user_id) as unique_users,
                AVG(execution_time_ms) as avg_execution_time,
                MAX(execution_time_ms) as max_execution_time,
                COUNT(CASE WHEN status = "slow" THEN 1 END) as slow_operations,
                COUNT(CASE WHEN status = "error" THEN 1 END) as error_operations
            ')
            ->first();

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Logs', $stats->total_logs],
                ['Unique Models', $stats->unique_models],
                ['Unique Users', $stats->unique_users],
                ['Avg Execution Time (ms)', round($stats->avg_execution_time, 2)],
                ['Max Execution Time (ms)', $stats->max_execution_time],
                ['Slow Operations', $stats->slow_operations],
                ['Error Operations', $stats->error_operations],
            ]
        );

        // Perform deletion
        $this->info("🗑️  Deleting old performance logs...");

        $deletedCount = $logsToDelete->delete();

        if ($deletedCount > 0) {
            $this->info("✅ Successfully deleted {$deletedCount} performance logs");

            // Show remaining logs count
            $remainingCount = DatabasePerformanceLog::count();
            $this->info("📊 Remaining performance logs: {$remainingCount}");

            // Show disk space saved (approximate)
            $estimatedBytesPerLog = 500; // Rough estimate
            $bytesSaved = $deletedCount * $estimatedBytesPerLog;
            $mbSaved = round($bytesSaved / 1024 / 1024, 2);
            $this->info("💾 Estimated disk space saved: {$mbSaved} MB");
        } else {
            $this->warn("⚠️  No logs were deleted");
        }

        return 0;
    }
}

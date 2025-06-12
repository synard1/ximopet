<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TempAuthLog;

class CleanupDuplicateRevokeLogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'temp-auth:cleanup-duplicates 
                            {--dry-run : Show what would be deleted without actually deleting}
                            {--force : Force cleanup without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup duplicate revoke logs in temp_auth_logs table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info('🔍 Scanning for duplicate revoke logs...');

        // Find all separate revoke logs (action='revoked') which are duplicates
        // These were created when we had double logging bug
        $duplicateRevokeLogs = TempAuthLog::where('action', 'revoked')->get();

        $this->info("Found {$duplicateRevokeLogs->count()} duplicate revoke logs");

        if ($duplicateRevokeLogs->isEmpty()) {
            $this->info('✅ No duplicate logs found. Database is clean!');
            return 0;
        }

        // Show what will be deleted
        $headers = ['ID', 'User ID', 'Component', 'Created At', 'Reason'];
        $rows = [];

        foreach ($duplicateRevokeLogs as $log) {
            $rows[] = [
                $log->id,
                $log->user_id,
                $log->component ?: 'N/A',
                $log->created_at ? $log->created_at->format('Y-m-d H:i:s') : 'N/A',
                'Duplicate revoke log'
            ];
        }

        $this->table($headers, $rows);

        if ($dryRun) {
            $this->info('🔍 DRY RUN: Above records would be deleted. Use --force to actually delete.');
            return 0;
        }

        if (!$force) {
            if (!$this->confirm('❓ Do you want to delete these duplicate revoke logs?')) {
                $this->info('❌ Cleanup cancelled.');
                return 0;
            }
        }

        // Delete duplicate logs
        $deletedCount = 0;
        foreach ($duplicateRevokeLogs as $log) {
            try {
                $log->delete();
                $deletedCount++;
                $this->info("✅ Deleted duplicate log ID: {$log->id}");
            } catch (\Exception $e) {
                $this->error("❌ Failed to delete log ID: {$log->id} - {$e->getMessage()}");
            }
        }

        $this->info("🎉 Cleanup completed! Deleted {$deletedCount} duplicate revoke logs.");

        // Show final statistics
        $this->newLine();
        $this->showCleanupStatistics();

        return 0;
    }

    private function showCleanupStatistics()
    {
        $this->info("📊 Final Statistics:");

        $totalGranted = TempAuthLog::where('action', 'granted')->count();
        $totalRevoked = TempAuthLog::where('action', 'granted')->whereNotNull('revoked_at')->count();
        $totalActive = TempAuthLog::where('action', 'granted')
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->count();
        $duplicateRevokeRemaining = TempAuthLog::where('action', 'revoked')->count();

        $this->info("  ✅ Total granted: {$totalGranted}");
        $this->info("  ❌ Total revoked: {$totalRevoked}");
        $this->info("  🟢 Currently active: {$totalActive}");
        $this->info("  🗑️ Duplicate revoke logs remaining: {$duplicateRevokeRemaining}");

        if ($duplicateRevokeRemaining > 0) {
            $this->warn("⚠️  Warning: {$duplicateRevokeRemaining} revoke logs still exist. These might be legitimate separate revoke actions.");
        } else {
            $this->info("✨ Database is now clean of duplicate revoke logs!");
        }
    }
}

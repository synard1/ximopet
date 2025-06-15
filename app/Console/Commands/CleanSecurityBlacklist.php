<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Middleware\SecurityBlacklistMiddleware;
use App\Models\SecurityBlacklist;
use App\Models\SecurityViolation;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CleanSecurityBlacklist extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'security:clean-blacklist 
                            {--force : Force cleanup without confirmation}
                            {--days=30 : Clean violations older than specified days}';

    /**
     * The console command description.
     */
    protected $description = 'Clean expired security blacklist entries and old violation records';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting security blacklist cleanup...');

        // Clean expired blacklist entries
        $deletedBlacklist = SecurityBlacklistMiddleware::cleanExpiredEntries();
        $this->info("Cleaned {$deletedBlacklist} expired blacklist entries");

        // Clean old violation records
        $days = (int) $this->option('days');
        $cutoffDate = Carbon::now()->subDays($days);

        if (!$this->option('force')) {
            if (!$this->confirm("Clean violation records older than {$days} days (before {$cutoffDate->format('Y-m-d H:i:s')})?")) {
                $this->info('Violation cleanup cancelled');
                return 0;
            }
        }

        $deletedViolations = SecurityViolation::cleanOldViolations($days);

        $this->info("Cleaned {$deletedViolations} old violation records");

        // Show current statistics
        $this->showStatistics();

        $this->info('Security cleanup completed successfully');
        return 0;
    }

    /**
     * Show current security statistics
     */
    private function showStatistics(): void
    {
        $this->info('Current Security Statistics:');

        // Active blacklist entries
        $activeBlacklist = SecurityBlacklist::getActive()->count();
        $this->line("- Active blacklist entries: {$activeBlacklist}");

        // Recent violations (last 24 hours)
        $recentViolations = SecurityViolation::where('created_at', '>=', Carbon::now()->subHours(24))->count();
        $this->line("- Violations in last 24 hours: {$recentViolations}");

        // Get statistics using model method
        $stats = SecurityViolation::getStatistics(7);

        if ($stats['top_reasons']->isNotEmpty()) {
            $this->line('- Top violation reasons (last 7 days):');
            foreach ($stats['top_reasons'] as $reason) {
                $this->line("  * {$reason->reason}: {$reason->count}");
            }
        }

        if ($stats['top_ips']->isNotEmpty()) {
            $this->line('- Most violated IPs (last 7 days):');
            foreach ($stats['top_ips'] as $ip) {
                $this->line("  * {$ip->ip_address}: {$ip->count} violations");
            }
        }
    }
}
 
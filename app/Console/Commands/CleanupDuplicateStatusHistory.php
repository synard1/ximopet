<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\LivestockPurchase;
use Illuminate\Support\Facades\Log;

class CleanupDuplicateStatusHistory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'livestock:cleanup-duplicate-status-history 
                            {--purchase-id= : Specific purchase ID to cleanup}
                            {--dry-run : Show what would be cleaned without actually doing it}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up duplicate status history records for livestock purchases';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Starting duplicate status history cleanup...');

        $purchaseId = $this->option('purchase-id');
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('🧪 DRY RUN MODE - No changes will be made');
        }

        // Get purchases to process
        if ($purchaseId) {
            $purchases = LivestockPurchase::where('id', $purchaseId)->get();
            if ($purchases->isEmpty()) {
                $this->error("❌ Purchase with ID {$purchaseId} not found");
                return 1;
            }
        } else {
            $purchases = LivestockPurchase::all();
        }

        $this->info("📊 Processing {$purchases->count()} purchase(s)...");

        $totalCleaned = 0;
        $processedCount = 0;

        $progressBar = $this->output->createProgressBar($purchases->count());
        $progressBar->start();

        foreach ($purchases as $purchase) {
            try {
                if ($isDryRun) {
                    // Count duplicates without deleting
                    $duplicates = $purchase->statusHistories()
                        ->select('status_from', 'status_to', 'created_at')
                        ->selectRaw('COUNT(*) as count')
                        ->groupBy('status_from', 'status_to', 'created_at')
                        ->having('count', '>', 1)
                        ->get();

                    $duplicateCount = 0;
                    foreach ($duplicates as $duplicate) {
                        $duplicateCount += $duplicate->count - 1; // Subtract 1 to keep one record
                    }

                    if ($duplicateCount > 0) {
                        $this->line("\n📝 Purchase {$purchase->id}: Would clean {$duplicateCount} duplicate(s)");
                        $totalCleaned += $duplicateCount;
                    }
                } else {
                    // Actually clean duplicates
                    $cleanedCount = $purchase->cleanupDuplicateStatusHistory();
                    if ($cleanedCount > 0) {
                        $this->line("\n✅ Purchase {$purchase->id}: Cleaned {$cleanedCount} duplicate(s)");
                        $totalCleaned += $cleanedCount;
                    }
                }

                $processedCount++;
            } catch (\Exception $e) {
                $this->error("\n❌ Error processing purchase {$purchase->id}: " . $e->getMessage());
                Log::error('CleanupDuplicateStatusHistory: Error processing purchase', [
                    'purchase_id' => $purchase->id,
                    'error' => $e->getMessage()
                ]);
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        if ($isDryRun) {
            $this->info("🧪 DRY RUN COMPLETED");
            $this->info("📊 Would clean {$totalCleaned} duplicate record(s) from {$processedCount} purchase(s)");
        } else {
            $this->info("✅ CLEANUP COMPLETED");
            $this->info("📊 Cleaned {$totalCleaned} duplicate record(s) from {$processedCount} purchase(s)");
        }

        Log::info('CleanupDuplicateStatusHistory: Command completed', [
            'total_cleaned' => $totalCleaned,
            'processed_count' => $processedCount,
            'is_dry_run' => $isDryRun
        ]);

        return 0;
    }
}

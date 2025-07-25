<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CurrentFeed;
use App\Services\Feed\CurrentFeedService;

/**
 * Command to recalculate and fix quantity for all CurrentFeed records based on FeedPurchaseItem data.
 *
 * Uses modular CurrentFeedService for robust, future-proof logic.
 * See docs/services/current-feed-service.md for details.
 */
class FixCurrentFeedQuantity extends Command
{
    protected $signature = 'feed:fix-current-feed
        {--dry-run : Show what would be fixed without making changes}
        {--livestock-id= : Filter by specific livestock ID}
        {--feed-id= : Filter by specific feed ID}';

    protected $description = 'Recalculate and fix quantity for all CurrentFeed records based on FeedPurchaseItem data.';

    /**
     * @var CurrentFeedService
     */
    protected $currentFeedService;

    public function __construct(CurrentFeedService $currentFeedService)
    {
        parent::__construct();
        $this->currentFeedService = $currentFeedService;
    }

    public function handle()
    {
        $this->info('🔧 Starting CurrentFeed quantity recalculation');
        $dryRun = $this->option('dry-run');
        $livestockId = $this->option('livestock-id');
        $feedId = $this->option('feed-id');

        // Build filter array for the service
        $filter = [];
        if ($livestockId) {
            $filter['livestock_id'] = $livestockId;
        }
        if ($feedId) {
            $filter['feed_id'] = $feedId;
        }

        // Use the modular service for recalculation
        $result = $this->currentFeedService->updateQuantity($filter, $dryRun);
        $total = count($result['details']);
        if ($total === 0) {
            $this->warn('No CurrentFeed records found matching the criteria');
            return 0;
        }
        $this->info("Found $total CurrentFeed records to process");
        $bar = $this->output->createProgressBar($total);
        $bar->start();
        foreach ($result['details'] as $detail) {
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();
        $this->info("\nRecalculation complete. Fixed: {$result['fixed']}, Skipped: {$result['skipped']}, Errors: {$result['errors']}");
        if (!empty($result['details'])) {
            $this->table(
                ['ID', 'Livestock', 'Feed', 'Unit', 'Old Qty', 'New Qty', 'Reason'],
                array_map(function ($d) {
                    return [
                        $d['id'],
                        $d['livestock_id'],
                        $d['feed_id'],
                        $d['unit_id'],
                        $d['old'],
                        $d['new'],
                        $d['reason'],
                    ];
                }, $result['details'])
            );
        }
        if ($dryRun) {
            $this->warn('Dry run mode: No data was actually updated.');
        }
        return 0;
    }
}

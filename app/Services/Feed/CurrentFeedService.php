<?php

namespace App\Services\Feed;

use App\Models\CurrentFeed;
use App\Models\FeedStock;
use App\Models\Livestock;
use App\Models\Feed;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * CurrentFeedService
 *
 * Modular service for updating, recalculating, and syncing CurrentFeed quantity.
 * Supports flexible filtering, batch/single update, dry-run, and logging.
 *
 * For full documentation, see: docs/services/current-feed-service.md
 */
class CurrentFeedService
{
    /**
     * Update or recalculate CurrentFeed quantity based on FeedStock for given filters.
     *
     * @param array $filter [livestock_id, feed_id, farm_id, unit_id]
     * @param bool $dryRun If true, only preview changes without saving
     * @return array [fixed, skipped, errors, details]
     */
    public function updateQuantity(array $filter = [], bool $dryRun = false): array
    {
        $query = CurrentFeed::query();
        if (!empty($filter['livestock_id'])) {
            $query->where('livestock_id', $filter['livestock_id']);
        }
        if (!empty($filter['feed_id'])) {
            $query->where('feed_id', $filter['feed_id']);
        }
        if (!empty($filter['farm_id'])) {
            $query->where('farm_id', $filter['farm_id']);
        }
        if (!empty($filter['unit_id'])) {
            $query->where('unit_id', $filter['unit_id']);
        }
        $feeds = $query->get();
        $result = [
            'fixed' => 0,
            'skipped' => 0,
            'errors' => 0,
            'details' => [],
        ];
        foreach ($feeds as $feed) {
            try {
                $syncResult = $this->syncWithFeedStock($feed, $dryRun);
                $result['fixed'] += $syncResult['fixed'] ?? 0;
                $result['skipped'] += $syncResult['skipped'] ?? 0;
                $result['errors'] += $syncResult['errors'] ?? 0;
                $result['details'][] = $syncResult['detail'];
            } catch (\Exception $e) {
                $result['errors']++;
                $result['details'][] = [
                    'id' => $feed->id,
                    'livestock_id' => $feed->livestock_id,
                    'feed_id' => $feed->feed_id,
                    'unit_id' => $feed->unit_id,
                    'old' => $feed->quantity,
                    'new' => null,
                    'reason' => 'Error: ' . $e->getMessage(),
                ];
                Log::error('[CurrentFeedService] Error updating CurrentFeed', [
                    'feed_id' => $feed->id,
                    'exception' => $e,
                ]);
            }
        }
        return $result;
    }

    /**
     * Batch recalculate all CurrentFeed records matching filter.
     *
     * @param array $filter
     * @param bool $dryRun
     * @return array
     */
    public function recalculateAll(array $filter = [], bool $dryRun = false): array
    {
        return $this->updateQuantity($filter, $dryRun);
    }

    /**
     * Sync a single CurrentFeed with FeedStock records.
     *
     * @param CurrentFeed $currentFeed
     * @param bool $dryRun
     * @return array [fixed, skipped, errors, detail]
     */
    public function syncWithFeedStock(CurrentFeed $currentFeed, bool $dryRun = false): array
    {
        // Find all FeedStock for this livestock + feed
        $stocks = FeedStock::where('livestock_id', $currentFeed->livestock_id)
            ->where('feed_id', $currentFeed->feed_id)
            ->whereNull('deleted_at')
            ->get();

        $newQuantity = $stocks->sum(function ($stock) use ($currentFeed) {
            // If CurrentFeed has no unit_id, sum all
            if (!$currentFeed->unit_id) {
                return ($stock->quantity_in ?? 0) - ($stock->quantity_used ?? 0) - ($stock->quantity_mutated ?? 0);
            }
            // If FeedStock has no feed_purchase_item_id, assume unit matches
            if (!$stock->feed_purchase_item_id) {
                return ($stock->quantity_in ?? 0) - ($stock->quantity_used ?? 0) - ($stock->quantity_mutated ?? 0);
            }
            $item = \App\Models\FeedPurchaseItem::find($stock->feed_purchase_item_id);
            if ($item && $item->unit_id === $currentFeed->unit_id) {
                return ($stock->quantity_in ?? 0) - ($stock->quantity_used ?? 0) - ($stock->quantity_mutated ?? 0);
            }
            return 0;
        });
        $oldQuantity = $currentFeed->quantity;
        $detail = [
            'id' => $currentFeed->id,
            'livestock_id' => $currentFeed->livestock_id,
            'feed_id' => $currentFeed->feed_id,
            'unit_id' => $currentFeed->unit_id,
            'old' => $oldQuantity,
            'new' => $newQuantity,
            'reason' => null,
        ];
        if ($oldQuantity != $newQuantity) {
            $detail['reason'] = 'Fixed (quantity updated)';
            if (!$dryRun) {
                $currentFeed->quantity = $newQuantity;
                $currentFeed->save();
            }
            Log::info('[CurrentFeedService] Updated CurrentFeed quantity', [
                'id' => $currentFeed->id,
                'livestock_id' => $currentFeed->livestock_id,
                'feed_id' => $currentFeed->feed_id,
                'unit_id' => $currentFeed->unit_id,
                'old' => $oldQuantity,
                'new' => $newQuantity,
            ]);
            return ['fixed' => 1, 'skipped' => 0, 'errors' => 0, 'detail' => $detail];
        } else {
            $detail['reason'] = $newQuantity == 0 ? 'No FeedStock found' : 'No change needed (already correct)';
            Log::debug('[CurrentFeedService] No update needed for CurrentFeed', [
                'id' => $currentFeed->id,
                'livestock_id' => $currentFeed->livestock_id,
                'feed_id' => $currentFeed->feed_id,
                'unit_id' => $currentFeed->unit_id,
                'quantity' => $oldQuantity,
            ]);
            return ['fixed' => 0, 'skipped' => 1, 'errors' => 0, 'detail' => $detail];
        }
    }

    /**
     * Get CurrentFeed records by filter.
     *
     * @param array $filter
     * @return Collection
     */
    public function getCurrentFeed(array $filter = []): Collection
    {
        $query = CurrentFeed::query();
        if (!empty($filter['livestock_id'])) {
            $query->where('livestock_id', $filter['livestock_id']);
        }
        if (!empty($filter['feed_id'])) {
            $query->where('feed_id', $filter['feed_id']);
        }
        if (!empty($filter['farm_id'])) {
            $query->where('farm_id', $filter['farm_id']);
        }
        if (!empty($filter['unit_id'])) {
            $query->where('unit_id', $filter['unit_id']);
        }
        return $query->get();
    }
}

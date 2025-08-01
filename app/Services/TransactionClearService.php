<?php

namespace App\Services;

use App\Models\{
    Livestock,
    LivestockBatch,
    CurrentLivestock,
    CurrentFeed,
    CurrentSupply,
    Recording,
    // RecordingItem,
    LivestockDepletion,
    LivestockSales,
    LivestockSalesItem,
    SalesTransaction,
    FeedUsage,
    FeedUsageDetail,
    FeedStock,
    FeedMutation,
    FeedMutationItem,
    SupplyUsage,
    SupplyUsageDetail,
    SupplyStock,
    SupplyMutation,
    SupplyMutationItem,
    Mutation,
    MutationItem,
    LivestockMutation,
    LivestockMutationItem,
    DailyAnalytics,
    PeriodAnalytics,
    AnalyticsAlert,
    LivestockCost,
    AlertLog,
    FeedPurchase,
    FeedPurchaseItem,
    SupplyPurchase,
    SupplyPurchaseBatch,
    LivestockPurchase,
    LivestockPurchaseItem,
    LivestockPurchaseStatusHistory,
    FeedStatusHistory,
    SupplyStatusHistory,
    FeedTransactionHistory,
    Coop
};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class TransactionClearService
{
    /**
     * Clear all transaction data while keeping purchase data
     */
    public function clearAllTransactionData(): array
    {
        $result = [
            'success' => false,
            'message' => '',
            'cleared_data' => [],
            'purchase_status_changed' => [],
            'errors' => []
        ];

        DB::beginTransaction();

        try {
            Log::info('🧹 Starting transaction data clearing process');

            // Step 1: Clear transaction records
            $clearedData = $this->clearTransactionRecords();
            $result['cleared_data'] = $clearedData;

            // Step 2: Clear usage and mutation data
            $this->clearUsageAndMutationData();

            // Step 3: Clear stock data (but not purchase data)
            $this->clearStockData();

            // Step 4: Clear analytics and alerts
            $this->clearAnalyticsData();

            // Step 5: Clear status history data
            $this->clearStatusHistoryData();

            // Step 6: Clear current data (CurrentFeed, CurrentLivestock, CurrentSupply)
            $this->clearCurrentData();

            // Step 6.5: Clear feed transaction histories (to handle foreign key constraints)
            $clearedHistoryData = $this->clearFeedTransactionHistories();
            $result['cleared_data'] = array_merge($result['cleared_data'], $clearedHistoryData);

            // Step 7: Clear livestock data (including soft-deleted)
            $clearedLivestockData = $this->clearLivestockData();
            $result['cleared_data'] = array_merge($result['cleared_data'], $clearedLivestockData);

            // Step 8: Reset current stock data
            $this->resetCurrentStockData();

            // Step 9: Ensure purchase data integrity
            $integrityResult = $this->ensurePurchaseDataIntegrity();
            $result['integrity_fixes'] = $integrityResult;

            // Step 10: Change purchase statuses to draft
            $purchaseStatusChanged = $this->changePurchaseStatusesToDraft();
            $result['purchase_status_changed'] = $purchaseStatusChanged;

            DB::commit();

            $result['success'] = true;
            $result['message'] = 'Transaction data cleared successfully. All livestock data deleted. Purchase statuses changed to draft.';

            Log::info('✅ Transaction data clearing completed successfully', [
                'cleared_records' => count($clearedData),
                'cleared_livestock' => $clearedLivestockData['livestock'],
                'purchase_status_changed' => $purchaseStatusChanged
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            $result['success'] = false;
            $result['message'] = 'Failed to clear transaction data: ' . $e->getMessage();
            $result['errors'][] = $e->getMessage();

            Log::error('❌ Transaction data clearing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return $result;
    }

    /**
     * Clear basic transaction records
     */
    private function clearTransactionRecords(): array
    {
        $cleared = [];

        // Clear recordings and related data (include soft-deleted)
        $recordingCount = Recording::withTrashed()->count();
        Recording::withTrashed()->forceDelete();
        // RecordingItem::withTrashed()->forceDelete();
        $cleared['recordings'] = $recordingCount;

        // Clear livestock depletion data (include soft-deleted)
        $depletionCount = LivestockDepletion::withTrashed()->count();
        LivestockDepletion::withTrashed()->forceDelete();
        $cleared['livestock_depletion'] = $depletionCount;

        // Clear livestock sales data (include soft-deleted)
        $salesCount = LivestockSales::withTrashed()->count();
        LivestockSales::withTrashed()->forceDelete();
        LivestockSalesItem::withTrashed()->forceDelete();
        $cleared['livestock_sales'] = $salesCount;

        // Clear sales transactions (include soft-deleted)
        $salesTransactionCount = SalesTransaction::withTrashed()->count();
        SalesTransaction::withTrashed()->forceDelete();
        $cleared['sales_transactions'] = $salesTransactionCount;

        // Clear livestock cost records (include soft-deleted)
        $costCount = LivestockCost::withTrashed()->count();
        LivestockCost::withTrashed()->forceDelete();
        $cleared['livestock_costs'] = $costCount;

        Log::info('📝 Transaction records cleared (including soft-deleted)', $cleared);

        return $cleared;
    }

    /**
     * Clear usage and mutation data
     */
    private function clearUsageAndMutationData(): void
    {
        // Clear feed usage data (include soft-deleted)
        FeedUsage::withTrashed()->forceDelete();
        FeedUsageDetail::withTrashed()->forceDelete();

        // Clear feed mutations (include soft-deleted)
        FeedMutation::withTrashed()->forceDelete();
        FeedMutationItem::withTrashed()->forceDelete();

        // Clear supply usage data (include soft-deleted)
        SupplyUsage::withTrashed()->forceDelete();
        SupplyUsageDetail::withTrashed()->forceDelete();

        // Clear supply mutations (include soft-deleted)
        SupplyMutation::withTrashed()->forceDelete();
        SupplyMutationItem::withTrashed()->forceDelete();

        // Clear livestock mutations (include soft-deleted)
        LivestockMutation::withTrashed()->forceDelete();
        LivestockMutationItem::withTrashed()->forceDelete();

        // Clear general mutations (include soft-deleted)
        Mutation::withTrashed()->forceDelete();
        MutationItem::withTrashed()->forceDelete();

        Log::info('🔄 Usage and mutation data cleared (including soft-deleted)');
    }

    /**
     * Clear stock data (non-purchase related)
     */
    private function clearStockData(): void
    {
        // Clear feed stocks (include soft-deleted)
        FeedStock::withTrashed()->forceDelete();

        // Clear supply stocks (include soft-deleted)
        SupplyStock::withTrashed()->forceDelete();

        Log::info('📦 Stock data cleared (including soft-deleted)');
    }

    /**
     * Clear analytics and alert data
     */
    private function clearAnalyticsData(): void
    {
        // Clear analytics data (include soft-deleted)
        DailyAnalytics::withTrashed()->forceDelete();
        PeriodAnalytics::withTrashed()->forceDelete();
        AnalyticsAlert::withTrashed()->forceDelete();

        // Clear transaction-related alerts (include soft-deleted)
        AlertLog::withTrashed()->whereIn('type', [
            'feed_usage',
            'supply_usage',
            'livestock_depletion',
            'livestock_sales',
            'mortality_alert',
            'performance_alert'
        ])->forceDelete();

        Log::info('📊 Analytics and alert data cleared (including soft-deleted)');
    }

    /**
     * Clear feed transaction histories (to handle foreign key constraints)
     */
    private function clearFeedTransactionHistories(): array
    {
        $cleared = [];

        // Clear feed transaction histories (include soft-deleted)
        $historyCount = FeedTransactionHistory::withTrashed()->count();
        FeedTransactionHistory::withTrashed()->forceDelete();
        $cleared['feed_transaction_histories'] = $historyCount;

        Log::info('📜 Feed transaction histories cleared (including soft-deleted)', $cleared);

        return $cleared;
    }

    /**
     * Clear status history data
     */
    private function clearStatusHistoryData(): void
    {
        // Clear livestock purchase status history (include soft-deleted)
        LivestockPurchaseStatusHistory::withTrashed()->forceDelete();

        // Clear feed status history (include soft-deleted)
        FeedStatusHistory::withTrashed()->forceDelete();

        // Clear supply status history (include soft-deleted)
        SupplyStatusHistory::withTrashed()->forceDelete();

        Log::info('📜 Status history data cleared (including soft-deleted)');
    }

    /**
     * Clear current data (CurrentFeed, CurrentLivestock, CurrentSupply)
     */
    private function clearCurrentData(): void
    {
        // Clear current feed data (include soft-deleted)
        CurrentFeed::withTrashed()->forceDelete();

        // Clear current livestock data (include soft-deleted)
        CurrentLivestock::withTrashed()->forceDelete();

        // Clear current supply data (include soft-deleted)
        CurrentSupply::withTrashed()->forceDelete();

        Log::info('📊 Current data cleared (including soft-deleted)');
    }

    /**
     * Reset coop data before livestock deletion (handle foreign key constraint)
     */
    private function resetCoopData(): array
    {
        $updated = [];

        // Count coops that have livestock_id set
        $coopsWithLivestock = Coop::whereNotNull('livestock_id')->count();

        // Reset coop data to handle foreign key constraint
        Coop::whereNotNull('livestock_id')->update([
            'livestock_id' => null,
            'quantity' => 0,
            'weight' => 0.00,
            'status' => 'active'
        ]);

        $updated['coops_reset'] = $coopsWithLivestock;

        Log::info('🏠 Coop data reset to handle foreign key constraint', $updated);

        return $updated;
    }

    /**
     * Clear livestock data (including soft-deleted)
     */
    private function clearLivestockData(): array
    {
        $cleared = [];

        // Count livestock and batches before deletion (include soft-deleted)
        $livestockCount = Livestock::withTrashed()->count();
        $batchCount = LivestockBatch::withTrashed()->count();

        // Step 1: Reset coop data to handle foreign key constraint
        $coopReset = $this->resetCoopData();
        $cleared = array_merge($cleared, $coopReset);

        // Step 2: Clear livestock batches first (due to foreign key constraints)
        LivestockBatch::withTrashed()->forceDelete();
        $cleared['livestock_batches'] = $batchCount;

        // Improvement: Nullify livestock_id pada LivestockPurchaseItem sebelum Livestock dihapus
        $livestockIds = Livestock::withTrashed()->pluck('id')->toArray();
        $nullifiedCount = LivestockPurchaseItem::whereIn('livestock_id', $livestockIds)->update(['livestock_id' => null]);
        Log::info('🛡️ Nullified livestock_id on LivestockPurchaseItem before deleting Livestock', ['count' => $nullifiedCount]);

        // Step 3: Clear livestock data (include soft-deleted)
        Livestock::withTrashed()->forceDelete();
        $cleared['livestock'] = $livestockCount;

        Log::info('🐔 Livestock data cleared (including soft-deleted)', $cleared);

        return $cleared;
    }

    /**
     * Reset current stock data to initial purchase state
     * Updated to use new FeedPurchase and FeedPurchaseItem structure
     */
    private function resetCurrentStockData(): void
    {
        // Since livestock is deleted, we don't need to reset current livestock data
        // Just clear any remaining current livestock records
        // CurrentLivestock is already cleared in clearCurrentData()

        // Reset current feed to initial purchase state
        $currentFeeds = CurrentFeed::with(['livestock', 'feed'])->get();

        foreach ($currentFeeds as $currentFeed) {
            // Get total purchased feed for this livestock and feed type from FeedPurchaseItem
            $totalPurchased = FeedPurchaseItem::whereHas('feedPurchase', function ($query) use ($currentFeed) {
                $query->where('livestock_id', $currentFeed->livestock_id);
            })
                ->where('feed_id', $currentFeed->feed_id)
                ->sum('converted_quantity');

            $currentFeed->update([
                'quantity' => $totalPurchased
            ]);
        }

        // Reset current supply to initial purchase state
        $currentSupplies = CurrentSupply::with(['livestock'])->get();

        foreach ($currentSupplies as $currentSupply) {
            // Get total purchased supply for this livestock and supply type
            $totalPurchased = SupplyPurchase::where('livestock_id', $currentSupply->livestock_id)
                ->where('item_id', $currentSupply->item_id)
                ->sum('converted_quantity');

            $currentSupply->update([
                'quantity' => $totalPurchased
            ]);
        }

        Log::info('📊 Current stock data reset to initial state');
    }

    /**
     * Ensure purchase data integrity by checking and fixing orphaned records
     * Updated to use new FeedPurchase and FeedPurchaseItem structure
     * NOTE: LivestockPurchase and LivestockPurchaseItem should NEVER be deleted
     */
    private function ensurePurchaseDataIntegrity(): array
    {
        $fixes = [];

        Log::info('🔍 Checking purchase data integrity...');

        // Check FeedPurchase and FeedPurchaseItem relationships (NEW STRUCTURE)
        $orphanedFeedPurchaseItems = FeedPurchaseItem::whereNotExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('feed_purchases')
                ->whereColumn('feed_purchases.id', 'feed_purchase_items.feed_purchase_id');
        })->withTrashed()->count();

        if ($orphanedFeedPurchaseItems > 0) {
            // Delete orphaned feed purchase items
            FeedPurchaseItem::whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('feed_purchases')
                    ->whereColumn('feed_purchases.id', 'feed_purchase_items.feed_purchase_id');
            })->withTrashed()->forceDelete();

            $fixes['orphaned_feed_purchase_items'] = $orphanedFeedPurchaseItems;
            Log::warning("🗑️ Deleted {$orphanedFeedPurchaseItems} orphaned feed purchase items");
        }

        // Check for FeedPurchase without any FeedPurchaseItem (but preserve them)
        $emptyFeedPurchases = FeedPurchase::whereDoesntHave('feedPurchaseItems')->withTrashed()->count();
        if ($emptyFeedPurchases > 0) {
            // Don't delete empty purchases, just log them for reference
            $fixes['empty_feed_purchases'] = $emptyFeedPurchases;
            Log::info("📝 Found {$emptyFeedPurchases} empty feed purchases (preserved)");
        }

        // Check SupplyPurchaseBatch and SupplyPurchase relationships
        $orphanedSupplyPurchases = SupplyPurchase::whereNotExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('supply_purchase_batches')
                ->whereColumn('supply_purchase_batches.id', 'supply_purchases.supply_purchase_batch_id');
        })->withTrashed()->count();

        if ($orphanedSupplyPurchases > 0) {
            // Delete orphaned supply purchases
            SupplyPurchase::whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('supply_purchase_batches')
                    ->whereColumn('supply_purchase_batches.id', 'supply_purchases.supply_purchase_batch_id');
            })->withTrashed()->forceDelete();

            $fixes['orphaned_supply_purchases'] = $orphanedSupplyPurchases;
            Log::warning("🗑️ Deleted {$orphanedSupplyPurchases} orphaned supply purchases");
        }

        // Check for SupplyPurchaseBatch without any SupplyPurchase (but preserve them)
        $emptySupplyBatches = SupplyPurchaseBatch::whereDoesntHave('supplyPurchases')->withTrashed()->count();
        if ($emptySupplyBatches > 0) {
            // Don't delete empty batches, just log them for reference
            $fixes['empty_supply_batches'] = $emptySupplyBatches;
            Log::info("📝 Found {$emptySupplyBatches} empty supply purchase batches (preserved)");
        }

        // Check and FIX orphaned LivestockPurchaseItem records
        $orphanedLivestockPurchaseItems = LivestockPurchaseItem::whereNotExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('livestock_purchases')
                ->whereColumn('livestock_purchases.id', 'livestock_purchase_items.livestock_purchase_id');
        })->withTrashed()->count();

        if ($orphanedLivestockPurchaseItems > 0) {
            // Improvement: Jangan hapus LivestockPurchaseItem meskipun orphan, cukup log warning
            Log::warning("⚠️ Found {$orphanedLivestockPurchaseItems} orphaned livestock purchase items (preserved, not deleted)");
            $fixes['orphaned_livestock_purchase_items_preserved'] = $orphanedLivestockPurchaseItems;
        }

        // Check and FIX empty LivestockPurchase records
        $emptyLivestockPurchases = LivestockPurchase::whereDoesntHave('details')->withTrashed()->count();
        if ($emptyLivestockPurchases > 0) {
            // Improvement: Jangan hapus LivestockPurchase meskipun tidak ada detail, cukup log warning
            Log::warning("⚠️ Found {$emptyLivestockPurchases} livestock purchases without details (preserved, not deleted)");
            $fixes['empty_livestock_purchases_preserved'] = $emptyLivestockPurchases;
        }

        // Check for orphaned LivestockBatch records that reference deleted LivestockPurchaseItem
        $orphanedBatches = LivestockBatch::whereNotNull('livestock_purchase_item_id')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('livestock_purchase_items')
                    ->whereColumn('livestock_purchase_items.id', 'livestock_batches.livestock_purchase_item_id');
            })->withTrashed()->count();

        if ($orphanedBatches > 0) {
            // These should already be deleted in clearLivestockData(), but double-check
            LivestockBatch::whereNotNull('livestock_purchase_item_id')
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('livestock_purchase_items')
                        ->whereColumn('livestock_purchase_items.id', 'livestock_batches.livestock_purchase_item_id');
                })->withTrashed()->forceDelete();

            $fixes['orphaned_livestock_batches'] = $orphanedBatches;
            Log::warning("🗑️ Deleted {$orphanedBatches} orphaned livestock batches");
        }

        // Final verification - ensure purchase data is preserved
        $livestockPurchaseCount = LivestockPurchase::withTrashed()->count();
        $livestockPurchaseItemCount = LivestockPurchaseItem::withTrashed()->count();
        $feedPurchaseCount = FeedPurchase::withTrashed()->count();
        $feedPurchaseItemCount = FeedPurchaseItem::withTrashed()->count();
        $supplyPurchaseCount = SupplyPurchase::withTrashed()->count();
        $supplyPurchaseBatchCount = SupplyPurchaseBatch::withTrashed()->count();

        Log::info('✅ Purchase data preserved after integrity check', [
            'livestock_purchases' => $livestockPurchaseCount,
            'livestock_purchase_items' => $livestockPurchaseItemCount,
            'feed_purchases' => $feedPurchaseCount,
            'feed_purchase_items' => $feedPurchaseItemCount,
            'supply_purchases' => $supplyPurchaseCount,
            'supply_purchase_batches' => $supplyPurchaseBatchCount,
        ]);

        if (empty($fixes)) {
            Log::info('✅ Purchase data integrity check passed - no issues found');
        } else {
            Log::info('🔧 Purchase data integrity fixes applied', $fixes);
        }

        return $fixes;
    }

    /**
     * Change all purchase statuses to draft
     * Updated to use new FeedPurchase structure
     */
    private function changePurchaseStatusesToDraft(): array
    {
        $changed = [];

        // Change livestock purchases to draft
        $livestockPurchaseCount = LivestockPurchase::where('status', '!=', 'draft')->count();
        LivestockPurchase::where('status', '!=', 'draft')->update(['status' => 'draft']);
        $changed['livestock_purchases'] = $livestockPurchaseCount;

        // Change feed purchases to draft (NEW STRUCTURE - FeedPurchase is the header)
        $feedPurchaseCount = FeedPurchase::where('status', '!=', 'draft')->count();
        FeedPurchase::where('status', '!=', 'draft')->update(['status' => 'draft']);
        $changed['feed_purchases'] = $feedPurchaseCount;

        // Change supply purchase batches to draft
        $supplyPurchaseBatchCount = SupplyPurchaseBatch::where('status', '!=', 'draft')->count();
        SupplyPurchaseBatch::where('status', '!=', 'draft')->update(['status' => 'draft']);
        $changed['supply_purchase_batches'] = $supplyPurchaseBatchCount;

        Log::info('🔄 Purchase statuses changed to draft', $changed);

        return $changed;
    }

    /**
     * Get summary of what will be cleared (for preview)
     * Updated to use new FeedPurchase and FeedPurchaseItem structure
     */
    public function getPreviewSummary(): array
    {
        return [
            'transaction_records' => [
                'recordings' => Recording::withTrashed()->count(),
                'livestock_depletion' => LivestockDepletion::withTrashed()->count(),
                'livestock_sales' => LivestockSales::withTrashed()->count(),
                'sales_transactions' => SalesTransaction::withTrashed()->count(),
                'livestock_costs' => LivestockCost::withTrashed()->count(),
            ],
            'usage_data' => [
                'feed_usage' => FeedUsage::withTrashed()->count(),
                'supply_usage' => SupplyUsage::withTrashed()->count(),
                'feed_mutations' => FeedMutation::withTrashed()->count(),
                'supply_mutations' => SupplyMutation::withTrashed()->count(),
                'livestock_mutations' => LivestockMutation::withTrashed()->count(),
            ],
            'stock_data' => [
                'feed_stocks' => FeedStock::withTrashed()->count(),
                'supply_stocks' => SupplyStock::withTrashed()->count(),
            ],
            'analytics_data' => [
                'daily_analytics' => DailyAnalytics::withTrashed()->count(),
                'period_analytics' => PeriodAnalytics::withTrashed()->count(),
                'analytics_alerts' => AnalyticsAlert::withTrashed()->count(),
            ],
            'transaction_histories' => [
                'feed_transaction_histories' => FeedTransactionHistory::withTrashed()->count(),
            ],
            'status_history_data' => [
                'livestock_purchase_status_history' => LivestockPurchaseStatusHistory::withTrashed()->count(),
                'feed_status_history' => FeedStatusHistory::withTrashed()->count(),
                'supply_status_history' => SupplyStatusHistory::withTrashed()->count(),
            ],
            'current_data' => [
                'current_feed' => CurrentFeed::withTrashed()->count(),
                'current_livestock' => CurrentLivestock::withTrashed()->count(),
                'current_supply' => CurrentSupply::withTrashed()->count(),
            ],
            'livestock_to_delete' => [
                'livestock' => Livestock::withTrashed()->count(),
                'livestock_batches' => LivestockBatch::withTrashed()->count(),
                'coops_to_reset' => Coop::whereNotNull('livestock_id')->count(),
            ],
            'purchase_data_preserved' => [
                'livestock_purchases' => DB::table('livestock_purchases')->count(),
                'feed_purchases' => DB::table('feed_purchases')->count(),
                'feed_purchase_items' => DB::table('feed_purchase_items')->count(),
                'supply_purchases' => DB::table('supply_purchases')->count(),
            ],
            'purchase_status_to_change' => [
                'livestock_purchases_non_draft' => LivestockPurchase::where('status', '!=', 'draft')->count(),
                'feed_purchases_non_draft' => FeedPurchase::where('status', '!=', 'draft')->count(),
                'supply_purchase_batches_non_draft' => SupplyPurchaseBatch::where('status', '!=', 'draft')->count(),
            ],
            'integrity_issues_detected' => [
                'orphaned_feed_purchase_items' => FeedPurchaseItem::whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('feed_purchases')
                        ->whereColumn('feed_purchases.id', 'feed_purchase_items.feed_purchase_id');
                })->withTrashed()->count(),
                'empty_feed_purchases' => FeedPurchase::whereDoesntHave('feedPurchaseItems')->withTrashed()->count(),
                'orphaned_supply_purchases' => SupplyPurchase::whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('supply_purchase_batches')
                        ->whereColumn('supply_purchase_batches.id', 'supply_purchases.supply_purchase_batch_id');
                })->withTrashed()->count(),
                'empty_supply_batches' => SupplyPurchaseBatch::whereDoesntHave('supplyPurchases')->withTrashed()->count(),
                'orphaned_livestock_purchase_items_preserved' => LivestockPurchaseItem::whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('livestock_purchases')
                        ->whereColumn('livestock_purchases.id', 'livestock_purchase_items.livestock_purchase_id');
                })->withTrashed()->count(),
                'empty_livestock_purchases_preserved' => LivestockPurchase::whereDoesntHave('details')->withTrashed()->count(),
                'orphaned_livestock_batches' => LivestockBatch::whereNotNull('livestock_purchase_item_id')
                    ->whereNotExists(function ($query) {
                        $query->select(DB::raw(1))
                            ->from('livestock_purchase_items')
                            ->whereColumn('livestock_purchase_items.id', 'livestock_batches.livestock_purchase_item_id');
                    })->withTrashed()->count(),
            ]
        ];
    }
}

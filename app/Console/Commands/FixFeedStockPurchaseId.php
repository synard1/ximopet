<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\FeedStock;
use App\Models\FeedPurchaseItem;
use App\Models\FeedPurchase;
use App\Models\Feed;
use App\Models\Unit;
use App\Models\Livestock;
use App\Models\Partner;

class FixFeedStockPurchaseId extends Command
{
    protected $signature = 'feed:fix-feedstock-purchase-id 
        {--dry-run : Show what would be fixed without making changes}
        {--batch-size=100 : Number of records to process in each batch}';

    protected $description = 'Fix FeedStock: ensure feed_purchase_id is set to FeedPurchase (header), and feed_purchase_item_id is stored in data column.';

    protected $stats = [
        'total_processed' => 0,
        'fixed' => 0,
        'skipped' => 0,
        'errors' => 0,
        'dry_run' => false
    ];

    protected $results = [];

    public function handle()
    {
        $this->stats['dry_run'] = $this->option('dry-run');
        $batchSize = (int) $this->option('batch-size');
        $this->info('🔧 FeedStock PurchaseId/Data Fix Tool');
        $this->info('====================================');
        if ($this->stats['dry_run']) {
            $this->warn('⚠️  DRY RUN MODE - No changes will be made');
        }

        // Query FeedStock where feed_purchase_id is null OR data is empty
        $query = FeedStock::query()
            ->where(function ($q) {
                $q->whereNull('feed_purchase_id')
                    ->orWhereNull('data')
                    ->orWhere('data', '{}')
                    ->orWhere('data', '[]')
                    ->orWhere('data', '');
            });

        $totalRecords = $query->count();
        if ($totalRecords === 0) {
            $this->info('✅ No FeedStock records found needing fix.');
            return 0;
        }
        $this->info("📊 Found {$totalRecords} FeedStock records to process");
        $bar = $this->output->createProgressBar($totalRecords);
        $bar->start();
        $query->chunk($batchSize, function ($stocks) use ($bar) {
            foreach ($stocks as $stock) {
                $this->processStock($stock);
                $bar->advance();
            }
        });
        $bar->finish();
        $this->newLine();
        $this->showResults();
        return 0;
    }

    /**
     * Process a single FeedStock record: ensure feed_purchase_id is set to FeedPurchase (header),
     * and feed_purchase_item_id is stored in data JSON.
     */
    protected function processStock(FeedStock $stock)
    {
        $this->stats['total_processed']++;
        $result = [
            'feed_stock_id' => $stock->id,
            'old_feed_purchase_id' => $stock->feed_purchase_id,
            'new_feed_purchase_id' => null,
            'feed_purchase_item_id' => null,
            'status' => '',
            'error' => '',
        ];
        try {
            $purchaseItem = null;
            // Case 1: feed_purchase_id is set (try as FeedPurchaseItem)
            if ($stock->feed_purchase_id) {
                $purchaseItem = FeedPurchaseItem::find($stock->feed_purchase_id);
            }
            // Case 2: feed_purchase_id is null, try to find FeedPurchaseItem by source_id (if source_type is purchase)
            if (!$purchaseItem && $stock->source_type === 'purchase' && $stock->source_id) {
                $purchaseItem = FeedPurchaseItem::find($stock->source_id);
            }
            // Case 3: Try to find FeedPurchaseItem by feed_id, livestock_id, and date (fallback, not always unique)
            if (!$purchaseItem && $stock->feed_id && $stock->livestock_id && $stock->date) {
                $purchaseItem = FeedPurchaseItem::where('feed_id', $stock->feed_id)
                    ->whereHas('feedPurchase', function ($q) use ($stock) {
                        $q->whereDate('date', $stock->date);
                    })
                    ->first();
            }
            if (!$purchaseItem) {
                $this->stats['skipped']++;
                $result['status'] = 'Skipped';
                $result['error'] = 'FeedPurchaseItem not found (by id/source_id/feed+date)';
                Log::warning('FeedPurchaseItem not found for FeedStock', [
                    'feed_stock_id' => $stock->id,
                    'feed_purchase_id' => $stock->feed_purchase_id,
                    'source_id' => $stock->source_id,
                    'feed_id' => $stock->feed_id,
                    'date' => $stock->date,
                ]);
                $this->results[] = $result;
                return;
            }
            $feedPurchase = $purchaseItem->feedPurchase;
            if (!$feedPurchase) {
                $this->stats['skipped']++;
                $result['status'] = 'Skipped';
                $result['error'] = 'FeedPurchase (header) not found';
                Log::warning('FeedPurchase not found for FeedPurchaseItem', [
                    'feed_stock_id' => $stock->id,
                    'feed_purchase_item_id' => $purchaseItem->id
                ]);
                $this->results[] = $result;
                return;
            }
            $feed = $purchaseItem->feed;
            $unit = $purchaseItem->unit;
            $convertedUnit = $purchaseItem->convertedUnit;
            $livestock = $stock->livestock;
            $supplier = $feedPurchase->supplier;
            $expedition = $feedPurchase->expedition;
            // Step 2: Build data column (store both header and item IDs)
            $data = [
                'feed_purchase_item_id' => $purchaseItem->id, // always store item ID in data
                'feed_purchase_id' => $feedPurchase->id,      // header ID (for reporting)
                'feed_purchase_number' => $feedPurchase->number_full,
                'supplier_id' => $feedPurchase->supplier_id,
                'supplier_name' => $supplier ? $supplier->name : null,
                'expedition_id' => $feedPurchase->expedition_id,
                'expedition_name' => $expedition ? $expedition->name : null,
                'expedition_fee' => $feedPurchase->expedition_fee,
                'date' => $feedPurchase->date ? $feedPurchase->date->toDateString() : null,
                'invoice_number' => $feedPurchase->invoice_number,
                'feed_id' => $feed ? $feed->id : null,
                'feed_name' => $feed ? $feed->name : null,
                'feed_code' => $feed ? $feed->code : null,
                'unit_id' => $unit ? $unit->id : null,
                'unit_name' => $unit ? $unit->name : null,
                'converted_unit_id' => $convertedUnit ? $convertedUnit->id : null,
                'converted_unit_name' => $convertedUnit ? $convertedUnit->name : null,
                'quantity' => $purchaseItem->quantity,
                'converted_quantity' => $purchaseItem->converted_quantity,
                'price_per_unit' => $purchaseItem->price_per_unit,
                'price_per_converted_unit' => $purchaseItem->price_per_converted_unit,
                'livestock_id' => $livestock ? $livestock->id : null,
                'livestock_name' => $livestock ? $livestock->name : null,
            ];
            $result['new_feed_purchase_id'] = $feedPurchase->id;
            $result['feed_purchase_item_id'] = $purchaseItem->id;
            // Step 3: Update FeedStock
            if (!$this->stats['dry_run']) {
                $stock->feed_purchase_id = $feedPurchase->id; // always set to header ID
                $stock->data = $data;
                $stock->save();
                $this->stats['fixed']++;
                $result['status'] = 'Fixed';
            } else {
                $this->stats['fixed']++;
                $result['status'] = 'Would Fix';
            }
            Log::info('FeedStock fixed', [
                'feed_stock_id' => $stock->id,
                'old_feed_purchase_id' => $result['old_feed_purchase_id'],
                'new_feed_purchase_id' => $feedPurchase->id,
                'feed_purchase_item_id' => $purchaseItem->id,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            $this->stats['errors']++;
            $result['status'] = 'Error';
            $result['error'] = $e->getMessage();
            Log::error('Error fixing FeedStock', [
                'feed_stock_id' => $stock->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
        $this->results[] = $result;
    }

    protected function showResults()
    {
        $this->info('====================================');
        $this->info('Process complete.');
        $this->info('Total processed: ' . $this->stats['total_processed']);
        $this->info('Fixed: ' . $this->stats['fixed']);
        $this->info('Skipped: ' . $this->stats['skipped']);
        $this->info('Errors: ' . $this->stats['errors']);
        if ($this->stats['dry_run']) {
            $this->warn('Dry run mode: No data was actually updated.');
        }
        if (!empty($this->results)) {
            $this->table(
                ['FeedStock ID', 'Old Purchase Header ID', 'New Purchase Header ID', 'FeedPurchaseItem ID', 'Status', 'Error'],
                array_map(function ($r) {
                    return [
                        $r['feed_stock_id'],
                        $r['old_feed_purchase_id'],
                        $r['new_feed_purchase_id'],
                        $r['feed_purchase_item_id'],
                        $r['status'],
                        $r['error'],
                    ];
                }, $this->results)
            );
        }
    }
}

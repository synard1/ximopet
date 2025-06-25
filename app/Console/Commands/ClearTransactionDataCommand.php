<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TransactionClearService;

class ClearTransactionDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'transaction:clear 
                            {--preview : Show preview of what will be cleared without executing}
                            {--force : Force clear without confirmation}';

    /**
     * The console command description.
     */
    protected $description = 'Clear all transaction data while preserving purchase data and reset livestock to initial state';

    /**
     * The transaction clear service instance.
     */
    protected TransactionClearService $clearService;

    /**
     * Create a new command instance.
     */
    public function __construct(TransactionClearService $clearService)
    {
        parent::__construct();
        $this->clearService = $clearService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Show preview if requested
        if ($this->option('preview')) {
            return $this->showPreview();
        }

        // Warning message
        $this->warn('⚠️  WARNING: This will clear ALL transaction data!');
        $this->warn('📋 This includes: recordings, feed usage, supply usage, livestock depletion, sales, etc.');
        $this->info('✅ Purchase data will be preserved.');
        $this->info('🔄 Livestock will be reset to initial purchase state.');

        // Confirmation unless forced
        if (!$this->option('force')) {
            if (!$this->confirm('Are you sure you want to proceed?')) {
                $this->info('Operation cancelled.');
                return 0;
            }

            // Double confirmation for safety
            if (!$this->confirm('This action cannot be undone. Continue?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        // Execute clearing
        $this->info('🧹 Starting transaction data clearing...');

        $result = $this->clearService->clearAllTransactionData();

        if ($result['success']) {
            $this->info('✅ ' . $result['message']);
            $this->displayClearingResults($result);
        } else {
            $this->error('❌ ' . $result['message']);

            if (!empty($result['errors'])) {
                $this->error('Errors encountered:');
                foreach ($result['errors'] as $error) {
                    $this->error('- ' . $error);
                }
            }

            return 1;
        }

        return 0;
    }

    /**
     * Show preview of what will be cleared
     */
    private function showPreview(): int
    {
        $this->info('🔍 Preview: Data that will be cleared');

        $preview = $this->clearService->getPreviewSummary();

        // Transaction records
        $this->info('📝 Transaction Records:');
        $this->table(
            ['Type', 'Count'],
            [
                ['Recordings', $preview['transaction_records']['recordings']],
                ['Livestock Depletion', $preview['transaction_records']['livestock_depletion']],
                ['Livestock Sales', $preview['transaction_records']['livestock_sales']],
                ['Sales Transactions', $preview['transaction_records']['sales_transactions']],
                ['OVK Records', $preview['transaction_records']['ovk_records']],
                ['Livestock Costs', $preview['transaction_records']['livestock_costs']],
            ]
        );

        // Usage data
        $this->info('🔄 Usage & Mutation Data:');
        $this->table(
            ['Type', 'Count'],
            [
                ['Feed Usage', $preview['usage_data']['feed_usage']],
                ['Supply Usage', $preview['usage_data']['supply_usage']],
                ['Feed Mutations', $preview['usage_data']['feed_mutations']],
                ['Supply Mutations', $preview['usage_data']['supply_mutations']],
                ['Livestock Mutations', $preview['usage_data']['livestock_mutations']],
            ]
        );

        // Stock data
        $this->info('📦 Stock Data:');
        $this->table(
            ['Type', 'Count'],
            [
                ['Feed Stocks', $preview['stock_data']['feed_stocks']],
                ['Supply Stocks', $preview['stock_data']['supply_stocks']],
            ]
        );

        // Analytics data
        $this->info('📊 Analytics Data:');
        $this->table(
            ['Type', 'Count'],
            [
                ['Daily Analytics', $preview['analytics_data']['daily_analytics']],
                ['Period Analytics', $preview['analytics_data']['period_analytics']],
                ['Analytics Alerts', $preview['analytics_data']['analytics_alerts']],
            ]
        );

        // Livestock to reset
        $this->info('🐔 Livestock to Reset: ' . $preview['livestock_to_reset']);

        // Preserved data
        $this->info('✅ Purchase Data (PRESERVED):');
        $this->table(
            ['Type', 'Count'],
            [
                ['Livestock Purchases', $preview['purchase_data_preserved']['livestock_purchases']],
                ['Feed Purchases', $preview['purchase_data_preserved']['feed_purchases']],
                ['Supply Purchases', $preview['purchase_data_preserved']['supply_purchases']],
            ]
        );

        $this->warn('💡 Run without --preview flag to execute the clearing.');

        return 0;
    }

    /**
     * Display the results of clearing operation
     */
    private function displayClearingResults(array $result): void
    {
        $this->info('📊 Clearing Results:');

        // Display cleared data
        if (!empty($result['cleared_data'])) {
            $this->table(
                ['Type', 'Cleared Count'],
                [
                    ['Recordings', $result['cleared_data']['recordings'] ?? 0],
                    ['Livestock Depletion', $result['cleared_data']['livestock_depletion'] ?? 0],
                    ['Livestock Sales', $result['cleared_data']['livestock_sales'] ?? 0],
                    ['Sales Transactions', $result['cleared_data']['sales_transactions'] ?? 0],
                    ['OVK Records', $result['cleared_data']['ovk_records'] ?? 0],
                    ['Livestock Costs', $result['cleared_data']['livestock_costs'] ?? 0],
                ]
            );
        }

        // Display restored livestock
        if (!empty($result['restored_livestock'])) {
            $this->info('🐔 Restored Livestock: ' . count($result['restored_livestock']));

            // Show sample of restored livestock
            $sampleSize = min(5, count($result['restored_livestock']));
            $sample = array_slice($result['restored_livestock'], 0, $sampleSize);

            $this->table(
                ['ID', 'Name', 'Old Depletion', 'Old Sales', 'Old Mutated'],
                array_map(function ($livestock) {
                    return [
                        $livestock['id'],
                        $livestock['name'],
                        $livestock['old_data']['quantity_depletion'],
                        $livestock['old_data']['quantity_sales'],
                        $livestock['old_data']['quantity_mutated'],
                    ];
                }, $sample)
            );

            if (count($result['restored_livestock']) > $sampleSize) {
                $remaining = count($result['restored_livestock']) - $sampleSize;
                $this->info("... and {$remaining} more livestock restored");
            }
        }

        $this->info('✨ All transaction data cleared and livestock restored to initial purchase state!');
    }
}

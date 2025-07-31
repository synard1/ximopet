<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\RecordingSale;
use App\Models\RecordingSaleItem;

class FixRecordingSalesDataCommand extends Command
{
    protected $signature = 'recording:sales:fix-data {--dry-run : Show what would be fixed without making changes}';
    protected $description = 'Fix inconsistent data in recording_sales and recording_sale_items tables';

    public function handle()
    {
        $this->info('🔧 Starting RecordingSales data consistency fix...');

        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('⚠️  DRY RUN MODE - No changes will be made');
        }

        try {
            // Find headers with inconsistent data
            $inconsistentHeaders = $this->findInconsistentHeaders();

            if ($inconsistentHeaders->isEmpty()) {
                $this->info('✅ No inconsistent data found!');
                return 0;
            }

            $this->info("📊 Found {$inconsistentHeaders->count()} headers with inconsistent data");

            $fixedCount = 0;
            $errorCount = 0;

            foreach ($inconsistentHeaders as $header) {
                $this->line("Processing header: {$header->id}");

                try {
                    $result = $this->fixHeaderData($header, $isDryRun);

                    if ($result['success']) {
                        $fixedCount++;
                        $this->info("✅ Fixed header: {$header->id}");
                        $this->line("   - Quantity: {$result['old_quantity']} → {$result['new_quantity']}");
                        $this->line("   - Weight: {$result['old_weight']} → {$result['new_weight']}");
                        $this->line("   - Batch count: {$result['old_batch_count']} → {$result['new_batch_count']}");
                    } else {
                        $errorCount++;
                        $this->error("❌ Failed to fix header: {$header->id} - {$result['error']}");
                    }
                } catch (\Exception $e) {
                    $errorCount++;
                    $this->error("❌ Error processing header {$header->id}: {$e->getMessage()}");
                }
            }

            $this->newLine();
            $this->info("📈 Summary:");
            $this->line("   - Total headers processed: {$inconsistentHeaders->count()}");
            $this->line("   - Successfully fixed: {$fixedCount}");
            $this->line("   - Errors: {$errorCount}");

            if ($isDryRun) {
                $this->warn('⚠️  This was a dry run. Run without --dry-run to apply changes.');
            } else {
                $this->info('✅ Data consistency fix completed!');
            }

            return 0;
        } catch (\Exception $e) {
            $this->error("❌ Fatal error: {$e->getMessage()}");
            Log::error('FixRecordingSalesDataCommand failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 1;
        }
    }

    private function findInconsistentHeaders()
    {
        return RecordingSale::where('is_header', true)
            ->whereHas('items')
            ->with('items')
            ->get()
            ->filter(function ($header) {
                $items = $header->items;

                if ($items->isEmpty()) {
                    return false; // Skip headers without items
                }

                $actualQuantity = $items->sum('quantity');
                $actualWeight = $items->sum('weight');
                $actualBatchCount = $items->count();

                // Check for inconsistencies
                $quantityMismatch = $header->quantity != $actualQuantity || $header->total_quantity != $actualQuantity;
                $weightMismatch = abs($header->weight - $actualWeight) > 0.01 || abs($header->total_weight - $actualWeight) > 0.01;
                $batchCountMismatch = $header->batch_count != $actualBatchCount;

                return $quantityMismatch || $weightMismatch || $batchCountMismatch;
            });
    }

    private function fixHeaderData(RecordingSale $header, bool $isDryRun): array
    {
        $items = $header->items;

        if ($items->isEmpty()) {
            return ['success' => false, 'error' => 'No items found for header'];
        }

        $actualQuantity = $items->sum('quantity');
        $actualWeight = $items->sum('weight');
        $actualAmount = $items->sum('amount');
        $actualBatchCount = $items->count();

        // Store old values for logging
        $oldData = [
            'quantity' => $header->quantity,
            'weight' => $header->weight,
            'total_quantity' => $header->total_quantity,
            'total_weight' => $header->total_weight,
            'total_amount' => $header->total_amount,
            'batch_count' => $header->batch_count,
        ];

        $newData = [
            'quantity' => $actualQuantity,
            'weight' => $actualWeight,
            'total_quantity' => $actualQuantity,
            'total_weight' => $actualWeight,
            'total_amount' => $actualAmount,
            'batch_count' => $actualBatchCount,
            'metadata' => array_merge($header->metadata ?? [], [
                'data_fix' => [
                    'fixed_at' => now()->toIso8601String(),
                    'fixed_by' => 'FixRecordingSalesDataCommand',
                    'old_data' => $oldData,
                    'new_data' => [
                        'quantity' => $actualQuantity,
                        'weight' => $actualWeight,
                        'total_quantity' => $actualQuantity,
                        'total_weight' => $actualWeight,
                        'total_amount' => $actualAmount,
                        'batch_count' => $actualBatchCount,
                    ],
                ],
            ]),
            'updated_by' => $header->created_by, // Use original creator
        ];

        if (!$isDryRun) {
            $header->update($newData);
        }

        return [
            'success' => true,
            'old_quantity' => $oldData['quantity'],
            'new_quantity' => $actualQuantity,
            'old_weight' => $oldData['weight'],
            'new_weight' => $actualWeight,
            'old_batch_count' => $oldData['batch_count'],
            'new_batch_count' => $actualBatchCount,
        ];
    }
}

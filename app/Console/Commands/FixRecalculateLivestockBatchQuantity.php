<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\LivestockBatch;
use App\Models\LivestockDepletion;
use App\Models\LivestockMutation;
use App\Models\LivestockSalesItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class FixRecalculateLivestockBatchQuantity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:recalculate-livestock-batch-quantity 
                            {--livestock_id=* : The ID of the livestock to process}
                            {--batch_id=* : The ID of the specific batch to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculates the available quantity for LivestockBatches based on actual transactions (depletions, mutations, sales).';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting Livestock Batch Quantity Recalculation...');

        $batchIds = $this->option('batch_id');
        $livestockIds = $this->option('livestock_id');

        $query = LivestockBatch::query();

        if (!empty($batchIds)) {
            $this->info('Processing specific batch IDs: ' . implode(', ', $batchIds));
            $query->whereIn('id', $batchIds);
        } elseif (!empty($livestockIds)) {
            $this->info('Processing batches for livestock IDs: ' . implode(', ', $livestockIds));
            $query->whereIn('livestock_id', $livestockIds);
        } else {
            $this->info('No specific IDs provided. Processing all active livestock batches.');
            // Optionally, you can filter for active batches, e.g., ->where('status', 'active')
        }

        $batches = $query->get();

        if ($batches->isEmpty()) {
            $this->warn('No batches found to process.');
            return 0;
        }

        $this->info("Found {$batches->count()} batch(es) to process.");
        $progressBar = $this->output->createProgressBar($batches->count());
        $updatedCount = 0;
        $mismatchDetails = [];

        DB::beginTransaction();
        try {
            foreach ($batches as $batch) {
                $initialQuantity = $batch->initial_quantity ?? 0;

                // 1. Calculate total depletions for this livestock since batch start date
                $totalDepletion = LivestockDepletion::where('livestock_id', $batch->livestock_id)
                    ->where('tanggal', '>=', $batch->start_date)
                    ->sum('jumlah');

                // 2. Calculate total mutations out (from this livestock to other livestock)
                $totalMutationOut = LivestockMutation::where('source_livestock_id', $batch->livestock_id)
                    ->where('direction', 'out')
                    ->where('tanggal', '>=', $batch->start_date)
                    ->sum('jumlah');

                // 3. Calculate total mutations in (from other livestock to this livestock)
                // Note: This is typically handled by creating new batches, but we'll include it for completeness
                $totalMutationIn = LivestockMutation::where('destination_livestock_id', $batch->livestock_id)
                    ->where('direction', 'in')
                    ->where('tanggal', '>=', $batch->start_date)
                    ->sum('jumlah');

                // 4. Calculate total sales
                // Note: livestock_sales_items doesn't have livestock_batch_id, only livestock_id
                // We need to calculate sales based on livestock_id and date range
                $totalSales = LivestockSalesItem::where('livestock_id', $batch->livestock_id)
                    ->where('tanggal', '>=', $batch->start_date)
                    ->sum('quantity');

                // Calculate the new available quantity
                // Formula: initial_quantity - depletions - mutations_out - sales + mutations_in
                $calculatedAvailable = $initialQuantity - $totalDepletion - $totalMutationOut - $totalSales + $totalMutationIn;
                $calculatedAvailable = max(0, $calculatedAvailable); // Ensure it's not negative

                $currentAvailable = $batch->quantity_available ?? 0;

                $line = "Batch #{$batch->id} ({$batch->name}) | Livestock ID: {$batch->livestock_id}";

                if ($currentAvailable != $calculatedAvailable) {
                    $updatedCount++;
                    $batch->quantity_available = $calculatedAvailable;
                    $batch->quantity_depletion = $totalDepletion;
                    $batch->quantity_mutated = $totalMutationOut;
                    $batch->quantity_sales = $totalSales;
                    $batch->save();

                    $this->line("\n<fg=yellow>$line</>");
                    $this->line("<fg=yellow>  └─ MISMATCH: DB: {$currentAvailable} | Calculated: {$calculatedAvailable}. UPDATING...</>");
                    $this->line("<fg=yellow>  └─ Details: Initial: {$initialQuantity}, Depletion: {$totalDepletion}, Mutation Out: {$totalMutationOut}, Sales: {$totalSales}, Mutation In: {$totalMutationIn}</>");
                } else {
                    $this->line("\n<fg=green>$line</>");
                    $this->line("<fg=green>  └─ OK: DB: {$currentAvailable} | Calculated: {$calculatedAvailable}.</>");
                    $this->line("<fg=green>  └─ Details: Initial: {$initialQuantity}, Depletion: {$totalDepletion}, Mutation Out: {$totalMutationOut}, Sales: {$totalSales}, Mutation In: {$totalMutationIn}</>");
                }

                $progressBar->advance();
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("\nAn error occurred during processing: " . $e->getMessage());
            return 1;
        }

        $progressBar->finish();
        $this->info("\n\nRecalculation complete.");
        if ($updatedCount > 0) {
            $this->warn("Summary: {$updatedCount} out of {$batches->count()} batches were updated.");
        } else {
            $this->info("Summary: All {$batches->count()} processed batches were already in sync.");
        }

        return 0;
    }
}

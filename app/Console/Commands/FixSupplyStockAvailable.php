<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SupplyStock;

class FixSupplyStockAvailable extends Command
{
    protected $signature = 'supply:fix-stock-available
        {--dry-run : Show what would be fixed without making changes}
        {--farm-id= : Filter by specific farm ID}
        {--supply-id= : Filter by specific supply ID}';

    protected $description = 'Recalculate quantity_available for all SupplyStock records (with optional filters).';

    public function handle()
    {
        $this->info('🔧 Starting SupplyStock quantity_available recalculation');
        $query = SupplyStock::query();
        if ($this->option('farm-id')) {
            $query->where('farm_id', $this->option('farm-id'));
        }
        if ($this->option('supply-id')) {
            $query->where('supply_id', $this->option('supply-id'));
        }
        $total = $query->count();
        if ($total === 0) {
            $this->warn('No SupplyStock records found matching the criteria');
            return 0;
        }
        $this->info("Found $total SupplyStock records to process");
        $bar = $this->output->createProgressBar($total);
        $bar->start();
        $fixed = 0;
        $skipped = 0;
        $errors = 0;
        $details = [];
        $query->chunk(100, function ($stocks) use (&$fixed, &$skipped, &$errors, &$details, $bar) {
            foreach ($stocks as $stock) {
                try {
                    $old = $stock->quantity_available;
                    $new = $stock->quantity_in - $stock->quantity_used - $stock->quantity_mutated - $stock->quantity_reserved;
                    if ($old !== $new) {
                        $details[] = [
                            'id' => $stock->id,
                            'old' => $old,
                            'new' => $new,
                        ];
                        if (!$this->option('dry-run')) {
                            $stock->quantity_available = $new;
                            $stock->save();
                        }
                        $fixed++;
                    } else {
                        $skipped++;
                    }
                } catch (\Exception $e) {
                    $errors++;
                    $this->error("Error on stock {$stock->id}: {$e->getMessage()}");
                }
                $bar->advance();
            }
        });
        $bar->finish();
        $this->newLine();
        $this->info("\nRecalculation complete. Fixed: $fixed, Skipped: $skipped, Errors: $errors");
        if (!empty($details)) {
            $this->table(['ID', 'Old Available', 'New Available'], $details);
        }
        return 0;
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Livestock;
use App\Models\LivestockDepletion;
use App\Models\CurrentLivestock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateLivestockQuantityDepletionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'livestock:update-quantity-depletion 
                            {--dry-run : Show what would be updated without making changes}
                            {--livestock-id= : Update specific livestock ID only}
                            {--force : Force update even if no changes detected}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update quantity_depletion in Livestock table based on LivestockDepletion records and recalculate CurrentLivestock';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $livestockId = $this->option('livestock-id');
        $force = $this->option('force');

        $this->info('🚀 Starting Livestock Quantity Depletion Update Process');
        $this->info('📊 Mode: ' . ($isDryRun ? 'DRY RUN (no changes will be made)' : 'LIVE UPDATE'));

        if ($livestockId) {
            $this->info('🎯 Targeting specific livestock: ' . $livestockId);
        }

        $livestocksQuery = Livestock::query();
        if ($livestockId) {
            $livestocksQuery->where('id', $livestockId);
        }

        $livestocks = $livestocksQuery->get();
        $totalLivestock = $livestocks->count();
        $updatedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;

        $this->info("📋 Found {$totalLivestock} livestock records to process");

        $progressBar = $this->output->createProgressBar($totalLivestock);
        $progressBar->start();

        foreach ($livestocks as $livestock) {
            try {
                $result = $this->updateLivestockQuantities($livestock, $isDryRun, $force);

                if ($result['updated']) {
                    $updatedCount++;
                } else {
                    $skippedCount++;
                }

                $progressBar->advance();
            } catch (\Exception $e) {
                $errorCount++;
                Log::error('Error updating livestock quantities', [
                    'livestock_id' => $livestock->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                $this->error("\nError processing livestock {$livestock->id}: " . $e->getMessage());
                $progressBar->advance();
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        // Summary
        $this->info('✅ Update Process Completed');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Livestock', $totalLivestock],
                ['Updated', $updatedCount],
                ['Skipped (no changes)', $skippedCount],
                ['Errors', $errorCount],
            ]
        );

        if ($isDryRun) {
            $this->warn('🔍 This was a DRY RUN - no actual changes were made');
            $this->info('💡 Run without --dry-run to apply changes');
        }

        return 0;
    }

    /**
     * Update livestock quantities for a single livestock
     * 
     * @param Livestock $livestock
     * @param bool $isDryRun
     * @param bool $force
     * @return array
     */
    private function updateLivestockQuantities(Livestock $livestock, bool $isDryRun = false, bool $force = false): array
    {
        // Calculate total depletion from LivestockDepletion records
        $totalDepletion = LivestockDepletion::where('livestock_id', $livestock->id)->sum('jumlah');

        // Get current quantity_depletion in Livestock
        $currentQuantityDepletion = $livestock->quantity_depletion ?? 0;

        // Check if update is needed
        $needsUpdate = $force || ($totalDepletion != $currentQuantityDepletion);

        if (!$needsUpdate) {
            return [
                'updated' => false,
                'message' => 'No changes needed',
                'current_depletion' => $currentQuantityDepletion,
                'calculated_depletion' => $totalDepletion
            ];
        }

        if ($isDryRun) {
            $this->line("\n🔍 [DRY RUN] Would update livestock: {$livestock->name} ({$livestock->id})");
            $this->line("   Current quantity_depletion: {$currentQuantityDepletion}");
            $this->line("   Calculated depletion: {$totalDepletion}");
            $this->line("   Change: " . ($totalDepletion - $currentQuantityDepletion));

            return [
                'updated' => true, // Mark as updated for counting purposes
                'message' => 'Would be updated (dry run)',
                'current_depletion' => $currentQuantityDepletion,
                'calculated_depletion' => $totalDepletion
            ];
        }

        return DB::transaction(function () use ($livestock, $totalDepletion, $currentQuantityDepletion) {
            // Update Livestock quantity_depletion
            $livestock->update([
                'quantity_depletion' => $totalDepletion,
                'updated_by' => auth()->id() ?? 1 // Use admin user if no auth
            ]);

            // Update CurrentLivestock with real-time calculation
            $this->updateCurrentLivestockQuantity($livestock);

            $this->line("\n✅ Updated livestock: {$livestock->name} ({$livestock->id})");
            $this->line("   Old quantity_depletion: {$currentQuantityDepletion}");
            $this->line("   New quantity_depletion: {$totalDepletion}");
            $this->line("   Change: " . ($totalDepletion - $currentQuantityDepletion));

            Log::info('📊 Updated livestock quantities via command', [
                'livestock_id' => $livestock->id,
                'livestock_name' => $livestock->name,
                'old_quantity_depletion' => $currentQuantityDepletion,
                'new_quantity_depletion' => $totalDepletion,
                'change' => $totalDepletion - $currentQuantityDepletion
            ]);

            return [
                'updated' => true,
                'message' => 'Successfully updated',
                'current_depletion' => $currentQuantityDepletion,
                'calculated_depletion' => $totalDepletion
            ];
        });
    }

    /**
     * Update CurrentLivestock quantity using consistent formula
     * 
     * @param Livestock $livestock
     * @return void
     */
    private function updateCurrentLivestockQuantity(Livestock $livestock): void
    {
        $currentLivestock = CurrentLivestock::where('livestock_id', $livestock->id)->first();
        if (!$currentLivestock) {
            $this->warn("   ⚠️ CurrentLivestock not found for {$livestock->name}");
            return;
        }

        // Calculate real-time quantity using formula: initial_quantity - quantity_depletion - quantity_sales - quantity_mutated
        $calculatedQuantity = $livestock->initial_quantity
            - ($livestock->quantity_depletion ?? 0)
            - ($livestock->quantity_sales ?? 0)
            - ($livestock->quantity_mutated ?? 0);

        // Ensure quantity doesn't go negative
        $calculatedQuantity = max(0, $calculatedQuantity);

        $oldQuantity = $currentLivestock->quantity;

        // Update CurrentLivestock
        $currentLivestock->update([
            'quantity' => $calculatedQuantity,
            'metadata' => array_merge($currentLivestock->metadata ?? [], [
                'last_updated' => now()->toIso8601String(),
                'updated_by' => auth()->id() ?? 1,
                'updated_by_name' => 'System Command',
                'previous_quantity' => $oldQuantity,
                'quantity_change' => $calculatedQuantity - $oldQuantity,
                'calculation_source' => 'update_quantity_depletion_command',
                'formula_breakdown' => [
                    'initial_quantity' => $livestock->initial_quantity,
                    'quantity_depletion' => $livestock->quantity_depletion ?? 0,
                    'quantity_sales' => $livestock->quantity_sales ?? 0,
                    'quantity_mutated' => $livestock->quantity_mutated ?? 0,
                    'calculated_quantity' => $calculatedQuantity
                ]
            ]),
            'updated_by' => auth()->id() ?? 1
        ]);

        $this->line("   📊 CurrentLivestock updated: {$oldQuantity} → {$calculatedQuantity}");
    }
}

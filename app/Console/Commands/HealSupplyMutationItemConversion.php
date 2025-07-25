<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SupplyMutationItem;
use App\Services\Recording\UnitConversionService;
use Illuminate\Support\Facades\Log;

class HealSupplyMutationItemConversion extends Command
{
    protected $signature = 'supply-mutation:heal-conversion {--dry-run}';
    protected $description = 'Fix and heal converted_quantity and converted_unit_id in SupplyMutationItem table';

    public function handle()
    {
        $this->info('Starting healing process for SupplyMutationItem conversion fields...');
        $dryRun = $this->option('dry-run');
        $total = 0;
        $fixed = 0;
        $skipped = 0;

        SupplyMutationItem::chunk(500, function ($items) use (&$total, &$fixed, &$skipped, $dryRun) {
            foreach ($items as $item) {
                $total++;
                $oldConvertedQuantity = $item->converted_quantity;
                $oldConvertedUnitId = $item->converted_unit_id;

                $result = UnitConversionService::getConvertedQuantityAndUnitId(
                    'supply',
                    $item->supply_id,
                    $item->unit_id,
                    $item->quantity
                );

                $newConvertedQuantity = $result['converted_quantity'];
                $newConvertedUnitId = $result['converted_unit_id'];

                // Only update if different
                if (
                    bccomp((string)$oldConvertedQuantity, (string)$newConvertedQuantity, 6) !== 0 ||
                    $oldConvertedUnitId !== $newConvertedUnitId
                ) {
                    $fixed++;
                    $this->line("Fixing ID: {$item->id} | Old: {$oldConvertedQuantity} ({$oldConvertedUnitId}) => New: {$newConvertedQuantity} ({$newConvertedUnitId})");
                    if (!$dryRun) {
                        $item->converted_quantity = $newConvertedQuantity;
                        $item->converted_unit_id = $newConvertedUnitId;
                        $item->save();
                    }
                } else {
                    $skipped++;
                }
            }
        });

        $this->info("Healing process completed.");
        $this->info("Total records checked: $total");
        $this->info("Records fixed: $fixed");
        $this->info("Records already correct: $skipped");
        if ($dryRun) {
            $this->warn("Dry run mode: No data was actually updated.");
        }
        Log::info('[HealSupplyMutationItemConversion] Finished', [
            'total' => $total,
            'fixed' => $fixed,
            'skipped' => $skipped,
            'dry_run' => $dryRun,
        ]);
    }
}

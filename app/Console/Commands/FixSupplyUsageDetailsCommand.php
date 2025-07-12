<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SupplyUsageDetail;
use App\Models\Supply;
use App\Models\SupplyStock;
use Illuminate\Support\Facades\DB;
use App\Services\Recording\UnitConversionService;

class FixSupplyUsageDetailsCommand extends Command
{
    protected $signature = 'fix:supply-usage-details {--dry-run}';
    protected $description = 'Perbaiki converted_unit_id pada SupplyUsageDetail agar selalu satuan terkecil';

    public function handle()
    {
        $this->info('Memulai perbaikan converted_unit_id pada SupplyUsageDetail...');
        $dryRun = $this->option('dry-run');
        $unitService = app(UnitConversionService::class);
        $total = 0;
        $fixed = 0;
        $skipped = 0;
        $details = SupplyUsageDetail::with('supply')->get();
        foreach ($details as $detail) {
            $supply = $detail->supply;
            if (!$supply) {
                $this->warn("[ID: {$detail->id}] Supply tidak ditemukan, skip");
                $skipped++;
                continue;
            }
            $unitInfo = $unitService->getDetailedSupplyUnitInfo($supply, (float)$detail->quantity_taken);
            $smallestUnitId = $unitInfo['smallest_unit_id'] ?? null;
            if (!$smallestUnitId) {
                $this->warn("[ID: {$detail->id}] Tidak ada satuan terkecil di supply {$supply->name}, skip");
                $skipped++;
                continue;
            }
            if ($detail->converted_unit_id != $smallestUnitId) {
                $this->line("[ID: {$detail->id}] converted_unit_id: {$detail->converted_unit_id} => {$smallestUnitId} (supply: {$supply->name})");
                if (!$dryRun) {
                    $detail->converted_unit_id = $smallestUnitId;
                    $detail->save();
                }
                $fixed++;
            } else {
                $skipped++;
            }
            $total++;
        }
        $this->info("Selesai. Total: $total, Fixed: $fixed, Skipped: $skipped");
        if ($dryRun) {
            $this->info('Dry run mode: tidak ada data yang diubah.');
        }
        return 0;
    }

    private function convertToSmallestUnit($supply, $quantity, $unitId)
    {
        if (!$supply || !isset($supply->data['conversion_units'])) return $quantity;
        $units = collect($supply->data['conversion_units']);
        $selectedUnit = $units->firstWhere('unit_id', $unitId);
        $smallestUnit = $units->firstWhere('is_smallest', true);
        if (!$selectedUnit || !$smallestUnit || $smallestUnit['value'] == 0) return $quantity;
        return ($quantity * $selectedUnit['value']) / $smallestUnit['value'];
    }

    private function getSmallestUnitId($supply)
    {
        if (!$supply || !isset($supply->data['conversion_units'])) return null;
        $units = collect($supply->data['conversion_units']);
        $smallestUnit = $units->firstWhere('is_smallest', true);
        return $smallestUnit['unit_id'] ?? null;
    }
}

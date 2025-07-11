<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SupplyUsageDetail;
use App\Models\Supply;
use App\Models\SupplyStock;
use Illuminate\Support\Facades\DB;

class FixSupplyUsageDetailsCommand extends Command
{
    protected $signature = 'fix:supply-usage-details {--fix : Apply fixes to database} {--dry-run : Only show what would be fixed}';
    protected $description = 'Auto-heal/self-healing for invalid supply_usage_details data (NULL fields, broken conversions, etc)';

    public function handle()
    {
        $this->info('Scanning for invalid supply_usage_details...');
        $query = SupplyUsageDetail::query()
            ->whereNull('converted_quantity')
            ->orWhereNull('converted_unit_id')
            ->orWhereNull('price_per_unit')
            ->orWhereNull('price_per_converted_unit')
            ->orWhereNull('total_price')
            ->orWhereNull('notes')
            ->orWhereNull('batch_number')
            ->orWhereNull('expiry_date');

        $count = $query->count();
        if ($count === 0) {
            $this->info('No invalid records found.');
            return 0;
        }
        $this->warn("Found $count invalid records.");
        $details = $query->get();
        $fixed = 0;
        $skipped = 0;
        foreach ($details as $detail) {
            $fix = [];
            $supply = Supply::find($detail->supply_id);
            $supplyStock = SupplyStock::find($detail->supply_stock_id);
            // Fix converted_quantity
            if (is_null($detail->converted_quantity) && $supply && $detail->quantity_taken && $detail->unit_id) {
                $converted = $this->convertToSmallestUnit($supply, $detail->quantity_taken, $detail->unit_id);
                $fix['converted_quantity'] = $converted;
            }
            // Fix converted_unit_id
            if (is_null($detail->converted_unit_id) && $supply) {
                $fix['converted_unit_id'] = $this->getSmallestUnitId($supply);
            }
            // Fix price fields
            if (is_null($detail->price_per_unit) && $supplyStock) {
                $fix['price_per_unit'] = $supplyStock->price_per_unit ?? 0;
            }
            if (is_null($detail->price_per_converted_unit) && $supplyStock) {
                $fix['price_per_converted_unit'] = $supplyStock->price_per_converted_unit ?? 0;
            }
            if (is_null($detail->total_price) && $detail->quantity_taken && $supplyStock) {
                $fix['total_price'] = ($fix['price_per_unit'] ?? $detail->price_per_unit ?? 0) * $detail->quantity_taken;
            }
            // Notes, batch_number, expiry_date: set default if null
            if (is_null($detail->notes)) {
                $fix['notes'] = '';
            }
            if (is_null($detail->batch_number)) {
                $fix['batch_number'] = $supplyStock->batch_number ?? '';
            }
            if (is_null($detail->expiry_date)) {
                $fix['expiry_date'] = $supplyStock->expiry_date ?? null;
            }
            if (count($fix) > 0) {
                $this->line("[ID: {$detail->id}] Will fix: " . json_encode($fix));
                if ($this->option('fix')) {
                    $detail->fill($fix);
                    $detail->save();
                    $fixed++;
                } else {
                    $skipped++;
                }
            }
        }
        $this->info("Summary: {$fixed} fixed, {$skipped} would be fixed (dry-run or not selected)");
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

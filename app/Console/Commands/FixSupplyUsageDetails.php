<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SupplyUsageDetail;
use App\Services\Recording\UnitConversionService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class FixSupplyUsageDetails extends Command
{
    protected $signature = 'fix:supply-usage-details {--date=} {--dry-run}';
    protected $description = 'Fix missing price_per_unit, price_per_converted_unit, and total_price in supply_usage_details';

    public function handle()
    {
        $date = $this->option('date');
        $dryRun = $this->option('dry-run');
        $query = SupplyUsageDetail::query();
        if ($date) {
            $query->whereHas('supplyUsage', function ($q) use ($date) {
                $q->whereDate('usage_date', $date);
            });
        }
        // JANGAN filter field harga di query, ambil semua data (atau filter by date saja)
        $details = $query->with(['supply', 'unit', 'convertedUnit', 'supplyStock.supplyPurchase'])->get();
        $unitService = app(UnitConversionService::class);
        $fixed = 0;
        $skipped = 0;
        $total = 0;
        $this->info('Memulai perbaikan supply_usage_details...');
        foreach ($details as $detail) {
            $total++;
            $old = $detail->toArray();
            $supply = $detail->supply;
            $unit = $detail->unit;
            $convertedUnit = $detail->convertedUnit;
            $stock = $detail->supplyStock;
            $purchase = $stock ? $stock->supplyPurchase : null;

            // Ambil harga satuan
            $pricePerUnit = $detail->price_per_unit;
            $pricePerConvertedUnit = $detail->price_per_converted_unit;
            if (empty($pricePerUnit) || $pricePerUnit == 0 || $pricePerUnit === '0' || $pricePerUnit === '0.00') {
                $pricePerUnit = $purchase->price_per_unit ?? $supply->price ?? 0;
            }
            if (empty($pricePerConvertedUnit) || $pricePerConvertedUnit == 0 || $pricePerConvertedUnit === '0' || $pricePerConvertedUnit === '0.00') {
                $pricePerConvertedUnit = $purchase->price_per_converted_unit ?? $supply->price ?? 0;
            }
            // Konversi quantity jika perlu
            $quantity = (float) $detail->quantity_taken;
            $convertedQuantity = (float) $detail->converted_quantity;
            if ($convertedQuantity == 0 && $unit && $convertedUnit && $unit->id != $convertedUnit->id) {
                $conv = $unitService::getConvertedQuantityAndUnitId('supply', $supply->id, $unit->id, $quantity);
                $convertedQuantity = $conv['converted_quantity'];
            }
            // Hitung total price
            $totalPrice = $convertedQuantity > 0 ? $convertedQuantity * $pricePerConvertedUnit : $quantity * $pricePerUnit;

            // Cek apakah field perlu diperbaiki (NULL, 0, 0.00, string kosong, atau tidak sesuai rumus)
            $update = [];
            $reason = [];
            if (empty($detail->price_per_unit) || $detail->price_per_unit == 0 || $detail->price_per_unit === '0' || $detail->price_per_unit === '0.00' || $detail->price_per_unit != $pricePerUnit) {
                $update['price_per_unit'] = $pricePerUnit;
                $reason[] = 'price_per_unit';
            }
            if (empty($detail->price_per_converted_unit) || $detail->price_per_converted_unit == 0 || $detail->price_per_converted_unit === '0' || $detail->price_per_converted_unit === '0.00' || $detail->price_per_converted_unit != $pricePerConvertedUnit) {
                $update['price_per_converted_unit'] = $pricePerConvertedUnit;
                $reason[] = 'price_per_converted_unit';
            }
            if (empty($detail->converted_quantity) || $detail->converted_quantity == 0 || $detail->converted_quantity === '0' || $detail->converted_quantity === '0.00' || $detail->converted_quantity != $convertedQuantity) {
                $update['converted_quantity'] = $convertedQuantity;
                $reason[] = 'converted_quantity';
            }
            if (empty($detail->total_price) || $detail->total_price == 0 || $detail->total_price === '0' || $detail->total_price === '0.00' || $detail->total_price != $totalPrice) {
                $update['total_price'] = $totalPrice;
                $reason[] = 'total_price';
            }
            if (!empty($update)) {
                $msg = "[FIXED] ID {$detail->id}: ";
                foreach ($update as $field => $newVal) {
                    $msg .= "$field: '" . var_export($detail->$field, true) . "' → '" . var_export($newVal, true) . "', ";
                }
                $msg .= 'Alasan: ' . implode(', ', $reason);
                if ($dryRun) {
                    $this->line("[DRY RUN] $msg");
                } else {
                    $detail->update($update);
                    Log::info('Fixed SupplyUsageDetail', ['id' => $detail->id, 'before' => $old, 'after' => $update]);
                    $this->line($msg);
                }
                $fixed++;
            } else {
                $this->line("[SKIPPED] ID {$detail->id}: Semua field sudah benar.");
                $skipped++;
            }
        }
        $this->info("Selesai. Total: $total, Fixed: $fixed, Skipped: $skipped");
        if ($dryRun) {
            $this->info('Dry run mode: tidak ada data yang diubah.');
        }
    }
}

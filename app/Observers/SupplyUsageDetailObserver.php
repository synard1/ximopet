<?php

namespace App\Observers;

use App\Models\LivestockCost;

class SupplyUsageDetailObserver
{
    public function saved($detail)
    {
        if (method_exists($detail, 'supplyUsage') && $detail->supplyUsage) {
            $livestockId = $detail->supplyUsage->livestock_id;
            $tanggal = $detail->supplyUsage->usage_date;
            if ($livestockId && $tanggal) {
                $cost = LivestockCost::where('livestock_id', $livestockId)
                    ->whereDate('tanggal', $tanggal)
                    ->first();
                app(\App\Services\Livestock\LivestockCostService::class)->calculateForDate($livestockId, $tanggal);
                // Tambahkan notes & history untuk hari yang diubah
                if ($cost) {
                    $cost->markInvalidCalculation('Supply usage diubah pada hari ini', $cost->toArray());
                }
                $futureCosts = LivestockCost::where('livestock_id', $livestockId)
                    ->where('tanggal', '>', $tanggal)
                    ->get();
                foreach ($futureCosts as $cost) {
                    $cost->markInvalidCalculation('Diupdate karena ada perubahan pada hari sebelumnya', $cost->toArray());
                }
            }
        }
    }
    public function deleted($detail)
    {
        if (method_exists($detail, 'supplyUsage') && $detail->supplyUsage) {
            $livestockId = $detail->supplyUsage->livestock_id;
            $tanggal = $detail->supplyUsage->usage_date;
            if ($livestockId && $tanggal) {
                app(\App\Services\Livestock\LivestockCostService::class)->calculateForDate($livestockId, $tanggal);
                $futureCosts = LivestockCost::where('livestock_id', $livestockId)
                    ->where('tanggal', '>', $tanggal)
                    ->get();
                foreach ($futureCosts as $cost) {
                    $cost->markInvalidCalculation('Diupdate karena ada perubahan pada hari sebelumnya', $cost->toArray());
                }
            }
        }
    }
}

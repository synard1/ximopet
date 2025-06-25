<?php

namespace App\Services\Recording;

use App\Models\FeedStock;
use App\Models\Recording;
use App\Models\Livestock;
use App\Models\LivestockDepletion;
use App\Models\LivestockSalesItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RecordingService
{

    /**
     * Get detailed population history for a livestock
     * 
     * @param string $livestockId The livestock ID
     * @param Carbon $currentDate The current date
     * @return array Population history details
     */
    public function getPopulationHistory($livestockId, $currentDate)
    {
        $livestock = Livestock::findOrFail($livestockId);
        $initialPopulation = $livestock->initial_quantity;
        $startDate = Carbon::parse($livestock->start_date);

        // Get all recordings up to the current date
        $recordings = Recording::where('livestock_id', $livestockId)
            ->where('tanggal', '<', $currentDate->format('Y-m-d'))
            ->orderBy('tanggal')
            ->get();

        // Get all depletion records up to the current date
        $depletions = LivestockDepletion::where('livestock_id', $livestockId)
            ->where('tanggal', '<', $currentDate->format('Y-m-d'))
            ->orderBy('tanggal')
            ->get();

        // Get all sales records up to the current date
        $sales = LivestockSalesItem::where('livestock_id', $livestockId)
            ->where('tanggal', '<', $currentDate->format('Y-m-d'))
            ->orderBy('tanggal')
            ->get();

        // Calculate daily population changes
        $populationByDate = [];
        $currentPopulation = $initialPopulation;
        $totalMortality = 0;
        $totalCulling = 0;
        $totalSales = 0;

        // Process recordings with their depletion data
        foreach ($recordings as $recording) {
            $recordDate = $recording->tanggal->format('Y-m-d');
            $payload = $recording->payload ?? [];

            $dayMortality = $payload['mortality'] ?? 0;
            $dayCulling = $payload['culling'] ?? 0;
            $daySales = $payload['sales_quantity'] ?? 0;

            $totalMortality += $dayMortality;
            $totalCulling += $dayCulling;
            $totalSales += $daySales;

            $currentPopulation = $recording->stock_akhir;

            $populationByDate[$recordDate] = [
                'date' => $recordDate,
                'population' => $currentPopulation,
                'mortality' => $dayMortality,
                'culling' => $dayCulling,
                'sales' => $daySales,
                'age' => $recording->age,
            ];
        }

        return [
            'initial_population' => $initialPopulation,
            'current_population' => $currentPopulation,
            'total_mortality' => $totalMortality,
            'total_culling' => $totalCulling,
            'total_sales' => $totalSales,
            'mortality_rate' => $initialPopulation > 0 ? ($totalMortality / $initialPopulation) * 100 : 0,
            'culling_rate' => $initialPopulation > 0 ? ($totalCulling / $initialPopulation) * 100 : 0,
            'sales_rate' => $initialPopulation > 0 ? ($totalSales / $initialPopulation) * 100 : 0,
            'survival_rate' => $initialPopulation > 0 ? ($currentPopulation / $initialPopulation) * 100 : 0,
            'daily_changes' => array_values($populationByDate),
            'age_days' => $startDate->diffInDays($currentDate),
        ];
    }

    public function process(Recording $recording, array $itemQuantities)
    {
        foreach ($itemQuantities as $itemId => $quantityUsed) {
            if ($quantityUsed <= 0) continue;

            $remainingQty = $quantityUsed;

            // Ambil stok FIFO
            $stocks = FeedStock::where('item_id', $itemId)
                ->where('ternak_id', $recording->ternak_id)
                ->where('date', '<=', $recording->recording_date)
                ->whereRaw('(quantity_in - quantity_used - quantity_mutated) > 0')
                ->orderBy('date')
                ->get();

            if ($stocks->sum(fn($s) => $s->available_quantity) < $quantityUsed) {
                throw new \Exception("Stok tidak cukup untuk item ID: $itemId");
            }

            foreach ($stocks as $stock) {
                if ($remainingQty <= 0) break;

                $available = $stock->quantity_in - $stock->quantity_used - $stock->quantity_mutated;
                $usedQty = min($remainingQty, $available);

                // Update FeedStock
                $stock->increment('quantity_used', $usedQty);

                // Simpan detail ke RecordingItem (mirip FeedUsageDetail)
                // RecordingItem::create([
                //     'recording_id'   => $recording->id,
                //     'feed_stock_id'  => $stock->id,
                //     'item_id'        => $itemId,
                //     'quantity_used'  => $usedQty,
                // ]);

                $remainingQty -= $usedQty;
            }
        }
    }
}

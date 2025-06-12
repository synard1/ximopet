<?php

namespace App\Observers;

use App\Models\LivestockDepletion;
use App\Models\Livestock;
use App\Models\CurrentLivestock;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class LivestockDepletionObserver
{
    /**
     * Handle the LivestockDepletion "created" event.
     */
    public function created(LivestockDepletion $livestockDepletion): void
    {
        Log::info('📊 LivestockDepletion created, updating quantities', [
            'livestock_id' => $livestockDepletion->livestock_id,
            'jenis' => $livestockDepletion->jenis,
            'jumlah' => $livestockDepletion->jumlah,
        ]);

        $this->updateLivestockQuantities($livestockDepletion->livestock_id);
    }

    /**
     * Handle the LivestockDepletion "updated" event.
     */
    public function updated(LivestockDepletion $livestockDepletion): void
    {
        Log::info('📊 LivestockDepletion updated, updating quantities', [
            'livestock_id' => $livestockDepletion->livestock_id,
            'jenis' => $livestockDepletion->jenis,
            'jumlah' => $livestockDepletion->jumlah,
        ]);

        $this->updateLivestockQuantities($livestockDepletion->livestock_id);
    }

    /**
     * Handle the LivestockDepletion "deleted" event.
     */
    public function deleted(LivestockDepletion $livestockDepletion): void
    {
        Log::info('📊 LivestockDepletion deleted, updating quantities', [
            'livestock_id' => $livestockDepletion->livestock_id,
            'jenis' => $livestockDepletion->jenis,
            'jumlah' => $livestockDepletion->jumlah,
        ]);

        $this->updateLivestockQuantities($livestockDepletion->livestock_id);
    }

    /**
     * Update Livestock quantity_depletion and CurrentLivestock quantity
     * 
     * @param string $livestockId
     * @return void
     */
    private function updateLivestockQuantities(string $livestockId): void
    {
        DB::transaction(function () use ($livestockId) {
            // Get the livestock record
            $livestock = Livestock::find($livestockId);
            if (!$livestock) {
                Log::warning('⚠️ Livestock not found', ['livestock_id' => $livestockId]);
                return;
            }

            // Calculate total depletion from all LivestockDepletion records
            $totalDepletion = LivestockDepletion::where('livestock_id', $livestockId)
                ->sum('jumlah');

            // Update quantity_depletion in Livestock
            $oldQuantityDepletion = $livestock->quantity_depletion ?? 0;
            $livestock->update([
                'quantity_depletion' => $totalDepletion,
                'updated_by' => auth()->id() ?? $livestock->updated_by
            ]);

            Log::info('📊 Updated Livestock quantity_depletion', [
                'livestock_id' => $livestockId,
                'old_quantity_depletion' => $oldQuantityDepletion,
                'new_quantity_depletion' => $totalDepletion,
                'change' => $totalDepletion - $oldQuantityDepletion
            ]);

            // Update CurrentLivestock with real-time calculation
            $this->updateCurrentLivestockQuantity($livestock);
        });
    }

    /**
     * Update CurrentLivestock quantity with real-time calculation
     * Formula: initial_quantity - quantity_depletion - quantity_sales - quantity_mutated
     * 
     * @param Livestock $livestock
     * @return void
     */
    private function updateCurrentLivestockQuantity(Livestock $livestock): void
    {
        $currentLivestock = CurrentLivestock::where('livestock_id', $livestock->id)->first();
        if (!$currentLivestock) {
            Log::warning('⚠️ CurrentLivestock not found', ['livestock_id' => $livestock->id]);
            return;
        }

        // Calculate real-time quantity using the formula
        $calculatedQuantity = $livestock->initial_quantity
            - ($livestock->quantity_depletion ?? 0)
            - ($livestock->quantity_sales ?? 0)
            - ($livestock->quantity_mutated ?? 0);

        // Ensure quantity doesn't go negative
        $calculatedQuantity = max(0, $calculatedQuantity);

        $oldQuantity = $currentLivestock->quantity;

        // Update CurrentLivestock with comprehensive metadata
        $currentLivestock->update([
            'quantity' => $calculatedQuantity,
            'metadata' => array_merge($currentLivestock->metadata ?? [], [
                'last_updated' => now()->toIso8601String(),
                'updated_by' => auth()->id() ?? $livestock->updated_by,
                'updated_by_name' => auth()->user()->name ?? 'System',
                'previous_quantity' => $oldQuantity,
                'quantity_change' => $calculatedQuantity - $oldQuantity,
                'calculation_source' => 'livestock_depletion_observer',
                'formula_breakdown' => [
                    'initial_quantity' => $livestock->initial_quantity,
                    'quantity_depletion' => $livestock->quantity_depletion ?? 0,
                    'quantity_sales' => $livestock->quantity_sales ?? 0,
                    'quantity_mutated' => $livestock->quantity_mutated ?? 0,
                    'calculated_quantity' => $calculatedQuantity
                ],
                'percentages' => [
                    'depletion_percentage' => $livestock->initial_quantity > 0
                        ? round((($livestock->quantity_depletion ?? 0) / $livestock->initial_quantity) * 100, 2)
                        : 0,
                    'sales_percentage' => $livestock->initial_quantity > 0
                        ? round((($livestock->quantity_sales ?? 0) / $livestock->initial_quantity) * 100, 2)
                        : 0,
                    'mutation_percentage' => $livestock->initial_quantity > 0
                        ? round((($livestock->quantity_mutated ?? 0) / $livestock->initial_quantity) * 100, 2)
                        : 0,
                    'remaining_percentage' => $livestock->initial_quantity > 0
                        ? round(($calculatedQuantity / $livestock->initial_quantity) * 100, 2)
                        : 0
                ]
            ]),
            'updated_by' => auth()->id() ?? $livestock->updated_by
        ]);

        Log::info('📊 Updated CurrentLivestock quantity', [
            'livestock_id' => $livestock->id,
            'livestock_name' => $livestock->name,
            'old_quantity' => $oldQuantity,
            'new_quantity' => $calculatedQuantity,
            'change' => $calculatedQuantity - $oldQuantity,
            'formula' => sprintf(
                '%d - %d - %d - %d = %d',
                $livestock->initial_quantity,
                $livestock->quantity_depletion ?? 0,
                $livestock->quantity_sales ?? 0,
                $livestock->quantity_mutated ?? 0,
                $calculatedQuantity
            )
        ]);
    }
}

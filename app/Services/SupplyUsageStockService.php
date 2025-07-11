<?php

namespace App\Services;

use App\Models\SupplyUsage;
use App\Models\SupplyStock;
use App\Models\CurrentSupply;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SupplyUsageStockService
{
    /**
     * Update stock for status change with proper accumulation tracking
     * 
     * @param SupplyUsage $usage
     * @param string $previousStatus
     * @param string $newStatus
     * @return array
     */
    public function updateStockForStatusChange(SupplyUsage $usage, string $previousStatus, string $newStatus): array
    {
        DB::beginTransaction();

        try {
            $result = [
                'success' => true,
                'usage_id' => $usage->id,
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
                'stock_actions' => [],
                'current_supply_actions' => [],
                'errors' => []
            ];

            // Determine stock action based on status transition
            $stockAction = $this->determineStockAction($previousStatus, $newStatus);

            if (!$stockAction) {
                $result['message'] = 'No stock action required for this transition';
                // Tetap update tracking metadata meskipun tidak ada perubahan stock
                $this->updateStockTrackingMetadata($usage, [], $result, true);
                DB::commit();
                return $result;
            }

            // Get current stock state for this usage
            $currentStockState = $this->getCurrentStockState($usage);

            // Calculate required stock changes
            $stockChanges = $this->calculateStockChanges($usage, $previousStatus, $newStatus, $currentStockState);

            // Apply stock changes
            $this->applyStockChanges($usage, $stockChanges, $result);

            // Update stock tracking metadata for ALL details (bukan hanya yang berubah)
            $this->updateStockTrackingMetadata($usage, $currentStockState, $result, false, $stockChanges);

            DB::commit();

            $result['message'] = 'Stock updated successfully for status change';

            Log::info('SupplyUsageStockService: Stock update completed', [
                'usage_id' => $usage->id,
                'transition' => $previousStatus . '_to_' . $newStatus,
                'stock_actions_count' => count($result['stock_actions']),
                'current_supply_actions_count' => count($result['current_supply_actions'])
            ]);

            return $result;
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('SupplyUsageStockService: Stock update failed', [
                'usage_id' => $usage->id,
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'usage_id' => $usage->id,
                'error' => $e->getMessage(),
                'message' => 'Failed to update stock for status change'
            ];
        }
    }

    /**
     * Determine what stock action is required for status transition
     */
    private function determineStockAction(string $previousStatus, string $newStatus): ?string
    {
        $stockImpactTransitions = [
            // Stock Reduction (start/continue processing)
            'draft_to_pending' => 'reduce',
            'draft_to_in_process' => 'reduce',
            'draft_to_completed' => 'reduce',
            'pending_to_in_process' => 'reduce',
            'pending_to_completed' => 'reduce',
            'in_process_to_completed' => 'reduce',

            // Stock Restoration (cancellation/rejection)
            'pending_to_cancelled' => 'restore',
            'in_process_to_cancelled' => 'restore',
            'completed_to_cancelled' => 'restore',
            'pending_to_rejected' => 'restore',
            'in_process_to_rejected' => 'restore',
        ];

        $transition = $previousStatus . '_to_' . $newStatus;
        return $stockImpactTransitions[$transition] ?? null;
    }

    /**
     * Get current stock state for this usage
     */
    private function getCurrentStockState(SupplyUsage $usage): array
    {
        $stockState = [];

        foreach ($usage->details as $detail) {
            $supplyStock = SupplyStock::find($detail->supply_stock_id);
            if ($supplyStock) {
                $stockState[$detail->supply_stock_id] = [
                    'current_quantity_used' => $supplyStock->quantity_used,
                    'detail_quantity' => $detail->converted_quantity,
                    'supply_id' => $detail->supply_id,
                    'farm_id' => $usage->farm_id,
                    'livestock_id' => $usage->livestock_id
                ];
            }
        }

        return $stockState;
    }

    /**
     * Calculate required stock changes based on current state
     */
    private function calculateStockChanges(SupplyUsage $usage, string $previousStatus, string $newStatus, array $currentStockState): array
    {
        $stockAction = $this->determineStockAction($previousStatus, $newStatus);
        $changes = [];

        // If no stock action required, return empty array
        if (!$stockAction) {
            Log::debug('SupplyUsageStockService: No stock action required', [
                'usage_id' => $usage->id,
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
                'stock_action' => $stockAction
            ]);
            return $changes;
        }

        // If status didn't change, no stock update needed
        if ($previousStatus === $newStatus) {
            Log::debug('SupplyUsageStockService: Status unchanged, no stock update needed', [
                'usage_id' => $usage->id,
                'previous_status' => $previousStatus,
                'new_status' => $newStatus
            ]);
            return $changes;
        }

        foreach ($usage->details as $detail) {
            $supplyStockId = $detail->supply_stock_id;
            $currentState = $currentStockState[$supplyStockId] ?? null;

            if (!$currentState) {
                Log::warning('SupplyUsageStockService: No current state found for stock', [
                    'usage_id' => $usage->id,
                    'supply_stock_id' => $supplyStockId
                ]);
                continue;
            }

            $detailQuantity = $detail->converted_quantity;
            $currentQuantityUsed = $currentState['current_quantity_used'];

            // Calculate how much stock has already been processed for this usage
            $alreadyProcessed = $this->getAlreadyProcessedQuantity($usage, $supplyStockId);

            // Calculate required change
            $requiredChange = 0;

            if ($stockAction === 'reduce') {
                // Need to reduce stock by the difference
                $requiredChange = $detailQuantity - $alreadyProcessed;
            } elseif ($stockAction === 'restore') {
                // Need to restore stock by the amount that was processed
                $requiredChange = -$alreadyProcessed; // Negative for restoration
            }

            Log::debug('SupplyUsageStockService: Stock change calculation', [
                'usage_id' => $usage->id,
                'supply_stock_id' => $supplyStockId,
                'detail_quantity' => $detailQuantity,
                'already_processed' => $alreadyProcessed,
                'required_change' => $requiredChange,
                'stock_action' => $stockAction,
                'previous_status' => $previousStatus,
                'new_status' => $newStatus
            ]);

            if ($requiredChange != 0) {
                $changes[$supplyStockId] = [
                    'supply_stock_id' => $supplyStockId,
                    'supply_id' => $detail->supply_id,
                    'farm_id' => $usage->farm_id,
                    'livestock_id' => $usage->livestock_id,
                    'detail_quantity' => $detailQuantity,
                    'already_processed' => $alreadyProcessed,
                    'required_change' => $requiredChange,
                    'action' => $stockAction,
                    'current_quantity_used' => $currentQuantityUsed
                ];
            } else {
                Log::debug('SupplyUsageStockService: No change required for stock', [
                    'usage_id' => $usage->id,
                    'supply_stock_id' => $supplyStockId,
                    'detail_quantity' => $detailQuantity,
                    'already_processed' => $alreadyProcessed
                ]);
            }
        }

        return $changes;
    }

    /**
     * Get quantity already processed for this usage and stock
     */
    private function getAlreadyProcessedQuantity(SupplyUsage $usage, string $supplyStockId): float
    {
        try {
            // Check if this usage has already been processed for this stock
            $processedQuantity = DB::table('supply_usage_stock_tracking')
                ->where('supply_usage_id', $usage->id)
                ->where('supply_stock_id', $supplyStockId)
                ->sum('quantity_processed');

            $result = floatval($processedQuantity);

            Log::debug('SupplyUsageStockService: Already processed quantity', [
                'usage_id' => $usage->id,
                'supply_stock_id' => $supplyStockId,
                'already_processed' => $result,
                'raw_result' => $processedQuantity
            ]);

            return $result;
        } catch (\Exception $e) {
            Log::warning('SupplyUsageStockService: Failed to get already processed quantity', [
                'usage_id' => $usage->id,
                'supply_stock_id' => $supplyStockId,
                'error' => $e->getMessage()
            ]);

            // Return 0 if there's an error (assume nothing processed yet)
            return 0.0;
        }
    }

    /**
     * Apply calculated stock changes
     */
    private function applyStockChanges(SupplyUsage $usage, array $stockChanges, array &$result): void
    {
        foreach ($stockChanges as $supplyStockId => $change) {
            try {
                // Update SupplyStock
                $supplyStock = SupplyStock::find($supplyStockId);
                if ($supplyStock) {
                    $oldQuantityUsed = $supplyStock->quantity_used;
                    $supplyStock->increment('quantity_used', $change['required_change']);
                    $newQuantityUsed = $supplyStock->quantity_used;

                    $result['stock_actions'][] = [
                        'supply_stock_id' => $supplyStockId,
                        'old_quantity_used' => $oldQuantityUsed,
                        'new_quantity_used' => $newQuantityUsed,
                        'change' => $change['required_change'],
                        'action' => $change['action']
                    ];

                    Log::debug('SupplyUsageStockService: SupplyStock updated', [
                        'supply_stock_id' => $supplyStockId,
                        'old_quantity_used' => $oldQuantityUsed,
                        'new_quantity_used' => $newQuantityUsed,
                        'change' => $change['required_change']
                    ]);
                }

                // Update CurrentSupply
                $this->updateCurrentSupply($change, $result);
            } catch (\Exception $e) {
                $result['errors'][] = [
                    'supply_stock_id' => $supplyStockId,
                    'error' => $e->getMessage()
                ];

                Log::error('SupplyUsageStockService: Failed to update stock', [
                    'supply_stock_id' => $supplyStockId,
                    'change' => $change,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Update CurrentSupply for stock change
     */
    private function updateCurrentSupply(array $change, array &$result): void
    {
        try {
            $currentSupply = CurrentSupply::where('farm_id', $change['farm_id'])
                ->where('item_id', $change['supply_id'])
                ->where('type', 'Supply')
                ->first();

            if ($currentSupply) {
                $oldQuantity = $currentSupply->quantity;
                $currentSupply->decrement('quantity', $change['required_change']);
                $newQuantity = $currentSupply->quantity;

                $result['current_supply_actions'][] = [
                    'current_supply_id' => $currentSupply->id,
                    'supply_id' => $change['supply_id'],
                    'old_quantity' => $oldQuantity,
                    'new_quantity' => $newQuantity,
                    'change' => -$change['required_change'], // Negative because we decrement
                    'action' => $change['action']
                ];

                Log::debug('SupplyUsageStockService: CurrentSupply updated', [
                    'current_supply_id' => $currentSupply->id,
                    'supply_id' => $change['supply_id'],
                    'old_quantity' => $oldQuantity,
                    'new_quantity' => $newQuantity,
                    'change' => -$change['required_change']
                ]);
            }
        } catch (\Exception $e) {
            $result['errors'][] = [
                'current_supply_error' => true,
                'supply_id' => $change['supply_id'],
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Update stock tracking metadata for audit trail
     * @param array $allDetailsOrCurrentStockState - gunakan semua detail, bukan hanya yang berubah
     * @param array $stockChanges - optional, untuk menandai mana yang berubah
     * @param bool $forceInsert - jika true, selalu insert meskipun delta 0
     */
    private function updateStockTrackingMetadata(SupplyUsage $usage, array $allDetailsOrCurrentStockState, array &$result, bool $forceInsert = false, array $stockChanges = []): void
    {
        $details = is_array($allDetailsOrCurrentStockState) && isset(array_values($allDetailsOrCurrentStockState)[0]['supply_id'])
            ? $allDetailsOrCurrentStockState
            : collect($usage->details)->keyBy('supply_stock_id')->toArray();

        foreach ($details as $supplyStockId => $detail) {
            try {
                $detailQuantity = isset($detail['detail_quantity']) ? $detail['detail_quantity'] : (is_object($detail) ? $detail->converted_quantity : 0);
                $change = $stockChanges[$supplyStockId] ?? null;
                $requiredChange = $change['required_change'] ?? 0;
                $action = $change['action'] ?? null;

                // Calculate total already processed before this change
                $alreadyProcessed = $this->getAlreadyProcessedQuantity($usage, $supplyStockId);
                $delta = $requiredChange; // delta for this status change

                // Always insert a new tracking record for every status change (audit trail)
                $trackingId = Str::uuid();
                DB::table('supply_usage_stock_tracking')->insert([
                    'id' => $trackingId,
                    'supply_usage_id' => $usage->id,
                    'supply_stock_id' => $supplyStockId,
                    'quantity_processed' => $detailQuantity,
                    'last_processed_at' => now(),
                    'last_processed_by' => Auth::id(),
                    'status' => $usage->status,
                    'metadata' => json_encode([
                        'previous_status' => $usage->getOriginal('status'),
                        'new_status' => $usage->status,
                        'action' => $action,
                        'change' => $requiredChange,
                        'delta' => $delta,
                        'already_processed_before' => $alreadyProcessed,
                        'tracking_id' => $trackingId,
                        'created_at' => now()->toIso8601String()
                    ]),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                Log::debug('SupplyUsageStockService: Tracking metadata inserted', [
                    'usage_id' => $usage->id,
                    'supply_stock_id' => $supplyStockId,
                    'tracking_id' => $trackingId,
                    'quantity_processed' => $detailQuantity,
                    'delta' => $delta,
                    'action' => 'inserted',
                    'required_change' => $requiredChange
                ]);
            } catch (\Exception $e) {
                Log::warning('SupplyUsageStockService: Failed to insert tracking metadata', [
                    'usage_id' => $usage->id,
                    'supply_stock_id' => $supplyStockId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $result['errors'][] = [
                    'type' => 'tracking_metadata_error',
                    'supply_stock_id' => $supplyStockId,
                    'error' => $e->getMessage()
                ];
            }
        }
    }

    /**
     * Recalculate stock for usage (useful for data correction)
     */
    public function recalculateStockForUsage(SupplyUsage $usage): array
    {
        // This method can be used for background job to recalculate stock
        // when there are data inconsistencies

        $result = [
            'success' => true,
            'usage_id' => $usage->id,
            'recalculations' => []
        ];

        foreach ($usage->details as $detail) {
            $supplyStock = SupplyStock::find($detail->supply_stock_id);
            if ($supplyStock) {
                // Recalculate quantity_used based on all usage details
                $totalUsed = DB::table('supply_usage_details')
                    ->where('supply_stock_id', $detail->supply_stock_id)
                    ->whereHas('supplyUsage', function ($query) {
                        $query->whereIn('status', ['pending', 'in_process', 'completed']);
                    })
                    ->sum('converted_quantity');

                $oldQuantityUsed = $supplyStock->quantity_used;
                $supplyStock->update(['quantity_used' => $totalUsed]);

                $result['recalculations'][] = [
                    'supply_stock_id' => $detail->supply_stock_id,
                    'old_quantity_used' => $oldQuantityUsed,
                    'new_quantity_used' => $totalUsed,
                    'difference' => $totalUsed - $oldQuantityUsed
                ];
            }
        }

        return $result;
    }
}

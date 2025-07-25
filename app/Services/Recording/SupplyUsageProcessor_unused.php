<?php

namespace App\Services\Recording;

use App\Models\Supply;
use App\Models\SupplyStock;
use App\Models\SupplyUsage;
use App\Models\SupplyUsageDetail;
use App\Models\CurrentSupply;
use App\Models\Livestock;
use App\Services\Recording\DTOs\ServiceResult;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * SupplyUsageProcessor Service
 * 
 * Handles supply usage processing with FIFO methodology,
 * stock management, and comprehensive tracking.
 */
class SupplyUsageProcessor
{
    /**
     * Process supply usage with FIFO methodology
     * 
     * @param array $data Supply usage data
     * @return ServiceResult
     */
    public function processSupplyUsage(array $data): ServiceResult
    {
        try {
            Log::info('🔄 SupplyUsageProcessor: Starting supply usage processing', [
                'recording_id' => $data['recording_id'] ?? null,
                'livestock_id' => $data['livestock_id'] ?? null,
                'supply_count' => count($data['supply_data'] ?? [])
            ]);

            DB::beginTransaction();

            $processedUsages = [];
            $totalProcessed = 0;

            foreach ($data['supply_data'] as $supplyUsage) {
                $result = $this->processSingleSupplyUsage($supplyUsage, $data);

                if ($result) {
                    $processedUsages[] = $result;
                    $totalProcessed++;
                }
            }

            DB::commit();

            Log::info('✅ SupplyUsageProcessor: Supply usage processing completed', [
                'recording_id' => $data['recording_id'] ?? null,
                'total_processed' => $totalProcessed,
                'processed_usages' => count($processedUsages)
            ]);

            return ServiceResult::success([
                'processed_usages' => $processedUsages,
                'total_processed' => $totalProcessed,
                'processing_method' => 'fifo',
                'timestamp' => now()->toIso8601String()
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('❌ SupplyUsageProcessor: Exception during processing', [
                'recording_id' => $data['recording_id'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return ServiceResult::failure([
                'error' => 'Supply usage processing failed',
                'details' => $e->getMessage(),
                'code' => 'SUPPLY_USAGE_PROCESSING_ERROR'
            ]);
        }
    }

    /**
     * Process single supply usage item
     * 
     * @param array $supplyUsage Supply usage data
     * @param array $contextData Context data (recording_id, livestock_id, etc.)
     * @return SupplyUsage|null
     */
    private function processSingleSupplyUsage(array $supplyUsage, array $contextData): ?SupplyUsage
    {
        try {
            // Create supply usage record
            $usage = SupplyUsage::create([
                'recording_id' => $contextData['recording_id'],
                'livestock_id' => $contextData['livestock_id'],
                'supply_id' => $supplyUsage['supply_id'],
                'quantity' => $supplyUsage['quantity'],
                'usage_date' => $contextData['date'],
                'user_id' => $contextData['user_id'] ?? Auth::id(),
                'company_id' => $contextData['company_id'] ?? Auth::user()->company_id,
                'metadata' => json_encode([
                    'processing_method' => 'fifo',
                    'processor' => 'SupplyUsageProcessor',
                    'timestamp' => now()->toIso8601String()
                ])
            ]);

            // Process supply usage details with FIFO
            $this->processSupplyUsageDetails($usage, $supplyUsage, $contextData);

            Log::info('✅ Single supply usage processed', [
                'usage_id' => $usage->id,
                'supply_id' => $supplyUsage['supply_id'],
                'quantity' => $supplyUsage['quantity']
            ]);

            return $usage;
        } catch (\Exception $e) {
            Log::error('❌ Single supply usage processing failed', [
                'supply_id' => $supplyUsage['supply_id'] ?? null,
                'quantity' => $supplyUsage['quantity'] ?? null,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Process supply usage details with FIFO methodology
     * 
     * @param SupplyUsage $usage
     * @param array $supplyUsage
     * @param array $contextData
     * @return void
     */
    private function processSupplyUsageDetails(SupplyUsage $usage, array $supplyUsage, array $contextData): void
    {
        $livestock = Livestock::find($contextData['livestock_id']);
        if (!$livestock) {
            Log::warning('Livestock not found for supply usage details', [
                'livestock_id' => $contextData['livestock_id']
            ]);
            return;
        }

        $quantityNeeded = $supplyUsage['quantity'];

        // Get available stocks using FIFO (oldest first)
        $availableStocks = SupplyStock::where('farm_id', $livestock->farm_id)
            ->where('supply_id', $supplyUsage['supply_id'])
            ->whereRaw('(quantity_in - quantity_used - quantity_mutated) > 0')
            ->orderBy('date')
            ->orderBy('created_at')
            ->get();

        Log::info('📦 Processing supply usage details with FIFO', [
            'supply_id' => $supplyUsage['supply_id'],
            'quantity_needed' => $quantityNeeded,
            'available_stocks' => $availableStocks->count()
        ]);

        foreach ($availableStocks as $stock) {
            if ($quantityNeeded <= 0) break;

            $availableInStock = $stock->quantity_in - $stock->quantity_used - $stock->quantity_mutated;
            $quantityToTake = min($quantityNeeded, $availableInStock);

            if ($quantityToTake > 0) {
                // Create supply usage detail
                SupplyUsageDetail::create([
                    'supply_usage_id' => $usage->id,
                    'supply_id' => $supplyUsage['supply_id'],
                    'supply_stock_id' => $stock->id,
                    'quantity_taken' => $quantityToTake,
                    'unit_price' => $stock->unit_price ?? 0,
                    'total_cost' => $quantityToTake * ($stock->unit_price ?? 0),
                    'batch_number' => $stock->batch_number,
                    'expiry_date' => $stock->expiry_date,
                    'created_by' => $contextData['user_id'] ?? Auth::id(),
                    'metadata' => json_encode([
                        'fifo_order' => $stock->date,
                        'stock_id' => $stock->id,
                        'available_before' => $availableInStock,
                        'quantity_taken' => $quantityToTake
                    ])
                ]);

                // Update stock quantity used
                $stock->quantity_used += $quantityToTake;
                $stock->save();

                // Update CurrentSupply
                $this->updateCurrentSupply($contextData['livestock_id'], $supplyUsage['supply_id'], $quantityToTake);

                $quantityNeeded -= $quantityToTake;

                Log::info('📋 Supply usage detail created', [
                    'usage_id' => $usage->id,
                    'supply_id' => $supplyUsage['supply_id'],
                    'stock_id' => $stock->id,
                    'quantity_taken' => $quantityToTake,
                    'remaining_needed' => $quantityNeeded,
                    'stock_date' => $stock->date
                ]);
            }
        }

        if ($quantityNeeded > 0) {
            Log::warning('⚠️ Insufficient stock for supply usage', [
                'supply_id' => $supplyUsage['supply_id'],
                'requested' => $supplyUsage['quantity'],
                'shortage' => $quantityNeeded,
                'available_stocks' => $availableStocks->count()
            ]);
        }
    }

    /**
     * Update current supply quantity
     * 
     * @param int $livestockId
     * @param int $supplyId
     * @param float $quantityUsed
     * @return void
     */
    private function updateCurrentSupply(int $livestockId, int $supplyId, float $quantityUsed): void
    {
        try {
            $currentSupply = CurrentSupply::where('livestock_id', $livestockId)
                ->where('item_id', $supplyId)
                ->first();

            if ($currentSupply) {
                $previousQuantity = $currentSupply->quantity;
                $currentSupply->quantity = max(0, $currentSupply->quantity - $quantityUsed);
                $currentSupply->save();

                Log::info('📊 Current supply updated', [
                    'livestock_id' => $livestockId,
                    'supply_id' => $supplyId,
                    'previous_quantity' => $previousQuantity,
                    'used_quantity' => $quantityUsed,
                    'new_quantity' => $currentSupply->quantity
                ]);
            } else {
                Log::warning('⚠️ Current supply record not found', [
                    'livestock_id' => $livestockId,
                    'supply_id' => $supplyId,
                    'quantity_used' => $quantityUsed
                ]);
            }
        } catch (\Exception $e) {
            Log::error('❌ Failed to update current supply', [
                'livestock_id' => $livestockId,
                'supply_id' => $supplyId,
                'quantity_used' => $quantityUsed,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get supply usage statistics
     * 
     * @param int $livestockId
     * @param string $period
     * @return ServiceResult
     */
    public function getSupplyUsageStats(int $livestockId, string $period = '30_days'): ServiceResult
    {
        try {
            $days = $this->parsePeriodToDays($period);
            $startDate = Carbon::now()->subDays($days);

            $stats = SupplyUsage::where('livestock_id', $livestockId)
                ->where('usage_date', '>=', $startDate)
                ->with(['supply', 'supplyUsageDetails'])
                ->get()
                ->groupBy('supply_id')
                ->map(function ($usages, $supplyId) {
                    $supply = $usages->first()->supply;
                    $totalQuantity = $usages->sum('quantity');
                    $totalCost = $usages->flatMap->supplyUsageDetails->sum('total_cost');
                    $usageCount = $usages->count();

                    return [
                        'supply_id' => $supplyId,
                        'supply_name' => $supply->name ?? 'Unknown',
                        'supply_code' => $supply->code ?? 'Unknown',
                        'total_quantity' => $totalQuantity,
                        'total_cost' => $totalCost,
                        'usage_count' => $usageCount,
                        'average_per_usage' => $usageCount > 0 ? $totalQuantity / $usageCount : 0,
                        'cost_per_unit' => $totalQuantity > 0 ? $totalCost / $totalQuantity : 0
                    ];
                });

            return ServiceResult::success([
                'stats' => $stats->values()->toArray(),
                'period' => $period,
                'livestock_id' => $livestockId,
                'total_supplies' => $stats->count(),
                'total_quantity' => $stats->sum('total_quantity'),
                'total_cost' => $stats->sum('total_cost')
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Failed to get supply usage stats', [
                'livestock_id' => $livestockId,
                'period' => $period,
                'error' => $e->getMessage()
            ]);

            return ServiceResult::failure([
                'error' => 'Failed to get supply usage statistics',
                'details' => $e->getMessage()
            ]);
        }
    }

    /**
     * Parse period string to days
     * 
     * @param string $period
     * @return int
     */
    private function parsePeriodToDays(string $period): int
    {
        $periodMap = [
            '7_days' => 7,
            '30_days' => 30,
            '90_days' => 90,
            '365_days' => 365
        ];

        return $periodMap[$period] ?? 30;
    }

    /**
     * Validate supply usage data
     * 
     * @param array $data
     * @return ServiceResult
     */
    public function validateSupplyUsageData(array $data): ServiceResult
    {
        $errors = [];

        // Check required fields
        if (empty($data['livestock_id'])) {
            $errors[] = 'Livestock ID is required';
        }

        if (empty($data['supply_data']) || !is_array($data['supply_data'])) {
            $errors[] = 'Supply data is required and must be an array';
        }

        if (empty($data['date'])) {
            $errors[] = 'Usage date is required';
        }

        // Validate each supply usage item
        foreach ($data['supply_data'] ?? [] as $index => $supplyUsage) {
            if (empty($supplyUsage['supply_id'])) {
                $errors[] = "Supply ID is required for item {$index}";
            }

            if (empty($supplyUsage['quantity']) || $supplyUsage['quantity'] <= 0) {
                $errors[] = "Valid quantity is required for item {$index}";
            }

            // Check if supply exists
            if (!empty($supplyUsage['supply_id'])) {
                $supply = Supply::find($supplyUsage['supply_id']);
                if (!$supply) {
                    $errors[] = "Supply not found for item {$index}";
                }
            }
        }

        if (!empty($errors)) {
            return ServiceResult::failure([
                'validation_errors' => $errors,
                'code' => 'VALIDATION_FAILED'
            ]);
        }

        return ServiceResult::success(['validation' => 'passed']);
    }
}

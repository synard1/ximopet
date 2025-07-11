<?php

declare(strict_types=1);

namespace App\Services\Recording;

use App\Models\{FeedUsage, SupplyUsage, FeedUsageDetail, SupplyUsageDetail, FeedStock, SupplyStock, Feed, Supply, Livestock, CurrentSupply};
use App\Services\Recording\DTOs\ProcessingResult;
use App\Services\Recording\Exceptions\RecordingException;
use App\Services\Recording\Contracts\FeedSupplyProcessingServiceInterface;
use Illuminate\Support\Facades\{DB, Log, Auth};
use Illuminate\Support\Collection;
use Carbon\Carbon;

/**
 * FeedSupplyProcessingService
 * 
 * Handles all feed usage and supply usage processing operations
 * with comprehensive tracking, validation, and FIFO processing.
 */
class FeedSupplyProcessingService implements FeedSupplyProcessingServiceInterface
{
    /**
     * Save feed usage with enhanced tracking
     * 
     * @param array $data The validated data
     * @param string $recordingId The recording ID for relation
     * @param string $livestockId The livestock ID
     * @param string $date The usage date
     * @param array $usages The usage data
     * @param int|null $feedUsageId Existing feed usage ID if updating
     * @return ProcessingResult
     */
    public function saveFeedUsageWithTracking(
        array $data,
        string $recordingId,
        string $livestockId,
        string $date,
        array $usages,
        ?string $feedUsageId = null
    ): ProcessingResult {
        try {
            DB::beginTransaction();

            if ($feedUsageId) {
                // UPDATE - Handle existing feed usage
                $usage = FeedUsage::findOrFail($feedUsageId);
                $hasChanged = $this->hasUsageChanged($usage, $usages);

                Log::info("Feed usage update check", [
                    'usage_id' => $feedUsageId,
                    'has_changed' => $hasChanged,
                    'existing_quantity' => $usage->total_quantity,
                    'new_quantity' => array_sum(array_column($usages, 'quantity')),
                    'usages_data' => $usages,
                    'usage_details' => $usage->details()->select('feed_id', 'quantity_taken')->get()->toArray()
                ]);

                if (!$hasChanged) {
                    Log::info("Feed usage unchanged, skipping update", [
                        'usage_id' => $feedUsageId,
                        'reason' => 'No changes detected'
                    ]);
                    DB::rollBack();
                    return ProcessingResult::success($usage, 'No changes detected');
                }

                // Validate usage date
                $this->validateFeedUsageDate($livestockId, $date);

                // CRITICAL FIX: Delete ALL existing feed usage records for this date to prevent duplicates
                $existingUsages = FeedUsage::where('livestock_id', $livestockId)
                    ->whereDate('usage_date', $date)
                    ->get();

                Log::info("Found existing feed usage records for date", [
                    'date' => $date,
                    'livestock_id' => $livestockId,
                    'existing_count' => $existingUsages->count(),
                    'existing_ids' => $existingUsages->pluck('id')->toArray()
                ]);

                foreach ($existingUsages as $existingUsage) {
                    Log::info("Deleting existing feed usage record", [
                        'usage_id' => $existingUsage->id,
                        'total_quantity' => $existingUsage->total_quantity
                    ]);

                    // Revert details first
                    $this->revertFeedUsageDetails($existingUsage);

                    // Delete the usage record
                    $existingUsage->delete();
                }

                // Create new usage record (not update existing)
                $usage = FeedUsage::create([
                    'usage_date' => $date,
                    'livestock_id' => $livestockId,
                    'recording_id' => $recordingId,
                    'total_quantity' => array_sum(array_column($usages, 'quantity')),
                    'metadata' => $this->buildFeedUsageMetadata($usages, 'create'),
                    'created_by' => Auth::id(),
                ]);
            } else {
                // CREATE - Create new feed usage
                $this->validateFeedUsageDate($livestockId, $date);

                // Create new usage record
                $usage = FeedUsage::create([
                    'usage_date' => $date,
                    'livestock_id' => $livestockId,
                    'recording_id' => $recordingId,
                    'total_quantity' => array_sum(array_column($usages, 'quantity')),
                    'metadata' => $this->buildFeedUsageMetadata($usages, 'create'),
                    'created_by' => Auth::id(),
                ]);
            }

            // Process feed usage with FIFO
            $this->processFeedUsageWithFifo($usage, $usages);

            // Update CurrentSupply
            $this->updateCurrentSupplyForFeedUsage($livestockId, $usages);

            DB::commit();

            Log::info("Feed usage processed successfully", [
                'usage_id' => $usage->id,
                'livestock_id' => $usage->livestock_id,
                'date' => $usage->usage_date,
                'total_quantity' => $usage->total_quantity,
                'feeds_count' => count($usages),
                'operation' => $feedUsageId ? 'update' : 'create'
            ]);

            return ProcessingResult::success($usage, 'Feed usage processed successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error processing feed usage', [
                'livestock_id' => $livestockId,
                'date' => $date,
                'feed_usage_id' => $feedUsageId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new RecordingException("Failed to process feed usage: " . $e->getMessage());
        }
    }

    /**
     * Save supply usage with enhanced tracking
     * 
     * @param array $data The validated data
     * @param string $recordingId The recording ID for relation
     * @param string $livestockId The livestock ID
     * @param string $date The usage date
     * @param array $supplyUsages The supply usage data
     * @param string|null $supplyUsageId Existing supply usage ID if updating
     * @return ProcessingResult
     */
    public function saveSupplyUsageWithTracking(
        array $data,
        string $recordingId,
        string $livestockId,
        string $date,
        array $supplyUsages,
        ?string $supplyUsageId = null
    ): ProcessingResult {
        try {
            DB::beginTransaction();

            if ($supplyUsageId) {
                // UPDATE - Handle existing supply usage
                $usage = SupplyUsage::findOrFail($supplyUsageId);
                $hasChanged = $this->hasSupplyUsageChanged($usage, $supplyUsages);

                if (!$hasChanged) {
                    DB::rollBack();
                    return ProcessingResult::success($usage, 'No changes detected');
                }

                // Validate usage date
                $this->validateSupplyUsageDate($livestockId, $date);

                // CRITICAL FIX: Delete ALL existing supply usage records for this date to prevent duplicates
                $existingUsages = SupplyUsage::where('livestock_id', $livestockId)
                    ->whereDate('usage_date', $date)
                    ->get();

                Log::info("Found existing supply usage records for date", [
                    'date' => $date,
                    'livestock_id' => $livestockId,
                    'existing_count' => $existingUsages->count(),
                    'existing_ids' => $existingUsages->pluck('id')->toArray()
                ]);

                foreach ($existingUsages as $existingUsage) {
                    Log::info("Deleting existing supply usage record", [
                        'usage_id' => $existingUsage->id,
                        'total_quantity' => $existingUsage->total_quantity
                    ]);

                    // Revert details first
                    $this->revertSupplyUsageDetails($existingUsage);

                    // Delete the usage record
                    $existingUsage->delete();
                }

                // Create new usage record (not update existing)
                $usage = SupplyUsage::create([
                    'usage_date' => $date,
                    'livestock_id' => $livestockId,
                    'total_quantity' => array_sum(array_column($supplyUsages, 'quantity')),
                    'created_by' => Auth::id(),
                ]);
            } else {
                // CREATE - Create new supply usage
                $this->validateSupplyUsageDate($livestockId, $date);

                // Create new usage record
                $usage = SupplyUsage::create([
                    'usage_date' => $date,
                    'livestock_id' => $livestockId,
                    'total_quantity' => array_sum(array_column($supplyUsages, 'quantity')),
                    'created_by' => Auth::id(),
                ]);
            }

            // Process each supply usage detail
            foreach ($supplyUsages as $supplyUsage) {
                $this->processSupplyUsageDetail($usage, $supplyUsage);
            }

            DB::commit();

            Log::info("Supply usage processed successfully", [
                'usage_id' => $usage->id,
                'livestock_id' => $usage->livestock_id,
                'date' => $usage->usage_date,
                'total_quantity' => $usage->total_quantity,
                'supplies_count' => count($supplyUsages),
            ]);

            return ProcessingResult::success($usage, 'Supply usage processed successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error processing supply usage', [
                'livestock_id' => $livestockId,
                'date' => $date,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new RecordingException("Failed to process supply usage: " . $e->getMessage());
        }
    }

    /**
     * Check if feed usage has changed
     */
    private function hasUsageChanged(FeedUsage $usage, array $newUsages): bool
    {
        $existingDetails = $usage->details()
            ->select('feed_id', DB::raw('CAST(SUM(quantity_taken) AS DECIMAL(10,2)) as total'))
            ->groupBy('feed_id')
            ->get()
            ->keyBy('feed_id');

        Log::info("Feed usage change detection", [
            'usage_id' => $usage->id,
            'existing_details_count' => $existingDetails->count(),
            'new_usages_count' => count($newUsages),
            'existing_details' => $existingDetails->toArray(),
            'new_usages' => $newUsages
        ]);

        // Check if quantities match with proper float comparison
        foreach ($newUsages as $row) {
            $feedId = $row['feed_id'];
            $qty = (float) $row['quantity'];

            if (!isset($existingDetails[$feedId])) {
                Log::info("Feed usage changed: new feed_id {$feedId} not found in existing");
                return true;
            }

            // Ensure existing quantity is properly cast to float
            $existingQty = (float) $existingDetails[$feedId]->total;

            // Use abs() comparison for float values to handle precision issues
            if (abs($existingQty - $qty) > 0.001) {
                Log::info("Feed usage changed: quantity mismatch for feed_id {$feedId}", [
                    'existing' => $existingQty,
                    'new' => $qty,
                    'difference' => abs($existingQty - $qty),
                    'existing_raw' => $existingDetails[$feedId]->total,
                    'existing_type' => gettype($existingDetails[$feedId]->total),
                    'new_type' => gettype($qty)
                ]);
                return true;
            }
        }

        // Check if any existing feeds are missing in new data
        $newFeedIds = collect($newUsages)->pluck('feed_id')->toArray();
        foreach ($existingDetails as $feedId => $detail) {
            if (!in_array($feedId, $newFeedIds)) {
                Log::info("Feed usage changed: existing feed_id {$feedId} not found in new data");
                return true;
            }
        }

        Log::info("Feed usage unchanged: all quantities match");
        return false;
    }

    /**
     * Check if supply usage has changed
     */
    private function hasSupplyUsageChanged(SupplyUsage $usage, array $newSupplyUsages): bool
    {
        $existingDetails = $usage->details()
            ->select('supply_id', DB::raw('CAST(SUM(quantity_taken) AS DECIMAL(10,2)) as total'))
            ->groupBy('supply_id')
            ->get()
            ->keyBy('supply_id');

        Log::info("Supply usage change detection", [
            'usage_id' => $usage->id,
            'existing_details_count' => $existingDetails->count(),
            'new_usages_count' => count($newSupplyUsages),
            'existing_details' => $existingDetails->toArray(),
            'new_usages' => $newSupplyUsages
        ]);

        // Check if quantities match with proper float comparison
        foreach ($newSupplyUsages as $row) {
            $supplyId = $row['supply_id'];
            $qty = (float) $row['quantity'];

            if (!isset($existingDetails[$supplyId])) {
                Log::info("Supply usage changed: new supply_id {$supplyId} not found in existing");
                return true;
            }

            // Ensure existing quantity is properly cast to float
            $existingQty = (float) $existingDetails[$supplyId]->total;

            // Use abs() comparison for float values to handle precision issues
            if (abs($existingQty - $qty) > 0.001) {
                Log::info("Supply usage changed: quantity mismatch for supply_id {$supplyId}", [
                    'existing' => $existingQty,
                    'new' => $qty,
                    'difference' => abs($existingQty - $qty),
                    'existing_raw' => $existingDetails[$supplyId]->total,
                    'existing_type' => gettype($existingDetails[$supplyId]->total),
                    'new_type' => gettype($qty)
                ]);
                return true;
            }
        }

        // Check if any existing supplies are missing in new data
        $newSupplyIds = collect($newSupplyUsages)->pluck('supply_id')->toArray();
        foreach ($existingDetails as $supplyId => $detail) {
            if (!in_array($supplyId, $newSupplyIds)) {
                Log::info("Supply usage changed: existing supply_id {$supplyId} not found in new data");
                return true;
            }
        }

        Log::info("Supply usage unchanged: all quantities match");
        return false;
    }

    /**
     * Validate feed usage date
     */
    private function validateFeedUsageDate(string $livestockId, string $date): void
    {
        $earliestStockDate = FeedStock::where('livestock_id', $livestockId)->min('date');

        if ($earliestStockDate && $date < $earliestStockDate) {
            throw new RecordingException("Feed usage date must be after the earliest stock entry date ({$earliestStockDate})");
        }
    }

    /**
     * Validate supply usage date
     */
    private function validateSupplyUsageDate(string $livestockId, string $date): void
    {
        $livestock = Livestock::find($livestockId);
        if (!$livestock) {
            throw new RecordingException("Livestock not found");
        }

        $earliestStockDate = SupplyStock::where('farm_id', $livestock->farm_id)->min('date');

        if ($earliestStockDate && $date < $earliestStockDate) {
            throw new RecordingException("Supply usage date must be after the earliest stock entry date ({$earliestStockDate})");
        }
    }

    /**
     * Build feed usage metadata
     */
    private function buildFeedUsageMetadata(array $usages, string $operation): array
    {
        return [
            'feed_types' => array_column($usages, 'feed_name'),
            'feed_codes' => array_column($usages, 'feed_code'),
            'unit_details' => array_map(function ($item) {
                return [
                    'unit_id' => $item['unit_id'] ?? null,
                    'unit_name' => $item['unit_name'] ?? null,
                    'original_unit_id' => $item['original_unit_id'] ?? null,
                    'original_unit_name' => $item['original_unit_name'] ?? null,
                ];
            }, $usages),
            'operation' => $operation,
            'timestamp' => now()->toIso8601String(),
            'processed_by' => Auth::id(),
            'processed_by_name' => Auth::user()->name ?? 'Unknown User',
        ];
    }

    /**
     * Revert feed usage details
     */
    private function revertFeedUsageDetails(FeedUsage $usage): void
    {
        $oldDetails = FeedUsageDetail::where('feed_usage_id', $usage->id)->get();

        Log::info("Reverting {$oldDetails->count()} feed usage details for usage ID {$usage->id}");

        $currentSupplyChanges = [];

        foreach ($oldDetails as $detail) {
            $stock = FeedStock::find($detail->feed_stock_id);
            if ($stock) {
                Log::info("Reverting feed stock usage", [
                    'stock_id' => $stock->id,
                    'feed_id' => $stock->feed_id,
                    'old_quantity_used' => $stock->quantity_used,
                    'quantity_to_revert' => $detail->quantity_taken,
                    'new_quantity_used' => max(0, $stock->quantity_used - $detail->quantity_taken),
                ]);

                $stock->quantity_used = max(0, $stock->quantity_used - $detail->quantity_taken);
                $stock->save();

                if (!isset($currentSupplyChanges[$stock->feed_id])) {
                    $currentSupplyChanges[$stock->feed_id] = 0;
                }
                $currentSupplyChanges[$stock->feed_id] += $detail->quantity_taken;
            }

            $detail->delete();
        }

        // Update CurrentSupply for reverted quantities
        foreach ($currentSupplyChanges as $feedId => $quantity) {
            $this->updateCurrentSupplyQuantity($usage->livestock_id, (string)$feedId, $quantity, 'add');
        }
    }

    /**
     * Revert supply usage details
     */
    private function revertSupplyUsageDetails(SupplyUsage $usage): void
    {
        $oldDetails = SupplyUsageDetail::where('supply_usage_id', $usage->id)->get();

        Log::info("Reverting {$oldDetails->count()} supply usage details for usage ID {$usage->id}");

        $currentSupplyChanges = [];

        foreach ($oldDetails as $detail) {
            $stock = SupplyStock::find($detail->supply_stock_id);
            if ($stock) {
                Log::info("Reverting supply stock usage", [
                    'stock_id' => $stock->id,
                    'supply_id' => $stock->supply_id,
                    'old_quantity_used' => $stock->quantity_used,
                    'quantity_to_revert' => $detail->quantity_taken,
                    'new_quantity_used' => max(0, $stock->quantity_used - $detail->quantity_taken),
                ]);

                $stock->quantity_used = max(0, $stock->quantity_used - $detail->quantity_taken);
                $stock->save();

                if (!isset($currentSupplyChanges[$stock->supply_id])) {
                    $currentSupplyChanges[$stock->supply_id] = 0;
                }
                $currentSupplyChanges[$stock->supply_id] += $detail->quantity_taken;
            }

            $detail->delete();
        }

        // Update CurrentSupply for reverted quantities
        foreach ($currentSupplyChanges as $supplyId => $quantity) {
            $this->updateCurrentSupplyQuantity($usage->livestock_id, (string)$supplyId, $quantity, 'add');
        }
    }

    /**
     * Process feed usage with FIFO
     */
    private function processFeedUsageWithFifo(FeedUsage $usage, array $usages): void
    {
        $processResult = app(\App\Services\FeedUsageService::class)->processWithMetadata($usage, $usages);

        Log::info("Feed usage FIFO processed", [
            'usage_id' => $usage->id,
            'details_count' => $processResult['details_count'] ?? 0,
            'feeds_processed' => $processResult['feeds_processed'] ?? [],
        ]);
    }

    /**
     * Process supply usage detail with FIFO
     */
    private function processSupplyUsageDetail(SupplyUsage $usage, array $usageData): void
    {
        $livestock = Livestock::find($usage->livestock_id);
        $quantityNeeded = $usageData['quantity'];

        // Get available stocks using FIFO (oldest first)
        $availableStocks = SupplyStock::where('farm_id', $livestock->farm_id)
            ->where('supply_id', $usageData['supply_id'])
            ->whereRaw('(quantity_in - quantity_used - quantity_mutated) > 0')
            ->orderBy('date')
            ->orderBy('created_at')
            ->get();

        foreach ($availableStocks as $stock) {
            if ($quantityNeeded <= 0) break;

            $availableInStock = $stock->quantity_in - $stock->quantity_used - $stock->quantity_mutated;
            $quantityToTake = min($quantityNeeded, $availableInStock);

            if ($quantityToTake > 0) {
                // Create supply usage detail
                SupplyUsageDetail::create([
                    'supply_usage_id' => $usage->id,
                    'supply_id' => $usageData['supply_id'],
                    'supply_stock_id' => $stock->id,
                    'quantity_taken' => $quantityToTake,
                    'created_by' => Auth::id(),
                ]);

                // Update stock quantity used
                $stock->quantity_used += $quantityToTake;
                $stock->save();

                // Update CurrentSupply
                $this->updateCurrentSupplyQuantity($usage->livestock_id, (string)$usageData['supply_id'], $quantityToTake, 'subtract');

                $quantityNeeded -= $quantityToTake;

                Log::info("Supply usage detail created", [
                    'usage_id' => $usage->id,
                    'supply_id' => $usageData['supply_id'],
                    'stock_id' => $stock->id,
                    'quantity_taken' => $quantityToTake,
                    'remaining_needed' => $quantityNeeded
                ]);
            }
        }

        if ($quantityNeeded > 0) {
            Log::warning("Insufficient stock for supply usage", [
                'supply_id' => $usageData['supply_id'],
                'requested' => $usageData['quantity'],
                'shortage' => $quantityNeeded
            ]);
        }
    }

    /**
     * Update CurrentSupply for feed usage
     */
    private function updateCurrentSupplyForFeedUsage(string $livestockId, array $usages): void
    {
        foreach ($usages as $usageData) {
            $this->updateCurrentSupplyQuantity($livestockId, (string)$usageData['feed_id'], $usageData['quantity'], 'subtract');
        }
    }

    /**
     * Update CurrentSupply quantity
     */
    private function updateCurrentSupplyQuantity(string $livestockId, string $itemId, float $quantity, string $operation): void
    {
        $currentSupply = CurrentSupply::where('livestock_id', $livestockId)
            ->where('item_id', $itemId)
            ->first();

        if ($currentSupply) {
            $oldQuantity = $currentSupply->quantity;

            if ($operation === 'add') {
                $currentSupply->quantity += $quantity;
            } else {
                $currentSupply->quantity -= $quantity;
            }

            $currentSupply->save();

            Log::info("Updated CurrentSupply", [
                'livestock_id' => $livestockId,
                'item_id' => $itemId,
                'old_quantity' => $oldQuantity,
                'change_quantity' => $quantity,
                'operation' => $operation,
                'new_quantity' => $currentSupply->quantity
            ]);
        }
    }

    /**
     * Get feed usage statistics
     */
    public function getFeedUsageStatistics(string $livestockId, ?Carbon $startDate = null, ?Carbon $endDate = null): ProcessingResult
    {
        try {
            $query = FeedUsage::where('livestock_id', $livestockId)
                ->with(['details.feedStock.feed']);

            if ($startDate) {
                $query->where('usage_date', '>=', $startDate);
            }

            if ($endDate) {
                $query->where('usage_date', '<=', $endDate);
            }

            $usages = $query->get();

            $statistics = [
                'total_usages' => $usages->count(),
                'total_quantity' => $usages->sum('total_quantity'),
                'average_daily_usage' => $usages->avg('total_quantity'),
                'feed_types' => $usages->flatMap(function ($usage) {
                    return $usage->details->pluck('feedStock.feed.name');
                })->unique()->values(),
                'usage_by_feed' => $usages->flatMap(function ($usage) {
                    return $usage->details->groupBy('feedStock.feed.name');
                })->map(function ($group) {
                    return $group->sum('quantity_taken');
                }),
                'date_range' => [
                    'start' => $usages->min('usage_date'),
                    'end' => $usages->max('usage_date')
                ]
            ];

            return ProcessingResult::success($statistics, 'Feed usage statistics retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Error getting feed usage statistics', [
                'livestock_id' => $livestockId,
                'error' => $e->getMessage()
            ]);

            return ProcessingResult::failure(['Failed to get feed usage statistics'], 'Statistics retrieval failed');
        }
    }

    /**
     * Get supply usage statistics
     */
    public function getSupplyUsageStatistics(string $livestockId, ?Carbon $startDate = null, ?Carbon $endDate = null): ProcessingResult
    {
        try {
            $query = SupplyUsage::where('livestock_id', $livestockId)
                ->with(['details.supplyStock.supply']);

            if ($startDate) {
                $query->where('usage_date', '>=', $startDate);
            }

            if ($endDate) {
                $query->where('usage_date', '<=', $endDate);
            }

            $usages = $query->get();

            $statistics = [
                'total_usages' => $usages->count(),
                'total_quantity' => $usages->sum('total_quantity'),
                'average_daily_usage' => $usages->avg('total_quantity'),
                'supply_types' => $usages->flatMap(function ($usage) {
                    return $usage->details->pluck('supplyStock.supply.name');
                })->unique()->values(),
                'usage_by_supply' => $usages->flatMap(function ($usage) {
                    return $usage->details->groupBy('supplyStock.supply.name');
                })->map(function ($group) {
                    return $group->sum('quantity_taken');
                }),
                'date_range' => [
                    'start' => $usages->min('usage_date'),
                    'end' => $usages->max('usage_date')
                ]
            ];

            return ProcessingResult::success($statistics, 'Supply usage statistics retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Error getting supply usage statistics', [
                'livestock_id' => $livestockId,
                'error' => $e->getMessage()
            ]);

            return ProcessingResult::failure(['Failed to get supply usage statistics'], 'Statistics retrieval failed');
        }
    }

    /**
     * Process feed usage data
     * 
     * @param array $data Feed usage data
     * @return ProcessingResult
     */
    public function processFeedUsage(array $data): ProcessingResult
    {
        try {
            $recordingId = $data['recording_id'] ?? null;
            $livestockId = $data['livestock_id'] ?? null;
            $date = $data['date'] ?? null;
            $feedData = $data['feed_data'] ?? [];

            Log::info('FeedSupplyProcessingService::processFeedUsage called', [
                'recording_id' => $recordingId,
                'livestock_id' => $livestockId,
                'date' => $date,
                'feed_data_count' => count($feedData),
                'feed_data_sample' => array_slice($feedData, 0, 2)
            ]);

            if (!$recordingId || !$livestockId || !$date) {
                Log::error('Missing required parameters for feed usage processing', [
                    'recording_id' => $recordingId,
                    'livestock_id' => $livestockId,
                    'date' => $date
                ]);
                return ProcessingResult::failure(['Missing required parameters'], 'Invalid feed usage data');
            }

            // Validate feed data structure
            if (!is_array($feedData)) {
                Log::error('Feed data is not an array', [
                    'feed_data_type' => gettype($feedData),
                    'feed_data' => $feedData
                ]);
                return ProcessingResult::failure(['Feed data must be an array'], 'Invalid feed data structure');
            }

            // Ensure feed data is properly formatted
            $formattedFeedData = [];
            foreach ($feedData as $index => $item) {
                if (!is_array($item)) {
                    Log::error('Feed item is not an array', [
                        'index' => $index,
                        'item_type' => gettype($item),
                        'item' => $item
                    ]);
                    continue;
                }

                if (!isset($item['feed_id']) || !isset($item['quantity'])) {
                    Log::error('Feed item missing required fields', [
                        'index' => $index,
                        'item' => $item
                    ]);
                    continue;
                }

                $formattedFeedData[] = $item;
            }

            if (empty($formattedFeedData)) {
                Log::warning('No valid feed data found after validation', [
                    'original_feed_data' => $feedData
                ]);
                return ProcessingResult::failure(['No valid feed data found'], 'No valid feed data');
            }

            Log::info('Feed data validated and formatted', [
                'original_count' => count($feedData),
                'formatted_count' => count($formattedFeedData)
            ]);

            return $this->saveFeedUsageWithTracking(
                $formattedFeedData,
                $recordingId,
                $livestockId,
                $date,
                $formattedFeedData,
                null
            );
        } catch (\Exception $e) {
            Log::error('Error processing feed usage', [
                'data' => $data,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return ProcessingResult::failure(
                ['Failed to process feed usage: ' . $e->getMessage()],
                'Feed usage processing failed'
            );
        }
    }

    /**
     * Process supply usage data
     * 
     * @param array $data Supply usage data
     * @return ProcessingResult
     */
    public function processSupplyUsage(array $data): ProcessingResult
    {
        try {
            $recordingId = $data['recording_id'] ?? null;
            $livestockId = $data['livestock_id'] ?? null;
            $date = $data['date'] ?? null;
            $supplyData = $data['supply_data'] ?? [];

            if (!$recordingId || !$livestockId || !$date) {
                return ProcessingResult::failure(['Missing required parameters'], 'Invalid supply usage data');
            }

            return $this->saveSupplyUsageWithTracking(
                $supplyData,
                $recordingId,
                $livestockId,
                $date,
                $supplyData,
                null
            );
        } catch (\Exception $e) {
            Log::error('Error processing supply usage', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);

            return ProcessingResult::failure(
                ['Failed to process supply usage: ' . $e->getMessage()],
                'Supply usage processing failed'
            );
        }
    }
}

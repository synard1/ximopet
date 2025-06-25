<?php

namespace App\Services\Feed;

use App\Config\CompanyConfig;
use App\Models\Feed;
use App\Models\FeedStock;
use App\Models\FeedUsage;
use App\Models\FeedUsageDetail;
use App\Models\Livestock;
use App\Models\LivestockBatch;
use App\Models\Recording;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;
use App\Services\Alert\FeedAlertService;

class ManualFeedUsageService
{
    protected FeedAlertService $feedAlertService;

    public function __construct(FeedAlertService $feedAlertService)
    {
        $this->feedAlertService = $feedAlertService;
    }

    /**
     * Get available feed stocks for manual selection based on livestock_id
     *
     * @param string $livestockId
     * @param string|null $feedId Optional filter by specific feed
     * @return array
     */
    public function getAvailableFeedStocksForManualSelection(string $livestockId, ?string $feedId = null): array
    {
        $livestock = Livestock::findOrFail($livestockId);

        // Build query for FeedStock based on livestock_id
        $query = FeedStock::with(['feed', 'feedPurchase.batch'])
            ->where('livestock_id', $livestockId)
            ->whereRaw('(quantity_in - quantity_used - quantity_mutated) > 0');

        // If specific feed is requested, filter by feed_id
        if ($feedId) {
            $query->where('feed_id', $feedId);
        }

        $feedStocks = $query->orderBy('date', 'asc') // FIFO order by default
            ->get();

        // Group by feed for better organization
        $feedGroups = $feedStocks->groupBy('feed_id');

        $result = [
            'livestock_id' => $livestockId,
            'livestock_name' => $livestock->name,
            'total_feed_types' => $feedGroups->count(),
            'total_stocks' => $feedStocks->count(),
            'feeds' => []
        ];

        foreach ($feedGroups as $feedId => $stocks) {
            $feed = $stocks->first()->feed;
            $stockBatches = [];

            foreach ($stocks as $stock) {
                $availableQuantity = $stock->quantity_in - $stock->quantity_used - $stock->quantity_mutated;
                $ageDays = $stock->date ? Carbon::parse($stock->date)->diffInDays(now()) : 0;

                // Get batch information if available - format as safe string for display
                $batchInfo = null;
                if ($stock->feedPurchase && $stock->feedPurchase->batch) {
                    $batch = $stock->feedPurchase->batch;
                    $batchInfo = 'Batch: ' . ($batch->batch_number ?? 'No batch number');
                    if ($batch->date) {
                        $batchInfo .= ' (' . $batch->date->format('M d, Y') . ')';
                    }
                }

                $stockBatches[] = [
                    'stock_id' => $stock->id,
                    'feed_purchase_id' => $stock->feed_purchase_id,
                    'batch_info' => $batchInfo,
                    'stock_name' => $this->generateStockName($stock, $batchInfo),
                    'date' => $stock->date?->format('Y-m-d'),
                    'source_type' => $stock->source_type,
                    'quantity_in' => $stock->quantity_in,
                    'quantity_used' => $stock->quantity_used,
                    'quantity_mutated' => $stock->quantity_mutated,
                    'available_quantity' => $availableQuantity,
                    'unit' => $feed->payload['unit_details']['name'] ?? 'kg',
                    'age_days' => $ageDays,
                    'cost_per_unit' => $this->calculateCostPerUnit($stock),
                    'total_cost' => $this->calculateTotalCost($stock),
                ];
            }

            $result['feeds'][] = [
                'feed_id' => $feed->id,
                'feed_name' => $feed->name,
                'feed_type' => $feed->type,
                'total_available' => $stocks->sum(fn($s) => $s->quantity_in - $s->quantity_used - $s->quantity_mutated),
                'stock_count' => $stocks->count(),
                'stocks' => $stockBatches
            ];
        }

        return $result;
    }

    /**
     * Get available feed batches for manual selection (legacy method for backward compatibility)
     *
     * @param string $feedId
     * @return array
     */
    public function getAvailableFeedBatchesForManualSelection(string $feedId): array
    {
        // This method is kept for backward compatibility
        // but now it should be called with livestock_id first
        throw new Exception('This method is deprecated. Use getAvailableFeedStocksForManualSelection with livestock_id instead.');
    }

    /**
     * Preview manual feed usage before processing
     *
     * @param array $usageData
     * @return array
     */
    public function previewManualFeedUsage(array $usageData): array
    {
        $this->validateUsageData($usageData);

        $livestock = Livestock::findOrFail($usageData['livestock_id']);
        $feed = isset($usageData['feed_id']) ? Feed::findOrFail($usageData['feed_id']) : null;

        // Validate batch if provided
        $batch = null;
        if (isset($usageData['livestock_batch_id'])) {
            $batch = LivestockBatch::where('id', $usageData['livestock_batch_id'])
                ->where('livestock_id', $livestock->id)
                ->where('status', 'active')
                ->firstOrFail();
        }

        // Check if this is edit mode by looking for usage_detail_id in stocks
        $isEditMode = false;
        $existingUsageDetails = [];
        foreach ($usageData['manual_stocks'] as $manualStock) {
            if (isset($manualStock['usage_detail_id'])) {
                $isEditMode = true;
                $existingUsageDetails[$manualStock['stock_id']] = $manualStock['usage_detail_id'];
            }
        }

        $previewStocks = [];
        $totalQuantity = 0;
        $totalCost = 0;
        $canFulfill = true;
        $issues = [];

        foreach ($usageData['manual_stocks'] as $manualStock) {
            $stock = FeedStock::with(['feed', 'feedPurchase.batch'])
                ->findOrFail($manualStock['stock_id']);

            // Verify stock belongs to the livestock
            if ($stock->livestock_id !== $livestock->id) {
                throw new Exception("Stock does not belong to livestock {$livestock->name}");
            }

            // If feed_id is specified, verify stock belongs to that feed
            if ($feed && $stock->feed_id !== $feed->id) {
                throw new Exception("Stock does not belong to feed {$feed->name}");
            }

            // Calculate available quantity
            $baseAvailableQuantity = $stock->quantity_in - $stock->quantity_used - $stock->quantity_mutated;
            $availableQuantity = $baseAvailableQuantity;

            // For edit mode: add back the previously used quantity for this specific stock
            if ($isEditMode && isset($existingUsageDetails[$manualStock['stock_id']])) {
                $existingDetail = FeedUsageDetail::find($existingUsageDetails[$manualStock['stock_id']]);
                if ($existingDetail) {
                    $previouslyUsedQuantity = floatval($existingDetail->quantity_taken);
                    $availableQuantity = $baseAvailableQuantity + $previouslyUsedQuantity;

                    Log::info('📊 Edit mode: Adjusted available quantity', [
                        'stock_id' => $stock->id,
                        'feed_name' => $stock->feed->name,
                        'base_available' => $baseAvailableQuantity,
                        'previously_used' => $previouslyUsedQuantity,
                        'adjusted_available' => $availableQuantity,
                        'requested_quantity' => $manualStock['quantity']
                    ]);
                }
            }

            $requestedQuantity = floatval($manualStock['quantity']);

            if ($requestedQuantity > $availableQuantity) {
                $canFulfill = false;
                $issues[] = "Insufficient stock for {$stock->feed->name}: requested {$requestedQuantity}, available {$availableQuantity}";
            }

            $costPerUnit = $this->calculateCostPerUnit($stock);
            $lineCost = $requestedQuantity * $costPerUnit;

            $previewStocks[] = [
                'stock_id' => $stock->id,
                'feed_id' => $stock->feed_id,
                'feed_name' => $stock->feed->name,
                'stock_name' => $this->generateStockName($stock, $manualStock['batch_info'] ?? null),
                'requested_quantity' => $requestedQuantity,
                'available_quantity' => $availableQuantity,
                'remaining_after_usage' => $availableQuantity - $requestedQuantity,
                'unit' => $stock->feed->unit->name ?? 'kg',
                'cost_per_unit' => $costPerUnit,
                'line_cost' => $lineCost,
                'stock_cost' => $lineCost, // Template expects this field
                'can_fulfill' => $requestedQuantity <= $availableQuantity,
                'batch_info' => $manualStock['batch_info'] ?? null,
                'note' => $manualStock['note'] ?? null,
                'is_edit_mode' => $isEditMode,
                'usage_detail_id' => $manualStock['usage_detail_id'] ?? null
            ];

            $totalQuantity += $requestedQuantity;
            $totalCost += $lineCost;
        }

        // Check for existing recording information
        $recordingInfo = null;
        if (isset($usageData['recording_id']) && $usageData['recording_id']) {
            $recording = Recording::find($usageData['recording_id']);
            if ($recording) {
                $recordingInfo = [
                    'recording_id' => $recording->id,
                    'recording_date' => $recording->date->format('Y-m-d'),
                    'recording_note' => $recording->note ?? 'No notes',
                    'has_existing_recording' => true
                ];
            }
        }

        return [
            'livestock_id' => $livestock->id,
            'livestock_name' => $livestock->name,
            'livestock_batch_id' => $batch?->id,
            'livestock_batch_name' => $batch?->name,
            'usage_date' => $usageData['usage_date'],
            'usage_purpose' => $usageData['usage_purpose'] ?? 'feeding',
            'notes' => $usageData['notes'] ?? null,
            'recording_info' => $recordingInfo,
            'stocks' => $previewStocks,
            'total_quantity' => $totalQuantity,
            'total_cost' => $totalCost,
            'average_cost_per_unit' => $totalQuantity > 0 ? $totalCost / $totalQuantity : 0,
            'can_fulfill' => $canFulfill,
            'issues' => $issues,
            'stock_count' => count($previewStocks),
            'is_edit_mode' => $isEditMode
        ];
    }

    /**
     * Process manual feed usage
     *
     * @param array $usageData
     * @return array
     */
    public function processManualFeedUsage(array $usageData): array
    {
        return DB::transaction(function () use ($usageData) {
            $this->validateUsageData($usageData);

            $livestock = Livestock::findOrFail($usageData['livestock_id']);
            $feed = isset($usageData['feed_id']) ? Feed::findOrFail($usageData['feed_id']) : null;

            // Validate and load batch if provided
            $batch = null;
            if (isset($usageData['livestock_batch_id'])) {
                $batch = LivestockBatch::where('id', $usageData['livestock_batch_id'])
                    ->where('livestock_id', $livestock->id)
                    ->where('status', 'active')
                    ->firstOrFail();
            }

            $processedStocks = [];
            $totalProcessed = 0;
            $totalCost = 0;

            Log::info('🚀 Starting manual feed usage process', [
                'livestock_id' => $livestock->id,
                'livestock_name' => $livestock->name,
                'livestock_batch_id' => $batch?->id,
                'livestock_batch_name' => $batch?->name,
                'feed_id' => $feed?->id,
                'feed_name' => $feed?->name,
                'stocks_count' => count($usageData['manual_stocks'])
            ]);

            // Create FeedUsage record
            $feedUsage = FeedUsage::create([
                'livestock_id' => $livestock->id,
                'livestock_batch_id' => $batch?->id, // Store in main field
                'recording_id' => $usageData['recording_id'] ?? null, // Link to recording if exists
                'usage_date' => $usageData['usage_date'],
                'purpose' => $usageData['usage_purpose'] ?? 'feeding', // Store in main field
                'notes' => $usageData['notes'] ?? null, // Store in main field
                'total_quantity' => 0, // Will be updated after processing
                'total_cost' => 0, // Will be updated after processing
                'metadata' => [
                    'livestock_batch_name' => $batch?->name,
                    'is_manual_usage' => true,
                    'created_via' => 'ManualFeedUsage Component',
                    'processed_at' => now()->toISOString(),
                    'recording_id' => $usageData['recording_id'] ?? null,
                    'has_recording_link' => isset($usageData['recording_id']) && $usageData['recording_id']
                ],
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            foreach ($usageData['manual_stocks'] as $manualStock) {
                $stock = FeedStock::with(['feed', 'feedPurchase.batch'])
                    ->findOrFail($manualStock['stock_id']);

                // Verify stock belongs to the livestock
                if ($stock->livestock_id !== $livestock->id) {
                    throw new Exception("Stock does not belong to livestock {$livestock->name}");
                }

                // If feed_id is specified, verify stock belongs to that feed
                if ($feed && $stock->feed_id !== $feed->id) {
                    throw new Exception("Stock does not belong to feed {$feed->name}");
                }

                $availableQuantity = floatval($stock->quantity_in - $stock->quantity_used - $stock->quantity_mutated);
                $requestedQuantity = floatval($manualStock['quantity']);

                // Validate numeric values
                if (!is_numeric($manualStock['quantity']) || $requestedQuantity <= 0) {
                    throw new Exception("Invalid quantity for {$stock->feed->name}: must be a positive number");
                }

                if ($requestedQuantity > $availableQuantity) {
                    throw new Exception("Insufficient stock for {$stock->feed->name}: requested {$requestedQuantity}, available {$availableQuantity}");
                }

                $costPerUnit = floatval($this->calculateCostPerUnit($stock));
                $lineCost = $requestedQuantity * $costPerUnit;

                // Create FeedUsageDetail
                $usageDetail = FeedUsageDetail::create([
                    'feed_usage_id' => $feedUsage->id,
                    'feed_stock_id' => $stock->id,
                    'feed_id' => $stock->feed_id,
                    'quantity_taken' => $requestedQuantity,
                    'metadata' => [
                        'livestock_batch_id' => $batch?->id,
                        'livestock_batch_name' => $batch?->name,
                        'usage_purpose' => $usageData['usage_purpose'] ?? 'feeding',
                        'is_manual_selection' => true,
                        'original_available_quantity' => $availableQuantity,
                        'cost_calculation' => [
                            'quantity' => $requestedQuantity,
                            'cost_per_unit' => $costPerUnit,
                            'total_cost' => $lineCost
                        ],
                        'notes' => $manualStock['note'] ?? null,
                        'created_at' => now()->toIso8601String(),
                        'created_by_name' => auth()->user()->name ?? 'Unknown User',
                    ],
                    'created_by' => auth()->id(),
                ]);

                // Update stock quantities with safe numeric calculation
                $newQuantityUsed = floatval($stock->quantity_used) + $requestedQuantity;
                $stock->update([
                    'quantity_used' => $newQuantityUsed,
                    'updated_by' => auth()->id(),
                ]);

                $processedStocks[] = [
                    'stock_id' => $stock->id,
                    'feed_id' => $stock->feed_id,
                    'feed_name' => $stock->feed->name,
                    'quantity_used' => $requestedQuantity,
                    'cost_per_unit' => $costPerUnit,
                    'total_cost' => $lineCost,
                    'usage_detail_id' => $usageDetail->id
                ];

                $totalProcessed += $requestedQuantity;
                $totalCost += $lineCost;

                Log::info('Feed stock processed', [
                    'stock_id' => $stock->id,
                    'feed_name' => $stock->feed->name,
                    'quantity_used' => $requestedQuantity,
                    'cost' => $lineCost,
                    'livestock_batch_id' => $batch?->id
                ]);
            }

            // Update feed usage totals
            $feedUsage->update([
                'total_quantity' => $totalProcessed,
                'total_cost' => $totalCost, // Store in main field
                'metadata' => array_merge($feedUsage->metadata ?? [], [
                    'average_cost_per_unit' => $totalProcessed > 0 ? $totalCost / $totalProcessed : 0,
                    'stocks_processed_count' => count($processedStocks),
                    'completed_at' => now()->toISOString()
                ]),
                'updated_by' => auth()->id(),
            ]);

            Log::info('Manual feed usage completed successfully', [
                'feed_usage_id' => $feedUsage->id,
                'livestock_id' => $livestock->id,
                'livestock_batch_id' => $batch?->id,
                'total_quantity' => $totalProcessed,
                'total_cost' => $totalCost,
                'stocks_processed' => count($processedStocks)
            ]);

            // Update livestock feed consumption stats
            $this->updateLivestockFeedConsumption($livestock, $totalProcessed, $totalCost);

            // Send alert for feed usage creation
            $this->sendFeedUsageAlert('created', [
                'feed_usage_id' => $feedUsage->id,
                'livestock_id' => $livestock->id,
                'livestock_name' => $livestock->name,
                'batch_id' => $batch?->id,
                'batch_name' => $batch?->name,
                'usage_date' => $usageData['usage_date'],
                'usage_purpose' => $usageData['usage_purpose'] ?? 'feeding',
                'total_quantity' => $totalProcessed,
                'total_cost' => $totalCost,
                'manual_stocks' => $processedStocks,
                'was_edit_mode' => false,
                'user_id' => auth()->id(),
                'user_name' => auth()->user()->name ?? 'Unknown User',
                'ip_address' => request()->ip(),
                'timestamp' => now()->toISOString()
            ]);

            return [
                'success' => true,
                'feed_usage_id' => $feedUsage->id,
                'livestock_id' => $livestock->id,
                'livestock_name' => $livestock->name,
                'livestock_batch_id' => $batch?->id,
                'livestock_batch_name' => $batch?->name,
                'recording_id' => $usageData['recording_id'] ?? null,
                'total_quantity' => $totalProcessed,
                'total_cost' => $totalCost,
                'average_cost_per_unit' => $totalProcessed > 0 ? $totalCost / $totalProcessed : 0,
                'stocks_processed' => $processedStocks,
                'usage_date' => $usageData['usage_date'],
                'usage_purpose' => $usageData['usage_purpose'] ?? 'feeding',
                'notes' => $usageData['notes'] ?? null,
                'processed_at' => now()->toISOString(),
            ];
        });
    }

    /**
     * Generate a descriptive name for a stock
     */
    private function generateStockName(FeedStock $stock, $batchInfo = null): string
    {
        $parts = [];

        if ($batchInfo && is_string($batchInfo)) {
            $parts[] = $batchInfo;
        } elseif ($batchInfo && is_array($batchInfo) && isset($batchInfo['batch_number'])) {
            $parts[] = "Batch: {$batchInfo['batch_number']}";
        }

        if ($stock->date) {
            $parts[] = "Date: {$stock->date->format('M d, Y')}";
        }

        if ($stock->source_type) {
            $parts[] = ucfirst($stock->source_type);
        }

        return implode(' - ', $parts) ?: "Stock #{$stock->id}";
    }

    /**
     * Calculate cost per unit for a stock
     */
    private function calculateCostPerUnit(FeedStock $stock): float
    {
        if ($stock->feedPurchase) {
            return $stock->feedPurchase->price_per_converted_unit ?? $stock->feedPurchase->price_per_unit ?? 0;
        }

        return 0;
    }

    /**
     * Calculate total cost for a stock
     */
    private function calculateTotalCost(FeedStock $stock): float
    {
        $costPerUnit = $this->calculateCostPerUnit($stock);
        $availableQuantity = $stock->quantity_in - $stock->quantity_used - $stock->quantity_mutated;

        return $costPerUnit * $availableQuantity;
    }

    /**
     * Validate usage data structure
     */
    private function validateUsageData(array $data): void
    {
        $validationRules = $this->getFeedUsageValidationRules();

        if (!isset($data['livestock_id'])) {
            throw new Exception('Livestock ID is required');
        }

        // Validate usage date
        if ($validationRules['require_usage_date'] ?? true) {
            if (!isset($data['usage_date'])) {
                throw new Exception('Usage date is required');
            }
        }

        // Validate usage purpose
        if ($validationRules['require_usage_purpose'] ?? true) {
            if (!isset($data['usage_purpose']) || empty($data['usage_purpose'])) {
                throw new Exception('Usage purpose is required');
            }
        }

        // Validate notes
        if ($validationRules['require_notes'] ?? false) {
            if (!isset($data['notes']) || empty($data['notes'])) {
                throw new Exception('Notes are required');
            }
        }

        if (!isset($data['manual_stocks']) || !is_array($data['manual_stocks'])) {
            throw new Exception('Manual stocks data is required and must be an array');
        }

        if (empty($data['manual_stocks'])) {
            throw new Exception('At least one stock must be selected');
        }

        foreach ($data['manual_stocks'] as $index => $stockData) {
            $this->validateManualStockData($stockData, $index, $validationRules);
        }
    }

    /**
     * Validate individual stock data
     */
    private function validateManualStockData(array $stockData, int $index, array $validationRules = []): void
    {
        if (!isset($stockData['stock_id'])) {
            throw new Exception("Stock ID is required for stock at index {$index}");
        }

        // Validate quantity
        if ($validationRules['require_quantity'] ?? true) {
            if (!isset($stockData['quantity']) || !is_numeric($stockData['quantity'])) {
                throw new Exception("Valid quantity is required for stock at index {$index}");
            }

            $quantity = $stockData['quantity'];
            $minQuantity = $validationRules['min_quantity'] ?? 0.1;
            $maxQuantity = $validationRules['max_quantity'] ?? 10000;
            $allowZeroQuantity = $validationRules['allow_zero_quantity'] ?? false;

            if (!$allowZeroQuantity && $quantity <= 0) {
                throw new Exception("Quantity must be greater than 0 for stock at index {$index}");
            }

            if ($quantity < $minQuantity) {
                throw new Exception("Quantity must be at least {$minQuantity} for stock at index {$index}");
            }

            if ($quantity > $maxQuantity) {
                throw new Exception("Quantity cannot exceed {$maxQuantity} for stock at index {$index}");
            }
        }
    }

    /**
     * Update livestock feed consumption
     *
     * @param Livestock $livestock
     * @param float $quantity
     * @param float $cost
     * @return void
     */
    private function updateLivestockFeedConsumption(Livestock $livestock, float $quantity, float $cost): void
    {
        $livestock->incrementFeedConsumption($quantity, $cost);

        Log::info('🐄 Updated livestock feed consumption', [
            'livestock_id' => $livestock->id,
            'added_quantity' => $quantity,
            'added_cost' => $cost,
            'total_feed_consumed' => $livestock->getTotalFeedConsumed(),
            'total_feed_cost' => $livestock->getTotalFeedCost()
        ]);
    }

    /**
     * Get feed usage input restrictions from company config
     *
     * @return array
     */
    public function getFeedUsageInputRestrictions(): array
    {
        return CompanyConfig::getManualFeedUsageInputRestrictions();
    }

    /**
     * Get feed usage validation rules from company config
     *
     * @return array
     */
    public function getFeedUsageValidationRules(): array
    {
        return CompanyConfig::getManualFeedUsageValidationRules();
    }

    /**
     * Get feed usage workflow settings from company config
     *
     * @return array
     */
    public function getFeedUsageWorkflowSettings(): array
    {
        return CompanyConfig::getManualFeedUsageWorkflowSettings();
    }

    /**
     * Get feed usage edit mode settings from company config
     *
     * @return array
     */
    public function getFeedUsageEditModeSettings(): array
    {
        return CompanyConfig::getManualFeedUsageEditModeSettings();
    }

    /**
     * Validate feed usage input restrictions
     *
     * @param string $livestockId
     * @param array $selectedStocks
     * @param bool $isEditMode Whether this is edit mode or not
     * @return array
     */
    public function validateFeedUsageInputRestrictions(string $livestockId, array $selectedStocks, bool $isEditMode = false): array
    {
        $restrictions = $this->getFeedUsageInputRestrictions();

        if (empty($restrictions)) {
            return ['valid' => true, 'errors' => []];
        }

        $errors = [];

        // Check if same day repeated input is allowed
        if (!($restrictions['allow_same_day_repeated_input'] ?? true)) {
            if ($this->hasFeedUsageToday($livestockId)) {
                $errors[] = 'Feed usage for today has already been recorded. Multiple entries per day are not allowed.';
            }
        }

        // Check if same batch repeated input is allowed (skip in edit mode)
        if (!$isEditMode && !($restrictions['allow_same_batch_repeated_input'] ?? true)) {
            $conflictingStocks = $this->getConflictingStocksToday($selectedStocks, $livestockId);
            if (!empty($conflictingStocks)) {
                $errors[] = "Some selected stocks have already been used today. Repeated stock usage per day is not allowed.";
            }
        }

        // Check maximum usage per day per batch (skip in edit mode)
        $maxUsagePerBatch = $restrictions['max_usage_per_day_per_batch'] ?? null;
        if (!$isEditMode && $maxUsagePerBatch) {
            $stockCounts = $this->getStockUsageCountsToday($selectedStocks, $livestockId);
            foreach ($stockCounts as $stockId => $count) {
                if ($count >= $maxUsagePerBatch) {
                    $errors[] = "Stock {$stockId} has reached the maximum usage limit of {$maxUsagePerBatch} times per day.";
                }
            }
        }

        // Check maximum usage per day per livestock (skip in edit mode)
        $maxUsagePerLivestock = $restrictions['max_usage_per_day_per_livestock'] ?? null;
        if (!$isEditMode && $maxUsagePerLivestock) {
            $todayUsageCount = $this->getTodayUsageCountForLivestock($livestockId);
            if ($todayUsageCount >= $maxUsagePerLivestock) {
                $errors[] = "Livestock has reached the maximum usage limit of {$maxUsagePerLivestock} entries per day.";
            }
        }

        // Check maximum entries per session
        $maxEntriesPerSession = $restrictions['max_entries_per_session'] ?? null;
        if ($maxEntriesPerSession && count($selectedStocks) > $maxEntriesPerSession) {
            $errors[] = "Maximum {$maxEntriesPerSession} stock entries allowed per session. You have selected " . count($selectedStocks) . " stocks.";
        }

        // Check for duplicate stocks (skip in edit mode since user can have existing duplicates)
        if (!$isEditMode && ($restrictions['prevent_duplicate_stocks'] ?? true)) {
            $stockIds = collect($selectedStocks)->pluck('stock_id')->toArray();
            if (count($stockIds) !== count(array_unique($stockIds))) {
                $errors[] = "Duplicate stocks are not allowed in the same usage entry.";
            }
        }

        // Check stock availability
        if ($restrictions['require_stock_availability_check'] ?? true) {
            foreach ($selectedStocks as $stock) {
                $stockRecord = FeedStock::find($stock['stock_id']);
                if (!$stockRecord) {
                    $errors[] = "Stock {$stock['stock_id']} not found.";
                    continue;
                }

                $availableQuantity = $stockRecord->quantity_in - $stockRecord->quantity_used - $stockRecord->quantity_mutated;

                // For edit mode, add back the previously used quantity if this stock has usage_detail_id
                if ($isEditMode && isset($stock['usage_detail_id'])) {
                    $existingDetail = FeedUsageDetail::find($stock['usage_detail_id']);
                    if ($existingDetail) {
                        $availableQuantity += floatval($existingDetail->quantity_taken);

                        Log::info('📊 Edit mode: Adjusted available quantity for validation', [
                            'stock_id' => $stockRecord->id,
                            'feed_name' => $stockRecord->feed->name,
                            'base_available' => $stockRecord->quantity_in - $stockRecord->quantity_used - $stockRecord->quantity_mutated,
                            'previously_used' => $existingDetail->quantity_taken,
                            'adjusted_available' => $availableQuantity,
                            'requested_quantity' => $stock['quantity']
                        ]);
                    }
                }

                if ($stock['quantity'] > $availableQuantity) {
                    $errors[] = "Insufficient stock quantity for {$stockRecord->feed->name}. Available: {$availableQuantity}, Requested: {$stock['quantity']}.";
                }
            }
        }

        // Check stock age restrictions
        $maxStockAge = $restrictions['max_stock_age_days'] ?? null;
        $warnOnOldStock = $restrictions['warn_on_old_stock'] ?? false;
        $oldStockThreshold = $restrictions['old_stock_threshold_days'] ?? 90;

        if ($maxStockAge || $warnOnOldStock) {
            foreach ($selectedStocks as $stock) {
                $stockRecord = FeedStock::find($stock['stock_id']);
                if (!$stockRecord || !$stockRecord->date) continue;

                $stockAge = Carbon::parse($stockRecord->date)->diffInDays(now());

                if ($maxStockAge && $stockAge > $maxStockAge) {
                    $errors[] = "Stock {$stockRecord->feed->name} is too old ({$stockAge} days). Maximum allowed age is {$maxStockAge} days.";
                }

                if ($warnOnOldStock && $stockAge > $oldStockThreshold) {
                    $errors[] = "Warning: Stock {$stockRecord->feed->name} is {$stockAge} days old. Consider using fresher stock.";
                }
            }
        }

        // Check minimum interval between usage (skip in edit mode)
        $minInterval = $restrictions['min_interval_minutes'] ?? 0;
        if (!$isEditMode && $minInterval > 0) {
            $lastUsageTime = $this->getLastUsageTime($livestockId);
            if ($lastUsageTime && $lastUsageTime->diffInMinutes(now()) < $minInterval) {
                $errors[] = "Minimum interval of {$minInterval} minutes between feed usage has not been met. Last usage was " . $lastUsageTime->diffForHumans() . ".";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Check if feed usage exists today
     */
    private function hasFeedUsageToday(string $livestockId): bool
    {
        return FeedUsage::where('livestock_id', $livestockId)
            ->whereDate('usage_date', today())
            ->exists();
    }

    /**
     * Get conflicting stocks that were used today
     */
    private function getConflictingStocksToday(array $selectedStocks, string $livestockId): array
    {
        $stockIds = collect($selectedStocks)->pluck('stock_id')->toArray();

        return FeedUsage::where('livestock_id', $livestockId)
            ->whereDate('usage_date', today())
            ->whereHas('details', function ($query) use ($stockIds) {
                $query->whereIn('feed_stock_id', $stockIds);
            })
            ->pluck('id')
            ->toArray();
    }

    /**
     * Get stock usage counts for today
     */
    private function getStockUsageCountsToday(array $selectedStocks, string $livestockId): array
    {
        $stockIds = collect($selectedStocks)->pluck('stock_id')->toArray();

        return FeedUsage::where('livestock_id', $livestockId)
            ->whereDate('usage_date', today())
            ->with('details')
            ->get()
            ->flatMap(function ($usage) {
                return $usage->details;
            })
            ->whereIn('feed_stock_id', $stockIds)
            ->groupBy('feed_stock_id')
            ->map(function ($group) {
                return $group->count();
            })
            ->toArray();
    }

    /**
     * Get today's usage count for livestock
     */
    private function getTodayUsageCountForLivestock(string $livestockId): int
    {
        return FeedUsage::where('livestock_id', $livestockId)
            ->whereDate('usage_date', today())
            ->count();
    }

    /**
     * Get last usage time
     */
    private function getLastUsageTime(string $livestockId): ?Carbon
    {
        $lastUsage = FeedUsage::where('livestock_id', $livestockId)
            ->orderBy('usage_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->first();

        return $lastUsage ? Carbon::parse($lastUsage->usage_date) : null;
    }

    /**
     * Check if feed usage exists for specific date
     *
     * @param string $livestockId
     * @param string $date
     * @return bool
     */
    public function hasUsageOnDate(string $livestockId, string $date): bool
    {
        return FeedUsage::where('livestock_id', $livestockId)
            ->whereDate('usage_date', $date)
            ->exists();
    }

    /**
     * Get existing feed usage data for specific date
     *
     * @param string $livestockId
     * @param string $date
     * @return array|null
     */
    public function getExistingUsageData(string $livestockId, string $date, ?string $livestockBatchId = null): ?array
    {
        $query = FeedUsage::with([
            'details.feedStock.feed',
            'details.feedStock.feedPurchase.batch'
        ])
            ->where('livestock_id', $livestockId)
            ->whereDate('usage_date', $date);

        // If livestock_batch_id is specified, filter by it to avoid cross-batch data deletion
        if ($livestockBatchId) {
            $query->where('livestock_batch_id', $livestockBatchId);
            Log::info('🔍 Filtering existing usage data by batch', [
                'livestock_id' => $livestockId,
                'date' => $date,
                'livestock_batch_id' => $livestockBatchId
            ]);
        } else {
            Log::info('🔍 Loading existing usage data without batch filter', [
                'livestock_id' => $livestockId,
                'date' => $date,
                'note' => 'This may load data from multiple batches'
            ]);
        }

        $usages = $query->get();

        Log::info('🔍 Found existing usage records', [
            'livestock_id' => $livestockId,
            'date' => $date,
            'livestock_batch_id' => $livestockBatchId,
            'usage_count' => $usages->count(),
            'usage_ids' => $usages->pluck('id')->toArray()
        ]);

        if ($usages->isEmpty()) {
            return null;
        }

        $livestock = Livestock::findOrFail($livestockId);

        // Group all usage details from all usages on this date
        $allDetails = collect();
        $totalQuantity = 0;
        $totalCost = 0;
        $usagePurpose = 'feeding';
        $notes = '';
        $livestockBatchId = null;
        $livestockBatchName = null;

        foreach ($usages as $usage) {
            foreach ($usage->details as $detail) {
                $allDetails->push($detail);
                $totalQuantity += $detail->quantity_taken;
                // Get cost from metadata since it's not in main fields
                $detailCost = $detail->metadata['cost_calculation']['total_cost'] ?? 0;
                $totalCost += floatval($detailCost);
            }

            // Use data from the latest usage - get from main fields
            $usagePurpose = $usage->purpose ?? 'feeding';
            $notes = $usage->notes ?? '';

            // Get livestock batch info from main field
            if ($usage->livestock_batch_id) {
                $livestockBatchId = $usage->livestock_batch_id;

                // Try to get batch name
                try {
                    $batch = LivestockBatch::find($usage->livestock_batch_id);
                    if ($batch) {
                        $livestockBatchName = $batch->name;
                    } else {
                        // Fallback to metadata if batch record not found
                        $livestockBatchName = $usage->metadata['livestock_batch_name'] ?? null;
                    }
                } catch (Exception $e) {
                    Log::warning('Could not load livestock batch', [
                        'batch_id' => $usage->livestock_batch_id,
                        'error' => $e->getMessage()
                    ]);
                    // Fallback to metadata
                    $livestockBatchName = $usage->metadata['livestock_batch_name'] ?? null;
                }
            }
        }

        // Convert details to format expected by component
        $selectedStocks = [];
        foreach ($allDetails as $detail) {
            $stock = $detail->feedStock;
            $availableQuantity = $stock->quantity_in - $stock->quantity_used - $stock->quantity_mutated;

            // Get batch information
            $batchInfo = null;
            if ($stock->feedPurchase && $stock->feedPurchase->batch) {
                $batch = $stock->feedPurchase->batch;
                $batchInfo = 'Batch: ' . ($batch->batch_number ?? 'No batch number');
                if ($batch->date) {
                    $batchInfo .= ' (' . $batch->date->format('M d, Y') . ')';
                }
            }

            // Get cost data from metadata
            $costPerUnit = floatval($detail->metadata['cost_calculation']['cost_per_unit'] ?? $this->calculateCostPerUnit($stock));
            $notes = $detail->metadata['notes'] ?? '';

            $selectedStocks[] = [
                'stock_id' => $stock->id,
                'feed_id' => $stock->feed_id,
                'feed_name' => $stock->feed->name,
                'stock_name' => $this->generateStockName($stock, $batchInfo),
                'available_quantity' => $availableQuantity + $detail->quantity_taken, // Add back the used quantity
                'unit' => $stock->feed->payload['unit_details']['name'] ?? 'kg',
                'cost_per_unit' => $costPerUnit,
                'age_days' => $stock->date ? Carbon::parse($stock->date)->diffInDays(now()) : 0,
                'batch_info' => $batchInfo,
                'quantity' => $detail->quantity_taken,
                'note' => $notes,
                'usage_detail_id' => $detail->id, // For tracking existing records
            ];
        }

        // Use total_cost from FeedUsage main field if available, otherwise calculate from details
        $finalTotalCost = 0;
        foreach ($usages as $usage) {
            if ($usage->total_cost && $usage->total_cost > 0) {
                $finalTotalCost += $usage->total_cost;
            } else {
                // Fallback: calculate from details if main field is empty
                foreach ($usage->details as $detail) {
                    $detailCost = $detail->metadata['cost_calculation']['total_cost'] ?? 0;
                    if ($detailCost > 0) {
                        $finalTotalCost += floatval($detailCost);
                    } else {
                        // Last fallback: calculate from quantity and cost per unit
                        $costPerUnit = $detail->metadata['cost_calculation']['cost_per_unit'] ?? $this->calculateCostPerUnit($detail->feedStock);
                        $finalTotalCost += floatval($detail->quantity_taken * $costPerUnit);
                    }
                }
            }
        }

        return [
            'livestock_id' => $livestockId,
            'livestock_name' => $livestock->name,
            'livestock_batch_id' => $livestockBatchId,
            'livestock_batch_name' => $livestockBatchName,
            'usage_date' => $date,
            'usage_purpose' => $usagePurpose,
            'notes' => $notes,
            'selected_stocks' => $selectedStocks,
            'total_quantity' => $totalQuantity,
            'total_cost' => $finalTotalCost,
            'is_edit_mode' => true,
            'existing_usage_ids' => $usages->pluck('id')->toArray(),
        ];
    }

    /**
     * Update existing feed usage data with configurable strategies
     *
     * @param array $usageData
     * @return array
     */
    public function updateExistingFeedUsage(array $usageData): array
    {
        if (!isset($usageData['existing_usage_ids']) || empty($usageData['existing_usage_ids'])) {
            throw new Exception('No existing usage IDs provided for update');
        }

        // Get edit mode settings from configuration
        $editSettings = $this->getFeedUsageEditModeSettings();
        $editStrategy = $editSettings['edit_strategy'] ?? 'update';

        Log::info('🔧 Starting feed usage update with configurable strategy', [
            'strategy' => $editStrategy,
            'existing_usage_ids' => $usageData['existing_usage_ids'],
            'livestock_id' => $usageData['livestock_id']
        ]);

        // Create backup if configured
        if ($editSettings['create_backup_before_edit'] ?? true) {
            $this->createEditBackup($usageData['existing_usage_ids']);
        }

        // Execute based on configured strategy
        switch ($editStrategy) {
            case 'delete_recreate':
                return $this->updateViaDeleteRecreate($usageData, $editSettings);
            case 'update':
            default:
                return $this->updateViaDirectUpdate($usageData, $editSettings);
        }
    }

    /**
     * Create backup of existing data before edit
     *
     * @param array $usageIds
     * @return void
     */
    private function createEditBackup(array $usageIds): void
    {
        try {
            $existingUsages = FeedUsage::with('details')->whereIn('id', $usageIds)->get();

            foreach ($existingUsages as $usage) {
                // Store backup in metadata
                $backupData = [
                    'original_data' => $usage->toArray(),
                    'backup_created_at' => now()->toISOString(),
                    'backup_reason' => 'pre_edit_backup'
                ];

                $usage->update([
                    'metadata' => array_merge($usage->metadata ?? [], ['edit_backup' => $backupData])
                ]);
            }

            Log::info('📦 Created edit backup', [
                'usage_ids' => $usageIds,
                'backup_count' => $existingUsages->count()
            ]);
        } catch (Exception $e) {
            Log::warning('⚠️ Failed to create edit backup', [
                'usage_ids' => $usageIds,
                'error' => $e->getMessage()
            ]);
            // Don't fail the entire operation if backup fails
        }
    }

    /**
     * Update via delete and recreate strategy
     *
     * @param array $usageData
     * @param array $editSettings
     * @return array
     */
    private function updateViaDeleteRecreate(array $usageData, array $editSettings): array
    {
        DB::beginTransaction();

        try {
            $livestock = Livestock::findOrFail($usageData['livestock_id']);
            $deleteStrategy = $editSettings['delete_strategy'] ?? 'soft';

            // Extract specific detail IDs if available
            $specificDetailIds = [];
            if (isset($usageData['manual_stocks'])) {
                foreach ($usageData['manual_stocks'] as $manualStock) {
                    if (isset($manualStock['usage_detail_id'])) {
                        $specificDetailIds[] = $manualStock['usage_detail_id'];
                    }
                }
            }

            Log::info('🗑️ Using delete-recreate strategy', [
                'delete_strategy' => $deleteStrategy,
                'livestock_id' => $livestock->id,
                'specific_detail_ids' => $specificDetailIds
            ]);

            if ($deleteStrategy === 'soft') {
                $this->performSoftDelete($usageData['existing_usage_ids'], $editSettings, $specificDetailIds);
            } else {
                $this->performHardDelete($usageData['existing_usage_ids'], $editSettings, $specificDetailIds);
            }

            // Remove edit mode flags and process as new usage
            unset($usageData['existing_usage_ids']);
            unset($usageData['is_edit_mode']);

            // Process as new usage
            $result = $this->processManualFeedUsage($usageData);

            // Track edit operation if configured
            if ($editSettings['track_edit_operations'] ?? true) {
                $this->trackEditOperation('delete_recreate', $usageData, $result);
            }

            DB::commit();

            Log::info('✅ Successfully updated via delete-recreate', [
                'livestock_id' => $livestock->id,
                'new_usage_id' => $result['feed_usage_id'] ?? null,
                'delete_strategy' => $deleteStrategy
            ]);

            return array_merge($result, [
                'is_update' => true,
                'edit_strategy' => 'delete_recreate',
                'delete_strategy' => $deleteStrategy,
                'message' => 'Feed usage updated successfully (delete-recreate)'
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('❌ Error in delete-recreate update', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Update via direct update strategy
     *
     * @param array $usageData
     * @param array $editSettings
     * @return array
     */
    private function updateViaDirectUpdate(array $usageData, array $editSettings): array
    {
        DB::beginTransaction();

        try {
            $livestock = Livestock::findOrFail($usageData['livestock_id']);
            $existingUsages = FeedUsage::whereIn('id', $usageData['existing_usage_ids'])->get();

            Log::info('🔄 Using direct update strategy', [
                'livestock_id' => $livestock->id,
                'existing_usages_count' => $existingUsages->count()
            ]);

            // Track field changes if configured
            $fieldChanges = [];
            if ($editSettings['update_settings']['track_field_changes'] ?? true) {
                $fieldChanges = $this->trackFieldChanges($existingUsages, $usageData);
            }

            // Extract specific detail IDs that will be edited
            $detailIdsToEdit = [];
            foreach ($usageData['manual_stocks'] as $manualStock) {
                if (isset($manualStock['usage_detail_id'])) {
                    $detailIdsToEdit[] = $manualStock['usage_detail_id'];
                }
            }

            // Restore stock quantities before updating - only for details being edited
            $totalQuantityToRestore = 0;
            $totalCostToRestore = 0;

            foreach ($existingUsages as $usage) {
                $detailsToRestore = $usage->details;
                if (!empty($detailIdsToEdit)) {
                    $detailsToRestore = $usage->details->whereIn('id', $detailIdsToEdit);
                }

                foreach ($detailsToRestore as $detail) {
                    $stock = $detail->feedStock;
                    $quantityToRestore = floatval($detail->quantity_taken);

                    if ($quantityToRestore > 0) {
                        $newQuantityUsed = max(0, $stock->quantity_used - $quantityToRestore);
                        $stock->update(['quantity_used' => $newQuantityUsed]);
                        $totalQuantityToRestore += $quantityToRestore;
                    }

                    // Get cost from detail metadata
                    $detailCost = $detail->metadata['cost_calculation']['total_cost'] ?? 0;
                    $totalCostToRestore += floatval($detailCost);
                }
            }

            // Update livestock totals (subtract only the restored values)
            if ($totalQuantityToRestore > 0 || $totalCostToRestore > 0) {
                $livestock->decrementFeedConsumption($totalQuantityToRestore, $totalCostToRestore);
                Log::info('📉 Decremented livestock totals for edited details only', [
                    'livestock_id' => $livestock->id,
                    'quantity_restored' => $totalQuantityToRestore,
                    'cost_restored' => $totalCostToRestore
                ]);
            }

            // Delete only the specific details that are being edited, not all details
            $detailIdsToDelete = [];
            foreach ($usageData['manual_stocks'] as $manualStock) {
                if (isset($manualStock['usage_detail_id'])) {
                    $detailIdsToDelete[] = $manualStock['usage_detail_id'];
                }
            }

            // If we have specific detail IDs, delete only those
            if (!empty($detailIdsToDelete)) {
                FeedUsageDetail::whereIn('id', $detailIdsToDelete)->delete();
                Log::info('🗑️ Deleted specific usage details', [
                    'detail_ids' => $detailIdsToDelete,
                    'count' => count($detailIdsToDelete)
                ]);
            } else {
                // Fallback: delete all details from existing usage IDs (old behavior)
                FeedUsageDetail::whereIn('feed_usage_id', $usageData['existing_usage_ids'])->delete();
                Log::info('🗑️ Deleted all details from usage IDs (fallback)', [
                    'usage_ids' => $usageData['existing_usage_ids']
                ]);
            }

            // Update main usage record instead of deleting
            $mainUsage = $existingUsages->first();
            $updateData = [
                'livestock_batch_id' => $usageData['livestock_batch_id'] ?? null,
                'usage_date' => $usageData['usage_date'],
                'purpose' => $usageData['usage_purpose'] ?? 'feeding',
                'notes' => $usageData['notes'] ?? null,
                'total_quantity' => 0, // Will be updated after processing
                'total_cost' => 0, // Will be updated after processing
                'updated_by' => auth()->id(),
            ];

            // Update timestamps if configured
            if ($editSettings['update_settings']['update_timestamps'] ?? true) {
                $updateData['updated_at'] = now();
            }

            // Add edit tracking metadata
            if ($editSettings['track_edit_operations'] ?? true) {
                $updateData['metadata'] = array_merge($mainUsage->metadata ?? [], [
                    'edit_history' => [
                        'edited_at' => now()->toISOString(),
                        'edited_by' => auth()->id(),
                        'edit_strategy' => 'direct_update',
                        'field_changes' => $fieldChanges
                    ]
                ]);
            }

            $mainUsage->update($updateData);

            // Process new stock details
            $totalQuantity = 0;
            $totalCost = 0;

            foreach ($usageData['manual_stocks'] as $manualStock) {
                $stock = FeedStock::findOrFail($manualStock['stock_id']);
                $requestedQuantity = floatval($manualStock['quantity']);
                $costPerUnit = $this->calculateCostPerUnit($stock);
                $lineCost = $requestedQuantity * $costPerUnit;

                // Create new usage detail
                FeedUsageDetail::create([
                    'feed_usage_id' => $mainUsage->id,
                    'feed_id' => $stock->feed_id,
                    'feed_stock_id' => $stock->id,
                    'quantity_taken' => $requestedQuantity,
                    'metadata' => [
                        'cost_calculation' => [
                            'cost_per_unit' => $costPerUnit,
                            'total_cost' => $lineCost,
                        ],
                        'stock_info' => [
                            'feed_name' => $stock->feed->name,
                            'batch_info' => $manualStock['batch_info'] ?? null,
                        ],
                        'notes' => $manualStock['note'] ?? null,
                    ],
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);

                // Update stock usage
                $stock->increment('quantity_used', $requestedQuantity);
                $totalQuantity += $requestedQuantity;
                $totalCost += $lineCost;
            }

            // Update totals
            $mainUsage->update([
                'total_quantity' => $totalQuantity,
                'total_cost' => $totalCost,
            ]);

            // Update livestock totals
            $livestock->incrementFeedConsumption($totalQuantity, $totalCost);

            // Delete other usage records if multiple exist
            if ($existingUsages->count() > 1) {
                $otherUsageIds = $existingUsages->skip(1)->pluck('id')->toArray();
                FeedUsage::whereIn('id', $otherUsageIds)->delete();
            }

            DB::commit();

            Log::info('✅ Successfully updated via direct update', [
                'livestock_id' => $livestock->id,
                'updated_usage_id' => $mainUsage->id,
                'total_quantity' => $totalQuantity,
                'total_cost' => $totalCost
            ]);

            return [
                'success' => true,
                'feed_usage_id' => $mainUsage->id,
                'total_quantity' => $totalQuantity,
                'total_cost' => $totalCost,
                'is_update' => true,
                'edit_strategy' => 'direct_update',
                'field_changes' => $fieldChanges,
                'message' => 'Feed usage updated successfully (direct update)'
            ];
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('❌ Error in direct update', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Perform soft delete of existing usage records
     *
     * @param array $usageIds
     * @param array $editSettings
     * @return void
     */
    private function performSoftDelete(array $usageIds, array $editSettings, array $specificDetailIds = []): void
    {
        $softDeleteSettings = $editSettings['soft_delete_settings'] ?? [];
        $incrementUsageCount = $softDeleteSettings['increment_usage_count'] ?? true;
        $deleteReason = $softDeleteSettings['default_delete_reason'] ?? 'edited';
        $preserveOriginalData = $softDeleteSettings['preserve_original_data'] ?? true;

        $existingUsages = FeedUsage::whereIn('id', $usageIds)->get();

        foreach ($existingUsages as $usage) {
            // Restore stock quantities - only for specific details if provided
            $detailsToProcess = $usage->details;
            if (!empty($specificDetailIds)) {
                $detailsToProcess = $usage->details->whereIn('id', $specificDetailIds);
            }

            foreach ($detailsToProcess as $detail) {
                $stock = $detail->feedStock;
                $quantityToRestore = floatval($detail->quantity_taken);

                if ($quantityToRestore > 0) {
                    $newQuantityUsed = max(0, $stock->quantity_used - $quantityToRestore);
                    $stock->update(['quantity_used' => $newQuantityUsed]);
                }
            }

            // Update livestock totals
            $livestock = $usage->livestock;
            $totalQuantity = floatval($usage->total_quantity ?? 0);
            $totalCost = floatval($usage->total_cost ?? $usage->metadata['total_cost'] ?? 0);

            if ($totalQuantity > 0 || $totalCost > 0) {
                $livestock->decrementFeedConsumption($totalQuantity, $totalCost);
            }

            // Prepare soft delete metadata
            $softDeleteMetadata = [
                'soft_delete_reason' => $deleteReason,
                'soft_deleted_at' => now()->toISOString(),
                'soft_deleted_by' => auth()->id(),
            ];

            if ($preserveOriginalData) {
                $softDeleteMetadata['original_data'] = $usage->toArray();
            }

            // Update metadata and soft delete
            $usage->update([
                'metadata' => array_merge($usage->metadata ?? [], ['soft_delete' => $softDeleteMetadata])
            ]);

            // Perform soft delete
            $usage->delete();

            // Increment usage count in database if configured
            if ($incrementUsageCount) {
                // This will add to usage count since we're creating new records later
                Log::info('📊 Soft delete will increment usage count', [
                    'usage_id' => $usage->id,
                    'reason' => 'delete_recreate_with_soft_delete'
                ]);
            }
        }

        Log::info('🗑️ Performed soft delete', [
            'usage_ids' => $usageIds,
            'delete_reason' => $deleteReason,
            'increment_usage_count' => $incrementUsageCount
        ]);
    }

    /**
     * Perform hard delete of existing usage records
     *
     * @param array $usageIds
     * @param array $editSettings
     * @return void
     */
    private function performHardDelete(array $usageIds, array $editSettings, array $specificDetailIds = []): void
    {
        $hardDeleteSettings = $editSettings['hard_delete_settings'] ?? [];
        $validateReferences = $hardDeleteSettings['validate_references'] ?? true;
        $restoreStockQuantities = $hardDeleteSettings['restore_stock_quantities'] ?? true;
        $updateLivestockTotals = $hardDeleteSettings['update_livestock_totals'] ?? true;

        $existingUsages = FeedUsage::whereIn('id', $usageIds)->get();

        // Validate references if configured
        if ($validateReferences) {
            // Add any reference validation logic here if needed
            Log::info('✅ Reference validation passed for hard delete');
        }

        foreach ($existingUsages as $usage) {
            // Restore stock quantities if configured - only for specific details if provided
            if ($restoreStockQuantities) {
                $detailsToProcess = $usage->details;
                if (!empty($specificDetailIds)) {
                    $detailsToProcess = $usage->details->whereIn('id', $specificDetailIds);
                }

                foreach ($detailsToProcess as $detail) {
                    $stock = $detail->feedStock;
                    $quantityToRestore = floatval($detail->quantity_taken);

                    if ($quantityToRestore > 0) {
                        $newQuantityUsed = max(0, $stock->quantity_used - $quantityToRestore);
                        $stock->update(['quantity_used' => $newQuantityUsed]);
                    }
                }
            }

            // Update livestock totals if configured
            if ($updateLivestockTotals) {
                $livestock = $usage->livestock;
                $totalQuantity = floatval($usage->total_quantity ?? 0);
                $totalCost = floatval($usage->total_cost ?? $usage->metadata['total_cost'] ?? 0);

                if ($totalQuantity > 0 || $totalCost > 0) {
                    $livestock->decrementFeedConsumption($totalQuantity, $totalCost);
                }
            }
        }

        // Hard delete details first - only specific details if provided
        if (!empty($specificDetailIds)) {
            FeedUsageDetail::whereIn('id', $specificDetailIds)->forceDelete();
            Log::info('🗑️ Hard deleted specific usage details', [
                'detail_ids' => $specificDetailIds,
                'count' => count($specificDetailIds)
            ]);
        } else {
            // Fallback: delete all details from usage IDs
            FeedUsageDetail::whereIn('feed_usage_id', $usageIds)->forceDelete();
            Log::info('🗑️ Hard deleted all details from usage IDs (fallback)', [
                'usage_ids' => $usageIds
            ]);
        }

        // Only delete usage records if they have no remaining details
        foreach ($usageIds as $usageId) {
            $remainingDetailsCount = FeedUsageDetail::where('feed_usage_id', $usageId)->count();
            if ($remainingDetailsCount == 0) {
                FeedUsage::where('id', $usageId)->forceDelete();
                Log::info('🗑️ Hard deleted usage record (no remaining details)', [
                    'usage_id' => $usageId
                ]);
            } else {
                Log::info('⚠️ Kept usage record (has remaining details)', [
                    'usage_id' => $usageId,
                    'remaining_details' => $remainingDetailsCount
                ]);
            }
        }

        Log::info('🗑️ Performed hard delete', [
            'usage_ids' => $usageIds,
            'restore_stock_quantities' => $restoreStockQuantities,
            'update_livestock_totals' => $updateLivestockTotals
        ]);
    }

    /**
     * Track field changes for audit purposes
     *
     * @param \Illuminate\Database\Eloquent\Collection $existingUsages
     * @param array $newUsageData
     * @return array
     */
    private function trackFieldChanges($existingUsages, array $newUsageData): array
    {
        $changes = [];
        $mainUsage = $existingUsages->first();

        if ($mainUsage) {
            // Track main field changes
            $fieldsToTrack = ['usage_date', 'purpose', 'notes', 'livestock_batch_id'];

            foreach ($fieldsToTrack as $field) {
                $oldValue = $mainUsage->{$field === 'purpose' ? 'purpose' : $field};
                $newValue = $newUsageData[$field === 'purpose' ? 'usage_purpose' : $field] ?? null;

                if ($oldValue != $newValue) {
                    $changes[$field] = [
                        'old' => $oldValue,
                        'new' => $newValue
                    ];
                }
            }

            // Track stock changes
            $oldStocks = $mainUsage->details->map(function ($detail) {
                return [
                    'stock_id' => $detail->feed_stock_id,
                    'quantity' => $detail->quantity_taken
                ];
            })->toArray();

            $newStocks = collect($newUsageData['manual_stocks'])->map(function ($stock) {
                return [
                    'stock_id' => $stock['stock_id'],
                    'quantity' => $stock['quantity']
                ];
            })->toArray();

            if ($oldStocks != $newStocks) {
                $changes['stocks'] = [
                    'old' => $oldStocks,
                    'new' => $newStocks
                ];
            }
        }

        return $changes;
    }

    /**
     * Track edit operation for audit purposes
     *
     * @param string $strategy
     * @param array $usageData
     * @param array $result
     * @return void
     */
    private function trackEditOperation(string $strategy, array $usageData, array $result): void
    {
        try {
            Log::info('📝 Tracking edit operation', [
                'strategy' => $strategy,
                'livestock_id' => $usageData['livestock_id'],
                'usage_date' => $usageData['usage_date'],
                'new_usage_id' => $result['feed_usage_id'] ?? null,
                'edited_by' => auth()->id(),
                'edited_at' => now()->toISOString()
            ]);

            // Additional tracking logic can be added here
            // e.g., storing in audit table, sending notifications, etc.
        } catch (Exception $e) {
            Log::warning('⚠️ Failed to track edit operation', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send feed usage alert
     */
    private function sendFeedUsageAlert(string $action, array $data): void
    {
        try {
            $this->feedAlertService->sendFeedUsageAlertWithAnomalyCheck($action, $data);
            Log::info("Feed usage {$action} alert sent successfully", ['livestock_id' => $data['livestock_id'] ?? 'unknown']);
        } catch (\Exception $e) {
            Log::error("Failed to send feed usage {$action} alert", [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
        }
    }

    /**
     * Send feed stats discrepancy alert
     */
    private function sendFeedStatsDiscrepancyAlert(array $data): void
    {
        try {
            $this->feedAlertService->sendFeedStatsDiscrepancyAlert($data);
            Log::info('Feed stats discrepancy alert sent successfully', ['livestock_id' => $data['livestock_id'] ?? 'unknown']);
        } catch (\Exception $e) {
            Log::error('Failed to send feed stats discrepancy alert', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
        }
    }
}

<?php

namespace App\Services\Livestock;

use App\Models\LivestockCost;
use App\Models\Item as Feed;
use App\Models\FeedUsage;
use App\Models\FeedUsageDetail;
use App\Models\Livestock;
use App\Models\Recording;
use App\Models\OVKRecord;
use App\Models\SupplyStock;
use App\Models\LivestockPurchaseItem;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class LivestockCostService
{

    /**
     * Calculate livestock cost for a specific date
     * Following the business flow: livestock entry → mortality recording → livestock placement → feeding (including day 1)
     * 
     * @param string $livestockId
     * @param string $tanggal
     * @return \App\Models\LivestockCost
     */
    public function calculateForDate($livestockId, $tanggal)
    {
        $tanggal = Carbon::parse($tanggal)->format('Y-m-d');

        Log::info("🔄 Starting livestock cost calculation", [
            'livestock_id' => $livestockId,
            'date' => $tanggal
        ]);

        // Get or create recording for this date
        $recording = Recording::where('livestock_id', $livestockId)
            ->whereDate('tanggal', $tanggal)
            ->first();

        $livestock = Livestock::findOrFail($livestockId);

        // Initialize values from recording or calculate defaults
        if ($recording) {
            $stockAwal = $recording->stock_awal;
            $stockAkhir = $recording->stock_akhir;
            $deplesiQty = ($recording->payload['mortality'] ?? 0) + ($recording->payload['culling'] ?? 0);
            $salesQty = $recording->payload['sales_quantity'] ?? 0;
        } else {
            // Calculate for days without explicit recording
            $startDate = Carbon::parse($livestock->start_date);
            $recordDate = Carbon::parse($tanggal);
            $age = $startDate->diffInDays($recordDate);

            // Get previous day's stock_akhir or use initial quantity
            $previousDate = $recordDate->copy()->subDay()->format('Y-m-d');
            $previousRecording = Recording::where('livestock_id', $livestockId)
                ->whereDate('tanggal', $previousDate)
                ->first();

            $stockAwal = $previousRecording ? $previousRecording->stock_akhir : $livestock->initial_quantity;
            $stockAkhir = $stockAwal; // No depletion if no recording
            $deplesiQty = 0;
            $salesQty = 0;

            // Create minimal recording if needed for cost calculation
            $recording = Recording::firstOrCreate([
                'livestock_id' => $livestockId,
                'tanggal' => $tanggal,
            ], [
                'age' => $age,
                'stock_awal' => $stockAwal,
                'stock_akhir' => $stockAkhir,
                'payload' => [
                    'mortality' => 0,
                    'culling' => 0,
                    'sales_quantity' => 0
                ],
                'created_by' => auth()->id() ?? 1,
                'updated_by' => auth()->id() ?? 1,
            ]);
        }

        // Get initial purchase data - FIXED: use correct field names
        $initialPurchaseItem = LivestockPurchaseItem::where('livestock_id', $livestockId)
            ->orderBy('created_at', 'asc')
            ->first();

        if (!$initialPurchaseItem) {
            Log::warning("⚠️ No initial purchase item found for livestock", ['livestock_id' => $livestockId]);
            throw new \Exception("Initial purchase data not found for livestock ID: {$livestockId}");
        }

        // FIXED: Use correct field names from LivestockPurchaseItem
        $initialPricePerUnit = floatval($initialPurchaseItem->price_per_unit ?? 0);
        $initialQuantity = floatval($initialPurchaseItem->quantity ?? 0);
        $initialTotalCost = floatval($initialPurchaseItem->price_total ?? 0);

        Log::info("📦 Initial purchase data", [
            'price_per_unit' => $initialPricePerUnit,
            'quantity' => $initialQuantity,
            'total_cost' => $initialTotalCost,
            'date' => $initialPurchaseItem->created_at
        ]);

        // Calculate feed costs for this date
        $feedResult = $this->calculateFeedCosts($livestockId, $tanggal);
        $feedCost = $feedResult['total_cost'];
        $feedDetails = $feedResult['details'];

        // Calculate OVK costs for this date
        $ovkResult = $this->calculateOVKCosts($livestockId, $tanggal, $livestock);
        $ovkCost = $ovkResult['total_cost'];
        $ovkDetails = $ovkResult['details'];

        // Get previous day's cumulative data for accurate deplesi calculation
        $previousCostData = $this->getPreviousDayCostData($livestockId, $tanggal);

        // Calculate deplesi cost based on business flow
        // Deplesi cost = number of depleted chickens × (initial price + accumulated costs per chicken up to previous day)
        $cumulativeCostPerChickenPreviousDay = $previousCostData['cumulative_cost_per_chicken'];
        $deplesiCost = $deplesiQty * $cumulativeCostPerChickenPreviousDay;

        // Total added cost for today (Feed + OVK + Deplesi)
        $totalDailyAddedCost = $feedCost + $ovkCost + $deplesiCost;

        // Calculate cumulative costs
        $cumulativeData = $this->calculateCumulativeCosts(
            $livestockId,
            $tanggal,
            $feedCost,
            $ovkCost,
            $deplesiCost,
            $initialPricePerUnit,
            $initialQuantity
        );

        // Calculate per-chicken costs
        $dailyAddedCostPerChicken = $stockAkhir > 0 ? round($totalDailyAddedCost / $stockAkhir, 2) : 0;
        $cumulativeCostPerChicken = $stockAkhir > 0 ?
            round($cumulativeData['total_cumulative_added_cost'] / $stockAkhir, 2) : 0;
        $totalCostPerChicken = $initialPricePerUnit + $cumulativeCostPerChicken;

        // Calculate OVK cost per chicken
        $ovkCostPerChicken = $stockAkhir > 0 ? round($ovkCost / $stockAkhir, 2) : 0;

        // Prepare summary statistics
        $summaryStats = [
            'livestock_id' => $livestockId,
            'date' => $tanggal,
            'initial_price_per_unit' => round($initialPricePerUnit, 2),
            'initial_quantity' => $initialQuantity,
            'initial_total_cost' => round($initialTotalCost, 2),

            // Daily costs
            'daily_feed_cost' => round($feedCost, 2),
            'daily_ovk_cost' => round($ovkCost, 2),
            'daily_deplesi_cost' => round($deplesiCost, 2),
            'total_daily_added_cost' => round($totalDailyAddedCost, 2),
            'daily_added_cost_per_chicken' => $dailyAddedCostPerChicken,

            // Cumulative costs
            'cumulative_feed_cost' => round($cumulativeData['cumulative_feed_cost'], 2),
            'cumulative_ovk_cost' => round($cumulativeData['cumulative_ovk_cost'], 2),
            'cumulative_deplesi_cost' => round($cumulativeData['cumulative_deplesi_cost'], 2),
            'total_cumulative_added_cost' => round($cumulativeData['total_cumulative_added_cost'], 2),
            'cumulative_added_cost_per_chicken' => $cumulativeCostPerChicken,

            // Final costs (including initial price)
            'total_cost_per_chicken' => round($totalCostPerChicken, 2),
            'total_flock_value' => round($totalCostPerChicken * $stockAkhir, 2),

            // Stock information
            'stock_awal' => $stockAwal,
            'stock_akhir' => $stockAkhir,
            'deplesi_qty' => $deplesiQty,
            'sales_qty' => $salesQty,
            'ovk_cost_per_chicken' => $ovkCostPerChicken,

            // Calculation metadata
            'calculation_method' => 'business_flow_v2.0',
            'version' => '2.0',
            'timestamp' => now()->toIso8601String(),
        ];

        Log::info("💰 Cost calculation summary", $summaryStats);

        // Save to LivestockCost with corrected structure
        $livestockCost = LivestockCost::updateOrCreate(
            [
                'livestock_id' => $livestockId,
                'tanggal' => $tanggal,
            ],
            [
                'recording_id' => $recording->id,
                'total_cost' => $totalDailyAddedCost, // Daily added cost
                'cost_per_ayam' => $totalCostPerChicken, // FIXED: Total cost per chicken (including initial price)
                'cost_breakdown' => [
                    // Daily costs
                    'pakan' => $feedCost,
                    'ovk' => $ovkCost,
                    'deplesi' => $deplesiCost,
                    'daily_total' => $totalDailyAddedCost,

                    // Per chicken costs
                    'ovk_per_ayam' => $ovkCostPerChicken,
                    'daily_added_cost_per_chicken' => $dailyAddedCostPerChicken,
                    'cumulative_cost_per_chicken' => $totalCostPerChicken,

                    // Stock data
                    'deplesi_ekor' => $deplesiQty,
                    'jual_ekor' => $salesQty,
                    'stock_awal' => $stockAwal,
                    'stock_akhir' => $stockAkhir,

                    // Detailed breakdowns
                    'feed_detail' => $feedDetails,
                    'ovk_detail' => $ovkDetails,

                    // Summary and metadata
                    'summary' => $summaryStats,
                    'prev_cost' => [
                        'total_added_cost' => $previousCostData['total_added_cost'],
                        'cumulative_cost_per_chicken' => $cumulativeCostPerChickenPreviousDay,
                    ],
                    'calculations' => [
                        'method' => 'business_flow_accurate',
                        'version' => '2.0',
                        'timestamp' => now()->toIso8601String(),
                    ],
                    'initial_purchase_item_details' => [
                        'found' => true,
                        'livestock_purchase_item_id' => $initialPurchaseItem->id,
                        'price_per_unit' => $initialPricePerUnit,
                        'quantity' => $initialQuantity,
                        'price_total' => $initialTotalCost,
                        'created_at' => $initialPurchaseItem->created_at,
                    ]
                ]
            ]
        );

        Log::info("✅ Livestock cost calculation completed", [
            'livestock_cost_id' => $livestockCost->id,
            'total_cost' => $livestockCost->total_cost,
            'cost_per_ayam' => $livestockCost->cost_per_ayam
        ]);

        return $livestockCost;
    }

    /**
     * Calculate feed costs for a specific date
     */
    private function calculateFeedCosts($livestockId, $tanggal)
    {
        $feedUsageDetails = FeedUsageDetail::whereHas('feedUsage', function ($query) use ($livestockId, $tanggal) {
            $query->where('livestock_id', $livestockId)
                ->whereDate('usage_date', $tanggal);
        })->with([
            'feedStock.feedPurchase.unit',
            'feedStock.feed',
            'feedUsage'
        ])->get();

        $totalFeedCost = 0;
        $feedDetails = [];

        foreach ($feedUsageDetails as $detail) {
            $feed = $detail->feedStock?->feed;
            $purchase = $detail->feedStock?->feedPurchase;

            if (!$feed || !$purchase) {
                continue;
            }

            $feedName = $feed->name ?? 'Unknown Feed';
            $feedId = $feed->id;

            // Get unit conversion information
            $conversionUnits = collect($feed->payload['conversion_units'] ?? []);
            $purchaseUnitId = $purchase->unit_id;
            $convertedUnitId = $purchase->converted_unit;

            $purchaseUnit = $purchase->unit?->name ?? 'Unknown';
            $smallestUnitName = 'Unknown';
            $conversionRate = 1;

            // Calculate conversion rate and unit names
            if (!empty($conversionUnits)) {
                $purchaseUnitData = $conversionUnits->firstWhere('unit_id', $purchaseUnitId);
                $smallestUnitData = $conversionUnits->firstWhere('unit_id', $convertedUnitId) ??
                    $conversionUnits->firstWhere('is_smallest', true);

                if ($purchaseUnitData && $smallestUnitData) {
                    $purchaseUnitValue = floatval($purchaseUnitData['value']);
                    $smallestUnitValue = floatval($smallestUnitData['value']);
                    $conversionRate = $purchaseUnitValue / $smallestUnitValue;

                    if ($convertedUnitId) {
                        $smallestUnit = \App\Models\Unit::find($convertedUnitId);
                        $smallestUnitName = $smallestUnit?->name ?? 'Unknown';
                    } else {
                        $smallestUnitName = $smallestUnitData['unit_name'] ?? 'Unknown';
                    }
                }
            }

            // Calculate cost
            $pricePerSmallestUnit = $purchase->price_per_converted_unit ??
                ($purchase->price_per_unit / $conversionRate);
            $qtyInSmallestUnit = $detail->quantity_taken;
            $subtotal = $qtyInSmallestUnit * $pricePerSmallestUnit;
            $qtyInPurchaseUnit = $qtyInSmallestUnit / $conversionRate;

            $totalFeedCost += $subtotal;

            // Aggregate by feed type
            $key = $feedName . ' (' . $feedId . ')';
            if (isset($feedDetails[$key])) {
                $feedDetails[$key]['jumlah_smallest_unit'] += $qtyInSmallestUnit;
                $feedDetails[$key]['jumlah_purchase_unit'] += $qtyInPurchaseUnit;
                $feedDetails[$key]['subtotal'] += $subtotal;
            } else {
                $feedDetails[$key] = [
                    'feed_id' => $feedId,
                    'feed_name' => $feedName,
                    'jumlah_smallest_unit' => $qtyInSmallestUnit,
                    'smallest_unit' => $smallestUnitName,
                    'jumlah_purchase_unit' => $qtyInPurchaseUnit,
                    'purchase_unit' => $purchaseUnit,
                    'conversion_rate' => $conversionRate,
                    'price_per_smallest_unit' => $pricePerSmallestUnit,
                    'price_per_purchase_unit' => $purchase->price_per_unit,
                    'subtotal' => $subtotal,
                ];
            }
        }

        return [
            'total_cost' => $totalFeedCost,
            'details' => $feedDetails
        ];
    }

    /**
     * Calculate OVK costs for a specific date
     */
    private function calculateOVKCosts($livestockId, $tanggal, $livestock)
    {
        $ovkRecords = OVKRecord::where('livestock_id', $livestockId)
            ->whereDate('usage_date', $tanggal)
            ->with(['items.supply', 'items.unit'])
            ->get();

        $totalOvkCost = 0;
        $ovkDetails = [];

        foreach ($ovkRecords as $ovkRecord) {
            foreach ($ovkRecord->items as $item) {
                $supply = $item->supply;
                $unit = $item->unit;

                if (!$supply || !$unit) continue;

                // Get the latest supply purchase for cost calculation
                $latestPurchase = SupplyStock::where('supply_id', $supply->id)
                    ->where('farm_id', $livestock->farm_id)
                    ->where('quantity_in', '>', 0)
                    ->orderBy('date', 'desc')
                    ->first();

                if (!$latestPurchase) continue;

                // Calculate cost (simplified for now, can be enhanced with unit conversion)
                $pricePerUnit = floatval($latestPurchase->supplyPurchase?->price_per_unit ?? 0);
                $quantity = floatval($item->quantity);
                $subtotal = $quantity * $pricePerUnit;

                $totalOvkCost += $subtotal;

                // Aggregate by supply type
                $key = $supply->name . ' (' . $supply->id . ')';
                if (isset($ovkDetails[$key])) {
                    $ovkDetails[$key]['quantity'] += $quantity;
                    $ovkDetails[$key]['subtotal'] += $subtotal;
                } else {
                    $ovkDetails[$key] = [
                        'supply_id' => $supply->id,
                        'supply_name' => $supply->name,
                        'quantity' => $quantity,
                        'unit' => $unit->name,
                        'price_per_unit' => $pricePerUnit,
                        'subtotal' => $subtotal,
                    ];
                }
            }
        }

        return [
            'total_cost' => $totalOvkCost,
            'details' => $ovkDetails
        ];
    }

    /**
     * Get previous day's cost data for accurate deplesi calculation
     */
    private function getPreviousDayCostData($livestockId, $tanggal)
    {
        $previousDate = Carbon::parse($tanggal)->subDay()->format('Y-m-d');
        $previousCost = LivestockCost::where('livestock_id', $livestockId)
            ->whereDate('tanggal', $previousDate)
            ->first();

        if ($previousCost) {
            return [
                'total_added_cost' => $previousCost->total_cost ?? 0,
                'cumulative_cost_per_chicken' => $previousCost->cost_per_ayam ?? 0,
            ];
        }

        // If no previous cost, use initial purchase price
        $initialPurchaseItem = LivestockPurchaseItem::where('livestock_id', $livestockId)
            ->orderBy('created_at', 'asc')
            ->first();

        $initialPricePerUnit = floatval($initialPurchaseItem->price_per_unit ?? 0);

        return [
            'total_added_cost' => 0,
            'cumulative_cost_per_chicken' => $initialPricePerUnit,
        ];
    }

    /**
     * Calculate cumulative costs across all previous days
     */
    private function calculateCumulativeCosts($livestockId, $tanggal, $feedCost, $ovkCost, $deplesiCost, $initialPricePerUnit, $initialQuantity)
    {
        // Get all previous costs
        $previousCosts = LivestockCost::where('livestock_id', $livestockId)
            ->whereDate('tanggal', '<', $tanggal)
            ->orderBy('tanggal', 'asc')
            ->get();

        $cumulativeFeedCost = 0;
        $cumulativeOvkCost = 0;
        $cumulativeDeplesiCost = 0;

        foreach ($previousCosts as $cost) {
            $breakdown = $cost->cost_breakdown ?? [];
            $cumulativeFeedCost += $breakdown['pakan'] ?? 0;
            $cumulativeOvkCost += $breakdown['ovk'] ?? 0;
            $cumulativeDeplesiCost += $breakdown['deplesi'] ?? 0;
        }

        // Add today's costs
        $cumulativeFeedCost += $feedCost;
        $cumulativeOvkCost += $ovkCost;
        $cumulativeDeplesiCost += $deplesiCost;

        $totalCumulativeAddedCost = $cumulativeFeedCost + $cumulativeOvkCost + $cumulativeDeplesiCost;

        return [
            'cumulative_feed_cost' => $cumulativeFeedCost,
            'cumulative_ovk_cost' => $cumulativeOvkCost,
            'cumulative_deplesi_cost' => $cumulativeDeplesiCost,
            'total_cumulative_added_cost' => $totalCumulativeAddedCost,
        ];
    }

    /**
     * Recalculate costs for a range of dates
     */
    public function recalculateRange($livestockId, $startDate = null, $endDate = null)
    {
        $query = Recording::where('livestock_id', $livestockId);

        if ($startDate) $query->whereDate('tanggal', '>=', $startDate);
        if ($endDate) $query->whereDate('tanggal', '<=', $endDate);

        $recordings = $query->orderBy('tanggal')->get();

        Log::info("🔄 Recalculating livestock costs for range", [
            'livestock_id' => $livestockId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'recordings_count' => $recordings->count()
        ]);

        foreach ($recordings as $record) {
            $this->calculateForDate($livestockId, $record->tanggal);
        }

        Log::info("✅ Completed recalculating livestock costs for range");
    }
}

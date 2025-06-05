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

class LivestockCostService
{

    public function calculateForDate($livestockId, $tanggal)
    {
        $tanggal = Carbon::parse($tanggal)->format('Y-m-d');
        $recording = Recording::where('livestock_id', $livestockId)
            ->whereDate('tanggal', $tanggal)
            ->first();

        $livestock = Livestock::findOrFail($livestockId);

        // Initialize default values for when there's no recording
        $stockAwal = 0;
        $stockAkhir = 0;
        $deplesiQty = 0;
        $salesQty = 0;

        // If no recording exists, create a temporary one
        if (!$recording) {
            // Calculate age based on livestock start date
            $startDate = Carbon::parse($livestock->start_date);
            $recordDate = Carbon::parse($tanggal);
            $age = $startDate->diffInDays($recordDate);

            $recording = Recording::create([
                'livestock_id' => $livestockId,
                'tanggal' => $tanggal,
                'age' => $age,
                'stock_awal' => 0,
                'stock_akhir' => 0,
                'payload' => [
                    'mortality' => 0,
                    'culling' => 0,
                    'sales_quantity' => 0
                ],
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);
        } else {
            $stockAwal = $recording->stock_awal;
            $stockAkhir = $recording->stock_akhir;
            $deplesiQty = ($recording->payload['mortality'] ?? 0) + ($recording->payload['culling'] ?? 0);
            $salesQty = $recording->payload['sales_quantity'] ?? 0;
        }

        // Feed cost - enhanced with proper unit conversion
        $feedUsageDetails = FeedUsageDetail::whereHas('feedUsage', function ($query) use ($livestockId, $tanggal) {
            $query->where('livestock_id', $livestockId)
                ->whereDate('usage_date', $tanggal);
        })->with([
            'feedStock.feedPurchase.unit',
            'feedStock.feed',
            'feedUsage'
        ])->get();

        $feedCost = 0;
        $feedDetails = [];

        foreach ($feedUsageDetails as $detail) {
            // Get feed and purchase information
            $feed = $detail->feedStock?->feed;
            $purchase = $detail->feedStock?->feedPurchase;

            if (!$feed || !$purchase) {
                continue; // Skip if feed or purchase data is missing
            }

            $namaPakan = $feed->name ?? 'Unknown Feed';
            $feedId = $feed->id;

            // Get unit conversion information
            $conversionUnits = collect($feed->payload['conversion_units'] ?? []);
            $purchaseUnitId = $purchase->unit_id;
            $convertedUnitId = $purchase->converted_unit;

            $purchaseUnit = $purchase->unit?->name ?? 'Unknown';
            $smallestUnitName = null;
            $conversionRate = 1;

            // Find conversion information
            if (!empty($conversionUnits)) {
                $purchaseUnitData = $conversionUnits->firstWhere('unit_id', $purchaseUnitId);
                $smallestUnitData = $conversionUnits->firstWhere('unit_id', $convertedUnitId) ??
                    $conversionUnits->firstWhere('is_smallest', true);

                if ($purchaseUnitData && $smallestUnitData) {
                    $purchaseUnitValue = floatval($purchaseUnitData['value']);
                    $smallestUnitValue = floatval($smallestUnitData['value']);
                    $conversionRate = $purchaseUnitValue / $smallestUnitValue;

                    // Get the smallest unit name from the unit model if available
                    if ($convertedUnitId) {
                        $smallestUnit = \App\Models\Unit::find($convertedUnitId);
                        $smallestUnitName = $smallestUnit?->name ?? 'Unknown';
                    } else {
                        $smallestUnitName = $smallestUnitData['unit_name'] ?? 'Unknown';
                    }
                }
            }

            // Get the price per smallest unit
            $pricePerSmallestUnit = $purchase->price_per_converted_unit ??
                ($purchase->price_per_unit / $conversionRate);

            // Get quantity in smallest unit (already in smallest unit in FeedUsageDetail)
            $qtyInSmallestUnit = $detail->quantity_taken;

            // Calculate cost using the price per smallest unit
            $subtotal = $qtyInSmallestUnit * $pricePerSmallestUnit;

            // Convert to purchase unit for display
            $qtyInPurchaseUnit = $qtyInSmallestUnit / $conversionRate;

            $feedCost += $subtotal;

            // Add comprehensive information to feed details
            $key = $namaPakan . ' (' . $feedId . ')';
            if (isset($feedDetails[$key])) {
                $feedDetails[$key]['jumlah_smallest_unit'] += $qtyInSmallestUnit;
                $feedDetails[$key]['jumlah_purchase_unit'] += $qtyInPurchaseUnit;
                $feedDetails[$key]['subtotal'] += $subtotal;
            } else {
                $feedDetails[$key] = [
                    'feed_id' => $feedId,
                    'feed_name' => $namaPakan,
                    'jumlah_smallest_unit' => $qtyInSmallestUnit,
                    'smallest_unit' => $smallestUnitName,
                    'jumlah_purchase_unit' => $qtyInPurchaseUnit,
                    'purchase_unit' => $purchaseUnit,
                    'conversion_rate' => $conversionRate,
                    'price_per_smallest_unit' => $pricePerSmallestUnit,
                    'price_per_purchase_unit' => $purchase->price_per_unit,
                    'subtotal' => $subtotal,
                    'usage_details' => [
                        'usage_date' => $detail->feedUsage->usage_date,
                        'usage_id' => $detail->feedUsage->id,
                        'stock_id' => $detail->feed_stock_id
                    ]
                ];
            }
        }

        // Calculate OVK costs
        $ovkCost = 0;
        $ovkDetails = [];

        $ovkRecords = OVKRecord::where('livestock_id', $livestockId)
            ->whereDate('usage_date', $tanggal)
            ->with(['items.supply', 'items.unit'])
            ->get();

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

                // Get unit conversion information
                $conversionUnits = collect($supply->payload['conversion_units'] ?? []);
                $purchaseUnitId = $latestPurchase->supplyPurchase?->unit_id;
                $convertedUnitId = $latestPurchase->supplyPurchase?->converted_unit;

                $purchaseUnit = $latestPurchase->supplyPurchase?->unit?->name ?? 'Unknown';
                $smallestUnitName = null;
                $conversionRate = 1;

                // Find conversion information
                if (!empty($conversionUnits)) {
                    $purchaseUnitData = $conversionUnits->firstWhere('unit_id', $purchaseUnitId);
                    $smallestUnitData = $conversionUnits->firstWhere('unit_id', $convertedUnitId) ??
                        $conversionUnits->firstWhere('is_smallest', true);

                    if ($purchaseUnitData && $smallestUnitData) {
                        $purchaseUnitValue = floatval($purchaseUnitData['value']);
                        $smallestUnitValue = floatval($smallestUnitData['value']);
                        $conversionRate = $purchaseUnitValue / $smallestUnitValue;

                        // Get the smallest unit name
                        if ($convertedUnitId) {
                            $smallestUnit = \App\Models\Unit::find($convertedUnitId);
                            $smallestUnitName = $smallestUnit?->name ?? 'Unknown';
                        } else {
                            $smallestUnitName = $smallestUnitData['unit_name'] ?? 'Unknown';
                        }
                    }
                }

                // Get the price per smallest unit
                $pricePerPurchaseUnit = floatval($latestPurchase->supplyPurchase?->price_per_unit ?? 0);
                $pricePerSmallestUnit = $pricePerPurchaseUnit / $conversionRate;

                // Convert quantity to smallest unit
                $qtyInSmallestUnit = floatval($item->quantity);
                if ($unit->id !== $convertedUnitId) {
                    // If the input unit is not the smallest unit, convert it
                    $inputUnitData = $conversionUnits->firstWhere('unit_id', $unit->id);
                    if ($inputUnitData) {
                        $inputUnitValue = floatval($inputUnitData['value']);
                        $qtyInSmallestUnit = ($qtyInSmallestUnit * $inputUnitValue) / $smallestUnitValue;
                    }
                }

                // Calculate cost using the price per smallest unit
                $subtotal = $qtyInSmallestUnit * $pricePerSmallestUnit;

                // Convert back to purchase unit for display
                $qtyInPurchaseUnit = $qtyInSmallestUnit / $conversionRate;

                $ovkCost += $subtotal;

                // Add to OVK details with comprehensive information
                $key = $supply->name . ' (' . $supply->id . ')';
                if (isset($ovkDetails[$key])) {
                    $ovkDetails[$key]['quantity_smallest_unit'] += $qtyInSmallestUnit;
                    $ovkDetails[$key]['quantity_purchase_unit'] += $qtyInPurchaseUnit;
                    $ovkDetails[$key]['subtotal'] += $subtotal;
                } else {
                    $ovkDetails[$key] = [
                        'supply_id' => $supply->id,
                        'supply_name' => $supply->name,
                        'quantity_smallest_unit' => $qtyInSmallestUnit,
                        'smallest_unit' => $smallestUnitName,
                        'quantity_purchase_unit' => $qtyInPurchaseUnit,
                        'purchase_unit' => $purchaseUnit,
                        'conversion_rate' => $conversionRate,
                        'price_per_smallest_unit' => $pricePerSmallestUnit,
                        'price_per_purchase_unit' => $pricePerPurchaseUnit,
                        'subtotal' => $subtotal,
                        'usage_details' => [
                            'usage_date' => $ovkRecord->usage_date,
                            'record_id' => $ovkRecord->id,
                            'original_quantity' => $item->quantity,
                            'original_unit' => $unit->name,
                            'input_unit_id' => $unit->id,
                            'smallest_unit_id' => $convertedUnitId,
                            'conversion_calculation' => [
                                'input_unit_value' => $inputUnitData['value'] ?? 1,
                                'smallest_unit_value' => $smallestUnitValue ?? 1,
                                'conversion_rate' => $conversionRate
                            ]
                        ]
                    ];
                }
            }
        }

        // Get previous costs with breakdown
        $prevCosts = LivestockCost::where('livestock_id', $livestockId)
            ->whereDate('tanggal', '<', $tanggal)
            ->orderBy('tanggal', 'desc')
            ->get();

        $totalPakanKumulatif = 0;
        $totalDeplesiKumulatif = 0;
        $totalOvkKumulatif = 0;
        // $totalStockAwalKumulatif = $stockAwal; // Start with today's stock awal - not needed for this approach

        // Get the initial purchase price per chicken
        $initialPurchaseItem = \App\Models\LivestockPurchaseItem::where('livestock_id', $livestockId)
            ->orderBy('created_at', 'asc')
            ->first();
        $initialChickenPrice = floatval($initialPurchaseItem->harga_per_ekor ?? 0);

        // Initialize deplesiCost before the if/else block
        $deplesiCost = 0;
        $prevCumulativeCostPerAyam = 0; // Initialize previous cumulative cost per ayam

        // Get the most recent cost for deplesi calculation and cumulative sums
        $latestCost = $prevCosts->first();
        if ($latestCost) {
            // For subsequent days, use the previous day's cumulative cost per ayam for deplesi calculation
            // The previous day's cumulative cost per ayam is stored in the 'cost_per_ayam' field in records <= v1.15,
            // and in 'summary.total_cumulative_cost_per_chicken' in records >= v1.18.
            // For v1.16 and v1.17, it was incorrectly stored in cost_per_ayam (which was daily added cost).
            // Let's rely on final_chicken_price from previous day as cumulative cost per ayam.
            $prevCumulativeCostPerAyam = $latestCost->summary['final_chicken_price'] ?? $latestCost->cost_per_ayam; // Fallback to cost_per_ayam if final_chicken_price not in summary (pre v1.16)

            $deplesiCost = $deplesiQty * $prevCumulativeCostPerAyam;

            // Accumulate ADDED costs (Feed, Deplesi, OVK) from previous records
            // Note: Accumulate based on stored values in breakdown to be accurate regardless of past calculation logic versions
            foreach ($prevCosts as $prev) {
                $breakdown = $prev->cost_breakdown;
                $totalPakanKumulatif += $breakdown['pakan'] ?? 0;
                $totalDeplesiKumulatif += $breakdown['deplesi'] ?? 0; // Use stored deplesi cost
                $totalOvkKumulatif += $breakdown['ovk'] ?? 0;
            }
        } else { // This is the first day
            // If this is the first day of recording, prevCostPerAyam for deplesi is the initial purchase price
            $prevCumulativeCostPerAyam = $initialChickenPrice;

            // On the first day, deplesi cost is calculated based on the initial purchase price
            $deplesiCost = $deplesiQty * $initialChickenPrice;
        }

        // Calculate total costs for today (Feed + Deplesi + OVK)
        $totalAddedCostHariIni = $feedCost + $deplesiCost + $ovkCost;

        // Total cumulative ADDED costs until today
        $totalCumulativeAddedCostsUntilToday = $totalPakanKumulatif + $totalDeplesiKumulatif + $totalOvkKumulatif + $totalAddedCostHariIni;

        // Calculate total cumulative Feed and OVK costs until today
        $totalCumulativeFeedCostUntilToday = $totalPakanKumulatif + $feedCost;
        $totalCumulativeOvkCostUntilToday = $totalOvkKumulatif + $ovkCost;

        // Calculate total cumulative cost (Initial Stock * Initial Price) + Total Cumulative Added Costs
        // This represents the total value of the flock including initial purchase cost and added costs.
        $initialStockQty = floatval($initialPurchaseItem->jumlah ?? 0);
        $totalCumulativeCostUntilToday = ($initialStockQty * $initialChickenPrice) + $totalCumulativeAddedCostsUntilToday;

        // Calculate daily added cost per chicken
        $dailyAddedCostPerChicken = $stockAkhir > 0 ? round($totalAddedCostHariIni / $stockAkhir, 2) : 0;

        // Calculate cumulative ADDED cost per chicken (Total Cumulative Added Costs / Stock Akhir) - Excluding initial price
        $cumulativeAddedCostPerChickenExcludingInitial = $stockAkhir > 0 ? round($totalCumulativeAddedCostsUntilToday / $stockAkhir, 2) : 0;

        // Calculate total cumulative cost per chicken (Initial Price + Cumulative Added Cost Per Chicken) - Including initial price
        $totalCumulativeCostPerChickenIncludingInitial = $initialChickenPrice + $cumulativeAddedCostPerChickenExcludingInitial;

        // Calculate OVK cost per chicken (just for today's OVK cost divided by today's stock akhir)
        $ovkCostPerChicken = $stockAkhir > 0 ? round($ovkCost / $stockAkhir, 2) : 0;

        // Final chicken price: This is the total cumulative cost per chicken (including initial price)
        $finalChickenPrice = $totalCumulativeCostPerChickenIncludingInitial;

        // Calculate summary statistics
        $summaryStats = [
            'total_feed_cost' => $feedCost, // Daily Feed Cost
            'total_deplesi_cost' => $deplesiCost, // Daily Deplesi Cost
            'total_ovk_cost' => $ovkCost, // Daily OVK Cost
            'ovk_cost_per_chicken' => $ovkCostPerChicken,
            'total_added_cost_hari_ini' => $totalAddedCostHariIni, // Renamed for clarity
            'daily_added_cost_per_chicken' => $dailyAddedCostPerChicken, // This is daily ADDED cost per chicken
            'stock_awal' => $stockAwal,
            'stock_akhir' => $stockAkhir,
            'deplesi_qty' => $deplesiQty,
            'sales_qty' => $salesQty,
            'initial_chicken_price' => round($initialChickenPrice, 2),
            'final_chicken_price' => round($finalChickenPrice, 2), // Total cumulative cost per chicken (including initial price)
            'total_cumulative_feed_cost' => round($totalCumulativeFeedCostUntilToday, 2),
            'total_cumulative_ovk_cost' => round($totalCumulativeOvkCostUntilToday, 2),
            'cumulative_added_cost_per_chicken_excluding_initial' => round($cumulativeAddedCostPerChickenExcludingInitial, 2), // Cumulative ADDED cost per chicken (excluding initial price)
            'total_cumulative_cost_per_chicken_including_initial' => round($totalCumulativeCostPerChickenIncludingInitial, 2), // Total cumulative cost per chicken (including initial price)
            'total_cumulative_cost_per_chicken' => round($cumulativeAddedCostPerChickenExcludingInitial, 2), // Cumulative ADDED cost per chicken (excluding initial price) - Renamed to match user request
            'version' => '1.24' // Increment version for this change
        ];

        // dd([
        //     'summaryStats' => $summaryStats,
        //     'tanggal' => $tanggal,
        //     'livestockId' => $livestockId
        // ]);

        // Save to LivestockCost
        $livestockCost = LivestockCost::updateOrCreate(
            [
                'livestock_id' => $livestockId,
                'tanggal' => $tanggal,
            ],
            [
                'recording_id' => $recording->id,
                'total_cost' => $totalAddedCostHariIni, // Store total ADDED cost for the day
                'cost_per_ayam' => $dailyAddedCostPerChicken, // Store daily ADDED cost per chicken
                'cost_breakdown' => [
                    'pakan'         => $feedCost, // Daily Feed Cost
                    'deplesi'       => $deplesiCost, // Daily Deplesi Cost
                    'ovk'           => $ovkCost, // Daily OVK Cost
                    'ovk_per_ayam'  => $ovkCostPerChicken,
                    'daily_added_cost_per_chicken' => $dailyAddedCostPerChicken, // Store daily ADDED cost per chicken in breakdown
                    'deplesi_ekor'  => $deplesiQty,
                    'jual_ekor'     => $salesQty,
                    'stock_awal'    => $stockAwal,
                    'stock_akhir'   => $stockAkhir,
                    'feed_detail'   => $feedDetails,
                    'ovk_detail'    => $ovkDetails,
                    'summary'       => $summaryStats, // Store full summary in breakdown for completeness
                    'prev_cost'     => [
                        // Store relevant previous day's cost data
                        'total_added_cost' => $latestCost ? $latestCost->total_cost : 0, // Total added cost of prev day
                        'pakan' => $latestCost ? ($latestCost->cost_breakdown['pakan'] ?? 0) : 0,
                        'deplesi' => $latestCost ? ($latestCost->cost_breakdown['deplesi'] ?? 0) : 0,
                        'ovk' => $latestCost ? ($latestCost->cost_breakdown['ovk'] ?? 0) : 0,
                        // Store the cumulative cost per ayam of the previous day for deplesi calculation consistency
                        // In v1.21 onwards, prev_cumulative_cost_per_ayam should be used from summary
                        'cumulative_cost_per_ayam' => $prevCumulativeCostPerAyam, // Use the value fetched earlier
                    ],
                    'calculations'  => [
                        'method'    => 'cumulative_cost',
                        'version'   => '1.24',
                        'timestamp' => now()->toIso8601String(),
                    ],
                    'initial_purchase_item_details' => [
                        'found' => $initialPurchaseItem !== null,
                        'livestock_purchase_item_id' => $initialPurchaseItem->id ?? null,
                        'harga_per_ekor' => $initialPurchaseItem->harga_per_ekor ?? null,
                        'created_at' => $initialPurchaseItem->created_at ?? null,
                    ]
                ]
            ]
        );

        // dd($livestockCost);

        return $livestockCost;
    }

    public function recalculateRange($livestockId, $startDate = null, $endDate = null)
    {
        $query = Recording::where('livestock_id', $livestockId);

        if ($startDate) $query->whereDate('tanggal', '>=', $startDate);
        if ($endDate) $query->whereDate('tanggal', '<=', $endDate);

        $recordings = $query->orderBy('tanggal')->get();

        foreach ($recordings as $record) {
            $this->calculateForDate($livestockId, $record->tanggal);
        }
    }

    // public function calculateAndSave(array $data): void
    // {
    //     $livestockId = $data['livestock_id'];
    //     $tanggal = Carbon::parse($data['tanggal'])->format('Y-m-d');
    //     $stockAkhir = $data['stock_akhir'];
    //     $mortality = $data['mortality'] ?? 0;
    //     $culling = $data['culling'] ?? 0;
    //     $salesQuantity = $data['sales_quantity'] ?? 0;
    //     $stockData = $data['stock'] ?? [];

    //     $feedCost = 0;
    //     foreach ($stockData as $item) {
    //         $feed = Feed::find($item['item_id']);
    //         $feedCost += $feed ? ($feed->harga * $item['qty']) : 0;
    //     }

    //     // Hitung deplesi cost berdasarkan data kemarin
    //     $yesterday = Carbon::parse($tanggal)->subDay()->format('Y-m-d');
    //     $costYesterday = LivestockCost::where('livestock_id', $livestockId)
    //                         ->whereDate('tanggal', $yesterday)
    //                         ->first();

    //     $costPerAyamSebelumnya = $costYesterday ? $costYesterday->cost_per_ayam : 0;
    //     $deplesiHariIni = $mortality + $culling;
    //     $deplesiCost = $deplesiHariIni * $costPerAyamSebelumnya;

    //     $totalCost = $feedCost + $deplesiCost;

    //     $jumlahAyamAktif = $stockAkhir;
    //     $costPerAyam = $jumlahAyamAktif > 0 ? round($totalCost / $jumlahAyamAktif, 2) : 0;

    //     $recording = Recording::where('livestock_id', $livestockId)
    //                     ->whereDate('tanggal', $tanggal)
    //                     ->first();

    //     // Simpan ke LivestockCost
    //     LivestockCost::updateOrCreate(
    //         [
    //             'livestock_id' => $livestockId,
    //             'tanggal' => $tanggal,
    //         ],
    //         [
    //             'recording_id' => $recording?->id,
    //             'total_cost' => $totalCost,
    //             'cost_per_ayam' => $costPerAyam,
    //             'cost_breakdown' => [
    //                 'pakan' => $feedCost,
    //                 'deplesi' => $deplesiCost,
    //                 'jual_ekor' => $salesQuantity,
    //             ]
    //         ]
    //     );
    // }
}

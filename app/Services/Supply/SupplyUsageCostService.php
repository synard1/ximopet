<?php

namespace App\Services\Supply;

use App\Models\SupplyUsage;
use App\Models\SupplyUsageDetail;
use App\Models\Livestock;
use App\Models\LivestockCost;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class SupplyUsageCostService
{
    /**
     * Calculate supply usage costs for a specific livestock and date
     * 
     * @param string $livestockId
     * @param string $tanggal
     * @return array
     */
    public function calculateForDate($livestockId, $tanggal): array
    {
        $tanggal = Carbon::parse($tanggal)->format('Y-m-d');

        Log::info("🧪 Calculating supply usage costs for date", [
            'livestock_id' => $livestockId,
            'date' => $tanggal
        ]);

        // Get supply usages for this date and livestock
        $supplyUsages = SupplyUsage::where('livestock_id', $livestockId)
            ->whereDate('usage_date', $tanggal)
            ->whereIn('status', ['completed', 'in_process'])
            ->with(['details.supply', 'details.supplyStock.supplyPurchase', 'details.unit'])
            ->get();

        $totalCost = 0;
        $details = [];
        $supplyBreakdown = [];

        foreach ($supplyUsages as $usage) {
            Log::info("📋 Processing supply usage", [
                'usage_id' => $usage->id,
                'status' => $usage->status,
                'details_count' => $usage->details->count()
            ]);

            foreach ($usage->details as $detail) {
                $supply = $detail->supply;
                $supplyStock = $detail->supplyStock;
                $supplyPurchase = $supplyStock?->supplyPurchase;
                $unit = $detail->unit;

                if (!$supply || !$supplyStock || !$supplyPurchase) {
                    Log::warning("⚠️ Missing supply data for detail", [
                        'detail_id' => $detail->id,
                        'supply_exists' => $supply ? 'yes' : 'no',
                        'stock_exists' => $supplyStock ? 'yes' : 'no',
                        'purchase_exists' => $supplyPurchase ? 'yes' : 'no'
                    ]);
                    continue;
                }

                // Calculate cost using price per converted unit
                $pricePerUnit = $supplyPurchase->price_per_converted_unit ??
                    $supplyPurchase->price_per_unit ?? 0;
                $quantity = floatval($detail->quantity_taken);
                $subtotal = $quantity * $pricePerUnit;

                $totalCost += $subtotal;

                // Create detail record
                $detailRecord = [
                    'supply_id' => $supply->id,
                    'supply_name' => $supply->name,
                    'quantity' => $quantity,
                    'unit' => $unit?->name ?? 'Unknown',
                    'price_per_unit' => $pricePerUnit,
                    'subtotal' => $subtotal,
                    'usage_id' => $usage->id,
                    'detail_id' => $detail->id,
                    'batch_number' => $detail->batch_number,
                    'expiry_date' => $detail->expiry_date,
                    'purchase_date' => $supplyStock->date,
                    'supplier' => $supplyPurchase->supplier->name ?? 'Unknown'
                ];

                $details[] = $detailRecord;

                // Aggregate by supply type
                $supplyKey = $supply->name . ' (' . $supply->id . ')';
                if (isset($supplyBreakdown[$supplyKey])) {
                    $supplyBreakdown[$supplyKey]['quantity'] += $quantity;
                    $supplyBreakdown[$supplyKey]['subtotal'] += $subtotal;
                    $supplyBreakdown[$supplyKey]['details'][] = $detailRecord;
                } else {
                    $supplyBreakdown[$supplyKey] = [
                        'supply_id' => $supply->id,
                        'supply_name' => $supply->name,
                        'quantity' => $quantity,
                        'unit' => $unit?->name ?? 'Unknown',
                        'price_per_unit' => $pricePerUnit,
                        'subtotal' => $subtotal,
                        'details' => [$detailRecord]
                    ];
                }

                Log::info("💊 Supply cost calculated", [
                    'supply_name' => $supply->name,
                    'quantity' => $quantity,
                    'price_per_unit' => $pricePerUnit,
                    'subtotal' => $subtotal
                ]);
            }
        }

        $result = [
            'total_cost' => $totalCost,
            'details' => $details,
            'supply_breakdown' => $supplyBreakdown,
            'usage_count' => $supplyUsages->count(),
            'detail_count' => count($details)
        ];

        Log::info("✅ Supply usage cost calculation completed", [
            'total_cost' => $totalCost,
            'usage_count' => $supplyUsages->count(),
            'detail_count' => count($details)
        ]);

        return $result;
    }

    /**
     * Calculate supply usage costs for a date range
     * 
     * @param string $livestockId
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function calculateForRange($livestockId, $startDate, $endDate): array
    {
        $startDate = Carbon::parse($startDate)->format('Y-m-d');
        $endDate = Carbon::parse($endDate)->format('Y-m-d');

        Log::info("🧪 Calculating supply usage costs for range", [
            'livestock_id' => $livestockId,
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);

        // Get supply usages for the date range
        $supplyUsages = SupplyUsage::where('livestock_id', $livestockId)
            ->whereBetween('usage_date', [$startDate, $endDate])
            ->whereIn('status', ['completed', 'in_process'])
            ->with(['details.supply', 'details.supplyStock.supplyPurchase', 'details.unit'])
            ->get();

        $totalCost = 0;
        $dailyBreakdown = [];
        $supplyBreakdown = [];

        foreach ($supplyUsages as $usage) {
            $date = $usage->usage_date->format('Y-m-d');
            $dailyCost = 0;

            foreach ($usage->details as $detail) {
                $supply = $detail->supply;
                $supplyStock = $detail->supplyStock;
                $supplyPurchase = $supplyStock?->supplyPurchase;
                $unit = $detail->unit;

                if (!$supply || !$supplyStock || !$supplyPurchase) {
                    continue;
                }

                $pricePerUnit = $supplyPurchase->price_per_converted_unit ??
                    $supplyPurchase->price_per_unit ?? 0;
                $quantity = floatval($detail->quantity_taken);
                $subtotal = $quantity * $pricePerUnit;

                $totalCost += $subtotal;
                $dailyCost += $subtotal;

                // Aggregate by supply type
                $supplyKey = $supply->name . ' (' . $supply->id . ')';
                if (isset($supplyBreakdown[$supplyKey])) {
                    $supplyBreakdown[$supplyKey]['quantity'] += $quantity;
                    $supplyBreakdown[$supplyKey]['subtotal'] += $subtotal;
                } else {
                    $supplyBreakdown[$supplyKey] = [
                        'supply_id' => $supply->id,
                        'supply_name' => $supply->name,
                        'quantity' => $quantity,
                        'unit' => $unit?->name ?? 'Unknown',
                        'price_per_unit' => $pricePerUnit,
                        'subtotal' => $subtotal
                    ];
                }
            }

            if (!isset($dailyBreakdown[$date])) {
                $dailyBreakdown[$date] = 0;
            }
            $dailyBreakdown[$date] += $dailyCost;
        }

        return [
            'total_cost' => $totalCost,
            'daily_breakdown' => $dailyBreakdown,
            'supply_breakdown' => $supplyBreakdown,
            'usage_count' => $supplyUsages->count()
        ];
    }

    /**
     * Create or update LivestockCost record for supply usage only
     * This method creates a minimal recording first, then calculates costs
     * 
     * @param string $livestockId
     * @param string $tanggal
     * @return \App\Models\LivestockCost
     */
    public function createCostRecord($livestockId, $tanggal): LivestockCost
    {
        $tanggal = Carbon::parse($tanggal)->format('Y-m-d');

        Log::info("💰 Creating LivestockCost record for supply usage", [
            'livestock_id' => $livestockId,
            'date' => $tanggal
        ]);

        // Get livestock data
        $livestock = Livestock::findOrFail($livestockId);

        // Check if recording already exists
        $existingRecording = \App\Models\Recording::where('livestock_id', $livestockId)
            ->whereDate('tanggal', $tanggal)
            ->first();

        if (!$existingRecording) {
            Log::info("📝 No recording exists, creating minimal recording first");
            // Create minimal recording using LivestockCostService
            $livestockCostService = app(\App\Services\Livestock\LivestockCostService::class);
            return $livestockCostService->calculateForDate($livestockId, $tanggal);
        }

        // Calculate supply usage costs
        $costData = $this->calculateForDate($livestockId, $tanggal);

        // Get previous day's cumulative data
        $previousCostData = $this->getPreviousDayCostData($livestockId, $tanggal);

        // Calculate stock data from recording
        $stockAwal = $existingRecording->stock_awal;
        $stockAkhir = $existingRecording->stock_akhir;

        // Calculate per-chicken costs
        $supplyUsageCostPerChicken = $stockAkhir > 0 ?
            round($costData['total_cost'] / $stockAkhir, 2) : 0;

        $cumulativeCostPerChicken = $previousCostData['cumulative_cost_per_chicken'] + $supplyUsageCostPerChicken;

        // Create or update LivestockCost record
        $livestockCost = LivestockCost::updateOrCreate(
            [
                'livestock_id' => $livestockId,
                'tanggal' => $tanggal,
            ],
            [
                'recording_id' => $existingRecording->id,
                'total_cost' => $costData['total_cost'],
                'cost_per_ayam' => $cumulativeCostPerChicken,
                'cost_breakdown' => [
                    'pakan' => 0,
                    'ovk' => 0,
                    'supply_usage' => $costData['total_cost'],
                    'deplesi' => 0,
                    'daily_total' => $costData['total_cost'],
                    'feed_per_ayam' => 0,
                    'ovk_per_ayam' => 0,
                    'supply_usage_per_ayam' => $supplyUsageCostPerChicken,
                    'daily_added_cost_per_chicken' => $supplyUsageCostPerChicken,
                    'cumulative_cost_per_chicken' => $cumulativeCostPerChicken,
                    'deplesi_ekor' => 0,
                    'jual_ekor' => 0,
                    'stock_awal' => $stockAwal,
                    'stock_akhir' => $stockAkhir,
                    'feed_detail' => [],
                    'ovk_detail' => [],
                    'supply_usage_detail' => $costData['supply_breakdown'],
                    'summary' => [
                        'calculation_date' => $tanggal,
                        'livestock_id' => $livestockId,
                        'calculation_type' => 'supply_usage_with_existing_recording',
                        'calculation_method' => 'supply_usage_service',
                        'version' => '1.0',
                        'timestamp' => now()->toIso8601String(),
                    ],
                    'prev_cost' => [
                        'total_added_cost' => $previousCostData['total_added_cost'],
                        'cumulative_cost_per_chicken' => $previousCostData['cumulative_cost_per_chicken'],
                    ],
                    'calculation_note' => 'Calculated by SupplyUsageCostService with existing recording'
                ],
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]
        );

        Log::info("✅ LivestockCost record created/updated for supply usage", [
            'livestock_cost_id' => $livestockCost->id,
            'total_cost' => $costData['total_cost'],
            'cost_per_chicken' => $cumulativeCostPerChicken,
            'supply_usage_count' => $costData['usage_count'],
            'recording_id' => $existingRecording->id
        ]);

        return $livestockCost;
    }

    /**
     * Get previous day's cost data for cumulative calculations
     * 
     * @param string $livestockId
     * @param string $tanggal
     * @return array
     */
    private function getPreviousDayCostData($livestockId, $tanggal): array
    {
        $previousDate = Carbon::parse($tanggal)->subDay()->format('Y-m-d');

        $previousCost = LivestockCost::where('livestock_id', $livestockId)
            ->whereDate('tanggal', $previousDate)
            ->first();

        if (!$previousCost) {
            return [
                'total_added_cost' => 0,
                'cumulative_cost_per_chicken' => 0
            ];
        }

        $breakdown = $previousCost->cost_breakdown ?? [];

        return [
            'total_added_cost' => $breakdown['daily_total'] ?? 0,
            'cumulative_cost_per_chicken' => $breakdown['cumulative_cost_per_chicken'] ?? 0
        ];
    }
}

<?php

namespace App\Services\Livestock;

use App\Models\LivestockCost;
use App\Models\Item as Feed;
use App\Models\FeedUsage;
use App\Models\FeedUsageDetail;
use App\Models\Livestock;
use App\Models\Recording;

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
        if (!$recording) return null;

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

        // Data populasi
        $stockAwal = $recording->stock_awal;
        $stockAkhir = $recording->stock_akhir;
        $deplesiQty = ($recording->payload['mortality'] ?? 0) + ($recording->payload['culling'] ?? 0);
        $salesQty = $recording->payload['sales_quantity'] ?? 0;

        // Get previous costs with breakdown
        $prevCosts = LivestockCost::where('livestock_id', $livestockId)
            ->whereDate('tanggal', '<', $tanggal)
            ->get();

        $totalPakan = 0;
        $totalDeplesi = 0;

        foreach ($prevCosts as $cost) {
            $breakdown = $cost->cost_breakdown; // assuming this is a JSON or array field
            $totalPakan += $breakdown['pakan'] ?? 0;
            $totalDeplesi += $breakdown['deplesi'] ?? 0;
        }

        $totalPrevCost = $totalPakan + $totalDeplesi;

        // Get previous population
        $totalPrevPopulasi = Recording::where('livestock_id', $livestockId)
            ->whereDate('tanggal', '<', $tanggal)
            ->get()
            ->sum(function ($record) {
                $mortality = $record->payload['mortality'] ?? 0;
                $culling   = $record->payload['culling'] ?? 0;
                $sales     = $record->payload['sales_quantity'] ?? 0;
                return $record->stock_awal - $mortality - $culling - $sales;
            });

        // Ambil cost per ayam dari hari sebelumnya (bukan rata-rata seluruh histori)
        $prevCostRecord = LivestockCost::where('livestock_id', $livestockId)
            ->whereDate('tanggal', '<', $tanggal)
            ->orderByDesc('tanggal')
            ->first();

        $prevCostPerAyam = $prevCostRecord ? ($prevCostRecord->cost_per_ayam ?? 0) : 0;

        // Hitung deplesi sesuai rumus
        $deplesiCost = $deplesiQty * ($livestock->harga + $prevCostPerAyam);

        // Total cost = pakan hari ini + deplesi hari ini + histori sebelumnya
        $totalCost = $totalPrevCost + $feedCost + $deplesiCost;

        $activeStock = $stockAkhir;
        $costPerAyam = $activeStock > 0 ? round($totalCost / $activeStock, 2) : 0;

        // Prepare summary statistics
        $summaryStats = [
            'biaya_pakan_per_ekor' => $stockAkhir > 0 ? round($feedCost / $stockAkhir, 2) : 0,
            'total_pakan_digunakan' => array_sum(array_column($feedDetails, 'jumlah_smallest_unit')),
            'total_pakan_dalam_unit_pembelian' => array_sum(array_column($feedDetails, 'jumlah_purchase_unit')),
            'rata_rata_harga_pakan_per_kg' => array_sum(array_column($feedDetails, 'jumlah_smallest_unit')) > 0
                ? round($feedCost / array_sum(array_column($feedDetails, 'jumlah_smallest_unit')), 2)
                : 0,
            'total_deplesi_ekor' => $deplesiQty,
            'total_jual_ekor' => $salesQty,
            'biaya_deplesi_per_ekor' => $deplesiQty > 0 ? round($deplesiCost / $deplesiQty, 2) : 0,
        ];

        // Save to LivestockCost
        $livestockCost = LivestockCost::updateOrCreate(
            [
                'livestock_id' => $livestockId,
                'tanggal' => $tanggal,
            ],
            [
                'recording_id' => $recording->id,
                'total_cost' => $totalCost,
                'cost_per_ayam' => $costPerAyam,
                'cost_breakdown' => [
                    'pakan'         => $feedCost,
                    'deplesi'       => round($deplesiCost, 2),
                    'cost_per_ayam' => $costPerAyam,
                    'deplesi_ekor'  => $deplesiQty,
                    'jual_ekor'     => $salesQty,
                    'stock_awal'    => $stockAwal,
                    'stock_akhir'   => $stockAkhir,
                    'feed_detail'   => $feedDetails,
                    'summary'       => $summaryStats,
                    'prev_cost'     => [
                        'total' => $totalPrevCost,
                        'pakan' => $totalPakan,
                        'deplesi' => $totalDeplesi,
                        'cost_per_ayam' => $prevCostPerAyam
                    ],
                    'calculations'  => [
                        'method'    => 'smallest_unit_based',
                        'version'   => '1.2',
                        'timestamp' => now()->toIso8601String(),
                    ]
                ]
            ]
        );

        return $livestockCost;
    }
    // public function calculateForDate($livestockId, $tanggal)
    // {
    //     $tanggal = Carbon::parse($tanggal)->format('Y-m-d');
    //     $recording = Recording::where('livestock_id', $livestockId)
    //                         ->whereDate('tanggal', $tanggal)
    //                         ->first();

    //     $livestock = Livestock::findOrFail($livestockId);
    //     if (!$recording) return null;

    //     // Feed cost
    //     $feedUsageDetails = FeedUsageDetail::whereHas('feedUsage', function ($query) use ($livestockId, $tanggal) {
    //         $query->where('livestock_id', $livestockId)
    //             ->whereDate('usage_date', $tanggal);
    //     })->with('feedStock.feedPurchase', 'feedStock.feed')->get();

    //     $feedCost = 0;
    //     $feedDetails = [];

    //     foreach ($feedUsageDetails as $detail) {
    //         $namaPakan = $detail->feedStock?->feed?->name ?? 'Unknown Feed';
    //         $harga = $detail->feedStock?->feedPurchase?->price_per_unit ?? 0;
    //         $qtyKg = $detail->quantity_taken ?? 0;
    //         $subtotal = $qtyKg * $harga;

    //         $feedCost += $subtotal;

    //         if (isset($feedDetails[$namaPakan])) {
    //             $feedDetails[$namaPakan]['jumlah_kg'] += $qtyKg;
    //             $feedDetails[$namaPakan]['subtotal'] += $subtotal;
    //         } else {
    //             $feedDetails[$namaPakan] = [
    //                 'jumlah_kg' => $qtyKg,
    //                 'subtotal' => $subtotal,
    //             ];
    //         }
    //     }

    //     // Data populasi
    //     $stockAwal = $recording->stock_awal;
    //     $stockAkhir = $recording->stock_akhir;
    //     $deplesiQty = ($recording->payload['mortality'] ?? 0) + ($recording->payload['culling'] ?? 0);
    //     $salesQty = $recording->payload['sales_quantity'] ?? 0;

    //     // Histori sebelumnya
    //     // $totalPrevCost = LivestockCost::where('livestock_id', $livestockId)
    //     //                             ->whereDate('tanggal', '<', $tanggal)
    //     //                             ->sum('total_cost');

    //     // Do this:
    //     $prevCosts = LivestockCost::where('livestock_id', $livestockId)
    //     ->whereDate('tanggal', '<', $tanggal)
    //     ->get();

    //     $totalPakan = 0;
    //     $totalDeplesi = 0;

    //     foreach ($prevCosts as $cost) {
    //         $breakdown = $cost->cost_breakdown; // assuming this is a JSON or array field
    //         $totalPakan += $breakdown['pakan'] ?? 0;
    //         $totalDeplesi += $breakdown['deplesi'] ?? 0;
    //         // Add other breakdowns if needed
    //     }

    //     $totalPrevCost = $totalPakan + $totalDeplesi;

    //     $totalPrevPopulasi = Recording::where('livestock_id', $livestockId)
    //                                 ->whereDate('tanggal', '<', $tanggal)
    //                                 ->get()
    //                                 ->sum(function ($record) {
    //                                     $mortality = $record->payload['mortality'] ?? 0;
    //                                     $culling   = $record->payload['culling'] ?? 0;
    //                                     $sales     = $record->payload['sales_quantity'] ?? 0;
    //                                     return $record->stock_awal - $mortality - $culling - $sales;
    //                                 });

    //     $prevCostPerAyam = $totalPrevPopulasi > 0
    //         ? $totalPrevCost / $totalPrevPopulasi
    //         : 0;

    //     // // === Logika baru ===
    //     // if ($totalPrevCost == 0) {
    //     //     // Belum ada histori → gunakan harga beli
    //     //     $deplesiCost = $deplesiQty * $livestock->harga;
    //     //     $totalCost = $feedCost + $deplesiCost;
    //     // } else {
    //     //     // Sudah ada histori → gunakan histori + feed hari ini + deplesi (harga beli + histori cost per ayam)
    //     //     $deplesiCost = $deplesiQty * ($livestock->harga + $prevCostPerAyam);
    //     //     $totalCost = $totalPrevCost + $feedCost + $deplesiCost;
    //     // }

    //     // Ambil cost per ayam dari hari sebelumnya (bukan rata-rata seluruh histori)
    //     $prevCostRecord = LivestockCost::where('livestock_id', $livestockId)
    //     ->whereDate('tanggal', '<', $tanggal)
    //     ->orderByDesc('tanggal')
    //     ->first();

    //     $prevCostPerAyam = $prevCostRecord ? ($prevCostRecord->cost_per_ayam ?? 0) : 0;

    //     // Hitung deplesi sesuai rumus baru
    //     $deplesiCost = $deplesiQty * ($livestock->harga + $prevCostPerAyam);

    //     // Total cost tetap breakdown pakan + deplesi + histori sebelumnya
    //     $totalCost = $totalPrevCost + $feedCost + $deplesiCost;

    //     $activeStock = $stockAkhir;
    //     $costPerAyam = $activeStock > 0 ? round($totalCost / $activeStock, 2) : 0;

    //     // dd([
    //     //     'totalcost' => $totalCost,
    //     //     'totalprevcost' => $totalPrevCost,
    //     //     'prevCostPerAyam' => $prevCostPerAyam,
    //     //     'costPerAyam' => $costPerAyam,
    //     //     'deplesicost' => $deplesiCost,
    //     // ]);

    //     // dd($totalCost);

    //     $livestockCost = LivestockCost::updateOrCreate(
    //         [
    //             'livestock_id' => $livestockId,
    //             'tanggal' => $tanggal,
    //         ],
    //         [
    //             'recording_id' => $recording->id,
    //             'total_cost' => $totalCost,
    //             'cost_per_ayam' => $costPerAyam,
    //             'cost_breakdown' => [
    //                 'pakan'         => $feedCost,
    //                 'deplesi'       => round($deplesiCost, 2),
    //                 'cost_per_ayam' => $costPerAyam,
    //                 'deplesi_ekor'  => $deplesiQty,
    //                 'jual_ekor'     => $salesQty,
    //                 'stock_awal'    => $stockAwal,
    //                 'stock_akhir'   => $stockAkhir,
    //                 'feed_detail'   => $feedDetails,
    //             ]
    //         ]
    //     );

    //     return $livestockCost;
    // }



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

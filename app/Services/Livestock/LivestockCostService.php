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

        // Feed cost
        $feedUsageDetails = FeedUsageDetail::whereHas('feedUsage', function ($query) use ($livestockId, $tanggal) {
            $query->where('livestock_id', $livestockId)
                ->whereDate('usage_date', $tanggal);
        })->with('feedStock.feedPurchase', 'feedStock.feed')->get();

        $feedCost = 0;
        $feedDetails = [];

        foreach ($feedUsageDetails as $detail) {
            $namaPakan = $detail->feedStock?->feed?->name ?? 'Unknown Feed';
            $harga = $detail->feedStock?->feedPurchase?->price_per_unit ?? 0;
            $qtyKg = $detail->quantity_taken ?? 0;
            $subtotal = $qtyKg * $harga;

            $feedCost += $subtotal;

            if (isset($feedDetails[$namaPakan])) {
                $feedDetails[$namaPakan]['jumlah_kg'] += $qtyKg;
                $feedDetails[$namaPakan]['subtotal'] += $subtotal;
            } else {
                $feedDetails[$namaPakan] = [
                    'jumlah_kg' => $qtyKg,
                    'subtotal' => $subtotal,
                ];
            }
        }

        // Data populasi
        $stockAwal = $recording->stock_awal;
        $stockAkhir = $recording->stock_akhir;
        $deplesiQty = ($recording->payload['mortality'] ?? 0) + ($recording->payload['culling'] ?? 0);
        $salesQty = $recording->payload['sales_quantity'] ?? 0;

        // Histori sebelumnya
        $totalPrevCost = LivestockCost::where('livestock_id', $livestockId)
                                    ->whereDate('tanggal', '<', $tanggal)
                                    ->sum('total_cost');

        $totalPrevPopulasi = Recording::where('livestock_id', $livestockId)
                                    ->whereDate('tanggal', '<', $tanggal)
                                    ->get()
                                    ->sum(function ($record) {
                                        $mortality = $record->payload['mortality'] ?? 0;
                                        $culling   = $record->payload['culling'] ?? 0;
                                        $sales     = $record->payload['sales_quantity'] ?? 0;
                                        return $record->stock_awal - $mortality - $culling - $sales;
                                    });

        $prevCostPerAyam = $totalPrevPopulasi > 0
            ? $totalPrevCost / $totalPrevPopulasi
            : 0;

        // === Logika baru ===
        if ($totalPrevCost == 0) {
            // Belum ada histori → gunakan harga beli
            $deplesiCost = $deplesiQty * $livestock->harga;
            $totalCost = $feedCost + $deplesiCost;
        } else {
            // Sudah ada histori → gunakan histori + feed hari ini + deplesi (harga beli + histori cost per ayam)
            $deplesiCost = $deplesiQty * ($livestock->harga + $prevCostPerAyam);
            $totalCost = $totalPrevCost + $feedCost + $deplesiCost;
        }

        $activeStock = $stockAkhir;
        $costPerAyam = $activeStock > 0 ? round($totalCost / $activeStock, 2) : 0;

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
                    'deplesi_ekor'  => $deplesiQty,
                    'jual_ekor'     => $salesQty,
                    'stock_awal'    => $stockAwal,
                    'stock_akhir'   => $stockAkhir,
                    'feed_detail'   => $feedDetails,
                ]
            ]
        );

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

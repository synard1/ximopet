<?php

namespace App\Http\Controllers\MasterData;

use App\DataTables\FeedsDataTable;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Models\FeedStock;
use App\Models\FeedPurchaseBatch;
use App\Models\FeedPurchase;
use App\Models\FeedMutation;
use App\Models\FeedMutationItem;
use App\Models\FeedUsage;
use App\Models\FeedUsageDetail;
use App\Models\Livestock;
use App\Models\Farm;
use App\Models\Kandang;

class FeedController extends Controller
{
    public function index(FeedsDataTable $dataTable)
    {
        addVendors(['datatables']);

        return $dataTable->render('pages/masterdata.feed.index');
    }

    public function getFeedPurchaseBatchDetail($batchId)
    {

        $feedPurchases = FeedPurchase::with([
            'feedItem:id,code,name,unit,unit_conversion,conversion',
            'feedStocks' // <- relasi baru nanti ditambahkan
        ])
        ->where('feed_purchase_batch_id', $batchId)
        ->get(['id', 'feed_purchase_batch_id', 'feed_id', 'quantity', 'price_per_unit']);

        $formatted = $feedPurchases->map(function ($item) {
            $feedItem = optional($item->feedItem);
            $konversi = floatval($feedItem->conversion) ?: 1;

            $quantity = floatval($item->quantity);
            $converted_quantity = $quantity * $konversi;

            // Summary dari semua FeedStock berdasarkan purchase
            $used = $item->feedStocks->sum('quantity_used');
            $mutated = $item->feedStocks->sum('quantity_mutated');
            $available = $item->feedStocks->sum('available');

            return [
                'id' => $item->id,
                'kode' => $feedItem->code,
                'name' => $feedItem->name,
                'quantity' => $quantity,
                'converted_quantity' => $converted_quantity,
                'sisa' => $quantity - $used,
                'unit' => $feedItem->unit,
                'unit_conversion' => $feedItem->unit_conversion,
                'conversion' => $konversi,
                'price_per_unit' => floatval($item->price_per_unit),
                'total' => config('xolution.ALLOW_ROUNDUP_PRICE')
                    ? floatval($quantity * $item->price_per_unit)
                    : intval($quantity * $item->price_per_unit),

                // Tambahan penggunaan dan mutasi
                'terpakai' => $used / $konversi,
                'mutated' => $mutated / $konversi,
                'available' => $available / $konversi,
            ];
        });

        return response()->json(['data' => $formatted]);
    }

    public function stockEdit(Request $request)
    {
        $id = $request->input('id');
        $value = $request->input('value');
        $column = $request->input('column');
        $user_id = auth()->id();

        // dd($request->all());

        try {
            DB::beginTransaction();

            $feedPurchase = FeedPurchase::with('feedItem')->findOrFail($id);
            $feedItem = $feedPurchase->feedItem;
            $konversi = floatval($feedItem->conversion) ?: 1;

            $feedStock = FeedStock::where('feed_purchase_id', $feedPurchase->id)->first();

            if ($column === 'quantity') {
                $usedQty = $feedStock->quantity_used ?? 0;
                $mutatedQty = $feedStock->quantity_mutated ?? 0;
                $sisa = $usedQty + $mutatedQty;
            
                if (($value * $konversi) < $sisa) {
                    return response()->json([
                        'message' => 'Jumlah baru lebih kecil dari jumlah yang sudah terpakai atau dimutasi',
                        'status' => 'error'
                    ], 422);
                }
            
                // Update FeedStock
                $feedStock->update([
                    'quantity_in' => $value * $konversi,
                    'available' => ($value * $konversi) - $sisa,
                    'updated_by' => $user_id,
                ]);
            
                // Update FeedPurchase
                $feedPurchase->update([
                    'quantity' => $value,
                    'updated_by' => $user_id,
                ]);
            } else {
                // Update price
                $feedPurchase->update([
                    'price_per_unit' => $value,
                    'updated_by' => $user_id,
                ]);

                $feedStock->update([
                    'amount' => $feedPurchase->quantity * $value,
                    'updated_by' => $user_id,
                ]);
            }

            // Update sub_total dan sisa berdasarkan usage
            $subTotal = $feedPurchase->quantity * $feedPurchase->price_per_unit;
            $usedQty = $feedStock->quantity_used ?? 0;
            $mutatedQty = $feedStock->quantity_mutated ?? 0;
            $available = ($feedPurchase->quantity * $konversi) - $usedQty - $mutatedQty;

            $feedStock->update([
                'available' => $available,
                'amount' => $subTotal,
            ]);

            // Update Batch total summary
            $batch = \App\Models\FeedPurchaseBatch::with('feedPurchases.feedItem')->findOrFail($feedPurchase->feed_purchase_batch_id);

            $totalQty = $batch->feedPurchases->sum(function ($purchase) {
                $konversi = floatval(optional($purchase->feedItem)->konversi) ?: 1;
                return $purchase->quantity;
            });

            $totalHarga = $batch->feedPurchases->sum(function ($purchase) {
                return $purchase->price_per_unit * $purchase->quantity;
            });

            $batch->update([
                'expedition_fee' => $batch->expedition_fee,
                'updated_by' => $user_id,
            ]);

            DB::commit();

            return response()->json(['message' => 'Berhasil Update Data', 'status' => 'success']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function getFeedCardByLivestock(Request $request)
    {
        $validated = $request->validate([
            'livestock_id' => 'required|uuid',
            'feed_id' => 'required|uuid',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $livestockId = $validated['livestock_id'];
        $feedId = $validated['feed_id'];
        $startDate = $validated['start_date'] ? Carbon::parse($validated['start_date']) : null;
        $endDate = $validated['end_date'] ? Carbon::parse($validated['end_date']) : null;

        try {
            $stocks = FeedStock::with([
                'feed',
                'feedPurchase.batch',
                'feedUsageDetails.feedUsage.livestock',
                'mutationDetails.mutation.toLivestock',
                'incomingMutation.mutation.fromLivestock',
            ])
                ->where('livestock_id', $livestockId)
                ->where('feed_id', $feedId)
                ->get();

            $result = [];

            // Proses transaksi pembelian awal
            $purchaseStocks = $stocks->whereNotNull('feed_purchase_id')->groupBy('feed_purchase_id');
            foreach ($purchaseStocks as $purchaseId => $items) {
                $first = $items->first();
                $histories = [];
                $purchaseDate = optional($first->feedPurchase->batch)->date;

                if ($purchaseDate && (!$startDate || $purchaseDate >= $startDate) && (!$endDate || $purchaseDate <= $endDate)) {
                    $histories[] = [
                        'tanggal' => $purchaseDate->format('Y-m-d'),
                        'keterangan' => 'Pembelian',
                        'masuk' => $items->sum('quantity_in'), // Aggregate quantity for the purchase
                        'keluar' => 0,
                    ];

                    $runningStock = $items->sum('quantity_in'); // Initial stock after purchase
                    $histories = $this->processUsageAndMutation($items, $histories, $startDate, $endDate, $runningStock);

                    usort($histories, fn($a, $b) => strcmp($a['tanggal'], $b['tanggal']));
                    $runningStock = 0;
                    foreach ($histories as &$entry) {
                        $entry['stok_awal'] = $runningStock;
                        $runningStock += ($entry['masuk'] ?? 0) - ($entry['keluar'] ?? 0);
                        $entry['stok_akhir'] = $runningStock;
                    }

                    $result[] = [
                        'feed_purchase_info' => [
                            'feed_name' => $first->feed->name ?? '-',
                            'no_batch' => optional($first->feedPurchase->batch)->invoice_number ?? '-',
                            'tanggal' => $purchaseDate->format('Y-m-d'),
                            'harga' => $first->feedPurchase->price_per_unit ?? 0,
                            'tipe' => 'Pembelian',
                        ],
                        'histories' => $histories,
                    ];
                }
            }

            // Proses transaksi mutasi masuk
            $mutationInStocks = $stocks->whereNotNull('source_id')->groupBy('source_id');
            foreach ($mutationInStocks as $mutationId => $items) {
                $first = $items->first();
                $mutation = FeedMutation::find($mutationId);
                $histories = [];

                if ($mutation && (!$startDate || $mutation->date >= $startDate) && (!$endDate || $mutation->date <= $endDate)) {
                    $histories[] = [
                        'tanggal' => $mutation->date->format('Y-m-d'),
                        'keterangan' => 'Mutasi dari ' . ($mutation->fromLivestock->name ?? '-'),
                        'masuk' => $items->sum('quantity_in'), // Aggregate quantity for the mutation
                        'keluar' => 0,
                    ];

                    $runningStock = $items->sum('quantity_in'); // Initial stock after mutation
                    $histories = $this->processUsageAndMutation($items, $histories, $startDate, $endDate, $runningStock);

                    usort($histories, fn($a, $b) => strcmp($a['tanggal'], $b['tanggal']));
                    $runningStock = 0;
                    foreach ($histories as &$entry) {
                        $entry['stok_awal'] = $runningStock;
                        $runningStock += ($entry['masuk'] ?? 0) - ($entry['keluar'] ?? 0);
                        $entry['stok_akhir'] = $runningStock;
                    }

                    $result[] = [
                        'feed_purchase_info' => [
                            'feed_name' => $first->feed->name ?? '-',
                            'no_batch' => '-',
                            'tanggal' => $mutation->date->format('Y-m-d'),
                            'harga' => 0,
                            'tipe' => 'Mutasi Masuk',
                        ],
                        'histories' => $histories,
                    ];
                }
            }

            // Proses transaksi mutasi masuk berdasarkan incomingMutation (jika source_id tidak ada)
            $mutationInByRelationStocks = $stocks->whereNull('source_id')->whereNotNull('incomingMutation')->groupBy('incomingMutation.feed_mutation_id');
            foreach ($mutationInByRelationStocks as $mutationId => $items) {
                $first = $items->first();
                $mutation = $first->incomingMutation->mutation;
                $histories = [];

                if ($mutation && (!$startDate || $mutation->date >= $startDate) && (!$endDate || $mutation->date <= $endDate)) {
                    $histories[] = [
                        'tanggal' => $mutation->date->format('Y-m-d'),
                        'keterangan' => 'Mutasi dari ' . ($mutation->fromLivestock->name ?? '-'),
                        'masuk' => $items->sum('quantity_in'), // Aggregate quantity for the mutation
                        'keluar' => 0,
                    ];

                    $runningStock = $items->sum('quantity_in'); // Initial stock after mutation
                    $histories = $this->processUsageAndMutation($items, $histories, $startDate, $endDate, $runningStock);

                    usort($histories, fn($a, $b) => strcmp($a['tanggal'], $b['tanggal']));
                    $runningStock = 0;
                    foreach ($histories as &$entry) {
                        $entry['stok_awal'] = $runningStock;
                        $runningStock += ($entry['masuk'] ?? 0) - ($entry['keluar'] ?? 0);
                        $entry['stok_akhir'] = $runningStock;
                    }

                    $result[] = [
                        'feed_purchase_info' => [
                            'feed_name' => $first->feed->name ?? '-',
                            'no_batch' => '-',
                            'tanggal' => $mutation->date->format('Y-m-d'),
                            'harga' => 0,
                            'tipe' => 'Mutasi Masuk (Relasi)',
                        ],
                        'histories' => $histories,
                    ];
                }
            }

            return response()->json([
                'status' => 'success',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    protected function processUsageAndMutation($items, array $histories, ?Carbon $startDate, ?Carbon $endDate, &$runningStock): array
    {
        foreach ($items as $stock) {
            // Pemakaian
            foreach ($stock->feedUsageDetails as $usageDetail) {
                $usageDate = $usageDetail->feedUsage->usage_date;
                if ($usageDate && (!$startDate || $usageDate >= $startDate) && (!$endDate || $usageDate <= $endDate)) {
                    $histories[] = [
                        'tanggal' => $usageDate->format('Y-m-d'),
                        'keterangan' => 'Pemakaian Ternak ' . ($usageDetail->feedUsage->livestock->name ?? '-'),
                        'masuk' => 0,
                        'keluar' => $usageDetail->quantity_taken,
                    ];
                }
            }
            // Mutasi keluar
            foreach ($stock->mutationDetails as $mutation) {
                $mutationDate = $mutation->mutation->date;
                if ($mutationDate && (!$startDate || $mutationDate >= $startDate) && (!$endDate || $mutationDate <= $endDate)) {
                    $histories[] = [
                        'tanggal' => $mutationDate->format('Y-m-d'),
                        'keterangan' => 'Mutasi ke ' . ($mutation->mutation->toLivestock->name ?? '-'),
                        'masuk' => 0,
                        'keluar' => $mutation->quantity,
                    ];
                }
            }
        }
        return $histories;
    }


    // error mutasi tercatat sebagai pembelian
    // public function getFeedCardByLivestock(Request $request)
    // {
    //     $validated = $request->validate([
    //         'livestock_id' => 'required|uuid',
    //         'feed_id' => 'required|uuid',
    //         'start_date' => 'nullable|date',
    //         'end_date' => 'nullable|date',
    //     ]);

    //     try {
    //         $stocks = FeedStock::with([
    //             'feed',
    //             'feedPurchase.batch',
    //             'feedUsageDetails.feedUsage.livestock',
    //             'mutationDetails.mutation.toLivestock',
    //         ])
    //         ->where('livestock_id', $validated['livestock_id'])
    //         ->where('feed_id', $validated['feed_id'])
    //         ->get();

    //         // dd($stocks);

    //         $start = $validated['start_date'] ? Carbon::parse($validated['start_date']) : null;
    //         $end = $validated['end_date'] ? Carbon::parse($validated['end_date']) : null;

    //         $grouped = $stocks->groupBy(fn($stock) => optional($stock->feedPurchase->batch)->id);

    //         $result = $grouped->map(function ($items) use ($start, $end) {
    //             $first = $items->first();
    //             $histories = [];

    //             foreach ($items as $stock) {
    //                 // Pembelian
    //                 $purchaseDate = optional($stock->feedPurchase->batch)->date;
    //                 if ($purchaseDate && (!$start || $purchaseDate >= $start) && (!$end || $purchaseDate <= $end)) {
    //                     $histories[] = [
    //                         'tanggal' => $purchaseDate->format('Y-m-d'),
    //                         'keterangan' => 'Pembelian',
    //                         'masuk' => $stock->quantity_in,
    //                         'keluar' => 0,
    //                     ];
    //                 }

    //                 // Pemakaian
    //                 foreach ($stock->feedUsageDetails as $usageDetail) {
    //                     $usageDate = $usageDetail->feedUsage->usage_date;
    //                     if ($usageDate && (!$start || $usageDate >= $start) && (!$end || $usageDate <= $end)) {
    //                         $histories[] = [
    //                             'tanggal' => $usageDate->format('Y-m-d'),
    //                             'keterangan' => 'Pemakaian Ternak ' . ($usageDetail->feedUsage->livestock->name ?? '-'),
    //                             'masuk' => 0,
    //                             'keluar' => $usageDetail->quantity_taken,
    //                         ];
    //                     }
    //                 }

    //                 // Mutasi
    //                 foreach ($stock->mutationDetails as $mutation) {
    //                     $mutationDate = $mutation->mutation->date;
    //                     if ($mutationDate && (!$start || $mutationDate >= $start) && (!$end || $mutationDate <= $end)) {
    //                         $histories[] = [
    //                             'tanggal' => $mutationDate->format('Y-m-d'),
    //                             'keterangan' => 'Mutasi ke ' . ($mutation->mutation->toLivestock->name ?? '-'),
    //                             'masuk' => 0,
    //                             'keluar' => $mutation->quantity,
    //                         ];
    //                     }
    //                 }
    //             }

    //             // Kalau tidak ada histori, skip
    //             if (empty($histories)) {
    //                 return null;
    //             }

    //             // Urutkan berdasarkan tanggal (dan buat running stok)
    //             usort($histories, fn($a, $b) => strcmp($a['tanggal'], $b['tanggal']));

    //             $runningStock = 0;
    //             foreach ($histories as &$entry) {
    //                 $entry['stok_awal'] = $runningStock;
    //                 $runningStock += ($entry['masuk'] ?? 0) - ($entry['keluar'] ?? 0);
    //                 $entry['stok_akhir'] = $runningStock;
    //             }

    //             return [
    //                 'feed_purchase_info' => [
    //                     'feed_name' => $first->feed->name ?? '-',
    //                     'no_batch' => $first->feedPurchase->batch->invoice_number ?? '-',
    //                     'tanggal' => optional($first->feedPurchase->batch)->date?->format('Y-m-d'),
    //                     'harga' => $first->feedPurchase->price_per_unit ?? 0,
    //                 ],
    //                 'histories' => $histories,
    //             ];
    //         })
    //         ->filter()
    //         ->values();

    //         return response()->json([
    //             'status' => 'success',
    //             'data' => $result,
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    public function exportPembelian(Request $request)
    {
        $purchases = FeedPurchase::with([
            'feedItem',
            'batch.vendor',
            'livestok',
        ])
        ->where('livestock_id',$request->periode)
        ->latest()->get();
        // $purchases = FeedPurchase::with(['feedItem'])->where('livestock_id',$request->periode)->latest()->get();
        // dd($purchases);

        if ($purchases->isNotEmpty()) {
            return view('pages.reports.feed.feed_purchase', compact('purchases'));
        } else {
            return response()->json([
                'error' => 'Data pembelian belum ada'
            ], 404);
        }
    }

    public function indexReportFeedPurchase()
    {
        $livestock = Livestock::all();
        $farms = Farm::whereIn('id', $livestock->pluck('farm_id'))->get();
        $kandangs = Kandang::whereIn('id', $livestock->pluck('kandang_id'))->get();

        $livestock = $livestock->map(function ($item) {
            return [
                'id' => $item->id,
                'farm_id' => $item->farm_id,
                'farm_name' => $item->farm->nama,
                'kandang_id' => $item->kandang_id,
                'kandang_name' => $item->kandang->nama,
                'name' => $item->name,
                'start_date' => $item->start_date,
                'year' => $item->start_date->format('Y'),
            ];
        })->toArray();

        return view('pages.reports.feed.index_feed_purchase', compact(['farms','kandangs','livestock']));
    }


    // public function getFeedCardByLivestock(Request $request)
    // {
    //     $validated = $request->validate([
    //         'livestock_id' => 'required|uuid',
    //         'feed_id' => 'required|uuid',
    //         'start_date' => 'nullable|date',
    //         'end_date' => 'nullable|date',
    //     ]);

    //     try {
    //         $stocks = FeedStock::with([
    //             'feed',
    //             'feedPurchase.batch',
    //             'feedUsageDetails.feedUsage.livestock',
    //             'mutationDetails.mutation.toLivestock',
    //         ])
    //         ->where('livestock_id', $validated['livestock_id'])
    //         ->where('feed_id', $validated['feed_id'])
    //         ->get();

    //         $start = $validated['start_date'] ? Carbon::parse($validated['start_date']) : null;
    //         $end = $validated['end_date'] ? Carbon::parse($validated['end_date']) : null;

    //         $grouped = $stocks->groupBy(fn($stock) => optional($stock->feedPurchase->batch)->id);

    //         $result = $grouped->map(function ($items) use ($start, $end) {
    //             $first = $items->first();
    //             $histories = [];

    //             foreach ($items as $stock) {
    //                 // Pembelian
    //                 $purchaseDate = optional($stock->feedPurchase->batch)->date;
    //                 if ($purchaseDate && (!$start || $purchaseDate >= $start) && (!$end || $purchaseDate <= $end)) {
    //                     $histories[] = [
    //                         'tanggal' => $purchaseDate->format('Y-m-d'),
    //                         'keterangan' => 'Pembelian',
    //                         'stok_awal' => 0,
    //                         'masuk' => $stock->quantity_in,
    //                         'keluar' => 0,
    //                         'stok_akhir' => $stock->quantity_in,
    //                     ];
    //                 }

    //                 // Pemakaian
    //                 foreach ($stock->feedUsageDetails as $usageDetail) {
    //                     $usageDate = $usageDetail->feedUsage->usage_date;
    //                     if ($usageDate && (!$start || $usageDate >= $start) && (!$end || $usageDate <= $end)) {
    //                         $histories[] = [
    //                             'tanggal' => $usageDate->format('Y-m-d'),
    //                             'keterangan' => 'Pemakaian Ternak ' . ($usageDetail->feedUsage->livestock->name ?? '-'),
    //                             'stok_awal' => 0,
    //                             'masuk' => 0,
    //                             'keluar' => $usageDetail->quantity_taken,
    //                             'stok_akhir' => 0,
    //                         ];
    //                     }
    //                 }

    //                 // Mutasi
    //                 foreach ($stock->mutationDetails as $mutation) {
    //                     $mutationDate = $mutation->mutation->date;
    //                     if ($mutationDate && (!$start || $mutationDate >= $start) && (!$end || $mutationDate <= $end)) {
    //                         $histories[] = [
    //                             'tanggal' => $mutationDate->format('Y-m-d'),
    //                             'keterangan' => 'Mutasi ke ' . ($mutation->mutation->toLivestock->name ?? '-'),
    //                             'stok_awal' => 0,
    //                             'masuk' => 0,
    //                             'keluar' => $mutation->quantity,
    //                             'stok_akhir' => 0,
    //                         ];
    //                     }
    //                 }
    //             }

    //             // Kalau kosong, skip data ini
    //             if (empty($histories)) {
    //                 return null;
    //             }

    //             // Urutkan berdasarkan tanggal
    //             usort($histories, fn($a, $b) => $a['tanggal'] <=> $b['tanggal']);

    //             return [
    //                 'feed_purchase_info' => [
    //                     'feed_name' => $first->feed->name ?? '-',
    //                     'no_batch' => $first->feedPurchase->batch->invoice_number ?? '-',
    //                     'expired_date' => optional($first->feedPurchase->batch)->date?->format('Y-m-d'),
    //                     'hpp' => $first->feedPurchase->price_per_unit ?? 0,
    //                 ],
    //                 'histories' => $histories,
    //             ];
    //         })
    //         // Hapus hasil null (yang histories-nya kosong)
    //         ->filter()
    //         ->values();

    //         return response()->json([
    //             'status' => 'success',
    //             'data' => $result,
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => $e->getMessage(),
    //         ], 500);
    //     }
    // }



    // tidak hidden accordion
    // public function getFeedCardByLivestock(Request $request)
    // {
    //     $validated = $request->validate([
    //         'livestock_id' => 'required|uuid',
    //         'feed_id' => 'required|uuid',
    //         'start_date' => 'nullable|date',
    //         'end_date' => 'nullable|date',
    //     ]);

    //     try {
    //         $stocks = FeedStock::with([
    //             'feed',
    //             'feedPurchase.batch',
    //             'feedUsageDetails.feedUsage.livestock',
    //             'mutationDetails.mutation.toLivestock',
    //         ])
    //         ->where('livestock_id', $validated['livestock_id'])
    //         ->where('feed_id', $validated['feed_id'])
    //         ->get();

    //         $start = $validated['start_date'] ? Carbon::parse($validated['start_date']) : null;
    //         $end = $validated['end_date'] ? Carbon::parse($validated['end_date']) : null;

    //         $grouped = $stocks->groupBy(fn($stock) => optional($stock->feedPurchase->batch)->id);

    //         $result = $grouped->map(function ($items) use ($start, $end) {
    //             $first = $items->first();
    //             $histories = [];

    //             foreach ($items as $stock) {
    //                 // Pembelian
    //                 $purchaseDate = optional($stock->feedPurchase->batch)->date;
    //                 if ($purchaseDate && (!$start || $purchaseDate >= $start) && (!$end || $purchaseDate <= $end)) {
    //                     $histories[] = [
    //                         'tanggal' => $purchaseDate->format('Y-m-d'),
    //                         'keterangan' => 'Pembelian',
    //                         'stok_awal' => 0,
    //                         'masuk' => $stock->quantity_in,
    //                         'keluar' => 0,
    //                         'stok_akhir' => $stock->quantity_in,
    //                     ];
    //                 }

    //                 // Pemakaian
    //                 foreach ($stock->feedUsageDetails as $usageDetail) {
    //                     $usageDate = $usageDetail->feedUsage->usage_date;
    //                     if ($usageDate && (!$start || $usageDate >= $start) && (!$end || $usageDate <= $end)) {
    //                         $histories[] = [
    //                             'tanggal' => $usageDate->format('Y-m-d'),
    //                             'keterangan' => 'Pemakaian Ternak ' . ($usageDetail->feedUsage->livestock->name ?? '-'),
    //                             'stok_awal' => 0,
    //                             'masuk' => 0,
    //                             'keluar' => $usageDetail->quantity_taken,
    //                             'stok_akhir' => 0,
    //                         ];
    //                     }
    //                 }

    //                 // Mutasi
    //                 foreach ($stock->mutationDetails as $mutation) {
    //                     $mutationDate = $mutation->mutation->date;
    //                     if ($mutationDate && (!$start || $mutationDate >= $start) && (!$end || $mutationDate <= $end)) {
    //                         $histories[] = [
    //                             'tanggal' => $mutationDate->format('Y-m-d'),
    //                             'keterangan' => 'Mutasi ke ' . ($mutation->mutation->toLivestock->name ?? '-'),
    //                             'stok_awal' => 0,
    //                             'masuk' => 0,
    //                             'keluar' => $mutation->quantity,
    //                             'stok_akhir' => 0,
    //                         ];
    //                     }
    //                 }
    //             }

    //             // Urutkan berdasarkan tanggal
    //             usort($histories, fn($a, $b) => $a['tanggal'] <=> $b['tanggal']);

    //             return [
    //                 'feed_purchase_info' => [
    //                     'feed_name' => $first->feed->name ?? '-',
    //                     'no_batch' => $first->feedPurchase->batch->invoice_number ?? '-',
    //                     'expired_date' => optional($first->feedPurchase->batch)->date?->format('Y-m-d'),
    //                     'hpp' => $first->feedPurchase->price_per_unit ?? 0,
    //                 ],
    //                 'histories' => $histories,
    //             ];
    //         })->values();

    //         return response()->json([
    //             'status' => 'success',
    //             'data' => $result,
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => $e->getMessage(),
    //         ], 500);
    //     }
    // }


    // tidak ada filter range date
    // public function getFeedCardByLivestock(Request $request)
    // {
    //     $validated = $request->validate([
    //         'livestock_id' => 'required|uuid',
    //         'feed_id' => 'required|uuid',
    //     ]);

    //     try {
    //         $stocks = FeedStock::with([
    //             'feedPurchase.batch',
    //             'feedUsageDetails.feedUsage',
    //             'mutationDetails.mutation',
    //         ])
    //         ->where('livestock_id', $validated['livestock_id'])
    //         ->where('feed_id', $validated['feed_id'])
    //         ->get();

    //         $grouped = $stocks->groupBy(fn($stock) => optional($stock->feedPurchase->batch)->id);

    //         $result = $grouped->map(function ($items) {
    //             $first = $items->first();

    //             $histories = [];

    //             foreach ($items as $stock) {
    //                 // Tambahkan histori pembelian
    //                 $histories[] = [
    //                     'tanggal' => optional($stock->feedPurchase->batch)->date?->format('Y-m-d'),
    //                     'keterangan' => 'Pembelian',
    //                     'stok_awal' => 0, // Hitung dari histori sebelumnya jika perlu
    //                     'masuk' => $stock->quantity_in,
    //                     'keluar' => 0,
    //                     'stok_akhir' => $stock->quantity_in, // Hitung dari histori sebelumnya jika perlu
    //                 ];

    //                 // Tambahkan histori penggunaan
    //                 foreach ($stock->feedUsageDetails as $usageDetail) {
    //                     $histories[] = [
    //                         'tanggal' => $usageDetail->feedUsage->usage_date->format('Y-m-d'),
    //                         'keterangan' => 'Pemakaian Ternak ' . ($usageDetail->feedUsage->livestock->name ?? '-'),
    //                         'stok_awal' => 0,
    //                         'masuk' => 0,
    //                         'keluar' => $usageDetail->quantity_taken,
    //                         'stok_akhir' => 0, // Update ini berdasarkan logika berjalan
    //                     ];
    //                 }

    //                 // Tambahkan histori mutasi
    //                 foreach ($stock->mutationDetails as $mutation) {
    //                     $histories[] = [
    //                         'tanggal' => $mutation->mutation->date->format('Y-m-d'),
    //                         'keterangan' => 'Mutasi ke ' . ($mutation->mutation->toLivestock->name ?? '-'),
    //                         'stok_awal' => 0,
    //                         'masuk' => 0,
    //                         'keluar' => $mutation->quantity,
    //                         'stok_akhir' => 0,
    //                     ];
    //                 }
    //             }

    //             // Urutkan berdasarkan tanggal
    //             usort($histories, fn($a, $b) => $a['tanggal'] <=> $b['tanggal']);

    //             return [
    //                 'feed_purchase_info' => [
    //                     'feed_name' => $first->feed->name ?? '-',
    //                     'no_batch' => $first->feedPurchase->batch->invoice_number ?? '-',
    //                     'expired_date' => optional($first->feedPurchase->batch)->date?->format('Y-m-d'),
    //                     'hpp' => $first->feedPurchase->price_per_unit ?? 0,
    //                 ],
    //                 'histories' => $histories,
    //             ];
    //         })->values();

    //         return response()->json([
    //             'status' => 'success',
    //             'data' => $result,
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => $e->getMessage(),
    //         ], 500);
    //     }
    // }


    // public function getFeedUsageByLivestock(Request $request)
    // {
    //     $validated = $request->validate([
    //         'livestock_id' => 'required|uuid',
    //         'feed_id' => 'required|uuid',
    //         'start_date' => 'required|date',
    //         'end_date' => 'required|date',
    //     ]);

    //     try {
    //         // Feed Usage Details
    //         $usageDetails = FeedUsageDetail::with([
    //                 'feed',
    //                 'feedStock.feedPurchase.batch',
    //                 'feedUsage.livestock.kandang.farm'
    //             ])
    //             ->whereHas('feedUsage', function ($q) use ($validated) {
    //                 $q->where('livestock_id', $validated['livestock_id'])
    //                 ->whereBetween('usage_date', [$validated['start_date'], $validated['end_date']]);
    //             })
    //             ->where('feed_id', $validated['feed_id'])
    //             ->get();

    //         // Feed Mutations
    //         $mutationItems = FeedMutationItem::with([
    //                 'feedStock.feedPurchase.batch',
    //                 'mutation.toLivestock.kandang.farm'
    //             ])
    //             ->whereHas('mutation', function ($q) use ($validated) {
    //                 $q->where('from_livestock_id', $validated['livestock_id'])
    //                 ->whereBetween('date', [$validated['start_date'], $validated['end_date']]);
    //             })
    //             ->whereHas('feedStock', function ($q) use ($validated) {
    //                 $q->where('feed_id', $validated['feed_id']);
    //             })
    //             ->get();

    //         // dd($mutationItems);

    //         $combined = collect();

    //         foreach ($usageDetails as $item) {
    //             $combined->push([
    //                 'type' => 'usage',
    //                 'tanggal' => $item->feedUsage->usage_date->format('Y-m-d'),
    //                 'jumlah' => $item->quantity_taken,
    //                 'feed_name' => $item->feed->name ?? '-',
    //                 'farm_name' => $item->feedUsage->livestock->kandang->farm->nama ?? '-',
    //                 'kandang_name' => $item->feedUsage->livestock->kandang->nama ?? '-',
    //                 'livestock_name' => $item->feedUsage->livestock->name ?? '-',
    //                 'purchase_date' => optional($item->feedStock?->feedPurchase?->batch)->date?->format('Y-m-d') ?? null,
    //             ]);
    //         }

    //         foreach ($mutationItems as $item) {
    //             $combined->push([
    //                 'type' => 'mutation',
    //                 'tanggal' => $item->mutation->date->format('Y-m-d'),
    //                 'jumlah' => $item->quantity,
    //                 'feed_name' => $item->feedStock->feed->name ?? '-',
    //                 'farm_name' => $item->mutation->toLivestock->kandang->farm->nama ?? '-',
    //                 'kandang_name' => $item->mutation->toLivestock->kandang->nama ?? '-',
    //                 'livestock_name' => $item->mutation->toLivestock->name ?? '-',
    //                 'purchase_date' => optional($item->feedStock?->feedPurchase?->batch)->date?->format('Y-m-d') ?? null,
    //             ]);
    //         }

    //         // Grouped by FeedPurchaseBatch Date
    //         $grouped = $combined->groupBy('purchase_date')->map(function ($items, $date) {
    //             return [
    //                 'purchase_date' => $date,
    //                 'usages' => $items->where('type', 'usage')->values(),
    //                 'mutations' => $items->where('type', 'mutation')->values(),
    //             ];
    //         })->values();

    //         return response()->json([
    //             'status' => 'success',
    //             'data' => $grouped,
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => $e->getMessage(),
    //         ], 400);
    //     }
    // }



    // public function getFeedUsageByLivestock(Request $request)
    // {
    //     $validated = $request->validate([
    //         'livestock_id' => 'required|uuid',
    //         'feed_id' => 'required|uuid',
    //         'start_date' => 'required|date',
    //         'end_date' => 'required|date',
    //     ]);

    //     // dd($validated);

    //     try {
    //         $query = FeedUsageDetail::with(['feed', 'feedUsage', 'feedUsage.livestock.kandang.farm'])
    //             ->whereHas('feedUsage', function ($q) use ($validated) {
    //                 // $q->where('livestock_id', '9ebc33ee-3cdb-43f5-aed2-23a3ab39b1ff')->whereBetween('usage_date', ['2025-03-01', '2025-03-02']);

    //                 $q->where('livestock_id', $validated['livestock_id'])
    //                 ->whereBetween('usage_date', [$validated['start_date'], $validated['end_date']]);
    //             })
    //             ->where('feed_id', $validated['feed_id'])
    //             ->orderByDesc('id')
    //             ->get();


    //         // dd($query->toArray());

    //         $result = $query->map(function ($item) {
    //             return [
    //                 'tanggal' => $item->feedUsage->usage_date->format('Y-m-d'),
    //                 'jumlah' => $item->quantity_taken,
    //                 'feed_name' => $item->feed->name ?? '-',
    //                 'farm_name' => $item->feedUsage->livestock->kandang->farm->nama ?? '-',
    //                 'kandang_name' => $item->feedUsage->livestock->kandang->nama ?? '-',
    //                 'livestock_name' => $item->feedUsage->livestock->name ?? '-',
    //             ];
    //         });

    //         return response()->json([
    //             'status' => 'success',
    //             'data' => $result,
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => $e->getMessage(),
    //         ], 400);
    //     }
    // }

}

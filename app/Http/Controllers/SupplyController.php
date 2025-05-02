<?php

namespace App\Http\Controllers;

use App\Models\SupplyMutation;
use App\Models\SupplyPurchase;
use App\Models\SupplyPurchaseBatch;
use App\Models\SupplyStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SupplyController extends Controller
{
    public function getFeedPurchaseBatchDetail($batchId)
    {

        $supplyPurchases = SupplyPurchase::with([
            'supplyItem:id,code,name,unit,unit_conversion,conversion',
            'supplyStocks' // <- relasi baru nanti ditambahkan
        ])
        ->where('supply_purchase_batch_id', $batchId)
        ->get(['id', 'supply_purchase_batch_id', 'supply_id', 'quantity', 'price_per_unit']);

        $formatted = $supplyPurchases->map(function ($item) {
            $supplyItem = optional($item->supplyItem);
            $conversion = floatval($supplyItem->conversion) ?: 1;

            $quantity = floatval($item->quantity);
            $converted_quantity = $quantity / $conversion;

            // Summary dari semua FeedStock berdasarkan purchase
            $used = $item->supplyStocks->sum('quantity_used');
            $mutated = $item->supplyStocks->sum('quantity_mutated');
            $available = $item->supplyStocks->sum('available');

            return [
                'id' => $item->id,
                'code' => $supplyItem->code,
                'name' => $supplyItem->name,
                'quantity' => $quantity,
                'converted_quantity' => $converted_quantity,
                'qty' => $converted_quantity,
                'sisa' => $quantity - $used,
                'unit' => $supplyItem->unit,
                'conversion' => $conversion,
                'price_per_unit' => floatval($item->price_per_unit),
                'total' => config('xolution.ALLOW_ROUNDUP_PRICE')
                    ? floatval($quantity * $item->price_per_unit)
                    : intval($quantity * $item->price_per_unit),

                // Tambahan penggunaan dan mutasi
                'terpakai' => $used / $conversion,
                'mutated' => $mutated / $conversion,
                'available' => $available / $conversion,
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

            $supplyPurchase = SupplyPurchase::with('supplyItem')->findOrFail($id);
            $supplyItem = $supplyPurchase->supplyItem;
            $conversion = floatval($supplyItem->conversion) ?: 1;

            $supplyStock = SupplyStock::where('supply_purchase_id', $supplyPurchase->id)->first();

            if ($column === 'qty') {
                $usedQty = $supplyStock->quantity_used ?? 0;
                $mutatedQty = $supplyStock->quantity_mutated ?? 0;
                $sisa = $usedQty + $mutatedQty;
            
                if (($value * $conversion) < $sisa) {
                    return response()->json([
                        'message' => 'Jumlah baru lebih kecil dari jumlah yang sudah terpakai atau dimutasi',
                        'status' => 'error'
                    ], 422);
                }
            
                // Update FeedStock
                $supplyStock->update([
                    'quantity_in' => $value * $conversion,
                    'available' => ($value * $conversion) - $sisa,
                    'updated_by' => $user_id,
                ]);
            
                // Update FeedPurchase
                $supplyPurchase->update([
                    'quantity' => $value,
                    'updated_by' => $user_id,
                ]);
            } else {
                // Update price
                $supplyPurchase->update([
                    'price_per_unit' => $value,
                    'updated_by' => $user_id,
                ]);

                $supplyStock->update([
                    'amount' => $supplyPurchase->quantity * $value,
                    'updated_by' => $user_id,
                ]);
            }

            // Update sub_total dan sisa berdasarkan usage
            $subTotal = $supplyPurchase->quantity * $supplyPurchase->price_per_unit;
            $usedQty = $supplyStock->quantity_used ?? 0;
            $mutatedQty = $supplyStock->quantity_mutated ?? 0;
            $available = ($supplyPurchase->quantity * $conversion) - $usedQty - $mutatedQty;

            $supplyStock->update([
                'available' => $available,
                'amount' => $subTotal,
            ]);

            // Update Batch total summary
            $batch = SupplyPurchaseBatch::with('supplyPurchases.supplyItem')->findOrFail($supplyPurchase->supply_purchase_batch_id);

            $totalQty = $batch->supplyPurchases->sum(function ($purchase) {
                $conversion = floatval(optional($purchase->supplyItem)->conversion) ?: 1;
                return $purchase->quantity;
            });

            $totalHarga = $batch->supplyPurchases->sum(function ($purchase) {
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

    public function getSupplyByFarm(Request $request)
    {
        $validated = $request->validate([
            'farm_id' => 'required|uuid',
            'supply_id' => 'required|uuid',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $farmId = $validated['farm_id'];
        $supplyId = $validated['supply_id'];
        $startDate = $validated['start_date'] ? Carbon::parse($validated['start_date']) : null;
        $endDate = $validated['end_date'] ? Carbon::parse($validated['end_date']) : null;

        try {
            $stocks = SupplyStock::with([
                'supply',
                'supplyPurchase.batch',
                'supplyUsageDetails.supplyUsage.supply',
                'mutationDetails.mutation.toFarm',
                'incomingMutation.mutation.fromFarm',
            ])
                ->where('farm_id', $farmId)
                ->where('supply_id', $supplyId)
                ->get();

            $result = [];

            // Proses transaksi pembelian awal
            $purchaseStocks = $stocks->whereNotNull('supply_purchase_id')->groupBy('supply_purchase_id');
            foreach ($purchaseStocks as $purchaseId => $items) {
                $first = $items->first();
                $histories = [];
                $purchaseDate = optional($first->supplyPurchase->batch)->date;

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
                        'supply_purchase_info' => [
                            'supply_name' => $first->supply->name ?? '-',
                            'no_batch' => optional($first->supplyPurchase->batch)->invoice_number ?? '-',
                            'tanggal' => $purchaseDate->format('Y-m-d'),
                            'harga' => $first->supplyPurchase->price_per_unit ?? 0,
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
                $mutation = SupplyMutation::find($mutationId);
                $histories = [];

                if ($mutation && (!$startDate || $mutation->date >= $startDate) && (!$endDate || $mutation->date <= $endDate)) {
                    $histories[] = [
                        'tanggal' => $mutation->date->format('Y-m-d'),
                        'keterangan' => 'Mutasi dari ' . ($mutation->fromFarm->name ?? '-'),
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
                        'keterangan' => 'Mutasi dari ' . ($mutation->fromFarm->name ?? '-'),
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
            // DB::rollBack(); // Rollback on any other exception
            $line = $e->getLine();
            $file = $e->getFile();
            $message = $e->getMessage();

            // Human-readable error message
            $errorMessage = 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.';

            // Log detailed error for debugging
            Log::error(" Error: $message | Line: $line | File: $file");

            // Optionally: log stack trace
            Log::debug("Stack trace: " . $e->getTraceAsString());

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        } 
        // } catch (\Exception $e) {
        //     return response()->json([
        //         'status' => 'error',
        //         'message' => $e->getMessage(),
        //     ], 500);
        // }
    }

    protected function processUsageAndMutation($items, array $histories, ?Carbon $startDate, ?Carbon $endDate, &$runningStock): array
    {
        foreach ($items as $stock) {
            // Pemakaian
            foreach ($stock->supplyUsageDetails as $usageDetail) {
                $usageDate = $usageDetail->supplyUsage->usage_date;
                if ($usageDate && (!$startDate || $usageDate >= $startDate) && (!$endDate || $usageDate <= $endDate)) {
                    $histories[] = [
                        'tanggal' => $usageDate->format('Y-m-d'),
                        'keterangan' => 'Pemakaian Ternak ' . ($usageDetail->supplyUsage->farm->name ?? '-'),
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
                        'keterangan' => 'Mutasi ke ' . ($mutation->mutation->toFarm->name ?? '-'),
                        'masuk' => 0,
                        'keluar' => $mutation->quantity,
                    ];
                }
            }
        }
        return $histories;
    }
}

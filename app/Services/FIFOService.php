<?php

namespace App\Services;

use App\Models\Kandang;
use App\Models\Transaksi;
use App\Models\TransaksiDetail;
use App\Models\StokMutasi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\StokHistory;

class FIFOService
{
    /**
     * Reduce stock using FIFO method.
     *
     * @param array $validatedData
     * @return void
     *
     * @throws \Exception
     */
    public function reduceStock(array $validatedData)
    {
        DB::transaction(function () use ($validatedData) {
            $kandang = Kandang::find($validatedData['kandang_id']);

            // Create single Transaksi
            $transaksi = Transaksi::create([
                'farm_id' => $validatedData['farm_id'],
                'kandang_id' => $validatedData['kandang_id'],
                'total_qty' => 0,
                'terpakai' => 0,
                'sisa' => 0,
                'jenis' => 'Pemakaian',
                'tanggal' => $validatedData['tanggal'],
                'user_id' => Auth::id(),
                'kelompok_ternak_id' => $kandang->kelompok_ternak_id,
                'status' => 'Aktif',
                'harga' => 0,
                'sub_total' => 0,
            ]);

            $totalQty = 0;
            $totalTerpakai = 0;
            $totalSisa = 0;
            $totalHarga = 0;
            $totalSubTotal = 0;

            foreach ($validatedData['stock'] as $stockItem) {
                $itemId = $stockItem['item_id'];
                $quantityUsed = $stockItem['qty_used'];

                // Fetch stock entries ordered by oldest first (FIFO)
                $stockEntries = TransaksiDetail::whereHas('transaksi', function ($query) use ($validatedData) {
                    $query->where('farm_id', $validatedData['farm_id']);
                })
                    ->where('item_id', $itemId)
                    ->where('jenis', 'Pembelian')
                    ->where('sisa', '>', 0)
                    ->whereNotIn('jenis_barang', ['DOC'])
                    ->orderBy('tanggal', 'asc')
                    ->lockForUpdate() // Prevent race conditions
                    ->get();

                if ($stockEntries->isEmpty()) {
                    throw new \Exception('No stock available for item ID: ' . $itemId);
                }

                $remainingQuantity = $quantityUsed;

                foreach ($stockEntries as $stockEntry) {
                    if ($remainingQuantity <= 0) {
                        break;
                    }

                    $deductQuantity = min($stockEntry->sisa, $remainingQuantity);

                    // Update stock entry
                    $stockEntry->sisa -= $deductQuantity;
                    $stockEntry->terpakai += $deductQuantity;
                    $stockEntry->save();

                    // Create TransaksiDetail
                    $transaksiDetail = TransaksiDetail::create([
                        'transaksi_id' => $transaksi->id,
                        'parent_id' => $stockEntry->id,
                        'jenis' => 'Pemakaian',
                        'tanggal' => $validatedData['tanggal'],
                        'item_id' => $stockEntry->item_id,
                        'item_name' => $stockEntry->item_name,
                        'qty' => $deductQuantity,
                        'jenis_barang' => $stockEntry->jenis_barang,
                        'kandang_id' => $validatedData['kandang_id'],
                        'harga' => $stockEntry->harga,
                        'sisa' => $stockEntry->sisa,
                        'terpakai' => $stockEntry->terpakai,
                        'satuan_besar' => $stockEntry->items->satuan_besar,
                        'satuan_kecil' => $stockEntry->items->satuan_kecil,
                        'konversi' => $stockEntry->items->konversi,
                        'sub_total' => ($deductQuantity / $stockEntry->items->konversi) * $stockEntry->harga,
                        'kelompok_ternak_id' => $kandang->kelompok_ternak_id,
                        'status' => 'Aktif',
                        'user_id' => Auth::id(),
                    ]);

                    // Create StokMutasi
                    StokHistory::create([
                        'transaksi_id' => $transaksi->id,
                        'item_id' => $stockEntry->item_id,
                        'item_nama' => $stockEntry->item_name,
                        'jenis_barang' => $stockEntry->jenis_barang,
                        'rekanan_id' => $stockEntry->rekanan_id,
                        'farm_id' => $validatedData['farm_id'],
                        'kandang_id' => $validatedData['kandang_id'],
                        'harga' => $stockEntry->harga,
                        'stok_awal' => $stockEntry->qty,
                        'stok_akhir' => $stockEntry->qty - $deductQuantity,
                        'qty' => $deductQuantity,
                        'tanggal' => $validatedData['tanggal'],
                        'user_id' => Auth::id(),
                        'jenis' => 'Pemakaian',
                        'status' => 'Aktif',
                    ]);

                    // Update remaining quantity to deduct
                    $remainingQuantity -= $deductQuantity;

                    // Update totals
                    $totalQty += $deductQuantity;
                    $totalTerpakai += $deductQuantity;
                    $totalSisa += $stockEntry->sisa;
                }

                if ($remainingQuantity > 0) {
                    throw new \Exception('Insufficient stock for item ID: ' . $itemId);
                }
            }

            // Update total harga and sub total
            $totalHarga = $transaksi->transaksiDetail()->sum('harga');
            $totalSubTotal = $transaksi->transaksiDetail()->sum('sub_total');

            // Update Transaksi totals after all details are created
            $transaksi->update([
                'total_qty' => $totalQty,
                'terpakai' => $totalTerpakai,
                'sisa' => $totalSisa,
                'harga' => $totalHarga,
                'sub_total' => $totalSubTotal,
            ]);
        });
    }

    /**
     * Reverse the stock reduction process.
     *
     * @param int $transaksiId
     * @return void
     *
     * @throws \Exception
     */
    public function reverseStockReduction($request)
    {
        DB::transaction(function () use ($request) {
            $transaksi = Transaksi::findOrFail($request->id);

            if ($transaksi->jenis !== $request->jenis) {
                throw new \Exception('This transaction is not a stock reduction.');
            }

            $transaksiDetails = $transaksi->transaksiDetail;

            foreach ($transaksiDetails as $detail) {
                // Find the original stock entry
                $originalStockEntry = TransaksiDetail::findOrFail($detail->parent_id);

                // Reverse the stock changes
                $originalStockEntry->sisa += $detail->qty;
                $originalStockEntry->terpakai -= $detail->qty;
                $originalStockEntry->save();

                // Delete the TransaksiDetail
                $detail->delete();
            }

            // Delete the StokHistory entries related to this transaction
            StokHistory::where('transaksi_id', $request->id)->delete();

            // Delete the main Transaksi
            $transaksi->delete();
        });
    }
}

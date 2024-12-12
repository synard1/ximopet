<?php

namespace App\Services;

use App\Models\CurrentStock;
use App\Models\CurrentTernak;
use App\Models\Kandang;
use App\Models\TransaksiHarian;
use App\Models\TransaksiHarianDetail;
use App\Models\TransaksiBeli;
use App\Models\TransaksiBeliDetail;
use App\Models\StokMutasi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\StockHistory;

use App\Models\Ternak;
use App\Services\TernakService;
use App\Models\TernakAfkir;
use App\Models\KematianTernak;
use App\Models\TernakJual;
use App\Models\TransaksiJual;
use App\Models\TransaksiJualDetail;
use App\Models\TernakHistory;

use Carbon\Carbon;

class FIFOService
{
    protected $ternakService;

    public function __construct(TernakService $ternakService)
    {
        $this->ternakService = $ternakService;
    }

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
            $ternak = CurrentTernak::where('farm_id',$validatedData['farm_id'])->where('kandang_id',$validatedData['kandang_id'])->where('status','Aktif')->first();

            // Create single Transaksi
            $transaksi = TransaksiHarian::create([
                'tanggal' => $validatedData['tanggal'],
                'kelompok_ternak_id' => $ternak->kelompok_ternak_id,
                'farm_id' => $validatedData['farm_id'],
                'kandang_id' => $validatedData['kandang_id'],
                'created_by' => Auth::id(),
            ]);

            // dd($ternak);

            // Calculate the age of the livestock using Carbon
            $tanggalMasuk = Carbon::parse($ternak->kelompokTernaks->start_date);
            $tanggalJual = Carbon::parse($transaksi->tanggal);
            $umur = $tanggalMasuk->diffInDays($tanggalJual);

            // Update Ternak History
            TernakHistory::updateOrCreate(
                [
                    'kelompok_ternak_id' => $transaksi->kelompok_ternak_id,
                    'tanggal' => $transaksi->tanggal,
                ],
                ['stok_awal' => $ternak->quantity,
                'umur' => $umur,
                'status' => 'OK',
                ]
            );

            if($validatedData['ternak_mati']){
                $dataTernakMati = $this->ternakService->ternakMati($validatedData, $transaksi);
             }
             if($validatedData['ternak_afkir']){
                $dataTernakAfkir = $this->ternakService->ternakAfkir($validatedData, $transaksi);
             }
             if($validatedData['ternak_jual']){
                $dataTernakJual = $this->ternakService->ternakJual($validatedData, $transaksi);
             }

            $totalQty = 0;
            $totalTerpakai = 0;
            $totalSisa = 0;
            $totalHarga = 0;
            $totalSubTotal = 0;

            foreach ($validatedData['stock'] as $stockItem) {
                $itemId = $stockItem['item_id'];
                $quantityUsed = $stockItem['qty_used'];

                // Fetch stock entries ordered by oldest first (FIFO)
                $stockEntries = TransaksiBeliDetail::whereHas('transaksiBeli', function ($query) use ($validatedData) {
                        $query->where('farm_id', $validatedData['farm_id']);
                    })
                    ->where('item_id', $itemId)
                    ->where('jenis', 'Pembelian')
                    ->where('sisa', '>', 0)
                    ->whereNotIn('jenis_barang', ['DOC'])
                    ->orderBy('tanggal', 'asc')
                    ->lockForUpdate() // Prevent race conditions
                    ->get();

                    // dd($stockEntries);
                
                    $currentStock = CurrentStock::whereHas('inventoryLocation', function ($query) use ($validatedData) {
                        $query->where('farm_id', $validatedData['farm_id']);
                    })
                    // ->where('farm_id', $validatedData['farm_id'])
                    ->where('item_id', $itemId)
                    ->first();

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

                    // Update Stok Mutasi
                    // $stokMutasi = StokMutasi::where('transaksi_id', $stockEntry->transaksi_id)->first();

                    // $stokMutasi->stok_masuk = $stockEntry->sisa;
                    // $stokMutasi->stok_akhir = $stockEntry->sisa;
                    // $stokMutasi->save();

                    // dd($transaksi->id);
                    // Create TransaksiDetail
                    $transaksiDetail = TransaksiHarianDetail::create([
                        'transaksi_id' => $transaksi->id,
                        'parent_id' => $stockEntry->id,
                        'type' => 'Pemakaian',
                        // 'tanggal' => $validatedData['tanggal'],
                        'item_id' => $stockEntry->item_id,
                        // 'item_name' => $stockEntry->item_name,
                        'quantity' => $deductQuantity,
                        // 'jenis_barang' => $stockEntry->jenis_barang,
                        // 'kandang_id' => $validatedData['kandang_id'],
                        'total_berat' => $stockEntry->harga ?? '0',
                        'harga' => $stockEntry->harga,
                        // 'sisa' => $stockEntry->sisa,
                        // 'terpakai' => $stockEntry->terpakai,
                        // 'satuan_besar' => $stockEntry->items->satuan_besar,
                        // 'satuan_kecil' => $stockEntry->items->satuan_kecil,
                        // 'konversi' => $stockEntry->items->konversi,
                        // 'sub_total' => ($deductQuantity / $stockEntry->items->konversi) * $stockEntry->harga,
                        // 'kelompok_ternak_id' => $kandang->kelompok_ternak_id,
                        // 'status' => 'Aktif',
                        'user_id' => Auth::id(),
                    ]);

                    // Create StokMutasi
                    StockHistory::create([
                        'tanggal' => $validatedData['tanggal'],
                        'transaksi_id' => $transaksi->id,
                        'stock_id' => $currentStock->id,
                        'item_id' => $stockEntry->item_id,
                        'location_id' => $currentStock->location_id,
                        'transaksi_id' => $stockEntry->transaksi_id,
                        'parent_id' => $stockEntry->id ?? null,
                        'jenis' => 'Pemakaian',
                        'batch_number' => $stockEntry->batch_number,
                        'expiry_date' => $stockEntry->expiry_date,
                        'quantity' => $deductQuantity,
                        'available_quantity' => $stockEntry->sisa,
                        'hpp' => $stockEntry->harga,
                        'status' => 'Aktif',
                        'created_by' => Auth::id(),
                    ]);

                    // Update CurrentStock
                    $currentStock = CurrentStock::where('item_id', $stockEntry->item_id)
                    ->where('location_id',$currentStock->location_id)
                    ->first();

                    if ($currentStock) {
                        // Update existing stock
                        $currentStock->quantity -= $deductQuantity;
                        $currentStock->available_quantity -= $deductQuantity;
                        $currentStock->save();
                    }

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
            // $totalHarga = $transaksi->details()->sum('harga');
            // $totalSubTotal = $transaksi->details()->sum('sub_total');

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
            $transaksi = TransaksiHarian::findOrFail($request->id);
            $transaksiDetails = $transaksi->details;




            // dd($transaksi->details['item_id']);

            // if ($transaksi->jenis !== $request->jenis) {
            //     throw new \Exception('This transaction is not a stock reduction.');
            // }


            foreach ($transaksiDetails as $detail) {
                $currentStock = CurrentStock::whereHas('inventoryLocation', function ($query) use($transaksi){
                    $query->where('farm_id', $transaksi->farm_id);
                })
                ->where('item_id', $detail->item_id)
                ->first();

                // dd($currentStock);

                // Find the original stock entry
                $originalStockEntry = TransaksiBeliDetail::findOrFail($detail->parent_id);

                // Reverse the stock changes
                $originalStockEntry->sisa += $detail->quantity;
                $originalStockEntry->terpakai -= $detail->quantity;
                $originalStockEntry->save();

                // Update CurrentStock
                // $currentStock = CurrentStock::where('item_id', $detail->item_id)
                // ->where('location_id',$currentStock->location_id)
                // ->first();

                if ($currentStock) {
                    // Update existing stock
                    $currentStock->quantity += $detail->quantity;
                    $currentStock->available_quantity += $detail->quantity;
                    $currentStock->save();
                }

                // Delete the TransaksiDetail
                $detail->delete();
            }

            //Check Ternak Afkir, Ternak Mati, Ternak Jual, Transaksi Jual, Transaksi Jual Detail
            $ternak = Ternak::where('id', $transaksi->kelompok_ternak_id)->first();
            $currentTernak = CurrentTernak::where('kelompok_ternak_id', $ternak->id)->first();
            $ternakAfkir = TernakAfkir::where('transaksi_id', $request->id)->where('tipe_transaksi','Harian')->first();
            $ternakMati = KematianTernak::where('transaksi_id', $request->id)->where('tipe_transaksi','Harian')->first();
            $ternakJual = TernakJual::where('transaksi_id', $request->id)->where('tipe_transaksi','Harian')->first();
            if($ternakJual){
                $transaksiJual = TransaksiJual::where('transaksi_id', $request->id)->where('tipe_transaksi','Harian')->first();
            }

            // dd($ternakJual->quantity);

            // Return Ternak Qty
            if($ternakAfkir){
                // $ternak->populasi_awal += $ternakAfkir->jumlah;
                $currentTernak->quantity += $ternakAfkir->jumlah;
                // $ternak->save();
                $currentTernak->save();
                $ternakAfkir->delete();

            }

            if($ternakMati){
                // $ternak->populasi_awal += $ternakMati->jumlah;
                $currentTernak->quantity += $ternakMati->quantity;
                // $ternak->save();
                $currentTernak->save();
                $ternakMati->delete();

            }

            if($ternakJual){
                // $ternak->populasi_awal += $ternakJual->jumlah;
                $currentTernak->quantity += $ternakJual->quantity;
                // $ternak->save();
                $currentTernak->save();
                $transaksiJual->details()->delete();
                $transaksiJual->delete();
                $ternakJual->delete();
            }

            // Delete the StokHistory entries related to this transaction
            StockHistory::where('transaksi_id', $request->id)->delete();

            //Delete Sub Data


            // Delete the main Transaksi
            $transaksi->delete();
        });
    }
}

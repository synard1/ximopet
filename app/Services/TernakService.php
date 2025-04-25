<?php

namespace App\Services;

use App\Models\CurrentTernak;
use App\Models\Kandang;
use App\Models\KelompokTernak;
use App\Models\KematianTernak;
use App\Models\Transaksi;
use App\Models\TransaksiDetail;
use App\Models\TransaksiJual;
use App\Models\TransaksiJualDetail;
use App\Models\StokMutasi;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection; // If using Collection methods explicitly
use Illuminate\Support\Facades\Log;

use App\Models\StandarBobot;
use App\Models\StokHistory;
use App\Models\TransaksiBeli;
use App\Models\TernakAfkir;
use App\Models\TernakJual;
use App\Models\TernakHistory;

use Carbon\Carbon;

class TernakService
{
    /**
     * Reduce stock using FIFO method.
     *
     * @param array $validatedData
     * @return void
     *
     * @throws \Exception
     */
    // public function ternakMati(array $validatedData)
    // {
    //     dd($validatedData);
    //     DB::transaction(function () use ($validatedData) {
    //         $kandang = Kandang::find($validatedData['kandang_id']);

    //         // Create single Transaksi
    //         $transaksi = Transaksi::create([
    //             'farm_id' => $validatedData['farm_id'],
    //             'kandang_id' => $validatedData['kandang_id'],
    //             'total_qty' => 0,
    //             'terpakai' => 0,
    //             'sisa' => 0,
    //             'jenis' => 'Pemakaian',
    //             'tanggal' => $validatedData['tanggal'],
    //             'user_id' => Auth::id(),
    //             'kelompok_ternak_id' => $kandang->kelompok_ternak_id,
    //             'status' => 'Aktif',
    //             'harga' => 0,
    //             'sub_total' => 0,
    //         ]);

    //         $totalQty = 0;
    //         $totalTerpakai = 0;
    //         $totalSisa = 0;
    //         $totalHarga = 0;
    //         $totalSubTotal = 0;

    //         foreach ($validatedData['stock'] as $stockItem) {
    //             $itemId = $stockItem['item_id'];
    //             $quantityUsed = $stockItem['qty_used'];

    //             // Fetch stock entries ordered by oldest first (FIFO)
    //             $stockEntries = TransaksiDetail::whereHas('transaksi', function ($query) use ($validatedData) {
    //                     $query->where('farm_id', $validatedData['farm_id']);
    //                 })
    //                 ->where('item_id', $itemId)
    //                 ->where('jenis', 'Pembelian')
    //                 ->where('sisa', '>', 0)
    //                 ->whereNotIn('jenis_barang', ['DOC'])
    //                 ->orderBy('tanggal', 'asc')
    //                 ->lockForUpdate() // Prevent race conditions
    //                 ->get();

    //             if ($stockEntries->isEmpty()) {
    //                 throw new \Exception('No stock available for item ID: ' . $itemId);
    //             }

    //             $remainingQuantity = $quantityUsed;

    //             foreach ($stockEntries as $stockEntry) {
    //                 if ($remainingQuantity <= 0) {
    //                     break;
    //                 }

    //                 $deductQuantity = min($stockEntry->sisa, $remainingQuantity);

    //                 // Update stock entry
    //                 $stockEntry->sisa -= $deductQuantity;
    //                 $stockEntry->terpakai += $deductQuantity;
    //                 $stockEntry->save();

    //                 // Update Stok Mutasi
    //                 $stokMutasi = StokMutasi::where('transaksi_id', $stockEntry->transaksi_id)->first();

    //                 $stokMutasi->stok_masuk = $stockEntry->sisa;
    //                 $stokMutasi->stok_akhir = $stockEntry->sisa;
    //                 $stokMutasi->save();

    //                 // Create TransaksiDetail
    //                 $transaksiDetail = TransaksiDetail::create([
    //                     'transaksi_id' => $transaksi->id,
    //                     'parent_id' => $stockEntry->id,
    //                     'jenis' => 'Pemakaian',
    //                     'tanggal' => $validatedData['tanggal'],
    //                     'item_id' => $stockEntry->item_id,
    //                     'item_name' => $stockEntry->item_name,
    //                     'qty' => $deductQuantity,
    //                     'jenis_barang' => $stockEntry->jenis_barang,
    //                     'kandang_id' => $validatedData['kandang_id'],
    //                     'harga' => $stockEntry->harga,
    //                     'sisa' => $stockEntry->sisa,
    //                     'terpakai' => $stockEntry->terpakai,
    //                     'satuan_besar' => $stockEntry->items->satuan_besar,
    //                     'satuan_kecil' => $stockEntry->items->satuan_kecil,
    //                     'konversi' => $stockEntry->items->konversi,
    //                     'sub_total' => ($deductQuantity / $stockEntry->items->konversi) * $stockEntry->harga,
    //                     'kelompok_ternak_id' => $kandang->kelompok_ternak_id,
    //                     'status' => 'Aktif',
    //                     'user_id' => Auth::id(),
    //                 ]);

    //                 // Create StokMutasi
    //                 StokHistory::create([
    //                     'transaksi_id' => $transaksi->id,
    //                     'parent_id' => $stokMutasi->id,
    //                     'item_id' => $stockEntry->item_id,
    //                     'item_name' => $stockEntry->item_name,
    //                     'satuan' => $transaksiDetail->satuan_besar,
    //                     'jenis_barang' => $stockEntry->jenis_barang,
    //                     'kadaluarsa' => $stockEntry->kadaluarsa ?? $transaksiDetail->tanggal->addMonths(18),
    //                     'perusahaan_nama' => $stockEntry->transaksi->rekanans->nama,
    //                     'hpp' => $transaksiDetail->harga,
    //                     'farm_id' => $validatedData['farm_id'],
    //                     'kandang_id' => $validatedData['kandang_id'],
    //                     'harga' => $stockEntry->harga,
    //                     'stok_awal' => $stockEntry->qty,
    //                     'stok_akhir' => $stockEntry->qty - $deductQuantity,
    //                     'stok_masuk' => 0,
    //                     'stok_keluar' => $deductQuantity,
    //                     'tanggal' => $validatedData['tanggal'],
    //                     'user_id' => Auth::id(),
    //                     'jenis' => 'Pemakaian',
    //                     'status' => 'Aktif',
    //                 ]);

    //                 // Update remaining quantity to deduct
    //                 $remainingQuantity -= $deductQuantity;

    //                 // Update totals
    //                 $totalQty += $deductQuantity;
    //                 $totalTerpakai += $deductQuantity;
    //                 $totalSisa += $stockEntry->sisa;
    //             }

    //             if ($remainingQuantity > 0) {
    //                 throw new \Exception('Insufficient stock for item ID: ' . $itemId);
    //             }
    //         }

    //         // Update total harga and sub total
    //         $totalHarga = $transaksi->transaksiDetail()->sum('harga');
    //         $totalSubTotal = $transaksi->transaksiDetail()->sum('sub_total');

    //         // Update Transaksi totals after all details are created
    //         $transaksi->update([
    //             'total_qty' => $totalQty,
    //             'terpakai' => $totalTerpakai,
    //             'sisa' => $totalSisa,
    //             'harga' => $totalHarga,
    //             'sub_total' => $totalSubTotal,
    //         ]);
    //     });
    // }

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

    public function ternakMati(array $validatedData, $transaksi)
    {
        $kelompokTernak =  $this->kelompokTernak($validatedData);
        // dd($test);

        DB::beginTransaction();

        try {
            $kelompokTernak =  $this->kelompokTernak($validatedData);
            $latestData = $this->getLatestKematianTernak($kelompokTernak);
            $currentTernak = $this->currentTernak($kelompokTernak);

            // Calculate the age of the livestock using Carbon
            $tanggalMasuk = Carbon::parse($kelompokTernak->tanggal_masuk);
            $tanggalJual = Carbon::parse($validatedData['tanggal']);
            $umur = $tanggalMasuk->diffInDays($tanggalJual);

            //Validasi Ternak Afkir Melebihi Stok Ternak
            if ($validatedData['ternak_mati'] > $currentTernak->quantity) {
                // return response()->json(['error' => 'Jumlah ternak yang mati melebihi stok ternak.'], 400);

                throw new \InvalidArgumentException('Jumlah ternak yang mati melebihi stok ternak.');

                // throw new \Exception('Jumlah ternak yang mati melebihi stok ternak.');
            }

            $ternakMati = new KematianTernak();
            $ternakMati->kelompok_ternak_id = $kelompokTernak->id;
            $ternakMati->transaksi_id = $transaksi->id;
            $ternakMati->tipe_transaksi = 'Harian';
            $ternakMati->tanggal = $validatedData['tanggal'];
            $ternakMati->farm_id = $validatedData['farm_id'];
            $ternakMati->kandang_id = $validatedData['kandang_id'];
            $ternakMati->stok_awal = $latestData['stok_akhir'];
            $ternakMati->quantity = $validatedData['ternak_mati'];
            $ternakMati->stok_akhir = $latestData['stok_akhir'] - $ternakMati->quantity ;
            $ternakMati->total_berat = 0;
            $ternakMati->penyebab = $validatedData['penyebab'] ?? 'Belum Ditentukan';
            $ternakMati->keterangan = $validatedData['keterangan'] ?? null;
            $ternakMati->created_by = auth()->user()->id;
            $ternakMati->umur = $umur;

            $ternakMati->save();

            // Update CurrentTernak
            $currentTernak->quantity -= $ternakMati->quantity;
            $currentTernak->save();

            // Update Ternak History
            $ternakHistory = TernakHistory::where('kelompok_ternak_id',$kelompokTernak->id)->where('tanggal',$validatedData['tanggal'])->first();
            $ternakHistory->ternak_mati = $validatedData['ternak_mati'];
            $ternakHistory->save();

            

            // $this->updateStok($this->selectedFarm, $this->selectedKandang, $this->quantity, 'kurang');

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Data ternak mati gagal ditambahkan. '. $e->getMessage());
        }
    }

    public function ternakAfkir(array $validatedData, $transaksi)
    {
        DB::beginTransaction();

        try {
            $kelompokTernak =  $this->kelompokTernak($validatedData);
            $latestData = $this->getLatestKematianTernak($kelompokTernak);
            $currentTernak = $this->currentTernak($kelompokTernak);
            // Calculate the age of the livestock using Carbon
            $tanggalMasuk = Carbon::parse($kelompokTernak->tanggal_masuk);
            $tanggalJual = Carbon::parse($validatedData['tanggal']);
            $umur = $tanggalMasuk->diffInDays($tanggalJual);

            //Validasi Ternak Afkir Melebihi Stok Ternak
            if ($validatedData['ternak_afkir'] > $currentTernak->quantity) {
                // return response()->json(['error' => 'Jumlah ternak yang afkir melebihi stok ternak.'], 400);

                throw new \Exception('Jumlah ternak yang afkir melebihi stok ternak.');
            }

            $ternakAfkir = new TernakAfkir();
            $ternakAfkir->kelompok_ternak_id = $kelompokTernak->id;
            $ternakAfkir->transaksi_id = $transaksi->id;
            $ternakAfkir->tipe_transaksi = 'Harian';
            $ternakAfkir->tanggal = $validatedData['tanggal'];
            $ternakAfkir->jumlah = $validatedData['ternak_afkir'];
            $ternakAfkir->total_berat = 0;
            $ternakAfkir->kondisi = 'Belum Ditentukan';
            $ternakAfkir->tindakan = 'Belum Ditentukan';
            $ternakAfkir->status = 'Data Belum Lengkap';
            $ternakAfkir->created_by = auth()->user()->id;
            $ternakAfkir->umur = $umur;

            $ternakAfkir->save();

            // Update CurrentTernak
            $currentTernak->quantity -= $ternakAfkir->jumlah;
            $currentTernak->save();

            // Update Ternak History
            $ternakHistory = TernakHistory::where('kelompok_ternak_id',$kelompokTernak->id)->where('tanggal',$validatedData['tanggal'])->first();
            $ternakHistory->ternak_afkir = $validatedData['ternak_afkir'];
            $ternakHistory->save();

            // Update stok
            // $this->updateStok($this->selectedFarm, $this->selectedKandang, $this->quantity, 'kurang');

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Data ternak mati gagal ditambahkan. '. $e->getMessage());
        }
    }

    public function ternakJualBaru(array $validatedData, $transaksi)
    {
        DB::beginTransaction();

        // dd($transaksi);

        try {
            $kelompokTernak = KelompokTernak::where('id', $validatedData['ternakId'])->firstOrFail();
            $currentTernak = CurrentTernak::where('kelompok_ternak_id', $validatedData['ternakId'])->first();

            //Validasi Penjualan Melebihi Stok Ternak, check validasi negatif
            if (config('xolution.ALLOW_NEGATIF_SELLING') == false && $validatedData['sales_quantity'] > $currentTernak->quantity) {
                // return response()->json(['error' => 'Stok ternak yang tersedia hanya '. $currentTernak->quantity.'Ekor.'], 400);

                throw new \Exception('Stok ternak yang tersedia hanya '. $currentTernak->quantity.'Ekor.');
            }

            // Calculate the age of the livestock using Carbon
            $tanggalMasuk = Carbon::parse($kelompokTernak->tanggal_masuk);
            $tanggalJual = Carbon::parse($validatedData['date']);
            $umur = $tanggalMasuk->diffInDays($tanggalJual);

            $transaksiBeli = TransaksiBeli::where('kelompok_ternak_id', $kelompokTernak->id)->first();

            // dd($validatedData['ternak_jual']);

            $ternakJual = new TernakJual();
            $ternakJual->kelompok_ternak_id = $kelompokTernak->id;
            $ternakJual->transaksi_id = $transaksi->id;
            $ternakJual->tipe_transaksi = 'Harian';
            $ternakJual->tanggal = $validatedData['date'];
            $ternakJual->quantity = $validatedData['sales_quantity'];
            $ternakJual->total_berat = $validatedData['sales_weight'] ?? 0;
            $ternakJual->umur = $umur;
            $ternakJual->status = 'Data Belum Lengkap';
            $ternakJual->created_by = auth()->user()->id;
            $ternakJual->save();

            // Create TransaksiJual record
            $transaksiJual = new TransaksiJual();
            $transaksiJual->faktur = $validatedData['faktur'] ?? null;
            $transaksiJual->transaksi_id = $transaksi->id;
            $transaksiJual->tipe_transaksi = 'Harian';
            $transaksiJual->tanggal = $validatedData['date'];
            $transaksiJual->transaksi_beli_id = $transaksiBeli->id;
            $transaksiJual->kelompok_ternak_id = $kelompokTernak->id;
            $transaksiJual->ternak_jual_id = $ternakJual->id;
            $transaksiJual->jumlah = $validatedData['sales_quantity'];
            $transaksiJual->harga = $validatedData['total_harga'] ?? 0;
            $transaksiJual->status = 'Data Belum Lengkap';
            $transaksiJual->created_by = auth()->user()->id;
            $transaksiJual->save();

            // Create TransaksiJualDetail record
            $transaksiJualDetail = new TransaksiJualDetail();
            $transaksiJualDetail->transaksi_jual_id = $transaksiJual->id;
            $transaksiJualDetail->rekanan_id = $validatedData['rekanan_id'] ?? null ;
            $transaksiJualDetail->farm_id = $kelompokTernak->farm_id;
            $transaksiJualDetail->kandang_id = $kelompokTernak->kandang_id;
            $transaksiJualDetail->harga_beli = $transaksiBeli->harga;
            $transaksiJualDetail->harga_jual = $validatedData['sales_price'] ?? 0;
            $transaksiJualDetail->qty = $validatedData['sales_quantity'];
            $transaksiJualDetail->berat = $validatedData['sales_weight'] ?? 0;
            $transaksiJualDetail->umur = $umur;
            $transaksiJualDetail->status = 'Data Belum Lengkap';
            $transaksiJualDetail->created_by = auth()->user()->id;
            $transaksiJualDetail->save();

            // Update CurrentTernak
            $currentTernak->quantity -= $ternakJual->quantity;
            $currentTernak->save();

            // Update Ternak Jual
            $ternakJual->transaksi_jual_id = $transaksiJual->id;
            $ternakJual->save();

            $referer = request()->headers->get('referer');

            if (strpos($referer, 'transaksi/harian') !== false) {
                $validatedData['tanggal_harian'] = $validatedData['tanggal'];
            }

            // Update Ternak History
            if (isset($validatedData['date'])) {

            }else{
                $ternakHistory = TernakHistory::where('kelompok_ternak_id',$kelompokTernak->id)->where('tanggal',$validatedData['date'])->first();
                $ternakHistory->ternak_jual = $validatedData['sales_quantity'];
                $ternakHistory->save();
            }

            DB::commit();
            return $transaksiJual;

        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Gagal menambahkan data penjualan ternak: '. $e->getMessage());
        }
    }

    public function ternakJual(array $validatedData, $transaksi)
    {
        DB::beginTransaction();

        try {
            $kelompokTernak =  $this->kelompokTernak($validatedData);
            $latestData = $this->getLatestKematianTernak($kelompokTernak);
            $currentTernak = $this->currentTernak($kelompokTernak);

            //Validasi Penjualan Melebihi Stok Ternak, check validasi negatif
            if (config('xolution.ALLOW_NEGATIF_SELLING') == false && $validatedData['ternak_jual'] > $currentTernak->quantity) {
                // return response()->json(['error' => 'Stok ternak yang tersedia hanya '. $currentTernak->quantity.'Ekor.'], 400);

                throw new \Exception('Stok ternak yang tersedia hanya '. $currentTernak->quantity.'Ekor.');
            }

            // Calculate the age of the livestock using Carbon
            $tanggalMasuk = Carbon::parse($kelompokTernak->tanggal_masuk);
            $tanggalJual = Carbon::parse($validatedData['tanggal']);
            $umur = $tanggalMasuk->diffInDays($tanggalJual);

            $transaksiBeli = TransaksiBeli::where('kelompok_ternak_id', $kelompokTernak->id)->first();

            // dd($validatedData['ternak_jual']);

            $ternakJual = new TernakJual();
            $ternakJual->kelompok_ternak_id = $kelompokTernak->id;
            $ternakJual->transaksi_id = $transaksi->id;
            $ternakJual->tipe_transaksi = 'Harian';
            $ternakJual->tanggal = $validatedData['tanggal'];
            $ternakJual->quantity = $validatedData['ternak_jual'];
            $ternakJual->total_berat = $validatedData['total_berat'] ?? 0;
            $ternakJual->umur = $umur;
            $ternakJual->status = 'Data Belum Lengkap';
            $ternakJual->created_by = auth()->user()->id;
            $ternakJual->save();

            // Create TransaksiJual record
            $transaksiJual = new TransaksiJual();
            $transaksiJual->faktur = $validatedData['faktur'] ?? null;
            $transaksiJual->transaksi_id = $transaksi->id;
            $transaksiJual->tipe_transaksi = 'Harian';
            $transaksiJual->tanggal = $validatedData['tanggal'];
            $transaksiJual->transaksi_beli_id = $transaksiBeli->id;
            $transaksiJual->kelompok_ternak_id = $kelompokTernak->id;
            $transaksiJual->ternak_jual_id = $ternakJual->id;
            $transaksiJual->jumlah = $validatedData['ternak_jual'];
            $transaksiJual->harga = $validatedData['total_harga'] ?? 0;
            $transaksiJual->status = 'Data Belum Lengkap';
            $transaksiJual->created_by = auth()->user()->id;
            $transaksiJual->save();

            // Create TransaksiJualDetail record
            $transaksiJualDetail = new TransaksiJualDetail();
            $transaksiJualDetail->transaksi_jual_id = $transaksiJual->id;
            $transaksiJualDetail->rekanan_id = $validatedData['rekanan_id'] ?? null ;
            $transaksiJualDetail->farm_id = $validatedData['farm_id'];
            $transaksiJualDetail->kandang_id = $validatedData['kandang_id'];
            $transaksiJualDetail->harga_beli = $transaksiBeli->harga;
            $transaksiJualDetail->harga_jual = $validatedData['harga'] ?? 0;
            $transaksiJualDetail->qty = $validatedData['ternak_jual'];
            $transaksiJualDetail->berat = $validatedData['total_berat'] ?? 0;
            $transaksiJualDetail->umur = $umur;
            $transaksiJualDetail->status = 'Data Belum Lengkap';
            $transaksiJualDetail->created_by = auth()->user()->id;
            $transaksiJualDetail->save();

            // Update CurrentTernak
            $currentTernak->quantity -= $ternakJual->quantity;
            $currentTernak->save();

            // Update Ternak Jual
            $ternakJual->transaksi_jual_id = $transaksiJual->id;
            $ternakJual->save();

            // Update stok
            // $this->updateStok($this->selectedFarm, $this->selectedKandang, $this->quantity, 'kurang');

            // dd($validatedData);

            $referer = request()->headers->get('referer');

            // dd($referer);

            if (strpos($referer, 'transaksi/harian') !== false) {
                $validatedData['tanggal_harian'] = $validatedData['tanggal'];
            }


            // Update Ternak History
            if (isset($validatedData['tanggal_harian'])) {

            }else{
                $ternakHistory = TernakHistory::where('kelompok_ternak_id',$kelompokTernak->id)->where('tanggal',$validatedData['tanggal'])->first();
                $ternakHistory->ternak_jual = $validatedData['ternak_jual'];
                $ternakHistory->save();
            }

            DB::commit();
            return $transaksiJual;

        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Gagal menambahkan data penjualan ternak: '. $e->getMessage());
        }
    }

    private function kelompokTernak($data){

        $data = TransaksiBeli::where('kandang_id', $data['kandang_id'])
            ->where('farm_id',$data['farm_id'])
            ->where('jenis', 'DOC')
            ->where('status', 'Aktif')
            ->first();
        
        $kelompokTernak = KelompokTernak::where('id', $data->kelompok_ternak_id)->firstOrFail();
        
        return $kelompokTernak;

    }

    private function currentTernak($data){

        $data = CurrentTernak::where('kelompok_ternak_id', $data['id'])
            ->first();
        
        return $data;

    }

    private function getLatestKematianTernak($data){

        $latestKematianTernak = KematianTernak::where('kelompok_ternak_id', $data['id'])
        ->latest('tanggal')
        ->first();

        if (!$latestKematianTernak) {
            // If no KematianTernak record exists, return the initial data from KelompokTernak
            $kelompokTernak = KelompokTernak::findOrFail($data['id']);

            return [
                'stok_awal' => $kelompokTernak->populasi_awal,
                'stok_akhir' => $kelompokTernak->populasi_awal
            ];
        }

        return [
            'stok_awal' => $latestKematianTernak->stok_awal,
            'stok_akhir' => $latestKematianTernak->stok_akhir
        ];

    }

    public function updateStandarTernak($data){
        try {
            DB::beginTransaction();

            $standarBobot = StandarBobot::where('id', $data['standar_bobot_id'])->first();
            $kelompokTernak = KelompokTernak::findOrFail($data['ternak_id']);

            // Prepare the new data structure
            $newData = [
                'standar_bobot' => [
                    'id' => $standarBobot->id,
                    'nama' => $standarBobot->strain ?? '',
                    'keterangan' => $standarBobot->keterangan ?? '',
                    'data' => $standarBobot->standar_data,
                ]
            ];

            // Get current data or initialize empty array
            $currentData = $kelompokTernak->data ?? [];

            // Remove any existing standar_bobot entries
            $filteredData = array_filter($currentData, function($item) {
                return !isset($item['standar_bobot']);
            });

            // Add the new standar_bobot data
            $filteredData[] = $newData;

            // Update the kelompok_ternak with new data
            $kelompokTernak->data = array_values($filteredData);
            $kelompokTernak->save();

            DB::commit();
            Log::info("Updated Standar Bobot data for KelompokTernak ID: {$kelompokTernak->id} with new Standar Bobot ID: {$standarBobot->id}");
            
            return response()->json(['message' => 'Kelompok Ternak updated successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to update Standar Bobot data: " . $e->getMessage());
            throw new \Exception('Failed to update Standar Bobot data: ' . $e->getMessage());
        }
    }
}

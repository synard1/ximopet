<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaksi;
use App\Models\TransaksiDetail;
use App\Models\StokMutasi;
use Illuminate\Support\Facades\DB;
use App\Services\FIFOService;
use App\Services\TernakService;
use App\Models\Item;
use App\Models\Farm;

class StockController extends Controller
{

    protected $fifoService;
    protected $ternakService;

    public function __construct(FIFOService $fifoService, TernakService $ternakService)
    {
        $this->fifoService = $fifoService;
        $this->ternakService = $ternakService;
    }

    public function stoks(Request $request)
    {
        $type = $request->input('type');
        
        // dd($request->all());
        if($type == 'reduce'){
            return $this->reduceStock($request);
        }elseif($type == 'edit'){
            return $this->stockEdit($request);
        }elseif($type == 'reverse'){
            return $this->reverseStockReduction($request);
        }elseif($type == 'details'){
            return $this->detailsStok($request);
        }

        // return response()->json(['message' => 'Stock reduced successfully'], 200);
    }

    public function reduceStock(Request $request)
    {
        $validatedData = $request->validate([
            'farm_id' => 'required|uuid',
            'kandang_id' => 'required|uuid',
            'tanggal' => 'required|date',
            'stock' => 'required|array',
            'stock.*.item_id' => 'required|uuid',
            'stock.*.qty_used' => 'required|integer|min:1',
            'ternak_mati' => 'integer',
            'ternak_afkir' => 'integer',
            'ternak_jual' => 'integer'
        ]);

        try {
            DB::beginTransaction();
            // if($validatedData['ternak_mati']){
            //    $dataTernakMati = $this->ternakService->ternakMati($validatedData);
            // }
            // if($validatedData['ternak_afkir']){
            //    $dataTernakAfkir = $this->ternakService->ternakAfkir($validatedData);
            // }
            // if($validatedData['ternak_jual']){
            //    $dataTernakJual = $this->ternakService->ternakJual($validatedData);
            // }
            $this->fifoService->reduceStock($validatedData);

            DB::commit();
            return response()->json(['message' => 'Stock reduced successfully'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function stockEdit(Request $request)
    {
        $id = $request->input('id');
        $value = $request->input('value');
        $column = $request->input('column');
        $user_id = auth()->user() ? auth()->user()->id : null;

        // dd($user_id);

        // dd($id, $value, $column);

        try {
            // Wrap database operation in a transaction (if applicable)
            DB::beginTransaction();

            // Update Detail Items
            $transaksiDetail = TransaksiDetail::findOrFail($id);
            // Find the corresponding StokMutasi record
            $stokMutasi = StokMutasi::where('transaksi_id', $transaksiDetail->transaksi_id)->firstOrFail();

            
            if($column == 'qty'){

                $stokMutasi->update([
                    'stok_awal'  => 0,
                    'stok_akhir' => $value * $transaksiDetail->items->konversi,
                    'stok_masuk' => $value * $transaksiDetail->items->konversi,
                    'updated_by' => auth()->user()->id,

                ]);

                $transaksiDetail->update(
                    [
                        $column => $value * $transaksiDetail->items->konversi,
                        'sisa' => $value * $transaksiDetail->items->konversi,
                        'updated_by' => auth()->user()->id,

                    ]
                );
            }else{
                $transaksiDetail->update(
                    [
                        $column => $value,
                    ]
                );
            }
            

            $test = ($transaksiDetail->qty / $transaksiDetail->items->konversi) * $transaksiDetail->harga ;
            // dd($transaksiDetail->qty . '-'. $transaksiDetail->items->konversi . '-'. $transaksiDetail->harga . '-'. $test);
            
            $transaksiDetail->update(
                [
                    'sub_total' => ($transaksiDetail->qty / $transaksiDetail->items->konversi) * $transaksiDetail->harga,
                    // 'total_qty' => ($transaksiDetail->qty / $transaksiDetail->items->konversi),
                ]
            );


            //Update Parent Transaksi
            // $transaksi = Transaksi::where('id', $transaksiDetail->transaksi_id)->first();

            $transaksi = Transaksi::findOrFail($transaksiDetail->transaksi_id);
            // $sumQty = TransaksiDetail::where('transaksi_id',$transaksiDetail->transaksi_id)->sum('qty');
            $sumQty = TransaksiDetail::where('transaksi_id', $transaksiDetail->transaksi_id)
                                    ->with('items') // Eager load relasi 'items'
                                    ->get() // Ambil semua data yang sesuai
                                    ->sum(function ($item) {
                                        return $item->qty / $item->items->konversi; // Hitung qty / konversi untuk setiap item
                                    });
            $sumHarga = TransaksiDetail::where('transaksi_id',$transaksiDetail->transaksi_id)->sum('harga');
            $transaksi->update(
                [
                    'total_qty' => $sumQty,
                    'sisa' => $sumQty,
                    'harga' => $sumHarga,
                    'sub_total' => $sumHarga * $sumQty
                ]
                );
        // return response()->json(['message' => 'Berhasil Update Data', 'status' => 'success' ]);


            // Commit the transaction
            DB::commit();

            // return response()->json(['success' => true,'message'=>'Berhasil Update Data']);
            return response()->json(['message' => 'Berhasil Update Data', 'status' => 'success' ]);

        } catch (\Throwable $th) {
            //throw $th;
            DB::rollBack();
        }



        // return response()->json(['success' => $updated]);
    }

    public function reverseStockReduction(Request $request)
    {
        try {
            $this->fifoService->reverseStockReduction($request);
            return response()->json(['status' => 'success', 'message' => 'Stock reduction reversed successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        // return 'aa';
        // dd($request->all());
    }

    public function detailsStok(Request $request)
    {
        $validatedData = $request->validate([
            'id' => 'required|uuid',
            'farm_id' => 'required|uuid',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        try {
            $farmIds = auth()->user()->farmOperators()->pluck('farm_id')->toArray();

            $item = Item::findOrFail($validatedData['id']);
            $StockHistory = $item->stokHistory()
                ->whereBetween('tanggal', [$validatedData['start_date'], $validatedData['end_date']]);

            if ($validatedData['farm_id'] === '2d245e3f-fdc9-4138-b32d-994f3f1953a5') {
                $stokHistory = $stokHistory->whereIn('farm_id', $farmIds);
            } else {
                $stokHistory = $stokHistory->where('farm_id', $validatedData['farm_id']);
            }

            $stokHistory = $stokHistory->orderBy('tanggal', 'DESC')->get();

            $stokHistory->transform(function ($item) {
                $item->nama_farm = Farm::find($item->farm_id)->nama;
                return $item;
            });

            $masuk = $item->stokHistory()
                ->whereIn('farm_id', $farmIds)
                ->where('jenis','Masuk')
                ->sum('stok_akhir');
            $terpakai = $item->stokHistory()
                ->whereIn('farm_id', $farmIds)
                ->where('jenis','Pemakaian')
                ->sum('stok_keluar');

            $stokAkhir = $masuk - $terpakai;

            

            // $result = [
            //     'id' => $item->id,
            //     'kode' => $item->kode,
            //     'name' => $item->name,
            //     'satuan' => $item->satuan_besar,
            //     'stok_history' => $stokHistory->map(function ($history) {
            //         $sisa = $history->stok_akhir;
            //         $terpakai = $history->jenis === 'Pemakaian' ? $history->qty : 0;
            //         return [
            //             'tanggal' => $history->tanggal,
            //             'qty' => $history->qty,
            //             'terpakai' => $terpakai,
            //             'sisa' => $sisa,
            //         ];
            //     })->toArray(),
            // ];

            // dd($item);

            // $stokDetails = DB::table('histori_stok')
            //     ->join('items', 'histori_stok.item_id', '=', 'items.id')
            //     ->join('transaksis', 'histori_stok.transaksi_id', '=', 'transaksis.id')
            //     ->join('transaksi_details', 'histori_stok.transaksi_id', '=', 'transaksi_details.transaksi_id')
            //     ->select(
            //         'histori_stok.item_id',
            //         'transaksis.tanggal',
            //         // 'histori_stok.qty as qty_masuk',
            //         // DB::raw('COALESCE(SUM(CASE WHEN histori_stok.jenis = "Masuk" THEN histori_stok.qty ELSE 0 END), 0) as qty_masuk'),
            //         // DB::raw('COALESCE(SUM(CASE WHEN histori_stok.jenis = "Pemakaian" THEN histori_stok.qty ELSE 0 END), 0) as qty_pemakaian'),
            //         DB::raw('COALESCE(SUM(CASE WHEN histori_stok.jenis = "Masuk" THEN histori_stok.qty ELSE 0 END), 0) - COALESCE(SUM(CASE WHEN histori_stok.jenis = "Pemakaian" THEN histori_stok.qty ELSE 0 END), 0) as sisa'),
            //         'items.satuan_besar as satuan',
            //         'items.konversi'
            //     )
            //     ->whereIn('histori_stok.farm_id', $farmIds)
            //     ->where('transaksi_details.jenis', 'Pembelian')
            //     ->where('transaksi_details.item_id', $item->id)
            //     ->where('histori_stok.item_id', $item->id)
            //     ->groupBy('histori_stok.id')
            //     ->orderBy('histori_stok.tanggal', 'DESC')
            //     ->get()
            //     ->toArray();

            return response()->json(['status' => 'success', 'data' => $stokHistory], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    
}

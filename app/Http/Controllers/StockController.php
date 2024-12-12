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
use App\Models\Rekanan;
use App\Models\StockHistory;
use App\Models\TransaksiBeli;
use App\Models\TransaksiBeliDetail;

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
            $transaksiDetail = TransaksiBeliDetail::findOrFail($id);
            // Find the corresponding StokMutasi & StockHistory record
            $stockHistory = StockHistory::where('transaksi_id', $transaksiDetail->transaksi_id)->where('jenis','Pembelian')->first();
            // $stokMutasi = StokMutasi::where('transaksi_id', $transaksiDetail->transaksi_id)->firstOrFail();

            
            if($column == 'qty'){

                // $stokMutasi->update([
                //     'stok_awal'  => 0,
                //     'stok_akhir' => $value * $transaksiDetail->items->konversi,
                //     'stok_masuk' => $value * $transaksiDetail->items->konversi,
                //     'updated_by' => auth()->user()->id,

                // ]);

                $stockHistory->update([
                    'quantity'  =>  $value,
                    'available_quantity' => $value,
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

                $stockHistory->update([
                    'hpp'  =>  $value,
                    'updated_by' => auth()->user()->id,

                ]);
            }
            

            $test = ($transaksiDetail->qty / $transaksiDetail->items->konversi) * $transaksiDetail->harga ;
            // dd($transaksiDetail->qty . '-'. $transaksiDetail->items->konversi . '-'. $transaksiDetail->harga . '-'. $test);
            
            $transaksiDetail->update(
                [
                    'sub_total' => ($transaksiDetail->qty / $transaksiDetail->items->konversi) * $transaksiDetail->harga,
                    'sisa' => ($transaksiDetail->qty - $transaksiDetail->terpakai),
                ]
            );


            //Update Parent Transaksi
            // $transaksi = Transaksi::where('id', $transaksiDetail->transaksi_id)->first();

            $transaksi = TransaksiBeli::findOrFail($transaksiDetail->transaksi_id);
            // $sumQty = TransaksiDetail::where('transaksi_id',$transaksiDetail->transaksi_id)->sum('qty');
            $sumQty = TransaksiBeliDetail::where('transaksi_id', $transaksiDetail->transaksi_id)
                                    ->with('items') // Eager load relasi 'items'
                                    ->get() // Ambil semua data yang sesuai
                                    ->sum(function ($item) {
                                        return $item->qty / $item->items->konversi; // Hitung qty / konversi untuk setiap item
                                    });
            $sumHarga = TransaksiBeliDetail::where('transaksi_id',$transaksiDetail->transaksi_id)->sum('harga');
            $transaksi->update(
                [
                    'total_qty' => $sumQty,
                    'sisa' => $sumQty,
                    'harga' => $sumHarga,
                    'sub_total' => $sumHarga * $sumQty
                ]
                );


            // Commit the transaction
            DB::commit();

            // return response()->json(['success' => true,'message'=>'Berhasil Update Data']);
            return response()->json(['message' => 'Berhasil Update Data', 'status' => 'success' ]);

        } catch (\Exception $e) {
            //throw $th;
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 400);

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

            // dd($farmIds);

            $item = Item::findOrFail($validatedData['id']);
            $stockHistory = $item->stockHistory()
                ->whereBetween('tanggal', [$validatedData['start_date'], $validatedData['end_date']]);

            if ($validatedData['farm_id'] === '2d245e3f-fdc9-4138-b32d-994f3f1953a5') {
                // $stokHistory = $stockHistory->whereHas('inventoryLocation.farm', function ($q) use ($farmIds) {
                //         $q->whereIn('id', $farmIds);
                //     });
                $stokHistory = $stockHistory->whereHas('inventoryLocation.farm', function ($query) use ($farmIds) {
                    $query->whereIn('id', $farmIds);
                });
                // dd($stokHistory);


            } else {
                $stokHistory = $stockHistory->where('farm_id', $validatedData['farm_id']);
            }


            // Get farm IDs for current user from farmOperators
            // $farmIds = auth()->user()->farmOperators()->pluck('farm_id')->toArray();

            // // dd($farmIds);
            
            // // Add a condition to filter items based on farm IDs
            // $stokHistory = $stockHistory->whereHas('inventoryLocation.farm', function ($q) use ($farmIds) {
            //     $q->whereIn('id', $farmIds);
            // });

            

            $stokHistory = $stokHistory->orderBy('tanggal', 'DESC')->get();

            // dd($stokHistory);



            $stokHistory->transform(function ($item) {
                // dd($item->transaksiBeli->rekanans->nama);
                $item->nama_farm = Farm::find($item->inventoryLocation->farm_id)->nama;
                $item->item_name = Item::find($item->item_id)->name;
                $item->perusahaan_nama = Rekanan::find($item->transaksiBeli->rekanan_id)->nama;
                return $item;
            });


            return response()->json(['status' => 'success', 'data' => $stokHistory], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    
}

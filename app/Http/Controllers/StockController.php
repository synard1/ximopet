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
use App\Models\TransaksiHarianDetail;

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
        if($type == 'reduce'){
            return $this->reduceStock($request);
        }elseif($type == 'edit'){
            return $this->stockEdit($request);
        }elseif($type == 'reverse'){
            return $this->reverseStockReduction($request);
        }elseif($type == 'details'){
            return $this->detailsStok($request);
        }
    }

    public function reduceStock(Request $request)
    {
        $validatedData = $request->validate([
            'farm_id' => 'required|uuid',
            'kandang_id' => 'required|uuid',
            'tanggal' => 'required|date',
            'stock' => 'required|array',
            'stock.*.item_id' => 'required|uuid',
            'stock.*.qty_used' => 'required|numeric',
            'ternak_mati' => 'integer',
            'ternak_afkir' => 'integer',
            'ternak_jual' => 'integer'
        ]);

        try {
            DB::beginTransaction();
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

        try {
            // Wrap database operation in a transaction (if applicable)
            DB::beginTransaction();

            // Update Detail Items
            $transaksiDetail = TransaksiBeliDetail::findOrFail($id);
            // Find the corresponding StokMutasi & StockHistory record
            $stockHistory = StockHistory::where('transaksi_id', $transaksiDetail->transaksi_id)->where('jenis','Pembelian')->first();

            
            if($column == 'qty'){
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
            
            $transaksiDetail->update(
                [
                    'sub_total' => ($transaksiDetail->qty / $transaksiDetail->items->konversi) * $transaksiDetail->harga,
                    'sisa' => ($transaksiDetail->qty - $transaksiDetail->terpakai),
                ]
            );


            //Update Parent Transaksi
            $transaksi = TransaksiBeli::findOrFail($transaksiDetail->transaksi_id);
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
    }

    public function reverseStockReduction(Request $request)
    {
        try {
            $this->fifoService->reverseStockReduction($request);
            return response()->json(['status' => 'success', 'message' => 'Stock reduction reversed successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
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
            $user = auth()->user();
        
            if ($user->hasRole('Manager') || $user->hasRole('Supervisor')) {
                // If user is a Manager, get all active farm IDs
                $farmIds = Farm::where('status', 'Aktif')->pluck('id')->toArray();
            } else {
                // For other roles, get farm IDs associated with the user
                $farmIds = $user->farmOperators()->pluck('farm_id')->toArray();
            }

            $item = Item::findOrFail($validatedData['id']);
            $stockHistory = $item->stockHistory()
                ->whereBetween('tanggal', [$validatedData['start_date'], $validatedData['end_date']]);

            if ($validatedData['farm_id'] === '2d245e3f-fdc9-4138-b32d-994f3f1953a5') {
                $stokHistory = $stockHistory->whereHas('inventoryLocation.farm', function ($query) use ($farmIds) {
                    $query->whereIn('id', $farmIds);
                });


            } else {
                $stokHistory = $stockHistory->whereHas('inventoryLocation', function ($query) use ($validatedData) {
                    $query->where('farm_id', $validatedData['farm_id']);
                });            
            }

            $stokHistory = $stokHistory->orderBy('tanggal', 'DESC')->get();

            $stokHistory->transform(function ($item) {
                $transaksiHarianDetail = TransaksiHarianDetail::where('parent_id', $item->parent_id)->first();

                $item->nama_farm = Farm::find($item->inventoryLocation->farm_id)->nama;
                $item->nama_kandang = $transaksiHarianDetail->transaksiHarian->kandang->nama ?? '';
                $item->item_name = Item::find($item->item_id)->name;
                if($item->parent_id){
                    $transaksiBeliDetail = TransaksiBeliDetail::where('id', $item->parent_id)->first();

                    $item->perusahaan_nama = $transaksiBeliDetail->transaksiBeli->rekanans->nama;
                 }else{
                    $transaksiBeli = TransaksiBeli::where('id', $item->transaksi_id)->first();
                    // $item->perusahaan_nama = $transaksiBeli ? $transaksiBeli->rekanans->nama : '-';
                }

                return $item;
            });


            return response()->json(['status' => 'success', 'data' => $stokHistory], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    
}

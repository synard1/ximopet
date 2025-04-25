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
use App\Models\Kandang;
use App\Models\Rekanan;
use App\Models\StockHistory;
use App\Models\TransaksiBeli;
use App\Models\TransaksiBeliDetail;
use App\Models\TransaksiHarianDetail;
use Illuminate\Support\Facades\Log;

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

            $farmIds = $user->hasRole('Manager') || $user->hasRole('Supervisor')
                ? Farm::whereIn('status', ['Digunakan', 'Aktif'])->pluck('id')->toArray()
                : $user->farmOperators()->pluck('farm_id')->toArray();

            $item = Item::findOrFail($validatedData['id']);
            $stockHistory = $item->stockHistory()
                ->whereBetween('tanggal', [$validatedData['start_date'], $validatedData['end_date']]);

            $stokHistory = $validatedData['farm_id'] === '2d245e3f-fdc9-4138-b32d-994f3f1953a5'
                ? $stockHistory->whereHas('kelompokTernak.farm', function ($query) use ($farmIds) {
                    $query->whereIn('id', $farmIds);
                })
                : $stockHistory->whereHas('kelompokTernak', function ($query) use ($validatedData) {
                    $query->where('farm_id', $validatedData['farm_id']);
                });


            $stokHistory = $stokHistory->orderBy('tanggal', 'DESC')->get();

            // dd($stokHistory);


            $stokHistory->transform(function ($item) {
                $transaksiHarianDetail = TransaksiHarianDetail::where('parent_id', $item->parent_id)->first();

                $item->nama_farm = Farm::find($item->kelompokTernak->farm_id)->nama;
                $item->nama_kandang = Kandang::find($item->kelompokTernak->kandang_id)->nama;
                // $item->nama_kandang = $transaksiHarianDetail->transaksiHarian->kandang->nama ?? '';
                $item->item_name = Item::find($item->item_id)->name;

                // if ($item->parent_id) {
                //     $transaksiBeliDetail = TransaksiBeliDetail::where('id', $item->parent_id)->first();
                //     $item->perusahaan_nama = $transaksiBeliDetail->transaksiBeli->rekanans->nama;
                // } else {
                //     $transaksiBeli = TransaksiBeli::where('id', $item->transaksi_id)->first();
                //     // $item->perusahaan_nama = $transaksiBeli ? $transaksiBeli->rekanans->nama : '-';
                // }
                $transaksiBeli = TransaksiBeli::where('id', $item->transaksi_id)->first();


                return $item;
            });

            return response()->json(['status' => 'success', 'data' => $stokHistory], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Transfer stock between kelompokTernak
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function transferStock(Request $request)
    {
        $validatedData = $request->validate([
            'source_kelompok_ternak_id' => 'required|uuid',
            'destination_kelompok_ternak_id' => 'required|uuid',
            'item_id' => 'required|uuid',
            'quantity' => 'required|numeric|min:0.01',
            'tanggal' => 'required|date',
            'notes' => 'nullable|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            // Get source and destination kelompokTernak
            $sourceKelompokTernak = \App\Models\KelompokTernak::findOrFail($validatedData['source_kelompok_ternak_id']);
            $destinationKelompokTernak = \App\Models\KelompokTernak::findOrFail($validatedData['destination_kelompok_ternak_id']);
            
            // Get the item
            $item = Item::findOrFail($validatedData['item_id']);
            
            // Check if source has enough stock
            $sourceStock = \App\Models\CurrentStock::where('item_id', $item->id)
                ->where('location_id', $sourceKelompokTernak->id)
                ->first();
                
            if (!$sourceStock || $sourceStock->quantity < $validatedData['quantity']) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Insufficient stock in source kelompokTernak'
                ], 400);
            }
            
            // Create stock movement record for source (reduction)
            $sourceMovement = \App\Models\StockMovement::create([
                'transaksi_id' => null,
                'item_id' => $item->id,
                'source_location_id' => $sourceKelompokTernak->id,
                'destination_location_id' => $destinationKelompokTernak->id,
                'quantity' => $validatedData['quantity'],
                'movement_type' => 'transfer_out',
                'tanggal' => $validatedData['tanggal'],
                'notes' => $validatedData['notes'] ?? 'Stock transfer to ' . $destinationKelompokTernak->nama,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);
            
            // Create stock movement record for destination (addition)
            $destinationMovement = \App\Models\StockMovement::create([
                'transaksi_id' => null,
                'item_id' => $item->id,
                'source_location_id' => $sourceKelompokTernak->id,
                'destination_location_id' => $destinationKelompokTernak->id,
                'quantity' => $validatedData['quantity'],
                'movement_type' => 'transfer_in',
                'tanggal' => $validatedData['tanggal'],
                'notes' => $validatedData['notes'] ?? 'Stock transfer from ' . $sourceKelompokTernak->nama,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);
            
            // Update source stock
            $sourceStock->update([
                'quantity' => $sourceStock->quantity - $validatedData['quantity'],
                'updated_by' => auth()->id(),
            ]);
            
            // Update or create destination stock
            $destinationStock = \App\Models\CurrentStock::where('item_id', $item->id)
                ->where('location_id', $destinationKelompokTernak->id)
                ->first();
                
            if ($destinationStock) {
                $destinationStock->update([
                    'quantity' => $destinationStock->quantity + $validatedData['quantity'],
                    'updated_by' => auth()->id(),
                ]);
            } else {
                \App\Models\CurrentStock::create([
                    'item_id' => $item->id,
                    'location_id' => $destinationKelompokTernak->id,
                    'quantity' => $validatedData['quantity'],
                    'reserved_quantity' => 0,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);
            }
            
            // Create stock history records
            \App\Models\StockHistory::create([
                'transaksi_id' => null,
                'item_id' => $item->id,
                'kelompok_ternak_id' => $sourceKelompokTernak->id,
                'quantity' => -$validatedData['quantity'],
                'available_quantity' => $sourceStock->quantity - $validatedData['quantity'],
                'hpp' => $sourceStock->hpp ?? 0,
                'jenis' => 'Transfer Keluar',
                'tanggal' => $validatedData['tanggal'],
                'parent_id' => $sourceMovement->id,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);
            
            \App\Models\StockHistory::create([
                'transaksi_id' => null,
                'item_id' => $item->id,
                'kelompok_ternak_id' => $destinationKelompokTernak->id,
                'quantity' => $validatedData['quantity'],
                'available_quantity' => ($destinationStock ? $destinationStock->quantity : 0) + $validatedData['quantity'],
                'hpp' => $sourceStock->hpp ?? 0,
                'jenis' => 'Transfer Masuk',
                'tanggal' => $validatedData['tanggal'],
                'parent_id' => $destinationMovement->id,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Stock transferred successfully',
                'data' => [
                    'source_movement' => $sourceMovement,
                    'destination_movement' => $destinationMovement,
                ]
            ], 200);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Stock transfer error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to transfer stock: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check available stock for an item in a specific location
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkAvailableStock(Request $request)
    {
        $validatedData = $request->validate([
            'location_id' => 'required|uuid',
            'item_id' => 'required|uuid',
        ]);
        
        try {
            $stock = \App\Models\CurrentStock::where('item_id', $validatedData['item_id'])
                ->where('kelompok_ternak_id', $validatedData['location_id'])
                ->first();
                
            $item = Item::findOrFail($validatedData['item_id']);
            
            return response()->json([
                'status' => 'success',
                'data' => [
                    'quantity' => $stock ? $stock->quantity : 0,
                    'unit' => $item->satuan_kecil,
                    'available' => $stock && $stock->quantity > 0,
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Check available stock error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to check available stock'
            ], 500);
        }
    }

    // original backup
    // public function detailsStok(Request $request)
    // {
    //     $validatedData = $request->validate([
    //         'id' => 'required|uuid',
    //         'farm_id' => 'required|uuid',
    //         'start_date' => 'required|date',
    //         'end_date' => 'required|date',
    //     ]);

    //     try {
    //         $user = auth()->user();
        
    //         if ($user->hasRole('Manager') || $user->hasRole('Supervisor')) {
    //             // If user is a Manager, get all active farm IDs
    //             $farmIds = Farm::whereIn('status', ['Digunakan','Aktif'])->pluck('id')->toArray();
    //             // $farmIds = Farm::where('status', 'Aktif')->pluck('id')->toArray();
    //             // $farmIds = Farm::all()->toArray();
    //         } else {
    //             // For other roles, get farm IDs associated with the user
    //             $farmIds = $user->farmOperators()->pluck('farm_id')->toArray();
    //         }

    //         // dd($farmIds);

    //         $item = Item::findOrFail($validatedData['id']);
    //         $stockHistory = $item->stockHistory()
    //             ->whereBetween('tanggal', [$validatedData['start_date'], $validatedData['end_date']]);

    //         dump($stockHistory);

    //         if ($validatedData['farm_id'] === '2d245e3f-fdc9-4138-b32d-994f3f1953a5') {
    //             $stokHistory = $stockHistory->whereHas('kelompokTernak.farm', function ($query) use ($farmIds) {
    //                 $query->whereIn('id', $farmIds);
    //             });


    //         } else {
    //             $stokHistory = $stockHistory->whereHas('kelompokTernak', function ($query) use ($validatedData) {
    //                 $query->where('farm_id', $validatedData['farm_id']);
    //             });            
    //         }

    //         $stokHistory = $stokHistory->orderBy('tanggal', 'DESC')->get();

    //         $stokHistory->transform(function ($item) {
    //             $transaksiHarianDetail = TransaksiHarianDetail::where('parent_id', $item->parent_id)->first();

    //             $item->nama_farm = Farm::find($item->inventoryLocation->farm_id)->nama;
    //             $item->nama_kandang = $transaksiHarianDetail->transaksiHarian->kandang->nama ?? '';
    //             $item->item_name = Item::find($item->item_id)->name;
    //             if($item->parent_id){
    //                 $transaksiBeliDetail = TransaksiBeliDetail::where('id', $item->parent_id)->first();

    //                 $item->perusahaan_nama = $transaksiBeliDetail->transaksiBeli->rekanans->nama;
    //              }else{
    //                 $transaksiBeli = TransaksiBeli::where('id', $item->transaksi_id)->first();
    //                 // $item->perusahaan_nama = $transaksiBeli ? $transaksiBeli->rekanans->nama : '-';
    //             }

    //             return $item;
    //         });


    //         return response()->json(['status' => 'success', 'data' => $stokHistory], 200);
    //     } catch (\Exception $e) {
    //         return response()->json(['error' => $e->getMessage()], 400);
    //     }
    // }

    
}

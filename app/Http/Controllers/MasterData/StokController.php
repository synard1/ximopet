<?php

namespace App\Http\Controllers\MasterData;

use App\DataTables\FeedStockDataTable;
use App\Models\Item;
use App\Models\Farm;
use App\Models\CurrentStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Http\Controllers\Controller;
use App\DataTables\StocksDataTable;
use App\DataTables\StoksPakanDataTable;
use App\DataTables\StoksOvkDataTable;
use App\DataTables\StockSupplyDataTable;
use App\Models\KelompokTernak;
use App\Models\TransaksiBeliDetail;

use App\Models\Livestock;

use App\DataTables\FeedMutationDataTable;
use App\Models\Supply;

class StokController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(StocksDataTable $dataTable)
    {
        addVendors(['datatables']);

        addJavascriptFile('assets/js/custom/fetch-data.js');

        // $farms = CurrentStock::where('status', 'Aktif')->get(['id', 'nama']);
        // $farms = Farm::whereHas('inventoryLocations.currentStocks', function ($query) {
        //     $query->where('current_stocks.status', 'Aktif');
        // })
        $farms = Farm::where('status', 'Aktif')
        ->select('id', 'nama')
        ->distinct()
        ->get();

        return $dataTable->render('pages/masterdata.stok.index',compact('farms'));
    }

    public function stockPakan(FeedStockDataTable $dataTable)
    {
        addVendors(['datatables']);
        addJavascriptFile('assets/js/custom/fetch-data.js');
                                
        $kelompokTernaks = Livestock::all();
        $items = Item::with(['itemCategory'])
        ->whereHas('itemCategory', function ($q) {
            $q->where('name', '!=', 'DOC');
        })
        ->orderBy('name', 'DESC')
        ->get();

        return $dataTable->render('pages/masterdata.stok.index_pakan', compact(['kelompokTernaks','items']));
    }

    public function stockOvk(StoksOvkDataTable $dataTable)
    {
        addVendors(['datatables']);

        addJavascriptFile('assets/js/custom/fetch-data.js');
        return $dataTable->render('pages/masterdata.stok.index_ovk');
    }

    // public function stockSupply(StockSupplyDataTable $dataTable)
    // {
    //     addVendors(['datatables']);

    //     addJavascriptFile('assets/js/custom/fetch-data.js');
    //     return $dataTable->render('pages/masterdata.stock.index_supply');
    // }

    public function stockSupply(StockSupplyDataTable $dataTable)
    {
        addVendors(['datatables']);
        addJavascriptFile('assets/js/custom/fetch-data.js');
                                
        // $kelompokTernaks = Livestock::all();
        $farms = Farm::all();
        $items = Supply::with(['category'])
                        ->orderBy('name', 'DESC')
                        ->get();
        // $items = Item::with(['itemCategory'])
        // ->whereHas('itemCategory', function ($q) {
        //     $q->where('name', '!=', 'DOC');
        // })
        // ->orderBy('name', 'DESC')
        // ->get();

        return $dataTable->render('pages/masterdata.stock.index_supply', compact(['farms','items']));
    }

    public function stockMutasi(FeedMutationDataTable $dataTable)
    {
        //
        // return view('pages.pakan.mutasi');
        addVendors(['datatables']);

        return $dataTable->render('pages.pakan.mutasi');

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Stok $stok)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Stok $stok)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Stok $stok)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Stok $stok)
    {
        //
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

            $itemId = $validatedData['item_id'];
            $quantityUsed = $validatedData['quantity'];

            // Get source and destination kelompokTernak
            $sourceKelompokTernak = \App\Models\KelompokTernak::findOrFail($validatedData['source_kelompok_ternak_id']);
            $destinationKelompokTernak = \App\Models\KelompokTernak::findOrFail($validatedData['destination_kelompok_ternak_id']);

            // Fetch stock entries ordered by oldest first (FIFO)
            $stockEntries = TransaksiBeliDetail::whereHas('transaksiBeli', function ($query) use ($validatedData) {
                                $query->where('kelompok_ternak_id', $validatedData['source_kelompok_ternak_id']);
                            })
                            ->where('item_id', $itemId)
                            ->where('jenis', 'Pembelian')
                            ->where('sisa', '>', 0)
                            ->whereNotIn('jenis_barang', ['DOC'])
                            ->orderBy('tanggal', 'asc')
                            ->lockForUpdate() // Prevent race conditions
                            ->first();

            $stockMovement = \App\Models\StockMovement::where('kelompok_ternak_id',$sourceKelompokTernak->id)->where('transaksi_id',$stockEntries->transaksi_id)->first();


            // if ($stockEntries->isEmpty()) {
            //     throw new \Exception('No stock available for item ID: ' . $itemId);
            // }

            // dd($stockEntries);

            // foreach ($validatedData['stock'] as $stockItem) {
            //     $itemId = $stockItem['item_id'];
            //     $quantityUsed = $stockItem['qty'];

            //     // Fetch stock entries ordered by oldest first (FIFO)
            //     $stockEntries = TransaksiBeliDetail::whereHas('transaksiBeli', function ($query) use ($validatedData) {
            //                         $query->where('kelompok_ternak_id', $validatedData['ternak_id']);
            //                     })
            //                     ->where('item_id', $itemId)
            //                     ->where('jenis', 'Pembelian')
            //                     ->where('sisa', '>', 0)
            //                     ->whereNotIn('jenis_barang', ['DOC'])
            //                     ->orderBy('tanggal', 'asc')
            //                     ->lockForUpdate() // Prevent race conditions
            //                     ->get();

            //         // dd($stockEntries);
                
            //         $currentStock = CurrentStock::where('kelompok_ternak_id', $validatedData['ternak_id'])->where('item_id', $itemId)->first();

            //         if ($stockEntries->isEmpty()) {
            //             throw new \Exception('No stock available for item ID: ' . $itemId);
            //         }

            //         $remainingQuantity = $quantityUsed;

            //         foreach ($stockEntries as $stockEntry) {
            //             if ($remainingQuantity <= 0) {
            //                 break;
            //             }

            //             $deductQuantity = min($stockEntry->sisa, $remainingQuantity);

            //             // Update stock entry
            //             $stockEntry->sisa -= $deductQuantity;
            //             $stockEntry->terpakai += $deductQuantity;
            //             $stockEntry->save();

            //             // Update CurrentStock
            //             $currentStock = CurrentStock::where('item_id', $stockEntry->item_id)
            //             ->where('kelompok_ternak_id',$validatedData['ternak_id'])
            //             ->first();

            //             if ($currentStock) {
            //                 // Update existing stock
            //                 $currentStock->quantity -= $deductQuantity;
            //                 // $currentStock->available_quantity -= $deductQuantity;
            //                 $currentStock->save();
            //             }

            //             // Update remaining quantity to deduct
            //             $remainingQuantity -= $deductQuantity;

            //             // Update totals
            //             $totalQty += $deductQuantity;
            //             $totalTerpakai += $deductQuantity;
            //             $totalSisa += $stockEntry->sisa;
            //         }

            //         if ($remainingQuantity > 0) {
            //             throw new \Exception('Insufficient stock for item ID: ' . $itemId);
            //         }
            // }
            
            // Get the item
            $item = Item::findOrFail($validatedData['item_id']);
            
            // Check if source has enough stock
            // $sourceStock = \App\Models\CurrentStock::where('item_id', $item->id)
            //     ->where('kelompok_ternak_id', $sourceKelompokTernak->id)
            //     ->first();
                
            if (!$stockEntries || $stockEntries->sisa < $validatedData['quantity']) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Insufficient stock in source kelompokTernak'
                ], 400);
            }
            
            // Create stock movement record for source (reduction)
            $sourceMovement = \App\Models\StockMovement::create([
                'kelompok_ternak_id' => $sourceKelompokTernak->id,
                'transaksi_id' => $stockEntries->transaksi_id,
                'parent_id' => $stockMovement->id,
                'item_id' => $item->id,
                'source_location_id' => $sourceKelompokTernak->id,
                'destination_location_id' => $destinationKelompokTernak->id,
                'quantity' => $validatedData['quantity'],
                'satuan' => $item->satuan_besar,
                'harga' => $stockEntries->harga,
                'movement_type' => 'transfer_out',
                'tanggal' => $validatedData['tanggal'],
                'notes' => $validatedData['notes'] ?? 'Stock transfer to ' . $destinationKelompokTernak->nama,
                'created_by' => auth()->id(),
                'status' => 'out',
            ]);
            
            // Create stock movement record for destination (addition)
            $destinationMovement = \App\Models\StockMovement::create([
                'kelompok_ternak_id' => $destinationKelompokTernak->id,
                'transaksi_id' => $stockEntries->transaksi_id,
                'parent_id' => $stockMovement->id,
                'item_id' => $item->id,
                'source_location_id' => $sourceKelompokTernak->id,
                'destination_location_id' => $destinationKelompokTernak->id,
                'quantity' => $validatedData['quantity'],
                'satuan' => $item->satuan_besar,
                'harga' => $stockEntries->harga,
                'movement_type' => 'transfer_in',
                'tanggal' => $validatedData['tanggal'],
                'notes' => $validatedData['notes'] ?? 'Stock transfer from ' . $sourceKelompokTernak->nama,
                'created_by' => auth()->id(),
                'status' => 'in',
            ]);
            
            // Update source stock
            $stockEntries->update([
                // 'sisa' => $stockEntries->sisa - $validatedData['quantity'],
                // 'terpakai' => $validatedData['quantity'],
                'updated_by' => auth()->id(),
            ]);
            
            // Update or create destination stock
            $destinationStock = \App\Models\CurrentStock::where('item_id', $item->id)
                ->where('kelompok_ternak_id', $destinationKelompokTernak->id)
                ->first();
                
            if ($destinationStock) {
                $destinationStock->update([
                    'quantity' => $destinationStock->quantity + $validatedData['quantity'],
                    'updated_by' => auth()->id(),
                ]);
            } else {
                $sourcesStock = \App\Models\CurrentStock::where('item_id', $item->id)
                ->where('kelompok_ternak_id', $sourceKelompokTernak->id)
                ->first();

                $sourcesStock->update([
                    'quantity' => $sourcesStock->quantity - $validatedData['quantity'],
                    'updated_by' => auth()->id(),
                ]);

                $destinationStock = \App\Models\CurrentStock::create([
                                    'item_id' => $item->id,
                                    'kelompok_ternak_id' => $destinationKelompokTernak->id,
                                    'quantity' => $validatedData['quantity'],
                                    'created_by' => auth()->id(),
                                    'status' => 'Aktif',
                                ]);
            }
            
            // Create stock history records
            \App\Models\StockHistory::create([
                'transaksi_id' => $stockEntries->transaksi_id,
                'stock_id' => $destinationStock->id,
                'item_id' => $item->id,
                'kelompok_ternak_id' => $sourceKelompokTernak->id,
                'quantity' => -$validatedData['quantity'],
                'available_quantity' => $stockEntries->quantity - $validatedData['quantity'],
                'harga' => $stockEntries->harga ?? 0,
                'jenis' => 'Transfer Keluar',
                'status' => 'Out',
                'tanggal' => $validatedData['tanggal'],
                'parent_id' => $sourceMovement->id,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);
            
            \App\Models\StockHistory::create([
                'transaksi_id' => $stockEntries->transaksi_id,
                'stock_id' => $destinationStock->id,
                'item_id' => $item->id,
                'kelompok_ternak_id' => $destinationKelompokTernak->id,
                'quantity' => $validatedData['quantity'],
                'available_quantity' => ($destinationStock ? $destinationStock->quantity : 0) + $validatedData['quantity'],
                'harga' => $stockEntries->harga ?? 0,
                'jenis' => 'Transfer Masuk',
                'status' => 'In',
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
}

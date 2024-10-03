<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaksi;
use App\Models\TransaksiDetail;
use App\Models\StokMutasi;
use App\Models\StokTransaksi;

class StokController extends Controller
{
    public function reduceStock(Request $request)
    {
        $validatedData = $request->validate([
            'farm_id' => 'required|uuid',
            'kandang_id' => 'required|uuid',
            'tanggal' => 'required|date',
            'stock' => 'required|array',
            'stock.*.item_id' => 'required|uuid',
            'stock.*.qty_used' => 'required|integer|min:1',
        ]);

        foreach ($validatedData['stock'] as $stockItem) {
            $itemId = $stockItem['item_id'];
            $quantityUsed = $stockItem['qty_used'];

            $stockEntries = TransaksiDetail::where('item_id', $itemId)
                ->where('farm_id', $validatedData['farm_id']) 
                ->where('sisa' , '>', 0)
                ->whereNotIn('jenis_barang', ['DOC'])
                // ->where('kandang_id', $validatedData['kandang_id']) 
                ->orderBy('tanggal', 'asc')
                ->get();

            $remainingQuantity = $quantityUsed;

            // Create Stocktransaksi
            $stokTransaksi = StokTransaksi::create([
                            'farm_id' => $validatedData['farm_id'],
                            'kandang_id' => $validatedData['kandang_id'],
                            'qty' => 0,
                            'terpakai' => 0,
                            'sisa' => 0,
                            'jenis' => 'Keluar',
                            'tanggal'=> $validatedData['tanggal'],                            
                            'user_id' => auth()->user()->id,
                        ]);

            foreach ($stockEntries as $stockEntry) {
                if ($remainingQuantity <= 0) {
                    break; 
                }
    
                if ($stockEntry->sisa >= $remainingQuantity) {
                    // Deduct from 'sisa' (remaining)
                    $stockEntry->sisa -= $remainingQuantity;
                
                    // Increment 'terpakai' (used) only if there's something to deduct
                    if ($remainingQuantity > 0) {
                        $stockEntry->terpakai += $remainingQuantity;
                        $stockEntry->save();
                
                        // Create StockMovement
                        StokMutasi::create([
                            'stok_transaksi_id' => $stokTransaksi->id,
                            'transaksi_detail_id' => $stockEntry->id,
                            'item_id' => $stockEntry->item_id,
                            'item_nama' => $stockEntry->item_nama,
                            'jenis_barang' => $stockEntry->jenis_barang,
                            'rekanan_id' => $stockEntry->rekanan_id,
                            'farm_id' => $validatedData['farm_id'],
                            'kandang_id' => $validatedData['kandang_id'],
                            'harga' => $stockEntry->harga,
                            'qty' => $remainingQuantity,
                            'terpakai' => $stockEntry->terpakai,
                            'sisa' => $stockEntry->sisa,
                            'jenis' => 'Keluar',
                            'tanggal'=> $validatedData['tanggal'],                            
                            'user_id' => auth()->user()->id,
                        ]);
                    } 
                
                    $remainingQuantity = 0; 
                } else {
                    $remainingQuantity -= $stockEntry->sisa;
                    $deductedQuantity = $stockEntry->sisa;
                
                    // Increment 'terpakai' and save only if there's something to deduct
                    if ($deductedQuantity > 0) {
                        $stockEntry->terpakai += $deductedQuantity;
                        $stockEntry->sisa = 0; 
                        $stockEntry->save();
                
                        StokMutasi::create([
                            'stok_transaksi_id' => $stokTransaksi->id,
                            'transaksi_detail_id' => $stockEntry->id,
                            'item_id' => $stockEntry->item_id,
                            'item_nama' => $stockEntry->item_nama,
                            'jenis_barang' => $stockEntry->jenis_barang,
                            'rekanan_id' => $stockEntry->rekanan_id,
                            'farm_id' => $validatedData['farm_id'],
                            'kandang_id' => $validatedData['kandang_id'],
                            'harga' => $stockEntry->harga,
                            'qty' => $deductedQuantity,
                            'terpakai' => $stockEntry->terpakai,
                            'sisa' => $stockEntry->sisa,
                            'jenis' => 'Keluar',
                            'tanggal'=> $validatedData['tanggal'],                            
                            'user_id' => auth()->user()->id,
                        ]);
                    }
                }
            }

            if ($remainingQuantity > 0) {
                return response()->json(['error' => 'Insufficient stock for item ID: ' . $itemId], 400);
            }
        }

        return response()->json(['message' => 'Stock reduced successfully'], 200);
    }
}

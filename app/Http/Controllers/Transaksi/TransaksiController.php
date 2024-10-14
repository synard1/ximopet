<?php

namespace App\Http\Controllers\Transaksi;

use App\Http\Controllers\Controller;
use App\Models\Transaksi;
use Illuminate\Http\Request;
use App\DataTables\DocsDataTable;
use App\DataTables\PembelianStoksDataTable;
use App\DataTables\PemakaianStoksDataTable;
use App\Models\StokHistory;
use App\Models\TransaksiDetail;

class TransaksiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function docIndex(DocsDataTable $dataTable)
    {
        return $dataTable->render('pages/transaksi.pembelian-doc.list');
    }

    public function stokIndex(PembelianStoksDataTable $dataTable)
    {
        addJavascriptFile('assets/js/custom/pages/transaksi/pembelian-stok.js');
        return $dataTable->render('pages/transaksi.pembelian-stok.list');
    }

    public function stokPakaiIndex(PemakaianStoksDataTable $dataTable)
    {
        addJavascriptFile('assets/js/custom/pages/transaksi/pemakaian-stok.js');
        return $dataTable->render('pages/transaksi.pemakaian-stok.list');
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
    public function show(Transaksi $transaksi)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Transaksi $transaksi)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Transaksi $transaksi)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Transaksi $transaksi)
    {
        //
    }

    public function getTransaksi(Request $request)
    {
        $bentuk = $request->bentuk;
        // $status = $request->status;
        $jenis = $request->jenis;
        $task = $request->task;
        $id = $request->id;
        $value = $request->input('value');
        $column = $request->input('column');

        if($task == 'UPDATE'){
            // dd($request->all());
            // Update Detail Items
            $transaksiDetail = TransaksiDetail::findOrFail($id);
            if($column == 'qty'){
                $transaksiDetail->update(
                    [
                        $column => $value * $transaksiDetail->items->konversi,
                    ]
                );
            }else{
                $transaksiDetail->update(
                    [
                        $column => $value,
                    ]
                );
            }

            return response()->json(['message' => 'Berhasil Update Data', 'status' => 'success' ]);
        }elseif($task == 'READ'){
            // Read Detail Items
            $transactions = TransaksiDetail::where('transaksi_id', $id)
                ->select('jenis_barang', 'item_name as nama', 'qty', 'terpakai', 'sisa', 'harga', 'sub_total')
                ->orderBy('tanggal', 'DESC')
                ->get();

            return response()->json(['data' => $transactions]);
        }

        // $result = Transaksi::where('status', $status)->get(['id','kode','nama','kapasitas','jumlah']);

        $transactions = Transaksi::with('transaksiDetail')
            ->where('id', $id)
            ->where('jenis', 'Pembelian')
            ->whereHas('transaksiDetail', function ($query) {
                $query->where('jenis_barang', 'DOC');
            })
            // ->where('user_id', auth()->user()->id) // Uncomment if needed
            ->orderBy('tanggal', 'DESC')
            ->get(); // Fetch the Transaksi records first

        // Now, map over the transactions to extract the desired data
        $data = $transactions->map(function ($transaction) {
            return [
                // 'faktur' => $transaction->faktur,
                'id' => $transaction->transaksiDetail->first()?->item_id, // Access item_id from the first related transaksiDetail (if it exists)
                'nama' => $transaction->transaksiDetail->first()?->item_nama, 
                'qty' => $transaction->transaksiDetail->first()?->qty, 
                'terpakai' => $transaction->transaksiDetail->first()?->terpakai, 
                'sisa' => $transaction->transaksiDetail->first()?->sisa, 
                'harga' => $transaction->transaksiDetail->first()?->harga, 
                'sub_total' => $transaction->transaksiDetail->first()?->sub_total, 
            ];
        });

        return response()->json(['data' => $data]);
    }
}

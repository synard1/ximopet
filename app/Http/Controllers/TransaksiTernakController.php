<?php

namespace App\Http\Controllers;

use App\Models\TransaksiTernak;
use Illuminate\Http\Request;

class TransaksiTernakController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
    public function show(TransaksiTernak $transaksiTernak)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TransaksiTernak $transaksiTernak)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TransaksiTernak $transaksiTernak)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TransaksiTernak $transaksiTernak)
    {
        //
    }

    public function getDetails(Request $request)
    {
        $kelompokTernakId = $request->kelompok_ternak_id;

        $transaksi = TransaksiTernak::with(['kelompokTernak'])
            ->where('kelompok_ternak_id', $kelompokTernakId)
            ->get()
            ->map(function ($item) {
                // Calculate jumlah_akhir based on quantity and deaths
                $jumlahAkhir = $item->quantity;
                if ($item->jenis_transaksi === 'kematian') {
                    $jumlahAkhir = $item->quantity - $item->jumlah_mati;
                }
                
                return [
                    'id' => $item->id,
                    'kelompok_ternak' => $item->kelompokTernak,
                    'tanggal' => $item->tanggal,
                    'quantity' => $item->quantity,
                    'jumlah_mati' => $item->jenis_transaksi === 'kematian' ? $item->quantity : 0,
                    'jumlah_akhir' => $jumlahAkhir,
                    'berat_rata' => $item->berat_rata,
                    'berat_total' => $item->berat_total,
                    'harga_satuan' => $item->harga_satuan,
                    'total_harga' => $item->total_harga,
                ];
            });

        return response()->json([
            'data' => $transaksi
        ]);
    }
}

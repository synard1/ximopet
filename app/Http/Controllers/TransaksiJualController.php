<?php

namespace App\Http\Controllers;

use App\Models\TransaksiJual;
use Illuminate\Http\Request;
use App\DataTables\PenjualansDataTable;
use App\Models\Kandang;
use App\Models\KelompokTernak;
use Carbon\Carbon;

class TransaksiJualController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(PenjualansDataTable $dataTable)
    {
        return $dataTable->render('pages.transaksi.penjualan.index');
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
    public function show(TransaksiJual $transaksiJual)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TransaksiJual $transaksiJual)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TransaksiJual $transaksiJual)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TransaksiJual $transaksiJual)
    {
        //
    }

    public function export()
    {
        $penjualanData = TransaksiJual::where('kelompok_ternak_id','9db8d5af-adb5-4ac2-959a-0c790fa1f7bb')->get();
        $ternak = KelompokTernak::where('id','9db8d5af-adb5-4ac2-959a-0c790fa1f7bb')->first();
        
        // Set locale to Indonesian
        setlocale(LC_TIME, 'id_ID');
        Carbon::setLocale('id');

        // Format the period
        $startDate = Carbon::parse($ternak->start_date);
        $endDate = Carbon::parse($ternak->end_date);

        $periode = $startDate->translatedFormat('F') . ' s.d. ' . $endDate->translatedFormat('F Y');


        // dd($ternak->kandang->nama);
        $kandang = $ternak->kandang->nama;
        return view('pages.reports.penjualan_details', compact(['penjualanData','ternak','periode','kandang']));
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Farm;
use App\Models\Kandang;
use App\Models\KelompokTernak;
use App\Models\Reports;
use App\Models\TernakJual;
use Illuminate\Http\Request;
use App\Models\TransaksiJual;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;


class ReportsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function indexPenjualan()
    {
        $kelompokTernak = KelompokTernak::all();
        $farms = Farm::whereIn('id', $kelompokTernak->pluck('farm_id'))->get();
        $kandangs = Kandang::whereIn('id', $kelompokTernak->pluck('kandang_id'))->get();

        $ternak = $kelompokTernak->map(function ($item) {
            return [
                'id' => $item->id,
                'farm_id' => $item->farm_id,
                'farm_name' => $item->farm->nama,
                'kandang_id' => $item->kandang_id,
                'kandang_name' => $item->kandang->nama,
                'name' => $item->name,
                'start_date' => $item->start_date,
                'year' => $item->start_date->format('Y'),
            ];
        })->toArray();

        return view('pages.reports.index_report_penjualan', compact(['farms','kandangs','ternak']));
    }

    public function indexPerforma()
    {
        $kelompokTernak = KelompokTernak::all();
        $farms = Farm::whereIn('id', $kelompokTernak->pluck('farm_id'))->get();
        $kandangs = Kandang::whereIn('id', $kelompokTernak->pluck('kandang_id'))->get();

        $ternak = $kelompokTernak->map(function ($item) {
            // Retrieve the entire data column
            $allData = $item->data ? json_decode($item->data, true) : [];

            return [
                'id' => $item->id,
                'farm_id' => $item->farm_id,
                'farm_name' => $item->farm->nama,
                'kandang_id' => $item->kandang_id,
                'kandang_name' => $item->kandang->nama,
                'name' => $item->name,
                'start_date' => $item->start_date,
                'year' => $item->start_date->format('Y'),
                'tanggal_surat' => $allData['administrasi']['tanggal_laporan'] ?? null,
            ];
        })->toArray();

        return view('pages.reports.index_report_performa', compact(['farms','kandangs','ternak']));
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
    public function show(Reports $reports)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Reports $reports)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Reports $reports)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Reports $reports)
    {
        //
    }

    public function exportPenjualan(Request $request)
    {
        $data = TernakJual::where('kelompok_ternak_id',$request->periode)->where('status','OK')->get();
        $ternak = KelompokTernak::where('id',$request->periode)->first();
        $kandang = $ternak->kandang->nama;

        // Set locale to Indonesian
        setlocale(LC_TIME, 'id_ID');
        Carbon::setLocale('id');

        // Format the period
        $startDate = Carbon::parse($ternak->start_date);
        $endDate = Carbon::parse($ternak->end_date);

        $periode = $startDate->translatedFormat('F') . ' s.d. ' . $endDate->translatedFormat('F Y');

        $penjualanData = TransaksiJual::where('kelompok_ternak_id',$request->periode)->orderBy('faktur','ASC')->get();


        return view('pages.reports.penjualan_details', compact(['data','kandang','periode','penjualanData']));
    }

    public function exportPerformance(Request $request)
    {
        // dd($request->all());
        $penjualanData = TransaksiJual::where('kelompok_ternak_id',$request->periode)->get();
        $ternak = KelompokTernak::where('id',$request->periode)->first();

        // Get existing data or initialize an empty array
        $existingData = json_decode($ternak->data, true) ?? [];

        // Check if "Ternak Mati" is selected
        $isTernakMati = in_array('ternak_mati', $request->input('integrasi', []));


        // dd($ternak);


        
        // Set locale to Indonesian
        setlocale(LC_TIME, 'id_ID');
        Carbon::setLocale('id');

        // Format the period
        $startDate = Carbon::parse($ternak->start_date);
        $endDate = Carbon::parse($ternak->end_date);

        $periode = $startDate->translatedFormat('F') . ' s.d. ' . $endDate->translatedFormat('F Y');


        $kematian = $ternak->kematianTernak()->whereNull('deleted_at')->sum("quantity");
        $penjualan = $ternak->penjualanTernaks()->whereNull('deleted_at')->sum("quantity");
        // $penjualanData = TransaksiJual::where('kelompok_ternak_id',$request->periode)->get();
        // $konsumsiPakan = $ternak->konsumsiPakan()->whereNull('deleted_at')->sum("quantity");
        // $umurPanen = $ternak->penjualanTernaks()->whereNull('deleted_at')->avg("umur");
        // $umurPanen = $ternak->transaksiJuals()->detail()->whereNull('deleted_at')->avg("umur");

        // Calculate average age with two decimal places
        // $umurPanen = $ternak->transaksiJuals()
        //     ->join('transaksi_jual_details', 'transaksi_jual.id', '=', 'transaksi_jual_details.transaksi_jual_id')
        //     ->whereNull('transaksi_jual.deleted_at')
        //     ->whereNull('transaksi_jual_details.deleted_at')
        //     ->avg('transaksi_jual_details.umur');

        // If you want to round the result to 2 decimal places, you can add:
        // $umurPanen = round($umurPanen, 2);
        $umurPanen = $penjualanData->sum(fn($data) => $data->detail->umur * $data->jumlah) / $penjualanData->sum('jumlah');
        $penjualanKilo = $ternak->penjualanTernaks()->whereNull('deleted_at')->sum("total_berat") / $ternak->penjualanTernaks()->whereNull('deleted_at')->sum("quantity");

        $konsumsiPakan = $ternak->transaksiHarians()
        ->join('transaksi_harian_details', 'transaksi_harians.id', '=', 'transaksi_harian_details.transaksi_id')
        ->join('items', 'transaksi_harian_details.item_id', '=', 'items.id')
        ->join('item_categories', 'items.category_id', '=', 'item_categories.id')
        ->where('item_categories.name', 'Pakan')
        ->whereNull('transaksi_harians.deleted_at')
        ->whereNull('transaksi_harian_details.deleted_at')
        ->sum('transaksi_harian_details.quantity');

        // $totalBerat = $ternak->penjualanTernaks()->whereNull('deleted_at')->sum("total_berat");
        // Replace this line:

        $totalBerat = $ternak->transaksiJuals()
            ->join('transaksi_jual_details', 'transaksi_jual.id', '=', 'transaksi_jual_details.transaksi_jual_id')
            ->whereNull('transaksi_jual.deleted_at')
            ->whereNull('transaksi_jual_details.deleted_at')
            ->sum('transaksi_jual_details.berat');

        $fcr = $konsumsiPakan / $totalBerat;


        if($isTernakMati){
            // New IP calculation
            $mortalityRate = ($kematian / $ternak->populasi_awal) * 100;
            $kematian = $kematian;
            $persentaseKematian = ($kematian / $ternak->populasi_awal) * 100;

        }else{
            // New IP calculation
            $mortalityRate =($ternak->populasi_awal - $penjualan) / $ternak->populasi_awal * 100;
            $kematian = $ternak->populasi_awal - $penjualan;
            $persentaseKematian = ($kematian / $ternak->populasi_awal) * 100;

        }
        // $mortalityRate = (288 / $ternak->populasi_awal) * 100;
        $averageWeight = $totalBerat / $penjualan; // in kg
        $ageInDays = $umurPanen;

        $ip = (100 - $mortalityRate) * ($averageWeight / ($fcr * $ageInDays)) * 100;

        // Round IP to 2 decimal places
        $ip = round($ip, 2);

        // Calculate total biaya pakan
        $totalBiayaPakan = $ternak->transaksiHarians()
        ->join('transaksi_harian_details', 'transaksi_harians.id', '=', 'transaksi_harian_details.transaksi_id')
        ->join('items', 'transaksi_harian_details.item_id', '=', 'items.id')
        ->join('item_categories', 'items.category_id', '=', 'item_categories.id')
        ->where('item_categories.name', 'Pakan')
        ->whereNull('transaksi_harians.deleted_at')
        ->whereNull('transaksi_harian_details.deleted_at')
        ->sum(DB::raw('transaksi_harian_details.quantity * transaksi_harian_details.harga'));

        // Round to 2 decimal places
        $totalBiayaPakan = round($totalBiayaPakan, 2);

        // Calculate biaya pakan grouped by item
        $biayaPakanDetails = $ternak->transaksiHarians()
        ->join('transaksi_harian_details', 'transaksi_harians.id', '=', 'transaksi_harian_details.transaksi_id')
        ->join('items', 'transaksi_harian_details.item_id', '=', 'items.id')
        ->join('item_categories', 'items.category_id', '=', 'item_categories.id')
        ->where('item_categories.name', 'Pakan')
        ->whereNull('transaksi_harians.deleted_at')
        ->whereNull('transaksi_harian_details.deleted_at')
        ->select(
            'items.id as item_id',
            'items.name as item_name',
            DB::raw('SUM(transaksi_harian_details.quantity) as total_quantity'),
            DB::raw('AVG(transaksi_harian_details.harga) as avg_price'),
            DB::raw('SUM(transaksi_harian_details.quantity * transaksi_harian_details.harga) as total_cost')
        )
        ->groupBy('items.id', 'items.name')
        ->orderBy('items.name')
        ->get();

        // Calculate total biaya pakan
        $totalBiayaOvk = $ternak->transaksiHarians()
        ->join('transaksi_harian_details', 'transaksi_harians.id', '=', 'transaksi_harian_details.transaksi_id')
        ->join('items', 'transaksi_harian_details.item_id', '=', 'items.id')
        ->join('item_categories', 'items.category_id', '=', 'item_categories.id')
        ->where('item_categories.name', 'OVK')
        ->whereNull('transaksi_harians.deleted_at')
        ->whereNull('transaksi_harian_details.deleted_at')
        ->sum(DB::raw('transaksi_harian_details.quantity * transaksi_harian_details.harga'));

        // Round to 2 decimal places
        $totalBiayaOvk = round($totalBiayaOvk, 2);

        // dd($totalBerat);

        $data =['beratJual' => $totalBerat, 'penjualan' => $penjualan, 'kematian' => $kematian, 'penjualanData' => $penjualanData, 'ternak' => $ternak, 'periode' => $periode, 'kematian' => $kematian];

        if ($request->tanggal_surat) {
            $tanggalSurat = Carbon::parse($request->tanggal_surat)->translatedFormat('d F Y');
        
            
        
            // Check if there's no existing data or if the tanggal_surat is different
            if (!isset($data['administrasi']['tanggal_laporan']) || $data['administrasi']['tanggal_laporan'] !== $request->tanggal_surat) {
                // Add or update the tanggal_surat
                $data['administrasi']['tanggal_laporan'] = $request->tanggal_surat;
        
                // Update the data column
                $ternak->update([
                    'data' => json_encode($existingData)
                ]);
            }
        } else {
            $tanggalSurat = Carbon::now()->translatedFormat('d F Y');
        
            // If no tanggal_surat is provided, store the current date
            $existingData = json_decode($ternak->data, true) ?? [];
            $data['administrasi']['tanggal_laporan'] = $tanggalSurat;
        
            // Update the data column
            $ternak->update([
                'data' => json_encode($existingData)
            ]);
        }

        //variable hitung akhir
        $biayaDoc = $ternak->populasi_awal * $ternak->harga_beli;

        // Add totalBiayaPakan to the $data array
        $data['totalBiayaPakan'] = $totalBiayaPakan;
        $data['biayaPakanDetails'] = $biayaPakanDetails;
        $data['totalBiayaOvk'] = $totalBiayaOvk;
        $data['tanggal_surat'] = $tanggalSurat;
        $data['bonus'] = $existingData['bonus'] ?? null;
        $data['total_hpp'] = $biayaDoc + $totalBiayaPakan + $totalBiayaOvk;
        if (isset($existingData['bonus'])) {
            $bonusTotal = array_column($existingData['bonus'], 'jumlah');
            $bonusTotal = $existingData['bonus']['jumlah'];
            $data['total_hpp'] += $bonusTotal;

            // dd($bonusTotal);

        }

        $data['hpp_per_ekor'] = $data['total_hpp'] / $penjualan;
        $data['hpp_per_kg'] = $data['total_hpp'] / $totalBerat;
        $data['total_penghasilan'] = ($data['beratJual'] * $penjualanData->avg('detail.harga_jual')) - $data['total_hpp'];
        $data['penghasilan_per_ekor'] = $data['total_penghasilan'] / $penjualan;

        if (isset($existingData['administrasi'])) {
            $data['administrasi'] = $existingData['administrasi'];
            $data['administrasi']['tanggal_laporan'] = Carbon::parse($data['administrasi']['tanggal_laporan'])->translatedFormat('d F Y');
        }





        // dd($biayaPakanDetails->toArray());
        $kandang = $ternak->kandang->nama;
        return view('pages.reports.performance_kemitraan', compact(['penjualanData','ternak','periode','kandang','kematian','persentaseKematian','penjualan','penjualanKilo','konsumsiPakan','umurPanen','fcr','ip','data']));
    }
}

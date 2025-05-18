<?php

namespace App\Http\Controllers;

use App\Models\CurrentLivestock;
use App\Models\Farm;
use App\Models\FeedUsage;
use App\Models\FeedUsageDetail;
use App\Models\Kandang;
use App\Models\KelompokTernak;
use App\Models\Livestock;
use App\Models\LivestockDepletion;
use App\Models\LivestockSales;
use App\Models\LivestockSalesItem;
use App\Models\Ternak;
use App\Models\Reports;
use App\Models\Recording;
use App\Models\TernakJual;
use Illuminate\Http\Request;
use App\Models\TransaksiJual;
use App\Models\TernakDepletion;
use App\Models\TransaksiHarianDetail;

use App\Models\LivestockCost;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;


class ReportsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function indexHarian()
    {
        $livestock = Livestock::all();
        $farms = Farm::whereIn('id', $livestock->pluck('farm_id'))->get();
        $kandangs = Kandang::whereIn('id', $livestock->pluck('kandang_id'))->get();

        $livestock = $livestock->map(function ($item) {
            return [
                'id' => $item->id,
                'farm_id' => $item->farm_id,
                'farm_name' => $item->farm->name,
                'kandang_id' => $item->kandang_id,
                'kandang_name' => $item->kandang->nama,
                'name' => $item->name,
                'start_date' => $item->start_date,
                'year' => $item->start_date->format('Y'),
            ];
        })->toArray();

        return view('pages.reports.index_report_harian', compact(['farms','kandangs','livestock']));
    }

    public function indexDailyCost()
    {
        $livestock = Livestock::all();
        $farms = Farm::whereIn('id', $livestock->pluck('farm_id'))->get();
        $kandangs = Kandang::whereIn('id', $livestock->pluck('kandang_id'))->get();

        $livestock = $livestock->map(function ($item) {
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

        return view('pages.reports.index_report_harian', compact(['farms','kandangs','livestock']));
    }

    public function indexPenjualan()
    {
        $kelompokTernak = Ternak::all();
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

    public function indexPerformaMitra()
    {
        $kelompokTernak = Ternak::all();
        $farms = Farm::whereIn('id', $kelompokTernak->pluck('farm_id'))->get();
        $kandangs = Kandang::whereIn('id', $kelompokTernak->pluck('kandang_id'))->get();

        $ternak = $kelompokTernak->map(function ($item) {
            // Retrieve the entire data column
            // $allData = $item->data ? json_decode($item->data, true) : [];
            $allData = isset($item->data[0]['administrasi']) ? $item->data[0]['administrasi'] : [];

            return [
                'id' => $item->id,
                'farm_id' => $item->farm_id,
                'farm_name' => $item->farm->nama,
                'kandang_id' => $item->kandang_id,
                'kandang_name' => $item->kandang->nama,
                'name' => $item->name,
                'start_date' => $item->start_date,
                'year' => $item->start_date->format('Y'),
                'tanggal_surat' => $allData['tanggal_laporan'] ?? null,
            ];
        })->toArray();

        return view('pages.reports.index_report_performa_mitra', compact(['farms','kandangs','ternak']));
    }

    public function indexPerforma()
    {
        $livestock = Livestock::all();
        $farms = Farm::whereIn('id', $livestock->pluck('farm_id'))->get();
        $kandangs = Kandang::whereIn('id', $livestock->pluck('kandang_id'))->get();

        $ternak = $livestock->map(function ($item) {
            // Retrieve the entire data column
            // $allData = $item->data ? json_decode($item->data, true) : [];
            $allData = isset($item->data[0]['administrasi']) ? $item->data[0]['administrasi'] : [];

            return [
                'id' => $item->id,
                'farm_id' => $item->farm_id,
                'farm_name' => $item->farm->name,
                'kandang_id' => $item->kandang_id,
                'kandang_name' => $item->kandang->nama,
                'name' => $item->name,
                'start_date' => $item->start_date,
                'year' => $item->start_date->format('Y'),
                'tanggal_surat' => $allData['tanggal_laporan'] ?? null,
            ];
        })->toArray();

        return view('pages.reports.index_report_performa', compact(['farms','kandangs','ternak']));
    }

    public function indexInventory()
    {
        $kelompokTernak = Ternak::all();
        $farms = Farm::whereIn('id', $kelompokTernak->pluck('farm_id'))->get();
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

        return view('pages.reports.index_report_inventory', compact(['farms','ternak']));
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
        $ternak = Ternak::where('id',$request->periode)->first();
        $kandang = $ternak->kandang->nama;

        // Set locale to Indonesian
        setlocale(LC_TIME, 'id_ID');
        Carbon::setLocale('id');

        // Format the period
        $startDate = Carbon::parse($ternak->start_date);
        $endDate = Carbon::parse($ternak->end_date);

        $periode = $startDate->translatedFormat('F') . ' s.d. ' . $endDate->translatedFormat('F Y');

        $penjualanData = TransaksiJual::where('kelompok_ternak_id',$request->periode)->where('status','OK')->orderBy('faktur','ASC')->get();

        if ($penjualanData->isNotEmpty()) {
            return view('pages.reports.penjualan_details', compact('data', 'kandang', 'periode', 'penjualanData'));
        } else {
            return response()->json([
                'error' => 'Data penjualan belum lengkap'
            ], 404);
        }
    }

    public function exportHarian(Request $request)
    {
        // Validasi input
        $request->validate([
            'farm' => 'required',
            'tanggal' => 'required|date'
        ]);

        $farm = Farm::findOrFail($request->farm);
        $tanggal = Carbon::parse($request->tanggal);

        // Ambil semua ternak aktif pada tanggal tersebut
        $livestocks = Livestock::where('farm_id', $farm->id)
            ->whereDate('start_date', '<=', $tanggal)
            ->get();

        $recordings = [];
        $totals = [
            'stock_awal' => 0,
            'mati' => 0,
            'afkir' => 0,
            'total_deplesi' => 0,
            'tangkap_ekor' => 0,
            'tangkap_kg' => 0,
            'stock_akhir' => 0,
            'pakan_harian' => [], // Inisialisasi sebagai array kosong
            'pakan_total' => 0
        ];

        foreach ($livestocks as $livestock) {
            $kandangNama = $livestock->kandang->nama;

            $recordingData = Recording::where('livestock_id', $livestock->id)
                ->whereDate('tanggal', $tanggal)
                ->first();

            $age = Carbon::parse($livestock->start_date)->diffInDays($tanggal);
            $stockAwal = $livestock->populasi_awal;

            // Ambil data deplesi
            $deplesi = LivestockDepletion::where('livestock_id', $livestock->id);


            $mortality = $deplesi->where('jenis', 'Mati')
                ->where('tanggal', $tanggal->format('Y-m-d'))
                ->sum('jumlah');



            $culling = $deplesi->where('jenis', 'Afkir')
                ->where('tanggal', $tanggal->format('Y-m-d'))
                ->sum('jumlah');

            $totalDeplesi = LivestockDepletion::where('livestock_id', $livestock->id) // <-- Clone here
                ->where('tanggal', '<=', $tanggal->format('Y-m-d'))
                ->sum('jumlah');

            // dd($mortality);


            // Ambil data penjualan
            $sales = LivestockSalesItem::where('livestock_id', $livestock->id)
                ->whereHas('livestockSale', function ($query) use ($tanggal) {
                    $query->whereDate('tanggal', $tanggal);
                })
                ->first();

            // Ambil data penggunaan pakan harian
            $feedUsageDetails = FeedUsageDetail::whereHas('feedUsage', function ($query) use ($livestock, $tanggal) {
                $query->where('livestock_id', $livestock->id)
                    ->whereDate('usage_date', $tanggal);
            })->with('feed')->get();

            // $pakanHarian = $feedUsageDetails->sum('quantity_taken');
            // $pakanJenis = $feedUsageDetails->pluck('feed.name')->unique()->join(', ') ?: '-';
            // Refactor untuk menampilkan banyak jenis pakan dengan <br>
            // $pakanJenisArray = $feedUsageDetails->pluck('feed.name')->unique()->toArray();
            // $pakanJenis = implode('<br>', $pakanJenisArray) ?: '-';

            $pakanHarianPerJenis = [];
            foreach ($feedUsageDetails as $detail) {
                $pakanJenis = $detail->feed->name;
                $pakanHarianPerJenis[$pakanJenis] = ($pakanHarianPerJenis[$pakanJenis] ?? 0) + $detail->quantity_taken;
            }

            // Ambil total pakan kumulatif
            $totalPakanUsage = FeedUsageDetail::whereHas('feedUsage', function ($query) use ($livestock, $tanggal) {
                $query->where('livestock_id', $livestock->id)
                    ->whereDate('usage_date', '<=', $tanggal);
            })->sum('quantity_taken');

            $berat_semalam = $recordingData->berat_semalam ?? 0;
            $berat_hari_ini = $recordingData->berat_hari_ini ?? 0;
            $kenaikan_berat = $recordingData->kenaikan_berat ?? 0;

            // $stockAkhir = $stockAwal - $totalDeplesi - ($sales->quantity ?? 0);
            $stockAkhir = $stockAwal - $totalDeplesi;

            // dd($totalDeplesi);

            // Simpan ke hasil
            $recordings[$kandangNama] = [
                'umur' => $age,
                'stock_awal' => $stockAwal,
                'mati' => $mortality,
                'afkir' => $culling,
                'total_deplesi' => $totalDeplesi,
                'deplesi_percentage' => $stockAwal > 0 ? round(($totalDeplesi / $stockAwal) * 100, 2) : 0,
                'jual_ekor' => $sales->quantity ?? 0,
                'jual_kg' => $sales->total_berat ?? 0,
                'stock_akhir' => $stockAkhir,
                'berat_semalam' => $berat_semalam,
                'berat_hari_ini' => $berat_hari_ini,
                'kenaikan_berat' => $kenaikan_berat,
                'pakan_jenis' => $pakanJenis ?? '-',
                'pakan_jenis' => implode('<br>', array_keys($pakanHarianPerJenis)) ?: '-', // Untuk total row
                'pakan_harian' => $pakanHarianPerJenis, // Array nilai pakan per jenis
                
                'pakan_total' => $totalPakanUsage,
                'normal_percentage' => 20,
                'bmtk_percentage' => 80,
                'gp_percentage' => 0
            ];

             // Agregasi total pakan harian per jenis
             foreach ($pakanHarianPerJenis as $jenis => $jumlah) {
                $totals['pakan_harian'][$jenis] = ($totals['pakan_harian'][$jenis] ?? 0) + $jumlah;
            }
            $totals['stock_awal'] += $stockAwal;
            $totals['mati'] += $mortality;
            $totals['afkir'] += $culling;
            $totals['total_deplesi'] += $totalDeplesi;
            $totals['stock_akhir'] += $stockAkhir;
            $totals['pakan_total'] += $totalPakanUsage;
        }

        return view('pages.reports.harian', [
            'farm' => $farm->nama,
            'tanggal' => $tanggal->format('d-M-y'),
            'recordings' => $recordings,
            'totals' => $totals,
            'diketahui' => 'RIA NARSO',
            'dibuat' => 'HENDRA'
        ]);
    }

    public function exportCostHarian(Request $request)
    {
        $request->validate([
            'farm' => 'required',
            'tanggal' => 'required|date'
        ]);

        $farm = Farm::findOrFail($request->farm);
        $tanggal = Carbon::parse($request->tanggal);

        // Ambil semua ternak aktif di farm pada tanggal tersebut
        $livestocks = Livestock::where('id', $farm->id)
            ->whereDate('start_date', '<=', $tanggal)
            ->get();

        $costs = [];
        $totals = [
            'total_cost' => 0,
            'total_ayam' => 0,
            'total_cost_per_ayam' => 0,
        ];

        foreach ($livestocks as $livestock) {
            $costData = LivestockCost::where('livestock_id', $livestock->id)
                ->whereDate('tanggal', $tanggal)
                ->first();

            $stockAwal = $livestock->populasi_awal;

            $totalCost = $costData?->total_cost ?? 0;
            $costPerAyam = $costData?->cost_per_ayam ?? 0;

            $costs[] = [
                'kandang' => $livestock->kandang->nama ?? '-',
                'livestock' => $livestock->name,
                'umur' => $livestock->umur_pada($tanggal), // jika punya helper
                'total_cost' => $totalCost,
                'cost_per_ayam' => $costPerAyam,
                'breakdown' => $costData?->cost_breakdown ?? [],
            ];

            $totals['total_cost'] += $totalCost;
            $totals['total_ayam'] += $stockAwal;
        }

        // Hitung total cost per ayam keseluruhan
        $totals['total_cost_per_ayam'] = $totals['total_ayam'] > 0
            ? round($totals['total_cost'] / $totals['total_ayam'], 2)
            : 0;

        return view('pages.reports.livestock-cost', [
            'farm' => $farm->nama,
            'tanggal' => $tanggal->format('d M Y'),
            'costs' => $costs,
            'totals' => $totals,
        ]);
    }



    // public function exportHarian(Request $request)
    // {
    //     // Validate request
    //     $request->validate([
    //         'farm' => 'required',
    //         'tanggal' => 'required|date'
    //     ]);

    //     // Get farm data
    //     $farm = Farm::findOrFail($request->farm);
    //     $tanggal = Carbon::parse($request->tanggal);

    //     // Get all active kelompok ternak for this farm on the specified date
    //     $livestocks = Livestock::where('farm_id', $farm->id)
    //         ->where(function($query) use ($tanggal) {
    //             $query->whereDate('start_date', '<=', $tanggal)
    //                 ->orWhere(function($q) use ($tanggal) {
    //                     $q->where('start_date', '<=', $tanggal->endOfDay()->toDateTimeString());
    //                 });
    //         })
    //         ->get();

    //     // dd($kelompokTernaks->toArray());
    //     // dd($kelompokTernaks);

    //     // Initialize recordings array
    //     $recordings = [];
    //     $totals = [
    //         'stock_awal' => 0,
    //         'mati' => 0,
    //         'afkir' => 0,
    //         'total_deplesi' => 0,
    //         'tangkap_ekor' => 0,
    //         'tangkap_kg' => 0,
    //         'stock_akhir' => 0,
    //         'pakan_harian' => 0,
    //         'pakan_total' => 0
    //     ];

    //     // Process each kelompok ternak
    //     foreach ($livestocks as $livestock) {
    //         $kandangNama = $livestock->kandang->nama;

    //         // dd($kandangNama);

    //         // --- Fetch Recording Data for the selected date ---
    //         $recordingData = Recording::where('ternak_id', $livestock->id)
    //         ->whereDate('tanggal', $tanggal)
    //         ->first();
            
    //         // Calculate age
    //         $age = Carbon::parse($livestock->start_date)->diffInDays($tanggal);

    //         // Get deplesi data
    //         $deplesi = TernakDepletion::where('ternak_id', $livestock->id)->get();

    //         // Get deplesi data
    //         $sales = TernakJual::where('kelompok_ternak_id', $livestock->id)
    //             ->whereDate('tanggal', $tanggal)
    //             ->first();

    //         // Calculate deplesi for the specific date using collection methods
    //         $mortality = $deplesi->where('jenis_deplesi', 'Mati')
    //             ->filter(function($item) use ($tanggal) {
    //                 return Carbon::parse($item->tanggal_deplesi)->format('Y-m-d') === $tanggal->format('Y-m-d');
    //             })
    //             ->sum('jumlah_deplesi');

    //         $culling = $deplesi->where('jenis_deplesi', 'Afkir')
    //             ->filter(function($item) use ($tanggal) {
    //                 return Carbon::parse($item->tanggal_deplesi)->format('Y-m-d') === $tanggal->format('Y-m-d');
    //             })
    //             ->sum('jumlah_deplesi');

    //         $totalDeplesi = $deplesi
    //             ->filter(function($item) use ($tanggal) {
    //                 return Carbon::parse($item->tanggal_deplesi)->format('Y-m-d') <= $tanggal->format('Y-m-d');
    //             })
    //             ->sum('jumlah_deplesi');

    //         // Get pakan data
    //         $pakanUsage = TransaksiHarianDetail::whereHas('transaksiHarian', function($query) use ($livestock, $tanggal) {
    //             $query->where('kelompok_ternak_id', $livestock->id)
    //                 ->whereDate('tanggal', $tanggal);
    //         })
    //         ->whereHas('item.category', function($query) {
    //             $query->where('name', 'Pakan');
    //         })
    //         ->get();

    //         // Calculate totals
    //         $stockAwal = $livestock->populasi_awal;
    //         $pakanHarian = $pakanUsage->sum('quantity');
            
    //         // Ambil FeedUsageDetail pada tanggal tertentu
    //         $feedUsageDetails = FeedUsageDetail::whereHas('feedUsage', function ($query) use ($livestock, $tanggal) {
    //             $query->where('ternak_id', $livestock->id)
    //                 ->whereDate('usage_date', $tanggal);
    //         })->with('feed')->get();

    //         // dd($feedUsageDetails );

    //         // Pakan harian (hari ini)
    //         $pakanHarian = $feedUsageDetails->sum('quantity_taken');
    //         $pakanJenis = $feedUsageDetails->pluck('feed.name')->unique()->join(', ') ?: '-';

    //         // Total penggunaan pakan kumulatif sampai tanggal
    //         $totalPakanUsage = FeedUsageDetail::whereHas('feedUsage', function ($query) use ($livestock, $tanggal) {
    //             $query->where('ternak_id', $livestock->id)
    //                 ->whereDate('usage_date', '<=', $tanggal);
    //         })->sum('quantity_taken');

    //         // Berat-berat
    //         $weight_yesterday = $recordingData->berat_semalam ?? 0;
    //         $weight_today = $recordingData->berat_hari_ini ?? 0;
    //         $weight_gain = $recordingData->kenaikan_berat ?? 0;

    //         // Simpan ke record summary
    //         $recordings[$kandangNama] = [
    //             'umur' => $age,
    //             'stock_awal' => $stockAwal,
    //             'mati' => $mortality,
    //             'afkir' => $culling,
    //             'total_deplesi' => $totalDeplesi,
    //             'deplesi_percentage' => $stockAwal > 0 ? round(($totalDeplesi / $stockAwal) * 100, 2) : 0,
    //             'jual_ekor' => $sales->quantity ?? 0,
    //             'jual_kg' => $sales->total_berat ?? 0,
    //             'stock_akhir' => $stockAwal - $totalDeplesi - ($sales->quantity ?? 0),
    //             'berat_semalam' => $weight_yesterday,
    //             'berat_hari_ini' => $weight_today,
    //             'kenaikan_berat' => $weight_gain,
    //             'pakan_jenis' => $pakanJenis,
    //             'pakan_harian' => $pakanHarian,
    //             'pakan_total' => $totalPakanUsage,
    //             'normal_percentage' => 20,
    //             'bmtk_percentage' => 80,
    //             'gp_percentage' => 0
    //         ];

    //         // Update total agregat
    //         $totals['stock_awal'] += $stockAwal;
    //         $totals['mati'] += $mortality;
    //         $totals['afkir'] += $culling;
    //         $totals['total_deplesi'] += $totalDeplesi;
    //         $totals['stock_akhir'] += ($stockAwal - $totalDeplesi);
    //         $totals['pakan_harian'] += $pakanHarian;
    //         $totals['pakan_total'] += $totalPakanUsage;
    //     }

    //     // dd($recordings);

    //     return view('pages.reports.harian', [
    //         'farm' => $farm->nama,
    //         'tanggal' => $tanggal->format('d-M-y'),
    //         'recordings' => $recordings,
    //         'totals' => $totals,
    //         'diketahui' => 'RIA NARSO',
    //         'dibuat' => 'HENDRA'
    //     ]);
    // }

    public function exportPerformancePartner(Request $request)
    {
        // dd($request->all());
        $penjualanData = TransaksiJual::where('kelompok_ternak_id',$request->periode)->get();
        $ternak = Ternak::where('id',$request->periode)->first();

        if($ternak->status === 'Aktif'){
            return response()->json([
                'error' => 'Status Batch '. trans('content.ternak',[],'id') .' Masih Aktif.'
            ], 404);
        }

        // Get existing data or initialize an empty array
        // $existingData = json_decode($ternak->data, true) ?? [];
        $existingData = $ternak->data ? $ternak->data : [];

        // Check if "Ternak Mati" is selected
        $isTernakMati = in_array('ternak_mati', $request->input('integrasi', []));

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
            ->where('transaksi_jual.status','OK')
            ->whereNull('transaksi_jual.deleted_at')
            ->whereNull('transaksi_jual_details.deleted_at')
            ->sum('transaksi_jual_details.berat');

        if($totalBerat){
            $fcr = $konsumsiPakan / $totalBerat;
        }else{
            return response()->json([
                'error' => 'Data Penjualan Ternak Masih Belum Lengkap.'
            ], 404);
        }



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

    public function exportPerformance(Request $request)
    {
        if (!$request->periode) {
            return;
        }

        $currentLivestock = CurrentLivestock::where('livestock_id', $request->periode)->first();
        if (!$currentLivestock) {
            return;
        }

        $startDate = Carbon::parse($currentLivestock->livestock->start_date);
        $today = Carbon::today();

        $records = collect();
        $feedNames = collect(); // Untuk nama pakan unik

        $currentDate = $startDate->copy();
        $stockAwal = $currentLivestock->livestock->populasi_awal;
        $totalPakanUsage = 0;
        $pakanAktualTotal = 0;

        $data = json_decode($currentLivestock->livestock->data, true);
        $standarData = $data[0]['livestock_breed_standard'] ?? [];

        while ($currentDate <= $today) {
            $dateStr = $currentDate->format('Y-m-d');

            // Penjualan ternak
            $sales = LivestockSalesItem::whereHas('livestockSale', function ($query) use ($dateStr) {
                $query->whereDate('tanggal', $dateStr);
            })->where('livestock_id', $request->periode)->first();

            $totalSales = $sales->quantity ?? 0;

            // Deplesi
            $deplesi = LivestockDepletion::where('livestock_id', $request->periode)
                ->whereDate('tanggal', $dateStr)
                ->get();

            $mortality = $deplesi->where('jenis', 'Mati')->sum('jumlah');
            $culling = $deplesi->where('jenis', 'Afkir')->sum('jumlah');
            $totalDeplesi = $mortality + $culling;

            $age = $startDate->diffInDays($currentDate);

            // Feed usage
            $pakanUsageDetails = FeedUsageDetail::whereHas('feedUsage', function ($query) use ($dateStr) {
                    $query->whereDate('usage_date', $dateStr);
                })
                ->whereHas('feedStock', function ($query) use ($request) {
                    $query->where('livestock_id', $request->periode);
                })
                ->with('feedStock.feed')
                ->get();

            // Ambil data berat harian
            $recording = Recording::where('livestock_id', $request->periode)
                ->whereDate('tanggal', $dateStr)
                ->first();

            $beratHarian = $recording->berat_hari_ini ?? 0;

            // Hitung penggunaan per jenis pakan
            $feedUsageMap = [];
            $feedNames = collect(['SP 10', 'SP 11', 'SP 12']);
            $feedNames = $feedNames->unique(); // ✅ aman
            // Ambil jumlah feed
            foreach ($pakanUsageDetails as $detail) {
                $feedName = $detail->feedStock->feed->name;
                $feedUsageMap[$feedName] = ($feedUsageMap[$feedName] ?? 0) + $detail->quantity_taken;
            }
            // foreach ($pakanUsageDetails as $detail) {
            //     $feedName = $detail->feedStock->feed->name;
            //     $feedNames->push($feedName); // simpan ke koleksi unik
            //     $feedUsageMap[$feedName] = ($feedUsageMap[$feedName] ?? 0) + $detail->quantity_taken;
            // }

            $pakanHarian = array_sum($feedUsageMap);
            $totalPakanUsage += $pakanHarian;

            $stock_akhir = $stockAwal - $totalDeplesi - $totalSales;
            $pakanAktual = $pakanHarian > 0 && $stock_akhir > 0 ? ($pakanHarian / $stock_akhir * 1000) : 0;
            $pakanAktualTotal += $pakanAktual;
            $totalBerat = ($beratHarian > 0 && $stock_akhir > 0) ? ($beratHarian / 1000) * $stock_akhir : 0;

            $fcrAktual = $totalBerat > 0 ? round($totalPakanUsage / $totalBerat, 2) : null;
            $survivalRate = $stockAwal > 0 ? ($stock_akhir / $stockAwal) * 100 : 0;
            $ipAktual = ($fcrAktual && $fcrAktual > 0 && $age > 0 && $beratHarian > 0)
                ? round(($survivalRate * $beratHarian * 100) / ($age * $fcrAktual), 2)
                : null;

            $record = [
                'tanggal' => $dateStr,
                'umur' => $age,
                'fcr_target' => $standarData['data'][$age]['fcr']['target'] ?? 0,
                'stock_awal' => $stockAwal,
                'mati' => $mortality,
                'afkir' => $culling,
                'jual_ekor' => $totalSales,
                'jual_kg' => $sales->total_berat ?? 0,
                'jual_rata' => ($totalSales > 0) ? ($sales->total_berat / $totalSales) : 0,
                'total_deplesi' => $totalDeplesi,
                'deplesi_percentage' => $stockAwal > 0 ? round(($totalDeplesi / $stockAwal) * 100, 2) : 0,
                'stock_akhir' => $stock_akhir,
                'pakan_harian' => $pakanHarian,
                'pakan_aktual' => round($pakanAktual, 0),
                'pakan_aktual_total' => round($pakanAktualTotal, 0),
                'pakan_total' => $totalPakanUsage,
                'berat_harian' => round($beratHarian, 0),
                'fcr_akt' => $fcrAktual,
                'ip_akt' => $ipAktual,
            ];

            // Tambahkan kolom berdasarkan jenis pakan, default 0
            foreach ($feedNames as $feedName) {
                $record[$feedName] = $feedUsageMap[$feedName] ?? 0;
            }

            $records->push($record);
            $stockAwal = $stock_akhir;
            $currentDate->addDay();
        }

        // Ambil daftar feed unik sebagai header (untuk ditampilkan di view)
        $feedHeaders = $feedNames->unique()->values();

        // dd($records);

        return view('pages.reports.performance', compact([
            'records',
            'currentLivestock',
            'feedHeaders'
        ]));
    }

    public function formatNumber($amount,$decimal) {
        // Convert the number to a string with two decimal places
        $formattedAmount = number_format($amount, $decimal, ',', '.');
    
        // Add the currency symbol and return the formatted number
        return $formattedAmount;
    }


    // public function exportPerformance(Request $request)
    // {
    //     if (!$request->periode) {
    //         return;
    //     }

    //     $currentLivestock = CurrentLivestock::where('livestock_id', $request->periode)->first();
    //     if (!$currentLivestock) {
    //         return;
    //     }

    //     $startDate = Carbon::parse($currentLivestock->livestock->start_date);
    //     $today = Carbon::today();

    //     $records = collect();
    //     $currentDate = $startDate->copy();
    //     $stockAwal = $currentLivestock->livestock->populasi_awal;
    //     $totalPakanUsage = 0;
    //     $pakanAktualTotal = 0;
    //     // $standarData = $currentLivestock->livestock->data ? $currentLivestock->livestock->data[0]['livestock_breed_standard'] : [];

    //     // dd($currentLivestock->livestock->data['livestock_breed_standard']);
    //     $data = json_decode($currentLivestock->livestock->data, true); // Ubah string JSON ke array
    //     if (is_array($data) && isset($data[0]['livestock_breed_standard'])) {
    //         // dd($data[0]['livestock_breed_standard']);
    //         $standarData = $data[0]['livestock_breed_standard'];
    //     } else {
    //         // dd("Data tidak valid atau 'livestock_breed_standard' tidak ditemukan.");
    //     }


    //     while ($currentDate <= $today) {
    //         $dateStr = $currentDate->format('Y-m-d');

    //         // Penjualan ternak
    //         $sales = LivestockSalesItem::whereHas('livestockSale', function ($query) use ($dateStr) {
    //                 $query->whereDate('tanggal', $dateStr);
    //             })
    //             ->where('livestock_id', $request->periode)
    //             ->first();

    //         $totalSales = $sales && $sales->quantity > 0 ? $sales->quantity : 0;

    //         // Deplesi
    //         $deplesi = LivestockDepletion::where('livestock_id', $request->periode)
    //             ->whereDate('tanggal', $dateStr)
    //             ->get();

    //         $mortality = $deplesi->where('jenis', 'Mati')->sum('jumlah');
    //         $culling = $deplesi->where('jenis', 'Afkir')->sum('jumlah');
    //         $totalDeplesi = $mortality + $culling;

    //         $age = $startDate->diffInDays($currentDate);

    //         // Feed usage
    //         $pakanUsageDetails = FeedUsageDetail::whereHas('feedUsage', function ($query) use ($dateStr) {
    //                 $query->whereDate('usage_date', $dateStr);
    //             })
    //             ->whereHas('feedStock', function ($query) use ($request) {
    //                 $query->where('livestock_id', $request->periode);
    //             })
    //             // ->whereDate('usage_date', $dateStr)
    //             ->with('feedStock.feed')
    //             ->get();
            
    //         // Berat Harian
    //         $recording = Recording::where('livestock_id', $request->periode)
    //         ->whereDate('tanggal', $dateStr)
    //         ->first();
    //         $beratHarian = $recording->berat_hari_ini ?? 0;

    //         // dd($pakanUsageDetails->toArray());

            

    //         $pakanHarian = $pakanUsageDetails->sum('quantity_taken');
    //         $totalPakanUsage += $pakanHarian;
    //         $stock_akhir = $stockAwal - $totalDeplesi - $totalSales;
    //         $pakanAktual = $pakanHarian > 0 ? ($pakanHarian / $stock_akhir * 1000) : 0;
    //         $pakanAktualTotal += $pakanAktual;
    //         $totalBerat = ($beratHarian > 0 && $stock_akhir > 0)
    //                     ? ($beratHarian / 1000) * $stock_akhir
    //                     : 0;
    //         $fcrAktual = ($totalBerat > 0)
    //                     ? round($totalPakanUsage / $totalBerat, 2)
    //                     : null; // atau 0 tergantung preferensi kamu

    //         // Hitung survival rate
    //         $survivalRate = ($stockAwal > 0) ? ($stock_akhir / $stockAwal) * 100 : 0;
    //         // Hitung IP aktual (jika syarat lengkap)
    //         $ipAktual = ($fcrAktual && $fcrAktual > 0 && $age > 0 && $beratHarian > 0)
    //                 ? round(($survivalRate * $beratHarian * 100) / ($age * $fcrAktual), 2)
    //                 : null; // bisa juga jadi 0 kalau kamu mau
    //         // $totalBerat = max($stock_akhir * $totalBerat, 1) ;

    //         // dd($totalBerat);

    //         $record = [
    //             'tanggal' => $dateStr,
    //             'umur' => $age,
    //             'fcr_target' => isset($standarData['data'][$age]) ? $standarData['data'][$age]['fcr']['target'] : 0,
    //             'stock_awal' => $stockAwal,
    //             'mati' => $mortality,
    //             'afkir' => $culling,
    //             'jual_ekor' => $sales->quantity ?? 0,
    //             'jual_kg' => $sales->total_berat ?? 0,
    //             'jual_rata' => ($sales && $sales->quantity > 0) ? ($sales->total_berat / $sales->quantity) : 0,
    //             'total_deplesi' => $totalDeplesi,
    //             'deplesi_percentage' => $stockAwal > 0 ? round(($totalDeplesi / $stockAwal) * 100, 2) : 0,
    //             'stock_akhir' => $stockAwal - $totalDeplesi - $totalSales,
    //             'pakan_jenis' => $pakanUsageDetails->pluck('feedStock.feed.name')->first() ?? '-',
    //             'pakan_harian' => $pakanHarian,
    //             'pakan_aktual' => round($pakanAktual, 0),
    //             'pakan_aktual_total' => round($pakanAktualTotal, 0),
    //             'pakan_total' => $totalPakanUsage,
    //             'berat_harian' => round($beratHarian, 0),
    //             'fcr_akt' => $fcrAktual,
    //             'ip_akt' => $ipAktual,
    //         ];

    //         $records->push($record);
    //         $stockAwal = $record['stock_akhir'];
    //         $currentDate->addDay();
    //     }

    //     // dd($records->toArray());


    //     $recordings = $records;
    //     return view('pages.reports.performance', compact(['recordings', 'currentLivestock']));
    // }

}

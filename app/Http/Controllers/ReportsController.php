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
use App\Models\LivestockPurchaseItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\BatchWorker;
use App\Models\Coop;
use App\Models\Worker;
use App\Services\Report\DaillyReportExcelExportService;
use Exception;




class ReportsController extends Controller
{
    protected $daillyReportExcelExportService;

    public function __construct(DaillyReportExcelExportService $daillyReportExcelExportService)
    {
        $this->daillyReportExcelExportService = $daillyReportExcelExportService;
    }

    /**
     * Display a listing of the resource.
     */
    public function indexHarian()
    {
        $livestock = Livestock::all();
        $farms = Farm::whereIn('id', $livestock->pluck('farm_id'))->get();
        $coops = Coop::whereIn('id', $livestock->pluck('coop_id'))->get();

        $livestock = $livestock->map(function ($item) {
            return [
                'id' => $item->id,
                'farm_id' => $item->farm_id,
                'farm_name' => $item->farm->name,
                'coop_id' => $item->coop_id,
                'coop_name' => $item->coop->name,
                'name' => $item->name,
                'start_date' => $item->start_date,
                'year' => $item->start_date->format('Y'),
            ];
        })->toArray();

        return view('pages.reports.index_report_harian', compact(['farms', 'coops', 'livestock']));
    }

    public function indexBatchWorker()
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

        return view('pages.reports.index_report_batch_worker', compact(['farms', 'kandangs', 'livestock']));
    }

    public function indexDailyCost()
    {
        $livestock = Livestock::all();
        $farms = Farm::whereIn('id', $livestock->pluck('farm_id'))->get();
        $coops = Coop::whereIn('id', $livestock->pluck('coop_id'))->get();

        $ternak = $livestock->map(function ($item) {
            return [
                'id' => $item->id,
                'farm_id' => $item->farm_id,
                'farm_name' => $item->farm->name,
                'coop_id' => $item->coop_id,
                'coop_name' => $item->coop->name,
                'name' => $item->name,
                'start_date' => $item->start_date,
                'year' => $item->start_date->format('Y'),
            ];
        })->toArray();

        return view('pages.reports.index_report_livestock_cost', compact(['farms', 'coops', 'ternak']));
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

        return view('pages.reports.index_report_penjualan', compact(['farms', 'kandangs', 'ternak']));
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

        return view('pages.reports.index_report_performa_mitra', compact(['farms', 'kandangs', 'ternak']));
    }

    public function indexPerforma()
    {
        $livestock = Livestock::all();
        $farms = Farm::whereIn('id', $livestock->pluck('farm_id'))->get();
        $coops = Coop::whereIn('id', $livestock->pluck('coop_id'))->get();

        $ternak = $livestock->map(function ($item) {
            // Retrieve the entire data column
            // $allData = $item->data ? json_decode($item->data, true) : [];
            $allData = isset($item->data[0]['administrasi']) ? $item->data[0]['administrasi'] : [];

            return [
                'id' => $item->id,
                'farm_id' => $item->farm_id,
                'farm_name' => $item->farm->name,
                'coop_id' => $item->coop_id,
                'coop_name' => $item->coop->nama,
                'name' => $item->name,
                'start_date' => $item->start_date,
                'year' => $item->start_date->format('Y'),
                'tanggal_surat' => $allData['tanggal_laporan'] ?? null,
            ];
        })->toArray();

        return view('pages.reports.index_report_performa', compact(['farms', 'coops', 'ternak']));
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

        return view('pages.reports.index_report_inventory', compact(['farms', 'ternak']));
    }

    /**
     * Display Livestock Purchase Report Index
     */
    public function indexPembelianLivestock()
    {
        $farms = Farm::all();
        $partners = Partner::where('type', 'Supplier')->get();
        $expeditions = Expedition::all();

        Log::info('Livestock Purchase Report Index accessed', [
            'user_id' => auth()->id(),
            'farms_count' => $farms->count(),
            'partners_count' => $partners->count()
        ]);

        return view('pages.reports.index_report_pembelian_livestock', compact(['farms', 'partners', 'expeditions']));
    }

    /**
     * Display Feed Purchase Report Index
     */
    public function indexPembelianPakan()
    {
        $farms = Farm::all();
        $partners = Partner::where('type', 'Supplier')->get();
        $expeditions = Expedition::all();
        $feeds = Feed::all();

        Log::info('Feed Purchase Report Index accessed', [
            'user_id' => auth()->id(),
            'farms_count' => $farms->count(),
            'feeds_count' => $feeds->count()
        ]);

        return view('pages.reports.index_report_pembelian_pakan', compact(['farms', 'partners', 'expeditions', 'feeds']));
    }

    /**
     * Display Supply Purchase Report Index  
     */
    public function indexPembelianSupply()
    {
        $farms = Farm::all();
        $partners = Partner::where('type', 'Supplier')->get();
        $expeditions = Expedition::all();
        $supplies = Supply::all();

        Log::info('Supply Purchase Report Index accessed', [
            'user_id' => auth()->id(),
            'farms_count' => $farms->count(),
            'supplies_count' => $supplies->count()
        ]);

        return view('pages.reports.index_report_pembelian_supply', compact(['farms', 'partners', 'expeditions', 'supplies']));
    }

    /**
     * Display Smart Analytics page
     */
    public function smartAnalytics()
    {
        return view('pages.reports.smart-analytics');
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
        $data = TernakJual::where('kelompok_ternak_id', $request->periode)->where('status', 'OK')->get();
        $ternak = Ternak::where('id', $request->periode)->first();
        $kandang = $ternak->kandang->nama;

        // Set locale to Indonesian
        setlocale(LC_TIME, 'id_ID');
        Carbon::setLocale('id');

        // Format the period
        $startDate = Carbon::parse($ternak->start_date);
        $endDate = Carbon::parse($ternak->end_date);

        $periode = $startDate->translatedFormat('F') . ' s.d. ' . $endDate->translatedFormat('F Y');

        $penjualanData = TransaksiJual::where('kelompok_ternak_id', $request->periode)->where('status', 'OK')->orderBy('faktur', 'ASC')->get();

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
            'tanggal' => 'required|date',
            'report_type' => 'required|in:simple,detail',
            'export_format' => 'nullable|in:html,excel,pdf,csv'
        ]);

        $farm = Farm::findOrFail($request->farm);
        $tanggal = Carbon::parse($request->tanggal);
        $reportType = $request->report_type ?? 'simple';
        $exportFormat = $request->export_format ?? 'html';

        // Log untuk debugging
        Log::info('Export Harian Report', [
            'farm_id' => $farm->id,
            'farm_name' => $farm->nama,
            'tanggal' => $tanggal->format('Y-m-d'),
            'report_type' => $reportType,
            'export_format' => $exportFormat,
            'request_params' => $request->all()
        ]);

        // Ambil data untuk export
        $exportData = $this->getHarianReportData($farm, $tanggal, $reportType);

        // Validate if there are recordings
        if (empty($exportData['recordings'])) {
            Log::warning('No Recording data found for export', [
                'farm_id' => $farm->id,
                'farm_name' => $farm->name,
                'tanggal' => $tanggal->format('Y-m-d'),
                'report_type' => $reportType
            ]);

            return response()->json([
                'error' => 'Tidak ada data Recording untuk tanggal ' . $tanggal->format('d-M-Y') . ' di farm ' . $farm->name . '.'
            ], 404);
        }

        // Route ke format export yang sesuai
        switch ($exportFormat) {
            case 'excel':
                return $this->exportToExcel($exportData, $farm, $tanggal, $reportType);
            case 'pdf':
                return $this->exportToPdf($exportData, $farm, $tanggal, $reportType);
            case 'csv':
                return $this->exportToCsv($exportData, $farm, $tanggal, $reportType);
            default:
                return $this->exportToHtml($exportData, $farm, $tanggal, $reportType);
        }
    }

    /**
     * Get report data for export (extracted from exportHarian for reusability)
     */
    private function getHarianReportData($farm, $tanggal, $reportType)
    {
        // Ambil semua ternak aktif pada tanggal tersebut
        $livestocks = Livestock::where('farm_id', $farm->id)
            ->whereDate('start_date', '<=', $tanggal)
            ->with(['coop'])
            ->get();

        // Check if there are any recordings for this date and livestocks
        $hasRecordings = Recording::whereIn('livestock_id', $livestocks->pluck('id')->toArray())
            ->whereDate('tanggal', $tanggal)
            ->exists();

        Log::info('Recording data check', [
            'has_recordings' => $hasRecordings,
            'livestock_count' => $livestocks->count(),
            'livestock_ids' => $livestocks->pluck('id')->toArray()
        ]);

        Log::info('Livestocks found', [
            'count' => $livestocks->count(),
            'livestock_ids' => $livestocks->pluck('id')->toArray()
        ]);

        $recordings = [];
        $totals = [
            'stock_awal' => 0,
            'mati' => 0,
            'afkir' => 0,
            'total_deplesi' => 0,
            'deplesi_percentage' => 0,
            'jual_ekor' => 0,
            'jual_kg' => 0,
            'stock_akhir' => 0,
            'berat_semalam' => 0,
            'berat_hari_ini' => 0,
            'kenaikan_berat' => 0,
            'pakan_harian' => [],
            'pakan_total' => 0,
            'tangkap_ekor' => 0,  // legacy field for compatibility
            'tangkap_kg' => 0,    // legacy field for compatibility
            'survival_rate' => 0  // survival rate percentage
        ];

        $distinctFeedNames = [];

        if ($reportType === 'detail') {
            // Mode Detail: Tampilkan data per batch dengan grouping per kandang
            $livestocksByCoopNama = $livestocks->groupBy(function ($livestock) {
                return $livestock->coop->name;
            });

            Log::info('Detail mode - Livestock grouped by coop', [
                'coop_groups' => $livestocksByCoopNama->map(function ($group, $coopName) {
                    return [
                        'coop_name' => $coopName,
                        'livestock_count' => $group->count(),
                        'livestock_ids' => $group->pluck('id')->toArray()
                    ];
                })->toArray()
            ]);

            foreach ($livestocksByCoopNama as $coopNama => $coopLivestocks) {
                $coopData = [];

                foreach ($coopLivestocks as $index => $livestock) {
                    $batchData = $this->processLivestockData($livestock, $tanggal, $distinctFeedNames, $totals);
                    $coopData[] = $batchData;
                }

                $recordings[$coopNama] = $coopData;
            }
        } else {
            // Mode Simple: Agregasi data per kandang
            $livestocksByCoopNama = $livestocks->groupBy(function ($livestock) {
                return $livestock->coop->name;
            });

            foreach ($livestocksByCoopNama as $coopNama => $coopLivestocks) {
                $aggregatedData = $this->processCoopAggregation($coopLivestocks, $tanggal, $distinctFeedNames, $totals);
                $recordings[$coopNama] = $aggregatedData;
            }
        }

        // Ensure all distinctFeedNames are represented in totals['pakan_harian']
        foreach ($distinctFeedNames as $feedName) {
            if (!isset($totals['pakan_harian'][$feedName])) {
                $totals['pakan_harian'][$feedName] = 0;
            }
        }

        // Calculate final totals and percentages
        $totals['deplesi_percentage'] = $totals['stock_awal'] > 0
            ? round(($totals['total_deplesi'] / $totals['stock_awal']) * 100, 2)
            : 0;

        // Calculate survival rate
        $totals['survival_rate'] = $totals['stock_awal'] > 0
            ? round(($totals['stock_akhir'] / $totals['stock_awal']) * 100, 2)
            : 0;

        // Sync legacy fields
        $totals['tangkap_ekor'] = $totals['jual_ekor'];
        $totals['tangkap_kg'] = $totals['jual_kg'];

        Log::info('Final report totals calculated', [
            'stock_awal' => $totals['stock_awal'],
            'stock_akhir' => $totals['stock_akhir'],
            'total_deplesi' => $totals['total_deplesi'],
            'deplesi_percentage' => $totals['deplesi_percentage'],
            'survival_rate' => $totals['survival_rate'],
            'distinct_feed_count' => count($distinctFeedNames),
            'feed_names' => $distinctFeedNames,
            'pakan_harian_keys' => array_keys($totals['pakan_harian']),
            'pakan_total' => $totals['pakan_total']
        ]);

        return [
            'farm' => $farm,
            'tanggal' => $tanggal,
            'recordings' => $recordings,
            'totals' => $totals,
            'distinctFeedNames' => $distinctFeedNames,
            'reportType' => $reportType
        ];
    }

    /**
     * Process individual livestock data
     */
    private function processLivestockData($livestock, $tanggal, &$distinctFeedNames, &$totals)
    {
        $recordingData = Recording::where('livestock_id', $livestock->id)
            ->whereDate('tanggal', $tanggal)
            ->first();

        $age = Carbon::parse($livestock->start_date)->diffInDays($tanggal);
        $stockAwal = (int) $livestock->initial_quantity;

        // Ambil data deplesi untuk tanggal spesifik (harian)
        $mortality = (int) LivestockDepletion::where('livestock_id', $livestock->id)
            ->where('jenis', 'Mati')
            ->where('tanggal', $tanggal->format('Y-m-d'))
            ->sum('jumlah');

        $culling = (int) LivestockDepletion::where('livestock_id', $livestock->id)
            ->where('jenis', 'Afkir')
            ->where('tanggal', $tanggal->format('Y-m-d'))
            ->sum('jumlah');

        // Total deplesi kumulatif sampai tanggal tersebut
        $totalDepletionCumulative = (int) LivestockDepletion::where('livestock_id', $livestock->id)
            ->where('tanggal', '<=', $tanggal->format('Y-m-d'))
            ->sum('jumlah');

        // Ambil data penjualan untuk tanggal spesifik
        $sales = LivestockSalesItem::where('livestock_id', $livestock->id)
            ->whereHas('livestockSale', function ($query) use ($tanggal) {
                $query->whereDate('tanggal', $tanggal);
            })
            ->first();

        // Total penjualan kumulatif sampai tanggal tersebut
        $totalSalesCumulative = (int) LivestockSalesItem::where('livestock_id', $livestock->id)
            ->whereHas('livestockSale', function ($query) use ($tanggal) {
                $query->whereDate('tanggal', '<=', $tanggal);
            })
            ->sum('quantity');

        // Ambil data penggunaan pakan harian
        $feedUsageDetails = FeedUsageDetail::whereHas('feedUsage', function ($query) use ($livestock, $tanggal) {
            $query->where('livestock_id', $livestock->id)
                ->whereDate('usage_date', $tanggal);
        })->with('feed')->get();

        $pakanHarianPerJenis = [];
        $totalPakanHarian = 0;

        foreach ($feedUsageDetails as $detail) {
            $pakanJenis = $detail->feed->name;
            $quantity = (float) $detail->quantity_taken;
            $pakanHarianPerJenis[$pakanJenis] = ($pakanHarianPerJenis[$pakanJenis] ?? 0) + $quantity;
            $totalPakanHarian += $quantity;
        }

        // Update distinctFeedNames dengan semua jenis pakan yang ada
        $distinctFeedNames = array_unique(array_merge($distinctFeedNames, array_keys($pakanHarianPerJenis)));

        // Ambil total pakan kumulatif sampai tanggal tersebut
        $totalPakanUsage = (float) FeedUsageDetail::whereHas('feedUsage', function ($query) use ($livestock, $tanggal) {
            $query->where('livestock_id', $livestock->id)
                ->whereDate('usage_date', '<=', $tanggal);
        })->sum('quantity_taken');

        $berat_semalam = (float) ($recordingData->berat_semalam ?? 0);
        $berat_hari_ini = (float) ($recordingData->berat_hari_ini ?? 0);
        $kenaikan_berat = (float) ($recordingData->kenaikan_berat ?? 0);

        // Hitung stock akhir dengan benar: stock awal - deplesi kumulatif - penjualan kumulatif
        $stockAkhir = $stockAwal - $totalDepletionCumulative - $totalSalesCumulative;

        // Update totals dengan data harian dan kumulatif yang benar
        $totals['stock_awal'] += $stockAwal;
        $totals['mati'] += $mortality; // harian
        $totals['afkir'] += $culling; // harian
        $totals['total_deplesi'] += $totalDepletionCumulative; // kumulatif
        $totals['jual_ekor'] += (int) ($sales->quantity ?? 0); // harian
        $totals['jual_kg'] += (float) ($sales->total_berat ?? 0); // harian
        $totals['stock_akhir'] += $stockAkhir;
        $totals['berat_semalam'] += $berat_semalam;
        $totals['berat_hari_ini'] += $berat_hari_ini;
        $totals['kenaikan_berat'] += $kenaikan_berat;
        $totals['pakan_total'] += $totalPakanUsage; // kumulatif

        // Legacy fields for backward compatibility
        $totals['tangkap_ekor'] += (int) ($sales->quantity ?? 0);
        $totals['tangkap_kg'] += (float) ($sales->total_berat ?? 0);

        // Update totals pakan harian dengan memastikan semua jenis pakan ada
        foreach ($pakanHarianPerJenis as $jenis => $jumlah) {
            $totals['pakan_harian'][$jenis] = ($totals['pakan_harian'][$jenis] ?? 0) + (float) $jumlah;
        }

        Log::info("Processed livestock data", [
            'livestock_id' => $livestock->id,
            'livestock_name' => $livestock->name,
            'stock_awal' => $stockAwal,
            'mortality_daily' => $mortality,
            'culling_daily' => $culling,
            'total_depletion_cumulative' => $totalDepletionCumulative,
            'total_sales_cumulative' => $totalSalesCumulative,
            'stock_akhir' => $stockAkhir,
            'feed_types_count' => count($pakanHarianPerJenis),
            'total_feed_daily' => $totalPakanHarian,
            'total_feed_cumulative' => $totalPakanUsage
        ]);

        return [
            'livestock_id' => $livestock->id,
            'livestock_name' => $livestock->name,
            'umur' => $age,
            'stock_awal' => $stockAwal,
            'mati' => $mortality, // harian
            'afkir' => $culling, // harian
            'total_deplesi' => $totalDepletionCumulative, // kumulatif
            'deplesi_percentage' => $stockAwal > 0 ? round(($totalDepletionCumulative / $stockAwal) * 100, 2) : 0,
            'jual_ekor' => (int) ($sales->quantity ?? 0), // harian
            'jual_kg' => (float) ($sales->total_berat ?? 0), // harian
            'stock_akhir' => $stockAkhir,
            'berat_semalam' => $berat_semalam,
            'berat_hari_ini' => $berat_hari_ini,
            'kenaikan_berat' => $kenaikan_berat,
            'pakan_harian' => $pakanHarianPerJenis,
            'pakan_total' => $totalPakanUsage,
            'pakan_jenis' => array_keys($pakanHarianPerJenis)
        ];
    }

    /**
     * Process coop aggregation for simple mode
     */
    private function processCoopAggregation($coopLivestocks, $tanggal, &$distinctFeedNames, &$totals)
    {
        $aggregatedData = [
            'umur' => 0,
            'stock_awal' => 0,
            'mati' => 0,
            'afkir' => 0,
            'total_deplesi' => 0,
            'jual_ekor' => 0,
            'jual_kg' => 0,
            'stock_akhir' => 0,
            'berat_semalam' => 0,
            'berat_hari_ini' => 0,
            'kenaikan_berat' => 0,
            'pakan_harian' => [],
            'pakan_total' => 0,
            'livestock_count' => $coopLivestocks->count()
        ];

        // Process each livestock individually first to get accurate totals
        $batchDataCollection = [];
        foreach ($coopLivestocks as $livestock) {
            $batchData = $this->processLivestockData($livestock, $tanggal, $distinctFeedNames, $totals);
            $batchDataCollection[] = $batchData;
        }

        // Now aggregate the processed data
        foreach ($batchDataCollection as $batchData) {
            // Use the last livestock's age (they should be the same for same coop/batch)
            $aggregatedData['umur'] = $batchData['umur'];
            $aggregatedData['stock_awal'] += $batchData['stock_awal'];
            $aggregatedData['mati'] += $batchData['mati'];
            $aggregatedData['afkir'] += $batchData['afkir'];
            $aggregatedData['total_deplesi'] += $batchData['total_deplesi'];
            $aggregatedData['jual_ekor'] += $batchData['jual_ekor'];
            $aggregatedData['jual_kg'] += $batchData['jual_kg'];
            $aggregatedData['stock_akhir'] += $batchData['stock_akhir'];
            $aggregatedData['berat_semalam'] += $batchData['berat_semalam'];
            $aggregatedData['berat_hari_ini'] += $batchData['berat_hari_ini'];
            $aggregatedData['kenaikan_berat'] += $batchData['kenaikan_berat'];
            $aggregatedData['pakan_total'] += $batchData['pakan_total'];

            // Aggregate pakan harian data
            foreach ($batchData['pakan_harian'] as $jenis => $jumlah) {
                $aggregatedData['pakan_harian'][$jenis] = ($aggregatedData['pakan_harian'][$jenis] ?? 0) + (float) $jumlah;
            }
        }

        // Ensure all feed types from distinctFeedNames are represented in aggregated data
        foreach ($distinctFeedNames as $feedName) {
            if (!isset($aggregatedData['pakan_harian'][$feedName])) {
                $aggregatedData['pakan_harian'][$feedName] = 0;
            }
        }

        // Calculate averages for weight data
        if ($aggregatedData['livestock_count'] > 0) {
            $aggregatedData['berat_semalam'] = $aggregatedData['berat_semalam'] / $aggregatedData['livestock_count'];
            $aggregatedData['berat_hari_ini'] = $aggregatedData['berat_hari_ini'] / $aggregatedData['livestock_count'];
            $aggregatedData['kenaikan_berat'] = $aggregatedData['kenaikan_berat'] / $aggregatedData['livestock_count'];
        }

        // Calculate depletion percentage
        $aggregatedData['deplesi_percentage'] = $aggregatedData['stock_awal'] > 0
            ? round(($aggregatedData['total_deplesi'] / $aggregatedData['stock_awal']) * 100, 2)
            : 0;

        Log::info("Processed coop aggregation", [
            'coop_livestock_count' => $aggregatedData['livestock_count'],
            'total_stock_awal' => $aggregatedData['stock_awal'],
            'total_stock_akhir' => $aggregatedData['stock_akhir'],
            'total_depletion' => $aggregatedData['total_deplesi'],
            'depletion_percentage' => $aggregatedData['deplesi_percentage'],
            'feed_types' => array_keys($aggregatedData['pakan_harian']),
            'distinct_feed_names' => $distinctFeedNames
        ]);

        return $aggregatedData;
    }

    /**
     * Export to HTML format (existing view)
     */
    private function exportToHtml($data, $farm, $tanggal, $reportType)
    {
        return view('pages.reports.harian', [
            'farm' => $farm->nama,
            'tanggal' => $tanggal->format('d-M-y'),
            'recordings' => $data['recordings'],
            'totals' => $data['totals'],
            'distinctFeedNames' => $data['distinctFeedNames'],
            'reportType' => $reportType,
            'diketahui' => '',
            'dibuat' => ''
        ]);
    }

    /**
     * Export to Excel format using DaillyReportExcelExportService
     */
    private function exportToExcel($data, $farm, $tanggal, $reportType)
    {
        return $this->daillyReportExcelExportService->exportToExcel($data, $farm, $tanggal, $reportType);
    }



    /**
     * Export to PDF format
     */
    private function exportToPdf($data, $farm, $tanggal, $reportType)
    {
        try {
            $filename = 'laporan_harian_' . $farm->name . '_' . $tanggal->format('Y-m-d') . '_' . $reportType . '.pdf';

            $pdf = app('dompdf.wrapper');
            $html = view('pages.reports.harian-pdf', [
                'farm' => $farm->name,
                'tanggal' => $tanggal->format('d-M-y'),
                'recordings' => $data['recordings'],
                'totals' => $data['totals'],
                'distinctFeedNames' => $data['distinctFeedNames'],
                'reportType' => $reportType,
                'diketahui' => 'RIA NARSO',
                'dibuat' => 'HENDRA'
            ])->render();

            $pdf->loadHTML($html);
            $pdf->setPaper('A4', 'landscape');

            Log::info('PDF export completed', [
                'filename' => $filename,
                'report_type' => $reportType
            ]);

            return $pdf->download($filename);
        } catch (Exception $e) {
            Log::error('PDF export failed', [
                'error' => $e->getMessage()
            ]);

            // Fallback to HTML view
            return $this->exportToHtml($data, $farm, $tanggal, $reportType);
        }
    }

    /**
     * Export to CSV format with structured table layout
     */
    private function exportToCsv($data, $farm, $tanggal, $reportType, $format = 'csv')
    {
        try {
            $extension = $format === 'excel' ? 'xlsx' : 'csv';
            $filename = 'laporan_harian_' . $farm->name . '_' . $tanggal->format('Y-m-d') . '_' . $reportType . '.' . $extension;

            // Use same structured data as Excel from service
            $csvData = $this->daillyReportExcelExportService->prepareStructuredData($data, $farm, $tanggal, $reportType);

            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Pragma' => 'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0'
            ];

            Log::info('CSV export completed', [
                'filename' => $filename,
                'rows_count' => count($csvData),
                'report_type' => $reportType
            ]);

            $callback = function () use ($csvData) {
                $file = fopen('php://output', 'w');

                // Add BOM for UTF-8 support
                fwrite($file, "\xEF\xBB\xBF");

                foreach ($csvData as $row) {
                    fputcsv($file, $row, ',', '"'); // Use comma separator for CSV
                }
                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (Exception $e) {
            Log::error('CSV export failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Export CSV gagal: ' . $e->getMessage()
            ], 500);
        }
    }



    /**
     * Export livestock cost report for a specific date
     * Updated to match the corrected LivestockCostService v2.0
     * 
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function exportCostHarian(Request $request)
    {
        $request->validate([
            'farm' => 'required',
            'kandang' => 'required',
            'tahun' => 'required',
            'periode' => 'required',
            'tanggal' => 'required|date',
            'report_type' => 'required|in:detail,simple',
        ]);

        $farm = Farm::findOrFail($request->farm);
        $livestock = Livestock::findOrFail($request->periode);
        $tanggal = Carbon::parse($request->tanggal);

        Log::info("📊 Generating livestock cost report", [
            'farm_id' => $farm->id,
            'livestock_id' => $livestock->id,
            'date' => $tanggal->format('Y-m-d'),
            'report_type' => $request->report_type
        ]);

        // Get cost data for the specified date
        $costData = LivestockCost::where('livestock_id', $livestock->id)
            ->whereDate('tanggal', $tanggal)
            ->first();

        if (!$costData) {
            Log::warning("⚠️ No cost data found for the specified date", [
                'livestock_id' => $livestock->id,
                'date' => $tanggal->format('Y-m-d')
            ]);

            // Try to generate cost data if missing
            $costService = app(\App\Services\Livestock\LivestockCostService::class);
            $costData = $costService->calculateForDate($livestock->id, $tanggal);
        }

        // Extract data from the corrected structure
        $breakdown = $costData->cost_breakdown ?? [];
        $summary = $breakdown['summary'] ?? [];
        $initialPurchaseDetails = $breakdown['initial_purchase_item_details'] ?? [];

        // Get stock and cost information
        $stockAwal = $breakdown['stock_awal'] ?? $livestock->initial_quantity ?? 0;
        $stockAkhir = $breakdown['stock_akhir'] ?? $stockAwal;
        $totalCost = $costData->total_cost ?? 0; // Daily added cost
        $costPerAyam = $costData->cost_per_ayam ?? 0; // Total cost per chicken (including initial price)
        $age = Carbon::parse($livestock->start_date)->diffInDays($tanggal);

        // Get initial purchase data
        $initialPurchaseItem = LivestockPurchaseItem::where('livestock_id', $livestock->id)
            ->orderBy('created_at', 'asc')
            ->first();

        $initialPurchasePrice = $initialPurchaseItem->price_per_unit ?? 0;
        $initialPurchaseQuantity = $initialPurchaseItem->quantity ?? $livestock->initial_quantity ?? 0;
        $initialPurchaseDate = $initialPurchaseItem->created_at ?? $livestock->start_date ?? null;
        $initialPurchaseTotalCost = $initialPurchasePrice * $initialPurchaseQuantity;

        Log::info("📦 Initial purchase data for report", [
            'price_per_unit' => $initialPurchasePrice,
            'quantity' => $initialPurchaseQuantity,
            'total_cost' => $initialPurchaseTotalCost,
            'date' => $initialPurchaseDate ? $initialPurchaseDate->format('Y-m-d') : '-',
        ]);

        // Prepare costs array for the report
        $costs = [];
        $totals = [
            'total_cost' => 0,
            'total_ayam' => 0,
            'total_cost_per_ayam' => 0,
        ];

        // Main cost entry
        $mainCost = [
            'kandang' => $livestock->coop->name ?? '-',
            'livestock' => $livestock->name,
            'umur' => $age,
            'total_cost' => $totalCost, // Daily added cost
            'cost_per_ayam' => $costPerAyam, // Total cost per chicken (initial + accumulated)
            'breakdown' => [],
        ];

        // Prepare breakdown based on report type
        if ($request->report_type === 'detail') {
            $detailedBreakdown = [];

            // Add Initial Purchase Cost entry (for context)
            if ($initialPurchaseItem) {
                $detailedBreakdown[] = [
                    'kategori' => 'Harga Awal DOC',
                    'jumlah' => $initialPurchaseQuantity,
                    'satuan' => 'Ekor',
                    'harga_satuan' => $initialPurchasePrice,
                    'subtotal' => $initialPurchaseTotalCost,
                    'tanggal' => $initialPurchaseDate ? $initialPurchaseDate->format('d/m/Y') : '-',
                    'is_initial_purchase' => true,
                ];
            }

            // Add Feed Details
            $feedDetails = $breakdown['feed_detail'] ?? [];
            foreach ($feedDetails as $feedKey => $feedItem) {
                $detailedBreakdown[] = [
                    'kategori' => $feedItem['feed_name'] ?? 'Pakan',
                    'jumlah' => $feedItem['jumlah_purchase_unit'] ?? 0,
                    'satuan' => $feedItem['purchase_unit'] ?? '-',
                    'harga_satuan' => $feedItem['price_per_purchase_unit'] ?? 0,
                    'subtotal' => $feedItem['subtotal'] ?? 0,
                    'tanggal' => $tanggal->format('d/m/Y'),
                    'is_initial_purchase' => false,
                ];
            }

            // Add OVK Details
            $ovkDetails = $breakdown['ovk_detail'] ?? [];
            foreach ($ovkDetails as $ovkKey => $ovkItem) {
                $detailedBreakdown[] = [
                    'kategori' => $ovkItem['supply_name'] ?? 'OVK',
                    'jumlah' => $ovkItem['quantity'] ?? 0,
                    'satuan' => $ovkItem['unit'] ?? '-',
                    'harga_satuan' => $ovkItem['price_per_unit'] ?? 0,
                    'subtotal' => $ovkItem['subtotal'] ?? 0,
                    'tanggal' => $tanggal->format('d/m/Y'),
                    'is_initial_purchase' => false,
                ];
            }

            // Add Deplesi Cost
            $deplesiCost = $breakdown['deplesi'] ?? 0;
            $deplesiEkor = $breakdown['deplesi_ekor'] ?? 0;
            if ($deplesiCost > 0) {
                // Use the cumulative cost per chicken from previous day for deplesi calculation
                $prevCostData = $breakdown['prev_cost'] ?? [];
                $cumulativeCostPerChicken = $prevCostData['cumulative_cost_per_chicken'] ?? $initialPurchasePrice;

                $detailedBreakdown[] = [
                    'kategori' => 'Deplesi (Harga Kumulatif)',
                    'jumlah' => $deplesiEkor,
                    'satuan' => 'Ekor',
                    'harga_satuan' => $cumulativeCostPerChicken,
                    'subtotal' => $deplesiCost,
                    'tanggal' => $tanggal->format('d/m/Y'),
                    'is_initial_purchase' => false,
                    'calculation_note' => 'Harga kumulatif per ayam x jumlah deplesi'
                ];
            }

            $mainCost['breakdown'] = $detailedBreakdown;

            // Calculate total cumulative cost for display
            $cumulativeAddedCost = $summary['total_cumulative_added_cost'] ?? 0;
            $totalCumulativeCostCalculated = $initialPurchaseTotalCost + $cumulativeAddedCost;
        } else {
            // Simple report type: Add aggregated categories
            $simpleBreakdown = [];

            $pakanCost = $breakdown['pakan'] ?? 0;
            if ($pakanCost > 0) {
                $simpleBreakdown[] = [
                    'kategori' => 'Pakan',
                    'subtotal' => $pakanCost,
                ];
            }

            $ovkCost = $breakdown['ovk'] ?? 0;
            if ($ovkCost > 0) {
                $simpleBreakdown[] = [
                    'kategori' => 'OVK',
                    'subtotal' => $ovkCost,
                ];
            }

            $deplesiCost = $breakdown['deplesi'] ?? 0;
            if ($deplesiCost > 0) {
                $simpleBreakdown[] = [
                    'kategori' => 'Deplesi',
                    'subtotal' => $deplesiCost,
                ];
            }

            $mainCost['breakdown'] = $simpleBreakdown;
            $totalCumulativeCostCalculated = 0; // Not calculated for simple report
        }

        $costs[] = $mainCost;

        // Calculate totals
        $totals['total_cost'] += $totalCost;
        $totals['total_ayam'] += $stockAkhir;
        $totals['total_cost_per_ayam'] = $totals['total_ayam'] > 0
            ? round($totals['total_cost'] / $totals['total_ayam'], 2)
            : 0;

        Log::info("💰 Report totals calculated", [
            'total_cost' => $totals['total_cost'],
            'total_ayam' => $totals['total_ayam'],
            'total_cost_per_ayam' => $totals['total_cost_per_ayam']
        ]);

        // Prepare additional data for detailed report
        $prevCostData = $breakdown['prev_cost'] ?? [];
        $summaryData = $breakdown['summary'] ?? [];

        return view('pages.reports.livestock-cost', [
            'farm' => $farm->name,
            'tanggal' => $tanggal->format('d M Y'),
            'costs' => $costs,
            'totals' => $totals,
            'report_type' => $request->report_type,
            'prev_cost_data' => $prevCostData,
            'summary_data' => $summaryData,
            'total_cumulative_cost_calculated' => $totalCumulativeCostCalculated ?? 0,
            'initial_purchase_data' => [
                'price_per_unit' => $initialPurchasePrice,
                'quantity' => $initialPurchaseQuantity,
                'total_cost' => $initialPurchaseTotalCost,
                'date' => $initialPurchaseDate ? $initialPurchaseDate->format('d/m/Y') : '-',
                'found' => $initialPurchaseItem !== null,
            ],
        ]);
    }

    public function exportLivestockCost(Request $request)
    {
        // Validate request
        $request->validate([
            'farm' => 'required',
            'tanggal' => 'required|date'
        ]);

        // Get farm data
        $farm = Farm::findOrFail($request->farm);
        $tanggal = Carbon::parse($request->tanggal);

        // Get all active livestock for this farm on the specified date
        $livestocks = Livestock::where('farm_id', $farm->id)
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
            $costBreakdown = $costData?->cost_breakdown ?? [];

            $costs[] = [
                'kandang' => $livestock->coop->name ?? '-',
                'livestock' => $livestock->name,
                'umur' => Carbon::parse($livestock->start_date)->diffInDays($tanggal),
                'total_cost' => $totalCost,
                'cost_per_ayam' => $costPerAyam,
                'breakdown' => $costBreakdown,
            ];

            $totals['total_cost'] += $totalCost;
            $totals['total_ayam'] += $stockAwal;
        }

        // Calculate total cost per ayam keseluruhan
        $totals['total_cost_per_ayam'] = $totals['total_ayam'] > 0
            ? round($totals['total_cost'] / $totals['total_ayam'], 2)
            : 0;

        dd($costs, $totals);

        return view('pages.reports.livestock-cost', [
            'farm' => $farm->nama,
            'tanggal' => $tanggal->format('d M Y'),
            'costs' => $costs,
            'totals' => $totals,
        ]);
    }

    public function exportPerformancePartner(Request $request)
    {
        // dd($request->all());
        $penjualanData = TransaksiJual::where('kelompok_ternak_id', $request->periode)->get();
        $ternak = Ternak::where('id', $request->periode)->first();

        if ($ternak->status === 'Aktif') {
            return response()->json([
                'error' => 'Status Batch ' . trans('content.ternak', [], 'id') . ' Masih Aktif.'
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
            ->where('transaksi_jual.status', 'OK')
            ->whereNull('transaksi_jual.deleted_at')
            ->whereNull('transaksi_jual_details.deleted_at')
            ->sum('transaksi_jual_details.berat');

        if ($totalBerat) {
            $fcr = $konsumsiPakan / $totalBerat;
        } else {
            return response()->json([
                'error' => 'Data Penjualan Ternak Masih Belum Lengkap.'
            ], 404);
        }



        if ($isTernakMati) {
            // New IP calculation
            $mortalityRate = ($kematian / $ternak->populasi_awal) * 100;
            $kematian = $kematian;
            $persentaseKematian = ($kematian / $ternak->populasi_awal) * 100;
        } else {
            // New IP calculation
            $mortalityRate = ($ternak->populasi_awal - $penjualan) / $ternak->populasi_awal * 100;
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

        $data = ['beratJual' => $totalBerat, 'penjualan' => $penjualan, 'kematian' => $kematian, 'penjualanData' => $penjualanData, 'ternak' => $ternak, 'periode' => $periode, 'kematian' => $kematian];

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
        return view('pages.reports.performance_kemitraan', compact(['penjualanData', 'ternak', 'periode', 'kandang', 'kematian', 'persentaseKematian', 'penjualan', 'penjualanKilo', 'konsumsiPakan', 'umurPanen', 'fcr', 'ip', 'data']));
    }

    /**
     * Export Livestock Purchase Report
     */
    public function exportPembelianLivestock(Request $request)
    {
        // Validasi input
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'farm_id' => 'nullable|exists:farms,id',
            'supplier_id' => 'nullable|exists:partners,id',
            'expedition_id' => 'nullable|exists:expeditions,id',
            'status' => 'nullable|in:draft,confirmed,arrived,completed',
            'export_format' => 'nullable|in:html,excel,pdf,csv'
        ]);

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $exportFormat = $request->export_format ?? 'html';

        Log::info('Export Livestock Purchase Report', [
            'user_id' => auth()->id(),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'export_format' => $exportFormat,
            'filters' => $request->only(['farm_id', 'supplier_id', 'expedition_id', 'status'])
        ]);

        // Ambil data pembelian livestock
        $purchasesQuery = LivestockPurchase::with([
            'farm', 
            'supplier', 
            'expedition', 
            'livestockPurchaseItems.livestockBreed',
            'livestockPurchaseItems.unit'
        ])
            ->whereBetween('date', [$startDate, $endDate])
            ->when($request->farm_id, function ($query) use ($request) {
                return $query->where('farm_id', $request->farm_id);
            })
            ->when($request->supplier_id, function ($query) use ($request) {
                return $query->where('supplier_id', $request->supplier_id);
            })
            ->when($request->expedition_id, function ($query) use ($request) {
                return $query->where('expedition_id', $request->expedition_id);
            })
            ->when($request->status, function ($query) use ($request) {
                return $query->where('status', $request->status);
            })
            ->orderBy('date', 'asc')
            ->orderBy('invoice_number', 'asc');

        $purchases = $purchasesQuery->get();

        if ($purchases->isEmpty()) {
            Log::warning('No Livestock Purchase data found for export', [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'filters' => $request->only(['farm_id', 'supplier_id', 'expedition_id', 'status'])
            ]);

            return response()->json([
                'error' => 'Tidak ada data pembelian livestock untuk periode ' . 
                          $startDate->format('d-M-Y') . ' s.d. ' . $endDate->format('d-M-Y')
            ], 404);
        }

        // Hitung summary data
        $summary = [
            'period' => $startDate->format('d-M-Y') . ' s.d. ' . $endDate->format('d-M-Y'),
            'total_purchases' => $purchases->count(),
            'total_suppliers' => $purchases->unique('supplier_id')->count(),
            'total_farms' => $purchases->unique('farm_id')->count(),
            'total_value' => $purchases->sum(function ($purchase) {
                return $purchase->livestockPurchaseItems->sum(function ($item) {
                    return $item->quantity * $item->price_per_unit;
                });
            }),
            'total_quantity' => $purchases->sum(function ($purchase) {
                return $purchase->livestockPurchaseItems->sum('quantity');
            }),
            'by_status' => $purchases->groupBy('status')->map->count(),
            'by_farm' => $purchases->groupBy('farm.name')->map->count(),
            'by_supplier' => $purchases->groupBy('supplier.name')->map->count()
        ];

        $exportData = [
            'purchases' => $purchases,
            'summary' => $summary,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'farm' => $request->farm_id ? Farm::find($request->farm_id) : null,
                'supplier' => $request->supplier_id ? Partner::find($request->supplier_id) : null,
                'expedition' => $request->expedition_id ? Expedition::find($request->expedition_id) : null,
                'status' => $request->status
            ]
        ];

        // Route ke format export yang sesuai
        switch ($exportFormat) {
            case 'excel':
                return $this->exportLivestockPurchaseToExcel($exportData);
            case 'pdf':
                return $this->exportLivestockPurchaseToPdf($exportData);
            case 'csv':
                return $this->exportLivestockPurchaseToCsv($exportData);
            default:
                return $this->exportLivestockPurchaseToHtml($exportData);
        }
    }

    /**
     * Export Feed Purchase Report
     */
    public function exportPembelianPakan(Request $request)
    {
        // Validasi input
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'farm_id' => 'nullable|exists:farms,id',
            'livestock_id' => 'nullable|exists:livestocks,id',
            'supplier_id' => 'nullable|exists:partners,id',
            'feed_id' => 'nullable|exists:feeds,id',
            'status' => 'nullable|in:draft,confirmed,arrived,completed',
            'export_format' => 'nullable|in:html,excel,pdf,csv'
        ]);

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $exportFormat = $request->export_format ?? 'html';

        Log::info('Export Feed Purchase Report', [
            'user_id' => auth()->id(),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'export_format' => $exportFormat,
            'filters' => $request->only(['farm_id', 'livestock_id', 'supplier_id', 'feed_id', 'status'])
        ]);

        // Ambil data pembelian pakan
        $batchesQuery = FeedPurchaseBatch::with([
            'supplier',
            'expedition',
            'feedPurchases.livestock.farm',
            'feedPurchases.livestock.coop',
            'feedPurchases.feed',
            'feedPurchases.unit'
        ])
            ->whereBetween('date', [$startDate, $endDate])
            ->when($request->supplier_id, function ($query) use ($request) {
                return $query->where('supplier_id', $request->supplier_id);
            })
            ->when($request->status, function ($query) use ($request) {
                return $query->where('status', $request->status);
            })
            ->when($request->farm_id || $request->livestock_id || $request->feed_id, function ($query) use ($request) {
                return $query->whereHas('feedPurchases', function ($q) use ($request) {
                    if ($request->farm_id) {
                        $q->whereHas('livestock', function ($subQ) use ($request) {
                            $subQ->where('farm_id', $request->farm_id);
                        });
                    }
                    if ($request->livestock_id) {
                        $q->where('livestock_id', $request->livestock_id);
                    }
                    if ($request->feed_id) {
                        $q->where('feed_id', $request->feed_id);
                    }
                });
            })
            ->orderBy('date', 'asc')
            ->orderBy('invoice_number', 'asc');

        $batches = $batchesQuery->get();

        if ($batches->isEmpty()) {
            Log::warning('No Feed Purchase data found for export', [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'filters' => $request->only(['farm_id', 'livestock_id', 'supplier_id', 'feed_id', 'status'])
            ]);

            return response()->json([
                'error' => 'Tidak ada data pembelian pakan untuk periode ' . 
                          $startDate->format('d-M-Y') . ' s.d. ' . $endDate->format('d-M-Y')
            ], 404);
        }

        // Hitung summary data
        $summary = [
            'period' => $startDate->format('d-M-Y') . ' s.d. ' . $endDate->format('d-M-Y'),
            'total_batches' => $batches->count(),
            'total_purchases' => $batches->sum(function ($batch) {
                return $batch->feedPurchases->count();
            }),
            'total_suppliers' => $batches->unique('supplier_id')->count(),
            'total_farms' => $batches->flatMap(function ($batch) {
                return $batch->feedPurchases->pluck('livestock.farm_id');
            })->unique()->count(),
            'total_value' => $batches->sum(function ($batch) {
                return $batch->feedPurchases->sum(function ($purchase) {
                    return $purchase->quantity * $purchase->price_per_unit;
                }) + $batch->expedition_fee;
            }),
            'total_quantity' => $batches->sum(function ($batch) {
                return $batch->feedPurchases->sum('converted_quantity');
            }),
            'by_status' => $batches->groupBy('status')->map->count(),
            'by_supplier' => $batches->groupBy('supplier.name')->map->count(),
            'by_feed' => $batches->flatMap->feedPurchases->groupBy('feed.name')->map->count()
        ];

        $exportData = [
            'batches' => $batches,
            'summary' => $summary,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'farm' => $request->farm_id ? Farm::find($request->farm_id) : null,
                'livestock' => $request->livestock_id ? Livestock::find($request->livestock_id) : null,
                'supplier' => $request->supplier_id ? Partner::find($request->supplier_id) : null,
                'feed' => $request->feed_id ? Feed::find($request->feed_id) : null,
                'status' => $request->status
            ]
        ];

        // Route ke format export yang sesuai
        switch ($exportFormat) {
            case 'excel':
                return $this->exportFeedPurchaseToExcel($exportData);
            case 'pdf':
                return $this->exportFeedPurchaseToPdf($exportData);
            case 'csv':
                return $this->exportFeedPurchaseToCsv($exportData);
            default:
                return $this->exportFeedPurchaseToHtml($exportData);
        }
    }

    /**
     * Export Supply Purchase Report
     */
    public function exportPembelianSupply(Request $request)
    {
        // Validasi input
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'farm_id' => 'nullable|exists:farms,id',
            'livestock_id' => 'nullable|exists:livestocks,id',
            'supplier_id' => 'nullable|exists:partners,id',
            'supply_id' => 'nullable|exists:supplies,id',
            'status' => 'nullable|in:draft,confirmed,arrived,completed',
            'export_format' => 'nullable|in:html,excel,pdf,csv'
        ]);

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $exportFormat = $request->export_format ?? 'html';

        Log::info('Export Supply Purchase Report', [
            'user_id' => auth()->id(),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'export_format' => $exportFormat,
            'filters' => $request->only(['farm_id', 'livestock_id', 'supplier_id', 'supply_id', 'status'])
        ]);

        // Ambil data pembelian supply
        $batchesQuery = SupplyPurchaseBatch::with([
            'supplier',
            'expedition',
            'supplyPurchases.livestock.farm',
            'supplyPurchases.livestock.coop',
            'supplyPurchases.supply',
            'supplyPurchases.unit'
        ])
            ->whereBetween('date', [$startDate, $endDate])
            ->when($request->supplier_id, function ($query) use ($request) {
                return $query->where('supplier_id', $request->supplier_id);
            })
            ->when($request->status, function ($query) use ($request) {
                return $query->where('status', $request->status);
            })
            ->when($request->farm_id || $request->livestock_id || $request->supply_id, function ($query) use ($request) {
                return $query->whereHas('supplyPurchases', function ($q) use ($request) {
                    if ($request->farm_id) {
                        $q->whereHas('livestock', function ($subQ) use ($request) {
                            $subQ->where('farm_id', $request->farm_id);
                        });
                    }
                    if ($request->livestock_id) {
                        $q->where('livestock_id', $request->livestock_id);
                    }
                    if ($request->supply_id) {
                        $q->where('supply_id', $request->supply_id);
                    }
                });
            })
            ->orderBy('date', 'asc')
            ->orderBy('invoice_number', 'asc');

        $batches = $batchesQuery->get();

        if ($batches->isEmpty()) {
            Log::warning('No Supply Purchase data found for export', [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'filters' => $request->only(['farm_id', 'livestock_id', 'supplier_id', 'supply_id', 'status'])
            ]);

            return response()->json([
                'error' => 'Tidak ada data pembelian supply untuk periode ' . 
                          $startDate->format('d-M-Y') . ' s.d. ' . $endDate->format('d-M-Y')
            ], 404);
        }

        // Hitung summary data
        $summary = [
            'period' => $startDate->format('d-M-Y') . ' s.d. ' . $endDate->format('d-M-Y'),
            'total_batches' => $batches->count(),
            'total_purchases' => $batches->sum(function ($batch) {
                return $batch->supplyPurchases->count();
            }),
            'total_suppliers' => $batches->unique('supplier_id')->count(),
            'total_farms' => $batches->flatMap(function ($batch) {
                return $batch->supplyPurchases->pluck('livestock.farm_id');
            })->unique()->count(),
            'total_value' => $batches->sum(function ($batch) {
                return $batch->supplyPurchases->sum(function ($purchase) {
                    return $purchase->quantity * $purchase->price_per_unit;
                }) + $batch->expedition_fee;
            }),
            'total_quantity' => $batches->sum(function ($batch) {
                return $batch->supplyPurchases->sum('converted_quantity');
            }),
            'by_status' => $batches->groupBy('status')->map->count(),
            'by_supplier' => $batches->groupBy('supplier.name')->map->count(),
            'by_supply' => $batches->flatMap->supplyPurchases->groupBy('supply.name')->map->count()
        ];

        $exportData = [
            'batches' => $batches,
            'summary' => $summary,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'farm' => $request->farm_id ? Farm::find($request->farm_id) : null,
                'livestock' => $request->livestock_id ? Livestock::find($request->livestock_id) : null,
                'supplier' => $request->supplier_id ? Partner::find($request->supplier_id) : null,
                'supply' => $request->supply_id ? Supply::find($request->supply_id) : null,
                'status' => $request->status
            ]
        ];

        // Route ke format export yang sesuai
        switch ($exportFormat) {
            case 'excel':
                return $this->exportSupplyPurchaseToExcel($exportData);
            case 'pdf':
                return $this->exportSupplyPurchaseToPdf($exportData);
            case 'csv':
                return $this->exportSupplyPurchaseToCsv($exportData);
            default:
                return $this->exportSupplyPurchaseToHtml($exportData);
        }
    }

    // Helper methods for Livestock Purchase Export
    private function exportLivestockPurchaseToHtml($data)
    {
        return view('pages.reports.pembelian-livestock', $data);
    }

    private function exportLivestockPurchaseToExcel($data)
    {
        // Implement Excel export logic
        return response()->json(['message' => 'Excel export not implemented yet'], 501);
    }

    private function exportLivestockPurchaseToPdf($data)
    {
        // Implement PDF export logic  
        return response()->json(['message' => 'PDF export not implemented yet'], 501);
    }

    private function exportLivestockPurchaseToCsv($data)
    {
        // Implement CSV export logic
        return response()->json(['message' => 'CSV export not implemented yet'], 501);
    }

    // Helper methods for Feed Purchase Export
    private function exportFeedPurchaseToHtml($data)
    {
        return view('pages.reports.pembelian-pakan', $data);
    }

    private function exportFeedPurchaseToExcel($data)
    {
        // Implement Excel export logic
        return response()->json(['message' => 'Excel export not implemented yet'], 501);
    }

    private function exportFeedPurchaseToPdf($data)
    {
        // Implement PDF export logic
        return response()->json(['message' => 'PDF export not implemented yet'], 501);
    }

    private function exportFeedPurchaseToCsv($data)
    {
        // Implement CSV export logic
        return response()->json(['message' => 'CSV export not implemented yet'], 501);
    }

    // Helper methods for Supply Purchase Export
    private function exportSupplyPurchaseToHtml($data)
    {
        return view('pages.reports.pembelian-supply', $data);
    }

    private function exportSupplyPurchaseToExcel($data)
    {
        // Implement Excel export logic
        return response()->json(['message' => 'Excel export not implemented yet'], 501);
    }

    private function exportSupplyPurchaseToPdf($data)
    {
        // Implement PDF export logic
        return response()->json(['message' => 'PDF export not implemented yet'], 501);
    }

    private function exportSupplyPurchaseToCsv($data)
    {
        // Implement CSV export logic
        return response()->json(['message' => 'CSV export not implemented yet'], 501);
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
        return view('pages.reports.performance_kemitraan', compact(['penjualanData', 'ternak', 'periode', 'kandang', 'kematian', 'persentaseKematian', 'penjualan', 'penjualanKilo', 'konsumsiPakan', 'umurPanen', 'fcr', 'ip', 'data']));
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

    public function exportBatchWorker(Request $request)
    {
        // Validate request
        // $request->validate([
        //     // 'start_date' => 'required|date',
        //     // 'end_date' => 'required|date',
        //     'farm_id' => 'nullable|exists:farms,id',
        //     'worker_id' => 'nullable|exists:workers,id',
        //     'status' => 'nullable|in:active,completed,terminated'
        // ]);

        // Get data
        $query = BatchWorker::query()
            ->with(['livestock', 'worker', 'creator', 'updater'])
            ->when($request->start_date, function ($query) use ($request) {
                return $query->where('start_date', '>=', $request->start_date);
            })
            ->when($request->end_date, function ($query) use ($request) {
                return $query->where('start_date', '<=', $request->end_date);
            })
            ->when($request->worker_id, function ($query) use ($request) {
                return $query->where('worker_id', $request->worker_id);
            })
            ->when($request->status, function ($query) use ($request) {
                return $query->where('status', $request->status);
            });

        $data = $query->get();

        // Get farm name
        $farm = $request->farm_id ? Farm::find($request->farm_id)->name : 'Semua Farm';

        // Get worker name
        $worker = $request->worker_id ? Worker::find($request->worker_id)->name : 'Semua Worker';

        // Set locale to Indonesian
        setlocale(LC_TIME, 'id_ID');
        Carbon::setLocale('id');

        // Format dates
        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        // Calculate summary
        $summary = [
            'total_assignments' => $data->count(),
            'unique_workers' => $data->unique('worker_id')->count(),
            'unique_batches' => $data->unique('livestock_id')->count(),
            'unique_farms' => $data->unique('farm_id')->count()
        ];

        // Group by status for detailed summary
        $statusSummary = $data->groupBy('status')
            ->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'workers' => $group->unique('worker_id')->count(),
                    'batches' => $group->unique('livestock_id')->count(),
                    'farms' => $group->unique('farm_id')->count()
                ];
            });

        return view('pages.reports.batch-worker-pdf', [
            'data' => $data,
            'startDate' => $startDate->format('d F Y'),
            'endDate' => $endDate->format('d F Y'),
            'farm' => $farm,
            'worker' => $worker,
            'status' => $request->status ? ucfirst($request->status) : 'Semua Status',
            'summary' => $summary,
            'statusSummary' => $statusSummary,
            'diketahui' => 'RIA NARSO',
            'dibuat' => 'HENDRA'
        ]);
    }
}

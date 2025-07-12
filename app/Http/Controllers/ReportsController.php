<?php

namespace App\Http\Controllers;

use App\Models\Reports;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\Report\DaillyReportExcelExportService;
use App\Services\Report\LivestockDepletionReportService;
use App\Services\Report\ReportDataAccessService;
use App\Services\Report\ReportIndexService;
use App\Services\Report\ReportCalculationService;
use App\Services\Report\ReportAggregationService;
use App\Services\Report\HarianReportService;
use App\Services\Report\PerformanceReportService;
use App\Services\Report\BatchWorkerReportService;
use App\Services\Report\CostReportService;
use App\Services\Report\PurchaseReportService;
use App\Services\Report\SalesReportService;
use App\Services\Report\LivestockCostReportService;
use App\Services\Report\ReportIndexOptimizationService;
use App\Services\Report\SupplyUsageReportService;


class ReportsController extends Controller
{
    protected $daillyReportExcelExportService;
    protected $depletionReportService;
    protected $dataAccessService;
    protected $indexService;
    protected $calculationService;
    protected $aggregationService;
    protected $harianReportService;
    protected $performanceReportService;
    protected $batchWorkerReportService;
    protected $costReportService;
    protected $purchaseReportService;
    protected $salesReportService;
    protected $livestockCostReportService;
    protected $indexOptimizationService;
    protected $supplyUsageReportService;

    public function __construct(
        DaillyReportExcelExportService $daillyReportExcelExportService,
        LivestockDepletionReportService $depletionReportService,
        ReportDataAccessService $dataAccessService,
        ReportIndexService $indexService,
        ReportCalculationService $calculationService,
        ReportAggregationService $aggregationService,
        HarianReportService $harianReportService,
        PerformanceReportService $performanceReportService,
        BatchWorkerReportService $batchWorkerReportService,
        CostReportService $costReportService,
        PurchaseReportService $purchaseReportService,
        SalesReportService $salesReportService,
        LivestockCostReportService $livestockCostReportService,
        ReportIndexOptimizationService $indexOptimizationService,
        SupplyUsageReportService $supplyUsageReportService
    ) {
        $this->daillyReportExcelExportService = $daillyReportExcelExportService;
        $this->depletionReportService = $depletionReportService;
        $this->dataAccessService = $dataAccessService;
        $this->indexService = $indexService;
        $this->calculationService = $calculationService;
        $this->aggregationService = $aggregationService;
        $this->harianReportService = $harianReportService;
        $this->performanceReportService = $performanceReportService;
        $this->batchWorkerReportService = $batchWorkerReportService;
        $this->costReportService = $costReportService;
        $this->purchaseReportService = $purchaseReportService;
        $this->salesReportService = $salesReportService;
        $this->livestockCostReportService = $livestockCostReportService;
        $this->indexOptimizationService = $indexOptimizationService;
        $this->supplyUsageReportService = $supplyUsageReportService;
    }

    /**
     * Display Harian report index page
     * Business Logic: Prepare livestock, farms, and coops data for daily report selection
     */
    public function indexHarian()
    {
        $data = $this->indexService->prepareHarianReportData();

        return view('pages.reports.index_report_harian', [
            'farms' => $data['farms'],
            'coops' => $data['coops'],
            'livestock' => $data['livestockForView']
        ]);
    }

    public function indexBatchWorker()
    {
        try {
            $data = $this->indexOptimizationService->prepareCommonIndexData('livestock');
            return view('pages.reports.index_report_batch_worker', $data);
        } catch (\Exception $e) {
            Log::error('Error in indexBatchWorker: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat memuat data: ' . $e->getMessage());
        }
    }

    public function indexDailyCost()
    {
        try {
            $data = $this->indexOptimizationService->prepareCommonIndexData('livestock');
            return view('pages.reports.index_report_livestock_cost', $data);
        } catch (\Exception $e) {
            Log::error('Error in indexDailyCost: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat memuat data: ' . $e->getMessage());
        }
    }

    public function indexPenjualan()
    {
        try {
            $data = $this->indexOptimizationService->prepareCommonIndexData('ternak');
            return view('pages.reports.index_report_penjualan', $data);
        } catch (\Exception $e) {
            Log::error('Error in indexPenjualan: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat memuat data: ' . $e->getMessage());
        }
    }

    public function indexPerformaMitra()
    {
        try {
            $data = $this->indexOptimizationService->prepareTernakIndexDataWithAdditional();
            return view('pages.reports.index_report_performa_mitra', $data);
        } catch (\Exception $e) {
            Log::error('Error in indexPerformaMitra: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat memuat data: ' . $e->getMessage());
        }
    }

    public function indexPerforma()
    {
        try {
            $data = $this->indexOptimizationService->prepareLivestockIndexDataWithAdditional();
            return view('pages.reports.index_report_performa', $data);
        } catch (\Exception $e) {
            Log::error('Error in indexPerforma: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat memuat data: ' . $e->getMessage());
        }
    }

    public function indexInventory()
    {
        try {
            $data = $this->indexOptimizationService->prepareInventoryIndexData();
            return view('pages.reports.index_report_inventory', $data);
        } catch (\Exception $e) {
            Log::error('Error in indexInventory: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat memuat data: ' . $e->getMessage());
        }
    }

    /**
     * Display Livestock Purchase Report Index
     */
    public function indexPembelianLivestock()
    {
        try {
            $data = $this->indexOptimizationService->preparePurchaseIndexData('livestock');
            return view('pages.reports.index_report_pembelian_livestock', $data);
        } catch (\Exception $e) {
            Log::error('Error in indexPembelianLivestock: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat memuat data: ' . $e->getMessage());
        }
    }

    /**
     * Display Feed Purchase Report Index
     */
    public function indexPembelianPakan()
    {
        try {
            $data = $this->indexOptimizationService->preparePurchaseIndexData('feed');
            return view('pages.reports.index_report_pembelian_pakan', $data);
        } catch (\Exception $e) {
            Log::error('Error in indexPembelianPakan: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat memuat data: ' . $e->getMessage());
        }
    }

    /**
     * Display Supply Purchase Report Index  
     */
    public function indexPembelianSupply()
    {
        try {
            $data = $this->indexOptimizationService->preparePurchaseIndexData('supply');
            return view('pages.reports.index_report_pembelian_supply', $data);
        } catch (\Exception $e) {
            Log::error('Error in indexPembelianSupply: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat memuat data: ' . $e->getMessage());
        }
    }

    /**
     * Display Supply Usage Report Index
     */
    public function indexSupplyUsage()
    {
        try {
            $data = $this->indexOptimizationService->prepareCommonIndexData('livestock');

            // Add supplies data for dropdown
            $supplies = \App\Models\Supply::query();
            $this->dataAccessService->applyCompanyFilter($supplies);
            $data['supplies'] = $supplies->get();

            // dd($data);
            return view('pages.reports.index_report_supply_usage', $data);
        } catch (\Exception $e) {
            Log::error('Error in indexSupplyUsage: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat memuat data: ' . $e->getMessage());
        }
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
        try {
            $format = $request->export_format ?? 'html';
            return $this->salesReportService->exportSalesReport($request, $format);
        } catch (\Exception $e) {
            Log::error('Error exporting penjualan report: ' . $e->getMessage());
            Log::debug('Stack trace: ' . $e->getTraceAsString());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat mengekspor laporan: ' . $e->getMessage());
        }
    }

    public function exportHarian(Request $request)
    {
        try {
            // Use HarianReportService for complete export handling
            $format = $request->export_format ?? 'html';
            return $this->harianReportService->exportHarianReport($request, $format);
        } catch (\Exception $e) {
            Log::error('Error exporting harian report: ' . $e->getMessage());
            Log::debug('Stack trace: ' . $e->getTraceAsString());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat mengekspor laporan: ' . $e->getMessage());
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
        try {
            // Validate and generate report using service
            $params = $this->costReportService->validateParams($request->all());
            $reportData = $this->costReportService->generateDailyCostReport($params);

            // Export in requested format
            $format = $request->format ?? 'html';
            return $this->costReportService->export($reportData, $format);
        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => $e->getMessage()], 404);
            }
            return back()->with('error', $e->getMessage());
        }
    }

    public function exportLivestockCost(Request $request)
    {
        try {
            $format = $request->export_format ?? 'html';
            return $this->livestockCostReportService->exportLivestockCostReport($request, $format);
        } catch (\Exception $e) {
            Log::error('Error exporting livestock cost report: ' . $e->getMessage());
            Log::debug('Stack trace: ' . $e->getTraceAsString());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat mengekspor laporan: ' . $e->getMessage());
        }
    }

    public function exportPerformancePartner(Request $request)
    {
        try {
            $format = $request->export_format ?? 'html';
            return $this->salesReportService->exportPerformancePartnerReport($request, $format);
        } catch (\Exception $e) {
            Log::error('Error exporting performance partner report: ' . $e->getMessage());
            Log::debug('Stack trace: ' . $e->getTraceAsString());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat mengekspor laporan: ' . $e->getMessage());
        }
    }

    public function exportPembelianLivestock(Request $request)
    {
        try {
            // Use PurchaseReportService for complete export handling
            return $this->purchaseReportService->exportLivestockPurchaseReport($request);
        } catch (\Exception $e) {
            Log::error('Error exporting livestock purchase report: ' . $e->getMessage());
            Log::debug('Stack trace: ' . $e->getTraceAsString());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat mengekspor laporan: ' . $e->getMessage());
        }
    }

    /**
     * Export Feed Purchase Report
     */
    public function exportPembelianPakan(Request $request)
    {
        try {
            // Use PurchaseReportService for complete export handling
            return $this->purchaseReportService->exportFeedPurchaseReport($request);
        } catch (\Exception $e) {
            Log::error('Error exporting feed purchase report: ' . $e->getMessage());
            Log::debug('Stack trace: ' . $e->getTraceAsString());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat mengekspor laporan: ' . $e->getMessage());
        }
    }

    /**
     * Export Supply Purchase Report
     */
    public function exportPembelianSupply(Request $request)
    {
        try {
            // Use PurchaseReportService for complete export handling
            return $this->purchaseReportService->exportSupplyPurchaseReport($request);
        } catch (\Exception $e) {
            Log::error('Error exporting supply purchase report: ' . $e->getMessage());
            Log::debug('Stack trace: ' . $e->getTraceAsString());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat mengekspor laporan: ' . $e->getMessage());
        }
    }

    /**
     * Export Supply Usage Report
     */
    public function exportSupplyUsage(Request $request)
    {
        try {
            // Use SupplyUsageReportService for complete export handling
            return $this->supplyUsageReportService->exportSupplyUsageReport($request);
        } catch (\Exception $e) {
            Log::error('Error exporting supply usage report: ' . $e->getMessage());
            Log::debug('Stack trace: ' . $e->getTraceAsString());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat mengekspor laporan: ' . $e->getMessage());
        }
    }

    public function exportPerformance(Request $request)
    {
        return $this->performanceReportService->exportPerformanceReport($request);

        try {
            // Use PerformanceReportService for complete export handling
            return $this->performanceReportService->exportPerformanceReport($request);
        } catch (\Exception $e) {
            Log::error('Error exporting performance report: ' . $e->getMessage());
            Log::debug('Stack trace: ' . $e->getTraceAsString());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat mengekspor laporan: ' . $e->getMessage());
        }
    }

    public function exportBatchWorker(Request $request)
    {
        try {
            // Validate and generate report using service
            $params = $this->batchWorkerReportService->validateParams($request->all());
            $reportData = $this->batchWorkerReportService->generateBatchWorkerReport($params);

            // Export in requested format
            $format = $request->format ?? 'html';
            return $this->batchWorkerReportService->export($reportData, $format);
        } catch (\Exception $e) {
            Log::error("[" . __CLASS__ . "::" . __FUNCTION__ . "] Error: " . $e->getMessage());
            Log::debug("[" . __CLASS__ . "::" . __FUNCTION__ . "] Stack trace: " . $e->getTraceAsString());
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
}

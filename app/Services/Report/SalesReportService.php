<?php

namespace App\Services\Report;

use App\Models\TernakJual;
use App\Models\Ternak;
use App\Models\TransaksiJual;
use App\Models\LivestockCost;
use App\Models\Farm;
use App\Models\Livestock;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class SalesReportService
{
    protected $dataAccessService;

    public function __construct(ReportDataAccessService $dataAccessService)
    {
        $this->dataAccessService = $dataAccessService;
    }

    /**
     * Generate sales report data
     * 
     * @param Request $request
     * @return array
     */
    public function generateSalesReport(Request $request)
    {
        try {
            $request->validate([
                'periode' => 'required|exists:ternaks,id'
            ]);

            $ternakId = $request->periode;
            $ternak = Ternak::with(['kandang', 'kematianTernak', 'penjualanTernaks'])->findOrFail($ternakId);

            // Check if ternak is still active
            if ($ternak->status === 'Aktif') {
                throw new \Exception('Status Batch ' . trans('content.ternak', [], 'id') . ' Masih Aktif.');
            }

            // Get sales data
            $data = TernakJual::where('kelompok_ternak_id', $ternakId)
                ->where('status', 'OK')
                ->get();

            $penjualanData = TransaksiJual::where('kelompok_ternak_id', $ternakId)
                ->where('status', 'OK')
                ->orderBy('faktur', 'ASC')
                ->get();

            if ($penjualanData->isEmpty()) {
                throw new \Exception('Data penjualan belum lengkap');
            }

            // Format period
            $startDate = Carbon::parse($ternak->start_date);
            $endDate = Carbon::parse($ternak->end_date);
            $periode = $startDate->translatedFormat('F') . ' s.d. ' . $endDate->translatedFormat('F Y');

            return [
                'data' => $data,
                'ternak' => $ternak,
                'kandang' => $ternak->kandang->nama,
                'periode' => $periode,
                'penjualanData' => $penjualanData
            ];
        } catch (\Exception $e) {
            Log::error('Error generating sales report: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate performance partner report
     * 
     * @param Request $request
     * @return array
     */
    public function generatePerformancePartnerReport(Request $request)
    {
        try {
            $request->validate([
                'periode' => 'required|exists:ternaks,id',
                'tanggal_surat' => 'nullable|date',
                'integrasi' => 'nullable|array'
            ]);

            $ternakId = $request->periode;
            $ternak = Ternak::with([
                'kandang',
                'kematianTernak',
                'penjualanTernaks',
                'transaksiHarians.transaksiHarianDetails.item.itemCategory',
                'transaksiJuals.transaksiJualDetails'
            ])->findOrFail($ternakId);

            // Check if ternak is still active
            if ($ternak->status === 'Aktif') {
                throw new \Exception('Status Batch ' . trans('content.ternak', [], 'id') . ' Masih Aktif.');
            }

            $existingData = $ternak->data ?: [];
            $isTernakMati = in_array('ternak_mati', $request->input('integrasi', []));

            // Calculate metrics
            $metrics = $this->calculatePerformanceMetrics($ternak, $isTernakMati);

            // Process tanggal surat
            $tanggalSurat = $this->processTanggalSurat($request, $ternak, $existingData);

            // Calculate costs
            $costs = $this->calculateCosts($ternak);

            // Build final data
            $data = array_merge($metrics, $costs, [
                'tanggal_surat' => $tanggalSurat,
                'bonus' => $existingData['bonus'] ?? null,
                'administrasi' => $existingData['administrasi'] ?? []
            ]);

            return [
                'data' => $data,
                'ternak' => $ternak,
                'kandang' => $ternak->kandang->nama,
                'periode' => $metrics['periode'],
                'penjualanData' => $metrics['penjualanData']
            ];
        } catch (\Exception $e) {
            Log::error('Error generating performance partner report: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Calculate performance metrics
     * 
     * @param Ternak $ternak
     * @param bool $isTernakMati
     * @return array
     */
    protected function calculatePerformanceMetrics($ternak, $isTernakMati)
    {
        // Set locale
        setlocale(LC_TIME, 'id_ID');
        Carbon::setLocale('id');

        // Format period
        $startDate = Carbon::parse($ternak->start_date);
        $endDate = Carbon::parse($ternak->end_date);
        $periode = $startDate->translatedFormat('F') . ' s.d. ' . $endDate->translatedFormat('F Y');

        // Get sales data
        $penjualanData = TransaksiJual::where('kelompok_ternak_id', $ternak->id)
            ->where('status', 'OK')
            ->get();

        // Calculate basic metrics
        $kematian = $ternak->kematianTernak()->whereNull('deleted_at')->sum("quantity");
        $penjualan = $ternak->penjualanTernaks()->whereNull('deleted_at')->sum("quantity");

        // Calculate consumption and weight
        $konsumsiPakan = $this->calculateFeedConsumption($ternak);
        $totalBerat = $this->calculateTotalWeight($ternak);

        if ($totalBerat <= 0) {
            throw new \Exception('Data Penjualan Ternak Masih Belum Lengkap.');
        }

        // Calculate performance metrics
        $umurPanen = $penjualanData->sum(fn($data) => $data->detail->umur * $data->jumlah) / $penjualanData->sum('jumlah');
        $penjualanKilo = $ternak->penjualanTernaks()->whereNull('deleted_at')->sum("total_berat") / $ternak->penjualanTernaks()->whereNull('deleted_at')->sum("quantity");
        $fcr = $konsumsiPakan / $totalBerat;

        // Calculate mortality rate
        if ($isTernakMati) {
            $mortalityRate = ($kematian / $ternak->populasi_awal) * 100;
            $persentaseKematian = ($kematian / $ternak->populasi_awal) * 100;
        } else {
            $mortalityRate = ($ternak->populasi_awal - $penjualan) / $ternak->populasi_awal * 100;
            $kematian = $ternak->populasi_awal - $penjualan;
            $persentaseKematian = ($kematian / $ternak->populasi_awal) * 100;
        }

        // Calculate IP
        $averageWeight = $totalBerat / $penjualan;
        $ageInDays = $umurPanen;
        $ip = (100 - $mortalityRate) * ($averageWeight / ($fcr * $ageInDays)) * 100;
        $ip = round($ip, 2);

        return [
            'beratJual' => $totalBerat,
            'penjualan' => $penjualan,
            'kematian' => $kematian,
            'penjualanData' => $penjualanData,
            'periode' => $periode,
            'kematian' => $kematian,
            'persentaseKematian' => $persentaseKematian,
            'penjualanKilo' => $penjualanKilo,
            'konsumsiPakan' => $konsumsiPakan,
            'umurPanen' => $umurPanen,
            'fcr' => $fcr,
            'ip' => $ip
        ];
    }

    /**
     * Calculate feed consumption
     * 
     * @param Ternak $ternak
     * @return float
     */
    protected function calculateFeedConsumption($ternak)
    {
        return $ternak->transaksiHarians()
            ->join('transaksi_harian_details', 'transaksi_harians.id', '=', 'transaksi_harian_details.transaksi_id')
            ->join('items', 'transaksi_harian_details.item_id', '=', 'items.id')
            ->join('item_categories', 'items.category_id', '=', 'item_categories.id')
            ->where('item_categories.name', 'Pakan')
            ->whereNull('transaksi_harians.deleted_at')
            ->whereNull('transaksi_harian_details.deleted_at')
            ->sum('transaksi_harian_details.quantity');
    }

    /**
     * Calculate total weight
     * 
     * @param Ternak $ternak
     * @return float
     */
    protected function calculateTotalWeight($ternak)
    {
        return $ternak->transaksiJuals()
            ->join('transaksi_jual_details', 'transaksi_jual.id', '=', 'transaksi_jual_details.transaksi_jual_id')
            ->where('transaksi_jual.status', 'OK')
            ->whereNull('transaksi_jual.deleted_at')
            ->whereNull('transaksi_jual_details.deleted_at')
            ->sum('transaksi_jual_details.berat');
    }

    /**
     * Calculate costs
     * 
     * @param Ternak $ternak
     * @return array
     */
    protected function calculateCosts($ternak)
    {
        // Calculate feed costs
        $totalBiayaPakan = $this->calculateFeedCosts($ternak);
        $biayaPakanDetails = $this->calculateFeedCostDetails($ternak);

        // Calculate OVK costs
        $totalBiayaOvk = $this->calculateOvkCosts($ternak);

        // Calculate total HPP
        $biayaDoc = $ternak->populasi_awal * $ternak->harga_beli;
        $totalHpp = $biayaDoc + $totalBiayaPakan + $totalBiayaOvk;

        // Add bonus if exists
        $existingData = $ternak->data ?: [];
        if (isset($existingData['bonus'])) {
            $bonusTotal = is_array($existingData['bonus'])
                ? array_sum(array_column($existingData['bonus'], 'jumlah'))
                : $existingData['bonus']['jumlah'];
            $totalHpp += $bonusTotal;
        }

        // Calculate per-unit costs
        $penjualan = $ternak->penjualanTernaks()->whereNull('deleted_at')->sum("quantity");
        $totalBerat = $this->calculateTotalWeight($ternak);

        return [
            'totalBiayaPakan' => round($totalBiayaPakan, 2),
            'biayaPakanDetails' => $biayaPakanDetails,
            'totalBiayaOvk' => round($totalBiayaOvk, 2),
            'total_hpp' => $totalHpp,
            'hpp_per_ekor' => $penjualan > 0 ? $totalHpp / $penjualan : 0,
            'hpp_per_kg' => $totalBerat > 0 ? $totalHpp / $totalBerat : 0,
            'total_penghasilan' => ($totalBerat * $ternak->transaksiJuals()->where('status', 'OK')->avg('detail.harga_jual')) - $totalHpp,
            'penghasilan_per_ekor' => $penjualan > 0 ? (($totalBerat * $ternak->transaksiJuals()->where('status', 'OK')->avg('detail.harga_jual')) - $totalHpp) / $penjualan : 0
        ];
    }

    /**
     * Calculate feed costs
     * 
     * @param Ternak $ternak
     * @return float
     */
    protected function calculateFeedCosts($ternak)
    {
        return $ternak->transaksiHarians()
            ->join('transaksi_harian_details', 'transaksi_harians.id', '=', 'transaksi_harian_details.transaksi_id')
            ->join('items', 'transaksi_harian_details.item_id', '=', 'items.id')
            ->join('item_categories', 'items.category_id', '=', 'item_categories.id')
            ->where('item_categories.name', 'Pakan')
            ->whereNull('transaksi_harians.deleted_at')
            ->whereNull('transaksi_harian_details.deleted_at')
            ->sum(DB::raw('transaksi_harian_details.quantity * transaksi_harian_details.harga'));
    }

    /**
     * Calculate feed cost details
     * 
     * @param Ternak $ternak
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function calculateFeedCostDetails($ternak)
    {
        return $ternak->transaksiHarians()
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
    }

    /**
     * Calculate OVK costs
     * 
     * @param Ternak $ternak
     * @return float
     */
    protected function calculateOvkCosts($ternak)
    {
        return $ternak->transaksiHarians()
            ->join('transaksi_harian_details', 'transaksi_harians.id', '=', 'transaksi_harian_details.transaksi_id')
            ->join('items', 'transaksi_harian_details.item_id', '=', 'items.id')
            ->join('item_categories', 'items.category_id', '=', 'item_categories.id')
            ->where('item_categories.name', 'OVK')
            ->whereNull('transaksi_harians.deleted_at')
            ->whereNull('transaksi_harian_details.deleted_at')
            ->sum(DB::raw('transaksi_harian_details.quantity * transaksi_harian_details.harga'));
    }

    /**
     * Process tanggal surat
     * 
     * @param Request $request
     * @param Ternak $ternak
     * @param array $existingData
     * @return string
     */
    protected function processTanggalSurat($request, $ternak, $existingData)
    {
        if ($request->tanggal_surat) {
            $tanggalSurat = Carbon::parse($request->tanggal_surat)->translatedFormat('d F Y');

            // Update data if tanggal_surat is different
            if (
                !isset($existingData['administrasi']['tanggal_laporan']) ||
                $existingData['administrasi']['tanggal_laporan'] !== $request->tanggal_surat
            ) {

                $existingData['administrasi']['tanggal_laporan'] = $request->tanggal_surat;
                $ternak->update(['data' => json_encode($existingData)]);
            }
        } else {
            $tanggalSurat = Carbon::now()->translatedFormat('d F Y');
            $existingData['administrasi']['tanggal_laporan'] = $tanggalSurat;
            $ternak->update(['data' => json_encode($existingData)]);
        }

        return $tanggalSurat;
    }

    /**
     * Export sales report
     * 
     * @param Request $request
     * @param string $format
     * @return \Illuminate\Http\Response
     */
    public function exportSalesReport(Request $request, $format = 'html')
    {
        try {
            $reportData = $this->generateSalesReport($request);

            if ($format === 'html') {
                return view('pages.reports.penjualan_details', $reportData);
            } else {
                // Handle other formats (excel, pdf, csv)
                return $this->exportToFormat($reportData, $format);
            }
        } catch (\Exception $e) {
            Log::error('Error exporting sales report: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Export performance partner report
     * 
     * @param Request $request
     * @param string $format
     * @return \Illuminate\Http\Response
     */
    public function exportPerformancePartnerReport(Request $request, $format = 'html')
    {
        try {
            $reportData = $this->generatePerformancePartnerReport($request);

            if ($format === 'html') {
                return view('pages.reports.performance_kemitraan', $reportData);
            } else {
                // Handle other formats (excel, pdf, csv)
                return $this->exportToFormat($reportData, $format);
            }
        } catch (\Exception $e) {
            Log::error('Error exporting performance partner report: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Export to specific format
     * 
     * @param array $reportData
     * @param string $format
     * @return \Illuminate\Http\Response
     */
    protected function exportToFormat($reportData, $format)
    {
        // Implementation for different export formats
        switch ($format) {
            case 'excel':
                return $this->exportToExcel($reportData);
            case 'pdf':
                return $this->exportToPdf($reportData);
            case 'csv':
                return $this->exportToCsv($reportData);
            default:
                throw new \Exception('Unsupported export format: ' . $format);
        }
    }

    /**
     * Export to Excel
     * 
     * @param array $reportData
     * @return \Illuminate\Http\Response
     */
    protected function exportToExcel($reportData)
    {
        // Excel export implementation
        // This would use PhpSpreadsheet or similar library
        throw new \Exception('Excel export not implemented yet');
    }

    /**
     * Export to PDF
     * 
     * @param array $reportData
     * @return \Illuminate\Http\Response
     */
    protected function exportToPdf($reportData)
    {
        // PDF export implementation
        // This would use DomPDF or similar library
        throw new \Exception('PDF export not implemented yet');
    }

    /**
     * Export to CSV
     * 
     * @param array $reportData
     * @return \Illuminate\Http\Response
     */
    protected function exportToCsv($reportData)
    {
        // CSV export implementation
        throw new \Exception('CSV export not implemented yet');
    }
}

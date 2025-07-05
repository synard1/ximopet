<?php

namespace App\Services\Report;

use App\Models\LivestockCost;
use App\Models\Farm;
use App\Models\Livestock;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class LivestockCostReportService
{
    protected $dataAccessService;

    public function __construct(ReportDataAccessService $dataAccessService)
    {
        $this->dataAccessService = $dataAccessService;
    }

    /**
     * Generate livestock cost report
     * 
     * @param Request $request
     * @return array
     */
    public function generateLivestockCostReport(Request $request)
    {
        try {
            $request->validate([
                'farm' => 'required|exists:farms,id',
                'tanggal' => 'required|date'
            ]);

            $farm = Farm::findOrFail($request->farm);
            $tanggal = Carbon::parse($request->tanggal);

            // Get all active livestock for this farm on the specified date
            $livestocks = Livestock::with(['coop'])
                ->where('farm_id', $farm->id)
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

            return [
                'farm' => $farm->nama,
                'tanggal' => $tanggal->format('d M Y'),
                'costs' => $costs,
                'totals' => $totals,
            ];
        } catch (\Exception $e) {
            Log::error('Error generating livestock cost report: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Export livestock cost report
     * 
     * @param Request $request
     * @param string $format
     * @return \Illuminate\Http\Response
     */
    public function exportLivestockCostReport(Request $request, $format = 'html')
    {
        try {
            $reportData = $this->generateLivestockCostReport($request);

            if ($format === 'html') {
                return view('pages.reports.livestock-cost', $reportData);
            } else {
                // Handle other formats (excel, pdf, csv)
                return $this->exportToFormat($reportData, $format);
            }
        } catch (\Exception $e) {
            Log::error('Error exporting livestock cost report: ' . $e->getMessage());
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

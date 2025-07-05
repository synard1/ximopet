<?php

namespace App\Services\Report;

use App\Models\Farm;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Exception;

class ReportExportService
{
    protected $daillyReportExcelExportService;

    public function __construct(DaillyReportExcelExportService $daillyReportExcelExportService)
    {
        $this->daillyReportExcelExportService = $daillyReportExcelExportService;
    }

    /**
     * Export report data to specified format
     * 
     * @param array $data Report data
     * @param Farm $farm Farm model
     * @param Carbon $tanggal Date
     * @param string $reportType Report type (detail/simple)
     * @param string $exportFormat Export format (html/excel/pdf/csv)
     * @return mixed
     */
    public function export(array $data, Farm $farm, Carbon $tanggal, string $reportType, string $exportFormat)
    {
        Log::info('Exporting report data', [
            'farm_id' => $farm->id,
            'farm_name' => $farm->name,
            'tanggal' => $tanggal->format('Y-m-d'),
            'report_type' => $reportType,
            'export_format' => $exportFormat,
            'user_id' => Auth::id()
        ]);

        switch ($exportFormat) {
            case 'excel':
                return $this->exportToExcel($data, $farm, $tanggal, $reportType);
            case 'pdf':
                return $this->exportToPdf($data, $farm, $tanggal, $reportType);
            case 'csv':
                return $this->exportToCsv($data, $farm, $tanggal, $reportType);
            default:
                return $this->exportToHtml($data, $farm, $tanggal, $reportType);
        }
    }

    /**
     * Export to HTML format (existing view)
     * Extracted from ReportsController::exportToHtml()
     */
    private function exportToHtml(array $data, Farm $farm, Carbon $tanggal, string $reportType)
    {
        Log::debug('Exporting to HTML format', [
            'farm_name' => $farm->name,
            'tanggal' => $tanggal->format('Y-m-d'),
            'report_type' => $reportType
        ]);

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
     * Extracted from ReportsController::exportToExcel()
     */
    private function exportToExcel(array $data, Farm $farm, Carbon $tanggal, string $reportType)
    {
        Log::debug('Exporting to Excel format', [
            'farm_name' => $farm->name,
            'tanggal' => $tanggal->format('Y-m-d'),
            'report_type' => $reportType
        ]);

        return $this->daillyReportExcelExportService->exportToExcel($data, $farm, $tanggal, $reportType);
    }

    /**
     * Export to PDF format
     * Extracted from ReportsController::exportToPdf()
     */
    private function exportToPdf(array $data, Farm $farm, Carbon $tanggal, string $reportType)
    {
        try {
            $filename = 'laporan_harian_' . $farm->name . '_' . $tanggal->format('Y-m-d') . '_' . $reportType . '.pdf';

            Log::debug('Exporting to PDF format', [
                'farm_name' => $farm->name,
                'tanggal' => $tanggal->format('Y-m-d'),
                'report_type' => $reportType,
                'filename' => $filename
            ]);

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

            Log::info('PDF export completed successfully', [
                'filename' => $filename,
                'report_type' => $reportType
            ]);

            return $pdf->download($filename);
        } catch (Exception $e) {
            Log::error('PDF export failed, falling back to HTML', [
                'error' => $e->getMessage(),
                'farm_name' => $farm->name,
                'tanggal' => $tanggal->format('Y-m-d')
            ]);

            // Fallback to HTML view
            return $this->exportToHtml($data, $farm, $tanggal, $reportType);
        }
    }

    /**
     * Export to CSV format with structured table layout
     * Extracted from ReportsController::exportToCsv()
     */
    private function exportToCsv(array $data, Farm $farm, Carbon $tanggal, string $reportType, string $format = 'csv')
    {
        try {
            $extension = $format === 'excel' ? 'xlsx' : 'csv';
            $filename = 'laporan_harian_' . $farm->name . '_' . $tanggal->format('Y-m-d') . '_' . $reportType . '.' . $extension;

            Log::debug('Exporting to CSV format', [
                'farm_name' => $farm->name,
                'tanggal' => $tanggal->format('Y-m-d'),
                'report_type' => $reportType,
                'filename' => $filename,
                'format' => $format
            ]);

            // Use same structured data as Excel from service
            $csvData = $this->daillyReportExcelExportService->prepareStructuredData($data, $farm, $tanggal, $reportType);

            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Pragma' => 'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0'
            ];

            Log::info('CSV export completed successfully', [
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
                'error' => $e->getMessage(),
                'farm_name' => $farm->name,
                'tanggal' => $tanggal->format('Y-m-d')
            ]);

            return response()->json([
                'error' => 'Export CSV gagal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate export data before processing
     * 
     * @param array $data
     * @return bool
     */
    public function validateExportData(array $data): bool
    {
        if (empty($data['recordings'])) {
            Log::warning('Export validation failed: No recordings data found');
            return false;
        }

        if (!isset($data['totals']) || !is_array($data['totals'])) {
            Log::warning('Export validation failed: Invalid totals data');
            return false;
        }

        if (!isset($data['distinctFeedNames']) || !is_array($data['distinctFeedNames'])) {
            Log::warning('Export validation failed: Invalid feed names data');
            return false;
        }

        Log::debug('Export data validation passed', [
            'recordings_count' => count($data['recordings']),
            'totals_keys' => array_keys($data['totals']),
            'feed_names_count' => count($data['distinctFeedNames'])
        ]);

        return true;
    }

    /**
     * Get export filename based on parameters
     * 
     * @param Farm $farm
     * @param Carbon $tanggal
     * @param string $reportType
     * @param string $extension
     * @return string
     */
    public function getExportFilename(Farm $farm, Carbon $tanggal, string $reportType, string $extension): string
    {
        $filename = 'laporan_harian_' .
            str_replace(' ', '_', $farm->name) . '_' .
            $tanggal->format('Y-m-d') . '_' .
            $reportType . '.' .
            $extension;

        Log::debug('Generated export filename', [
            'filename' => $filename,
            'farm_name' => $farm->name,
            'tanggal' => $tanggal->format('Y-m-d'),
            'report_type' => $reportType,
            'extension' => $extension
        ]);

        return $filename;
    }

    /**
     * Get supported export formats
     * 
     * @return array
     */
    public function getSupportedFormats(): array
    {
        return [
            'html' => 'HTML View',
            'excel' => 'Excel (XLSX)',
            'pdf' => 'PDF Document',
            'csv' => 'CSV File'
        ];
    }

    /**
     * Get export format description
     * 
     * @param string $format
     * @return string
     */
    public function getFormatDescription(string $format): string
    {
        $formats = $this->getSupportedFormats();
        return $formats[$format] ?? 'Unknown Format';
    }
}

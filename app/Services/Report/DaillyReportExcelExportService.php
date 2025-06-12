<?php

namespace App\Services\Report;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Exception;

class DaillyReportExcelExportService
{
    /**
     * Export data to Excel with proper formatting and table structure
     */
    public function exportToExcel($data, $farm, $tanggal, $reportType)
    {
        try {
            $filename = 'laporan_harian_' . Str::slug($farm->name) . '_' . $tanggal->format('Y-m-d') . '_' . $reportType . '.xlsx';

            Log::info('Excel export initiated', [
                'filename' => $filename,
                'report_type' => $reportType,
                'farm_id' => $farm->id
            ]);

            // Create new spreadsheet
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Set sheet title
            $sheet->setTitle('Laporan Harian');

            // Build the Excel content
            $this->buildExcelContent($sheet, $data, $farm, $tanggal, $reportType);

            // Create Excel writer
            $writer = new Xlsx($spreadsheet);

            // Set headers for download
            $headers = [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'max-age=0, no-cache, must-revalidate',
                'Pragma' => 'public'
            ];

            // Stream the file
            return response()->stream(function () use ($writer) {
                $writer->save('php://output');
            }, 200, $headers);
        } catch (Exception $e) {
            Log::error('Excel export failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'farm_id' => $farm->id ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Export Excel gagal',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Build Excel content with proper formatting
     */
    public function buildExcelContent($sheet, $data, $farm, $tanggal, $reportType)
    {
        $currentRow = 1;

        // 1. Build Header Section
        $currentRow = $this->buildHeaderSection($sheet, $farm, $tanggal, $reportType, $currentRow);

        // 2. Build Data Table
        $currentRow = $this->buildDataTable($sheet, $data, $reportType, $currentRow);

        // 3. Build Summary Section
        $currentRow = $this->buildSummarySection($sheet, $data, $currentRow);

        // 4. Build Footer Section
        $this->buildFooterSection($sheet, $currentRow);

        // 5. Apply final formatting
        $this->applyFinalFormatting($sheet);
    }

    /**
     * Build header section with company info and report details
     */
    public function buildHeaderSection($sheet, $farm, $tanggal, $reportType, $startRow)
    {
        $row = $startRow;

        // Main title
        $sheet->setCellValue('A' . $row, 'LAPORAN HARIAN TERNAK');
        $sheet->mergeCells('A' . $row . ':O' . $row);
        $this->applyHeaderStyle($sheet, 'A' . $row . ':O' . $row, 'title');
        $row++;

        // Farm info section
        $sheet->setCellValue('A' . $row, 'Farm:');
        $sheet->setCellValue('B' . $row, $farm->name);
        $sheet->setCellValue('D' . $row, 'Tanggal:');
        $sheet->setCellValue('E' . $row, $tanggal->format('d F Y'));
        $this->applyHeaderStyle($sheet, 'A' . $row . ':E' . $row, 'info');
        $row++;

        $sheet->setCellValue('A' . $row, 'Mode:');
        $sheet->setCellValue('B' . $row, ucfirst($reportType));
        $sheet->setCellValue('D' . $row, 'Waktu Export:');
        $sheet->setCellValue('E' . $row, now()->format('d F Y H:i:s'));
        $this->applyHeaderStyle($sheet, 'A' . $row . ':E' . $row, 'info');
        $row++;

        // Empty row for spacing
        $row++;

        return $row;
    }

    /**
     * Build main data table with proper headers and formatting
     */
    public function buildDataTable($sheet, $data, $reportType, $startRow)
    {
        $row = $startRow;

        // Prepare headers
        $headers = $this->getTableHeaders($reportType, $data['distinctFeedNames']);

        // Set headers
        $colIndex = 0;
        foreach ($headers as $header) {
            $sheet->setCellValue($this->getColumnLetter($colIndex) . $row, $header);
            $colIndex++;
        }

        // Apply header styling
        $headerRange = 'A' . $row . ':' . $this->getColumnLetter(count($headers) - 1) . $row;
        $this->applyTableHeaderStyle($sheet, $headerRange);
        $row++;

        // Add data rows
        $dataStartRow = $row;
        foreach ($data['recordings'] as $coopName => $records) {
            if ($reportType === 'detail') {
                foreach ($records as $record) {
                    $this->addDataRow($sheet, $row, $coopName, $record, $data['distinctFeedNames'], $reportType);
                    $row++;
                }
            } else {
                $this->addDataRow($sheet, $row, $coopName, $records, $data['distinctFeedNames'], $reportType);
                $row++;
            }
        }

        // Apply data table styling
        if ($row > $dataStartRow) {
            $dataRange = 'A' . $dataStartRow . ':' . $this->getColumnLetter(count($headers) - 1) . ($row - 1);
            $this->applyDataTableStyle($sheet, $dataRange);
        }

        return $row;
    }

    /**
     * Get table headers based on report type
     */
    public function getTableHeaders($reportType, $feedNames)
    {
        $mainHeaders = $reportType === 'detail'
            ? ['Kandang', 'Batch', 'Umur', 'Stock Awal', 'Mati', 'Afkir', 'Total Deplesi', '% Mortalitas', 'Jual Ekor', 'Jual KG', 'Stock Akhir', 'Berat Semalam', 'Berat Hari Ini', 'Kenaikan Berat']
            : ['Kandang', 'Umur', 'Stock Awal', 'Mati', 'Afkir', 'Total Deplesi', '% Mortalitas', 'Jual Ekor', 'Jual KG', 'Stock Akhir', 'Berat Semalam', 'Berat Hari Ini', 'Kenaikan Berat'];

        $feedHeaders = array_merge($feedNames, ['Total Pakan']);

        return array_merge($mainHeaders, $feedHeaders);
    }

    /**
     * Add single data row to the sheet
     */
    public function addDataRow($sheet, $row, $coopName, $record, $feedNames, $reportType)
    {
        $colIndex = 0;

        // Basic data
        $sheet->setCellValue($this->getColumnLetter($colIndex++) . $row, $coopName);

        if ($reportType === 'detail') {
            $sheet->setCellValue($this->getColumnLetter($colIndex++) . $row, $record['livestock_name'] ?? '-');
        }

        $sheet->setCellValue($this->getColumnLetter($colIndex++) . $row, (int)($record['umur'] ?? 0));
        $sheet->setCellValue($this->getColumnLetter($colIndex++) . $row, (int)($record['stock_awal'] ?? 0));
        $sheet->setCellValue($this->getColumnLetter($colIndex++) . $row, (int)($record['mati'] ?? 0));
        $sheet->setCellValue($this->getColumnLetter($colIndex++) . $row, (int)($record['afkir'] ?? 0));
        $sheet->setCellValue($this->getColumnLetter($colIndex++) . $row, (int)($record['total_deplesi'] ?? 0));
        $sheet->setCellValue($this->getColumnLetter($colIndex++) . $row, ($record['deplesi_percentage'] ?? 0) / 100); // For percentage formatting
        $sheet->setCellValue($this->getColumnLetter($colIndex++) . $row, (int)($record['jual_ekor'] ?? 0));
        $sheet->setCellValue($this->getColumnLetter($colIndex++) . $row, (float)($record['jual_kg'] ?? 0));
        $sheet->setCellValue($this->getColumnLetter($colIndex++) . $row, (int)($record['stock_akhir'] ?? 0));
        $sheet->setCellValue($this->getColumnLetter($colIndex++) . $row, (float)($record['berat_semalam'] ?? 0));
        $sheet->setCellValue($this->getColumnLetter($colIndex++) . $row, (float)($record['berat_hari_ini'] ?? 0));
        $sheet->setCellValue($this->getColumnLetter($colIndex++) . $row, (float)($record['kenaikan_berat'] ?? 0));

        // Feed data
        foreach ($feedNames as $feedName) {
            $feedAmount = $record['pakan_harian'][$feedName] ?? 0;
            $sheet->setCellValue($this->getColumnLetter($colIndex++) . $row, (float)$feedAmount);
        }
        $sheet->setCellValue($this->getColumnLetter($colIndex++) . $row, (float)($record['pakan_total'] ?? 0));
    }

    /**
     * Build summary section
     */
    public function buildSummarySection($sheet, $data, $startRow)
    {
        if (!isset($data['totals'])) {
            return $startRow;
        }

        $row = $startRow + 1; // Add spacing

        // Summary title
        $sheet->setCellValue('A' . $row, 'RINGKASAN TOTAL');
        $sheet->mergeCells('A' . $row . ':D' . $row);
        $this->applyHeaderStyle($sheet, 'A' . $row . ':D' . $row, 'summary');
        $row++;

        // Summary data
        $colIndex = 0;
        $sheet->setCellValue($this->getColumnLetter($colIndex++) . $row, 'TOTAL');

        // Determine if we need to skip batch column (for detail mode)
        $reportType = $data['reportType'] ?? 'simple';
        if ($reportType === 'detail') {
            $sheet->setCellValue($this->getColumnLetter($colIndex++) . $row, ''); // Skip batch column
        }

        $sheet->setCellValue($this->getColumnLetter($colIndex++) . $row, ''); // Skip umur column
        $sheet->setCellValue($this->getColumnLetter($colIndex++) . $row, (int)($data['totals']['stock_awal'] ?? 0));
        $sheet->setCellValue($this->getColumnLetter($colIndex++) . $row, (int)($data['totals']['mati'] ?? 0));
        $sheet->setCellValue($this->getColumnLetter($colIndex++) . $row, (int)($data['totals']['afkir'] ?? 0));
        $sheet->setCellValue($this->getColumnLetter($colIndex++) . $row, (int)($data['totals']['total_deplesi'] ?? 0));
        $sheet->setCellValue($this->getColumnLetter($colIndex++) . $row, ($data['totals']['deplesi_percentage'] ?? 0) / 100);
        $sheet->setCellValue($this->getColumnLetter($colIndex++) . $row, (int)($data['totals']['jual_ekor'] ?? 0));
        $sheet->setCellValue($this->getColumnLetter($colIndex++) . $row, (float)($data['totals']['jual_kg'] ?? 0));
        $sheet->setCellValue($this->getColumnLetter($colIndex++) . $row, (int)($data['totals']['stock_akhir'] ?? 0));
        $sheet->setCellValue($this->getColumnLetter($colIndex++) . $row, (float)($data['totals']['berat_semalam'] ?? 0));
        $sheet->setCellValue($this->getColumnLetter($colIndex++) . $row, (float)($data['totals']['berat_hari_ini'] ?? 0));
        $sheet->setCellValue($this->getColumnLetter($colIndex++) . $row, (float)($data['totals']['kenaikan_berat'] ?? 0));

        // Total feed data
        foreach ($data['distinctFeedNames'] as $feedName) {
            $feedTotal = $data['totals']['pakan_harian'][$feedName] ?? 0;
            $sheet->setCellValue($this->getColumnLetter($colIndex++) . $row, (float)$feedTotal);
        }
        $sheet->setCellValue($this->getColumnLetter($colIndex++) . $row, (float)($data['totals']['pakan_total'] ?? 0));

        // Apply summary styling
        $summaryRange = 'A' . $row . ':' . $this->getColumnLetter($colIndex - 1) . $row;
        $this->applySummaryStyle($sheet, $summaryRange);

        return $row + 1;
    }

    /**
     * Build footer section with export info
     */
    public function buildFooterSection($sheet, $startRow)
    {
        $row = $startRow + 1;

        $sheet->setCellValue('A' . $row, 'Generated by: Farm Management System');
        $this->applyFooterStyle($sheet, 'A' . $row);
        $row++;

        $sheet->setCellValue('A' . $row, 'Export Time: ' . now()->format('d F Y H:i:s T'));
        $this->applyFooterStyle($sheet, 'A' . $row);
    }

    /**
     * Apply header styling
     */
    public function applyHeaderStyle($sheet, $range, $type = 'default')
    {
        $style = [
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]
        ];

        switch ($type) {
            case 'title':
                $style['font']['size'] = 16;
                $style['alignment']['horizontal'] = Alignment::HORIZONTAL_CENTER;
                $style['fill'] = [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4']
                ];
                $style['font']['color'] = ['rgb' => 'FFFFFF'];
                break;
            case 'summary':
                $style['fill'] = [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E7E6E6']
                ];
                break;
        }

        $sheet->getStyle($range)->applyFromArray($style);
    }

    /**
     * Apply table header styling
     */
    public function applyTableHeaderStyle($sheet, $range)
    {
        $style = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '5B9BD5']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ];

        $sheet->getStyle($range)->applyFromArray($style);
    }

    /**
     * Apply data table styling
     */
    public function applyDataTableStyle($sheet, $range)
    {
        $style = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D0D0D0']
                ]
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ];

        $sheet->getStyle($range)->applyFromArray($style);

        // Apply alternating row colors
        $this->applyAlternatingRowColors($sheet, $range);

        // Format specific columns
        $this->formatSpecificColumns($sheet, $range);
    }

    /**
     * Apply alternating row colors
     */
    public function applyAlternatingRowColors($sheet, $range)
    {
        $coordinates = explode(':', $range);
        $startCell = $coordinates[0];
        $endCell = $coordinates[1];

        preg_match('/([A-Z]+)(\d+)/', $startCell, $startMatches);
        preg_match('/([A-Z]+)(\d+)/', $endCell, $endMatches);

        $startRow = (int)$startMatches[2];
        $endRow = (int)$endMatches[2];
        $endCol = $endMatches[1];

        for ($row = $startRow; $row <= $endRow; $row++) {
            if ($row % 2 == 0) {
                $rowRange = 'A' . $row . ':' . $endCol . $row;
                $sheet->getStyle($rowRange)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F8F9FA']
                    ]
                ]);
            }
        }
    }

    /**
     * Format specific columns (numbers, percentages)
     */
    public function formatSpecificColumns($sheet, $range)
    {
        // This would need to be customized based on your specific column positions
        // Example: Format percentage column
        $coordinates = explode(':', $range);
        preg_match('/([A-Z]+)(\d+)/', $coordinates[0], $startMatches);
        preg_match('/([A-Z]+)(\d+)/', $coordinates[1], $endMatches);

        $startRow = (int)$startMatches[2];
        $endRow = (int)$endMatches[2];

        // Format percentage column (assuming it's column H)
        $percentageRange = 'H' . $startRow . ':H' . $endRow;
        $sheet->getStyle($percentageRange)->getNumberFormat()->setFormatCode('0.00%');

        // Format decimal columns
        $decimalColumns = ['J', 'L', 'M', 'N']; // Adjust based on your columns
        foreach ($decimalColumns as $col) {
            $decimalRange = $col . $startRow . ':' . $col . $endRow;
            $sheet->getStyle($decimalRange)->getNumberFormat()->setFormatCode('#,##0.00');
        }
    }

    /**
     * Apply summary styling
     */
    public function applySummaryStyle($sheet, $range)
    {
        $style = [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFF2CC']
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ];

        $sheet->getStyle($range)->applyFromArray($style);
    }

    /**
     * Apply footer styling
     */
    public function applyFooterStyle($sheet, $range)
    {
        $style = [
            'font' => [
                'italic' => true,
                'size' => 9,
                'color' => ['rgb' => '666666']
            ]
        ];

        $sheet->getStyle($range)->applyFromArray($style);
    }

    /**
     * Apply final formatting (column widths, etc.)
     */
    public function applyFinalFormatting($sheet)
    {
        // Get the highest column used in the sheet
        $highestColumn = $sheet->getHighestColumn();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

        // Auto-size columns up to the highest used column
        for ($i = 1; $i <= $highestColumnIndex; $i++) {
            $colLetter = $this->getColumnLetter($i - 1);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        // Set minimum width for some columns
        $sheet->getColumnDimension('A')->setWidth(15); // Kandang
        $sheet->getColumnDimension('B')->setWidth(12); // Batch/Umur

        // Set page orientation and margins
        $sheet->getPageSetup()
            ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);

        $sheet->getPageMargins()
            ->setTop(0.75)
            ->setBottom(0.75)
            ->setLeft(0.7)
            ->setRight(0.7);
    }

    /**
     * Get column letter from index (supports multi-letter columns like AA, AB, etc.)
     */
    public function getColumnLetter($index)
    {
        $letter = '';
        while ($index >= 0) {
            $letter = chr(65 + ($index % 26)) . $letter;
            $index = intval($index / 26) - 1;
        }
        return $letter;
    }

    /**
     * Prepare structured data for CSV compatibility
     * Returns array data similar to the old prepareStructuredExcelData method
     */
    public function prepareStructuredData($data, $farm, $tanggal, $reportType)
    {
        $excelData = [];

        // Add title section
        $excelData[] = ['LAPORAN HARIAN TERNAK'];
        $excelData[] = ['Farm: ' . $farm->name];
        $excelData[] = ['Tanggal: ' . $tanggal->format('d-M-Y')];
        $excelData[] = ['Mode: ' . ucfirst($reportType)];
        $excelData[] = []; // Empty row

        // Prepare main headers
        $mainHeaders = $reportType === 'detail'
            ? ['Kandang', 'Batch', 'Umur', 'Stock Awal', 'Mati', 'Afkir', 'Total Deplesi', '% Mortalitas', 'Jual Ekor', 'Jual KG', 'Stock Akhir', 'Berat Semalam', 'Berat Hari Ini', 'Kenaikan Berat']
            : ['Kandang', 'Umur', 'Stock Awal', 'Mati', 'Afkir', 'Total Deplesi', '% Mortalitas', 'Jual Ekor', 'Jual KG', 'Stock Akhir', 'Berat Semalam', 'Berat Hari Ini', 'Kenaikan Berat'];

        // Add feed columns
        $feedHeaders = array_merge($data['distinctFeedNames'], ['Total Pakan']);

        // Combine headers
        $headers = array_merge($mainHeaders, $feedHeaders);
        $excelData[] = $headers;

        // Add data rows with proper formatting
        foreach ($data['recordings'] as $coopName => $records) {
            if ($reportType === 'detail') {
                foreach ($records as $record) {
                    $row = [
                        $coopName,
                        $record['livestock_name'] ?? '-',
                        (int)$record['umur'],
                        (int)$record['stock_awal'],
                        (int)$record['mati'],
                        (int)$record['afkir'],
                        (int)$record['total_deplesi'],
                        number_format($record['deplesi_percentage'], 2) . '%',
                        (int)$record['jual_ekor'],
                        number_format($record['jual_kg'], 2),
                        (int)$record['stock_akhir'],
                        number_format($record['berat_semalam'], 0),
                        number_format($record['berat_hari_ini'], 0),
                        number_format($record['kenaikan_berat'], 0)
                    ];

                    // Add feed data
                    foreach ($data['distinctFeedNames'] as $feedName) {
                        $feedAmount = $record['pakan_harian'][$feedName] ?? 0;
                        $row[] = number_format($feedAmount, 2);
                    }
                    $row[] = number_format($record['pakan_total'], 2);

                    $excelData[] = $row;
                }
            } else {
                // Simple mode: aggregated per coop
                $row = [
                    $coopName,
                    (int)$records['umur'],
                    (int)$records['stock_awal'],
                    (int)$records['mati'],
                    (int)$records['afkir'],
                    (int)$records['total_deplesi'],
                    number_format($records['deplesi_percentage'], 2) . '%',
                    (int)$records['jual_ekor'],
                    number_format($records['jual_kg'], 2),
                    (int)$records['stock_akhir'],
                    number_format($records['berat_semalam'], 0),
                    number_format($records['berat_hari_ini'], 0),
                    number_format($records['kenaikan_berat'], 0)
                ];

                // Add feed data
                foreach ($data['distinctFeedNames'] as $feedName) {
                    $feedAmount = $records['pakan_harian'][$feedName] ?? 0;
                    $row[] = number_format($feedAmount, 2);
                }
                $row[] = number_format($records['pakan_total'], 2);

                $excelData[] = $row;
            }
        }

        // Add summary section
        if (isset($data['totals'])) {
            $excelData[] = []; // Empty row
            $excelData[] = ['RINGKASAN TOTAL'];

            $summaryRow = ['TOTAL'];

            if ($reportType === 'detail') {
                $summaryRow[] = ''; // Empty batch column
            }

            $summaryRow = array_merge($summaryRow, [
                '', // Empty umur column
                (int)($data['totals']['stock_awal'] ?? 0),
                (int)($data['totals']['mati'] ?? 0),
                (int)($data['totals']['afkir'] ?? 0),
                (int)($data['totals']['total_deplesi'] ?? 0),
                number_format($data['totals']['deplesi_percentage'] ?? 0, 2) . '%',
                (int)($data['totals']['jual_ekor'] ?? 0),
                number_format($data['totals']['jual_kg'] ?? 0, 2),
                (int)($data['totals']['stock_akhir'] ?? 0),
                number_format($data['totals']['berat_semalam'] ?? 0, 0),
                number_format($data['totals']['berat_hari_ini'] ?? 0, 0),
                number_format($data['totals']['kenaikan_berat'] ?? 0, 0)
            ]);

            // Add total feed data
            foreach ($data['distinctFeedNames'] as $feedName) {
                $feedTotal = $data['totals']['pakan_harian'][$feedName] ?? 0;
                $summaryRow[] = number_format($feedTotal, 2);
            }
            $summaryRow[] = number_format($data['totals']['pakan_total'] ?? 0, 2);

            $excelData[] = $summaryRow;
        }

        // Add export info
        $excelData[] = []; // Empty row
        $excelData[] = ['Diekspor pada: ' . now()->format('d-M-Y H:i:s')];
        $excelData[] = ['System: Demo Farm Management System'];

        return $excelData;
    }
}

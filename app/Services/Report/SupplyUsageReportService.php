<?php

namespace App\Services\Report;

use App\Models\Farm;
use App\Models\Livestock;
use App\Models\SupplyUsage;
use App\Models\SupplyUsageDetail;
use App\Models\Supply;
use App\Services\Recording\UnitConversionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Barryvdh\DomPDF\Facade\Pdf;

class SupplyUsageReportService
{
    protected $unitConversionService;

    public function __construct(UnitConversionService $unitConversionService)
    {
        $this->unitConversionService = $unitConversionService;
    }

    /**
     * Generate supply usage report data
     * 
     * @param array $params
     * @return array
     */
    public function generateSupplyUsageReport(array $params): array
    {
        $farm = Farm::findOrFail($params['farm_id']);
        $startDate = Carbon::parse($params['start_date']);
        $endDate = Carbon::parse($params['end_date']);
        $reportType = $params['report_type'] ?? 'detail';

        Log::info('Generating Supply Usage Report', [
            'farm_id' => $farm->id,
            'farm_name' => $farm->name,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'report_type' => $reportType,
            'user_id' => Auth::id()
        ]);

        // Get supply usage data
        $supplyUsages = $this->getSupplyUsageData($farm, $startDate, $endDate, $params);

        // Process data based on report type
        $processedData = $this->processSupplyUsageData($supplyUsages, $reportType, $params);

        Log::info('Supply Usage Report generated successfully', [
            'farm_id' => $farm->id,
            'supply_usages_count' => $supplyUsages->count(),
            'processed_data_count' => count($processedData['data']),
            'total_cost' => $processedData['totals']['total_cost'],
            'total_quantity' => $processedData['totals']['total_quantity']
        ]);

        return [
            'farm' => $farm,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'reportType' => $reportType,
            'data' => $processedData['data'],
            'totals' => $processedData['totals'],
            'summary' => $processedData['summary']
        ];
    }

    /**
     * Get supply usage data from database
     * 
     * @param Farm $farm
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param array $params
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getSupplyUsageData(Farm $farm, Carbon $startDate, Carbon $endDate, array $params)
    {
        $query = SupplyUsage::with([
            'livestock.coop',
            'livestock.farm',
            'details.supply',
            'details.unit',
            'details.supplyStock.supplyPurchaseDetail',
        ])
            ->whereHas('livestock', function ($q) use ($farm) {
                $q->where('farm_id', $farm->id);
            })
            ->whereBetween('usage_date', [$startDate, $endDate])
            ->whereIn('status', ['pending', 'in_process', 'completed']);

        // Filter by livestock if specified
        if (!empty($params['livestock_id'])) {
            $query->where('livestock_id', $params['livestock_id']);
        }

        // Filter by coop if specified
        if (!empty($params['coop_id'])) {
            $query->whereHas('livestock', function ($q) use ($params) {
                $q->where('coop_id', $params['coop_id']);
            });
        }

        // Filter by supply type if specified
        if (!empty($params['supply_id'])) {
            $query->whereHas('details', function ($q) use ($params) {
                $q->where('supply_id', $params['supply_id']);
            });
        }

        return $query->orderBy('usage_date', 'desc')->get();
    }

    /**
     * Process supply usage data based on report type
     * 
     * @param \Illuminate\Database\Eloquent\Collection $supplyUsages
     * @param string $reportType
     * @param array $params
     * @return array
     */
    private function processSupplyUsageData($supplyUsages, string $reportType, array $params): array
    {
        if ($reportType === 'detail') {
            return $this->processDetailReport($supplyUsages, $params); // PATCH: pass $params
        } else {
            return $this->processSimpleReport($supplyUsages, $params); // PATCH: pass $params
        }
    }

    /**
     * Process detail report (per usage record)
     * 
     * @param \Illuminate\Database\Eloquent\Collection $supplyUsages
     * @return array
     */
    private function processDetailReport($supplyUsages, $params = []): array
    {
        $processedData = [];
        $totals = [
            'total_cost' => 0, // will now be sum of converted_total_cost
            'total_quantity' => 0,
            'total_records' => 0,
            'supply_types' => []
        ];

        $filterSupplyId = $params['supply_id'] ?? null;

        foreach ($supplyUsages as $usage) {
            foreach ($usage->details as $detail) {
                // PATCH: Filter detail jika supply_id di-request
                if ($filterSupplyId && $detail->supply_id !== $filterSupplyId) {
                    Log::debug('Skipping detail due to supply_id filter', [
                        'detail_id' => $detail->id,
                        'supply_id' => $detail->supply_id,
                        'filter_supply_id' => $filterSupplyId,
                    ]);
                    continue;
                }
                $supplyName = $detail->supply->name ?? 'Unknown';
                $unitName = $detail->unit->name ?? 'pcs';
                $supply = $detail->supply;
                $smallestUnit = '-';
                if ($supply) {
                    $unitInfo = $this->unitConversionService->getDetailedSupplyUnitInfo($supply, (float)$detail->quantity_taken);
                    $smallestUnit = $unitInfo['smallest_unit_name'] ?? '-';
                    Log::debug('Extracted smallest unit', [
                        'supply_id' => $supply->id,
                        'supply_name' => $supply->name,
                        'smallest_unit' => $smallestUnit,
                        'unit_info' => $unitInfo
                    ]);
                }
                // --- Harga & Unit dari Purchase Detail ---
                $purchase = $detail->supplyStock->supplyPurchase ?? null;
                $unitCost = $detail->price_per_unit;
                $convertedUnitCost = $detail->price_per_converted_unit;
                // Fallback if missing or zero
                if (!$unitCost || $unitCost == 0) {
                    $unitCost = $purchase ? $purchase->price_per_unit : 0;
                }
                if (!$convertedUnitCost || $convertedUnitCost == 0) {
                    $convertedUnitCost = $purchase ? $purchase->price_per_converted_unit : 0;
                }
                $unitName = $detail->unit->name ?? ($purchase && $purchase->unit ? $purchase->unit->name : 'pcs');
                $convertedUnitName = $detail->converted_unit_id
                    ? ($detail->convertedUnit->name ?? '-')
                    : ($purchase && $purchase->convertedUnit ? $purchase->convertedUnit->name : '-');
                $quantity = (float) $detail->quantity_taken;
                $convertedQuantity = $detail->converted_quantity ?? ($purchase ? $purchase->converted_quantity : null) ?? $quantity;
                $cost = $quantity * $unitCost;
                $convertedCost = $convertedQuantity * $convertedUnitCost;

                Log::debug('SupplyUsageDetail pricing', [
                    'detail_id' => $detail->id,
                    'unit_cost' => $unitCost,
                    'converted_unit_cost' => $convertedUnitCost,
                    'purchase_unit_cost' => $purchase ? $purchase->price_per_unit : null,
                    'purchase_converted_unit_cost' => $purchase ? $purchase->price_per_converted_unit : null,
                ]);

                $processedData[] = [
                    'usage_date' => $usage->usage_date,
                    'livestock_name' => $usage->livestock->name ?? 'Unknown',
                    'coop_name' => $usage->livestock->coop->name ?? 'Unknown',
                    'supply_name' => $supplyName,
                    'quantity' => $quantity,
                    'unit' => $unitName,
                    'smallest_unit' => $smallestUnit,
                    'unit_cost' => $unitCost,
                    'total_cost' => $cost,
                    'converted_quantity' => $convertedQuantity,
                    'converted_unit' => $convertedUnitName,
                    'converted_unit_cost' => $convertedUnitCost,
                    'converted_total_cost' => $convertedCost,
                    'status' => $usage->status,
                    'notes' => $usage->notes ?? '-'
                ];

                // Update totals (now only use converted cost)
                $totals['total_cost'] += $convertedCost;
                $totals['total_quantity'] += $convertedQuantity;
                $totals['total_records']++;

                // Track supply types (use converted cost)
                if (!isset($totals['supply_types'][$supplyName])) {
                    $totals['supply_types'][$supplyName] = [
                        'quantity' => 0,
                        'cost' => 0,
                        'unit' => $convertedUnitName
                    ];
                }
                $totals['supply_types'][$supplyName]['quantity'] += $convertedQuantity;
                $totals['supply_types'][$supplyName]['cost'] += $convertedCost;
            }
        }

        $summary = [
            'period' => $supplyUsages->first()?->usage_date->format('d M Y') . ' - ' . $supplyUsages->last()?->usage_date->format('d M Y'),
            'total_records' => $totals['total_records'],
            'total_cost' => $totals['total_cost'], // summary now uses converted total cost
            'total_quantity' => $totals['total_quantity'],
            'supply_types_count' => count($totals['supply_types'])
        ];

        return [
            'data' => $processedData,
            'totals' => $totals,
            'summary' => $summary
        ];
    }

    /**
     * Process simple report (aggregated by date/livestock)
     * 
     * @param \Illuminate\Database\Eloquent\Collection $supplyUsages
     * @return array
     */
    private function processSimpleReport($supplyUsages, $params = []): array
    {
        $processedData = [];
        $totals = [
            'total_cost' => 0,
            'total_quantity' => 0,
            'total_records' => 0,
            'supply_types' => []
        ];

        $filterSupplyId = $params['supply_id'] ?? null;

        // Group by date and livestock
        $groupedUsages = $supplyUsages->groupBy(function ($usage) {
            return $usage->usage_date->format('Y-m-d') . '_' . $usage->livestock_id;
        });

        foreach ($groupedUsages as $key => $usages) {
            $firstUsage = $usages->first();
            $usageDate = $firstUsage->usage_date;
            $livestock = $firstUsage->livestock;

            $dailyData = [
                'usage_date' => $usageDate,
                'livestock_name' => $livestock->name ?? 'Unknown',
                'coop_name' => $livestock->coop->name ?? 'Unknown',
                'supply_breakdown' => [],
                'total_quantity' => 0,
                'total_cost' => 0,
                'supply_count' => 0
            ];

            foreach ($usages as $usage) {
                foreach ($usage->details as $detail) {
                    // PATCH: Filter detail jika supply_id di-request
                    if ($filterSupplyId && $detail->supply_id !== $filterSupplyId) {
                        Log::debug('Skipping detail (simple) due to supply_id filter', [
                            'detail_id' => $detail->id,
                            'supply_id' => $detail->supply_id,
                            'filter_supply_id' => $filterSupplyId,
                        ]);
                        continue;
                    }
                    $supplyName = $detail->supply->name ?? 'Unknown';
                    $unitName = $detail->unit->name ?? 'pcs';
                    $quantity = (float) $detail->quantity_taken;
                    $unitCost = $detail->price_per_unit ?? $detail->supply->price ?? 0;
                    $cost = $quantity * $unitCost;

                    if (!isset($dailyData['supply_breakdown'][$supplyName])) {
                        $dailyData['supply_breakdown'][$supplyName] = [
                            'quantity' => 0,
                            'cost' => 0,
                            'unit' => $unitName
                        ];
                    }

                    $dailyData['supply_breakdown'][$supplyName]['quantity'] += $quantity;
                    $dailyData['supply_breakdown'][$supplyName]['cost'] += $cost;
                    $dailyData['total_quantity'] += $quantity;
                    $dailyData['total_cost'] += $cost;
                }
            }

            $dailyData['supply_count'] = count($dailyData['supply_breakdown']);
            // Only add if there is at least one supply_breakdown (after filter)
            if ($dailyData['supply_count'] > 0) {
                $processedData[] = $dailyData;

                // Update totals
                $totals['total_cost'] += $dailyData['total_cost'];
                $totals['total_quantity'] += $dailyData['total_quantity'];
                $totals['total_records']++;

                // Track supply types
                foreach ($dailyData['supply_breakdown'] as $supplyName => $data) {
                    if (!isset($totals['supply_types'][$supplyName])) {
                        $totals['supply_types'][$supplyName] = [
                            'quantity' => 0,
                            'cost' => 0,
                            'unit' => $data['unit']
                        ];
                    }
                    $totals['supply_types'][$supplyName]['quantity'] += $data['quantity'];
                    $totals['supply_types'][$supplyName]['cost'] += $data['cost'];
                }
            }
        }

        $summary = [
            'period' => $supplyUsages->first()?->usage_date->format('d M Y') . ' - ' . $supplyUsages->last()?->usage_date->format('d M Y'),
            'total_records' => $totals['total_records'],
            'total_cost' => $totals['total_cost'],
            'total_quantity' => $totals['total_quantity'],
            'supply_types_count' => count($totals['supply_types'])
        ];

        return [
            'data' => $processedData,
            'totals' => $totals,
            'summary' => $summary
        ];
    }

    /**
     * Validate supply usage report parameters
     * 
     * @param array $params
     * @return array
     */
    public function validateParams(array $params): array
    {
        $rules = [
            'farm_id' => 'required|uuid|exists:farms,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'report_type' => 'nullable|in:detail,simple'
        ];

        $validator = validator($params, $rules);

        if ($validator->fails()) {
            throw new \InvalidArgumentException($validator->errors()->first());
        }

        return $params;
    }

    /**
     * Export supply usage report in requested format
     * 
     * @param array $data
     * @param string $format
     * @return mixed
     */
    public function exportSupplyUsage(array $data, string $format)
    {
        switch ($format) {
            case 'excel':
                return $this->exportToExcel($data);
            case 'pdf':
                return $this->exportToPdf($data);
            case 'csv':
                return $this->exportToCsv($data);
            case 'html':
            default:
                return view('pages.reports.supply-usage', $data);
        }
    }

    /**
     * Export to Excel format
     * 
     * @param array $data
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    private function exportToExcel(array $data)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set title
        $sheet->setCellValue('A1', 'LAPORAN PEMAKAIAN SUPPLY/OVK');
        $sheet->mergeCells('A1:H1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        // Set period
        $sheet->setCellValue('A2', 'Periode: ' . $data['summary']['period']);
        $sheet->mergeCells('A2:H2');

        // Set headers
        $headers = ['No', 'Tanggal', 'Batch', 'Kandang', 'Jenis Supply', 'Jumlah', 'Satuan', 'Harga Satuan', 'Total Harga'];
        $col = 'A';
        $row = 4;
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $row, $header);
            $sheet->getStyle($col . $row)->getFont()->setBold(true);
            $col++;
        }

        // Fill data
        $row = 5;
        $no = 1;
        foreach ($data['data'] as $item) {
            $sheet->setCellValue('A' . $row, $no);
            $sheet->setCellValue('B' . $row, $item['usage_date']->format('d/m/Y'));
            $sheet->setCellValue('C' . $row, $item['livestock_name']);
            $sheet->setCellValue('D' . $row, $item['coop_name']);
            $sheet->setCellValue('E' . $row, $item['supply_name']);
            $sheet->setCellValue('F' . $row, $item['quantity']);
            $sheet->setCellValue('G' . $row, $item['unit']);
            $sheet->setCellValue('H' . $row, number_format($item['unit_cost'], 0, ',', '.'));
            $sheet->setCellValue('I' . $row, number_format($item['total_cost'], 0, ',', '.'));
            $row++;
            $no++;
        }

        // Set totals
        $row++;
        $sheet->setCellValue('A' . $row, 'TOTAL');
        $sheet->mergeCells('A' . $row . ':H' . $row);
        $sheet->setCellValue('I' . $row, number_format($data['totals']['total_cost'], 0, ',', '.'));
        $sheet->getStyle('A' . $row . ':I' . $row)->getFont()->setBold(true);

        // Auto size columns
        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'laporan_pemakaian_supply_' . date('Y-m-d_H-i-s') . '.xlsx';
        $path = storage_path('app/public/temp/' . $filename);

        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $writer->save($path);

        return response()->download($path)->deleteFileAfterSend();
    }

    /**
     * Export to PDF format
     * 
     * @param array $data
     * @return \Illuminate\Http\Response
     */
    private function exportToPdf(array $data)
    {
        $pdf = Pdf::loadView('pages.reports.supply-usage-pdf', $data);
        $filename = 'laporan_pemakaian_supply_' . date('Y-m-d_H-i-s') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Export to CSV format
     * 
     * @param array $data
     * @return \Illuminate\Http\Response
     */
    private function exportToCsv(array $data)
    {
        $filename = 'laporan_pemakaian_supply_' . date('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');

            // Add BOM for UTF-8
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Headers
            fputcsv($file, ['No', 'Tanggal', 'Batch', 'Kandang', 'Jenis Supply', 'Jumlah', 'Satuan', 'Harga Satuan', 'Total Harga']);

            // Data
            $no = 1;
            foreach ($data['data'] as $item) {
                fputcsv($file, [
                    $no,
                    $item['usage_date']->format('d/m/Y'),
                    $item['livestock_name'],
                    $item['coop_name'],
                    $item['supply_name'],
                    $item['quantity'],
                    $item['unit'],
                    number_format($item['unit_cost'], 0, ',', '.'),
                    number_format($item['total_cost'], 0, ',', '.')
                ]);
                $no++;
            }

            // Totals
            fputcsv($file, ['TOTAL', '', '', '', '', '', '', '', number_format($data['totals']['total_cost'], 0, ',', '.')]);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export supply usage report (main method)
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function exportSupplyUsageReport($request)
    {
        try {
            // Validate and generate report
            $params = $this->validateParams($request->all());
            $reportData = $this->generateSupplyUsageReport($params);

            // Export in requested format
            $format = $request->export_format ?? 'html';
            return $this->exportSupplyUsage($reportData, $format);
        } catch (\Exception $e) {
            Log::error('Error exporting supply usage report: ' . $e->getMessage());
            Log::debug('Stack trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }
}

<?php

namespace App\Services\Report;

use App\Models\BatchWorker;
use App\Models\Farm;
use App\Models\Coop;
use App\Models\Livestock;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Barryvdh\DomPDF\Facade\Pdf;

class BatchWorkerReportService
{
    /**
     * Generate batch worker report data
     * Extracted from ReportsController::exportBatchWorker()
     * 
     * @param array $params
     * @return array
     */
    public function generateBatchWorkerReport(array $params): array
    {
        $farm = Farm::findOrFail($params['farm_id']);
        $coop = Coop::findOrFail($params['coop_id']);
        $livestock = Livestock::findOrFail($params['periode']);
        $startDate = Carbon::parse($params['start_date']);
        $endDate = Carbon::parse($params['end_date']);

        Log::info('Generating Batch Worker Report', [
            'farm_id' => $farm->id,
            'farm_name' => $farm->name,
            'coop_id' => $coop->id,
            'coop_name' => $coop->name,
            'livestock_id' => $livestock->id,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'report_type' => $params['report_type'],
            'user_id' => Auth::id()
        ]);

        // Get batch workers data
        $batchWorkers = $this->getBatchWorkersData($farm, $startDate, $endDate);

        // Process data based on report type
        $processedData = $this->processBatchWorkersData($batchWorkers, $params['report_type']);

        Log::info('Batch Worker Report generated successfully', [
            'farm_id' => $farm->id,
            'batch_workers_count' => $batchWorkers->count(),
            'processed_data_count' => count($processedData),
            'report_type' => $params['report_type']
        ]);

        return [
            'farm' => $farm,
            'coop' => $coop,
            'livestock' => $livestock,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'batchWorkers' => $batchWorkers,
            'processedData' => $processedData,
            'reportType' => $params['report_type']
        ];
    }

    /**
     * Get batch workers data with proper filtering
     * 
     * @param Farm $farm
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getBatchWorkersData(Farm $farm, Carbon $startDate, Carbon $endDate)
    {
        $batchWorkers = BatchWorker::with(['worker', 'livestock.kandang'])
            ->whereHas('livestock', function ($query) use ($farm) {
                $query->where('farm_id', $farm->id);
            })
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->where('start_date', '<=', $startDate)
                            ->where(function ($q) use ($endDate) {
                                $q->whereNull('end_date')
                                    ->orWhere('end_date', '>=', $endDate);
                            });
                    });
            })
            ->orderBy('start_date')
            ->get();

        Log::debug('Batch workers data retrieved', [
            'farm_id' => $farm->id,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'batch_workers_count' => $batchWorkers->count(),
            'batch_worker_ids' => $batchWorkers->pluck('id')->toArray()
        ]);

        return $batchWorkers;
    }

    /**
     * Process batch workers data based on report type
     * 
     * @param \Illuminate\Database\Eloquent\Collection $batchWorkers
     * @param string $reportType
     * @return array
     */
    private function processBatchWorkersData($batchWorkers, string $reportType): array
    {
        if ($reportType === 'detail') {
            return $this->processDetailData($batchWorkers);
        } else {
            return $this->processSimpleData($batchWorkers);
        }
    }

    /**
     * Process detail data - individual worker records
     * 
     * @param \Illuminate\Database\Eloquent\Collection $batchWorkers
     * @return array
     */
    private function processDetailData($batchWorkers): array
    {
        $processedData = [];

        foreach ($batchWorkers as $worker) {
            $processedData[] = [
                'worker_id' => $worker->worker->id,
                'worker_name' => $worker->worker->name,
                'kandang_name' => $worker->livestock->kandang->nama ?? 'Unknown',
                'livestock_name' => $worker->livestock->name ?? 'Unknown',
                'start_date' => $worker->start_date,
                'end_date' => $worker->end_date,
                'period_display' => $worker->start_date->format('d/m/Y') . ' - ' .
                    ($worker->end_date ? $worker->end_date->format('d/m/Y') : 'Sekarang'),
                'role' => $worker->role ?? '-',
                'status' => $worker->status,
                'notes' => $worker->notes ?? '-',
                'duration_days' => $worker->end_date ?
                    $worker->start_date->diffInDays($worker->end_date) :
                    $worker->start_date->diffInDays(Carbon::now())
            ];
        }

        Log::debug('Detail data processed', [
            'processed_count' => count($processedData),
            'worker_names' => collect($processedData)->pluck('worker_name')->toArray()
        ]);

        return $processedData;
    }

    /**
     * Process simple data - aggregated worker summary
     * 
     * @param \Illuminate\Database\Eloquent\Collection $batchWorkers
     * @return array
     */
    private function processSimpleData($batchWorkers): array
    {
        $aggregatedData = [];

        // Group by worker
        $workerGroups = $batchWorkers->groupBy('worker.id');

        foreach ($workerGroups as $workerId => $workerBatches) {
            $firstBatch = $workerBatches->first();
            $totalDuration = 0;
            $kandangList = [];
            $roleList = [];

            foreach ($workerBatches as $batch) {
                $duration = $batch->end_date ?
                    $batch->start_date->diffInDays($batch->end_date) :
                    $batch->start_date->diffInDays(Carbon::now());
                $totalDuration += $duration;

                if (!in_array($batch->livestock->kandang->nama, $kandangList)) {
                    $kandangList[] = $batch->livestock->kandang->nama;
                }

                if ($batch->role && !in_array($batch->role, $roleList)) {
                    $roleList[] = $batch->role;
                }
            }

            $aggregatedData[] = [
                'worker_id' => $workerId,
                'worker_name' => $firstBatch->worker->name,
                'total_assignments' => $workerBatches->count(),
                'total_duration_days' => $totalDuration,
                'kandang_list' => implode(', ', $kandangList),
                'role_list' => implode(', ', $roleList),
                'status' => $firstBatch->status,
                'first_assignment' => $workerBatches->min('start_date'),
                'last_assignment' => $workerBatches->max('end_date') ?? 'Aktif'
            ];
        }

        Log::debug('Simple data processed', [
            'aggregated_count' => count($aggregatedData),
            'total_workers' => $workerGroups->count(),
            'worker_names' => collect($aggregatedData)->pluck('worker_name')->toArray()
        ]);

        return $aggregatedData;
    }

    /**
     * Export batch worker report to Excel
     * Extracted from ReportsController::exportBatchWorkerToExcel()
     * 
     * @param array $data
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportToExcel(array $data)
    {
        Log::info('Exporting batch worker report to Excel', [
            'farm_name' => $data['farm']->name,
            'batch_workers_count' => $data['batchWorkers']->count()
        ]);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set header
        $sheet->setCellValue('A1', 'LAPORAN PENUGASAN PEKERJA');
        $sheet->setCellValue('A3', 'FARM');
        $sheet->setCellValue('B3', ': ' . $data['farm']->nama);
        $sheet->setCellValue('D3', 'PERIODE');
        $sheet->setCellValue('E3', ': ' . $data['startDate']->format('d F Y') . ' - ' . $data['endDate']->format('d F Y'));

        // Set table headers
        $headers = ['No', 'Nama Pekerja', 'Kandang', 'Periode', 'Peran', 'Status', 'Catatan'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '5', $header);
            $col++;
        }

        // Fill data
        $row = 6;
        foreach ($data['batchWorkers'] as $index => $worker) {
            $sheet->setCellValue('A' . $row, $index + 1);
            $sheet->setCellValue('B' . $row, $worker->worker->name);
            $sheet->setCellValue('C' . $row, $worker->livestock->kandang->nama);
            $sheet->setCellValue('D' . $row, $worker->start_date->format('d/m/Y') . ' - ' .
                ($worker->end_date ? $worker->end_date->format('d/m/Y') : 'Sekarang'));
            $sheet->setCellValue('E' . $row, $worker->role ?? '-');
            $sheet->setCellValue('F' . $row, $worker->status);
            $sheet->setCellValue('G' . $row, $worker->notes ?? '-');
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Create Excel file
        $writer = new Xlsx($spreadsheet);
        $filename = 'Laporan_Penugasan_Pekerja_' . date('Y-m-d_His') . '.xlsx';

        Log::info('Excel export completed', [
            'filename' => $filename,
            'rows_count' => $row - 6
        ]);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    /**
     * Export batch worker report to PDF
     * Extracted from ReportsController::exportBatchWorkerToPdf()
     * 
     * @param array $data
     * @return \Illuminate\Http\Response
     */
    public function exportToPdf(array $data)
    {
        Log::info('Exporting batch worker report to PDF', [
            'farm_name' => $data['farm']->name,
            'batch_workers_count' => $data['batchWorkers']->count()
        ]);

        $pdf = Pdf::loadView('pages.reports.batch-worker', $data);
        $pdf->setPaper('a4', 'landscape');

        $filename = 'Laporan_Penugasan_Pekerja_' . date('Y-m-d_His') . '.pdf';

        Log::info('PDF export completed', [
            'filename' => $filename
        ]);

        return $pdf->download($filename);
    }

    /**
     * Export batch worker report to CSV
     * Extracted from ReportsController::exportBatchWorkerToCsv()
     * 
     * @param array $data
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportToCsv(array $data)
    {
        Log::info('Exporting batch worker report to CSV', [
            'farm_name' => $data['farm']->name,
            'batch_workers_count' => $data['batchWorkers']->count()
        ]);

        $filename = 'Laporan_Penugasan_Pekerja_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');

            // Add BOM for Excel UTF-8 compatibility
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Headers
            fputcsv($file, ['LAPORAN PENUGASAN PEKERJA']);
            fputcsv($file, ['']);
            fputcsv($file, ['FARM', $data['farm']->nama]);
            fputcsv($file, ['PERIODE', $data['startDate']->format('d F Y') . ' - ' . $data['endDate']->format('d F Y')]);
            fputcsv($file, ['']);

            // Table headers
            fputcsv($file, ['No', 'Nama Pekerja', 'Kandang', 'Periode', 'Peran', 'Status', 'Catatan']);

            // Data
            foreach ($data['batchWorkers'] as $index => $worker) {
                fputcsv($file, [
                    $index + 1,
                    $worker->worker->name,
                    $worker->livestock->kandang->nama,
                    $worker->start_date->format('d/m/Y') . ' - ' . ($worker->end_date ? $worker->end_date->format('d/m/Y') : 'Sekarang'),
                    $worker->role ?? '-',
                    $worker->status,
                    $worker->notes ?? '-'
                ]);
            }

            fclose($file);
        };

        Log::info('CSV export completed', [
            'filename' => $filename
        ]);

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export batch worker report based on format
     * 
     * @param array $data
     * @param string $format
     * @return mixed
     */
    public function export(array $data, string $format)
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
                return view('pages.reports.batch-worker', $data);
        }
    }

    /**
     * Validate batch worker report parameters
     * 
     * @param array $params
     * @return array
     */
    public function validateParams(array $params): array
    {
        $rules = [
            'farm_id' => 'required|uuid|exists:farms,id',
            'coop_id' => 'required|uuid|exists:coops,id',
            'tahun' => 'required|integer',
            'periode' => 'required|uuid|exists:livestocks,id',
            'report_type' => 'required|in:detail,simple',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date'
        ];

        $validator = validator($params, $rules);

        if ($validator->fails()) {
            Log::warning('Batch worker report validation failed', [
                'errors' => $validator->errors()->toArray(),
                'params' => $params
            ]);
            throw new \InvalidArgumentException('Invalid parameters: ' . implode(', ', $validator->errors()->all()));
        }

        return $params;
    }

    /**
     * Get batch worker statistics
     * 
     * @param array $data
     * @return array
     */
    public function getStatistics(array $data): array
    {
        $batchWorkers = $data['batchWorkers'];

        $stats = [
            'total_workers' => $batchWorkers->count(),
            'active_workers' => $batchWorkers->whereNull('end_date')->count(),
            'completed_assignments' => $batchWorkers->whereNotNull('end_date')->count(),
            'unique_workers' => $batchWorkers->pluck('worker.id')->unique()->count(),
            'kandang_coverage' => $batchWorkers->pluck('livestock.kandang.nama')->unique()->count(),
            'average_duration' => 0
        ];

        // Calculate average duration
        $totalDuration = 0;
        $completedCount = 0;

        foreach ($batchWorkers as $worker) {
            if ($worker->end_date) {
                $totalDuration += $worker->start_date->diffInDays($worker->end_date);
                $completedCount++;
            }
        }

        $stats['average_duration'] = $completedCount > 0 ? round($totalDuration / $completedCount, 1) : 0;

        Log::debug('Batch worker statistics calculated', $stats);

        return $stats;
    }
}

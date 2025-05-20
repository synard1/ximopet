<?php

namespace App\Livewire\Reports;

use App\Models\BatchWorker;
use App\Models\Farm;
use App\Models\Worker;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class BatchWorkerReport extends Component
{
    use WithPagination;

    public $startDate;
    public $endDate;
    public $farmId;
    public $workerId;
    public $status;
    public $exportFormat = 'excel';

    protected $queryString = [
        'startDate' => ['except' => ''],
        'endDate' => ['except' => ''],
        'farmId' => ['except' => ''],
        'workerId' => ['except' => ''],
        'status' => ['except' => '']
    ];

    public function mount()
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
    }

    public function render()
    {
        $query = BatchWorker::query()
            ->with(['batch', 'worker', 'farm', 'creator', 'updater'])
            ->when($this->startDate, function ($query) {
                return $query->where('start_date', '>=', $this->startDate);
            })
            ->when($this->endDate, function ($query) {
                return $query->where('start_date', '<=', $this->endDate);
            })
            ->when($this->workerId, function ($query) {
                return $query->where('worker_id', $this->workerId);
            })
            ->when($this->status, function ($query) {
                return $query->where('status', $this->status);
            });

        $batchWorkers = $query->paginate(10);

        $summary = $query->select([
            'status',
            DB::raw('COUNT(*) as total_assignments'),
            DB::raw('COUNT(DISTINCT worker_id) as unique_workers'),
            DB::raw('COUNT(DISTINCT livestock_id) as unique_batches'),
        ])
            ->groupBy('status')
            ->get();

        return view('livewire.reports.batch-worker-report', [
            'batchWorkers' => $batchWorkers,
            'summary' => $summary,
            'farms' => Farm::all(),
            'workers' => Worker::all(),
            'statuses' => ['active', 'completed', 'terminated']
        ]);
    }

    public function export()
    {
        $query = BatchWorker::query()
            ->with(['batch', 'worker', 'farm', 'creator', 'updater'])
            ->when($this->startDate, function ($query) {
                return $query->where('start_date', '>=', $this->startDate);
            })
            ->when($this->endDate, function ($query) {
                return $query->where('start_date', '<=', $this->endDate);
            })
            ->when($this->farmId, function ($query) {
                return $query->where('farm_id', $this->farmId);
            })
            ->when($this->workerId, function ($query) {
                return $query->where('worker_id', $this->workerId);
            })
            ->when($this->status, function ($query) {
                return $query->where('status', $this->status);
            });

        $data = $query->get();

        if ($this->exportFormat === 'excel') {
            return $this->exportToExcel($data);
        } else {
            return $this->exportToPdf($data);
        }
    }

    protected function exportToExcel($data)
    {
        $fileName = 'batch-worker-report-' . now()->format('Y-m-d') . '.xlsx';

        return response()->streamDownload(function () use ($data) {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Set headers
            $sheet->setCellValue('A1', 'Batch');
            $sheet->setCellValue('B1', 'Worker');
            $sheet->setCellValue('C1', 'Farm');
            $sheet->setCellValue('D1', 'Start Date');
            $sheet->setCellValue('E1', 'End Date');
            $sheet->setCellValue('F1', 'Status');
            $sheet->setCellValue('G1', 'Notes');
            $sheet->setCellValue('H1', 'Created By');
            $sheet->setCellValue('I1', 'Created At');

            // Add data
            $row = 2;
            foreach ($data as $item) {
                $sheet->setCellValue('A' . $row, $item->batch->name);
                $sheet->setCellValue('B' . $row, $item->worker->name);
                $sheet->setCellValue('C' . $row, $item->farm->name);
                $sheet->setCellValue('D' . $row, $item->start_date->format('Y-m-d'));
                $sheet->setCellValue('E' . $row, $item->end_date ? $item->end_date->format('Y-m-d') : '');
                $sheet->setCellValue('F' . $row, $item->status);
                $sheet->setCellValue('G' . $row, $item->notes);
                $sheet->setCellValue('H' . $row, $item->creator->name);
                $sheet->setCellValue('I' . $row, $item->created_at->format('Y-m-d H:i:s'));
                $row++;
            }

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName);
    }

    protected function exportToPdf($data)
    {
        $fileName = 'batch-worker-report-' . now()->format('Y-m-d') . '.pdf';

        return response()->streamDownload(function () use ($data) {
            $pdf = Pdf::loadView('pages.reports.batch-worker-pdf', [
                'data' => $data,
                'startDate' => $this->startDate,
                'endDate' => $this->endDate,
                'farm' => $this->farmId ? Farm::find($this->farmId)->name : 'All Farms',
                'worker' => $this->workerId ? Worker::find($this->workerId)->name : 'All Workers',
                'status' => $this->status ?: 'All Statuses'
            ]);

            echo $pdf->output();
        }, $fileName);
    }

    public function resetFilters()
    {
        $this->reset(['startDate', 'endDate', 'farmId', 'workerId', 'status']);
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
    }
}

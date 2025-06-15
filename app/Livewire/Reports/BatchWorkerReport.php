<?php

namespace App\Livewire\Reports;

use App\Models\BatchWorker;
use App\Models\Farm;
use App\Models\Worker;
use App\Models\Coop;
use App\Models\Livestock;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class BatchWorkerReport extends Component
{
    use WithPagination;

    public $startDate;
    public $endDate;
    public $farmId;
    public $workerId;
    public $status;
    public $exportFormat = 'excel';
    public $farms = [];
    public $format = 'html';
    public $coopId;
    public $tahun;
    public $periodeId;
    public $reportType = 'detail';
    public $coops = [];
    public $tahunList = [];
    public $periodeList = [];
    public $showReport = false;
    public $reportData = [];

    protected $queryString = [
        'startDate' => ['except' => ''],
        'endDate' => ['except' => ''],
        'farmId' => ['except' => ''],
        'workerId' => ['except' => ''],
        'status' => ['except' => '']
    ];

    protected $rules = [
        'farmId' => 'required|uuid|exists:farms,id',
        'coopId' => 'required|uuid|exists:coops,id',
        'tahun' => 'required|integer',
        'periodeId' => 'required|uuid|exists:livestocks,id',
        'reportType' => 'required|in:detail,simple'
    ];

    protected $messages = [
        'farmId.required' => 'Farm harus dipilih',
        'farmId.uuid' => 'ID Farm tidak valid',
        'farmId.exists' => 'Farm yang dipilih tidak valid',
        'coopId.required' => 'Kandang harus dipilih',
        'coopId.uuid' => 'ID Kandang tidak valid',
        'coopId.exists' => 'Kandang yang dipilih tidak valid',
        'tahun.required' => 'Tahun harus dipilih',
        'tahun.integer' => 'Format tahun tidak valid',
        'periodeId.required' => 'Periode harus dipilih',
        'periodeId.uuid' => 'ID Periode tidak valid',
        'periodeId.exists' => 'Periode yang dipilih tidak valid',
        'reportType.required' => 'Jenis laporan harus dipilih',
        'reportType.in' => 'Jenis laporan tidak valid'
    ];

    public function mount()
    {
        $this->farms = Farm::orderBy('name')->get();
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
        $this->tahun = now()->year;
    }

    public function updatedFarmId($value)
    {
        $this->coopId = null;
        $this->tahun = null;
        $this->periodeId = null;
        $this->showReport = false;

        if ($value) {
            $this->coops = Coop::where('farm_id', $value)
                ->orderBy('name')
                ->get();
        } else {
            $this->coops = [];
        }
    }

    public function updatedCoopId($value)
    {
        $this->tahun = null;
        $this->periodeId = null;
        $this->showReport = false;

        if ($value) {
            $this->tahunList = Livestock::where('coop_id', $value)
                ->selectRaw('YEAR(start_date) as tahun')
                ->distinct()
                ->orderByDesc('tahun')
                ->pluck('tahun')
                ->toArray();
        } else {
            $this->tahunList = [];
        }
    }

    public function updatedTahun($value)
    {
        $this->periodeId = null;
        $this->showReport = false;

        if ($value && $this->coopId) {
            $this->periodeList = Livestock::where('coop_id', $this->coopId)
                ->whereYear('start_date', $value)
                ->orderBy('start_date')
                ->get();
        } else {
            $this->periodeList = [];
        }
    }

    public function generateReport()
    {

        // dd($this->all());
        $this->validate();

        try {
            $farm = Farm::findOrFail($this->farmId);
            $coop = Coop::findOrFail($this->coopId);
            $livestock = Livestock::findOrFail($this->periodeId);

            $batchWorkers = BatchWorker::with(['worker', 'livestock.kandang'])
                ->where('livestock_id', $this->periodeId)
                ->orderBy('start_date')
                ->get();

            $this->reportData = [
                'farm' => $farm,
                'coop' => $coop,
                'livestock' => $livestock,
                'batchWorkers' => $batchWorkers,
                'reportType' => $this->reportType
            ];

            $this->showReport = true;

            // dd($this->reportData);
        } catch (\Exception $e) {
            $this->dispatch('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function exportReport($format)
    {
        $this->validate();

        try {
            $farm = Farm::findOrFail($this->farmId);
            $coop = Coop::findOrFail($this->coopId);
            $livestock = Livestock::findOrFail($this->periodeId);

            $batchWorkers = BatchWorker::with(['worker', 'livestock.kandang'])
                ->where('livestock_id', $this->periodeId)
                ->orderBy('start_date')
                ->get();

            $data = [
                'farm' => $farm,
                'coop' => $coop,
                'livestock' => $livestock,
                'batchWorkers' => $batchWorkers,
                'reportType' => $this->reportType
            ];

            switch ($format) {
                case 'excel':
                    return $this->exportToExcel($data);
                case 'pdf':
                    return $this->exportToPdf($data);
                default:
                    $this->dispatch('error', 'Format export tidak valid');
            }
        } catch (\Exception $e) {
            $this->dispatch('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    private function exportToExcel($data)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set header
        $sheet->setCellValue('A1', 'Laporan Penugasan Pekerja');
        $sheet->setCellValue('A2', 'Farm: ' . $data['farm']->name);
        $sheet->setCellValue('A3', 'Kandang: ' . $data['coop']->name);
        $sheet->setCellValue('A4', 'Periode: ' . $data['livestock']->name);

        // Set table header
        $headers = ['No', 'Nama Pekerja', 'Kandang', 'Tanggal Mulai', 'Tanggal Selesai', 'Peran', 'Status', 'Catatan'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '6', $header);
            $col++;
        }

        // Fill data
        $row = 7;
        foreach ($data['batchWorkers'] as $index => $worker) {
            $sheet->setCellValue('A' . $row, $index + 1);
            $sheet->setCellValue('B' . $row, $worker->worker->nama);
            $sheet->setCellValue('C' . $row, $worker->livestock->kandang->name);
            $sheet->setCellValue('D' . $row, $worker->start_date->format('d/m/Y'));
            $sheet->setCellValue('E' . $row, $worker->end_date ? $worker->end_date->format('d/m/Y') : '-');
            $sheet->setCellValue('F' . $row, $worker->peran);
            $sheet->setCellValue('G' . $row, ucfirst($worker->status));
            $sheet->setCellValue('H' . $row, $worker->catatan ?? '-');
            $row++;
        }

        // Auto size columns
        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Create Excel file
        $writer = new Xlsx($spreadsheet);
        $filename = 'laporan_penugasan_pekerja_' . date('Y-m-d_His') . '.xlsx';
        $path = storage_path('app/public/' . $filename);
        $writer->save($path);

        return response()->download($path)->deleteFileAfterSend();
    }

    private function exportToPdf($data)
    {
        $pdf = PDF::loadView('pages.reports.batch-worker', $data);
        $pdf->setPaper('a4', 'landscape');

        $filename = 'laporan_penugasan_pekerja_' . date('Y-m-d_His') . '.pdf';
        return $pdf->download($filename);
    }

    public function render()
    {
        return view('livewire.reports.batch-worker-report');
    }

    public function resetForm()
    {
        $this->reset(['farmId', 'coopId', 'tahun', 'periodeId', 'reportType', 'showReport', 'reportData']);
        $this->resetValidation();
    }
}

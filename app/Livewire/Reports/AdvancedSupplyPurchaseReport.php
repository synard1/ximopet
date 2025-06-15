<?php

namespace App\Livewire\Reports;

use Livewire\Component;
use App\Models\Partner;
use App\Models\SupplyPurchaseBatch;
use App\Models\SupplyPurchase;
use App\Models\Supply;
use App\Models\Farm;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AdvancedSupplyPurchaseReport extends Component
{
    public $farm;
    public $year;
    public $supplierId;
    public $supplyId;
    public $status;
    public $startDate;
    public $endDate;

    public $years = [];
    public $suppliers = [];
    public $supplies = [];
    public $farms = [];
    public $statuses = [
        '' => 'Semua Status',
        'draft' => 'Draft',
        'confirmed' => 'Confirmed',
        'arrived' => 'Arrived',
        'completed' => 'Completed',
    ];

    public $batches = [];
    public $summary = [];
    public $message = null;

    public $invoiceNumbers = [];
    public $invoiceNumber;

    public $invoiceDetail;

    public function mount()
    {
        $this->years = range(date('Y'), date('Y') - 5);
        $this->suppliers = Partner::where('type', 'Supplier')->orderBy('name')->get();
        $this->supplies = Supply::orderBy('name')->get();
        $this->farms = Farm::orderBy('name')->get();
        $this->startDate = now()->subMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
    }

    public function filterReport()
    {
        $query = SupplyPurchaseBatch::with([
            'supplier',
            'supplyPurchases.supply',
            'supplyPurchases.unit',
            'farm'
        ])
            ->when($this->startDate, fn($q) => $q->where('date', '>=', $this->startDate))
            ->when($this->endDate, fn($q) => $q->where('date', '<=', $this->endDate))
            ->when($this->supplierId, fn($q) => $q->where('supplier_id', $this->supplierId))
            ->when($this->status, fn($q) => $q->where('status', $this->status))
            ->when($this->farm, fn($q) => $q->where('farm_id', $this->farm))
            ->when($this->year, fn($q) => $q->whereYear('date', $this->year))
            ->when($this->supplyId, function ($q) {
                $q->whereHas('supplyPurchases', function ($sq) {
                    $sq->where('supply_id', $this->supplyId);
                });
            })
            ->when($this->invoiceNumber, fn($q) => $q->where('invoice_number', $this->invoiceNumber));

        $this->batches = $query->get();
        if (!$this->batches instanceof Collection) {
            $this->batches = collect($this->batches);
        }

        if ($this->batches->isEmpty()) {
            $this->summary = [];
            $this->message = 'Tidak ada data pembelian supply untuk filter ini.';
            $this->invoiceDetail = null;
            return;
        }

        // Summary
        $batches = $this->batches; // ensure Collection
        $this->summary = [
            'total_batches' => $batches->count(),
            'total_purchases' => $batches->sum(fn($batch) => $batch->supplyPurchases->count()),
            'total_suppliers' => $batches->unique('supplier_id')->count(),
            'total_farms' => $batches->pluck('farm_id')->unique()->count(),
            'total_value' => $batches->sum(fn($batch) => $batch->supplyPurchases->sum(fn($purchase) => $purchase->quantity * $purchase->price_per_unit)),
            'total_quantity' => $batches->sum(fn($batch) => $batch->supplyPurchases->sum('quantity')),
        ];
        $this->message = null;

        // Fetch invoice detail if invoiceNumber is set
        if ($this->invoiceNumber) {
            $this->fetchInvoiceDetail();
        } else {
            $this->invoiceDetail = null;
        }
    }

    public function exportExcel()
    {
        // Implement export logic or trigger controller route
        // You can use a redirect or emit event for JS to handle
        $params = http_build_query([
            'farm' => $this->farm,
            'tahun' => $this->year,
            'supplier' => $this->supplierId,
            'supply' => $this->supplyId,
            'status' => $this->status,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'export_format' => 'excel'
        ]);
        return redirect("/report/supply-purchase/export?$params");
    }

    public function resetFilters()
    {
        $this->farm = null;
        $this->year = null;
        $this->supplierId = null;
        $this->supplyId = null;
        $this->status = null;
        $this->startDate = now()->subMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
        $this->batches = [];
        $this->summary = [];
        $this->message = null;
    }

    public function updatedYear()
    {
        $this->fetchInvoiceNumbers();
    }
    public function updatedSupplierId()
    {
        $this->fetchInvoiceNumbers();
    }

    public function fetchInvoiceNumbers()
    {
        if ($this->year && $this->supplierId) {
            $this->invoiceNumbers = SupplyPurchaseBatch::whereYear('date', $this->year)
                ->where('supplier_id', $this->supplierId)
                ->pluck('invoice_number')
                ->unique()
                ->values();
        } else {
            $this->invoiceNumbers = [];
        }
    }

    public function updatedInvoiceNumber()
    {
        if ($this->invoiceNumber) {
            $this->fetchInvoiceDetail();
        } else {
            $this->invoiceDetail = null;
        }
    }

    public function fetchInvoiceDetail()
    {
        $batch = SupplyPurchaseBatch::with(['supplier', 'farm', 'supplyPurchases.supply', 'supplyPurchases.unit'])
            ->where('invoice_number', $this->invoiceNumber)
            ->when($this->supplierId, fn($q) => $q->where('supplier_id', $this->supplierId))
            ->when($this->year, fn($q) => $q->whereYear('date', $this->year))
            ->first();

        $this->invoiceDetail = $batch;
        \Log::info('[AdvancedSupplyPurchaseReport] fetchInvoiceDetail', [
            'invoice_number' => $this->invoiceNumber,
            'supplier_id' => $this->supplierId,
            'year' => $this->year,
            'result' => $batch ? $batch->id : null
        ]);
    }

    public function showHtmlReport()
    {
        $params = http_build_query([
            'farm' => $this->farm,
            'tahun' => $this->year,
            'supplier' => $this->supplierId,
            'supply' => $this->supplyId,
            'status' => $this->status,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'invoice_number' => $this->invoiceNumber,
            'print' => 'true'
        ]);
        $this->dispatch('openPrintWindow', url: "/report/supply-purchase/html?$params");
        \Log::info('[AdvancedSupplyPurchaseReport] showHtmlReport', ['url' => "/report/supply-purchase/html?$params"]);
    }

    public function render()
    {
        return view('livewire.reports.advanced-supply-purchase-report');
    }
}

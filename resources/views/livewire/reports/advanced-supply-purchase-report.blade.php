<div class="container-fluid px-0 w-100">
    <div class="row justify-content-center w-100">
        <div class="col-12">
            <div class="card shadow-sm mt-4 mb-5 w-100">
                <div class="gradient-header d-flex align-items-center">
                    <i class="fa fa-filter fa-lg me-2"></i>
                    <h5 class="mb-0">Filter Laporan Pembelian Supply/OVK</h5>
                </div>
                <div class="summary-box">
                    <form wire:submit.prevent="filterReport" class="mb-0">
                        <div class="row g-3 mb-3">
                            <div class="col-md-2">
                                <label class="form-label required">Farm</label>
                                <select class="form-select" wire:model="farm">
                                    <option value="">Pilih Farm</option>
                                    @foreach($farms as $farm)
                                    <option value="{{ $farm->id }}">{{ $farm->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label required">Tahun</label>
                                <select class="form-select" wire:model="year">
                                    <option value="">Pilih Tahun</option>
                                    @foreach($years as $y)
                                    <option value="{{ $y }}">{{ $y }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Supplier</label>
                                <select class="form-select" wire:model.live="supplierId">
                                    <option value="">Semua Supplier</option>
                                    @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Jenis Supply</label>
                                <select class="form-select" wire:model="supplyId">
                                    <option value="">Semua Supply</option>
                                    @foreach($supplies as $supply)
                                    <option value="{{ $supply->id }}">{{ $supply->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Status</label>
                                <select class="form-select" wire:model="status">
                                    <option value="">Semua Status</option>
                                    <option value="draft">Draft</option>
                                    <option value="confirmed">Confirmed</option>
                                    <option value="arrived">Arrived</option>
                                    <option value="completed">Completed</option>
                                </select>
                            </div>
                            @if($year && $supplierId && count($invoiceNumbers))
                            <div class="col-md-3">
                                <label class="form-label">No. Invoice/Transaksi</label>
                                <select class="form-select" wire:model="invoiceNumber">
                                    <option value="">Pilih Invoice</option>
                                    @foreach($invoiceNumbers as $inv)
                                    <option value="{{ $inv }}">{{ $inv }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @endif
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-2">
                                <label class="form-label required">Tanggal Mulai</label>
                                <input type="date" class="form-control" wire:model="startDate">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label required">Tanggal Selesai</label>
                                <input type="date" class="form-control" wire:model="endDate">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-warning w-100" id="showButton">
                                    <i class="fa fa-search me-2"></i>Tampilkan
                                </button>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="button" class="btn btn-success w-100 ms-2" wire:click="exportExcel">
                                    <i class="fa fa-file-excel me-2"></i>Export Excel
                                </button>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="button" class="btn btn-info w-100 ms-2" wire:click="showHtmlReport">
                                    <i class="fa fa-print me-2"></i>Lihat/Print HTML
                                </button>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="button" class="btn btn-secondary w-100 ms-2" wire:click="resetFilters">
                                    <i class="fa fa-undo me-2"></i>Reset Filter
                                </button>
                            </div>
                        </div>
                    </form>
                    @if($summary && count($summary))
                    <div class="row mb-2">
                        <div class="col-md-6 col-lg-3 mb-2">
                            <span class="summary-label">Total Batch</span><br>
                            <span class="summary-value">{{ $summary['total_batches'] ?? '-' }}</span>
                        </div>
                        <div class="col-md-6 col-lg-3 mb-2">
                            <span class="summary-label">Total Pembelian</span><br>
                            <span class="summary-value">{{ $summary['total_purchases'] ?? '-' }}</span>
                        </div>
                        <div class="col-md-6 col-lg-3 mb-2">
                            <span class="summary-label">Total Supplier</span><br>
                            <span class="summary-value">{{ $summary['total_suppliers'] ?? '-' }}</span>
                        </div>
                        <div class="col-md-6 col-lg-3 mb-2">
                            <span class="summary-label">Total Farm</span><br>
                            <span class="summary-value">{{ $summary['total_farms'] ?? '-' }}</span>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-6 col-lg-3 mb-2">
                            <span class="summary-label">Total Nilai</span><br>
                            <span class="summary-value">Rp {{ number_format($summary['total_value'] ?? 0, 0, ',', '.')
                                }}</span>
                        </div>
                        <div class="col-md-6 col-lg-3 mb-2">
                            <span class="summary-label">Total Quantity</span><br>
                            <span class="summary-value">{{ number_format($summary['total_quantity'] ?? 0, 2) }}</span>
                        </div>
                    </div>
                    @endif
                </div>
                <div class="px-4 pb-4 w-100">
                    @if($batches && count($batches))
                    <h5 class="mt-4 mb-3"><i class="fa fa-list-alt me-2"></i>Detail Batch Pembelian Supply/OVK</h5>
                    <div class="table-responsive w-100">
                        <table class="table table-bordered table-hover table-striped align-middle mb-0 w-100">
                            <thead>
                                <tr>
                                    <th class="text-center">No</th>
                                    <th class="text-center">Tanggal</th>
                                    <th class="text-center">Invoice</th>
                                    <th class="text-center">Supplier</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Total Items</th>
                                    <th class="text-center">Total Nilai</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($batches as $i => $batch)
                                <tr>
                                    <td class="text-center">{{ $i + 1 }}</td>
                                    <td class="text-center">{{ \Carbon\Carbon::parse($batch->date)->format('d-m-Y') }}
                                    </td>
                                    <td class="text-center">{{ $batch->invoice_number }}</td>
                                    <td class="text-nowrap">{{ $batch->supplier->name ?? '-' }}</td>
                                    <td class="text-center">
                                        <span class="badge badge-status badge-{{ strtolower($batch->status) }}">
                                            {{ ucfirst($batch->status) }}
                                        </span>
                                    </td>
                                    <td class="text-center">{{ $batch->supplyPurchases->count() }}</td>
                                    <td class="text-end">Rp {{
                                        number_format($batch->supplyPurchases->sum(function($p){return $p->quantity *
                                        $p->price_per_unit;}), 0, ',', '.') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                    @if($message)
                    <div class="alert alert-warning mt-3">{{ $message }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @push('styles')
    <style>
        .gradient-header {
            background: linear-gradient(90deg, #f857a6 0%, #ff5858 100%);
            color: #fff;
            padding: 18px 24px 12px 24px;
            border-radius: 10px 10px 0 0;
            margin-bottom: 0;
        }

        .summary-box {
            background: #f8f9fa;
            border-radius: 0 0 10px 10px;
            padding: 18px 24px 12px 24px;
            margin-bottom: 24px;
        }

        .summary-label {
            color: #888;
            font-size: 0.95em;
        }

        .summary-value {
            font-weight: bold;
            font-size: 1.15em;
        }

        .table th,
        .table td {
            vertical-align: middle;
            white-space: nowrap;
        }

        .table thead th {
            background: #f1f3f6;
            font-weight: 600;
            position: sticky;
            top: 0;
            z-index: 2;
        }

        .badge-status {
            font-size: 0.95em;
            padding: 0.4em 0.7em;
        }

        .badge-cancelled {
            background: #f8d7da !important;
            color: #c82333 !important;
        }

        .badge-arrived {
            background: #d1ecf1 !important;
            color: #0c5460 !important;
        }

        .badge-confirmed {
            background: #fff3cd !important;
            color: #856404 !important;
        }

        .badge-completed {
            background: #d4edda !important;
            color: #155724 !important;
        }

        .table-responsive {
            overflow-x: auto;
        }

        .w-100 {
            width: 100% !important;
        }

        .table td,
        .table th {
            word-break: break-word;
        }
    </style>
    @endpush
</div>

@if($invoiceDetail)
<div class="card mt-4">
    <div class="card-header bg-info text-white">
        <strong>Detail Transaksi: {{ $invoiceDetail->invoice_number }}</strong>
    </div>
    <div class="card-body">
        <div class="row mb-2">
            <div class="col-md-6">
                <b>Supplier:</b> {{ $invoiceDetail->supplier->name ?? '-' }}<br>
                <b>Farm:</b> {{ $invoiceDetail->farm->name ?? '-' }}<br>
                <b>Tanggal:</b> {{ \Carbon\Carbon::parse($invoiceDetail->date)->format('d-m-Y') }}<br>
            </div>
            <div class="col-md-6">
                <b>Status:</b> <span class="badge badge-status badge-{{ strtolower($invoiceDetail->status) }}">{{
                    ucfirst($invoiceDetail->status) }}</span><br>
                <b>No. Invoice:</b> {{ $invoiceDetail->invoice_number }}<br>
            </div>
        </div>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Qty</th>
                    <th>Satuan</th>
                    <th>Harga Satuan</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @php $grandTotal = 0; @endphp
                @foreach($invoiceDetail->supplyPurchases as $item)
                @php $subtotal = $item->quantity * $item->price_per_unit; $grandTotal += $subtotal; @endphp
                <tr>
                    <td>{{ $item->supply->name ?? '-' }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ $item->unit->name ?? '-' }}</td>
                    <td>Rp {{ number_format($item->price_per_unit, 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="4" class="text-end">Grand Total</th>
                    <th>Rp {{ number_format($grandTotal, 0, ',', '.') }}</th>
                </tr>
            </tfoot>
        </table>
        @if($invoiceDetail->keterangan)
        <div class="mt-2"><b>Keterangan:</b> {{ $invoiceDetail->keterangan }}</div>
        @endif
    </div>
</div>
@endif

@if($invoiceDetail)
<div class="mb-3">
    <button class="btn btn-primary" onclick="window.print()">
        <i class="fa fa-print"></i> Print
    </button>
    <button class="btn btn-success" wire:click="exportInvoiceExcel">
        <i class="fa fa-file-excel"></i> Export Excel
    </button>
</div>
@endif

@push('scripts')
<script>
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('openPrintWindow', (data) => {
            const printWindow = window.open(data.url, '_blank');
            printWindow.onload = function() {
                printWindow.print();
            };
        });
    });
</script>
@endpush
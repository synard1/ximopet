<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Pembelian Supply/OVK</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 15px;
            background: #fff;
        }

        .gradient-header {
            background: linear-gradient(90deg, #f857a6 0%, #ff5858 100%);
            color: #fff;
            padding: 18px 24px 12px 24px;
            border-radius: 10px 10px 0 0;
            margin-bottom: 0;
            font-size: 1.25rem;
        }

        .summary-box {
            background: #f8f9fa;
            border-radius: 0 0 10px 10px;
            padding: 18px 24px 12px 24px;
            margin-bottom: 24px;
        }

        .summary-label {
            color: #888;
            font-size: 1em;
        }

        .summary-value {
            font-weight: bold;
            font-size: 1.15em;
        }

        .table th,
        .table td {
            vertical-align: middle;
            white-space: nowrap;
            font-size: 15px;
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

        @media print {
            body {
                font-size: 13px;
                background: #fff;
            }

            .card,
            .summary-box,
            .gradient-header {
                box-shadow: none !important;
                border-radius: 0 !important;
                margin: 0 !important;
                padding: 8px 8px 4px 8px !important;
            }

            .gradient-header {
                font-size: 1.1rem;
                padding: 8px 8px 4px 8px !important;
            }

            .summary-box {
                padding: 8px 8px 4px 8px !important;
                margin-bottom: 8px !important;
            }

            .table-responsive {
                overflow-x: visible !important;
            }

            .table {
                width: 100% !important;
                font-size: 13px !important;
            }

            .table th,
            .table td {
                font-size: 13px !important;
                white-space: nowrap;
                padding: 4px 8px !important;
            }

            html,
            body {
                margin: 0 !important;
                padding: 0 !important;
            }
        }
    </style>
</head>

<body>
    <div class="container-fluid px-0 w-100">
        <div class="row justify-content-center w-100">
            <div class="col-12">
                <div class="card shadow-sm mt-4 mb-5 w-100">
                    <div class="gradient-header d-flex align-items-center">
                        <i class="fa fa-clipboard-list fa-lg me-2"></i>
                        <h4 class="mb-0">LAPORAN PEMBELIAN SUPPLY/OVK</h4>
                    </div>
                    <div class="summary-box">
                        <div class="row mb-2">
                            <div class="col-md-6 col-lg-3 mb-2">
                                <span class="summary-label">Periode</span><br>
                                <span class="summary-value">{{ $summary['period'] ?? '-' }}</span>
                            </div>
                            <div class="col-md-6 col-lg-3 mb-2">
                                <span class="summary-label">Generated</span><br>
                                <span class="summary-value">{{ now()->format('d M Y, H:i') }} WIB</span>
                            </div>
                            <div class="col-md-6 col-lg-3 mb-2">
                                <span class="summary-label">Total Batches</span><br>
                                <span class="summary-value">{{ $summary['total_batches'] ?? '-' }}</span>
                            </div>
                            <div class="col-md-6 col-lg-3 mb-2">
                                <span class="summary-label">Total Purchases</span><br>
                                <span class="summary-value">{{ $summary['total_purchases'] ?? '-' }}</span>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-6 col-lg-3 mb-2">
                                <span class="summary-label">Total Quantity</span><br>
                                <span class="summary-value">{{ number_format($summary['total_quantity'] ?? 0, 2) }}
                                    Unit</span>
                            </div>
                            <div class="col-md-6 col-lg-3 mb-2">
                                <span class="summary-label">Total Value</span><br>
                                <span class="summary-value">Rp {{ number_format($summary['total_value'] ?? 0, 0, ',',
                                    '.') }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="px-4 pb-4 w-100">
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
                                    @forelse($batches as $i => $batch)
                                    <tr>
                                        <td class="text-center">{{ $i + 1 }}</td>
                                        <td class="text-center">{{ \Carbon\Carbon::parse($batch->date)->format('d-m-Y')
                                            }}</td>
                                        <td class="text-center">{{ $batch->invoice_number }}</td>
                                        <td class="text-nowrap">{{ $batch->supplier->name ?? '-' }}</td>
                                        <td class="text-center">
                                            <span class="badge badge-status badge-{{ strtolower($batch->status) }}">
                                                {{ ucfirst($batch->status) }}
                                            </span>
                                        </td>
                                        <td class="text-center">{{ $batch->supplyPurchases->count() }}</td>
                                        <td class="text-end">Rp {{
                                            number_format($batch->supplyPurchases->sum(function($p){return $p->quantity
                                            * $p->price_per_unit;}), 0, ',', '.') }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">Tidak ada data pembelian untuk
                                            periode/filter ini.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @if(isset($invoiceDetail) && $invoiceDetail)
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
</body>
<script>
    // Auto print jika parameter print=true
    if (new URLSearchParams(window.location.search).get('print') === 'true') {
        window.onload = function() {
            window.print();
        };
    }
</script>

</html>
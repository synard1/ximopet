<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Pembelian Pakan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .border-left-primary {
            border-left: 0.25rem solid #4e73df !important;
        }

        .border-left-success {
            border-left: 0.25rem solid #1cc88a !important;
        }

        .border-left-info {
            border-left: 0.25rem solid #36b9cc !important;
        }

        .border-left-warning {
            border-left: 0.25rem solid #f6c23e !important;
        }

        .summary-card .card-body {
            min-height: 90px;
        }

        .summary-card .h5 {
            font-size: 1.5rem;
        }

        .summary-card .fa-2x {
            font-size: 2.2rem;
        }

        .table th,
        .table td {
            vertical-align: middle;
        }

        .report-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 0.5rem;
        }

        .report-header h2 {
            font-size: 1.6rem;
        }

        .report-header h4 {
            font-size: 1.1rem;
        }

        .badge-success {
            background: #1cc88a;
        }

        .badge-secondary {
            background: #858796;
        }
    </style>
</head>

<body class="bg-white">
    <div class="container-fluid px-2 px-md-4 py-2">
        <!-- Report Header -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="report-header p-3 text-center shadow-sm mb-2">
                    <h2 class="mb-1">📊 LAPORAN PEMBELIAN PAKAN</h2>
                    <h4 class="mb-0">{{ $summary['period'] }}</h4>
                    <div class="small mt-1">
                        <i class="fas fa-calendar-alt"></i> Generated: {{ now()->format('d M Y, H:i') }} WIB
                    </div>
                </div>
            </div>
        </div>
        <!-- Summary Cards -->
        <div class="row g-3 mb-3">
            <div class="col-6 col-md-3">
                <div class="card summary-card border-left-primary shadow-sm">
                    <div class="card-body text-center">
                        <div class="text-xs text-primary text-uppercase mb-1">Total Batches</div>
                        <div class="h5 mb-0">{{ number_format($summary['total_batches']) }}</div>
                        <i class="fas fa-boxes fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card summary-card border-left-success shadow-sm">
                    <div class="card-body text-center">
                        <div class="text-xs text-success text-uppercase mb-1">Total Purchases</div>
                        <div class="h5 mb-0">{{ number_format($summary['total_purchases']) }}</div>
                        <i class="fas fa-shopping-cart fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card summary-card border-left-info shadow-sm">
                    <div class="card-body text-center">
                        <div class="text-xs text-info text-uppercase mb-1">Total Quantity</div>
                        <div class="h5 mb-0">{{ number_format($summary['total_quantity'], 2) }} Kg</div>
                        <i class="fas fa-weight fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card summary-card border-left-warning shadow-sm">
                    <div class="card-body text-center">
                        <div class="text-xs text-warning text-uppercase mb-1">Total Value</div>
                        <div class="h5 mb-0">Rp {{ number_format($summary['total_value'], 0, ',', '.') }}</div>
                        <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
        <!-- Batch Details -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm mb-4">
                    <div class="card-header py-2 bg-light border-bottom">
                        <h6 class="m-0 fw-bold text-primary">📦 Detail Batch Pembelian Pakan</h6>
                    </div>
                    <div class="card-body p-2">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover mb-0">
                                <thead class="table-light">
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
                                    @forelse($batches as $index => $batch)
                                    <tr>
                                        <td class="text-center">{{ $index + 1 }}</td>
                                        <td class="text-center">{{ \Carbon\Carbon::parse($batch->date)->format('d-M-Y')
                                            }}</td>
                                        <td class="text-center">{{ $batch->invoice_number ?? 'N/A' }}</td>
                                        <td>{{ $batch->supplier->name ?? 'N/A' }}</td>
                                        <td class="text-center">
                                            <span
                                                class="badge {{ $batch->status == 'completed' ? 'badge-success' : 'badge-secondary' }}">
                                                {{ ucfirst($batch->status ?? 'draft') }}
                                            </span>
                                        </td>
                                        <td class="text-center">{{ $batch->feedPurchases->count() }}</td>
                                        <td class="text-end">
                                            Rp {{ number_format($batch->feedPurchases->sum(function($purchase) {
                                            return $purchase->quantity * $purchase->price_per_unit;
                                            }) + ($batch->expedition_fee ?? 0), 0, ',', '.') }}
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="7" class="text-center">Tidak ada data pembelian pakan</td>
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
</body>

</html>
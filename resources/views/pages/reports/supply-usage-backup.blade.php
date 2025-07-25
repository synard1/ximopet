<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Pemakaian Supply/OVK</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 14px;
            background: #fff;
        }

        .gradient-header {
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
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
            color: #666;
            font-size: 0.9em;
        }

        .summary-value {
            font-weight: bold;
            font-size: 1.1em;
        }

        .table th,
        .table td {
            vertical-align: middle;
            white-space: nowrap;
            font-size: 13px;
        }

        .table thead th {
            background: #f1f3f6;
            font-weight: 600;
            position: sticky;
            top: 0;
            z-index: 2;
        }

        .badge-status {
            font-size: 0.85em;
            padding: 0.3em 0.6em;
        }

        .badge-pending {
            background: #fff3cd !important;
            color: #856404 !important;
        }

        .badge-in_process {
            background: #d1ecf1 !important;
            color: #0c5460 !important;
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

        .supply-breakdown {
            font-size: 0.85em;
            color: #666;
        }

        .supply-breakdown .supply-item {
            margin-bottom: 2px;
            padding: 2px 4px;
            background: #f8f9fa;
            border-radius: 3px;
        }

        @media print {
            body {
                font-size: 12px;
            }

            .table th,
            .table td {
                font-size: 11px;
            }

            .gradient-header {
                font-size: 1.1rem;
                padding: 15px 20px 10px 20px;
            }

            .summary-box {
                padding: 15px 20px 10px 20px;
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
                        <i class="fa fa-pills fa-lg me-2"></i>
                        <h4 class="mb-0">LAPORAN PEMAKAIAN SUPPLY/OVK</h4>
                    </div>
                    <div class="summary-box">
                        <div class="row mb-2">
                            <div class="col-md-6 col-lg-3 mb-2">
                                <span class="summary-label">Farm</span><br>
                                <span class="summary-value">{{ $farm->name ?? '-' }}</span>
                            </div>
                            <div class="col-md-6 col-lg-3 mb-2">
                                <span class="summary-label">Periode</span><br>
                                <span class="summary-value">{{ $summary['period'] ?? '-' }}</span>
                            </div>
                            <div class="col-md-6 col-lg-3 mb-2">
                                <span class="summary-label">Total Records</span><br>
                                <span class="summary-value">{{ number_format($summary['total_records'] ?? 0) }}</span>
                            </div>
                            <div class="col-md-6 col-lg-3 mb-2">
                                <span class="summary-label">Total Biaya</span><br>
                                <span class="summary-value">Rp {{ number_format($summary['total_cost'] ?? 0, 0, ',',
                                    '.') }}</span>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 col-lg-3 mb-2">
                                <span class="summary-label">Total Quantity</span><br>
                                <span class="summary-value">{{ number_format($summary['total_quantity'] ?? 0, 2)
                                    }}</span>
                            </div>
                            <div class="col-md-6 col-lg-3 mb-2">
                                <span class="summary-label">Jenis Supply</span><br>
                                <span class="summary-value">{{ $summary['supply_types_count'] ?? 0 }} jenis</span>
                            </div>
                            <div class="col-md-6 col-lg-3 mb-2">
                                <span class="summary-label">Tipe Laporan</span><br>
                                <span class="summary-value">{{ ucfirst($reportType) }}</span>
                            </div>
                            <div class="col-md-6 col-lg-3 mb-2">
                                <span class="summary-label">Generated</span><br>
                                <span class="summary-value">{{ now()->format('d M Y, H:i') }} WIB</span>
                            </div>
                        </div>
                    </div>
                    <div class="px-4 pb-4 w-100">
                        <h5 class="mt-4 mb-3">
                            <i class="fa fa-list-alt me-2"></i>
                            Detail Pemakaian Supply/OVK
                            @if($reportType === 'detail')
                            (Per Record)
                            @else
                            (Per Batch/Hari)
                            @endif
                        </h5>
                        <div class="table-responsive w-100">
                            @if($reportType === 'detail')
                            <!-- Detail Report Table -->
                            <table class="table table-bordered table-hover table-striped align-middle mb-0 w-100">
                                <thead>
                                    <tr>
                                        <th class="text-center">No</th>
                                        <th class="text-center">Tanggal</th>
                                        <th class="text-center">Batch</th>
                                        <th class="text-center">Kandang</th>
                                        <th class="text-center">Jenis Supply</th>
                                        <th class="text-center">Jumlah (Terkecil)</th>
                                        <th class="text-center">Satuan Terkecil</th>
                                        <th class="text-center">Harga Satuan (Terkecil)</th>
                                        <th class="text-center">Total Harga (Terkecil)</th>
                                        <th class="text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($data as $index => $item)
                                    <tr>
                                        <td class="text-center">{{ $index + 1 }}</td>
                                        <td class="text-center">{{ $item['usage_date']->format('d/m/Y') }}</td>
                                        <td>{{ $item['livestock_name'] }}</td>
                                        <td>{{ $item['coop_name'] }}</td>
                                        <td>{{ $item['supply_name'] }}</td>
                                        <td class="text-end">{{ number_format($item['converted_quantity'], 2) }}</td>
                                        <td class="text-center">{{ $item['converted_unit'] }}</td>
                                        <td class="text-end">Rp {{ number_format($item['converted_unit_cost'], 0, ',',
                                            '.') }}</td>
                                        <td class="text-end">Rp {{ number_format($item['converted_total_cost'], 0, ',',
                                            '.') }}</td>
                                        <td class="text-center">
                                            <span class="badge badge-status badge-{{ $item['status'] }}">
                                                {{ ucfirst($item['status']) }}
                                            </span>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="10" class="text-center py-4">
                                            <i class="fa fa-info-circle me-2"></i>
                                            Tidak ada data pemakaian supply untuk periode yang dipilih
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                                @if(count($data) > 0)
                                <tfoot>
                                    <tr class="table-dark">
                                        <td colspan="8" class="text-end fw-bold">TOTAL</td>
                                        <td class="text-end fw-bold">Rp {{ number_format($totals['total_cost'], 0, ',',
                                            '.') }}</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                                @endif
                            </table>
                            @else
                            <!-- Simple Report Table -->
                            <table class="table table-bordered table-hover table-striped align-middle mb-0 w-100">
                                <thead>
                                    <tr>
                                        <th class="text-center">No</th>
                                        <th class="text-center">Tanggal</th>
                                        <th class="text-center">Batch</th>
                                        <th class="text-center">Kandang</th>
                                        <th class="text-center">Jenis Supply</th>
                                        <th class="text-center">Jumlah (Terkecil)</th>
                                        <th class="text-center">Satuan Terkecil</th>
                                        <th class="text-center">Harga Satuan (Terkecil)</th>
                                        <th class="text-center">Total Harga (Terkecil)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($data as $index => $item)
                                    <tr>
                                        <td class="text-center">{{ $index + 1 }}</td>
                                        <td class="text-center">{{ $item['usage_date']->format('d/m/Y') }}</td>
                                        <td>{{ $item['livestock_name'] }}</td>
                                        <td>{{ $item['coop_name'] }}</td>
                                        <td>{{ $item['supply_name'] }}</td>
                                        <td class="text-end">{{ number_format($item['converted_quantity'], 2) }}</td>
                                        <td class="text-center">{{ $item['converted_unit'] }}</td>
                                        <td class="text-end">Rp {{ number_format($item['converted_unit_cost'], 0, ',',
                                            '.') }}</td>
                                        <td class="text-end">Rp {{ number_format($item['converted_total_cost'], 0, ',',
                                            '.') }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="9" class="text-center py-4">
                                            <i class="fa fa-info-circle me-2"></i>
                                            Tidak ada data pemakaian supply untuk periode yang dipilih
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                                @if(count($data) > 0)
                                <tfoot>
                                    <tr class="table-dark">
                                        <td colspan="8" class="text-end fw-bold">TOTAL</td>
                                        <td class="text-end fw-bold">Rp {{ number_format($totals['total_cost'], 0, ',',
                                            '.') }}</td>
                                    </tr>
                                </tfoot>
                                @endif
                            </table>
                            @endif
                        </div>
                        <div class="mt-4">
                            <h6>Ringkasan per Jenis Supply</h6>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped mb-0">
                                    <thead>
                                        <tr>
                                            <th>Jenis Supply</th>
                                            <th>Total Quantity</th>
                                            <th>Satuan</th>
                                            <th>Total Biaya</th>
                                            <th>% dari Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if(count($totals['supply_types'] ?? []) > 0)
                                        @foreach($totals['supply_types'] as $supplyName => $supplyData)
                                        <tr>
                                            <td>{{ $supplyName }}</td>
                                            <td>{{ number_format($supplyData['quantity'], 2) }}</td>
                                            <td>{{ $supplyData['unit'] }}</td>
                                            <td>Rp {{ number_format($supplyData['cost'], 0, ',', '.') }}</td>
                                            <td>{{ $totals['total_cost'] > 0 ? number_format(($supplyData['cost'] /
                                                $totals['total_cost']) * 100, 1) : 0 }}%</td>
                                        </tr>
                                        @endforeach
                                        @else
                                        <tr>
                                            <td colspan="5" class="text-center">Tidak ada data</td>
                                        </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>






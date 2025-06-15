<html>

<head>
    <title>Laporan Pembelian Livestock</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
            color: #333;
            line-height: 1.5;
            font-size: 15px;
        }

        .container {
            max-width: 1100px;
            margin: 24px auto;
            background: #fff;
            padding: 18px 14px 18px 14px;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
        }

        .report-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            border-radius: 10px 10px 0 0;
            padding: 18px 18px 12px 18px;
            margin-bottom: 0;
            text-align: center;
        }

        .report-header h2 {
            margin-bottom: 6px;
            font-size: 1.4rem;
            font-weight: 700;
        }

        .report-header h4 {
            margin-bottom: 0;
            font-size: 1rem;
        }

        .report-header p {
            margin-top: 8px;
            font-size: 0.9rem;
            color: #e0e0e0;
        }

        .summary-cards {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 18px 0 10px 0;
            justify-content: space-between;
        }

        .summary-card {
            flex: 1 1 150px;
            background: #f6f8fc;
            color: #333;
            border-radius: 8px;
            padding: 10px 8px;
            min-width: 120px;
            box-shadow: 0 1px 4px rgba(102, 126, 234, 0.07);
            text-align: center;
        }

        .summary-card h4 {
            margin: 0 0 4px 0;
            font-size: 1rem;
            color: #667eea;
            font-weight: 600;
        }

        .summary-card .value {
            font-size: 1.1rem;
            font-weight: 700;
            color: #222;
        }

        .filters {
            background: #f8f9fa;
            padding: 10px 12px;
            border-radius: 8px;
            margin-bottom: 14px;
            border-left: 4px solid #667eea;
        }

        .filters h3 {
            color: #667eea;
            margin-top: 0;
            font-size: 1rem;
        }

        .filter-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px 18px;
        }

        .filter-item {
            min-width: 120px;
            font-size: 0.95rem;
        }

        .filter-label {
            font-weight: 600;
            color: #495057;
        }

        .filter-value {
            color: #6c757d;
        }

        .table-responsive {
            overflow-x: auto;
            margin-bottom: 14px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.07);
            font-size: 14px;
        }

        .table th,
        .table td {
            padding: 7px 8px;
            text-align: center;
            border-bottom: 1px solid #e9ecef;
            font-size: 0.95rem;
        }

        .table th {
            background: #667eea;
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.96rem;
            letter-spacing: 0.5px;
        }

        .table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .table tbody tr:nth-child(even) {
            background-color: #fafafa;
        }

        .status {
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-draft {
            background: #ffc107;
            color: #000;
        }

        .status-confirmed {
            background: #17a2b8;
            color: #fff;
        }

        .status-arrived {
            background: #28a745;
            color: #fff;
        }

        .status-completed {
            background: #6f42c1;
            color: #fff;
        }

        .currency {
            font-family: 'Courier New', monospace;
            font-weight: 600;
        }

        .no-data {
            text-align: center;
            padding: 30px;
            color: #6c757d;
            font-style: italic;
        }

        .breakdown {
            background: #f8f9fa;
            padding: 10px 12px;
            border-radius: 8px;
            margin-top: 18px;
        }

        .breakdown-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 14px 18px;
        }

        .breakdown-section {
            min-width: 120px;
            flex: 1 1 120px;
        }

        .breakdown-section h4 {
            color: #667eea;
            margin-bottom: 8px;
            font-size: 0.98rem;
        }

        .breakdown-item {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px solid #e9ecef;
            font-size: 0.95rem;
        }

        @media (max-width: 900px) {

            .summary-cards,
            .breakdown-grid,
            .filter-grid {
                flex-direction: column;
                gap: 8px;
            }

            .container {
                padding: 8px 2px;
            }
        }

        @media print {

            html,
            body {
                background: white !important;
                font-size: 9px !important;
                line-height: 1.2 !important;
            }

            .container {
                box-shadow: none !important;
                background: white !important;
                padding: 0 !important;
                margin: 0 !important;
                max-width: 100vw !important;
                width: 100vw !important;
            }

            .report-header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
                color: #fff !important;
                border-radius: 0 !important;
                padding: 8px 4px 4px 4px !important;
            }

            .summary-cards {
                display: flex !important;
                flex-direction: row !important;
                flex-wrap: nowrap !important;
                justify-content: space-between !important;
                gap: 2px !important;
                margin: 4px 0 2px 0 !important;
                page-break-inside: avoid !important;
            }

            .summary-card {
                flex: 0 0 20% !important;
                max-width: 20% !important;
                min-width: 0 !important;
                box-sizing: border-box !important;
                padding: 2px 1px !important;
                background: #f6f8fc !important;
                color: #333 !important;
                box-shadow: none !important;
                margin-bottom: 0 !important;
            }

            .summary-card h4,
            .summary-card .value {
                font-size: 0.7rem !important;
            }

            .filters,
            .breakdown {
                background: #f8f9fa !important;
                padding: 2px 4px !important;
                margin-bottom: 2px !important;
                border-left: 4px solid #667eea !important;
                page-break-inside: avoid !important;
            }

            .breakdown-grid {
                display: grid !important;
                grid-template-columns: repeat(3, 1fr) !important;
                gap: 6px !important;
            }

            .breakdown-section h4 {
                font-size: 0.7rem !important;
                margin-bottom: 2px !important;
            }

            .breakdown-item {
                font-size: 0.7rem !important;
                padding: 1px 0 !important;
            }

            .table-responsive {
                overflow: visible !important;
                page-break-inside: avoid !important;
            }

            .table {
                font-size: 8px !important;
                width: 100% !important;
                max-width: 100vw !important;
            }

            .table th,
            .table td {
                padding: 1px 1px !important;
                font-size: 8px !important;
            }

            .status {
                font-size: 8px !important;
                padding: 1px 6px !important;
            }

            .table td div[style*='font-size: 12px'] {
                font-size: 8px !important;
            }
        }
    </style>
</head>

<body>
    @section('title', 'Laporan Pembelian Livestock - ' . $summary['period'])
    <div class="container">
        <div class="report-header">
            <h2>📊 LAPORAN PEMBELIAN LIVESTOCK</h2>
            <h4>{{ $summary['period'] }}</h4>
            <p><i class="fas fa-calendar-alt"></i> Generated: {{ now()->format('d M Y, H:i') }} WIB</p>
        </div>

        <div class="summary-cards">
            <div class="summary-card">
                <h4>Total Batches</h4>
                <div class="value">{{ number_format($summary['total_purchases'] ?? 0) }}</div>
            </div>
            <div class="summary-card">
                <h4>Total Purchases</h4>
                <div class="value">Rp {{ number_format($summary['total_value'], 0, ',', '.') }}</div>
            </div>
            <div class="summary-card">
                <h4>Total Ekor</h4>
                <div class="value">{{ number_format($summary['total_quantity']) }}</div>
            </div>
            <div class="summary-card">
                <h4>Total Suppliers</h4>
                <div class="value">{{ $summary['total_suppliers'] }}</div>
            </div>
            <div class="summary-card">
                <h4>Total Farms</h4>
                <div class="value">{{ $summary['total_farms'] }}</div>
            </div>
        </div>

        <div class="filters">
            <h3>Filter yang Diterapkan</h3>
            <div class="filter-grid">
                <div class="filter-item"><span class="filter-label">Periode:</span> <span class="filter-value">{{
                        $summary['period'] }}</span></div>
                @if($filters['farm'])<div class="filter-item"><span class="filter-label">Farm:</span> <span
                        class="filter-value">{{ $filters['farm']->name }}</span></div>@endif
                @if($filters['supplier'])<div class="filter-item"><span class="filter-label">Supplier:</span> <span
                        class="filter-value">{{ $filters['supplier']->name }}</span></div>@endif
                @if($filters['expedition'])<div class="filter-item"><span class="filter-label">Ekspedisi:</span> <span
                        class="filter-value">{{ $filters['expedition']->name }}</span></div>@endif
                @if($filters['status'])<div class="filter-item"><span class="filter-label">Status:</span> <span
                        class="filter-value">{{ ucfirst($filters['status']) }}</span></div>@endif
            </div>
        </div>

        @if($purchases->count() > 0)
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>Invoice</th>
                        <th>Farm</th>
                        <th>Supplier</th>
                        <th>Ekspedisi</th>
                        <th>Status</th>
                        <th>Qty (Ekor)</th>
                        <th>Total Nilai</th>
                        <th>Detail Items</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($purchases as $index => $purchase)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ \Carbon\Carbon::parse($purchase->date)->format('d/m/Y') }}</td>
                        <td>{{ $purchase->invoice_number }}</td>
                        <td>{{ $purchase->farm->name ?? '-' }}</td>
                        <td>{{ $purchase->supplier->name ?? '-' }}</td>
                        <td>{{ $purchase->expedition->name ?? '-' }}</td>
                        <td><span class="status status-{{ $purchase->status }}">{{ ucfirst($purchase->status) }}</span>
                        </td>
                        <td>{{ number_format($purchase->details->sum('quantity')) }}</td>
                        <td class="currency">Rp {{ number_format($purchase->details->sum(function($item) { return
                            $item->quantity * $item->price_per_unit; }), 0, ',', '.') }}</td>
                        <td style="text-align:left;">
                            @foreach($purchase->details as $item)
                            <div style="margin-bottom: 5px; font-size: 12px;">
                                <strong>{{ $item->livestockStrain->name ?? 'N/A' }}</strong><br>
                                {{ number_format($item->quantity) }} {{ $item->unit->name ?? 'ekor' }} @ Rp {{
                                number_format($item->price_per_unit, 0, ',', '.') }}
                            </div>
                            @endforeach
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="no-data">
            <h4>Tidak ada data pembelian livestock</h4>
            <p>Tidak ditemukan data pembelian untuk filter yang diterapkan</p>
        </div>
        @endif

        @if($purchases->count() > 0)
        <div class="breakdown">
            <h3 style="color: #667eea; margin-bottom: 18px;">Ringkasan Detail</h3>
            <div class="breakdown-grid">
                <div class="breakdown-section">
                    <h4>Berdasarkan Status</h4>
                    @foreach($summary['by_status'] as $status => $count)
                    <div class="breakdown-item">
                        <span>{{ ucfirst($status) }}</span>
                        <span>{{ $count }} pembelian</span>
                    </div>
                    @endforeach
                </div>
                <div class="breakdown-section">
                    <h4>Berdasarkan Farm</h4>
                    @foreach($summary['by_farm'] as $farm => $count)
                    <div class="breakdown-item">
                        <span>{{ $farm }}</span>
                        <span>{{ $count }} pembelian</span>
                    </div>
                    @endforeach
                </div>
                <div class="breakdown-section">
                    <h4>Berdasarkan Supplier</h4>
                    @foreach($summary['by_supplier'] as $supplier => $count)
                    <div class="breakdown-item">
                        <span>{{ $supplier }}</span>
                        <span>{{ $count }} pembelian</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
    </div>
</body>

</html>
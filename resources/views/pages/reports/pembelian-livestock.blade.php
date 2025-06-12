<x-default-layout>

    @section('title', 'Laporan Pembelian Livestock - ' . $summary['period'])
    <div class="card">
        <div class="card-body">
            <!-- Report Header -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center py-4"
                            style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                            <h2 class="mb-2">📊 LAPORAN PEMBELIAN LIVESTOCK</h2>
                            <h4 class="mb-0">{{ $summary['period'] }}</h4>
                            <p class="mb-0 mt-2">
                                <i class="fas fa-calendar-alt"></i> Generated: {{ now()->format('d M Y, H:i') }} WIB
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Total Batches</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        {{ number_format($summary['total_purchases']) }}
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-boxes fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Total Purchases</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        {{ number_format($summary['total_quantity']) }}
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-shopping-cart fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Total Ekor</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        Rp {{ number_format($summary['total_value'], 0, ',', '.') }}
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-weight fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Total Suppliers</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        {{ $summary['total_suppliers'] }}
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Total Farms</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        {{ $summary['total_farms'] }}
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-home fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters Applied -->
            <div class="filters">
                <h3>Filter yang Diterapkan</h3>
                <div class="filter-grid">
                    <div class="filter-item">
                        <span class="filter-label">Periode:</span>
                        <span class="filter-value">{{ $summary['period'] }}</span>
                    </div>
                    @if($filters['farm'])
                    <div class="filter-item">
                        <span class="filter-label">Farm:</span>
                        <span class="filter-value">{{ $filters['farm']->name }}</span>
                    </div>
                    @endif
                    @if($filters['supplier'])
                    <div class="filter-item">
                        <span class="filter-label">Supplier:</span>
                        <span class="filter-value">{{ $filters['supplier']->name }}</span>
                    </div>
                    @endif
                    @if($filters['expedition'])
                    <div class="filter-item">
                        <span class="filter-label">Ekspedisi:</span>
                        <span class="filter-value">{{ $filters['expedition']->name }}</span>
                    </div>
                    @endif
                    @if($filters['status'])
                    <div class="filter-item">
                        <span class="filter-label">Status:</span>
                        <span class="filter-value">{{ ucfirst($filters['status']) }}</span>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Data Table -->
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
                            <th class="text-right">Qty (Ekor)</th>
                            <th class="text-right">Total Nilai</th>
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
                            <td>
                                <span class="status status-{{ $purchase->status }}">
                                    {{ ucfirst($purchase->status) }}
                                </span>
                            </td>
                            <td class="text-right">
                                {{ number_format($purchase->details->sum('quantity')) }}
                            </td>
                            <td class="text-right currency">
                                Rp {{ number_format($purchase->details->sum(function($item) {
                                return $item->quantity * $item->price_per_unit;
                                }), 0, ',', '.') }}
                            </td>
                            <td>
                                @foreach($purchase->details as $item)
                                <div style="margin-bottom: 5px; font-size: 12px;">
                                    <strong>{{ $item->livestockStrain->name ?? 'N/A' }}</strong><br>
                                    {{ number_format($item->quantity) }} {{ $item->unit->name ?? 'ekor' }}
                                    @ Rp {{ number_format($item->price_per_unit, 0, ',', '.') }}
                                </div>
                                @endforeach
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                <div class="no-data">
                    <h4>Tidak ada data pembelian livestock</h4>
                    <p>Tidak ditemukan data pembelian untuk filter yang diterapkan</p>
                </div>
                @endif

                <!-- Breakdown Summary -->
                @if($purchases->count() > 0)
                <div class="breakdown">
                    <h3 style="color: #007bff; margin-bottom: 20px;">Ringkasan Detail</h3>
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
        </div>
    </div>


    @push('styles')
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f8f9fa;
            color: #333;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #007bff;
            padding-bottom: 20px;
        }

        .header h1 {
            color: #007bff;
            margin: 0;
            font-size: 28px;
            font-weight: bold;
        }

        .header p {
            color: #666;
            margin: 10px 0 0;
            font-size: 16px;
        }

        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .summary-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }

        .summary-card h3 {
            margin: 0 0 10px;
            font-size: 24px;
            font-weight: bold;
        }

        .summary-card p {
            margin: 0;
            font-size: 14px;
            opacity: 0.9;
        }

        .filters {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border-left: 4px solid #007bff;
        }

        .filters h3 {
            color: #007bff;
            margin-top: 0;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .filter-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .filter-label {
            font-weight: 600;
            color: #495057;
        }

        .filter-value {
            color: #6c757d;
        }

        .table-container {
            overflow-x: auto;
            margin-bottom: 30px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .table th,
        .table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }

        .table th {
            background: #007bff;
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
        }

        .table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .table tbody tr:nth-child(even) {
            background-color: #fafafa;
        }

        .status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-draft {
            background: #ffc107;
            color: #000;
        }

        .status-confirmed {
            background: #17a2b8;
            color: white;
        }

        .status-arrived {
            background: #28a745;
            color: white;
        }

        .status-completed {
            background: #6f42c1;
            color: white;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .currency {
            font-family: 'Courier New', monospace;
            font-weight: 600;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #6c757d;
            font-style: italic;
        }

        .breakdown {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 30px;
        }

        .breakdown-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .breakdown-section h4 {
            color: #007bff;
            margin-bottom: 15px;
            font-size: 16px;
        }

        .breakdown-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .timestamp {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e9ecef;
            color: #6c757d;
            font-size: 12px;
        }

        @media print {
            body {
                background: white;
            }

            .container {
                box-shadow: none;
            }

            .summary-cards {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
    @endpush
</x-default-layout>
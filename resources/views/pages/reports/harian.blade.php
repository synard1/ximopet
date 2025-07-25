<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Harian Kandang</title>
    <style>
        @page {
            size: landscape;
            margin: 1.5cm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            color: #333;
            background-color: #fff;
            margin: 0;
            padding: 0;
        }

        /* Header Section */
        .report-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .report-header h1 {
            margin: 0 0 15px 0;
            font-size: 18pt;
            font-weight: 600;
            text-align: center;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
        }

        .header-info {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            align-items: center;
        }

        .header-info-item {
            background: rgba(255, 255, 255, 0.1);
            padding: 10px 15px;
            border-radius: 6px;
            border-left: 4px solid #fff;
        }

        .header-info-item .label {
            font-size: 9pt;
            opacity: 0.9;
            margin-bottom: 3px;
        }

        .header-info-item .value {
            font-size: 12pt;
            font-weight: 600;
        }

        /* Table Styling */
        .table-container {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
        }

        thead {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }

        th {
            background: #495057;
            color: white;
            font-weight: 600;
            padding: 12px 8px;
            text-align: center;
            border: 1px solid #dee2e6;
            font-size: 8pt;
            line-height: 1.2;
        }

        th:first-child {
            border-left: none;
        }

        th:last-child {
            border-right: none;
        }

        .header-section {
            background: #6c757d;
            color: white;
            font-weight: 600;
        }

        .sub-header {
            background: #868e96;
            color: white;
            font-weight: 500;
        }

        td {
            padding: 10px 8px;
            text-align: center;
            border: 1px solid #dee2e6;
            vertical-align: middle;
            font-size: 9pt;
        }

        tbody tr {
            background: white;
            transition: background-color 0.2s ease;
        }

        tbody tr:nth-child(even) {
            background: #f8f9fa;
        }

        tbody tr:hover {
            background: #e3f2fd;
        }

        .text-left {
            text-align: left !important;
        }

        .text-right {
            text-align: right !important;
        }

        .text-center {
            text-align: center !important;
        }

        /* Footer Total Row */
        tfoot tr {
            background: linear-gradient(135deg, #495057 0%, #6c757d 100%);
            color: white;
            font-weight: 600;
        }

        tfoot td {
            padding: 12px 8px;
            border-color: #495057;
            font-size: 9pt;
        }

        /* Summary Cards */
        .summary-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 20px;
        }

        .summary-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-left: 4px solid #667eea;
        }

        .summary-card h3 {
            margin: 0 0 15px 0;
            color: #495057;
            font-size: 12pt;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .summary-card h3::before {
            content: "📊";
            font-size: 16pt;
        }

        .summary-card.supply-card h3::before {
            content: "📦";
        }

        .summary-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }

        .stat-item {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 6px;
            border-left: 3px solid #667eea;
        }

        .stat-label {
            font-size: 8pt;
            color: #6c757d;
            margin-bottom: 3px;
        }

        .stat-value {
            font-size: 11pt;
            font-weight: 600;
            color: #495057;
        }

        .stat-highlight {
            background: #e7f3ff;
            border-left-color: #007bff;
        }

        .stat-success {
            background: #e8f5e8;
            border-left-color: #28a745;
        }

        .stat-danger {
            background: #ffeaea;
            border-left-color: #dc3545;
        }

        /* Supply Table */
        .supply-table {
            margin-top: 15px;
        }

        .supply-table table {
            font-size: 8pt;
        }

        .supply-table th {
            background: #495057;
            padding: 8px 6px;
            font-size: 8pt;
        }

        .supply-table td {
            padding: 8px 6px;
            font-size: 8pt;
        }

        .supply-table .text-right {
            font-weight: 600;
        }

        /* Responsive Design */
        @media print {
            .summary-section {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .summary-card {
                break-inside: avoid;
            }

            .table-container {
                break-inside: avoid;
            }
        }

        /* Utility Classes */
        .font-weight-bold {
            font-weight: 600;
        }

        .text-muted {
            color: #6c757d;
        }

        .bg-light {
            background-color: #f8f9fa;
        }

        .border-radius {
            border-radius: 6px;
        }

        /* Badge Styling */
        .badge {
            display: inline-block;
            padding: 2px 6px;
            background: #e9ecef;
            border-radius: 4px;
            font-size: 7pt;
            font-weight: 500;
            color: #495057;
        }

        .badge-success {
            background: #d4edda;
            color: #155724;
        }

        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }

        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }

        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* Print Specific Styles */
        @media print {
            body {
                font-size: 9pt;
            }

            .report-header {
                background: #495057 !important;
                -webkit-print-color-adjust: exact;
            }

            .summary-card {
                box-shadow: none;
                border: 1px solid #dee2e6;
            }

            .table-container {
                box-shadow: none;
                border: 1px solid #dee2e6;
            }
        }
    </style>
</head>

<body>
    <!-- Header Section -->
    <div class="report-header">
        <h1>LAPORAN HARIAN KANDANG</h1>
        <div class="header-info">
            <div class="header-info-item">
                <div class="label">FARM</div>
                <div class="value">{{ $farm }}</div>
            </div>
            <div class="header-info-item">
                <div class="label">TANGGAL</div>
                <div class="value">{{ $tanggal }}</div>
            </div>
            <div class="header-info-item">
                <div class="label">TIPE LAPORAN</div>
                <div class="value">{{ strtoupper($reportType) }} {{ $reportType === 'detail' ? '(PER BATCH)' : '(PER
                    KANDANG)' }}</div>
            </div>
        </div>
        @if($reportType === 'detail')
        <div style="margin-top: 10px; font-size: 9pt; opacity: 0.9;">
            <em>Mode Detail: Menampilkan data per deplesi record dengan normalisasi jenis deplesi</em>
        </div>
        @endif
    </div>

    <!-- Main Table -->
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th rowspan="3">KANDANG</th>
                    @if($reportType === 'detail')
                    <th rowspan="3">BATCH</th>
                    @endif
                    <th rowspan="3">UMUR<br><small>(Hari)</small></th>
                    <th colspan="8" class="header-section">POPULASI AYAM</th>
                    <th colspan="3" class="header-section">BERAT BADAN</th>
                    <th colspan="{{ count($distinctFeedNames) + 1 }}" class="header-section">PEMAKAIAN PAKAN</th>
                </tr>
                <tr>
                    <th rowspan="2" class="sub-header">EKOR<br>AYAM</th>
                    <th colspan="3" class="sub-header">DEPLESI</th>
                    <th rowspan="2" class="sub-header">%<br>MORTALITAS</th>
                    <th colspan="2" class="sub-header">PENJUALAN</th>
                    <th rowspan="2" class="sub-header">SISA<br>AYAM</th>
                    <th rowspan="2" class="sub-header">SEMALAM<br><small>(Gr)</small></th>
                    <th rowspan="2" class="sub-header">HARI INI<br><small>(Gr)</small></th>
                    <th rowspan="2" class="sub-header">KENAIKAN<br><small>(Gr)</small></th>
                    @if(count($distinctFeedNames) > 0)
                    <th colspan="{{ count($distinctFeedNames) }}" class="sub-header">JENIS PAKAN</th>
                    @else
                    <th class="sub-header">JENIS PAKAN</th>
                    @endif
                    <th rowspan="2" class="sub-header">TOTAL<br>TERPAKAI<br><small>(Kg)</small></th>
                </tr>
                <tr>
                    <th>MATI</th>
                    <th>AFKIR</th>
                    <th>TOTAL</th>
                    <th>EKOR</th>
                    <th>KG</th>
                    @if(count($distinctFeedNames) > 0)
                    @foreach($distinctFeedNames as $feedName)
                    <th>{{ $feedName }}</th>
                    @endforeach
                    @else
                    <th>-</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @if($reportType === 'detail')
                {{-- MODE DETAIL: Tampilkan per batch --}}
                @forelse($recordings as $coopNama => $batchesData)
                @if(is_array($batchesData) && count($batchesData) > 0)
                @php
                $validBatches = collect($batchesData)->filter(function($batch) {
                return is_array($batch) && isset($batch['livestock_name']);
                });
                @endphp

                @if($validBatches->count() > 0)
                @foreach($validBatches as $index => $batch)
                <tr>
                    @if($index === 0)
                    <td rowspan="{{ $validBatches->count() }}" class="text-left font-weight-bold">
                        {{ $coopNama ?? '-' }}
                    </td>
                    @endif
                    <td class="text-left">
                        <strong>{{ $batch['livestock_name'] ?? '-' }}</strong>
                        @if(isset($batch['depletion_type']) && $batch['depletion_type'])
                        <br><span class="badge badge-warning">{{ $batch['depletion_category'] ?? 'other' }}</span>
                        @endif
                    </td>
                    <td class="font-weight-bold">{{ $batch['umur'] ?? '0' }}</td>
                    <td>{{ formatNumber($batch['stock_awal'] ?? 0, 0) }}</td>
                    <td class="@if(($batch['mati'] ?? 0) > 0) text-danger @endif">{{ $batch['mati'] ?? '0' }}</td>
                    <td class="@if(($batch['afkir'] ?? 0) > 0) text-warning @endif">{{ $batch['afkir'] ?? '0' }}</td>
                    <td class="font-weight-bold">{{ $batch['total_deplesi'] ?? '0' }}</td>
                    <td
                        class="@if(($batch['deplesi_percentage'] ?? 0) > 5) text-danger @elseif(($batch['deplesi_percentage'] ?? 0) > 2) text-warning @endif">
                        {{ number_format($batch['deplesi_percentage'] ?? 0, 2) }}%
                    </td>
                    <td>{{ formatNumber($batch['jual_ekor'] ?? 0, 0) }}</td>
                    <td>{{ formatNumber($batch['jual_kg'] ?? 0, 0) }}</td>
                    <td class="font-weight-bold">{{ formatNumber($batch['stock_akhir'] ?? 0, 0) }}</td>
                    <td>{{ round($batch['berat_semalam'] ?? 0, 0) }}</td>
                    <td>{{ round($batch['berat_hari_ini'] ?? 0, 0) }}</td>
                    <td class="@if(($batch['kenaikan_berat'] ?? 0) > 0) text-success @endif font-weight-bold">
                        {{ round($batch['kenaikan_berat'] ?? 0, 0) }}
                    </td>
                    @if(count($distinctFeedNames) > 0)
                    @foreach($distinctFeedNames as $feedName)
                    <td>{{ formatNumber($batch['pakan_harian'][$feedName] ?? 0, 0) }}</td>
                    @endforeach
                    @else
                    <td>0</td>
                    @endif
                    <td class="font-weight-bold">{{ formatNumber($batch['pakan_total'] ?? 0, 0) }}</td>
                </tr>
                @endforeach
                @else
                <tr>
                    <td class="text-left">{{ $coopNama ?? '-' }}</td>
                    <td colspan="{{ 13 + count($distinctFeedNames) }}" class="text-muted">
                        <em>Data batch tidak valid atau kosong</em>
                    </td>
                </tr>
                @endif
                @else
                <tr>
                    <td class="text-left">{{ $coopNama ?? '-' }}</td>
                    <td colspan="{{ 13 + count($distinctFeedNames) }}" class="text-muted">
                        <em>Format data tidak sesuai ({{ gettype($batchesData) }})</em>
                    </td>
                </tr>
                @endif
                @empty
                <tr>
                    <td colspan="{{ 15 + count($distinctFeedNames) }}" class="text-muted">
                        <em>Tidak ada data untuk ditampilkan</em>
                    </td>
                </tr>
                @endforelse
                @else
                {{-- MODE SIMPLE: Tampilkan per kandang --}}
                @forelse($recordings as $coopNama => $record)
                <tr>
                    <td class="text-left font-weight-bold">{{ $coopNama ?? '-' }}</td>
                    <td class="font-weight-bold">{{ $record['umur'] ?? '0' }}</td>
                    <td>{{ formatNumber($record['stock_awal'] ?? 0, 0) }}</td>
                    <td class="@if(($record['mati'] ?? 0) > 0) text-danger @endif">{{ $record['mati'] ?? '0' }}</td>
                    <td class="@if(($record['afkir'] ?? 0) > 0) text-warning @endif">{{ $record['afkir'] ?? '0' }}</td>
                    <td class="font-weight-bold">{{ $record['total_deplesi'] ?? '0' }}</td>
                    <td
                        class="@if(($record['deplesi_percentage'] ?? 0) > 5) text-danger @elseif(($record['deplesi_percentage'] ?? 0) > 2) text-warning @endif">
                        {{ number_format($record['deplesi_percentage'] ?? 0, 2) }}%
                    </td>
                    <td>{{ formatNumber($record['jual_ekor'] ?? 0, 0) }}</td>
                    <td>{{ formatNumber($record['jual_kg'] ?? 0, 0) }}</td>
                    <td class="font-weight-bold">{{ formatNumber($record['stock_akhir'] ?? 0, 0) }}</td>
                    <td>{{ round($record['berat_semalam'] ?? 0, 0) }}</td>
                    <td>{{ round($record['berat_hari_ini'] ?? 0, 0) }}</td>
                    <td class="@if(($record['kenaikan_berat'] ?? 0) > 0) text-success @endif font-weight-bold">
                        {{ round($record['kenaikan_berat'] ?? 0, 0) }}
                    </td>
                    @if(count($distinctFeedNames) > 0 && isset($record['pakan_harian']))
                    @foreach($distinctFeedNames as $feedName)
                    <td>{{ formatNumber($record['pakan_harian'][$feedName] ?? 0, 0) }}</td>
                    @endforeach
                    @else
                    @for($i = 0; $i < max(1, count($distinctFeedNames)); $i++) <td>0</td>
                        @endfor
                        @endif
                        <td class="font-weight-bold">{{ formatNumber($record['pakan_total'] ?? 0, 0) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="{{ 14 + count($distinctFeedNames) }}" class="text-muted">
                        <em>Tidak ada data untuk ditampilkan</em>
                    </td>
                </tr>
                @endforelse
                @endif
            </tbody>

            <!-- Total Row -->
            <tfoot>
                <tr>
                    <td colspan="{{ $reportType === 'detail' ? '3' : '2' }}" class="text-left font-weight-bold">TOTAL
                    </td>
                    <td class="font-weight-bold">{{ formatNumber($totals['stock_awal'] ?? 0, 0) }}</td>
                    <td class="font-weight-bold">{{ $totals['mati'] ?? '0' }}</td>
                    <td class="font-weight-bold">{{ $totals['afkir'] ?? '0' }}</td>
                    <td class="font-weight-bold">{{ $totals['total_deplesi'] ?? '0' }}</td>
                    <td class="font-weight-bold">{{ number_format($totals['deplesi_percentage'] ?? 0, 2) }}%</td>
                    <td class="font-weight-bold">{{ formatNumber($totals['jual_ekor'] ?? 0, 0) }}</td>
                    <td class="font-weight-bold">{{ formatNumber($totals['jual_kg'] ?? 0, 0) }}</td>
                    <td class="font-weight-bold">{{ formatNumber($totals['stock_akhir'] ?? 0, 0) }}</td>
                    <td colspan="3"></td>
                    @if(count($distinctFeedNames) > 0)
                    @foreach($distinctFeedNames as $feedName)
                    <td class="font-weight-bold">{{ formatNumber($totals['pakan_harian'][$feedName] ?? 0, 0) }}</td>
                    @endforeach
                    @else
                    <td class="font-weight-bold">0</td>
                    @endif
                    <td class="font-weight-bold">{{ formatNumber($totals['pakan_total'] ?? 0, 0) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Summary Section -->
    <div class="summary-section">
        <!-- Ringkasan Laporan -->
        <div class="summary-card">
            <h3>Ringkasan Laporan</h3>
            <div class="summary-stats">
                <div class="stat-item stat-highlight">
                    <div class="stat-label">Stock Awal</div>
                    <div class="stat-value">{{ formatNumber($totals['stock_awal'] ?? 0, 0) }} ekor</div>
                </div>
                <div class="stat-item stat-highlight">
                    <div class="stat-label">Stock Akhir</div>
                    <div class="stat-value">{{ formatNumber($totals['stock_akhir'] ?? 0, 0) }} ekor</div>
                </div>
                <div class="stat-item stat-danger">
                    <div class="stat-label">Total Deplesi</div>
                    <div class="stat-value">{{ $totals['total_deplesi'] ?? 0 }} ekor ({{
                        number_format($totals['deplesi_percentage'] ?? 0, 2) }}%)</div>
                </div>
                <div class="stat-item stat-success">
                    <div class="stat-label">Survival Rate</div>
                    <div class="stat-value">{{ number_format($totals['survival_rate'] ?? 0, 2) }}%</div>
                </div>
            </div>
            <div class="stat-item">
                <div class="stat-label">Total Pakan Terpakai</div>
                <div class="stat-value">{{ formatNumber($totals['pakan_total'] ?? 0, 0) }} kg</div>
            </div>
            @if(count($distinctFeedNames) > 0)
            <div style="margin-top: 10px; font-size: 9pt; color: #6c757d;">
                <strong>Jenis Pakan:</strong> {{ implode(', ', $distinctFeedNames) }}
            </div>
            @else
            <div style="margin-top: 10px; font-size: 9pt; color: #6c757d;">
                <em>Tidak ada data penggunaan pakan</em>
            </div>
            @endif
        </div>

        <!-- Ringkasan Supply/OVK -->
        <div class="summary-card supply-card">
            <h3>Ringkasan Supply/OVK</h3>
            @if(isset($supplyUsage) && isset($supplyUsage['by_type']) && count($supplyUsage['by_type']) > 0)
            <div class="stat-item stat-highlight" style="margin-bottom: 15px;">
                <div class="stat-label">Total Biaya Supply/OVK</div>
                <div class="stat-value">Rp {{ number_format($supplyUsage['total_cost'] ?? 0, 0, ',', '.') }}</div>
            </div>

            <div class="supply-table">
                <table>
                    <thead>
                        <tr>
                            <th class="text-left">Nama Supply</th>
                            <th>Jumlah</th>
                            <th>Satuan</th>
                            <th class="text-right">Harga Satuan</th>
                            <th class="text-right">Total Biaya</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($supplyUsage['by_type'] as $supplyName => $data)
                        <tr>
                            <td class="text-left">{{ $supplyName }}</td>
                            <td>{{ formatNumber($data['quantity'] ?? 0, 2) }}</td>
                            <td>{{ $data['unit'] ?? '-' }}</td>
                            <td class="text-right">Rp {{ number_format($data['unit_cost'] ?? 0, 0, ',', '.') }}</td>
                            <td class="text-right font-weight-bold">Rp {{ number_format($data['cost'] ?? 0, 0, ',', '.')
                                }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="stat-item" style="text-align: center; color: #6c757d;">
                <em>Tidak ada data pemakaian supply/OVK untuk tanggal ini</em>
            </div>
            @endif
        </div>
    </div>
</body>

</html>
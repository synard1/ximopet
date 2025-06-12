<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Harian Kandang - {{ $farm }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 0.8cm;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.2;
            margin: 0;
            padding: 0;
        }

        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }

        .header h1 {
            margin: 0;
            font-size: 16pt;
            font-weight: bold;
            color: #333;
        }

        .header-info {
            margin: 5px 0;
            font-size: 11pt;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 9pt;
        }

        th,
        td {
            border: 1px solid #333;
            padding: 3px 4px;
            text-align: center;
            vertical-align: middle;
        }

        th {
            background-color: #f0f0f0;
            font-weight: bold;
            font-size: 8pt;
        }

        .header-row {
            background-color: #e6e6e6;
            font-weight: bold;
        }

        .total-row {
            background-color: #d9d9d9;
            font-weight: bold;
        }

        .text-left {
            text-align: left;
        }

        .text-right {
            text-align: right;
        }

        .footer {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
            font-size: 10pt;
        }

        .signature-box {
            text-align: center;
            width: 200px;
        }

        .signature-line {
            border-top: 1px solid #333;
            margin-top: 40px;
            padding-top: 5px;
        }

        .report-summary {
            margin: 10px 0;
            padding: 8px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 3px;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin: 5px 0;
        }

        .summary-item {
            text-align: center;
            padding: 5px;
            background-color: white;
            border: 1px solid #ccc;
            border-radius: 2px;
        }

        .summary-label {
            font-size: 8pt;
            color: #666;
            margin-bottom: 2px;
        }

        .summary-value {
            font-size: 11pt;
            font-weight: bold;
            color: #333;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>LAPORAN HARIAN KANDANG</h1>
        <div class="header-info">
            <strong>FARM:</strong> {{ $farm }} |
            <strong>TANGGAL:</strong> {{ $tanggal }} |
            <strong>TIPE:</strong> {{ strtoupper($reportType) }} {{ $reportType === 'detail' ? '(PER BATCH)' : '(PER
            KANDANG)' }}
        </div>
    </div>

    <!-- Report Summary -->
    <div class="report-summary">
        <div class="summary-grid">
            <div class="summary-item">
                <div class="summary-label">Total Kandang</div>
                <div class="summary-value">{{ count($recordings) }}</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Stock Awal</div>
                <div class="summary-value">{{ number_format($totals['stock_awal']) }}</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Total Deplesi</div>
                <div class="summary-value">{{ number_format($totals['total_deplesi']) }}</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Stock Akhir</div>
                <div class="summary-value">{{ number_format($totals['stock_akhir']) }}</div>
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th rowspan="3">KDG</th>
                @if($reportType === 'detail')
                <th rowspan="3">BATCH</th>
                @endif
                <th rowspan="3">UMUR</th>
                <th colspan="8">POPULASI AYAM</th>
                <th colspan="3">BERAT BADAN AYAM</th>
                <th colspan="{{ count($distinctFeedNames) + 1 }}">PEMAKAIAN PAKAN</th>
            </tr>
            <tr class="header-row">
                <th rowspan="2">EKOR<br>AYAM</th>
                <th colspan="3">DEPLESI</th>
                <th>%</th>
                <th colspan="2">PENJUALAN AYAM</th>
                <th rowspan="2">SISA<br>AYAM</th>
                <th rowspan="2">SEMALAM<br>(Gr)</th>
                <th rowspan="2">HARI INI<br>(Gr)</th>
                <th rowspan="2">KENAIKAN<br>(Gr)</th>
                <th colspan="{{ count($distinctFeedNames) }}">JENIS PAKAN</th>
                <th rowspan="2">TOTAL<br>TERPAKAI</th>
            </tr>
            <tr class="header-row">
                <th>MATI</th>
                <th>AFKIR</th>
                <th>TOTAL</th>
                <th>MORTALITAS</th>
                <th>EKOR</th>
                <th>KG</th>
                @foreach($distinctFeedNames as $feedName)
                <th>{{ $feedName }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @if($reportType === 'detail')
            @foreach($recordings as $coopNama => $batchesData)
            @foreach($batchesData as $index => $batch)
            <tr>
                @if($index === 0)
                <td rowspan="{{ count($batchesData) }}" class="text-left">{{ $coopNama ?? '' }}</td>
                @endif
                <td class="text-left">{{ $batch['livestock_name'] ?? '' }}</td>
                <td>{{ $batch['umur'] ?? '' }}</td>
                <td>{{ number_format($batch['stock_awal']) ?? '' }}</td>
                <td>{{ $batch['mati'] ?? '' }}</td>
                <td>{{ $batch['afkir'] ?? '' }}</td>
                <td>{{ $batch['total_deplesi'] ?? '' }}</td>
                <td>{{ $batch['deplesi_percentage'] ?? '' }}%</td>
                <td>{{ number_format($batch['jual_ekor']) ?? '' }}</td>
                <td>{{ number_format($batch['jual_kg']) ?? '' }}</td>
                <td>{{ number_format($batch['stock_akhir']) ?? '' }}</td>
                <td>{{ round($batch['berat_semalam'], 0) ?? '' }}</td>
                <td>{{ round($batch['berat_hari_ini'], 0) ?? '' }}</td>
                <td>{{ round($batch['kenaikan_berat'], 0) ?? '' }}</td>
                @foreach($distinctFeedNames as $feedName)
                <td>{{ number_format($batch['pakan_harian'][$feedName] ?? 0) }}</td>
                @endforeach
                <td>{{ number_format($batch['pakan_total']) ?? '' }}</td>
            </tr>
            @endforeach
            @endforeach
            @else
            @foreach($recordings as $coopNama => $record)
            <tr>
                <td class="text-left">{{ $coopNama ?? '' }}</td>
                <td>{{ $record['umur'] ?? '' }}</td>
                <td>{{ number_format($record['stock_awal']) ?? '' }}</td>
                <td>{{ $record['mati'] ?? '' }}</td>
                <td>{{ $record['afkir'] ?? '' }}</td>
                <td>{{ $record['total_deplesi'] ?? '' }}</td>
                <td>{{ $record['deplesi_percentage'] ?? '' }}%</td>
                <td>{{ number_format($record['jual_ekor']) ?? '' }}</td>
                <td>{{ number_format($record['jual_kg']) ?? '' }}</td>
                <td>{{ number_format($record['stock_akhir']) ?? '' }}</td>
                <td>{{ round($record['berat_semalam'], 0) ?? '' }}</td>
                <td>{{ round($record['berat_hari_ini'], 0) ?? '' }}</td>
                <td>{{ round($record['kenaikan_berat'], 0) ?? '' }}</td>
                @foreach($record['pakan_harian'] as $quantity)
                <td>{{ number_format($quantity, 0) }}</td>
                @endforeach
                <td>{{ number_format($record['pakan_total']) ?? '' }}</td>
            </tr>
            @endforeach
            @endif
        </tbody>

        <!-- Total Row -->
        <tfoot>
            <tr class="total-row">
                <td colspan="{{ $reportType === 'detail' ? '3' : '2' }}"><strong>TOTAL</strong></td>
                <td><strong>{{ number_format($totals['stock_awal']) ?? '' }}</strong></td>
                <td><strong>{{ $totals['mati'] ?? '' }}</strong></td>
                <td><strong>{{ $totals['afkir'] ?? '' }}</strong></td>
                <td><strong>{{ $totals['total_deplesi'] ?? '' }}</strong></td>
                <td></td>
                <td><strong>{{ number_format($totals['tangkap_ekor']) ?? '' }}</strong></td>
                <td><strong>{{ number_format($totals['tangkap_kg']) ?? '' }}</strong></td>
                <td><strong>{{ number_format($totals['stock_akhir']) ?? '' }}</strong></td>
                <td colspan="3"></td>
                @foreach($totals['pakan_harian'] as $totalQuantity)
                <td><strong>{{ number_format($totalQuantity, 0) }}</strong></td>
                @endforeach
                <td><strong>{{ number_format($totals['pakan_total']) ?? '' }}</strong></td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        {{-- <div class="signature-box">
            <div>Diketahui oleh,</div>
            <div class="signature-line">{{ $diketahui }}</div>
        </div>
        <div class="signature-box">
            <div>Dibuat oleh,</div>
            <div class="signature-line">{{ $dibuat }}</div>
        </div> --}}
    </div>

    <div style="text-align: center; margin-top: 15px; font-size: 8pt; color: #666;">
        Dicetak pada: {{ date('d/m/Y H:i:s') }} |
        {{ $reportType === 'detail' ? 'Detail Mode (Per Batch)' : 'Simple Mode (Per Kandang)' }}
    </div>
</body>

</html>
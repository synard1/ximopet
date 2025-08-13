<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Ekspedisi</title>
    <style>
        @page {
            size: portrait;
            margin: 1cm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 10pt;
            color: #2f3542;
            margin: 0;
            background: #fff;
        }

        /* Header lebih minimal dan rapat */
        .report-header {
            background: #f4f6f9;
            color: #2f3542;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #e6e9ef;
            margin-bottom: 10px;
        }

        .report-header h1 {
            margin: 0 0 6px 0;
            font-size: 14pt;
            font-weight: 700;
            text-align: center;
            letter-spacing: .3px;
        }

        .header-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 8px;
        }

        .header-info-item {
            background: #fff;
            padding: 6px 10px;
            border-radius: 6px;
            border: 1px solid #eceff3;
        }

        .header-info-item .label {
            font-size: 9pt;
            color: #6c757d;
        }

        .header-info-item .value {
            font-size: 10.5pt;
            font-weight: 600;
            color: #2f3542;
        }

        /* Cards ringkasan yang subtle dan rapat */
        .cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            margin: 8px 0 10px 0;
        }

        .card {
            background: #fff;
            border: 1px solid #e6e9ef;
            border-radius: 8px;
            padding: 10px;
            text-align: center;
        }

        .card h6 {
            margin: 0 0 4px 0;
            color: #6c757d;
            font-weight: 600;
            font-size: 10pt;
        }

        .card .val {
            font-size: 12.5pt;
            font-weight: 700;
            color: #2f3542;
        }

        /* Tabel */
        .table-container {
            background: #fff;
            border: 1px solid #e6e9ef;
            border-radius: 8px;
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
        }

        thead th {
            background: #f1f3f5;
            color: #2f3542;
            padding: 8px 6px;
            border: 1px solid #e6e9ef;
            text-align: center;
            font-weight: 600;
        }

        td {
            padding: 6px;
            border: 1px solid #e6e9ef;
            text-align: center;
            color: #2f3542;
        }

        .text-left {
            text-align: left;
        }

        tfoot td {
            background: #fafbfc;
            font-weight: 600;
        }

        @media print {

            .report-header,
            thead th,
            tfoot td {
                -webkit-print-color-adjust: exact;
                color-adjust: exact;
            }
        }
    </style>
</head>

<body>
    <div class="report-header">
        <h1>LAPORAN EKSPEDISI</h1>
        <div class="header-info">
            <div class="header-info-item">
                <div class="label">Periode</div>
                <div class="value">{{ formatDateId($filters['start_date'] ?? null) }} s.d. {{
                    formatDateId($filters['end_date'] ?? null) }}</div>
            </div>
            <div class="header-info-item">
                <div class="label">Ekspedisi</div>
                <div class="value">{{ $expeditionName }}</div>
            </div>
            {{-- Zona sementara disembunyikan --}}
            {{-- <div class="header-info-item">
                <div class="label">Zona</div>
                <div class="value">{{ $filters['zone'] ?? 'Semua' }}</div>
            </div> --}}
            <div class="header-info-item">
                <div class="label">Jenis Transaksi / Status</div>
                <div class="value">{{ transactionTypeLabel($filters['transaction_type'] ?? null) }} / {{
                    $filters['status'] ?? 'Semua' }}</div>
            </div>
        </div>
    </div>

    @if($includeHeader)
    <div class="cards">
        <div class="card">
            <h6>Total Transaksi</h6>
            <div class="val">{{ number_format($summary['total_transactions'] ?? 0) }}</div>
        </div>
        <div class="card">
            <h6>Total Biaya</h6>
            <div class="val">Rp {{ number_format($summary['total_cost'] ?? 0, 0, ',', '.') }}</div>
        </div>
        <div class="card">
            <h6>Total Berat</h6>
            <div class="val">{{ number_format($summary['total_weight'] ?? 0, 0, ',', '.') }} kg</div>
        </div>
        <div class="card">
            <h6>Rata Biaya/Kg</h6>
            <div class="val">Rp {{ number_format($summary['average_cost_per_kg'] ?? 0, 0, ',', '.') }}</div>
        </div>
    </div>
    @endif

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Ekspedisi</th>
                    <th>Jenis Transaksi</th>
                    {{-- <th>Zona Tujuan</th> --}}
                    <th>Total Berat (kg)</th>
                    <th>Biaya Ekspedisi</th>
                    <th>Biaya/Kg</th>
                    <th>Status</th>
                    <th>Tanggal Kirim</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="text-left">{{ $item['expedition']['name'] ?? '-' }}</td>
                    <td>{{ transactionTypeLabel($item['transaction_type'] ?? null) }}</td>
                    {{-- <td>{{ $item['destination_zone'] ?? '-' }}</td> --}}
                    <td>{{ number_format($item['total_weight'] ?? 0, 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($item['expedition_cost'] ?? 0, 0, ',', '.') }}</td>
                    <td>
                        @php
                        $tw = (float)($item['total_weight'] ?? 0);
                        $ec = (float)($item['expedition_cost'] ?? 0);
                        $cpk = $tw > 0 ? $ec / $tw : 0;
                        @endphp
                        Rp {{ number_format($cpk, 0, ',', '.') }}
                    </td>
                    <td>{{ $item['status'] ?? '-' }}</td>
                    <td>{{ formatDateId($item['shipping_date'] ?? null) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="9"><em>Tidak ada data</em></td>
                </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="text-left"><strong>TOTAL</strong></td>
                    <td><strong>{{ number_format($summary['total_weight'] ?? 0, 0, ',', '.') }}</strong></td>
                    <td><strong>Rp {{ number_format($summary['total_cost'] ?? 0, 0, ',', '.') }}</strong></td>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div style="text-align:center; margin-top:8px; font-size:8pt; color:#6c757d;">
        Dicetak pada: {{ date('d/m/Y H:i:s') }}
    </div>
</body>

</html>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Pemakaian Supply/OVK</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            line-height: 1.4;
            background-color: #f4f4f4;
        }

        header {
            background-color: #667eea;
            padding: 15px;
            text-align: center;
            font-weight: bold;
            color: #fff;
            font-size: 18px;
            letter-spacing: 1px;
        }

        .content {
            margin: 20px;
            background-color: white;
            padding: 18px 20px 20px 20px;
            border-radius: 8px;
            overflow-x: auto;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
            font-size: 14px;
        }

        .summary-table th,
        .summary-table td {
            padding: 6px 10px;
            border: 0;
            text-align: left;
        }

        .summary-table th {
            color: #888;
            font-weight: normal;
            width: 180px;
        }

        .summary-table td {
            font-weight: bold;
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 13px;
            background: #fff;
        }

        .report-table th,
        .report-table td {
            padding: 7px 6px;
            border: 1px solid #e0e0e0;
            white-space: nowrap;
            text-align: center;
        }

        .report-table th {
            background-color: #f0f0f0;
            font-weight: bold;
        }

        .report-table tfoot td {
            background: #f8f9fa;
            font-weight: bold;
            color: #333;
        }

        .status-badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-in_process {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-completed {
            background: #d4edda;
            color: #155724;
        }

        .summary-section {
            margin-top: 30px;
            background: #f8f9fa;
            border-radius: 6px;
            padding: 15px 20px;
            font-size: 14px;
        }

        .summary-section h3 {
            margin-top: 0;
            font-size: 16px;
            color: #333;
        }

        .summary-section table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .summary-section th,
        .summary-section td {
            padding: 6px 8px;
            border: 1px solid #e0e0e0;
            text-align: center;
        }

        .summary-section th {
            background: #f0f0f0;
            font-weight: bold;
        }

        .summary-section td {
            background: #fff;
        }

        .print-btn {
            display: block;
            margin: 25px auto 0 auto;
            background: #667eea;
            color: #fff;
            border: none;
            border-radius: 5px;
            padding: 10px 30px;
            font-size: 15px;
            cursor: pointer;
        }

        @media print {
            @page {
                size: A4 landscape;
                margin: 10mm;
            }

            body {
                background: #fff;
                font-size: 12px;
            }

            .content {
                margin: 10px;
                padding: 10px;
                overflow-x: visible;
            }

            .print-btn {
                display: none !important;
            }
        }
    </style>
</head>

<body>
    <header>LAPORAN PEMAKAIAN SUPPLY/OVK</header>
    <div class="content">
        <table class="summary-table">
            <tr>
                <th>Farm</th>
                <td>{{ $farm->name ?? '-' }}</td>
                <th>Periode</th>
                <td>{{ $summary['period'] ?? '-' }}</td>
            </tr>
            <tr>
                <th>Total Records</th>
                <td>{{ number_format($summary['total_records'] ?? 0) }}</td>
                <th>Total Biaya</th>
                <td>Rp {{ number_format($summary['total_cost'] ?? 0, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <th>Total Quantity</th>
                <td>{{ number_format($summary['total_quantity'] ?? 0, 2) }}</td>
                <th>Jenis Supply</th>
                <td>{{ $summary['supply_types_count'] ?? 0 }} jenis</td>
            </tr>
            <tr>
                <th>Tipe Laporan</th>
                <td>{{ ucfirst($reportType) }}</td>
                <th>Generated</th>
                <td>{{ now()->format('d M Y, H:i') }} WIB</td>
            </tr>
        </table>
        <h3 style="margin-top: 30px; margin-bottom: 10px; color: #667eea;">Detail Pemakaian Supply/OVK</h3>
        <div style="overflow-x:auto;">
            @if($reportType === 'detail')
            <table class="report-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>Batch</th>
                        <th>Kandang</th>
                        <th>Jenis Supply</th>
                        <th>Jumlah (Terkecil)</th>
                        <th>Satuan Terkecil</th>
                        <th>Harga Satuan (Terkecil)</th>
                        <th>Total Harga (Terkecil)</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $item['usage_date']->format('d/m/Y') }}</td>
                        <td>{{ $item['livestock_name'] }}</td>
                        <td>{{ $item['coop_name'] }}</td>
                        <td>{{ $item['supply_name'] }}</td>
                        <td style="text-align:right;">{{ number_format($item['converted_quantity'], 2) }}</td>
                        <td>{{ $item['converted_unit'] }}</td>
                        <td style="text-align:right;">Rp {{ number_format($item['converted_unit_cost'], 0, ',', '.') }}
                        </td>
                        <td style="text-align:right;">Rp {{ number_format($item['converted_total_cost'], 0, ',', '.') }}
                        </td>
                        <td>
                            <span class="status-badge status-{{ $item['status'] }}">{{ ucfirst($item['status'])
                                }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10">Tidak ada data pemakaian supply untuk periode yang dipilih</td>
                    </tr>
                    @endforelse
                </tbody>
                @if(count($data) > 0)
                <tfoot>
                    <tr>
                        <td colspan="8" style="text-align:right;">TOTAL</td>
                        <td style="text-align:right;">Rp {{ number_format($totals['total_cost'], 0, ',', '.') }}</td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
            @else
            <table class="report-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>Batch</th>
                        <th>Kandang</th>
                        <th>Jenis Supply</th>
                        <th>Jumlah (Terkecil)</th>
                        <th>Satuan Terkecil</th>
                        <th>Harga Satuan (Terkecil)</th>
                        <th>Total Harga (Terkecil)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $item['usage_date']->format('d/m/Y') }}</td>
                        <td>{{ $item['livestock_name'] }}</td>
                        <td>{{ $item['coop_name'] }}</td>
                        <td>{{ $item['supply_name'] }}</td>
                        <td style="text-align:right;">{{ number_format($item['converted_quantity'], 2) }}</td>
                        <td>{{ $item['converted_unit'] }}</td>
                        <td style="text-align:right;">Rp {{ number_format($item['converted_unit_cost'], 0, ',', '.') }}
                        </td>
                        <td style="text-align:right;">Rp {{ number_format($item['converted_total_cost'], 0, ',', '.') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9">Tidak ada data pemakaian supply untuk periode yang dipilih</td>
                    </tr>
                    @endforelse
                </tbody>
                @if(count($data) > 0)
                <tfoot>
                    <tr>
                        <td colspan="8" style="text-align:right;">TOTAL</td>
                        <td style="text-align:right;">Rp {{ number_format($totals['total_cost'], 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
            @endif
        </div>
        <div class="summary-section">
            <h3>Ringkasan per Jenis Supply</h3>
            <table>
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
                        <td>{{ $totals['total_cost'] > 0 ? number_format(($supplyData['cost'] / $totals['total_cost']) *
                            100, 1) : 0 }}%</td>
                    </tr>
                    @endforeach
                    @else
                    <tr>
                        <td colspan="5">Tidak ada data</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
        {{-- <button class="print-btn" onclick="window.print()">Cetak Laporan</button> --}}
    </div>
</body>

</html>
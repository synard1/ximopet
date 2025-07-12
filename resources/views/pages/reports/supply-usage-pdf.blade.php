<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Pemakaian Supply/OVK</title>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }

        .header h1 {
            margin: 0;
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
        }

        .summary {
            margin-bottom: 20px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }

        .summary-label {
            font-weight: bold;
            color: #666;
        }

        .summary-value {
            font-weight: bold;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 9px;
        }

        .table th,
        .table td {
            border: 1px solid #ddd;
            padding: 4px 6px;
            text-align: left;
            vertical-align: top;
        }

        .table th {
            background: #f1f3f6;
            font-weight: bold;
            text-align: center;
        }

        .table td {
            text-align: left;
        }

        .text-center {
            text-align: center;
        }

        .text-end {
            text-align: right;
        }

        .text-right {
            text-align: right;
        }

        .badge {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
        }

        .badge-pending {
            background: #fff3cd;
            color: #856404;
        }

        .badge-in_process {
            background: #d1ecf1;
            color: #0c5460;
        }

        .badge-completed {
            background: #d4edda;
            color: #155724;
        }

        .supply-breakdown {
            font-size: 8px;
            color: #666;
        }

        .supply-item {
            margin-bottom: 1px;
            padding: 1px 2px;
            background: #f8f9fa;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 8px;
            color: #666;
        }

        .page-break {
            page-break-before: always;
        }

        .no-data {
            text-align: center;
            padding: 20px;
            color: #666;
            font-style: italic;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 8px;
        }

        .summary-table th,
        .summary-table td {
            border: 1px solid #ddd;
            padding: 3px 4px;
            text-align: left;
        }

        .summary-table th {
            background: #f1f3f6;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>LAPORAN PEMAKAIAN SUPPLY/OVK</h1>
    </div>

    <div class="summary">
        <div class="summary-row">
            <span class="summary-label">Farm:</span>
            <span class="summary-value">{{ $farm->name ?? '-' }}</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Periode:</span>
            <span class="summary-value">{{ $summary['period'] ?? '-' }}</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Total Records:</span>
            <span class="summary-value">{{ number_format($summary['total_records'] ?? 0) }}</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Total Biaya:</span>
            <span class="summary-value">Rp {{ number_format($summary['total_cost'] ?? 0, 0, ',', '.') }}</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Total Quantity:</span>
            <span class="summary-value">{{ number_format($summary['total_quantity'] ?? 0, 2) }}</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Jenis Supply:</span>
            <span class="summary-value">{{ $summary['supply_types_count'] ?? 0 }} jenis</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Tipe Laporan:</span>
            <span class="summary-value">{{ ucfirst($reportType) }}</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Generated:</span>
            <span class="summary-value">{{ now()->format('d M Y, H:i') }} WIB</span>
        </div>
    </div>

    @if($reportType === 'detail')
    <!-- Detail Report Table -->
    <table class="table">
        <thead>
            <tr>
                <th style="width: 5%">No</th>
                <th style="width: 10%">Tanggal</th>
                <th style="width: 15%">Batch</th>
                <th style="width: 12%">Kandang</th>
                <th style="width: 18%">Jenis Supply</th>
                <th style="width: 8%">Jumlah</th>
                <th style="width: 6%">Satuan</th>
                <th style="width: 12%">Harga Satuan</th>
                <th style="width: 12%">Total Harga</th>
                <th style="width: 8%">Status</th>
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
                <td class="text-end">{{ number_format($item['quantity'], 2) }}</td>
                <td class="text-center">{{ $item['unit'] }}</td>
                <td class="text-end">Rp {{ number_format($item['unit_cost'], 0, ',', '.') }}</td>
                <td class="text-end">Rp {{ number_format($item['total_cost'], 0, ',', '.') }}</td>
                <td class="text-center">
                    <span class="badge badge-{{ $item['status'] }}">
                        {{ ucfirst($item['status']) }}
                    </span>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="10" class="no-data">
                    Tidak ada data pemakaian supply untuk periode yang dipilih
                </td>
            </tr>
            @endforelse
        </tbody>
        @if(count($data) > 0)
        <tfoot>
            <tr style="background: #f8f9fa; font-weight: bold;">
                <td colspan="8" class="text-end">TOTAL</td>
                <td class="text-end">Rp {{ number_format($totals['total_cost'], 0, ',', '.') }}</td>
                <td></td>
            </tr>
        </tfoot>
        @endif
    </table>
    @else
    <!-- Simple Report Table -->
    <table class="table">
        <thead>
            <tr>
                <th style="width: 5%">No</th>
                <th style="width: 10%">Tanggal</th>
                <th style="width: 20%">Batch</th>
                <th style="width: 15%">Kandang</th>
                <th style="width: 8%">Jumlah Supply</th>
                <th style="width: 12%">Total Quantity</th>
                <th style="width: 15%">Total Biaya</th>
                <th style="width: 15%">Detail Supply</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td class="text-center">{{ $item['usage_date']->format('d/m/Y') }}</td>
                <td>{{ $item['livestock_name'] }}</td>
                <td>{{ $item['coop_name'] }}</td>
                <td class="text-center">{{ $item['supply_count'] }}</td>
                <td class="text-end">{{ number_format($item['total_quantity'], 2) }}</td>
                <td class="text-end">Rp {{ number_format($item['total_cost'], 0, ',', '.') }}</td>
                <td>
                    <div class="supply-breakdown">
                        @foreach($item['supply_breakdown'] as $supplyName => $supplyData)
                        <div class="supply-item">
                            <strong>{{ $supplyName }}:</strong>
                            {{ number_format($supplyData['quantity'], 2) }} {{ $supplyData['unit'] }}
                            (Rp {{ number_format($supplyData['cost'], 0, ',', '.') }})
                        </div>
                        @endforeach
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="no-data">
                    Tidak ada data pemakaian supply untuk periode yang dipilih
                </td>
            </tr>
            @endforelse
        </tbody>
        @if(count($data) > 0)
        <tfoot>
            <tr style="background: #f8f9fa; font-weight: bold;">
                <td colspan="5" class="text-end">TOTAL</td>
                <td class="text-end">{{ number_format($totals['total_quantity'], 2) }}</td>
                <td class="text-end">Rp {{ number_format($totals['total_cost'], 0, ',', '.') }}</td>
                <td></td>
            </tr>
        </tfoot>
        @endif
    </table>
    @endif

    @if(count($data) > 0 && !empty($totals['supply_types']))
    <div class="page-break"></div>

    <div class="header">
        <h1>RINGKASAN PER JENIS SUPPLY</h1>
    </div>

    <table class="summary-table">
        <thead>
            <tr>
                <th style="width: 40%">Jenis Supply</th>
                <th style="width: 20%">Total Quantity</th>
                <th style="width: 10%">Satuan</th>
                <th style="width: 20%">Total Biaya</th>
                <th style="width: 10%">% dari Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($totals['supply_types'] as $supplyName => $supplyData)
            <tr>
                <td>{{ $supplyName }}</td>
                <td class="text-end">{{ number_format($supplyData['quantity'], 2) }}</td>
                <td class="text-center">{{ $supplyData['unit'] }}</td>
                <td class="text-end">Rp {{ number_format($supplyData['cost'], 0, ',', '.') }}</td>
                <td class="text-center">
                    {{ $totals['total_cost'] > 0 ? number_format(($supplyData['cost'] / $totals['total_cost']) * 100, 1)
                    : 0 }}%
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background: #f8f9fa; font-weight: bold;">
                <td>TOTAL</td>
                <td class="text-end">{{ number_format($totals['total_quantity'], 2) }}</td>
                <td></td>
                <td class="text-end">Rp {{ number_format($totals['total_cost'], 0, ',', '.') }}</td>
                <td class="text-center">100%</td>
            </tr>
        </tfoot>
    </table>
    @endif

    <div class="footer">
        <p>Laporan ini dibuat secara otomatis oleh sistem pada {{ now()->format('d M Y, H:i') }} WIB</p>
        <p>Halaman 1 dari {{ count($data) > 0 ? '2' : '1' }}</p>
    </div>
</body>

</html>
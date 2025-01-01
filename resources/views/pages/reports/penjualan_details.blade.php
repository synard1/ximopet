<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penjualan Ternak</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 1cm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.3;
            margin: 0;
            padding: 0;
        }
        h1 {
            font-size: 14pt;
            margin-bottom: 10px;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 10px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 3px;
            font-size: 8pt;
        }
        th {
            background-color: #f2f2f2;
            text-align: center;
            font-weight: bold;
        }
        td {
            text-align: center;
        }
        .info-table {
            width: auto;
            margin: 0 0 10px 0;
            border: none;
        }
        .info-table td {
            border: none;
            padding: 2px;
            font-size: 9pt;
        }
        .info-table td:first-child {
            font-weight: bold;
            text-align: left;
            padding-right: 5px;
        }
        .info-table td:last-child {
            text-align: left;
        }
        tfoot th, tfoot td {
            font-weight: bold;
        }
        @media print {
            body {
                width: 210mm;
                height: 297mm;
            }
        }
    </style>
</head>
<body>
    <h1>Laporan Penjualan</h1>
    <table class="info-table">
        <tbody>
            <tr>
                <td>
                    Nama Kandang
                </td>
                <td>
                    : {{ $kandang }}
                </td>
            </tr>
            <tr>
                <td>
                    Periode Pemeliharaan
                </td>
                <td>
                    : {{ $periode }}
                </td>
            </tr>
        </tbody>
    </table>

    <table id="tableReport">
        <thead>
            <tr>
                <th>Tgl Masuk DOC</th>
                <th>Tgl Penjualan</th>
                <th>No. Faktur</th>
                <th>Nama Pelanggan</th>
                <th>Jumlah</th>
                <th>Berat (Kg)</th>
                <th>ABW (Kg)</th>
                <th>Harga/Kg (Rp)</th>
                <th>Total (Rp)</th>
                <th>Umur Panen</th>
                <th>Umur Panen x Jumlah Ayam</th>
            </tr>
        </thead>
        <tbody>
            @forelse($penjualanData as $data)
                <tr>
                    <td>{{ $data->kelompokTernak->start_date->format('d-M-y') }}</td>
                    <td>{{ $data->tanggal->format('d-M-y') }}</td>
                    <td>{{ $data->faktur }}</td>
                    <td style="text-align: left;">{{ $data->detail->rekanan->nama }}</td>
                    <td style="text-align: right;">{{ number_format($data->jumlah, 0, ',', '.') }}</td>
                    <td style="text-align: right;">{{ number_format($data->detail->berat, 1, ',', '.') }}</td>
                    <td>{{ number_format(round($data->detail->berat / $data->jumlah, 2), 2, ',', '.') }}</td>
                    <td>{{ number_format($data->harga, 0, ',', '.') }}</td>
                    <td style="text-align: right;">{{ number_format($data->detail->harga_jual * $data->detail->berat, 0, ',', '.') }}</td>
                    <td>{{ $data->detail->umur }}</td>
                    <td style="text-align: right;">{{ number_format($data->detail->umur * $data->jumlah , 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10">No data available</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <th colspan="4">Total</th>
                <th style="text-align: right; font-weight: bold;">{{ number_format($penjualanData->sum('jumlah'), 0, ',', '.') }}</th>
                <th style="text-align: right; font-weight: bold;">{{ number_format($penjualanData->sum('detail.berat'), 1, ',', '.') }}</th>
                <th>
                    {{ number_format($penjualanData->sum('detail.berat')/$penjualanData->sum('jumlah'), 2, ',', '.') }}
                </th>       
                <th></th>
                <th style="text-align: right; font-weight: bold;">{{ number_format($penjualanData->sum(fn($data) => $data->detail->berat * $data->harga), 0, ',', '.') }}</th>
                <th></th>
                <th style="text-align: right; font-weight: bold;">{{ number_format($penjualanData->sum(fn($data) => $data->detail->umur * $data->jumlah), 0, ',', '.') }}</th>
            </tr>
            <tr>
                <td colspan="8" style="text-align: center; font-weight: bold;">Rata - Rata Umur Panen</td>
                <td style="text-align: right; font-weight: bold;">{{ number_format($penjualanData->sum(fn($data) => $data->detail->umur * $data->jumlah) / $penjualanData->sum('jumlah'), 2, ',', '.') }}</td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td colspan="8" style="text-align: center; font-weight: bold;">Rata - Rata Harga Jual (Rp)</td>
                <td style="text-align: right; font-weight: bold;">{{ number_format($penjualanData->sum(fn($data) => $data->detail->berat * $data->harga) / $penjualanData->sum('detail.berat'), 0, ',', '.') }}</td>
                <td></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>

@php
    
    function formatNumber($amount,$decimal) {
        // Convert the number to a string with two decimal places
        $formattedAmount = number_format($amount, $decimal, ',', '.');
    
        // Add the currency symbol and return the formatted number
        return $formattedAmount;
    }

@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Harian Kandang</title>
    <style>
        @page {
            size: landscape;
            margin: 1cm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.3;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid black;
            padding: 4px 8px;
            text-align: center;
        }
        .header {
            font-weight: bold;
            text-align: left;
            margin-bottom: 10px;
        }
        .header-row td {
            background-color: #e6e6ff;
            font-weight: bold;
        }
        .footer-signatures {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
        }
        .text-left { text-align: left; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .no-border { border: none; }
    </style>
</head>
<body>
    <div class="header">
        LAPORAN HARIAN KANDANG<br>
        FARM : {{ $farm }}<br>
        TANGGAL : {{ $tanggal }}
    </div>

    <table>
        <thead class="bg-gray-100 text-gray-700">
            <tr>
                <th class="table-header" rowspan="3">KDG</th>
                <th class="table-header" rowspan="3">UMUR</th>
                <th colspan="8">POPULASI AYAM</th>
                <th colspan="3">BERAT BADAN AYAM</th>
                <th colspan="4">PEMAKAIAN PAKAN</th>
                {{-- <th colspan="3">STRAIN ROSS</th> --}}
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
                <th colspan="3">JENIS PAKAN</th>
                <th rowspan="2">TOTAL<br>TERPAKAI</th>
                {{-- <th>NORMAL %</th>
                <th>BM-TK %</th>
                <th>GP %</th> --}}
            </tr>
            <tr class="header-row">
                <th>MATI</th>
                <th>AFKIR</th>
                <th>TOTAL</th>
                <th>MORTALITAS</th>
                <th>EKOR</th>
                <th>KG</th>
                <th>SP 10</th>
                <th>SP 11</th>
                <th>SP 12</th>
                {{-- <th></th>
                <th></th>
                <th></th> --}}
            </tr>
        </thead>
        @foreach($recordings as $kandangNama => $record)
        <tr>
            <td>{{ $kandangNama ?? '' }}</td>
            <td>{{ $record['umur'] ?? '' }}</td>
            <td>{{ formatNumber($record['stock_awal'],0) ?? '' }}</td>
            <td>{{ $record['mati'] ?? '' }}</td>
            <td>{{ $record['afkir'] ?? '' }}</td>
            <td>{{ $record['total_deplesi'] ?? '' }}</td>
            <td>{{ $record['deplesi_percentage'] ?? '' }}</td>
            <td>{{ formatNumber($record['jual_ekor'],0) ?? '' }}</td>
            <td>{{ formatNumber($record['jual_kg'],0) ?? '' }}</td>
            <td>{{ formatNumber($record['stock_akhir'],0) ?? '' }}</td>
            <td>{{ round($record['berat_semalam'], 0) ?? '' }}</td>
            <td>{{ round($record['berat_hari_ini'], 0) ?? '' }}</td>
            <td>{{ round($record['kenaikan_berat'], 0) ?? '' }}</td>
            <td>{{ formatNumber($record['pakan_harian']['SP 10'] ?? 0, 0) }}</td>
            <td>{{ formatNumber($record['pakan_harian']['SP 11'] ?? 0, 0) }}</td>
            <td>{{ formatNumber($record['pakan_harian']['SP 12'] ?? 0, 0) }}</td>
            <td>{{ formatNumber($record['pakan_total'],0) ?? '' }}</td>
            {{-- <td>{{ $record['normal_percentage'] ?? '' }}</td>
            <td>{{ $record['bmtk_percentage'] ?? '' }}</td>
            <td>{{ $record['gp_percentage'] ?? '' }}</td> --}}
        </tr>
        @endforeach


        <!-- Total Row -->
        <tr class="header-row">
            <td colspan="2">TOTAL</td>
            <td>{{ formatNumber($totals['stock_awal'],0) ?? '' }}</td>
            <td>{{ $totals['mati'] ?? '' }}</td>
            <td>{{ $totals['afkir'] ?? '' }}</td>
            <td>{{ $totals['total_deplesi'] ?? '' }}</td>
            <td></td>
            <td>{{ formatNumber($totals['tangkap_ekor'],0) ?? '' }}</td>
            <td>{{ formatNumber($totals['tangkap_kg'],0) ?? '' }}</td>
            <td>{{ formatNumber($totals['stock_akhir'],0) ?? '' }}</td>
            <td colspan="3"></td>
            <td>{{ formatNumber($totals['pakan_harian']['SP 10'] ?? 0, 0) }}</td>
            <td>{{ formatNumber($totals['pakan_harian']['SP 11'] ?? 0, 0) }}</td>
            <td>{{ formatNumber($totals['pakan_harian']['SP 12'] ?? 0, 0) }}</td>
            <td>{{ formatNumber($totals['pakan_total'],0) ?? '' }}</td>
            {{-- <td colspan="3"></td> --}}
        </tr>
    </table>

    <div class="footer-signatures">
        {{-- <div>
            Diketahui oleh,<br><br><br>
            ( {{ $diketahui }} )
        </div>
        <div>
            Dibuat oleh,<br><br><br>
            ( {{ $dibuat }} )
        </div> --}}
    </div>
</body>
</html>
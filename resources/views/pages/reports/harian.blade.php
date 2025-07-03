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

        th,
        td {
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

        .text-left {
            text-align: left;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .no-border {
            border: none;
        }

        .summary-info {
            margin-top: 20px;
            padding: 10px;
            background-color: #f5f5f5;
            border: 1px solid #ddd;
        }
    </style>
</head>

<body>
    <div class="header">
        LAPORAN HARIAN KANDANG<br>
        FARM : {{ $farm }}<br>
        TANGGAL : {{ $tanggal }}<br>
        TIPE : {{ strtoupper($reportType) }} {{ $reportType === 'detail' ? '(PER BATCH)' : '(PER KANDANG)' }}
        @if($reportType === 'detail')
        <br><small>Mode Detail: Menampilkan data per deplesi record dengan normalisasi jenis deplesi</small>
        @endif
    </div>

    <table>
        <thead class="bg-gray-100 text-gray-700">
            <tr>
                <th class="table-header" rowspan="3">KDG</th>
                @if($reportType === 'detail')
                <th class="table-header" rowspan="3">BATCH</th>
                @endif
                <th class="table-header" rowspan="3">UMUR</th>
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
                @if(count($distinctFeedNames) > 0)
                <th colspan="{{ count($distinctFeedNames) }}">JENIS PAKAN</th>
                @else
                <th>JENIS PAKAN</th>
                @endif
                <th rowspan="2">TOTAL<br>TERPAKAI</th>
            </tr>
            <tr class="header-row">
                <th>MATI</th>
                <th>AFKIR</th>
                <th>TOTAL</th>
                <th>MORTALITAS</th>
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
                <td rowspan="{{ $validBatches->count() }}">{{ $coopNama ?? '-' }}</td>
                @endif
                <td title="Batch: {{ $batch['livestock_name'] ?? '-' }}">
                    {{ $batch['livestock_name'] ?? '-' }}
                    @if(isset($batch['depletion_type']) && $batch['depletion_type'])
                    <br><small class="text-muted">{{ $batch['depletion_category'] ?? 'other' }}</small>
                    @endif
                </td>
                <td>{{ $batch['umur'] ?? '0' }}</td>
                <td>{{ formatNumber($batch['stock_awal'] ?? 0, 0) }}</td>
                <td>{{ $batch['mati'] ?? '0' }}</td>
                <td>{{ $batch['afkir'] ?? '0' }}</td>
                <td>{{ $batch['total_deplesi'] ?? '0' }}</td>
                <td>{{ number_format($batch['deplesi_percentage'] ?? 0, 2) }}%</td>
                <td>{{ formatNumber($batch['jual_ekor'] ?? 0, 0) }}</td>
                <td>{{ formatNumber($batch['jual_kg'] ?? 0, 0) }}</td>
                <td>{{ formatNumber($batch['stock_akhir'] ?? 0, 0) }}</td>
                <td>{{ round($batch['berat_semalam'] ?? 0, 0) }}</td>
                <td>{{ round($batch['berat_hari_ini'] ?? 0, 0) }}</td>
                <td>{{ round($batch['kenaikan_berat'] ?? 0, 0) }}</td>
                @if(count($distinctFeedNames) > 0)
                @foreach($distinctFeedNames as $feedName)
                <td>{{ formatNumber($batch['pakan_harian'][$feedName] ?? 0, 0) }}</td>
                @endforeach
                @else
                <td>0</td>
                @endif
                <td>{{ formatNumber($batch['pakan_total'] ?? 0, 0) }}</td>
            </tr>
            @endforeach
            @else
            {{-- No valid batches found --}}
            <tr>
                <td>{{ $coopNama ?? '-' }}</td>
                <td colspan="{{ 13 + count($distinctFeedNames) }}">Data batch tidak valid atau kosong</td>
            </tr>
            @endif
            @else
            {{-- Fallback jika data batch tidak valid --}}
            <tr>
                <td>{{ $coopNama ?? '-' }}</td>
                <td colspan="{{ 13 + count($distinctFeedNames) }}">Format data tidak sesuai ({{ gettype($batchesData)
                    }})</td>
            </tr>
            @endif
            @empty
            <tr>
                <td colspan="{{ 15 + count($distinctFeedNames) }}">Tidak ada data untuk ditampilkan</td>
            </tr>
            @endforelse
            @else
            {{-- MODE SIMPLE: Tampilkan per kandang --}}
            @forelse($recordings as $coopNama => $record)
            <tr>
                <td>{{ $coopNama ?? '-' }}</td>
                <td>{{ $record['umur'] ?? '0' }}</td>
                <td>{{ formatNumber($record['stock_awal'] ?? 0, 0) }}</td>
                <td>{{ $record['mati'] ?? '0' }}</td>
                <td>{{ $record['afkir'] ?? '0' }}</td>
                <td>{{ $record['total_deplesi'] ?? '0' }}</td>
                <td>{{ number_format($record['deplesi_percentage'] ?? 0, 2) }}%</td>
                <td>{{ formatNumber($record['jual_ekor'] ?? 0, 0) }}</td>
                <td>{{ formatNumber($record['jual_kg'] ?? 0, 0) }}</td>
                <td>{{ formatNumber($record['stock_akhir'] ?? 0, 0) }}</td>
                <td>{{ round($record['berat_semalam'] ?? 0, 0) }}</td>
                <td>{{ round($record['berat_hari_ini'] ?? 0, 0) }}</td>
                <td>{{ round($record['kenaikan_berat'] ?? 0, 0) }}</td>
                @if(count($distinctFeedNames) > 0 && isset($record['pakan_harian']))
                @foreach($distinctFeedNames as $feedName)
                <td>{{ formatNumber($record['pakan_harian'][$feedName] ?? 0, 0) }}</td>
                @endforeach
                @else
                @for($i = 0; $i < max(1, count($distinctFeedNames)); $i++) <td>0</td>
                    @endfor
                    @endif
                    <td>{{ formatNumber($record['pakan_total'] ?? 0, 0) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="{{ 14 + count($distinctFeedNames) }}">Tidak ada data untuk ditampilkan</td>
            </tr>
            @endforelse
            @endif
        </tbody>

        <!-- Total Row -->
        <tfoot>
            <tr class="header-row">
                <td colspan="{{ $reportType === 'detail' ? '3' : '2' }}">TOTAL</td>
                <td>{{ formatNumber($totals['stock_awal'] ?? 0, 0) }}</td>
                <td>{{ $totals['mati'] ?? '0' }}</td>
                <td>{{ $totals['afkir'] ?? '0' }}</td>
                <td>{{ $totals['total_deplesi'] ?? '0' }}</td>
                <td>{{ number_format($totals['deplesi_percentage'] ?? 0, 2) }}%</td>
                <td>{{ formatNumber($totals['jual_ekor'] ?? 0, 0) }}</td>
                <td>{{ formatNumber($totals['jual_kg'] ?? 0, 0) }}</td>
                <td>{{ formatNumber($totals['stock_akhir'] ?? 0, 0) }}</td>
                <td colspan="3"></td>
                @if(count($distinctFeedNames) > 0)
                @foreach($distinctFeedNames as $feedName)
                <td>{{ formatNumber($totals['pakan_harian'][$feedName] ?? 0, 0) }}</td>
                @endforeach
                @else
                <td>0</td>
                @endif
                <td>{{ formatNumber($totals['pakan_total'] ?? 0, 0) }}</td>
            </tr>
        </tfoot>
    </table>

    <!-- Summary Information -->
    <div class="summary-info">
        <strong>RINGKASAN LAPORAN:</strong><br>
        Total Stock Awal: {{ formatNumber($totals['stock_awal'] ?? 0, 0) }} ekor<br>
        Total Stock Akhir: {{ formatNumber($totals['stock_akhir'] ?? 0, 0) }} ekor<br>
        Total Deplesi: {{ $totals['total_deplesi'] ?? 0 }} ekor ({{ number_format($totals['deplesi_percentage'] ?? 0, 2)
        }}%)<br>
        Survival Rate: {{ number_format($totals['survival_rate'] ?? 0, 2) }}%<br>
        Total Pakan Terpakai: {{ formatNumber($totals['pakan_total'] ?? 0, 0) }} kg<br>
        @if(count($distinctFeedNames) > 0)
        Jenis Pakan yang Digunakan: {{ implode(', ', $distinctFeedNames) }}
        @else
        Tidak ada data penggunaan pakan
        @endif
    </div>

    <div class="footer-signatures">
        {{-- Signature section commented out as per original --}}
    </div>
</body>

</html>
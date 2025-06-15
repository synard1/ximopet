<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Performa Ayam Broiler</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            line-height: 1.4;
            background-color: #f4f4f4;
        }

        header {
            background-color: #FFD700;
            padding: 15px;
            text-align: center;
            font-weight: bold;
            color: #333;
            font-size: 16px;
        }

        .content {
            margin: 20px;
            background-color: white;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
        }

        .content p {
            margin: 8px 0;
        }

        .content p strong {
            display: inline-block;
            width: 200px;
        }

        .info-header {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 14px;
            text-align: left;
            table-layout: auto;
        }

        .info-header th,
        .info-header td {
            padding: 6px;
            border: 0px solid #ddd;
            white-space: nowrap;
            font-weight: bold;
        }

        .performance-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 12px;
            text-align: left;
            table-layout: auto;
        }

        .performance-table th,
        .performance-table td {
            padding: 6px;
            border: 1px solid #ddd;
            white-space: nowrap;
        }

        .performance-table th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }

        .performance-table td {
            text-align: center;
        }

        /* Color coding for performance indicators */
        .fcr-good {
            background-color: #d4edda;
            color: #155724;
        }

        .fcr-average {
            background-color: #fff3cd;
            color: #856404;
        }

        .fcr-poor {
            background-color: #f8d7da;
            color: #721c24;
        }

        .ip-excellent {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .ip-good {
            background-color: #d4edda;
            color: #155724;
        }

        .ip-average {
            background-color: #fff3cd;
            color: #856404;
        }

        .ip-poor {
            background-color: #f8d7da;
            color: #721c24;
        }

        .weight-above {
            background-color: #d4edda;
            color: #155724;
        }

        .weight-below {
            background-color: #f8d7da;
            color: #721c24;
        }

        .ovk-highlight {
            background-color: #e7f3ff;
        }

        .feed-highlight {
            background-color: #f0fff0;
        }

        .table-header {
            padding: 8px 4px;
            text-align: center;
            border: 1px solid #ddd;
            background-color: #f0f0f0;
            font-weight: bold;
            font-size: 11px;
        }

        .strain-info {
            background-color: #e9ecef;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            font-weight: bold;
        }

        .legend {
            margin: 15px 0;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
            font-size: 11px;
        }

        .legend-item {
            display: inline-block;
            margin: 2px 5px;
            padding: 2px 8px;
            border-radius: 3px;
        }

        @media print {
            @page {
                size: A4 landscape;
                margin: 10mm;
            }

            body {
                margin: 0;
                padding: 0;
                font-size: 12px;
            }

            .content {
                margin: 10px;
                padding: 10px;
                overflow-x: visible;
            }

            .performance-table {
                font-size: 10px;
                margin: 10px 0;
                width: 100%;
                table-layout: auto;
            }

            .performance-table th,
            .performance-table td {
                padding: 4px;
            }

            header {
                font-size: 14px;
                padding: 10px;
            }

            .hide-on-print {
                display: none !important;
            }
        }
    </style>
</head>

<body>
    <header>
        LAPORAN PERFORMA AYAM BROILER - ENHANCED
    </header>

    <div class="content">
        <!-- Farm and Livestock Information -->
        <table class='info-header'
            style="width: 100%; margin-bottom: 20px; font-size: 14px; border-collapse: collapse;">
            <tr>
                <td><strong>FARM</strong></td>
                <td>: {{ $currentLivestock->livestock->farm->name }}</td>
                <td><strong>DOC MASUK</strong></td>
                <td>: {{ number_format($currentLivestock->livestock->initial_quantity) }} Ekor</td>
            </tr>
            <tr>
                <td><strong>KANDANG</strong></td>
                <td>: {{ $currentLivestock->livestock->coop->name }}</td>
                <td><strong>BONUS DOC</strong></td>
                <td>: {{ number_format($currentLivestock->livestock->bonus_doc ?? 0) }} Ekor</td>
            </tr>
            <tr>
                <td><strong>TGL. MASUK DOC</strong></td>
                <td>: {{ $currentLivestock->livestock->start_date->translatedFormat('d F Y') }}</td>
                <td><strong>STRAIN</strong></td>
                <td>: {{ $strain ?? '-' }}</td>
            </tr>
            <tr>
                <td><strong>PERIODE</strong></td>
                <td>: {{ $currentLivestock->livestock->name }}</td>
                <td><strong>BERAT RATA-RATA DOC</strong></td>
                <td>: {{ number_format($currentLivestock->livestock->initial_weight ?? 0) }} Gram</td>
            </tr>
        </table>

        <!-- Strain Information -->
        @if(isset($strain) && $strain !== 'Unknown')
        <div class="strain-info">
            <strong>INFORMASI STRAIN:</strong> {{ $strain }}
            - Standar FCR dan IP disesuaikan dengan karakteristik strain ini
        </div>
        @endif

        <!-- Performance Legend -->
        <div class="legend">
            <strong>KETERANGAN WARNA:</strong>
            <span class="legend-item fcr-good">FCR Baik (≤ Standar)</span>
            <span class="legend-item fcr-poor">FCR Buruk (> Standar)</span>
            <span class="legend-item ip-excellent">IP Sangat Baik (≥ 400)</span>
            <span class="legend-item ip-good">IP Baik (300-399)</span>
            <span class="legend-item ip-poor">IP Buruk (< 300)</span>
                    <span class="legend-item weight-above">Berat > Standar</span>
                    <span class="legend-item weight-below">Berat < Standar</span>
        </div>

        <!-- Main Performance Table -->
        <table class="performance-table">
            <thead>
                <tr style="text-align: center;">
                    <th class="table-header" rowspan="2">Tanggal</th>
                    <th class="table-header" rowspan="2">Umur<br>(Hari)</th>
                    <th class="table-header" rowspan="2">Stock<br>Awal</th>

                    <!-- Deplesi Column Group -->
                    <th class="table-header" colspan="4">Deplesi</th>

                    <!-- Penangkapan Column Group -->
                    <th class="table-header" colspan="3">Penangkapan</th>

                    <!-- Stock Akhir -->
                    <th class="table-header" rowspan="2">Stock<br>Akhir</th>

                    <!-- Dynamic Feed Columns -->
                    @if(isset($allFeedNames) && $allFeedNames->count() > 0)
                    <th class="table-header feed-highlight" colspan="{{ $allFeedNames->count() + 1 }}">Pemakaian Pakan
                        (Kg)</th>
                    @else
                    <th class="table-header feed-highlight" colspan="4">Pemakaian Pakan (Kg)</th>
                    @endif

                    <!-- Body Weight -->
                    <th class="table-header" colspan="2">Berat Badan (Gr)</th>

                    <!-- FCR Performance -->
                    <th class="table-header" colspan="3">FCR (Feed Conversion Ratio)</th>

                    <!-- IP Performance -->
                    <th class="table-header" colspan="3">IP (Index Performance)</th>

                    <!-- OVK/Supply Usage -->
                    <th class="table-header ovk-highlight hide-on-print" colspan="2">OVK/Supply</th>
                </tr>
                <tr style="text-align: center;">
                    <!-- Deplesi Sub-headers -->
                    <th class="table-header">Mati</th>
                    <th class="table-header">Afkir</th>
                    <th class="table-header">Total</th>
                    <th class="table-header">%</th>

                    <!-- Penangkapan Sub-headers -->
                    <th class="table-header">Ekor</th>
                    <th class="table-header">Kg</th>
                    <th class="table-header">Rata-rata<br>(Gr)</th>

                    <!-- Dynamic Feed Sub-headers -->
                    @if(isset($allFeedNames) && $allFeedNames->count() > 0)
                    @foreach($allFeedNames as $feedName)
                    <th class="table-header feed-highlight">{{ $feedName }}</th>
                    @endforeach
                    <th class="table-header feed-highlight">Total</th>
                    @else
                    <th class="table-header feed-highlight">SP 10</th>
                    <th class="table-header feed-highlight">SP 11</th>
                    <th class="table-header feed-highlight">SP 12</th>
                    <th class="table-header feed-highlight">Total</th>
                    @endif

                    <!-- Body Weight Sub-headers -->
                    <th class="table-header">Aktual</th>
                    <th class="table-header">Standar</th>

                    <!-- FCR Sub-headers -->
                    <th class="table-header">Aktual</th>
                    <th class="table-header">Standar</th>
                    <th class="table-header">Selisih</th>

                    <!-- IP Sub-headers -->
                    <th class="table-header">Aktual</th>
                    <th class="table-header">Standar</th>
                    <th class="table-header">Selisih</th>

                    <!-- OVK Sub-headers -->
                    <th class="table-header ovk-highlight hide-on-print">Jenis</th>
                    <th class="table-header ovk-highlight hide-on-print">Total (Kg)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($records ?? [] as $record)
                <tr class="border-b hover:bg-gray-50">
                    <td class="p-2">{{ \Carbon\Carbon::parse($record['tanggal'])->format('d/m/Y') }}</td>
                    <td class="p-2">{{ $record['umur'] ?? 0 }}</td>
                    <td class="p-2">{{ number_format($record['stock_awal'] ?? 0) }}</td>

                    <!-- Deplesi -->
                    <td class="p-2">{{ number_format($record['mati'] ?? 0) }}</td>
                    <td class="p-2">{{ number_format($record['afkir'] ?? 0) }}</td>
                    <td class="p-2">{{ number_format($record['total_deplesi'] ?? 0) }}</td>
                    <td class="p-2">{{ number_format($record['deplesi_percentage'] ?? 0, 2) }}%</td>

                    <!-- Penangkapan -->
                    <td class="p-2">{{ number_format($record['jual_ekor'] ?? 0) }}</td>
                    <td class="p-2">{{ number_format($record['jual_kg'] ?? 0, 1) }}</td>
                    <td class="p-2">{{ number_format($record['jual_rata'] ?? 0) }}</td>

                    <!-- Stock Akhir -->
                    <td class="p-2">{{ number_format($record['stock_akhir'] ?? 0) }}</td>

                    <!-- Dynamic Feed Usage -->
                    @if(isset($allFeedNames) && $allFeedNames->count() > 0)
                    @foreach($allFeedNames as $feedName)
                    <td class="p-2 feed-highlight">{{ number_format($record[$feedName] ?? 0, 1) }}</td>
                    @endforeach
                    <td class="p-2 feed-highlight"><strong>{{ number_format($record['feed_total'] ?? 0, 1) }}</strong>
                    </td>
                    @else
                    <td class="p-2 feed-highlight">{{ number_format($record['SP 10'] ?? 0, 1) }}</td>
                    <td class="p-2 feed-highlight">{{ number_format($record['SP 11'] ?? 0, 1) }}</td>
                    <td class="p-2 feed-highlight">{{ number_format($record['SP 12'] ?? 0, 1) }}</td>
                    <td class="p-2 feed-highlight"><strong>{{ number_format($record['feed_total'] ?? 0, 1) }}</strong>
                    </td>
                    @endif

                    <!-- Body Weight -->
                    <td
                        class="p-2 {{ (isset($record['bw_actual']) && isset($record['bw_standard']) && $record['bw_actual'] >= $record['bw_standard']) ? 'weight-above' : 'weight-below' }}">
                        {{ number_format($record['bw_actual'] ?? 0) }}
                    </td>
                    <td class="p-2">{{ number_format($record['bw_standard'] ?? 0) }}</td>

                    <!-- FCR Performance -->
                    <td
                        class="p-2 {{ (isset($record['fcr_actual']) && isset($record['fcr_standard']) && $record['fcr_actual'] <= $record['fcr_standard']) ? 'fcr-good' : 'fcr-poor' }}">
                        {{ number_format($record['fcr_actual'] ?? 0, 3) }}
                    </td>
                    <td class="p-2">{{ number_format($record['fcr_standard'] ?? 0, 3) }}</td>
                    <td class="p-2">
                        @if(isset($record['fcr_difference']))
                        @if($record['fcr_difference'] > 0)
                        <span style="color: red;">+{{ number_format($record['fcr_difference'], 3) }}</span>
                        @else
                        <span style="color: green;">{{ number_format($record['fcr_difference'], 3) }}</span>
                        @endif
                        @else
                        -
                        @endif
                    </td>

                    <!-- IP Performance -->
                    <td
                        class="p-2 {{ isset($record['ip_actual']) ? ($record['ip_actual'] >= 400 ? 'ip-excellent' : ($record['ip_actual'] >= 300 ? 'ip-good' : ($record['ip_actual'] >= 200 ? 'ip-average' : 'ip-poor'))) : '' }}">
                        {{ number_format($record['ip_actual'] ?? 0) }}
                    </td>
                    <td class="p-2">{{ number_format($record['ip_standard'] ?? 0) }}</td>
                    <td class="p-2">
                        @if(isset($record['ip_difference']))
                        @if($record['ip_difference'] > 0)
                        <span style="color: green;">+{{ number_format($record['ip_difference']) }}</span>
                        @else
                        <span style="color: red;">{{ number_format($record['ip_difference']) }}</span>
                        @endif
                        @else
                        -
                        @endif
                    </td>

                    <!-- OVK/Supply Usage -->
                    <td class="p-2 ovk-highlight hide-on-print">
                        @if(isset($record['ovk_details']) && count($record['ovk_details']) > 0)
                        @foreach($record['ovk_details'] as $ovk)
                        {{ $ovk['name'] }}<br>
                        @endforeach
                        @else
                        -
                        @endif
                    </td>
                    <td class="p-2 ovk-highlight hide-on-print">{{ number_format($record['ovk_total'] ?? 0, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Performance Summary -->
        @if(isset($records) && $records->count() > 0)
        @php
        $lastRecord = $records->last();
        $totalDays = $records->count();
        $avgFCR = $records->where('fcr_actual', '>', 0)->avg('fcr_actual');
        $avgIP = $records->where('ip_actual', '>', 0)->avg('ip_actual');
        $totalFeedConsumption = $records->sum('feed_total');
        $totalOVKUsage = $records->sum('ovk_total');
        $finalSurvivalRate = isset($lastRecord['stock_akhir']) && $currentLivestock->livestock->initial_quantity > 0
        ? ($lastRecord['stock_akhir'] / $currentLivestock->livestock->initial_quantity) * 100
        : 0;
        @endphp

        <div style="margin-top: 20px; padding: 15px; background-color: #f8f9fa; border-radius: 5px;">
            <h3 style="margin-top: 0;">RINGKASAN PERFORMA</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div>
                    <strong>Total Hari Pemeliharaan:</strong> {{ $totalDays }} hari<br>
                    <strong>Tingkat Kelangsungan Hidup:</strong> {{ number_format($finalSurvivalRate, 2) }}%<br>
                    <strong>FCR Rata-rata:</strong> {{ number_format($avgFCR ?? 0, 3) }}
                </div>
                <div>
                    <strong>IP Rata-rata:</strong> {{ number_format($avgIP ?? 0) }}<br>
                    <strong>Total Konsumsi Pakan:</strong> {{ number_format($totalFeedConsumption, 1) }} kg<br>
                    <strong>Total Penggunaan OVK:</strong> {{ number_format($totalOVKUsage, 2) }} kg
                </div>
            </div>
        </div>
        @endif

        <!-- Technical Notes -->
        <div style="margin-top: 20px; padding: 10px; background-color: #e9ecef; border-radius: 5px; font-size: 11px;">
            <strong>CATATAN TEKNIS:</strong><br>
            • FCR (Feed Conversion Ratio) = Total Pakan Dikonsumsi (kg) ÷ Total Berat Hidup (kg)<br>
            • IP (Index Performance) = (Tingkat Hidup % × Berat Rata-rata kg) ÷ (FCR × Umur hari) × 100<br>
            • Standar FCR dan IP disesuaikan dengan strain ayam (Ross/Cobb) berdasarkan penelitian industri<br>
            • Data pakan diambil secara dinamis dari sistem pencatatan harian<br>
            • OVK/Supply mencakup semua penggunaan obat, vitamin, dan suplemen
        </div>
    </div>

    <!-- Print Button -->
    <div style="text-align: center; margin: 20px; display: none;" class="hide-on-print">
        <button onclick="window.print()"
            style="background-color: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">
            Cetak Laporan
        </button>
    </div>
</body>

</html>
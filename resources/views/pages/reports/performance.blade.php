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
            @if(!empty($records))
            <tr>
                <td><strong>FARM</strong></td>
                <td>: {{ $records[0]['farm_name'] }}</td>
                <td><strong>DOC MASUK</strong></td>
                <td>: {{ number_format($records[0]['initial_quantity']) }} Ekor</td>
            </tr>
            <tr>
                <td><strong>KANDANG</strong></td>
                <td>: {{ $records[0]['coop_name'] }}</td>
                <td><strong>BONUS DOC</strong></td>
                <td>: {{ number_format($records[0]['bonus_doc'] ?? 0) }} Ekor</td>
            </tr>
            <tr>
                <td><strong>TGL. MASUK DOC</strong></td>
                <td>: {{ \Carbon\Carbon::parse($records[0]['start_date'])->translatedFormat('d F Y') }}</td>
                <td><strong>STRAIN</strong></td>
                <td>: {{ $records[0]['strain'] ?? '-' }}</td>
            </tr>
            <tr>
                <td><strong>PERIODE</strong></td>
                <td>: {{ $records[0]['livestock_name'] }}</td>
                <td><strong>BERAT RATA-RATA DOC</strong></td>
                <td>: {{ number_format($records[0]['initial_weight'] ?? 0) }} Gram</td>
            </tr>
            @else
            <tr>
                <td colspan="4">Tidak ada data untuk ditampilkan.</td>
            </tr>
            @endif
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
                @forelse($records as $record)
                @forelse($record['daily_records'] as $daily)
                <tr class="border-b hover:bg-gray-50">
                    <td class="p-2">{{ $daily['date']->format('d/m/Y') }}</td>
                    <td class="p-2">{{ $daily['age'] }}</td>
                    <td class="p-2">{{ number_format($daily['stock_awal']) }}</td>
                    <td class="p-2">{{ number_format($daily['mati']) }}</td>
                    <td class="p-2">{{ number_format($daily['afkir']) }}</td>
                    <td class="p-2">{{ number_format($daily['total_deplesi']) }}</td>
                    <td class="p-2">{{ number_format($daily['deplesi_percentage'], 2) }}%</td>
                    <td class="p-2">{{ number_format($daily['jual_ekor']) }}</td>
                    <td class="p-2">{{ number_format($daily['jual_kg'], 1) }}</td>
                    <td class="p-2">{{ number_format($daily['jual_rata']) }}</td>
                    <td class="p-2">{{ number_format($daily['stock_akhir']) }}</td>

                    {{-- Dynamic Feed Usage --}}
                    @if(isset($allFeedNames))
                    @foreach($allFeedNames as $feedName)
                    <td class="p-2 feed-highlight">{{ number_format($daily['feed_consumption_by_type'][$feedName] ?? 0,
                        1) }}</td>
                    @endforeach
                    @endif
                    <td class="p-2 feed-highlight"><strong>{{ number_format($daily['feed_total'], 1) }}</strong></td>

                    <td class="p-2">{{ number_format($daily['bw_actual']) }}</td>
                    <td class="p-2">{{-- bw_standard needed --}}</td>
                    <td class="p-2">{{ number_format($daily['fcr_actual'], 3) }}</td>
                    <td class="p-2">{{-- fcr_standard needed --}}</td>
                    <td class="p-2">{{-- fcr_difference needed --}}</td>
                    <td class="p-2">{{ number_format($daily['ip_actual']) }}</td>
                    <td class="p-2">{{-- ip_standard needed --}}</td>
                    <td class="p-2">{{-- ip_difference needed --}}</td>
                    <td class="p-2 ovk-highlight hide-on-print">{{-- ovk details needed --}}</td>
                    <td class="p-2 ovk-highlight hide-on-print">{{-- ovk total needed --}}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="100%">Tidak ada data harian.</td>
                </tr>
                @endforelse
                @empty
                <tr>
                    <td colspan="100%">Tidak ada laporan.</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Performance Summary -->
        @if(isset($records) && count($records) > 0)
        @php
        $lastRecord = $records[0]; // Summary is now in the first record
        $totalDays = count($lastRecord['daily_records']);
        $avgFCR = $lastRecord['fcr_actual'] ?? 0;
        $avgIP = $lastRecord['ip_actual'] ?? 0;

        // Calculate totals from daily records
        $totalFeedConsumption = 0;
        $totalOVKUsage = 0; // Assuming this will be added to daily records later
        foreach($lastRecord['daily_records'] as $daily) {
        $totalFeedConsumption += $daily['feed_total'];
        // $totalOVKUsage += $daily['ovk_total'] ?? 0;
        }

        $finalDailyRecord = end($lastRecord['daily_records']);
        $finalSurvivalRate = ($lastRecord['initial_quantity'] > 0) ? ($finalDailyRecord['stock_akhir'] /
        $lastRecord['initial_quantity']) * 100 : 0;
        @endphp
        <div style="margin-top: 20px; padding: 15px; background-color: #f8f9fa; border-radius: 5px;">
            <h3 style="margin-top: 0;">RINGKASAN PERFORMA</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div>
                    <strong>Total Hari Pemeliharaan:</strong> {{ $totalDays }} hari<br>
                    <strong>Tingkat Kelangsungan Hidup:</strong> {{ number_format($finalSurvivalRate, 2) }}%<br>
                    <strong>FCR Rata-rata:</strong> {{ number_format($avgFCR, 3) }}
                </div>
                <div>
                    <strong>IP Rata-rata:</strong> {{ number_format($avgIP) }}<br>
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
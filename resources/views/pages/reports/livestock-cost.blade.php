<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Biaya Ayam</title>
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

        .footer-signatures {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
        }

        .breakdown-table {
            margin-top: 10px;
            width: 100%;
        }

        .breakdown-table th {
            background-color: #f0f0f0;
        }

        .breakdown-section {
            margin-top: 20px;
            page-break-inside: avoid;
        }

        .breakdown-section h4 {
            margin: 10px 0;
            color: #333;
        }

        .supply-highlight {
            background-color: #f0f8ff;
        }

        .feed-highlight {
            background-color: #f0fff0;
        }

        .deplesi-highlight {
            background-color: #fff5ee;
        }

        .initial-purchase-highlight {
            background-color: #e8f4fd;
        }
    </style>
</head>

<body>
    <div class="header">
        LAPORAN BIAYA AYAM<br>
        FARM : {{ $farm }}<br>
        TANGGAL : {{ $tanggal }}
    </div>

    <table>
        <thead>
            <tr class="header-row">
                <th>KANDANG</th>
                <th>BATCH</th>
                <th>UMUR (HARI)</th>
                <th>TOTAL BIAYA HARIAN</th>
                <th>BIAYA PER AYAM</th>
                <th>BIAYA KUMULATIF PER AYAM</th>
            </tr>
        </thead>
        <tbody>
            @foreach($costs as $cost)
            <tr>
                <td>{{ $cost['kandang'] }}</td>
                <td>{{ $cost['livestock'] }}</td>
                <td>{{ $cost['umur'] }}</td>
                <td class="text-right">{{ formatNumber($cost['total_cost'], 2) }}</td>
                <td class="text-right">{{ formatNumber($cost['daily_cost_per_ayam'] ?? 0, 2) }}</td>
                <td class="text-right">{{ formatNumber($cost['cost_per_ayam'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="header-row">
                <td colspan="3" class="text-right">TOTAL:</td>
                <td class="text-right">{{ formatNumber($totals['total_cost'], 2) }}</td>
                <td class="text-right">{{ formatNumber($totals['daily_cost_per_ayam'] ?? 0, 2) }}</td>
                <td class="text-right">{{ formatNumber($totals['total_cost_per_ayam'], 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <!-- Cost Breakdown Sections -->
    @foreach($costs as $cost)
    @if(isset($cost['breakdown']) && is_array($cost['breakdown']))
    <div class="breakdown-section">
        <h4>Detail Biaya - {{ $cost['kandang'] }} ({{ $cost['livestock'] }})</h4>
        <table class="breakdown-table">
            <thead>
                <tr class="header-row">
                    <th>KATEGORI</th>
                    @if($report_type === 'detail')
                    <th>JUMLAH</th>
                    <th>SATUAN</th>
                    <th>HARGA SATUAN</th>
                    <th>TANGGAL</th>
                    @endif
                    <th>SUBTOTAL</th>
                </tr>
            </thead>
            <tbody>
                @foreach($cost['breakdown'] as $item)
                <tr @if(isset($item['is_initial_purchase']) && $item['is_initial_purchase'])
                    class="initial-purchase-highlight" @elseif(str_contains(strtolower($item['kategori'] ?? ''
                    ), 'pakan' ) || str_contains(strtolower($item['kategori'] ?? '' ), 'feed' )) class="feed-highlight"
                    @elseif(str_contains(strtolower($item['kategori'] ?? '' ), 'supply' ) ||
                    str_contains(strtolower($item['kategori'] ?? '' ), 'ovk' ) ||
                    str_contains(strtolower($item['kategori'] ?? '' ), 'biocid' ) ||
                    str_contains(strtolower($item['kategori'] ?? '' ), 'cevamune' )) class="supply-highlight"
                    @elseif(str_contains(strtolower($item['kategori'] ?? '' ), 'deplesi' )) class="deplesi-highlight"
                    @endif>
                    <td class="text-left">
                        {{ $item['kategori'] ?? '-' }}
                        @if(isset($item['is_initial_purchase']) && $item['is_initial_purchase'])
                        <small style="color: #0066cc;">(Harga Pembelian Awal)</small>
                        @endif
                        @if(isset($item['calculation_note']))
                        <br><small style="color: #666;">{{ $item['calculation_note'] }}</small>
                        @endif
                    </td>
                    @if($report_type === 'detail')
                    <td class="text-right">{{ formatNumber($item['jumlah'] ?? 0, 2) }}</td>
                    <td class="text-center">{{ $item['satuan'] ?? '-' }}</td>
                    <td class="text-right">{{ formatNumber($item['harga_satuan'] ?? 0, 2) }}</td>
                    <td class="text-center">{{ $item['tanggal'] ?? '-' }}</td>
                    @endif
                    <td class="text-right">{{ formatNumber($item['subtotal'] ?? 0, 2) }}</td>
                </tr>
                @endforeach

                @if($report_type === 'detail')
                <tr class="header-row">
                    <td colspan="{{ $report_type === 'detail' ? '5' : '1' }}" class="text-right">Total Biaya Hari
                        Sebelumnya:</td>
                    <td class="text-right">{{ formatNumber($prev_cost_data['total_added_cost'] ?? 0, 2) }}</td>
                </tr>
                <tr class="header-row">
                    <td colspan="{{ $report_type === 'detail' ? '5' : '1' }}" class="text-right">Total Biaya Kumulatif
                        Sampai Hari Ini:</td>
                    <td class="text-right">{{ formatNumber($total_cumulative_cost_calculated ?? 0, 2) }}</td>
                </tr>
                @if(isset($initial_purchase_data) && $initial_purchase_data['found'])
                <tr class="initial-purchase-highlight">
                    <td colspan="{{ $report_type === 'detail' ? '5' : '1' }}" class="text-right"><strong>Harga Awal DOC
                            ({{ $initial_purchase_data['date'] }}):</strong></td>
                    <td class="text-right"><strong>{{ formatNumber($initial_purchase_data['total_cost'], 2) }}</strong>
                    </td>
                </tr>
                @endif
                @endif
            </tbody>
        </table>

        <!-- Summary Information for Detail Report -->
        @if($report_type === 'detail' && isset($summary_data))
        <div style="margin-top: 15px; padding: 10px; background-color: #f9f9f9; border: 1px solid #ddd;">
            <h5 style="margin: 0 0 10px 0;">Ringkasan Biaya Harian:</h5>
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; font-size: 10pt;">
                <div><strong>Pakan:</strong> {{ formatNumber($summary_data['daily_feed_cost'] ?? 0, 2) }}</div>
                <div><strong>Supply:</strong> {{ formatNumber(($summary_data['daily_ovk_cost'] ?? 0) +
                    ($summary_data['daily_supply_usage_cost'] ?? ($summary_data['supply_usage'] ?? 0)), 2) }}</div>
                <div><strong>Deplesi:</strong> {{ formatNumber($summary_data['daily_deplesi_cost'] ?? 0, 2) }}</div>
                <div><strong>Total Harian:</strong> {{ formatNumber($summary_data['total_daily_added_cost'] ?? 0, 2) }}
                </div>
                <div><strong>Per Ayam:</strong> {{ formatNumber($summary_data['daily_added_cost_per_chicken'] ?? 0, 2)
                    }}</div>
                <div></div>
            </div>
        </div>
        @endif
    </div>
    @else
    <div class="breakdown-section">
        <h4>Detail Biaya - {{ $cost['kandang'] }} ({{ $cost['livestock'] }})</h4>
        <table class="breakdown-table">
            <tbody>
                <tr>
                    <td colspan="5" class="text-center">Tidak ada detail biaya.</td>
                </tr>
            </tbody>
        </table>
    </div>
    @endif
    @endforeach

    <div class="footer-signatures">
        {{-- <div>
            Diketahui oleh,<br><br><br>
            ( {{ $diketahui ?? 'RIA NARSO' }} )
        </div>
        <div>
            Dibuat oleh,<br><br><br>
            ( {{ $dibuat ?? 'HENDRA' }} )
        </div> --}}
    </div>
</body>

</html>
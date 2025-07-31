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

        th,
        td {
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

        tfoot th,
        tfoot td {
            font-weight: bold;
        }

        /* Warning and Error Messages */
        .message-container {
            margin: 20px 0;
            padding: 15px;
            border-radius: 5px;
            font-size: 11pt;
            text-align: center;
        }

        .warning-message {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
        }

        .error-message {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .info-message {
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }

        .no-data-message {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            color: #6c757d;
            font-style: italic;
        }

        @media print {
            @page {
                margin: 0.5cm;
            }

            body {
                width: 210mm;
                height: 297mm;
                margin: 0;
                padding: 0.5cm;
                zoom: 98%;
            }

            .page-break {
                page-break-before: always;
            }
        }
    </style>
</head>

<body>
    <h1>Laporan Penjualan</h1>

    <!-- Display Warning/Error Messages -->
    @if(isset($warning_message))
    <div class="message-container warning-message">
        <strong>⚠️ Peringatan:</strong> {{ $warning_message }}
    </div>
    @endif

    @if(isset($error_message))
    <div class="message-container error-message">
        <strong>❌ Error:</strong> {{ $error_message }}
    </div>
    @endif

    <!-- Display Info Message for No Data -->
    @if(isset($has_data) && !$has_data && !isset($warning_message) && !isset($error_message))
    <div class="message-container no-data-message">
        <strong>ℹ️ Informasi:</strong> Belum ada data penjualan untuk periode yang dipilih.
    </div>
    @endif

    <!-- Display Draft Data Available Info -->
    @if(isset($draft_data_available) && $draft_data_available && isset($include_draft_sales) && !$include_draft_sales)
    <div class="message-container info-message">
        <strong>💡 Saran:</strong> Terdapat data draft yang dapat ditampilkan. Aktifkan opsi "Include Draft Sales" untuk
        melihat data tersebut.
    </div>
    @endif

    <table class="info-table">
        <tbody>
            <tr>
                <td>
                    Nama Farm
                </td>
                <td>
                    : {{ $farm ?? 'N/A' }}
                </td>
            </tr>
            <tr>
                <td>
                    Nama Kandang
                </td>
                <td>
                    : {{ $coop ?? $kandang ?? 'N/A' }}
                </td>
            </tr>
            <tr>
                <td>
                    Periode Pemeliharaan
                </td>
                <td>
                    : {{ $periode ?? 'N/A' }}
                </td>
            </tr>
            @if(isset($summary) && $summary['total_sales'] > 0)
            <tr>
                <td>
                    Total Transaksi
                </td>
                <td>
                    : {{ number_format($summary['total_sales'], 0, ',', '.') }} transaksi
                </td>
            </tr>
            <tr>
                <td>
                    Total Penjualan
                </td>
                <td>
                    : {{ number_format($summary['total_amount'], 0, ',', '.') }} (Rp)
                </td>
            </tr>
            @endif

            @if(isset($include_temp_sales) && $include_temp_sales && isset($summary) &&
            isset($summary['total_temp_sales']) && $summary['total_temp_sales'] > 0)
            <tr>
                <td>
                    Total Transaksi Temporer
                </td>
                <td>
                    : {{ number_format($summary['total_temp_sales'], 0, ',', '.') }} transaksi
                </td>
            </tr>
            @endif

            @if(isset($include_draft_sales) && $include_draft_sales)
            <tr>
                <td>
                    <span class="text-warning">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        Include Draft Sales
                    </span>
                </td>
                <td>
                    : <span class="text-warning">Ya</span>
                </td>
            </tr>
            @endif
        </tbody>
    </table>

    @if(isset($has_data) && $has_data && ($penjualanData->isNotEmpty() || (isset($tempSalesData) &&
    $tempSalesData->isNotEmpty())))
    <!-- Main Sales Data -->
    @if($penjualanData->isNotEmpty())
    <h4 class="mt-4 mb-3">
        <i class="fas fa-chart-line text-primary me-2"></i>
        Data Penjualan Final
    </h4>
    <table id="tableReport" class="mb-4">
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
            @foreach($penjualanData as $data)
            <tr>
                @if(isset($data->kelompokTernak))
                <td>{{ $data->kelompokTernak->start_date->format('d-M-y') }}</td>
                @elseif(isset($data->date))
                <td>{{ \Carbon\Carbon::parse($data->date)->format('d-M-y') }}</td>
                @else
                <td>-</td>
                @endif

                @if(isset($data->tanggal))
                <td>{{ $data->tanggal->format('d-M-y') }}</td>
                @elseif(isset($data->date))
                <td>{{ \Carbon\Carbon::parse($data->date)->format('d-M-y') }}</td>
                @else
                <td>-</td>
                @endif

                <td>{{ $data->faktur ?? $data->invoice_number ?? '-' }}</td>

                @if(isset($data->detail) && isset($data->detail->rekanan))
                <td style="text-align: left;">{{ $data->detail->rekanan->nama }}</td>
                @elseif(isset($data->customer))
                <td style="text-align: left;">{{ $data->customer->name ?? $data->customer_name ?? '-' }}</td>
                @else
                <td style="text-align: left;">-</td>
                @endif

                <td style="text-align: right;">{{ number_format($data->jumlah ?? $data->total_quantity ?? 0, 0, ',',
                    '.') }}</td>

                @if(isset($data->detail))
                <td style="text-align: right;">{{ number_format($data->detail->berat ?? 0, 1, ',', '.') }}</td>
                <td>{{ number_format(round(($data->detail->berat ?? 0) / ($data->jumlah ?? 1), 2), 2, ',', '.') }}</td>
                <td>{{ number_format($data->harga ?? 0, 0, ',', '.') }}</td>
                <td style="text-align: right;">{{ number_format(($data->detail->harga_jual ?? 0) * ($data->detail->berat
                    ?? 0), 0, ',', '.') }}</td>
                <td>{{ $data->detail->umur ?? '-' }}</td>
                <td style="text-align: right;">{{ number_format(($data->detail->umur ?? 0) * ($data->jumlah ?? 0), 0,
                    ',', '.') }}</td>
                @else
                <td style="text-align: right;">{{ number_format($data->total_weight ?? 0, 1, ',', '.') }}</td>
                <td>{{ number_format(round(($data->total_weight ?? 0) / ($data->total_quantity ?? 1), 2), 2, ',', '.')
                    }}</td>
                <td>{{ number_format($data->total_amount ?? 0, 0, ',', '.') }}</td>
                <td style="text-align: right;">{{ number_format($data->total_amount ?? 0, 0, ',', '.') }}</td>
                <td>-</td>
                <td style="text-align: right;">-</td>
                @endif
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <!-- Temporary Sales Data -->
    @if(isset($tempSalesData) && $tempSalesData->isNotEmpty())
    <h4 class="mt-4 mb-3">
        <i class="fas fa-clock text-warning me-2"></i>
        Data Penjualan Temporer (RecordingSale)
        <small class="text-muted">- Data dari sistem recording yang belum final</small>
    </h4>
    <table id="tableTempReport" class="mb-4">
        <thead>
            <tr style="background-color: #fff3cd;">
                <th>Tgl Penjualan</th>
                <th>Status</th>
                <th>Batch</th>
                <th>Jumlah</th>
                <th>Berat (Kg)</th>
                <th>ABW (Kg)</th>
                {{-- <th>Harga/Kg (Rp)</th>
                <th>Total (Rp)</th>
                <th>Tipe</th> --}}
            </tr>
        </thead>
        <tbody>
            @foreach($tempSalesData as $tempData)
            @if($tempData->is_header && $tempData->items && $tempData->items->isNotEmpty())
            <!-- Header record with multiple items -->
            @foreach($tempData->items as $item)
            <tr>
                <td>{{ \Carbon\Carbon::parse($tempData->date)->format('d-M-y') }}</td>
                <td>
                    <span
                        class="badge bg-{{ $tempData->status === 'confirmed' ? 'success' : ($tempData->status === 'pending' ? 'warning' : 'secondary') }}">
                        {{ ucfirst($tempData->status) }}
                    </span>
                </td>
                <td>{{ $item->batch->name ?? 'Unknown Batch' }}</td>
                <td style="text-align: right;">{{ number_format($item->quantity ?? 0, 0, ',', '.') }}</td>
                <td style="text-align: right;">{{ number_format($item->weight ?? 0, 1, ',', '.') }}</td>
                <td>{{ ($item->quantity ?? 0) > 0 ? number_format(round(($item->weight ?? 0) / ($item->quantity ?? 1),
                    2), 2, ',', '.') : '0.00' }}</td>
                {{-- <td>{{ number_format($item->price_per_unit ?? 0, 0, ',', '.') }}</td>
                <td style="text-align: right;">{{ number_format($item->amount ?? 0, 0, ',', '.') }}</td>
                <td>
                    <span class="badge bg-info">Header</span>
                </td> --}}
            </tr>
            @endforeach
            @else
            <!-- Single record (legacy or non-header) -->
            <tr>
                <td>{{ \Carbon\Carbon::parse($tempData->date)->format('d-M-y') }}</td>
                <td>
                    <span
                        class="badge bg-{{ $tempData->status === 'confirmed' ? 'success' : ($tempData->status === 'pending' ? 'warning' : 'secondary') }}">
                        {{ ucfirst($tempData->status) }}
                    </span>
                </td>
                <td>{{ $tempData->livestockBatch->name ?? 'Main Batch' }}</td>
                <td style="text-align: right;">{{ number_format($tempData->quantity ?? 0, 0, ',', '.') }}</td>
                <td style="text-align: right;">{{ number_format($tempData->weight ?? 0, 1, ',', '.') }}</td>
                <td>{{ ($tempData->quantity ?? 0) > 0 ? number_format(round(($tempData->weight ?? 0) /
                    ($tempData->quantity ?? 1), 2), 2, ',', '.') : '0.00' }}
                </td>
                <td>{{ number_format($tempData->price ?? 0, 0, ',', '.') }}</td>
                <td style="text-align: right;">{{ number_format(($tempData->quantity ?? 0) * ($tempData->price ?? 0), 0,
                    ',', '.') }}</td>
                <td>
                    <span class="badge bg-secondary">Single</span>
                </td>
            </tr>
            @endif
            @endforeach
        </tbody>
    </table>
    @endif
    {{-- <tfoot>
        <tr>
            <th colspan="4">Total</th>
            <th style="text-align: right; font-weight: bold;">{{ number_format($data->sum('total_quantity'), 0,
                ',', '.') }}</th>
            <th style="text-align: right; font-weight: bold;">{{ number_format($data->sum('total_weight'),
                1, ',', '.') }}</th>
            <th>
                {{ $data->sum('total_quantity') > 0 ? number_format($data->sum('total_weight') /
                $data->sum('total_quantity'), 2, ',', '.') : '0.00' }}
            </th>
            <th></th>
            <th style="text-align: right; font-weight: bold;">{{ number_format($data->sum('total_amount'), 0, ',', '.')
                }}</th>
            <th></th>
            <th style="text-align: right; font-weight: bold;">{{ number_format($data->sum('total_quantity'), 0, ',',
                '.') }}</th>
        </tr>
        <tr>
            <td colspan="8" style="text-align: center; font-weight: bold;">Rata - Rata Umur Panen</td>
            <td style="text-align: right; font-weight: bold;">{{ $data->sum('total_quantity') > 0 ?
                number_format($data->sum('total_quantity') / $data->count(), 2, ',', '.') : '0.00' }}</td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td colspan="8" style="text-align: center; font-weight: bold;">Rata - Rata Harga Jual (Rp)</td>
            <td style="text-align: right; font-weight: bold;">{{ $data->sum('total_weight') > 0 ?
                number_format($data->sum('total_amount') / $data->sum('total_weight'), 0, ',', '.') : '0.00' }}</td>
            <td></td>
            <td></td>
        </tr>
    </tfoot> --}}
    </table>
    @else
    <div class="message-container no-data-message">
        <strong>📊 Tidak Ada Data:</strong> Belum ada data penjualan yang dapat ditampilkan untuk periode ini.
        <br><br>
        <small>Silakan cek kembali setelah ada transaksi penjualan atau hubungi administrator jika ada masalah.</small>
    </div>
    @endif
</body>

</html>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Export Pembelian Feed</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 6px;
            text-align: left;
        }

        th {
            background-color: #f8f8f8;
            font-weight: bold;
        }

        .batch-header {
            background-color: #eaeaea;
            font-weight: bold;
        }

        h2 {
            text-align: center;
            margin-bottom: 40px;
        }

        .summary {
            margin-top: 5px;
            margin-bottom: 10px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h2>Export Data Pembelian Feed</h2>

    @php
        $grouped = $purchases->groupBy('feed_purchase_batch_id');
    @endphp

    @foreach ($grouped as $batchId => $items)
        @php
            $batch = $items->first()->batch;
        @endphp

        <div class="summary">
            No. Faktur: {{ $batch->invoice_number ?? '-' }} <br>
            Tanggal Pembelian: {{ $batch->date?->format('d M Y') ?? '-' }} <br>
            Vendor: {{ $batch->vendor->nama ?? '-' }}
        </div>

        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Feed</th>
                    <th>Kandang</th>
                    <th>Jumlah (kg)</th>
                    <th>Harga per Unit</th>
                    <th>Total Harga</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $i => $purchase)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $purchase->feedItem->name ?? '-' }}</td>
                        <td>{{ $purchase->livestok->name ?? '-' }}</td>
                        <td>{{ $purchase->quantity }}</td>
                        <td>Rp {{ number_format($purchase->price_per_unit, 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($purchase->quantity * $purchase->price_per_unit, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach

</body>
</html>

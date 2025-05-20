<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Laporan Batch Worker</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
        }

        .header p {
            margin: 5px 0;
            color: #666;
        }

        .filters {
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }

        .filters p {
            margin: 5px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f8f9fa;
        }

        .status-active {
            color: #28a745;
        }

        .status-completed {
            color: #007bff;
        }

        .status-terminated {
            color: #dc3545;
        }

        .summary {
            margin-top: 20px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }

        .summary h3 {
            margin-top: 0;
        }

        .summary p {
            margin: 5px 0;
        }

        .status-summary {
            margin-top: 10px;
            padding: 10px;
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .status-summary h4 {
            margin: 0 0 10px 0;
            color: #333;
        }

        .footer {
            margin-top: 30px;
            text-align: right;
        }

        .footer p {
            margin: 5px 0;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Laporan Batch Worker</h1>
        <p>Tanggal: {{ now()->format('d F Y') }}</p>
    </div>

    <div class="filters">
        <h3>Filter Laporan</h3>
        <p><strong>Periode:</strong> {{ $startDate }} s.d. {{ $endDate }}</p>
        <p><strong>Farm:</strong> {{ $farm }}</p>
        <p><strong>Worker:</strong> {{ $worker }}</p>
        <p><strong>Status:</strong> {{ $status }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Batch</th>
                <th>Worker</th>
                <th>Farm</th>
                <th>Tanggal Mulai</th>
                <th>Tanggal Selesai</th>
                <th>Status</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item->batch->name }}</td>
                <td>{{ $item->worker->name }}</td>
                <td>{{ $item->farm->name }}</td>
                <td>{{ $item->start_date->format('d/m/Y') }}</td>
                <td>{{ $item->end_date ? $item->end_date->format('d/m/Y') : '-' }}</td>
                <td class="status-{{ $item->status }}">{{ ucfirst($item->status) }}</td>
                <td>{{ $item->notes ?: '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary">
        <h3>Ringkasan</h3>
        <p><strong>Total Penugasan:</strong> {{ $summary['total_assignments'] }}</p>
        <p><strong>Jumlah Worker:</strong> {{ $summary['unique_workers'] }}</p>
        <p><strong>Jumlah Batch:</strong> {{ $summary['unique_batches'] }}</p>
        <p><strong>Jumlah Farm:</strong> {{ $summary['unique_farms'] }}</p>

        @if($statusSummary->count() > 0)
        <div class="status-summary">
            <h4>Ringkasan per Status</h4>
            @foreach($statusSummary as $status => $details)
            <p><strong>{{ ucfirst($status) }}:</strong></p>
            <ul>
                <li>Jumlah Penugasan: {{ $details['count'] }}</li>
                <li>Jumlah Worker: {{ $details['workers'] }}</li>
                <li>Jumlah Batch: {{ $details['batches'] }}</li>
                <li>Jumlah Farm: {{ $details['farms'] }}</li>
            </ul>
            @endforeach
        </div>
        @endif
    </div>

    <div class="footer">
        <p>Diketahui oleh,</p>
        <br><br><br>
        <p>( {{ $diketahui }} )</p>
        <br><br>
        <p>Dibuat oleh,</p>
        <br><br><br>
        <p>( {{ $dibuat }} )</p>
    </div>
</body>

</html>
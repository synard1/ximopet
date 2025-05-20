<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Batch Worker Report</title>
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
    </style>
</head>

<body>
    <div class="header">
        <h1>Batch Worker Report</h1>
        <p>Generated on: {{ now()->format('Y-m-d H:i:s') }}</p>
    </div>

    <div class="filters">
        <h3>Report Filters</h3>
        <p><strong>Date Range:</strong> {{ $startDate }} to {{ $endDate }}</p>
        <p><strong>Farm:</strong> {{ $farm }}</p>
        <p><strong>Worker:</strong> {{ $worker }}</p>
        <p><strong>Status:</strong> {{ $status }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Batch</th>
                <th>Worker</th>
                <th>Farm</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Status</th>
                <th>Notes</th>
                <th>Created By</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $item)
            <tr>
                <td>{{ $item->batch->name }}</td>
                <td>{{ $item->worker->name }}</td>
                <td>{{ $item->farm->name }}</td>
                <td>{{ $item->start_date->format('Y-m-d') }}</td>
                <td>{{ $item->end_date ? $item->end_date->format('Y-m-d') : '-' }}</td>
                <td>{{ ucfirst($item->status) }}</td>
                <td>{{ $item->notes ?: '-' }}</td>
                <td>{{ $item->creator->name }}</td>
                <td>{{ $item->created_at->format('Y-m-d H:i:s') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary">
        <h3>Summary</h3>
        <p><strong>Total Assignments:</strong> {{ $data->count() }}</p>
        <p><strong>Unique Workers:</strong> {{ $data->unique('worker_id')->count() }}</p>
        <p><strong>Unique Batches:</strong> {{ $data->unique('livestock_id')->count() }}</p>
        <p><strong>Unique Farms:</strong> {{ $data->unique('farm_id')->count() }}</p>
    </div>
</body>

</html>
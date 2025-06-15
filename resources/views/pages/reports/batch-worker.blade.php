<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Penugasan Pekerja</title>
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

        #tableHeader {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 12px;
            text-align: left;
            table-layout: auto;
        }

        #tableHeader th,
        #tableHeader td {
            padding: 6px;
            border: 0px solid #ddd;
            white-space: nowrap;
            font-weight: bold;
        }

        #tableData {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 12px;
            text-align: left;
            table-layout: auto;
        }

        #tableData th,
        #tableData td {
            padding: 6px;
            border: 1px solid #ddd;
            white-space: nowrap;
        }

        #tableData th {
            background-color: #f0f0f0;
            font-weight: bold;
        }

        .table-header {
            @apply px-4 py-2 text-left border-b border-r border-gray-300 text-sm font-medium;
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

            table {
                font-size: 10px;
                margin: 10px 0;
                width: 100%;
                table-layout: auto;
            }

            th,
            td {
                padding: 4px;
            }

            header {
                font-size: 14px;
                padding: 10px;
            }
        }
    </style>
</head>

<body>
    <header>
        LAPORAN PENUGASAN PEKERJA
    </header>

    <div class="content">
        <table id='tableHeader' style="width: 100%; margin-bottom: 20px; font-size: 14px; border-collapse: collapse;">
            <tr>
                <td><strong>FARM</strong></td>
                <td>: {{ $farm->nama }}</td>
                <td><strong>PERIODE</strong></td>
                <td>: {{ $startDate->format('d F Y') }} - {{ $endDate->format('d F Y') }}</td>
            </tr>
        </table>

        <table id='tableData' class="table-auto w-full border-collapse border border-gray-300 mt-4 text-sm">
            <thead class="bg-gray-100 text-gray-700">
                <tr>
                    <th class="table-header">No</th>
                    <th class="table-header">Nama Pekerja</th>
                    <th class="table-header">Kandang</th>
                    <th class="table-header">Periode</th>
                    <th class="table-header">Peran</th>
                    <th class="table-header">Status</th>
                    <th class="table-header">Catatan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($batchWorkers as $index => $worker)
                <tr class="border-b hover:bg-gray-50">
                    <td class="p-2">{{ $index + 1 }}</td>
                    <td class="p-2">{{ $worker->worker->name }}</td>
                    <td class="p-2">{{ $worker->livestock->kandang->nama }}</td>
                    <td class="p-2">
                        {{ $worker->start_date->format('d/m/Y') }} -
                        {{ $worker->end_date ? $worker->end_date->format('d/m/Y') : 'Sekarang' }}
                    </td>
                    <td class="p-2">{{ $worker->role ?? '-' }}</td>
                    <td class="p-2">{{ $worker->status }}</td>
                    <td class="p-2">{{ $worker->notes ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>

</html>
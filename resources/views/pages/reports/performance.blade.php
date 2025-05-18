<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Performa Ayam</title>
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
            overflow-x: auto; /* Add horizontal scroll if needed */
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
            table-layout: auto; /* Allow table to adjust to content */
        }
		  
		#tableHeader th, #tableHeader td {
            padding: 6px;
            border: 0px solid #ddd;
            white-space: nowrap; /* Prevent text wrapping in cells */
            font-weight: bold;
        }

        #tableData {
			width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 12px;
            text-align: left;
            table-layout: auto; /* Allow table to adjust to content */
        }
		  
		#tableData th, #tableData td {
            padding: 6px;
            border: 1px solid #ddd;
            white-space: nowrap; /* Prevent text wrapping in cells */

        }

        #tableData th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
		
        /* table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 12px;
            text-align: left;
            table-layout: auto;
        }
        th, td {
            padding: 6px;
            border: 1px solid #ddd;
            white-space: nowrap;
        }
		
        th {
            background-color: #f0f0f0;
            font-weight: bold;
        } */

        .table-header {
            @apply px-4 py-2 text-left border-b border-r border-gray-300 text-sm font-medium;
        }

        @media print {
            /* A4 Paper size settings */
            @page {
                size: A4 landscape; /* Change to landscape for wider tables */
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
                overflow-x: visible; /* Remove scroll in print */
            }

            table {
                font-size: 10px;
                margin: 10px 0;
                width: 100%;
                table-layout: auto;
            }

            th, td {
                padding: 4px;
            }

            /* Reduce the height and spacing to make it fit on a single page */
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
        LAPORAN PERFORMA AYAM BROILER
    </header>

    <div class="content">
        
        <table id='tableHeader' style="width: 100%; margin-bottom: 20px; font-size: 14px; border-collapse: collapse;">
            <tr>
                <td><strong>FARM</strong></td>
                <td>: {{ $currentLivestock->livestock->farm->name }}</td>
                <td><strong>DOC MASUK</strong></td>
                <td>: {{ $currentLivestock->livestock->populasi_awal }} Ekor</td>
            </tr>
            <tr>
                <td><strong>KANDANG</strong></td>
                <td>: {{ $currentLivestock->livestock->kandang->nama }}</td>
                <td><strong>BONUS DOC</strong></td>
                <td>: {{ $currentLivestock->livestock->bonus_doc ?? 0 }} Ekor</td>
            </tr>
            <tr>
                <td><strong>TGL. MASUK DOC</strong></td>
                <td>: {{ $currentLivestock->livestock->start_date->translatedFormat('d F Y') }}</td>
                <td><strong>STRAIN</strong></td>
                <td>: {{ $currentLivestock->livestock->standarBobot->breed ?? '-' }}</td>
            </tr>
            <tr>
                <td><strong>PRIODE</strong></td>
                <td>: {{ $currentLivestock->livestock->name }}</td>
                <td><strong>BERAT RATA2 DOC</strong></td>
                <td>: {{ $currentLivestock->livestock->berat_awal ?? 0 }} Gram</td>
            </tr>
        </table>

        <table id='tableData' class="table-auto w-full border-collapse border border-gray-300 mt-4 text-sm">
            <thead class="bg-gray-100 text-gray-700">
                <tr style="text-align: center;">
                    <th class="table-header" rowspan="2">Tanggal</th>
                    <th class="table-header" rowspan="2">Umur</th>
                    <th class="table-header" rowspan="2">Stock<br>Awal</th>
                    
                    <!-- Deplesi Column Group -->
                    <th class="table-header text-center" style="text-align: center;" colspan="4">Deplesi</th>
                    
                    <!-- Penangkapan Column Group -->
                    <th class="table-header text-center" style="text-align: center;" colspan="3">Penangkapan</th>
                    
                    <!-- Stock Akhir -->
                    <th class="table-header" rowspan="2">Stock<br>Akhir</th>
                    
                    <!-- Pemakaian Pakan Column Group -->
                    <th class="table-header text-center" style="text-align: center;" colspan="5">Pemakaian Pakan</th>
    
                    <th class="table-header" rowspan="2">BW Akt<br>(Gr)</th>
                    <th class="table-header" rowspan="2">BW Std<br>(Gr)</th>
                    
                    <!-- FCR Column -->
                    <th class="table-header text-center" style="text-align: center;" colspan="3">FCR</th>
                    
                    <!-- IP Column -->
                    <th class="table-header text-center" style="text-align: center;" colspan="2">IP</th>

                    <th class="table-header text-center hide-on-print" style="text-align: center;" colspan="2">OVK</th>
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
                    <th class="table-header">Rata<br>Rata</th>

                    <!-- Pemakaian Pakan Sub-headers -->
                    <th class="table-header">SP 10</th>
                    <th class="table-header">SP 11</th>
                    <th class="table-header">SP 12</th>
                    
    
                    <!-- FCR Sub-headers -->
                    <th class="table-header">Akt</th>
                    <th class="table-header">Std</th>
                    <th class="table-header">Slsh</th>
                    <th class="table-header">Slsh</th>
                    <th class="table-header">Slsh</th>
                    
                    <!-- IP Sub-headers -->
                    <th class="table-header">Akt</th>
                    <th class="table-header">Std</th>

                     <!-- OVK Sub-headers -->
                     <th class="table-header hide-on-print">Jenis</th>
                     <th class="table-header hide-on-print">Jumlah</th>

                </tr>
            </thead>
            <tbody>
                @foreach($records ?? [] as $record)
                <tr class="border-b hover:bg-gray-50">
                    <td class="p-2">{{ $record['tanggal'] }}</td>
                    <td class="p-2">{{ $record['umur'] }}</td>
                    <td class="p-2">{{ $record['stock_awal'] }}</td>
                    
                    <!-- Deplesi -->
                    <td class="p-2">{{ $record['mati'] }}</td>
                    <td class="p-2">{{ $record['afkir'] }}</td>
                    <td class="p-2">{{ $record['total_deplesi'] }}</td>
                    <td class="p-2">{{ $record['deplesi_percentage'] }}%</td>
                    
                    <!-- Penangkapan -->
                    <td class="p-2">{{ $record['jual_ekor'] ?? 0 }}</td>
                    <td class="p-2">{{ $record['jual_kg'] ?? 0 }}</td>
                    <td class="p-2">{{ $record['jual_rata'] ?? 0 }}</td>
                    
                    <!-- Stock Akhir -->
                    <td class="p-2">{{ $record['stock_akhir'] }}</td>
                    
                    <!-- Pemakaian Pakan -->
                    <!-- Data -->
                    <td class="p-2">{{ $record['SP 10'] ?? 0 }}</td>
                    <td class="p-2">{{ $record['SP 11'] ?? 0 }}</td>
                    <td class="p-2">{{ $record['SP 12'] ?? 0 }}</td>
                    {{-- <td class="p-2">{{ $record['pakan_jenis'] ?? '-' }}</td>
                    <td class="p-2">{{ $record['pakan_harian'] ?? 0 }}</td>
                    <td class="p-2">{{ $record['pakan_total'] ?? 0 }}</td>
                    <td class="p-2">{{ $record['pakan_aktual'] ?? 0 }}</td>
                    <td class="p-2">{{ $record['pakan_aktual_total'] ?? 0 }}</td> --}}

                    <td class="p-2">{{ $record['berat_harian'] ?? 0 }}</td>
                    <td class="p-2">{{ $record['bw_standar'] ?? 0 }}</td>
                    {{-- <td class="p-2">{{ $record['pakan_sisa'] ?? 0 }}</td>
                    <td class="p-2">{{ $record['pakan_total_sisa'] ?? 0 }}</td> --}}
                    
                    <!-- FCR -->
                    <td class="p-2">{{ $record['fcr_akt'] ?? 0 }}</td>
                    <td class="p-2">{{ $record['fcr_target'] ?? 0 }}</td>
                    <td class="p-2">{{ $record['fcr_selisih'] ?? 0 }}</td>
                    
                    <!-- IP -->
                    <td class="p-2">{{ $record['ip_akt'] ?? 0 }}</td>
                    <td class="p-2">{{ $record['ip_std'] ?? 0 }}</td>
                    <td class="p-2">{{ $record['ip_std'] ?? 0 }}</td>
                    <td class="p-2">{{ $record['ip_std'] ?? 0 }}</td>

                    <td class="p-2 hide-on-print">{{ $record['ovk'] ?? 0 }}</td>
                    <td class="p-2 hide-on-print">{{ $record['ovk'] ?? 0 }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>
</html>

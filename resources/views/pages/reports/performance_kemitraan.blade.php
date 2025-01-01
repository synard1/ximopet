<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Performance Kemitraan</title>
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
        }
        .content p {
            margin: 8px 0;
        }
        .content p strong {
            display: inline-block;
            width: 200px;
        }
		
		#tablePenjualan {
			width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 12px;
            text-align: left;
		  }
		  
		#tablePenjualan th, #tablePenjualan td {
			padding: 6px;
            border: 1px solid #ddd;
		  }
		  
		#tableHpp {
			width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 12px;
            text-align: left;
		  }
		  
		#tableTotal {
			width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 12px;
            text-align: left;
		  }
		  
		#tableTotal th, #tableTotal td {
			padding: 6px;
            border: 0px solid #ddd;
		  }
		  
		#tableSign {
			width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 12px;
            text-align: left;
		  }
		  
		#tableSign th, #tableSign td {
			padding: 6px;
            border: 0px solid #ddd;
		  }
		  
<!--         table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 12px;
            text-align: left;
        } -->
<!--         th, td {
            padding: 6px;
            border: 1px solid #ddd;
        } -->
		
        th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .summary {
            font-weight: bold;
            background-color: #FFD700;
            padding: 8px;
            text-align: right;
            margin-top: 20px;
        }
        .signature {
    margin-top: 30px;
    display: flex;
    justify-content: space-between;
    font-size: 12px;
  }

		.signature div {
		  width: 100%;  /* Make signatures full width */
		  text-align: center; /* Center text within each signature */
		}
        .signature strong {
            display: block;
            margin-top: 10px;
        }
        @media print {
            /* A4 Paper size settings */
            @page {
                size: A4;
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
            }

            table {
                font-size: 10px;
                margin: 10px 0;
            }

            th, td {
                padding: 4px;
            }

            .summary {
                margin-top: 10px;
                font-size: 14px;
            }

            .signature {
                font-size: 12px;
                margin-top: 20px;
            }

            /* Reduce the height and spacing to make it fit on a single page */
            header {
                font-size: 14px;
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <header>
        PT. MITRA UNGGAS BERSAMA<br>
        PERFORMANCE KEMITRAAN
    </header>

    <div class="content">
        <p>
            <strong>Nama Mitra:</strong> {{ $ternak->kandang->nama }}<br>
            <strong>Tgl. Masuk DOC:</strong> {{ $ternak->start_date->translatedFormat('d F Y') }}<br>
            <strong>Jumlah DOC:</strong> {{ number_format($ternak->populasi_awal, 0, ',', '.') }} Ekor<br>
            <strong>Deplesi / Mortalitas:</strong> {{ number_format($kematian, 0, ',', '.') . ' Ekor ( '. number_format($persentaseKematian,2)  .' % )'  }}<br>
            <strong>Penjualan:</strong> {{  number_format($penjualan,0) .' Ekor ('.number_format($data['beratJual'],2) .' Kg) ('.number_format($data['beratJual'] / $penjualan,2) .' Kg)' }}<br>
            <strong>Pemakaian Pakan:</strong> {{ number_format($konsumsiPakan,0) }} Kg<br>
            <strong>FCR:</strong> {{ number_format($fcr,2)  }} Kg<br>
            <strong>Umur Panen:</strong> {{  number_format($umurPanen, 2, ',', '.') }} Hari<br>
            <strong>IP:</strong> {{ $ip }}<br>
            <strong>Person PPL Incharge:</strong> {{  $ternak->pic }}
        </p>

        <table id="tablePenjualan">
            <thead>
                <tr>
                    <th colspan="5">Penghasilan Inti Mitra</th>

                </tr>
            </thead>
            <tbody>
				<tr>
                    <td>Penjualan Ayam</td>
					<td>Bruto</td>
                    <td>{{ number_format($data['beratJual'],2) }} Kg</td>
                    <td>Rp {{ number_format($penjualanData->avg('detail.harga_jual'), 0, ',', '.') }}</td>
					<td>Rp {{  number_format($data['beratJual'] * $penjualanData->avg('detail.harga_jual'),2) }}</td>
                </tr>
				<tr>
                    <td></td>
					<td>Klaim</td>
                    <td></td>
                    <td>Rp -</td>
					<td>-</td>
                </tr>
				<tr>
                    <td></td>
					<td>Netto</td>
                    <td></td>
                    <td>Rp {{ number_format($penjualanData->avg('detail.harga_jual'), 0, ',', '.') }}</td>
					<td>Rp {{  number_format($data['beratJual'] * $penjualanData->avg('detail.harga_jual'),2) }}</td>
                </tr>
            </tbody>
        </table>

        <table>
            <thead>
                <tr>
                    <th>Harga Pokok Produksi (HPP)</th>
                    <th>Qty</th>
                    <th>Rp/Kg</th>
					<th>Sub Total</th>
                    <th>Total Rp</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Biaya DOC</td>
                    <td>{{ number_format($ternak->populasi_awal, 0, ',', '.') }} Ekor</td>
                    <td>Rp {{ number_format($ternak->hpp, 0, ',', '.') }}</td>
					<td></td>
                    <td style="text-align: right;">{{ number_format($ternak->populasi_awal * $ternak->hpp, 2, ',', '.') }}</td>
                </tr>
                <tr>
                    <td colspan="4">Biaya Pakan</td>
					<td style="text-align: right;">{{ number_format($data['totalBiayaPakan'], 2, ',', '.')}}</td>
                </tr>
                @forelse($data['biayaPakanDetails'] as $data2)
                    <tr>
                        <td>- {{ $data2->item_name }}</td>
                        <td>{{ number_format($data2->total_quantity, 0, ',', '.') }} Kg</td>
                        <td>Rp {{ number_format($data2->avg_price, 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($data2->total_cost, 0, ',', '.') }}</td>
                        <td></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9">No data available</td>
                    </tr>
                @endforelse
                {{-- <tr>
                    <td>- Super 10</td>
                    <td>1.750 Kg</td>
                    <td>8.650</td>
					<td>15.137.500,00</td>
					<td></td>
                </tr>
                <tr>
                    <td>- Super 11</td>
                    <td>5.500 Kg</td>
                    <td>8.600</td>
					<td>47.300.000,00</td>
					<td></td>
                </tr>
                <tr>
                    <td>- Super 12</td>
                    <td>8.250 Kg</td>
                    <td>8.306</td>
					<td>68.524.500,00</td>
					<td></td>
                </tr> --}}
                <tr>
                    <td>Biaya OVK</td>
                    <td>{{  $ternak->populasi_awal }} Ekor</td>
                    <td>Rp {{ number_format($data['totalBiayaOvk'] / $ternak->populasi_awal , 0, ',', '.')}}</td>
					<td></td>
                    <td style="text-align: right;">{{ number_format($data['totalBiayaOvk'], 2, ',', '.')}}</td>
                </tr>
                <tr>
                    <td>Pembayaran Bonus dan BOP</td>
                    <td></td>
                    <td></td>
					<td></td>
                    <td style="text-align: right;">{{ isset($data['bonus']) ? number_format($data['bonus']['jumlah'], 2, ',', '.') : '0' }}</td>
                </tr>

            </tbody>
        </table>


        <table id="tableTotal">
            <tbody>
                {{-- <tr>
                    <td>Pembayaran Bonus dan BOP</td>
                    <td style="text-align: right;">{{ isset($data['bonus']) ? number_format($data['bonus']['jumlah'], 2, ',', '.') : '0' }}</td>
                </tr> --}}
                <tr>
                    <td>Total HPP</td>
                    <td style="text-align: right;">{{ number_format($data['total_hpp'], 2, ',', '.')}}</td>
                </tr>
                <tr>
                    <td>HPP per Ekor</td>
                    <td style="text-align: right;">{{ number_format($data['hpp_per_ekor'], 2, ',', '.')}}</td>
                </tr>
                <tr>
                    <td>HPP per Kg</td>
                    <td style="text-align: right;">{{ number_format($data['hpp_per_kg'], 2, ',', '.')}}</td>
                </tr>
				<tr>
                    <td style="font-weight: bold;">Total Penghasilan Inti Mitra</td>
                    <td style="text-align: right; font-weight: bold; font-size: 14px;">{{ number_format($data['total_penghasilan'], 2, ',', '.')}}</td>
                </tr>
				<tr>
                    <td>Penghasilan Inti Mitra per Ekor</td>
                    <td style="text-align: right;">{{ number_format($data['penghasilan_per_ekor'], 2, ',', '.')}}</td>
                </tr>
				<tr rowspan="2">
                    <td></td>
                    <td style="text-align: right;"></td>
                </tr>
				<tr>
                    <td class="summary" style="text-align: left;">TOTAL PENGHASILAN INTI MITRA</td>
                    <td class="summary" style="text-align: right;">{{ number_format($data['total_penghasilan'], 2, ',', '.')}}</td>
                </tr>
            </tbody>
        </table>
		
		<table id="tableSign">
            <tbody>
				<tr>
                    <td style="width: 300px;"></td>
					<td></td>
                    <td></td>
                    <td></td>
					<td>Medan, {{ $data['administrasi']['tanggal_laporan'] }}</td>
                </tr>
				<tr>
                    <td></td>
					<td style="height: 70px; vertical-align: top;">Disetujui Oleh,</td>
                    <td></td>
                    <td></td>
					<td style="height: 70px; vertical-align: top;">Diverifikasi Oleh,</td>
                </tr>
				<tr>
                    <td></td>
					<td><strong>{{ $data['administrasi']['persetujuan_nama'] }}</strong> <br>{{ $data['administrasi']['persetujuan_jabatan'] }}</td>
                    <td></td>
                    <td></td>
					<td><strong>{{ $data['administrasi']['verifikator_nama'] }}</strong><br>{{ $data['administrasi']['verifikator_jabatan'] }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</body>
</html>

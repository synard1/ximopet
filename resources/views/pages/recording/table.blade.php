<div class="overflow-x-auto">
    <table class="table-auto w-full border-collapse border border-gray-300 mt-4 text-sm">
        <thead class="bg-gray-100 text-gray-700">
            <tr>
                <th class="table-header" rowspan="2">Tanggal</th>
                <th class="table-header" rowspan="2">Umur</th>
                <th class="table-header" rowspan="2">Stock Awal</th>
                
                <!-- Deplesi Column Group -->
                <th class="table-header text-center" colspan="4">DEPLESI</th>
                
                <!-- Penangkapan Column Group -->
                <th class="table-header text-center" colspan="4">PENANGKAPAN</th>
                
                <!-- Mutasi Ayam -->
                <th class="table-header text-center" colspan="2">MUTASI AYAM</th>
                
                <!-- Stock Akhir -->
                <th class="table-header" rowspan="2">STOCK AKHIR</th>
                
                <!-- Pemakaian Pakan Column Group -->
                <th class="table-header text-center" colspan="7">PEMAKAIAN PAKAN</th>

                <th class="table-header" rowspan="2">BW Akt (Gr)</th>
                <th class="table-header" rowspan="2">BW Std (Gr)</th>
                
                <!-- FCR Column -->
                <th class="table-header text-center" colspan="3">FCR</th>
                
                <!-- IP Column -->
                <th class="table-header text-center" colspan="2">IP</th>
                
                <!-- Umur Pakan -->
                <th class="table-header" rowspan="2">UMUR PANEN</th>
                
                <!-- Stock Pakan Column Group -->
                <th class="table-header text-center" colspan="3">STOCK PAKAN MASUK</th>
            </tr>
            <tr>
                <!-- Deplesi Sub-headers -->
                <th class="table-header">Mati</th>
                <th class="table-header">Afkir</th>
                <th class="table-header">Total</th>
                <th class="table-header">%</th>
                
                <!-- Penangkapan Sub-headers -->
                <th class="table-header">Ekor</th>
                <th class="table-header">Kg</th>
                <th class="table-header">Rata2 (Kg)</th>
                <th class="table-header">Ekor</th>
                
                <!-- Mutasi Ayam Sub-headers -->
                <th class="table-header">Ekor</th>
                <th class="table-header">Dr/Ke</th>
                
                <!-- Pemakaian Pakan Sub-headers -->
                <th class="table-header">Jenis</th>
                <th class="table-header">Harian (Kg)</th>
                <th class="table-header">Total (Kg)</th>
                <th class="table-header">Std (Gr)</th>
                <th class="table-header">Total Std (Gr)</th>
                <th class="table-header">Sisa (Gr)</th>
                <th class="table-header">Total Sisa (Gr)</th>
                

                <!-- FCR Sub-headers -->
                <th class="table-header">Aktual</th>
                <th class="table-header">Standar</th>
                <th class="table-header">Selisih</th>
                
                <!-- IP Sub-headers -->
                <th class="table-header">Std</th>
                <th class="table-header">Selish</th>
                
                <!-- Stock Pakan Sub-headers -->
                <th class="table-header">Jenis</th>
                <th class="table-header">Kg</th>
                <th class="table-header">SISA (Kg)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($recordings ?? [] as $record)
            <tr class="border-b hover:bg-gray-50">
                <td class="p-2">{{ $record['tanggal'] }}</td>
                <td class="p-2">{{ $record['age'] }}</td>
                <td class="p-2">{{ $record['stock_awal'] }}</td>
                
                <!-- Deplesi -->
                <td class="p-2">{{ $record['mati'] }}</td>
                <td class="p-2">{{ $record['afkir'] }}</td>
                <td class="p-2">{{ $record['total_deplesi'] }}</td>
                <td class="p-2">{{ $record['deplesi_percentage'] }}%</td>
                
                <!-- Penangkapan -->
                <td class="p-2">{{ $record['tangkap_ekor'] ?? 0 }}</td>
                <td class="p-2">{{ $record['tangkap_kg'] ?? 0 }}</td>
                <td class="p-2">{{ $record['tangkap_rata2'] ?? 0 }}</td>
                <td class="p-2">{{ $record['tangkap_total_ekor'] ?? 0 }}</td>
                
                <!-- Mutasi -->
                <td class="p-2">{{ $record['mutasi_dari'] ?? '-' }}</td>
                <td class="p-2">{{ $record['mutasi_dari'] ?? '-' }}</td>
                
                <!-- Stock Akhir -->
                <td class="p-2">{{ $record['stock_akhir'] }}</td>
                
                <!-- Pemakaian Pakan -->
                <td class="p-2">{{ $record['pakan_jenis'] ?? '-' }}</td>
                <td class="p-2">{{ $record['pakan_harian'] ?? 0 }}</td>
                <td class="p-2">{{ $record['pakan_total'] ?? 0 }}</td>
                <td class="p-2">{{ $record['pakan_std'] ?? 0 }}</td>
                <td class="p-2">{{ $record['pakan_total_std'] ?? 0 }}</td>
                <td class="p-2">{{ $record['pakan_sisa'] ?? 0 }}</td>
                <td class="p-2">{{ $record['pakan_total_sisa'] ?? 0 }}</td>
                
                <!-- FCR -->
                <td class="p-2">{{ $record['bw_aktual'] ?? 0 }}</td>
                <td class="p-2">{{ $record['bw_standar'] ?? 0 }}</td>
                <td class="p-2">{{ $record['fcr_aktual'] ?? 0 }}</td>
                
                <!-- IP -->
                <td class="p-2">{{ $record['ip_standar'] ?? 0 }}</td>
                <td class="p-2">{{ $record['ip_selisih'] ?? 0 }}</td>
                
                <!-- Umur Pakan -->
                <td class="p-2">{{ $record['umur_pakan'] ?? 0 }}</td>
                
                <!-- Stock Pakan -->
                <td class="p-2">{{ $record['stock_pakan_jenis'] ?? '-' }}</td>
                <td class="p-2">{{ $record['stock_pakan_kg'] ?? 0 }}</td>
                <td class="p-2">{{ $record['stock_pakan_sisa'] ?? 0 }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<style>
.table-header {
    @apply px-4 py-2 text-left border-b border-r border-gray-300 text-sm font-medium;
}
</style>
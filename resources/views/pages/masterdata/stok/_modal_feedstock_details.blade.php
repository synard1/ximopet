<!-- Modal -->
<div class="modal fade" id="kt_modal_feedstock_details" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-4">Detail Histori Stok Pakan</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="d-flex justify-content-between mb-3">
                <h5 class="fw-bold">Riwayat Stok per Batch</h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-success btn-sm" onclick="exportAllTablesToExcel()">Export Semua Excel</button>
                    <button class="btn btn-outline-dark btn-sm" onclick="printAllTables()">Print Semua</button>
                </div>
            </div>
            
            <div class="modal-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Rentang Tanggal:</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="dateRange" placeholder="Pilih rentang tanggal">
                            <button type="button" class="btn btn-primary" id="applyDateFilter">Terapkan</button>
                        </div>
                    </div>
                </div>
                <div id="feedstockDetailsContainer" class="accordion" style="max-height: 70vh; overflow-y: auto;">
                    <!-- Data FeedStock Grouped by Batch akan dimuat di sini via JS -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="closeDetails()">Tutup</button>
            </div>
        </div>
    </div>
</div>
@push('scripts')
    <script>
        function closeDetails() {
            try {
                destroyDetailsTable();
                
                if (window.LaravelDataTables && window.LaravelDataTables['stoks-table']) {
                    window.LaravelDataTables['stoks-table'].ajax.reload();
                    // console.log('Stoks table successfully reloaded');
                } else {
                    // console.log('Stoks table not found or not initialized');
                }
            } catch (error) {
                console.error('Error in closeDetails function:', error);
            }
        }

        function destroyDetailsTable() {
            if ($.fn.DataTable.isDataTable('#detailsStokTable')) {
                $('#detailsStokTable').DataTable().destroy();
                // console.log('Details table successfully destroyed');
            } else {
                // console.log('Details table was not a DataTable instance');
            }
            $('#detailsStokTable tbody').empty();
            // resetFarmSelect();
            // resetDateRange();
            // resetTableHeader();
        }

        // function resetFarmSelect() {
        //     const farmSelect = document.getElementById('farmSelect');
        //     if (farmSelect) {
        //         farmSelect.selectedIndex = 0;
        //         // console.log('Farm select reset to default');
        //     } else {
        //         console.log('Farm select element not found');
        //     }
        // }

        function resetDateRange() {
            const dateRangeInput = document.getElementById('dateRange');
            if (dateRangeInput) {
                const today = new Date().toISOString().split('T')[0];
                dateRangeInput.value = `${today} - ${today}`;
                // console.log('Date range reset to current date');
            } else {
                console.log('Date range input element not found');
            }
        }

        function resetTableHeader() {
            const headerHtml = `
                <tr style="border-bottom: 3px double #dee2e6;">
                    <th class="text-left" style="width: auto">#</th>
                    <th class="text-left" style="width: auto">Tanggal</th>
                    <th class="text-left" style="width: auto">Farm</th>
                    <th class="text-left" style="width: auto">Kandang</th>
                    <th class="text-left" style="width: auto">Nama Barang</th>
                    <th class="text-left" style="width: auto">Jumlah</th>
                </tr>
            `;
            $('#detailsStokTable thead').html(headerHtml);
            // console.log('Table header reset');
        }

        $('#kt_modal_feedstock_details').on('show.bs.modal', function () {
            // $.ajax({
            //     url: '/api/v1/farms-list', // Define the route to fetch farms
            //     method: 'GET',
            //     success: function(data) {
            //         const farmSelect = $('#farmSelect');
            //         farmSelect.empty(); // Clear existing options
            //         farmSelect.append('<option value="">Select Farm</option>'); // Add default option
            //         farmSelect.append('<option value="2d245e3f-fdc9-4138-b32d-994f3f1953a5">All Farms</option>'); // Add default option
            //         $.each(data, function(index, farm) {
            //             farmSelect.append('<option value="' + farm.id + '">' + farm.nama + '</option>');
            //         });
            //     },
            //     error: function(xhr) {
            //         console.error('Error fetching farms:', xhr);
            //     }
            // });
        });

        function exportTableToExcel(batchId) {
            const table = document.querySelector(`#${batchId} table`);
            if (!table) return;

            const tableHTML = table.outerHTML.replace(/ /g, '%20');

            const a = document.createElement('a');
            a.href = 'data:application/vnd.ms-excel,' + tableHTML;
            a.download = `${batchId}.xls`;
            a.click();
        }

        function printTable(batchId) {
            const table = document.querySelector(`#${batchId} table`);
            if (!table) return;

            const printWindow = window.open('', '', 'height=600,width=800');
            printWindow.document.write('<html><head><title>Print Table</title>');
            printWindow.document.write('<style>table { width: 100%; border-collapse: collapse; } th, td { border: 1px solid #ddd; padding: 8px; }</style>');
            printWindow.document.write('</head><body >');
            printWindow.document.write(table.outerHTML);
            printWindow.document.write('</body></html>');
            printWindow.document.close();
            printWindow.print();
        }

        function exportTableToExcel(batchId) {
            const table = document.querySelector(`#${batchId} table`);
            if (!table) return;

            const tableHTML = table.outerHTML.replace(/ /g, '%20');

            const a = document.createElement('a');
            a.href = 'data:application/vnd.ms-excel,' + tableHTML;
            a.download = `${batchId}.xls`;
            a.click();
        }

        function printTable(batchId) {
            const table = document.querySelector(`#${batchId} table`);
            if (!table) return;

            const printWindow = window.open('', '', 'height=600,width=800');
            printWindow.document.write('<html><head><title>Print Table</title>');
            printWindow.document.write('<style>table { width: 100%; border-collapse: collapse; } th, td { border: 1px solid #ddd; padding: 8px; }</style>');
            printWindow.document.write('</head><body >');
            printWindow.document.write(table.outerHTML);
            printWindow.document.write('</body></html>');
            printWindow.document.close();
            printWindow.print();
        }

        function exportAllTablesToExcel() {
            const tables = document.querySelectorAll('#feedstockDetailsContainer table');
            let html = '';
            tables.forEach((table, i) => {
                html += `<h3>Table ${i + 1}</h3>` + table.outerHTML + '<br><br>';
            });

            const a = document.createElement('a');
            a.href = 'data:application/vnd.ms-excel,' + html.replace(/ /g, '%20');
            a.download = `feedstock_histories_all.xls`;
            a.click();
        }

        function printAllTables() {
            const tables = document.querySelectorAll('#feedstockDetailsContainer table');
            const printWindow = window.open('', '', 'height=700,width=900');
            printWindow.document.write('<html><head><title>Print Semua</title>');
            printWindow.document.write('<style>table { width: 100%; border-collapse: collapse; margin-bottom: 40px; } th, td { border: 1px solid #ccc; padding: 6px; }</style>');
            printWindow.document.write('</head><body>');

            tables.forEach((table, i) => {
                printWindow.document.write(`<h3>Table ${i + 1}</h3>`);
                printWindow.document.write(table.outerHTML);
            });

            printWindow.document.write('</body></html>');
            printWindow.document.close();
            printWindow.print();
        }


    </script>
@endpush
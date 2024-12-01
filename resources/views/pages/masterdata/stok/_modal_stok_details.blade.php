<!-- Modal -->
<div class="modal fade" id="kt_modal_stok_details" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-4" id="kt_modal_stok_details_title">Stock Details</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-4">
                    <div class="col-md-4">
                        <label for="farmSelect" class="form-label">Select Farm:</label>
                        <select class="form-select" id="farmSelect">
                            <option value="2d245e3f-fdc9-4138-b32d-994f3f1953a5">All Farms</option>
                            @foreach(auth()->user()->farmOperators as $farmOperator)
                                <option value="{{ $farmOperator->farm_id }}">{{ $farmOperator->farm->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Date Range:</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="dateRange" placeholder="Select date range">
                            <button type="button" class="btn btn-primary" id="applyDateFilter">Apply</button>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table id="detailsStokTable" class="table table-striped table-hover" style="width:100%">
                        <thead>
                            <tr style="border-bottom: 3px double #dee2e6;">
                                <th class="text-center" style="width: auto">#</th>
                                <th class="text-center" style="width: auto">Tanggal</th>
                                <th class="text-center" style="width: auto">Farm</th>
                                <th class="text-center" style="width: auto">Nama Barang</th>
                                <th class="text-center" style="width: auto">Supplier</th>
                                <th class="text-center" style="width: auto">HPP</th>
                                <th class="text-center" style="width: auto">Stok Awal</th>
                                <th class="text-center" style="width: auto">Stok Masuk</th>
                                <th class="text-center" style="width: auto">Stok Keluar</th>
                                <th class="text-center" style="width: auto">Stok Akhir</th>
                                <th class="text-center" style="width: auto">Satuan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data will be populated by DataTables -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="closeDetails()">Close</button>
            </div>
        </div>
    </div>
</div>

@push('styles')
    <link href="https://cdn.datatables.net/2.1.2/css/dataTables.dataTables.css" rel="stylesheet" type="text/css" />
@endpush

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

        function resetFarmSelect() {
            const farmSelect = document.getElementById('farmSelect');
            if (farmSelect) {
                farmSelect.selectedIndex = 0;
                // console.log('Farm select reset to default');
            } else {
                console.log('Farm select element not found');
            }
        }

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
                    <th class="text-center" style="width: auto">#</th>
                    <th class="text-center" style="width: auto">Tanggal</th>
                    <th class="text-center" style="width: auto">Farm</th>
                    <th class="text-center" style="width: auto">Nama Barang</th>
                    <th class="text-center" style="width: auto">Supplier</th>
                    <th class="text-center" style="width: auto">HPP</th>
                    <th class="text-center" style="width: auto">Stok Awal</th>
                    <th class="text-center" style="width: auto">Stok Masuk</th>
                    <th class="text-center" style="width: auto">Stok Keluar</th>
                    <th class="text-center" style="width: auto">Stok Akhir</th>
                    <th class="text-center" style="width: auto">Satuan</th>
                </tr>
            `;
            $('#detailsStokTable thead').html(headerHtml);
            // console.log('Table header reset');
        }
    </script>
@endpush

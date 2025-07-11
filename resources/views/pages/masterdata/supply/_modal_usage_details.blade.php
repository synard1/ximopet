<!-- Modal -->
<div class="modal fade" id="kt_modal_usage_details" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="kt_modal_usage_details_title">Detail Pemakaian</h1>
            </div>
            <div class="modal-body">
                <div class="mb-5" id="usage-info">
                    <div class="row mb-2">
                        <div class="col-md-3 fw-bold">Tanggal:</div>
                        <div class="col-md-9" id="usage-date"></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-3 fw-bold">Dari:</div>
                        <div class="col-md-9" id="usage-from"></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-3 fw-bold">Ke:</div>
                        <div class="col-md-9" id="usage-to"></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-3 fw-bold">Catatan:</div>
                        <div class="col-md-9" id="usage-notes"></div>
                    </div>
                </div>
                <table id="detailsTable" class="display" style="width:100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Tanggal Pemakaian</th>
                            <th>Kode</th>
                            <th>Nama Item</th>
                            <th>Jumlah</th>
                            <th>Satuan</th>
                            <th>Jumlah Konversi</th>
                            <th>Satuan Sistem</th>
                            <th>Harga per Unit</th>
                            <th>Harga per Konversi</th>
                            <th
                        </tr>
                    </thead>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                    onclick="closeDetails()">Close</button>
            </div>
        </div>
    </div>
</div>
@push('styles')
<link href="https://cdn.datatables.net/2.1.2/css/dataTables.dataTables.css" rel="stylesheet" type="text/css" />
@endpush

@push('scripts')
<script>
    function getDetails(param) {
        // Get the Sanctum token from the session
        const token = '{{ Session::get('auth_token') }}';

        // Set up headers with the token
        const headers = {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json'
        };

        // First fetch mutation details
        fetch(`/api/v2/supply/mutation/details/${param}`, {
            method: 'POST',
            headers: headers
        })
        .then(response => response.json())
        .then(data => {
            // Update the mutation info section
            document.getElementById('mutation-date').textContent = data.date;
            document.getElementById('mutation-from').textContent = data.from;
            document.getElementById('mutation-to').textContent = data.to;
            document.getElementById('mutation-notes').textContent = data.notes || '-';
            
            // Update modal title
            document.getElementById('kt_modal_mutation_details_title').textContent = 
                `Detail Mutasi ${data.type.toUpperCase()} - ${data.scope.charAt(0).toUpperCase() + data.scope.slice(1)}`;
            
            // Initialize DataTable with items
            initializeDataTable(data.items);
        })
        .catch(error => {
            console.error('Error fetching mutation details:', error);
            toastr.error('Gagal memuat detail mutasi');
        });
    }
    
    function initializeDataTable(items) {
        // Create a data source with calculated values
        const dataSource = items.map((item, index) => {
            const purchaseDate = new Date(item.purchase_date).toLocaleDateString('id-ID');
            const originalQuantity = parseFloat(item.original_quantity || item.quantity);
            const convertedQuantity = parseFloat(item.quantity);
            const price = parseFloat(item.purchase_price);
            const subTotal = convertedQuantity * price;
            
            return {
                id: index + 1,
                purchase_date: purchaseDate,
                item_code: item.item_code || '-',
                item_name: item.item_name,
                original_quantity: originalQuantity,
                source_unit: item.source_unit || '-',
                converted_quantity: convertedQuantity,
                target_unit: item.target_unit || '-',
                purchase_price: price,
                sub_total: subTotal,
                // Add conversion info for tooltip
                conversion_rate: item.conversion_rate || 1
            };
        });
        
        // Initialize DataTable
        const table = new DataTable('#detailsTable', {
            data: dataSource,
            columns: [
                {
                    data: 'id',
                    title: '#'
                },
                {
                    data: 'purchase_date',
                    title: 'Tanggal Pembelian'
                },
                {
                    data: 'item_code',
                    title: 'Kode'
                },
                {
                    data: 'item_name',
                    title: 'Nama Item'
                },
                {
                    data: 'original_quantity',
                    className: 'text-center',
                    title: 'Jumlah',
                    render: $.fn.dataTable.render.number(',', '.', 2)
                },
                {
                    data: 'source_unit',
                    title: 'Satuan'
                },
                {
                    data: 'converted_quantity',
                    className: 'text-center',
                    title: 'Jumlah Konversi',
                    render: $.fn.dataTable.render.number(',', '.', 2)
                },
                {
                    data: 'target_unit',
                    title: 'Satuan Sistem'
                },
                {
                    data: 'purchase_price',
                    className: 'text-end',
                    title: 'Harga',
                    render: $.fn.dataTable.render.number(',', '.', 0, 'Rp')
                },
                {
                    data: 'sub_total',
                    className: 'text-end',
                    title: 'Sub Total',
                    render: $.fn.dataTable.render.number(',', '.', 0, 'Rp')
                }
            ],
            createdRow: function(row, data, index) {
                // Add tooltip with conversion information
                $(row).find('td:eq(4), td:eq(5), td:eq(6), td:eq(7)').attr('title', 
                    `Faktor Konversi: ${data.conversion_rate}x\n` +
                    `1 ${data.source_unit} = ${data.conversion_rate} ${data.target_unit}`
                );
                
                // Add visual indicator for conversion
                if (data.conversion_rate !== 1) {
                    $(row).find('td:eq(6), td:eq(7)').addClass('text-info');
                }
            }
        });
        
        // Add tooltip initialization if needed
        $('[title]').tooltip({
            container: 'body',
            placement: 'top'
        });
    }

    function closeDetails() {
        var table = new DataTable('#detailsTable');
        table.destroy();
        
        // Reset mutation info fields
        document.getElementById('mutation-date').textContent = '';
        document.getElementById('mutation-from').textContent = '';
        document.getElementById('mutation-to').textContent = '';
        document.getElementById('mutation-notes').textContent = '';
        
        // Reload the parent table if it exists
        if (typeof window.LaravelDataTables !== 'undefined' && 
            window.LaravelDataTables['pembelianStoks-table']) {
            window.LaravelDataTables['pembelianStoks-table'].ajax.reload();
        }
    }
</script>
@endpush
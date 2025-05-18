<!-- Modal -->
<div class="modal fade" id="kt_modal_mutation_details" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="kt_modal_mutation_details_title">Detail Mutasi</h1>
            </div>
            <div class="modal-body">
                <div class="mb-5" id="mutation-info">
                    <div class="row mb-2">
                        <div class="col-md-3 fw-bold">Tanggal:</div>
                        <div class="col-md-9" id="mutation-date"></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-3 fw-bold">Dari:</div>
                        <div class="col-md-9" id="mutation-from"></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-3 fw-bold">Ke:</div>
                        <div class="col-md-9" id="mutation-to"></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-3 fw-bold">Catatan:</div>
                        <div class="col-md-9" id="mutation-notes"></div>
                    </div>
                </div>
                <table id="detailsTable" class="display" style="width:100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Tanggal Pembelian</th>
                            <th>Kode</th>
                            <th>Nama Item</th>
                            <th>Jumlah</th>
                            <th>Satuan</th>
                            <th>Jumlah Konversi</th>
                            <th>Satuan Sistem</th>
                            <th>Harga</th>
                            <th>Sub Total</th>
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
<style>
    /* Highlight conversion factors */
    .conversion-highlight {
        color: #0d6efd;
        font-weight: 500;
    }
</style>
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

        // Clear previous data
        document.getElementById('mutation-date').textContent = '';
        document.getElementById('mutation-from').textContent = '';
        document.getElementById('mutation-to').textContent = '';
        document.getElementById('mutation-notes').textContent = '';

        // First fetch mutation details
        fetch(`/api/v2/feed/mutation/details/${param}`, {
            method: 'POST',
            headers: headers
        })
        .then(response => response.json())
        .then(data => {
            console.log('Mutation data:', data); // For debugging
            
            // Update the mutation info section
            document.getElementById('mutation-date').textContent = data.date || '';
            document.getElementById('mutation-from').textContent = data.from || '';
            document.getElementById('mutation-to').textContent = data.to || '';
            document.getElementById('mutation-notes').textContent = data.notes || '-';
            
            // Update modal title
            let type = (data.type || 'feed').toUpperCase();
            let scope = data.scope ? data.scope.charAt(0).toUpperCase() + data.scope.slice(1) : 'Internal';
            document.getElementById('kt_modal_mutation_details_title').textContent = 
                `Detail Mutasi ${type} - ${scope}`;
            
            // Process and display the items
            initializeDataTable(data.items || [], data.payload || {});
        })
        .catch(error => {
            console.error('Error fetching mutation details:', error);
            toastr.error('Gagal memuat detail mutasi');
        });
    }
    
    function initializeDataTable(items, payload) {
        // Destroy existing table if it exists
        if ($.fn.DataTable.isDataTable('#detailsTable')) {
            $('#detailsTable').DataTable().destroy();
        }
        
        // Create a data source with calculated values
        const dataSource = items.map((item, index) => {
            // Format purchase date
            const purchaseDate = item.purchase_date ? 
                new Date(item.purchase_date).toLocaleDateString('id-ID') : '-';
            
            // Get unit information directly from the item's unit_metadata
            const unitMetadata = item.unit_metadata || {};
            
            // Get source unit name (prefer from payload, fall back to item)
            let sourceUnitName = item.source_unit || '-';
            
            // Try to get from payload items_metadata if available
            if (payload.items_metadata && payload.items_metadata.length > 0) {
                // First try to match by name
                const metadataByName = payload.items_metadata.find(meta => 
                    meta.item_name === item.item_name || 
                    meta.item_id === item.item_id
                );
                
                if (metadataByName && metadataByName.unit_name) {
                    sourceUnitName = metadataByName.unit_name;
                }
            }
            
            // If we have unit_metadata with input_unit_id, we can try to look up the unit name
            if (unitMetadata.input_unit_id) {
                // First check if there's a matching unit in the payload
                const matchingUnit = payload.items_metadata ? 
                    payload.items_metadata.find(meta => 
                        meta.unit_id === unitMetadata.input_unit_id
                    ) : null;
                
                if (matchingUnit && matchingUnit.unit_name) {
                    sourceUnitName = matchingUnit.unit_name;
                }
            }
            
            // Get conversion rate from unit_metadata or calculate it
            let conversionRate = unitMetadata.conversion_rate || 1;
            if (!conversionRate && item.original_quantity && item.quantity) {
                conversionRate = parseFloat(item.quantity) / parseFloat(item.original_quantity);
            }
            
            // Get quantities
            const originalQuantity = parseFloat(item.original_quantity || 0);
            const convertedQuantity = parseFloat(item.quantity || 0);
            
            // Calculate price and subtotal
            const price = parseFloat(item.purchase_price || 0);
            const subTotal = convertedQuantity * price;
            
            return {
                id: index + 1,
                purchase_date: purchaseDate,
                item_code: item.item_code || '-',
                item_name: item.item_name || '-',
                original_quantity: originalQuantity,
                source_unit: sourceUnitName,
                converted_quantity: convertedQuantity,
                target_unit: item.target_unit || '-',
                purchase_price: price,
                sub_total: subTotal,
                conversion_rate: conversionRate,
                unit_metadata: unitMetadata
            };
        });
        
        // Initialize DataTable
        const table = new DataTable('#detailsTable', {
            data: dataSource,
            columns: [
                {
                    data: 'id',
                    title: '#',
                    className: 'text-center'
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
                    className: 'text-center conversion-highlight',
                    title: 'Jumlah Konversi',
                    render: $.fn.dataTable.render.number(',', '.', 2)
                },
                {
                    data: 'target_unit',
                    className: 'conversion-highlight',
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
                // Format conversion rate for display
                const formattedRate = parseFloat(data.conversion_rate).toLocaleString('id-ID', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 2
                });
                
                // Create detailed tooltip
                const tooltipContent = 
                    `<div style="text-align: left;">
                       <strong>Faktor Konversi:</strong> ${formattedRate}x<br>
                       <strong>Detail:</strong> 1 ${data.source_unit} = ${formattedRate} ${data.target_unit}
                     </div>`;
                
                // Apply tooltip to quantity and unit cells
                const unitCells = $(row).find('td:eq(4), td:eq(5), td:eq(6), td:eq(7)');
                unitCells.attr('data-bs-toggle', 'tooltip')
                         .attr('data-bs-html', 'true')
                         .attr('data-bs-title', tooltipContent);
                
                // Add visual indicator for conversion if rate is not 1
                if (data.conversion_rate !== 1) {
                    // Make the conversion columns bold
                    $(row).find('td:eq(6), td:eq(7)').addClass('fw-bold');
                    
                    // Add a small indicator showing the conversion rate
                    const conversionCell = $(row).find('td:eq(5)');
                    conversionCell.html(`${data.source_unit} <small class="text-muted">(x${formattedRate})</small>`);
                }
            },
            drawCallback: function() {
                // Initialize Bootstrap tooltips after table is drawn
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.forEach(function(tooltipTriggerEl) {
                    new bootstrap.Tooltip(tooltipTriggerEl, {
                        container: 'body',
                        html: true
                    });
                });
            },
            language: {
                "paginate": {
                    "first": "Pertama",
                    "last": "Terakhir",
                    "next": "Selanjutnya",
                    "previous": "Sebelumnya"
                },
                "search": "Cari:",
                "lengthMenu": "Tampilkan _MENU_ data",
                "info": "Menampilkan _START_ hingga _END_ dari _TOTAL_ data",
                "infoEmpty": "Tidak ada data yang ditampilkan",
                "infoFiltered": "(difilter dari _MAX_ total data)",
                "zeroRecords": "Tidak ditemukan data yang sesuai",
                "emptyTable": "Tidak ada data tersedia"
            }
        });
    }

    function closeDetails() {
        // Destroy existing table to clean up memory
        if ($.fn.DataTable.isDataTable('#detailsTable')) {
            $('#detailsTable').DataTable().destroy();
        } else {
            try {
                var table = new DataTable('#detailsTable');
                table.destroy();
            } catch (e) {
                console.log('Table already destroyed or not initialized');
            }
        }
        
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
<!-- Modal -->
<div class="modal fade" id="kt_modal_pembelian_details" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="kt_modal_pembelian_details_title">Modal title</h1>
                {{-- <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button> --}}
            </div>
            <div class="modal-body">
                <table id="detailsTable" class="display" style="width:100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Jenis</th>
                            <th>Nama</th>
                            <th>Jumlah</th>
                            <th>Terpakai</th>
                            <th>Sisa</th>
                            <th>Satuan</th>
                            <th>Harga</th>
                            <th>Sub Total</th>
                        </tr>
                    </thead>
                </table>
                <p>* Hanya <b>Jumlah</b> dan <b>Harga</b> yang bisa di ubah secara langsung</br>
                    * Data hanya bisa diubah jika belum memiliki data transaksi</br>
                    * Klik pada kolom data yang akan di ubah untuk membuka fungsi ubah secara langsung</p>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                    onclick="closeDetails()">Close</button>
                {{-- <button type="button" class="btn btn-primary">Understood</button> --}}
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
            // console.log(param);
            // Get the Sanctum token from the session
            const token = '{{ Session::get('auth_token') }}';

            // Set up headers with the token
            const headers = {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
            };

            const table = new DataTable('#detailsTable', {
                ajax: {
                    url: `/api/v1/transaksi/details/${param}`,
                    headers: headers
                },
                columns: [{
                        data: 'id',
                        title: '#',
                        render: function(data, type, row, meta) {
                            return meta.row + meta.settings._iDisplayStart + 1;
                        }
                    },
                    {
                        data: 'jenis_barang'
                    },
                    {
                        data: 'item_name'
                    },
                    {
                        data: 'qty',
                        className: 'editable', // Tambahkan className di sini
                    },
                    {
                        data: 'terpakai',
                    },
                    {
                        data: 'sisa',
                    },
                    {
                        data: 'satuan_besar'
                    },
                    {
                        data: 'harga',
                        className: 'editable',
                        render: $.fn.dataTable.render.number(',', '.', 0, 'Rp')
                    },
                    {
                        data: 'sub_total',
                        render: $.fn.dataTable.render.number(',', '.', 0, 'Rp')
                    }
                ]
            });

            // Enable inline editing
            // Make cells editable (using a simple approach for now)
            table.on('click', 'tbody td.editable', function() {
                var cell = $(this);
                // var originalValue = cell.text();

                // Extract the numeric value without the prefix
                var originalValueText = cell.text();
                var originalValue = parseFloat(originalValueText.replace(/[^0-9.-]+/g, '')); // Remove non-numeric characters


                // Get the row data to check 'terpakai'
                var rowData = table.row(cell.closest('tr')).data();
                // console.log(rowData.terpakai);

                // Disable editing if 'terpakai' is greater than 0 or if it's null/undefined
                if (rowData.terpakai > 0 || rowData.terpakai === null || rowData.terpakai === undefined) {
                    return; // Exit the click handler, preventing editing
                }

                // Create an input field for editing
                var input = $('<input type="text" value="' + originalValue + '">');
                cell.html(input);
                input.focus();

                // Handle saving the edit
                input.blur(function() {
                    // var newValue = input.val();
                    var newValue = parseFloat(input.val()); // Parse the new value as a float

                    // if (newValue !== originalValue) {
                    if (!isNaN(newValue) && newValue !== originalValue) { 
                        // Get the row and column data
                        var rowData = table.row(cell.closest('tr')).data();
                        var columnIndex = table.cell(cell).index().column;
                        var columnData = table.settings().init().columns[columnIndex];

                        // Send AJAX request to update the data
                        $.ajax({
                            url: '/api/v1/stocks',
                            method: 'POST',
                            headers: {
                                'Authorization': 'Bearer ' + '{{ session('auth_token') }}',
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            data: {
                                type: 'edit',
                                id: rowData.id,
                                column: columnData.data,
                                value: newValue
                            },
                            success: function(response) {
                                cell.text(newValue);
                                toastr.success(response.message);
                                table.ajax.reload();
                            },
                            error: function(xhr, status, error) {
                                table.ajax.reload();
                                if (xhr.status === 401) {
                                    toastr.error('Unauthorized. Please log in again.');
                                    // Optionally, redirect to login page
                                    // window.location.href = '/login';
                                } else {
                                    toastr.error('Error updating value: ' + xhr.responseJSON.message);
                                }
                            }
                        });
                    } else if (isNaN(newValue) || newValue === '') {
                        alert('Error value cannot blank');
                        table.ajax.reload();

                    } else {
                        // No change, revert to original value
                        table.ajax.reload();
                    }
                });
            });
        }

        function closeDetails() {
            var table = new DataTable('#detailsTable');
            table.destroy();
            window.LaravelDataTables['pembelianStoks-table'].ajax.reload();
        }
    </script>
@endpush

<!-- Modal -->
<div class="modal fade" id="kt_modal_pembelian_details" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="kt_modal_pembelian_details_title">Modal title</h1>
            </div>
            <div class="modal-body">
                <table id="detailsTable" class="display" style="width:100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nama</th>
                            <th>Jumlah</th>
                            <th>Terpakai</th>
                            <th>Mutasi</th>
                            <th>Sisa</th>
                            <th>Satuan</th>
                            <th>Harga</th>
                            <th>Sub Total</th>
                        </tr>
                    </thead>
                </table>
                @if(auth()->user()->can('update feed purchasing') )
                <div class="alert alert-warning mb-4" id="statusAlert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Status <span id="statusLabel"></span> tidak dapat dibatalkan atau diubah kembali setelah
                    disimpan.
                </div>
                <div id="editableAlert">
                    <p>* Hanya <b>Jumlah</b> dan <b>Harga</b> yang bisa di ubah secara langsung</br>
                        * Data hanya bisa diubah jika belum memiliki data transaksi</br>
                        * Klik pada kolom data yang akan di ubah untuk membuka fungsi ubah secara langsung</p>
                </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"">Close</button>
            </div>
        </div>
    </div>
</div>
@push('styles')
<link href=" https://cdn.datatables.net/2.1.2/css/dataTables.dataTables.css" rel="stylesheet" type="text/css" />
                @endpush

                @push('scripts')
                <script>
                    function getDetails(param, statusData) {
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
                                url: `/api/v2/feed/purchase/details/${param}`,
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
                                    data: 'name'
                                },
                                {
                                    data: 'quantity',
                                    className: 'editable text-center', // Add text-end class for right alignment
                                    // render: $.fn.dataTable.render.number(',', '.', 2)
                                    
                                },
                                {
                                    data: 'total_terpakai',
                                    className: 'text-center', // Add text-end class for right alignment
                                    // render: $.fn.dataTable.render.number(',', '.', 0)
                                },
                                {
                                    data: 'mutated',
                                    className: 'text-center', // Add text-end class for right alignment
                                    // render: $.fn.dataTable.render.number(',', '.', 0)
                                },
                                {
                                    data: 'sisa',
                                    className: 'text-center', // Add text-end class for right alignment
                                    // render: $.fn.dataTable.render.number(',', '.', 0)
                                },
                                {
                                    data: 'unit'
                                },
                                {
                                    data: 'price_per_unit',
                                    className: 'editable text-end',
                                    render: $.fn.dataTable.render.number(',', '.', 0, 'Rp')
                                },
                                {
                                    data: 'total',
                                    className: 'text-end', // Add text-end class for right alignment
                                    render: $.fn.dataTable.render.number(',', '.', 0, 'Rp')
                                }
                            ]
                        });


            if (statusData === 'completed') {
                $('#statusAlert').show();
                $('#editableAlert').hide();
            } else {
                $('#statusAlert').hide();
                $('#editableAlert').show();
            }

            // console.log('statusData', status);

            // Enable inline editing
            // Make cells editable (using a simple approach for now)
            @if(auth()->user()->can('update feed purchasing'))
            if (statusData !== 'completed') {
            table.on('click', 'tbody td.editable', function() {
                var cell = $(this);
                var originalValueText = cell.text();
                var originalValue = parseFloat(originalValueText.replace(/[^0-9.-]+/g, ''));

                // Get the row data safely
                var row = table.row(cell.closest('tr'));
                if (!row || !row.data()) {
                    console.error('Unable to get row data');
                    return;
                }
                var rowData = row.data();
                

                // Safely check 'terpakai'
                var terpakai = rowData && rowData.terpakai !== undefined ? rowData.terpakai : 0;

                // Disable editing if 'terpakai' is greater than 0
                // if (terpakai > 0) {
                //     return;
                // }

                // Create an input field for editing
                var input = $('<input type="text" value="' + originalValue + '">');
                cell.html(input);
                input.focus();

                // Handle saving the edit
                input.blur(function() {
                    var newValue = parseFloat(input.val());
                    var columnIndex = table.cell(cell).index().column;
                    var columnData = table.settings().init().columns[columnIndex];
                    console.log('newValue ' + newValue);
                    console.log('columnIndex ' + columnIndex);
                    console.log('columnData ' + columnData);
                    


                    if (!isNaN(newValue) && newValue !== originalValue) {
                        // Check if we're editing the 'quantity' column
                        if (columnData.data === 'quantity') {
                            // Get the 'terpakai' value from the row data
                            var terpakai = parseFloat(rowData.terpakai);

                            // Check if new quantity is less than 'terpakai'
                            if (newValue < terpakai) {
                                Swal.fire({
                                    title: 'Error!',
                                    text: 'Jumlah baru tidak boleh kurang dari jumlah terpakai.',
                                    icon: 'error',
                                    confirmButtonText: 'OK'
                                });
                                table.ajax.reload();
                                return;
                            }
                        }

                        // Send AJAX request to update the data
                        $.ajax({
                            url: '/api/v2/feed/purchase/edit',
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
                                } else {
                                    toastr.error('Error updating value: ' + xhr.responseJSON.message);
                                }
                            }
                        });
                    } else if (isNaN(newValue) || newValue === '') {
                        Swal.fire({
                            title: 'Error!',
                            text: 'Nilai tidak boleh kosong.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                        table.ajax.reload();
                    } else {
                        table.ajax.reload();
                    }
                });

                });
            }
            @endif
        }

        function closeDetails() {
            var table = new DataTable('#detailsTable');
            table.destroy();
            window.LaravelDataTables['pembelianStoks-table'].ajax.reload();
        }
                </script>
                @endpush
<!-- Modal -->
<div class="modal fade" id="kt_modal_pemakaian_details" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h1 class="modal-title fs-5" id="kt_modal_pemakaian_details_title">Modal title</h1>
          {{-- <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button> --}}
        </div>
        <div class="modal-body">
            <table id="detailsTable" class="display" style="width:100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Tanggal</th>
                        <th>Jenis</th>
                        <th>Nama</th>
                        {{-- <th>Stok Awal</th> --}}
                        <th>Jumlah</th>
                        {{-- <th>Stok Akhir</th> --}}
                        {{-- <th>Harga</th> --}}
                        {{-- <th>Sub Total</th> --}}
                    </tr>
                </thead>
            </table>
            <p> * Klik pada baris jumlah untuk melakukan perubahan secara langsung</br>
            </p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="closeDetails()">Close</button>
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
        function getDetailPemakaian(param, tanggal) {
            // console.log(param);
            var dataId = param;

            const table = new DataTable('#detailsTable', {
                // ajax: `/api/v1/transaksi/pemakaian/details/${param}`,
                ajax: {
                    url: "/api/v2/d/transaksi/detail", // Replace with your actual route
                    headers: {
                                'Authorization': 'Bearer ' + '{{ session('auth_token') }}',
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                    type: 'POST', // Use POST method
                    data: function (d) {
                        // Add your additional data here
                        d.task = 'READ';
                        d.jenis = 'Pemakaian';
                        d.id = param;
                    }
                },
                columns: [
                    { data: 'id',
                      title: '#',
                        render: function (data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                        } 
                    },
                    { data: 'tanggal' },
                    { data: 'kategori' },
                    { data: 'nama' },
                    { data: 'terpakai', 
                      className: 'editable',
                      title: 'Jumlah',
                      render: $.fn.dataTable.render.number( '.', ',', 0, '' ) },
                    // { data: 'stok_awal', render: $.fn.dataTable.render.number( '.', ',', 2, '' ) },
                    // { data: 'terpakai', render: $.fn.dataTable.render.number( '.', ',', 2, '' ) },
                    // { data: 'sisa', render: $.fn.dataTable.render.number( '.', ',', 2, '' ) },
                    // { data: 'harga', render: $.fn.dataTable.render.number( '.', ',', 2, 'Rp' ) },
                    // { data: 'sub_total', render: $.fn.dataTable.render.number( '.', ',', 2, 'Rp' ) }
                ]
            });

            // Store tanggal in a data attribute of the table
            $('#detailsTable').data('tanggal', tanggal);

            // Make cells editable (using a simple approach for now)
            table.on('click', 'tbody td.editable', function(e){
              e.preventDefault();

              const parent = e.target.closest('tr');

              
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
                var tanggal = rowData && rowData.tanggal !== undefined ? rowData.tanggal : 0;


                // Create an input field for editing
                var input = $('<input type="text" value="' + originalValue + '">');
                cell.html(input);
                input.focus();

                // Handle saving the edit
                input.blur(function() {
                    var newValue = parseFloat(input.val());

                    if (!isNaN(newValue) && newValue !== originalValue) { 
                        var columnIndex = table.cell(cell).index().column;
                        var columnData = table.settings().init().columns[columnIndex];
                        var category = parent.querySelectorAll('td')[2].innerText;
                        // var tanggal = $('#detailsTable').data('tanggal'); // Get tanggal from data attribute


                        // Send AJAX request to update the data
                        $.ajax({
                            url: '/api/v2/d/transaksi/detail',
                            method: 'POST',
                            headers: {
                                'Authorization': 'Bearer ' + '{{ session('auth_token') }}',
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            data: {
                                task: 'UPDATE',
                                id: dataId,
                                tanggal: tanggal,
                                category: category,
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
                        alert('Error: Value cannot be blank');
                        table.ajax.reload();
                    } else {
                        table.ajax.reload();
                    }
                });
            });
        }

        function closeDetails() {
          var table = new DataTable('#detailsTable');
          table.destroy();
          // console.log('tables destroy');
        }

        // Destroy DataTables on modal close
        // $('#kt_modal_pemakaian_details').on('hidden.bs.modal', function () {
        //     // Destroy the DataTables instance
        //     window.LaravelDataTables['detailsTable'].destroy();
        //     console.log('tables destroy');
        // });
        
    </script>
  @endpush
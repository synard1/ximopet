<!-- Modal -->
<div class="modal fade" id="kt_modal_pembelian_details" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
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
                        <th>Harga</th>
                        <th>Sub Total</th>
                    </tr>
                </thead>
            </table>
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
        function getDetails(param) {
            console.log(param);
            const table = new DataTable('#detailsTable', {
                ajax: `/api/v1/transaksi/details/${param}`,
                columns: [
                    { data: 'id', title:'#',
                        render: function (data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                        } 
                    },
                    { data: 'jenis_barang' },
                    { data: 'nama' },
                    { data: 'qty', className: 'editable', render: $.fn.dataTable.render.number( '.', ',', 2, '' ) },
                    { data: 'terpakai', render: $.fn.dataTable.render.number( '.', ',', 2, '' ) },
                    { data: 'sisa', render: $.fn.dataTable.render.number( '.', ',', 2, '' ) },
                    { data: 'harga', render: $.fn.dataTable.render.number( '.', ',', 2, 'Rp' ) },
                    { data: 'sub_total', render: $.fn.dataTable.render.number( '.', ',', 2, 'Rp' ) }
                ]
            });

            // Enable inline editing
    // Make cells editable (using a simple approach for now)
    table.on('click', 'tbody td.editable', function() {
        var cell = $(this);
        var originalValue = cell.text();
        console.log(originalValue);

        // Create an input field for editing
        var input = $('<input type="text" value="' + originalValue + '">');
        cell.html(input);
        input.focus();

        // Handle saving the edit
        input.blur(function() {
            var newValue = input.val();
            if (newValue !== originalValue) {

                // Get the row and column data
                var rowData = table.row(cell.closest('tr')).data();
                var columnIndex = table.cell(cell).index().column;
                var columnData = table.settings().init().columns[columnIndex];
                
                // Send AJAX request to update the data
                $.ajax({
                    url: '/api/v1/stocks-edit', // Replace with your actual Laravel route
                    method: 'POST',
                    data: { 
                        // Include the row's ID or other identifiers
                        id: rowData.id, 
                        column: columnData.data, // Get the column's data property
                        value: newValue 
                    },
                    success: function(response) {
                        // Handle successful update
                        cell.text(newValue); 
                        toastr.success(response.message); 
                        table.ajax.reload();
                    },
                    error: function(error) {
                        // Handle errors
                        // cell.text(originalValue); 
                        // cell.data('originalValue', originalValue); // Store original value in data attribute
                        table.ajax.reload();
                        alert('Error updating value.'); 
                    }
                });
            }else if(newValue == ''){
                alert('Error value cannot blank'); 
                table.ajax.reload();

            }else {
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
          // console.log('tables destroy');
        }

        // Destroy DataTables on modal close
        // $('#kt_modal_pembelian_details').on('hidden.bs.modal', function () {
        //     // Destroy the DataTables instance
        //     window.LaravelDataTables['detailsTable'].destroy();
        //     console.log('tables destroy');
        // });
        
    </script>
  @endpush
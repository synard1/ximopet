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
        function getDetailPemakaian(param) {
            console.log(param);
            new DataTable('#detailsTable', {
                // ajax: `/api/v1/transaksi/pemakaian/details/${param}`,
                ajax: {
                    url: "/api/v1/transaksi", // Replace with your actual route
                    headers: {
                                'Authorization': 'Bearer ' + '{{ session('auth_token') }}',
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                    type: 'POST', // Use POST method
                    data: function (d) {
                        // Add your additional data here
                        d.task = 'READ';
                        d.type = 'detail';
                        d.jenis = 'Pemakaian';
                        d.id = param;
                    }
                },
                columns: [
                    { data: '#',
                        render: function (data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                        } 
                    },
                    { data: 'jenis_barang' },
                    { data: 'nama' },
                    { data: 'qty', render: $.fn.dataTable.render.number( '.', ',', 2, '' ) },
                    { data: 'terpakai', render: $.fn.dataTable.render.number( '.', ',', 2, '' ) },
                    { data: 'sisa', render: $.fn.dataTable.render.number( '.', ',', 2, '' ) },
                    { data: 'harga', render: $.fn.dataTable.render.number( '.', ',', 2, 'Rp' ) },
                    { data: 'sub_total', render: $.fn.dataTable.render.number( '.', ',', 2, 'Rp' ) }
                ]
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
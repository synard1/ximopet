<!-- Modal -->
<div class="modal fade" id="kt_modal_ternak_details" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="kt_modal_ternak_details_title">Detail Ternak</h1>
                {{-- <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button> --}}
            </div>
            <div class="modal-body">
                <table id="detailsTernakTable" class="display" style="width:100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Tanggal</th>
                            <th>Stok Awal</th>
                            <th>Jumlah Mati</th>
                            <th>Berat Mati</th>
                            <th>Stok Akhir</th>
                            <th>Penyebab</th>
                        </tr>
                    </thead>
                </table>
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
        // function getDetailsTernak(param) {
        //     // console.log(param);
        //     // Get the Sanctum token from the session
        //     const token = '{{ Session::get('auth_token') }}';

        //     // Set up headers with the token
        //     const headers = {
        //         'Authorization': `Bearer ${token}`,
        //         'Accept': 'application/json'
        //     };

        //     const table = new DataTable('#detailsTernakTable', {
        //         ajax: {
        //             url: `/api/v1/ternaks`,
        //             headers: headers,
        //             type: 'POST',
        //             data: function (d) {
        //                 d.roles = 'Operator';
        //                 d.task = 'READ';
        //                 d.type = 'Detail';
        //                 d.jenis = 'Mutasi';
        //                 d.id = param;
        //             },
        //             dataSrc: '' // Add this line to handle the array response
        //         },
        //         columns: [{
        //                 title: '#',
        //                 render: function(data, type, row, meta) {
        //                     return meta.row + meta.settings._iDisplayStart + 1;
        //                 }
        //             },
        //             {
        //                 data: 'tanggal',
        //                 render: function(data) {
        //                     return new Date(data).toLocaleDateString('en-GB');
        //                 }
        //             },
        //             {
        //                 data: 'stok_awal'
        //             },
        //             {
        //                 data: 'jumlah_mati'
        //             },
        //             {
        //                 data: 'berat_mati'
        //             },
        //             {
        //                 data: 'stok_akhir'
        //             },
        //             {
        //                 data: 'penyebab'
        //             }
        //         ]
        //     });
        // }

        function closeDetails() {
            var table = new DataTable('#detailsTernakTable');
            table.destroy();
            window.LaravelDataTables['ternaks-table'].ajax.reload();
        }
    </script>
@endpush

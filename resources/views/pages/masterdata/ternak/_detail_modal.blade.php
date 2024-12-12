<div class="modal fade" id="kt_modal_ternak_details" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bolder modal-title">Ternak Details</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body scroll-y mx-5 mx-xl-15 my-7">
                <div id="ternak_details_content">
                    <table class="table table-striped table-hover" id="detailTable">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                {{-- <th>Populasi Awal</th> --}}
                                <th>Ternak Mati</th>
                                <th>Ternak Afkir</th>
                                <th>Ternak Terjual</th>
                                {{-- <th>Sisa Ternak</th> --}}
                                <th>Pakan (Kg)</th>
                                <th>Obat (ml)</th>
                                <th>Vitamin (ml)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data will be populated dynamically -->
                        </tbody>
                    </table>
                    </div>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<!-- Include necessary DataTables scripts here -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    var modal = document.getElementById('kt_modal_ternak_details');
    var dataTable;
    // var dailyData;

    // Check if the modal element exists
    // if (modal) {
    //     modal.addEventListener('shown.bs.modal', function () {
    //         if ($.fn.DataTable.isDataTable('#detailTable')) {
    //             dataTable.destroy();
    //         }

    //         var tableBody = $('#detailTable tbody');
    //         tableBody.empty();

    //         // Get the data from PHP and parse it as JSON
    //         // var dailyData = @json($dailyData ?? []);
            
    //         // console.log(dailyData);


    //         // Populate the table with the data
    //         @if(isset($dailyData))

    //         console.log({{ $dailyData }});

    //             @foreach($dailyData as $data)
    //                 tableBody.append(`
    //                     <tr>
    //                         <td>{{ $data['tanggal'] }}</td>
    //                         <td>{{ $data['stok_awal'] }}</td>
    //                         <td>{{ $data['ternak_mati'] ?? 0 }}</td>
    //                         <td>{{ $data['ternak_afkir'] ?? 0 }}</td>
    //                         <td>{{ $data['ternak_terjual'] ?? 0 }}</td>
    //                         <td>{{ $data['stok_akhir'] }}</td>
    //                         <td>{{ $data['pakan_harian'] ?? 0 }}</td>
    //                         <td>{{ $data['obat_harian'] ?? 0 }}</td>
    //                         <td>{{ $data['vitamin_harian'] ?? 0 }}</td>
    //                     </tr>
    //                 `);
    //             @endforeach
    //         @endif

    //         // Initialize DataTable
    //         dataTable = $('#detailTable').DataTable({
    //             pageLength: 10,
    //             lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
    //             order: [[0, 'desc']],
    //             language: {
    //                 emptyTable: "Tidak ada data yang tersedia"
    //             },
    //             columnDefs: [
    //                 { targets: 0, type: 'date' },
    //                 { targets: '_all', type: 'num' }
    //             ],
    //             autoWidth: false,
    //             responsive: true,
    //             scrollX: true,
    //             dom: 'Bfrtip',
    //             buttons: ['copy', 'csv', 'excel', 'pdf', 'print']
    //         });
    //     });

    //     modal.addEventListener('hidden.bs.modal', function () {
    //         if (dataTable) {
    //             dataTable.destroy();
    //         }
    //     });
    // }
});
</script>
@endpush

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
                                <th>Pakan Nama</th>
                                <th>Pakan (Kg)</th>
                                <th>OVK (ml)</th>
                                {{-- <th>Aksi</th> --}}
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
});
</script>
@endpush
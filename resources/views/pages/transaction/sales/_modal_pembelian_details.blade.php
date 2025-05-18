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
        
        function closeDetails() {
            var table = new DataTable('#detailsTable');
            table.destroy();
            window.LaravelDataTables['pembelianStoks-table'].ajax.reload();
        }
    </script>
@endpush

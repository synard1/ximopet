<div class="modal fade" id="kt_modal_pembelian_doc_details" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="ki-duotone ki-note-2 fs-2 me-2"><i class="path1"></i><i class="path2"></i></i>
                    Detail Transaksi DOC
                </h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <table id="detailsTableDoc" class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Batch</th>
                            <th>Tanggal</th>
                            <th class="text-center">Jumlah</th>
                            <th class="text-center">Mati</th>
                            <th class="text-center">Sisa</th>
                            <th class="text-center">Berat (kg)</th>
                            <th class="text-center">Total Berat (kg)</th>
                            <th class="text-center">Harga</th>
                            <th class="text-center">Total</th>
                        </tr>
                    </thead>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-2"><i class="path1"></i><i class="path2"></i></i>
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let docTable;

function getDetailPembelianDOC(kelompokTernakId) {
    if ($.fn.DataTable.isDataTable('#detailsTableDoc')) {
        $('#detailsTableDoc').DataTable().destroy();
    }

    docTable = new DataTable('#detailsTableDoc', {
        keys: true,
        ajax: {
            url: "/api/v1/transaksi-ternak/details",
            type: 'POST',
            data: {kelompok_ternak_id: kelompokTernakId}
        },
        columns: [
            {data: null, render: (data, type, row, meta) => meta.row + 1},
            {
                data: 'kelompok_ternak.name',
                render: (data, type, row) => `
                    <div class="d-flex align-items-center">
                        <div>
                            <div class="fw-bold">${data || '-'}</div>
                            <small class="text-muted">${row.jenis_transaksi || '-'}</small>
                        </div>
                    </div>`
            },
            {data: 'tanggal', render: data => moment(data).format('DD/MM/YYYY')},
            {data: 'quantity', className: 'text-end', render: $.fn.dataTable.render.number(',', '.', 0)},
            {
                data: 'jumlah_mati', 
                className: 'text-end',
                render: data => data > 0 ? `<span class="text-danger">${$.fn.dataTable.render.number(',', '.', 0).display(data)}</span>` : '0'
            },
            {data: 'jumlah_akhir', className: 'text-end', render: $.fn.dataTable.render.number(',', '.', 0)},
            {data: 'berat_rata', className: 'text-end', render: $.fn.dataTable.render.number(',', '.', 2)},
            {data: 'berat_total', className: 'text-end', render: $.fn.dataTable.render.number(',', '.', 2)},
            {data: 'harga_satuan', className: 'text-end', render: $.fn.dataTable.render.number(',', '.', 0, 'Rp ')},
            {
                data: 'total_harga',
                className: 'text-end fw-bold',
                render: $.fn.dataTable.render.number(',', '.', 0, 'Rp ')
            }
        ],
        order: [[2, 'desc']],
        pageLength: 10,
        language: {
            "emptyTable": "Tidak ada data yang tersedia",
            "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            "infoEmpty": "Menampilkan 0 sampai 0 dari 0 data", 
            "infoFiltered": "(disaring dari _MAX_ total data)",
            "lengthMenu": "Tampilkan _MENU_ data per halaman",
            "loadingRecords": "Memuat...",
            "processing": "Memproses...",
            "search": "Cari:",
            "zeroRecords": "Tidak ditemukan data yang sesuai",
            "paginate": {
                "first": "Pertama",
                "last": "Terakhir",
                "next": "Selanjutnya", 
                "previous": "Sebelumnya"
            }
        },
        dom: 'Bfrtip',
        buttons: ['copy', 'excel', 'pdf', 'print']
    });
}

$('#kt_modal_pembelian_doc_details').on('hidden.bs.modal', () => {
    if ($.fn.DataTable.isDataTable('#detailsTableDoc')) {
        $('#detailsTableDoc').DataTable().destroy();
    }
});
</script>
@endpush
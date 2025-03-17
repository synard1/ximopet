<div>
    {{-- @if($isOpen)
    @endif --}}
    @include('livewire.transaksi._create_pembelian_stok2')

    @push('styles')
    <link href="https://cdn.datatables.net/2.1.2/css/dataTables.dataTables.css" rel="stylesheet" type="text/css" />
    @endpush

    @push('scripts')
    <script>
        function getDetailsPurchasing(param) {
            console.log(param);
            new DataTable('#itemsTable', {
                ajax: `/api/v1/transaksi/details/${param}`,
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

        function closeDetailsPurchasing() {
            var table = new DataTable('#itemsTable');
            table.destroy();
        }

        function checkNoSj() {
            return new Promise((resolve) => {
                var noSjInput = document.getElementById('noSjInput');
                if (noSjInput.value.trim() === '') {
                    Swal.fire({
                        title: 'Warning!',
                        text: 'No. SJ masih kosong. Apakah anda ingin melanjutkan?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, Simpan Data',
                        cancelButtonText: 'Tidak'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            resolve(true);
                        } else {
                            resolve(false);
                        }
                    });
                } else {
                    resolve(true);
                }
            });
        }

        // Modify the existing store button to call checkNoSj before submitting
        document.querySelector('button[wire\\:click="store()"]').addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            checkNoSj().then((shouldProceed) => {
                if (shouldProceed) {
                    Livewire.dispatch('store');
                }
            });
        });
        
    </script>
  @endpush
</div>

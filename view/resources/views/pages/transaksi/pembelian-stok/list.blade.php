<x-default-layout>

    @section('title')
        Data Pembelian Stok
    @endsection

    @section('breadcrumbs')
    @endsection
    <div class="card" id="stokTableCard">
        <!--begin::Card header-->
        <div class="card-header border-0 pt-6">
            <!--begin::Card title-->
            <div class="card-title">
                <!--begin::Search-->
                <div class="d-flex align-items-center position-relative my-1">
                    {!! getIcon('magnifier', 'fs-3 position-absolute ms-5') !!}
                    <input type="text" data-kt-user-table-filter="search" class="form-control form-control-solid w-250px ps-13" placeholder="Cari Data DOC" id="mySearchInput"/>
                </div>
                <!--end::Search-->
            </div>
            <!--end::Card title-->

            <!--begin::Card toolbar-->
            <div class="card-toolbar">
                <!--begin::Toolbar-->
                <div class="d-flex justify-content-end" data-kt-user-table-toolbar="base">
                    <!--begin::Add user-->
                    <button type="button" class="btn btn-primary" data-kt-button="create_new">
                        {!! getIcon('plus', 'fs-2', '', 'i') !!}
                        Tambah Data Pembelian
                    </button>
                    <!--end::Add user-->
                </div>
                <!--end::Toolbar-->

                <!--begin::Modal-->
                <livewire:master-data.kandang-list />
                <!--end::Modal-->
            </div>
            <!--end::Card toolbar-->
        </div>
        <!--end::Card header-->

        <!--begin::Card body-->
        <div class="card-body py-4">
            <!--begin::Table-->
                {{ $dataTable->table() }}
            <!--end::Table-->
        </div>
        <!--end::Card body-->
    </div>

    <livewire:transaksi.pembelian-list />
    @include('pages.transaksi.pembelian-stok._modal_pembelian_details')

    @push('scripts')
        {{ $dataTable->scripts() }}
        <script>
            document.querySelectorAll('[data-kt-button="create_new"]').forEach(function (element) {
                element.addEventListener('click', function () {
                    // Simulate delete request -- for demo purpose only
                    Swal.fire({
                        html: `Preparing Form`,
                        icon: "info",
                        buttonsStyling: false,
                        showConfirmButton: false,
                        timer: 2000
                    }).then(function () {

                        $('#supplierDropdown').select2();

                        Livewire.on('reinitialize-select2', function () {
                            $('.select2').select2();
                        });

                        console.log('form loaded');
                        Livewire.dispatch('createPembelian');

                        const cardList = document.getElementById(`stokTableCard`);
                        cardList.style.display = 'none';
                        // cardList.classList.toggle('d-none');

                        const cardForm = document.getElementById(`stokFormCard`);
                        cardForm.style.display = 'block';
                        // cardList.classList.toggle('d-none');
                        // fetchFarm();

                    });
                    
                });

            });

            document.addEventListener('livewire:init', function () {
                    Livewire.on('closeForm', function () {
                        showLoadingSpinner();
                        const cardList = document.getElementById(`stokTableCard`);
                        cardList.style.display = 'block';

                        const cardForm = document.getElementById(`stokFormCard`);
                        cardForm.style.display = 'none';

                        // Reload DataTables
                        $('.table').each(function() {
                            if ($.fn.DataTable.isDataTable(this)) {
                                $(this).DataTable().ajax.reload();
                            }
                        });

                        
                    });
                });
            
            
            // Initialize the search functionality
            document.getElementById('mySearchInput').addEventListener('keyup', function () {
                // Replace 'pembelianStok-table' with your actual DataTable ID if different
                window.LaravelDataTables['pembelianStok-table'].search(this.value).draw();
            });

            document.addEventListener('livewire:init', function () {
                Livewire.on('success', function () {
                    $('#kt_modal_add_user').modal('hide');
                    // Replace 'pembelianStok-table' with your actual DataTable ID if different
                    window.LaravelDataTables['pembelianStok-table'].ajax.reload();
                });
            });
        </script>
    @endpush
</x-default-layout>

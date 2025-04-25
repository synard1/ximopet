<x-default-layout>

    @section('title')
        Mutasi Pakan
    @endsection

    @section('breadcrumbs')
    @endsection
    <div class="card" id="tableCard">
        <!--begin::Card header-->
        <div class="card-header border-0 pt-6">
            <!--begin::Card title-->
            <div class="card-title">
                {{-- <!--begin::Search-->
                <div class="d-flex align-items-center position-relative my-1">
                    {!! getIcon('magnifier', 'fs-3 position-absolute ms-5') !!}
                    <input type="text" data-kt-user-table-filter="search" class="form-control form-control-solid w-250px ps-13" placeholder="Cari Data Pembelian" id="mySearchInput"/>
                </div> --}}
                <!--end::Search-->
            </div>
            <!--begin::Card title-->
            <!--begin::Card toolbar-->
            <div class="card-toolbar">
                <!--begin::Toolbar-->
                <div class="d-flex justify-content-end" data-kt-user-table-toolbar="base">
                    <!--begin::Add user-->
                    <button type="button" class="btn btn-primary" data-kt-button="create_new">
                        {!! getIcon('plus', 'fs-2', '', 'i') !!}
                        Tambah Data
                    </button>
                    <!--end::Add user-->
                </div>
                <!--end::Toolbar-->
            </div>
            <!--end::Card toolbar-->

        </div>
        

        <div class="card-body">
            <div class="table-responsive">
                {{ $dataTable->table() }}
            </div>

        </div>
    </div>
    <div class="card" id="formCard" style="display: none;">
        <div class="card-body">
            <livewire:feed-mutations.create />

        </div>
    </div>

        

    @push('scripts')
    {{ $dataTable->scripts() }}

    <script>
        document.querySelectorAll('[data-kt-button="create_new"]').forEach(function (element) {
            element.addEventListener('click', function () {
                Swal.fire({
                    html: `Preparing Form`,
                    icon: "info",
                    buttonsStyling: false,
                    showConfirmButton: false,
                    timer: 2000
                }).then(function () {

                    const cardList = document.getElementById(`tableCard`);
                    cardList.style.display = 'none';

                    const cardForm = document.getElementById(`formCard`);
                    cardForm.style.display = 'block';

                });
                
            });
        });

        document.addEventListener('livewire:init', function () {
            Livewire.on('closeForm', function () {
                showLoadingSpinner();
                const cardList = document.getElementById(`tableCard`);
                cardList.style.display = 'block';

                const cardForm = document.getElementById(`formCard`);
                cardForm.style.display = 'none';

                // Reload DataTables
                $('.table').each(function() {
                    if ($.fn.DataTable.isDataTable(this)) {
                        $(this).DataTable().ajax.reload();
                    }
                });

                
            });
        });

    </script>


    @endpush
</x-default-layout>
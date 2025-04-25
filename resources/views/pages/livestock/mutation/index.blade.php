<x-default-layout>

    @section('title')
        Mutasi Ayam
    @endsection

    @section('breadcrumbs')
    @endsection
    <div class="card" id="cardTable">
        <!--begin::Card header-->
        <div class="card-header border-0 pt-6">
            <!--begin::Card title-->
            <div class="card-title">
                {{-- <!--begin::Search-->
                <div class="d-flex align-items-center position-relative my-1">
                    {!! getIcon('magnifier', 'fs-3 position-absolute ms-5') !!}
                    <input type="text" data-kt-user-table-filter="search" class="form-control form-control-solid w-250px ps-13" placeholder="Cari Data" id="searchCompany"/>
                </div>
                <!--end::Search--> --}}
            </div>
            <!--begin::Card title-->

            <div class="card-toolbar">
                <div class="d-flex justify-content-end" data-kt-user-table-toolbar="base">
                    <button type="button" class="btn btn-primary" data-kt-button="kt_new_mutation">
                        {!! getIcon('plus', 'fs-2', '', 'i') !!}
                        Tambah Data
                    </button>
                </div>
            </div>

        </div>
        <!--end::Card header-->

        <!--begin::Card body-->
        <div class="card-body py-4">
            <!--begin::Table-->
            <div class="table-responsive">
                {{ $dataTable->table() }}
            </div>
            <!--end::Table-->
        </div>
        <!--end::Card body-->
    </div>

    <livewire:livestock.mutation.mutation-form />


    @push('scripts')
        {{ $dataTable->scripts() }}
        <script>
            document.querySelectorAll('[data-kt-button="kt_new_mutation"]').forEach(function (element) {
                element.addEventListener('click', function () {
                    // Simulate delete request -- for demo purpose only
                    Swal.fire({
                        html: `Preparing Form`,
                        icon: "info",
                        buttonsStyling: false,
                        showConfirmButton: false,
                        timer: 2000
                    }).then(function () {

                        // $('#supplierDropdown').select2();

                        // Livewire.on('reinitialize-select2', function () {
                        //     $('.select2').select2();
                        // });

                        // console.log('form loaded');
                        // Livewire.dispatch('createPembelian');

                        const cardList = document.getElementById(`cardTable`);
                        cardList.style.display = 'none';
                        // cardList.classList.toggle('d-none');

                        const cardForm = document.getElementById(`cardForm`);
                        cardForm.style.display = 'block';
                        // cardList.classList.toggle('d-none');
                        // fetchFarm();

                    });
                    
                });

            });

        document.addEventListener('livewire:init', function () {
            Livewire.on('closeForm', function () {
                showLoadingSpinner();
                const cardList = document.getElementById(`cardTable`);
                cardList.style.display = 'block';

                const cardForm = document.getElementById(`cardForm`);
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


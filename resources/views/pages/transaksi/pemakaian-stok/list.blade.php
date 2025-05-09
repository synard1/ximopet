<x-default-layout>

    @section('title')
        Data Pemakaian Stok
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
                    <input type="text" data-kt-user-table-filter="search" class="form-control form-control-solid w-250px ps-13" placeholder="Cari Transaksi" id="mySearchInput"/>
                </div>
                <!--end::Search-->
            </div>
            <!--begin::Card title-->

            @can('create transaction')
            <!--begin::Card toolbar-->
            <div class="card-toolbar">
                <!--begin::Toolbar-->
                <div class="d-flex justify-content-end" data-kt-user-table-toolbar="base">
                    <!--begin::Add user-->
                    <button type="button" class="btn btn-primary" data-kt-button="new_use_stok">
                        {!! getIcon('plus', 'fs-2', '', 'i') !!}
                        Tambah Data Pemakaian
                    </button>
                    <!--end::Add user-->
                </div>
                <!--end::Toolbar-->
            </div>
            <!--end::Card toolbar-->
                
            @endcan
            

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

    <livewire:transaksi.pemakaian-stok />
    @include('pages.transaksi.pemakaian-stok._modal_pemakaian_details')


    @push('scripts')
        {{ $dataTable->scripts() }}
        <script>
            document.getElementById('mySearchInput').addEventListener('keyup', function () {
                window.LaravelDataTables['pemakaianStoks-table'].search(this.value).draw();
            });
            document.addEventListener('livewire:init', function () {
                Livewire.on('closeFormPemakaian', function () {
                    showLoadingSpinner();
                    const cardList = document.getElementById(`stokTableCard`);
                    cardList.style.display = 'block';

                    const cardForm = document.getElementById(`pemakaianStokFormCard`);
                    cardForm.style.display = 'none';

                    // Reload DataTables
                    $('.table').each(function() {
                        if ($.fn.DataTable.isDataTable(this)) {
                            $(this).DataTable().ajax.reload();
                        }
                    });

                    
                });
                // Livewire.on('success', function () {
                //     $('#kt_modal_add_user').modal('hide');
                //     window.LaravelDataTables['pemakaianStoks-table'].ajax.reload();
                // });

                // Livewire.on('closeFormPemakaian', function () {
                //     showLoadingSpinner();
                //     const cardList = document.getElementById(`stokTableCard`);
                //     cardList.style.display = 'block';

                //     const cardForm = document.getElementById(`pemakaianStokFormCard`);
                //     cardForm.style.display = 'none';

                //     const element = document.getElementById('pemakaianStokFormCard');
                //     const form = element.querySelector('#kt_pemakaian_stok_form');
                //     form.reset(); // Reset form	

                //     // Assuming you have a jQuery reference to your select element
                //     // const $selectedFarm = $('#selectedFarm');

                //     // Reset the selected option to the first one (usually the placeholder)
                //     // $('#selectedFarm').val('');
                //     // Assuming you've initialized Select2 on the element with id 'selectedFarm'
                //     $('#selectedFarm').val(null).trigger('change.select2'); 
                //     $('#kandangs').val(null).trigger('change.select2'); 

                //     const updateArea = $('#formDiva'); 
                //     const saveChangesButton = document.getElementById('saveChangesButton');

                //     updateArea.addClass('grey-block'); 

                //     // Disable the button
                //     saveChangesButton.disabled = true;

                //     // Reload DataTables
                //     $('.table').each(function() {
                //         if ($.fn.DataTable.isDataTable(this)) {
                //             $(this).DataTable().ajax.reload();
                //         }
                //     });

                    
                // });
            });
            // $('#kt_modal_add_user').on('hidden.bs.modal', function () {
            //     Livewire.dispatch('new_user');
            // });

            document.querySelectorAll('[data-kt-button="new_use_stok"]').forEach(function (element) {
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

                        // Livewire.on('reinitialize-select2-pemakaianStok', function () {
                        //     // updateDropdowns();
                        //     console.log('test update dropdown');
                        //     // $('#itemsSelect').select2();
                            
                        // });

                        // console.log('form loaded');
                        Livewire.dispatch('createPemakaianStok');

                        const cardList = document.getElementById(`stokTableCard`);
                        cardList.style.display = 'none';
                        // cardList.classList.toggle('d-none');

                        const cardForm = document.getElementById(`pemakaianStokFormCard`);
                        cardForm.style.display = 'block';
                        // cardList.classList.toggle('d-none');
                        // fetchFarm();

                    });
                    
                });

            });
        </script>
    @endpush
</x-default-layout>


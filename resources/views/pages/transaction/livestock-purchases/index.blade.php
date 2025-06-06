<x-default-layout>

    @section('title')
    Data Pembelian Ayam
    @endsection

    @section('breadcrumbs')
    @endsection
    <div class="card" id="stokTableCard">
        <!--begin::Card header-->
        <div class="card-header border-0 pt-6">
            <!--begin::Card title-->
            <div class="card-title">
            </div>
            <!--begin::Card title-->

            <!--begin::Card toolbar-->
            <div class="card-toolbar" id="cardToolbar">
                <!--begin::Toolbar-->
                <div class="d-flex justify-content-end" data-kt-user-table-toolbar="base">
                    <!--begin::Filter-->
                    <button type="button" class="btn btn-light-primary me-3" data-kt-menu-trigger="click"
                        data-kt-menu-placement="bottom-end">
                        <i class="ki-outline ki-filter fs-2"></i>Filter</button>
                    <!--begin::Menu 1-->
                    <div class="menu menu-sub menu-sub-dropdown w-300px w-md-325px" data-kt-menu="true">
                        <!--begin::Header-->
                        <div class="px-7 py-5">
                            <div class="fs-5 text-gray-900 fw-bold">Filter Options</div>
                        </div>
                        <!--end::Header-->
                        <!--begin::Separator-->
                        <div class="separator border-gray-200"></div>
                        <!--end::Separator-->
                        <!--begin::Content-->
                        <div class="px-7 py-5" data-kt-subscription-table-filter="form">
                            <!--begin::Input group-->
                            <div class="mb-10">
                                <label class="form-label fs-6 fw-semibold">Month:</label>
                                <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                    data-placeholder="Select option" data-allow-clear="true"
                                    data-kt-subscription-table-filter="month" data-hide-search="true">
                                    <option></option>
                                    <option value="jan">January</option>
                                    <option value="feb">February</option>
                                    <option value="mar">March</option>
                                    <option value="apr">April</option>
                                    <option value="may">May</option>
                                    <option value="jun">June</option>
                                    <option value="jul">July</option>
                                    <option value="aug">August</option>
                                    <option value="sep">September</option>
                                    <option value="oct">October</option>
                                    <option value="nov">November</option>
                                    <option value="dec">December</option>
                                </select>
                            </div>
                            <!--end::Input group-->
                            <!--begin::Input group-->
                            <div class="mb-10">
                                <label class="form-label fs-6 fw-semibold">Status:</label>
                                <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                    data-placeholder="Select option" data-allow-clear="true"
                                    data-kt-subscription-table-filter="status" data-hide-search="true">
                                    <option></option>
                                    <option value="Active">Active</option>
                                    <option value="Expiring">Expiring</option>
                                    <option value="Suspended">Suspended</option>
                                </select>
                            </div>
                            <!--end::Input group-->
                            <!--begin::Input group-->
                            <div class="mb-10">
                                <label class="form-label fs-6 fw-semibold">Billing Method:</label>
                                <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                    data-placeholder="Select option" data-allow-clear="true"
                                    data-kt-subscription-table-filter="billing" data-hide-search="true">
                                    <option></option>
                                    <option value="Auto-debit">Auto-debit</option>
                                    <option value="Manual - Credit Card">Manual - Credit Card</option>
                                    <option value="Manual - Cash">Manual - Cash</option>
                                    <option value="Manual - Paypal">Manual - Paypal</option>
                                </select>
                            </div>
                            <!--end::Input group-->
                            <!--begin::Input group-->
                            <div class="mb-10">
                                <label class="form-label fs-6 fw-semibold">Product:</label>
                                <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                    data-placeholder="Select option" data-allow-clear="true"
                                    data-kt-subscription-table-filter="product" data-hide-search="true">
                                    <option></option>
                                    <option value="Basic">Basic</option>
                                    <option value="Basic Bundle">Basic Bundle</option>
                                    <option value="Teams">Teams</option>
                                    <option value="Teams Bundle">Teams Bundle</option>
                                    <option value="Enterprise">Enterprise</option>
                                    <option value=" Enterprise Bundle">Enterprise Bundle</option>
                                </select>
                            </div>
                            <!--end::Input group-->
                            <!--begin::Actions-->
                            <div class="d-flex justify-content-end">
                                <button type="reset"
                                    class="btn btn-light btn-active-light-primary fw-semibold me-2 px-6"
                                    data-kt-menu-dismiss="true" data-kt-subscription-table-filter="reset">Reset</button>
                                <button type="submit" class="btn btn-primary fw-semibold px-6"
                                    data-kt-menu-dismiss="true"
                                    data-kt-subscription-table-filter="filter">Apply</button>
                            </div>
                            <!--end::Actions-->
                        </div>
                        <!--end::Content-->
                    </div>
                    <!--end::Menu 1-->
                    <!--end::Filter-->
                    <!--begin::Add user-->
                    <button type="button" class="btn btn-primary" onclick="Livewire.dispatch('showCreateForm')">
                        {!! getIcon('plus', 'fs-2', '', 'i') !!}
                        Tambah Data Pembelian Ayam
                    </button>
                    <!--end::Add user-->
                </div>
                <!--end::Toolbar-->
            </div>
            <!--end::Card toolbar-->

        </div>
        <!--end::Card header-->

        <!--begin::Card body-->
        <div class="card-body py-4">
            <div id="datatable-container">
                <!--begin::Table-->
                <div class="table-responsive">
                    {{ $dataTable->table() }}
                </div>
                <!--end::Table-->
            </div>
            <livewire:livestock-purchase.create />


        </div>
        <!--end::Card body-->
    </div>

    {{--
    <livewire:transaksi.pembelian-list /> --}}
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

                    // $('#supplierDropdown').select2();

                    // Livewire.on('reinitialize-select2', function () {
                    //     $('.select2').select2();
                    // });

                    // console.log('form loaded');
                    // Livewire.dispatch('createPembelian');

                    const cardList = document.getElementById(`stokTableCard`);
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
            window.addEventListener('hide-datatable', () => {
                $('#datatable-container').hide();
                $('#cardToolbar').hide();
            });

            window.addEventListener('show-datatable', () => {
                $('#datatable-container').show();
                $('#cardToolbar').show();
            });

            // Livewire.on('closeForm', function () {
            //     showLoadingSpinner();
            //     const cardList = document.getElementById(`stokTableCard`);
            //     cardList.style.display = 'block';

            //     const cardForm = document.getElementById(`cardForm`);
            //     cardForm.style.display = 'none';

            //     // Reload DataTables
            //     $('.table').each(function() {
            //         if ($.fn.DataTable.isDataTable(this)) {
            //             $(this).DataTable().ajax.reload();
            //         }
            //     });
            // });

            Livewire.on('showForm', function () {
                // Show the form card
                const cardForm = document.getElementById('cardForm');
                if (cardForm) {
                    cardForm.style.display = 'block';
                    console.log('form ada');
                    
                }
            });
        });
        
        
            // document.getElementById('mySearchInput').addEventListener('keyup', function () {
            //     window.LaravelDataTables['pembelianStoks-table'].search(this.value).draw();
            // });
            // document.addEventListener('livewire:init', function () {
            //     Livewire.on('success', function () {
            //         $('#kt_modal_add_user').modal('hide');
            //         window.LaravelDataTables['kandangs-table'].ajax.reload();
            //     });
            // });

            // Fix for printable false not working
            // $(document).ready(function() {
            //     window.LaravelDataTables['pembelianStoks-table'].on('preXhr.dt', function(e, settings, data) {
            //         data.columns = settings.aoColumns.map(function(col, index) {
            //             return {
            //                 data: col.data,
            //                 name: col.name,
            //                 searchable: col.searchable,
            //                 orderable: col.orderable,
            //                 search: {value: "", regex: false}
            //             };
            //         }).filter(function(col) {
            //             return col.data !== 'action';
            //         });
            //     });
            // });

        $(document).ready(function() {
            let table = $('#livestock-purchases-table').DataTable();
            // Tambahkan baris filter jika belum ada
            if ($('#livestock-purchases-table thead tr').length < 2) {
                let filterRow = '<tr>';
                $('#livestock-purchases-table thead th').each(function (i) {
                    let colTitle = $(this).text().trim();
                    if (colTitle === 'Farm') {
                        filterRow += '<th><input type="text" placeholder="Cari Farm" class="form-control form-control-sm column-search" data-column="'+i+'" /></th>';
                    } else if (colTitle === 'Kandang') {
                        filterRow += '<th><input type="text" placeholder="Cari Kandang" class="form-control form-control-sm column-search" data-column="'+i+'" /></th>';
                    } else {
                        filterRow += '<th></th>';
                    }
                });
                filterRow += '</tr>';
                $('#livestock-purchases-table thead').append(filterRow);
            }
            // Event filter per kolom
            $('#livestock-purchases-table').on('keyup change', '.column-search', function() {
                let i = $(this).data('column');
                table.column(i).search(this.value).draw();
            });
        });
    </script>
    @endpush
</x-default-layout>
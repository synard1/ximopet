<x-default-layout>
    @section('title')
    Company Management 11
    @endsection

    {{-- @section('breadcrumbs')
    <li class="breadcrumb-item text-muted">Company</li>
    <li class="breadcrumb-item text-dark">List</li>
    @endsection --}}

    <div class="card" id="companyTableCard">
        <!--begin::Card header-->
        <div class="card-header border-0 pt-6">
            <!--begin::Card title-->
            <div class="card-title">
                {{--
                <!--begin::Search-->
                <div class="d-flex align-items-center position-relative my-1">
                    {!! getIcon('magnifier', 'fs-3 position-absolute ms-5') !!}
                    <input type="text" data-kt-user-table-filter="search"
                        class="form-control form-control-solid w-250px ps-13" placeholder="Search Company"
                        id="mySearchInput" />
                </div>
                <!--end::Search--> --}}
            </div>
            <!--end::Card title-->

            <!--begin::Card toolbar-->
            <div class="card-toolbar">
                <!--begin::Toolbar-->
                <div class="d-flex justify-content-end" data-kt-user-table-toolbar="base">
                    @if(auth()->user()->hasRole('SuperAdmin'))
                    <button type="button" class="btn btn-primary" data-kt-button="create_new">
                        {!! getIcon('plus', 'fs-2', '', 'i') !!}
                        Add New Company
                    </button>
                    @endif
                </div>
                <!--end::Toolbar-->
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

    <livewire:company.company-form />
    <livewire:superadmin.company-permission-manager />
    {{--
    <livewire:company.company-admin-management /> --}}

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
                        Livewire.dispatch('createCompany');
                        const cardList = document.getElementById(`companyTableCard`);
                        cardList.style.display = 'none';
                        const cardForm = document.getElementById(`companyFormCard`);
                        cardForm.style.display = 'block';
                    });
                });
            });

            document.addEventListener('livewire:init', function () {
                Livewire.on('closeForm', function () {
                    showLoadingSpinner();
                    const cardList = document.getElementById(`companyTableCard`);
                    cardList.style.display = 'block';
                    const cardForm = document.getElementById(`companyFormCard`);
                    cardForm.style.display = 'none';
                    
                    // Reload DataTables
                    $('.table').each(function() {
                        if ($.fn.DataTable.isDataTable(this)) {
                            $(this).DataTable().ajax.reload();
                        }
                    });
                });

                Livewire.on('closePanel', function () {
                    showLoadingSpinner();
                    const cardList = document.getElementById(`companyTableCard`);
                    cardList.style.display = 'block';
                    const cardPermission = document.getElementById(`companyPermissionCard`);
                    cardPermission.style.display = 'none';
                });
            });
            
            // Initialize the search functionality
            // document.getElementById('mySearchInput').addEventListener('keyup', function () {
            //     window.LaravelDataTables['company-table'].search(this.value).draw();
            // });

            document.addEventListener('livewire:init', function () {
                Livewire.on('success', function () {
                    $('#kt_modal_add_user').modal('hide');
                    window.LaravelDataTables['company-table'].ajax.reload();
                });
            });
    </script>
    @endpush
</x-default-layout>
{{--
Refactor: Show company details as editable form for company admin, inspired by Metronic8 demo60 account/settings layout.
Date: 2024-06-14 00:00:00
By: AI Assistant
--}}
<x-default-layout>
    @section('title')
    Company Management
    @endsection

    {{-- @section('breadcrumbs')
    <li class="breadcrumb-item text-muted">Company</li>
    <li class="breadcrumb-item text-dark">List</li>
    @endsection --}}

    @if($isCompanyAdmin)
    {{-- (auth()->user()->hasRole('SuperAdmin') && $company) ||
    ($isCompanyAdmin && $company && auth()->user()->company_id == $company->id)
    ) --}}
    <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x mb-5 fs-6">
        <li class="nav-item">
            <a class="nav-link active" data-bs-toggle="tab" href="#kt_tab_pane_overview">Overview</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#kt_tab_pane_settings">Settings</a>
        </li>
        {{-- <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button"
                aria-expanded="false">Dropdown</a>
            <ul class="dropdown-menu">
                <li><a class="nav-link dropdown-item" data-bs-toggle="tab" href="#kt_tab_pane_10">Action</a></li>
            </ul>
        </li> --}}
    </ul>

    <div class="tab-content" id="myTabContent">
        <div class="tab-pane fade show active" id="kt_tab_pane_overview" role="tabpanel">
            <div class="card mb-5 mb-xl-10">
                <div class="card-header border-0 pt-9">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="fw-bolder text-gray-900 mb-2">Profile Details</h3>
                        <button type="button" class="btn btn-sm btn-light-primary" id="editBtn">
                            <i class="bi bi-pencil-fill"></i> Edit
                        </button>
                    </div>
                </div>
                <div class="card-body p-9">
                    <form class="form" id="companyProfileForm" enctype="multipart/form-data" method="POST"
                        action="{{ route('setting.companies.update', $company->id) }}">
                        @csrf
                        <div class="d-flex flex-row align-items-start mb-7">
                            <div class="flex-grow-1">
                                <div class="row mb-7">
                                    <label class="col-lg-2 fw-semibold text-muted">Company Name <span
                                            class="text-danger">*</span></label>
                                    <div class="col-lg-10">
                                        <input type="text" name="name" class="form-control form-control-solid"
                                            value="{{ $company->name }}" readonly />
                                    </div>
                                </div>
                                {{-- <div class="row mb-7">
                                    <label class="col-lg-2 fw-semibold text-muted">Domain</label>
                                    <div class="col-lg-10">
                                        <input type="text" name="domain" class="form-control form-control-solid"
                                            value="{{ $company->domain }}" readonly />
                                    </div>
                                </div> --}}
                                <div class="row mb-7">
                                    <label class="col-lg-2 fw-semibold text-muted">Email</label>
                                    <div class="col-lg-10">
                                        <input type="email" name="email" class="form-control form-control-solid"
                                            value="{{ $company->email }}" readonly />
                                    </div>
                                </div>
                                <div class="row mb-7">
                                    <label class="col-lg-2 fw-semibold text-muted">Phone</label>
                                    <div class="col-lg-10">
                                        <input type="text" name="phone" class="form-control form-control-solid"
                                            value="{{ $company->phone }}" readonly />
                                    </div>
                                </div>
                                <div class="row mb-7">
                                    <label class="col-lg-2 fw-semibold text-muted">Address</label>
                                    <div class="col-lg-10">
                                        <input type="text" name="address" class="form-control form-control-solid"
                                            value="{{ $company->address }}" readonly />
                                    </div>
                                </div>
                                <div class="row mb-7">
                                    <label class="col-lg-2 fw-semibold text-muted">Status</label>
                                    <div class="col-lg-10">
                                        <input type="text" name="status" class="form-control form-control-solid"
                                            value="{{ ucfirst($company->status) }}" readonly />
                                    </div>
                                </div>
                            </div>
                            <div class="ms-5 d-flex flex-column align-items-center">
                                @if($company && $company->logo_url)
                                <img id="companyLogoPreview" src="{{ $company->logo_url }}" alt="Company Logo"
                                    style="max-width:120px;max-height:120px;border-radius:8px;object-fit:contain;">
                                @else
                                <div id="companyLogoPreviewContainer"
                                    class="border rounded bg-light d-flex align-items-center justify-content-center"
                                    style="width:120px;height:120px;">
                                    <span class="text-muted">No Logo</span>
                                </div>
                                @endif
                                <div class="mt-2 d-none" id="logoInputRow">
                                    <input type="file" name="logo" class="form-control" accept="image/*"
                                        id="logoInput" />
                                </div>
                            </div>
                        </div>

                        <div class="row mb-7 d-none" id="saveButtons">
                            <div class="col-lg-10 offset-lg-2">
                                <button type="submit" class="btn btn-primary me-3">
                                    <i class="bi bi-check2"></i> Save Changes
                                </button>
                                <button type="button" class="btn btn-light" id="cancelBtn">
                                    <i class="bi bi-x"></i> Cancel
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="tab-pane fade" id="kt_tab_pane_settings" role="tabpanel">
            @if($company)
            <livewire:company.company-settings :company="$company" />
            @endif
        </div>
        <div class="tab-pane fade" id="kt_tab_pane_user" role="tabpanel">
            ...
        </div>
        <div class="tab-pane fade" id="kt_tab_pane_10" role="tabpanel">
            ... aaa
        </div>
    </div>
    <livewire:company.company-user-mapping-form />


    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const editBtn = document.getElementById('editBtn');
            const cancelBtn = document.getElementById('cancelBtn');
            const saveButtons = document.getElementById('saveButtons');
            const form = document.getElementById('companyProfileForm');
            const inputs = form.querySelectorAll('input:not([type="hidden"])');
            const logoInputRow = document.getElementById('logoInputRow');

            editBtn.addEventListener('click', function() {
                inputs.forEach(input => {
                    if (input.name !== 'name' && input.name !== 'domain' && input.name !== 'status') {
                        input.removeAttribute('readonly');
                    }
                });
                saveButtons.classList.remove('d-none');
                editBtn.classList.add('d-none');
                // Tampilkan input logo
                logoInputRow.classList.remove('d-none');
            });

            cancelBtn.addEventListener('click', function() {
                inputs.forEach(input => {
                    input.setAttribute('readonly', true);
                });
                saveButtons.classList.add('d-none');
                editBtn.classList.remove('d-none');
                // Sembunyikan input logo
                logoInputRow.classList.add('d-none');
                form.reset();
            });

            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(form);
                
                // Add credentials to ensure cookies are sent
                fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin', // Add this line
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                        'Accept': 'application/json' // Add this line
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: data.message || 'Company profile updated successfully!'
                        });
                        // Set all inputs to readonly again
                        inputs.forEach(input => input.setAttribute('readonly', true));
                        saveButtons.classList.add('d-none');
                        editBtn.classList.remove('d-none');
                        // Sembunyikan input logo
                        document.getElementById('logoInputRow').classList.add('d-none');
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Failed to update company profile.'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to update company profile. Please try again.'
                    });
                });
            });

            document.getElementById('logoInput').addEventListener('change', function(e) {
                const [file] = e.target.files;
                if (file) {
                    const img = document.querySelector('img[alt=\"Company Logo\"]');
                    img.src = URL.createObjectURL(file);
                }
            });
        });
    </script>
    @endpush
    @else
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
            <div class="card-toolbar" id="cardToolbar">
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
            <div class="table-responsive" id="datatable-container">
                <!--begin::Table-->
                {{ $dataTable->table() }}
                <!--end::Table-->

            </div>
            <!--begin::Form User Mapping-->
            <livewire:company.company-user-mapping-form />
            <!--end::Form User Mapping-->

            <livewire:company.company-admin-management />
        </div>
        <!--end::Card body-->
    </div>
    @endif

    <livewire:company.company-form />

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

    <script>
        document.addEventListener('livewire:init', function () {
                window.addEventListener('hide-datatable', () => {
                    $('#datatable-container').hide();
                    $('#cardToolbar').hide();
                });

                window.addEventListener('show-datatable', () => {
                    $('#datatable-container').show();
                    $('#cardToolbar').show();
                });
                
                // Add event listener for closeMapping event
                Livewire.on('closeMapping', () => {
                    console.log('closeMapping event received');
                });
                
            });
    </script>
    @endpush
</x-default-layout>
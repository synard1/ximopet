<x-default-layout>

    @section('title')
    Roles
    @endsection

    @section('breadcrumbs')
    {{ Breadcrumbs::render('user-management.roles.index') }}
    @endsection

    <!--begin::Content container-->
    <div id="kt_app_content_container" class="app-container container-xxl">
        <!--begin::Card-->
        <div class="card">
            @if(auth()->user()->hasRole('SuperAdmin'))
            <!--begin::Card header-->
            <div class="card-header border-0 pt-6">
                <!--begin::Card title-->
                <div class="card-title">
                    <div class="d-flex align-items-center position-relative my-1">
                        <span class="svg-icon svg-icon-1 position-absolute ms-6">
                            <i class="ki-duotone ki-magnifier fs-3">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                        </span>
                        <input type="text" data-kt-roles-table-filter="search"
                            class="form-control form-control-solid w-250px ps-14" placeholder="Search Roles" />
                    </div>
                </div>
                <!--begin::Card title-->

                <!--begin::Card toolbar-->
                <div class="card-toolbar">
                    <!--begin::Toolbar-->
                    <div class="d-flex justify-content-end" data-kt-roles-table-toolbar="base">
                        <!--begin::Add role-->
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                            data-bs-target="#kt_modal_add_role">
                            <i class="ki-duotone ki-plus fs-2"></i>
                            Add Role
                        </button>
                        <!--end::Add role-->

                        <!--begin::Backup roles-->
                        <button type="button" class="btn btn-light-primary ms-2" id="backup-roles-button">
                            <i class="ki-duotone ki-save fs-2"></i>
                            Backup Roles
                        </button>
                        <!--end::Backup roles-->
                    </div>
                    <!--end::Toolbar-->
                </div>
                <!--end::Card toolbar-->
            </div>
            <!--end::Card header-->
            @endif
            <!--begin::Card body-->
            <div class="card-body py-4">
                <livewire:permission.role-list></livewire:permission.role-list>
            </div>
            <!--end::Card body-->
        </div>
        <!--end::Card-->
    </div>
    <!--end::Content container-->

    <!--begin::Modal-->
    <livewire:permission.role-modal></livewire:permission.role-modal>
    <!--end::Modal-->

    @push('scripts')
    <script>
        $(document).ready(function() {
            // Handle backup button click
            $('#backup-roles-button').on('click', function() {
                $.ajax({
                    url: '{{ route("roles.backup") }}',
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                text: response.message,
                                icon: "success",
                                buttonsStyling: false,
                                confirmButtonText: "Ok, got it!",
                                customClass: {
                                    confirmButton: "btn btn-primary"
                                }
                            });
                        } else {
                            Swal.fire({
                                text: response.message,
                                icon: "error",
                                buttonsStyling: false,
                                confirmButtonText: "Ok, got it!",
                                customClass: {
                                    confirmButton: "btn btn-primary"
                                }
                            });
                        }
                    },
                    error: function(xhr) {
                        Swal.fire({
                            text: "Failed to create backup. Please try again.",
                            icon: "error",
                            buttonsStyling: false,
                            confirmButtonText: "Ok, got it!",
                            customClass: {
                                confirmButton: "btn btn-primary"
                            }
                        });
                    }
                });
            });
        });
    </script>
    @endpush

</x-default-layout>
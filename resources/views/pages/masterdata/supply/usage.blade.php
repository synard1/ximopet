<x-default-layout>

    @section('title')
    Data Pemakaian Supply
    @endsection

    @section('breadcrumbs')
    @endsection
    @if(auth()->user()->can('read supply usage'))
    <div class="card">
        <!--begin::Card header-->
        <div class="card-header border-0 pt-6">
            <!--begin::Card title-->
            <div class="card-title">
            </div>
            <!--begin::Card title-->

            @if(auth()->user()->can('create supply usage'))
            <!--begin::Card toolbar-->
            <div class="card-toolbar" id="cardToolbar">
                <!--begin::Toolbar-->
                <div class="d-flex justify-content-end" data-kt-user-table-toolbar="base">
                    <!--begin::Add user-->
                    <button type="button" class="btn btn-primary" onclick="Livewire.dispatch('showUsageForm')">
                        {!! getIcon('plus', 'fs-2', '', 'i') !!}
                        Tambah Data Pemakaian
                    </button>
                    <!--end::Add user-->
                </div>
                <!--end::Toolbar-->
            </div>
            <!--end::Card toolbar-->
            @endif
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
            <livewire:master-data.supply.usage />
        </div>
        <!--end::Card body-->

    </div>

    @php
    // Determine which statuses are possible based on config
    $config = \App\Config\SupplyUsageBypassConfig::getConfig();
    $possibleStatuses = ['draft', 'in_process', 'completed', 'cancelled'];
    // If approval is enabled in the future, add more statuses here
    @endphp
    <!--begin::Status Legend Card-->
    <div class="card mt-6">
        <div class="card-header" id="legendHeader" role="button" data-bs-toggle="collapse" data-bs-target="#legendBody"
            aria-expanded="false" aria-controls="legendBody" style="cursor: pointer;">
            <div class="card-title d-flex align-items-center justify-content-between w-100">
                <div class="d-flex align-items-center">
                    <i class="ki-duotone ki-information-5 fs-2 text-primary me-2">
                        <i class="path1"></i>
                        <i class="path2"></i>
                        <i class="path3"></i>
                    </i>
                    Legend Status
                </div>
                <div class="d-flex align-items-center">
                    <span class="badge badge-light-primary fs-7 fw-bold me-2">{{ count($possibleStatuses) }}
                        Status</span>
                    <i class="ki-duotone ki-down fs-3 text-muted transition-all" id="legendIcon">
                        <i class="path1"></i>
                        <i class="path2"></i>
                    </i>
                </div>
            </div>
        </div>
        <div class="collapse" id="legendBody" aria-labelledby="legendHeader">
            <div class="card-body">
                <div class="row g-4">
                    <!--begin::Draft Status-->
                    <div class="col-md-4" @if(!in_array('draft', $possibleStatuses)) style="display:none" @endif>
                        <div class="d-flex align-items-center">
                            <div class="badge badge-secondary fs-7 fw-bold me-3 px-3 py-2">
                                <i class="ki-duotone ki-file fs-6 me-1">
                                    <i class="path1"></i>
                                    <i class="path2"></i>
                                </i>
                                Draft
                            </div>
                            <div class="text-muted fs-7">
                                Data pemakaian supply masih dalam tahap penyusunan, belum disimpan permanen
                            </div>
                        </div>
                    </div>
                    <!--end::Draft Status-->

                    <!--begin::Pending Status-->
                    <div class="col-md-4" @if(!in_array('pending', $possibleStatuses)) style="display:none" @endif>
                        <div class="d-flex align-items-center">
                            <div class="badge badge-warning fs-7 fw-bold me-3 px-3 py-2">
                                <i class="ki-duotone ki-clock fs-6 me-1">
                                    <i class="path1"></i>
                                    <i class="path2"></i>
                                </i>
                                Pending
                            </div>
                            <div class="text-muted fs-7">
                                Data sudah disimpan tapi menunggu approval/konfirmasi
                            </div>
                        </div>
                    </div>
                    <!--end::Pending Status-->

                    <!--begin::In Process Status-->
                    <div class="col-md-4" @if(!in_array('in_process', $possibleStatuses)) style="display:none" @endif>
                        <div class="d-flex align-items-center">
                            <div class="badge badge-info fs-7 fw-bold me-3 px-3 py-2">
                                <i class="ki-duotone ki-gear fs-6 me-1">
                                    <i class="path1"></i>
                                    <i class="path2"></i>
                                </i>
                                In Process
                            </div>
                            <div class="text-muted fs-7">
                                Data stock di proses, data pemakaian belum lengkap.
                            </div>
                        </div>
                    </div>
                    <!--end::In Process Status-->

                    <!--begin::Completed Status-->
                    <div class="col-md-4" @if(!in_array('completed', $possibleStatuses)) style="display:none" @endif>
                        <div class="d-flex align-items-center">
                            <div class="badge badge-success fs-7 fw-bold me-3 px-3 py-2">
                                <i class="ki-duotone ki-check-circle fs-6 me-1">
                                    <i class="path1"></i>
                                    <i class="path2"></i>
                                </i>
                                Completed
                            </div>
                            <div class="text-muted fs-7">
                                Data pemakaian selesai dan tersimpan dengan benar
                            </div>
                        </div>
                    </div>
                    <!--end::Completed Status-->

                    <!--begin::Under Review Status-->
                    <div class="col-md-4" @if(!in_array('under_review', $possibleStatuses)) style="display:none" @endif>
                        <div class="d-flex align-items-center">
                            <div class="badge badge-primary fs-7 fw-bold me-3 px-3 py-2">
                                <i class="ki-duotone ki-eye fs-6 me-1">
                                    <i class="path1"></i>
                                    <i class="path2"></i>
                                </i>
                                Under Review
                            </div>
                            <div class="text-muted fs-7">
                                Data sedang direview oleh supervisor/manager
                            </div>
                        </div>
                    </div>
                    <!--end::Under Review Status-->

                    <!--begin::Rejected Status-->
                    <div class="col-md-4" @if(!in_array('rejected', $possibleStatuses)) style="display:none" @endif>
                        <div class="d-flex align-items-center">
                            <div class="badge badge-danger fs-7 fw-bold me-3 px-3 py-2">
                                <i class="ki-duotone ki-cross fs-6 me-1">
                                    <i class="path1"></i>
                                    <i class="path2"></i>
                                </i>
                                Rejected
                            </div>
                            <div class="text-muted fs-7">
                                Data ditolak oleh reviewer, perlu perbaikan
                            </div>
                        </div>
                    </div>
                    <!--end::Rejected Status-->

                    <!--begin::Cancelled Status-->
                    <div class="col-md-4" @if(!in_array('cancelled', $possibleStatuses)) style="display:none" @endif>
                        <div class="d-flex align-items-center">
                            <div class="badge badge-danger fs-7 fw-bold me-3 px-3 py-2">
                                <i class="ki-duotone ki-cross-circle fs-6 me-1">
                                    <i class="path1"></i>
                                    <i class="path2"></i>
                                </i>
                                Cancelled
                            </div>
                            <div class="text-muted fs-7">
                                Data pemakaian dibatalkan, stock dikembalikan
                            </div>
                        </div>
                    </div>
                    <!--end::Cancelled Status-->
                    <!--
                        Status legend ini mengikuti config di App\Config\SupplyUsageBypassConfig dan helper App\Helpers\SupplyUsageStatusHelper.
                        Jika config berubah (misal approval diaktifkan), tambahkan status lain sesuai kebutuhan.
                    -->
                </div>
            </div>
        </div>
    </div>
    <!--end::Status Legend Card-->

    @else
    <div class="card">
        <div class="card-body">
            <div class="text-center">
                <i class="fas fa-lock fa-3x text-danger mb-3"></i>
                <h3 class="text-danger">Unauthorized Access</h3>
                <p class="text-muted">You do not have permission to view supply usage data.</p>
            </div>
        </div>
    </div>
    @endif
    @include('pages.masterdata.supply._modal_usage_details')

    @push('scripts')
    {{ $dataTable->scripts() }}
    <script>
        // Initialize KTMenu
        KTMenu.init();

        // Handle status dropdown changes
        $(document).on("change", ".status-select", function () {
            const $select = $(this);
            const usageId = $select.data("kt-usage-id");
            const newStatus = $select.val();
            const currentStatus = $select.data("current");

            console.log("Supply Usage status change initiated:", {
                usageId: usageId,
                currentStatus: currentStatus,
                newStatus: newStatus
            });

            // Show confirmation dialog
            Swal.fire({
                title: "Update Status",
                text: `Apakah Anda yakin ingin mengubah status dari "${currentStatus}" ke "${newStatus}"?`,
                icon: "question",
                showCancelButton: true,
                confirmButtonText: "Ya, Update",
                cancelButtonText: "Batal",
                buttonsStyling: false,
                customClass: {
                    confirmButton: "btn btn-primary",
                    cancelButton: "btn btn-secondary",
                },
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading state
                    $select.prop('disabled', true);
                    
                    // Dispatch Livewire event
                    Livewire.dispatch("updateStatusSupplyUsage", {
                        usageId: usageId,
                        status: newStatus,
                        notes: null
                    });

                    // Show success message
                    // Swal.fire({
                    //     title: "Status Updated",
                    //     text: "Status berhasil diperbarui!",
                    //     icon: "success",
                    //     timer: 2000,
                    //     showConfirmButton: false,
                    // });
                } else {
                    // Reset to previous value if cancelled
                    $select.val(currentStatus);
                }
            });
        });

        // Handle success messages
        document.addEventListener('livewire:init', () => {
            // Listen for 'reload-table' event emitted by Livewire
            Livewire.on("reload-table", () => {
                // Reload all DataTables on the page
                if (window.LaravelDataTables) {
                    Object.values(LaravelDataTables).forEach(dt => {
                        if (dt && dt.ajax && typeof dt.ajax.reload === "function") {
                            dt.ajax.reload();
                        }
                    });
                }
            });

            Livewire.on('success', (message) => {
                Swal.fire({
                    title: "Success",
                    text: message,
                    icon: "success",
                    timer: 3000,
                    showConfirmButton: false,
                });
            });

            Livewire.on('error', (message) => {
                Swal.fire({
                    title: "Error",
                    text: message,
                    icon: "error",
                });
            });

            Livewire.on('statusUpdated', () => {
                // Refresh DataTable after status update
                const table = $("#supply-usage-table").DataTable();
                if (table && table.ajax) {
                    table.ajax.reload(null, false);
                }
            });

            // Handle datatable show/hide
                window.addEventListener('hide-datatable', () => {
                    $('#datatable-container').hide();
                    $('#cardToolbar').hide();
                });

                window.addEventListener('show-datatable', () => {
                    $('#datatable-container').show();
                    $('#cardToolbar').show();
                });
        });

        // Legend collapse functionality
        document.addEventListener('DOMContentLoaded', function() {
            const legendHeader = document.getElementById('legendHeader');
            const legendIcon = document.getElementById('legendIcon');
            
            if (legendHeader) {
                legendHeader.addEventListener('click', function() {
                    // Toggle icon rotation
                    legendIcon.style.transform = legendIcon.style.transform === 'rotate(180deg)' 
                        ? 'rotate(0deg)' 
                        : 'rotate(180deg)';
            });
            }
        });
    </script>
    @endpush
</x-default-layout>
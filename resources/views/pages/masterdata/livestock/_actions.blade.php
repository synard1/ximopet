@if(auth()->user()->canAny(['edit livestock management', 'read records management', 'read worker assignment']))
<a href="#" class="btn btn-light btn-active-light-primary btn-flex btn-center btn-sm" data-kt-menu-trigger="click"
    data-kt-menu-placement="bottom-end">
    Actions
    <i class="ki-duotone ki-down fs-5 ms-1"></i>
</a>
<!--begin::Menu-->
<div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-4"
    data-kt-menu="true">

    {{-- @if (auth()->user()->can('edit livestock management'))
    <!--begin::Menu item-->
    <div class="menu-item px-3">
        <a href="#" class="menu-link px-3" data-ternak-id="{{ $livestock->id }}" data-kt-action="update_row">
            Edit
        </a>
    </div>
    <!--end::Menu item-->
    @endif --}}

    @can('update livestock records')
    <!--begin::Menu item-->
    <div class="menu-item px-3">
        <a href="#" class="menu-link px-3" data-ternak-id="{{ $livestock->id }}" data-kt-action="update_records">
            Records
        </a>
    </div>
    <!--end::Menu item-->
    @endcan

    {{-- @if($livestock->isFifoDepletionEnabled())
    <!--begin::Menu item-->
    <div class="menu-item px-3">
        <a href="#" class="menu-link px-3" data-livestock-id="{{ $livestock->id }}" data-kt-action="fifo_depletion">
            <i class="ki-duotone ki-minus-circle fs-6 me-2">
                <span class="path1"></span>
                <span class="path2"></span>
            </i>
            FIFO Depletion
        </a>
    </div>
    <!--end::Menu item-->
    @endif --}}

    @if($livestock->isManualMutationEnabled())
    <!--begin::Menu item-->
    <div class="menu-item px-3">
        <a href="#" class="menu-link px-3" data-livestock-id="{{ $livestock->id }}" data-kt-action="manual_mutation">
            <i class="ki-duotone ki-arrows-loop fs-6 me-2">
                <span class="path1"></span>
                <span class="path2"></span>
            </i>
            Manual Mutation
        </a>
    </div>
    <!--end::Menu item-->
    @endif

    {{-- @if($livestock->isFifoMutationEnabled())
    <!--begin::Menu item-->
    <div class="menu-item px-3">
        <a href="#" class="menu-link px-3" data-livestock-id="{{ $livestock->id }}" data-kt-action="fifo_mutation">
            <i class="ki-duotone ki-arrows-loop fs-6 me-2">
                <span class="path1"></span>
                <span class="path2"></span>
            </i>
            FIFO Mutation
        </a>
    </div>
    <!--end::Menu item-->
    @endif --}}

    @if($livestock->isManualDepletionEnabled())
    <!--begin::Menu item-->
    <div class="menu-item px-3">
        <a href="#" class="menu-link px-3" data-livestock-id="{{ $livestock->id }}" data-kt-action="manual_depletion">
            <i class="ki-duotone ki-minus-circle fs-6 me-2">
                <span class="path1"></span>
                <span class="path2"></span>
            </i>
            Manual Depletion
        </a>
    </div>
    <!--end::Menu item-->
    @endif

    @if($livestock->isManualFeedUsageEnabled())
    <!--begin::Menu item-->
    <div class="menu-item px-3">
        <a href="#" class="menu-link px-3" data-livestock-id="{{ $livestock->id }}" data-kt-action="manual_usage">
            <i class="ki-duotone ki-minus-circle fs-6 me-2">
                <span class="path1"></span>
                <span class="path2"></span>
            </i>
            Manual Usage
        </a>
    </div>
    <!--end::Menu item-->
    @endif

    <!--begin::Menu item-->
    <div class="menu-item px-3">
        <a href="#" class="menu-link px-3" data-livestock-id="{{ $livestock->id }}" data-kt-action="update_setting">
            Setting
        </a>
    </div>
    <!--end::Menu item-->

    @can('read worker assignment')
    <!--begin::Menu item-->
    <div class="menu-item px-3">
        <a href="#" class="menu-link px-3" data-livestock-id="{{ $livestock->id }}" data-kt-action="assign_worker">
            Penugasan
        </a>
    </div>
    <!--end::Menu item-->
    @endcan

    @if($livestock->isLocked())
    <!--begin::Menu item-->
    <div class="menu-item px-3">
        <a href="#" class="menu-link px-3" data-ternak-id="{{ $livestock->id }}" data-kt-action="update_detail">
            Detail Ternak
        </a>
    </div>
    <!--end::Menu item-->

    <!--begin::Menu item-->
    <div class="menu-item px-3">
        <a href="#" class="menu-link px-3" data-kt-ternak-id="{{ $livestock->id }}" data-kt-action="final_row">
            Finalisasi
        </a>
    </div>
    <!--end::Menu item-->
    @endif

</div>
<!--end::Menu-->
@endif
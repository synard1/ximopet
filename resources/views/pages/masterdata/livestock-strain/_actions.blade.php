@if(auth()->user()->can('update livestock strain') || auth()->user()->can('delete livestock strain'))
<a href="#" class="btn btn-light btn-active-light-primary btn-flex btn-center btn-sm" data-kt-menu-trigger="click"
    data-kt-menu-placement="bottom-end">
    Actions
    <i class="ki-duotone ki-down fs-5 ms-1"></i>
</a>
<!--begin::Menu-->
<div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-4"
    data-kt-menu="true">

    <!--begin::Menu item-->
    @if(auth()->user()->can('update livestock strain master data'))
    <div class="menu-item px-3">
        <a href="#" class="menu-link px-3" data-kt-livestock-strain-id="{{ $livestockStrain->id }}"
            data-kt-action="update_row">
            Edit
        </a>
    </div>
    @endif
    <!--end::Menu item-->

    <!--begin::Menu item-->
    @if(auth()->user()->can('delete livestock strain master data'))
    <div class="menu-item px-3">
        <a href="#" class="menu-link px-3" data-kt-livestock-strain-id="{{ $livestockStrain->id }}"
            data-kt-action="delete_row">
            Delete
        </a>
    </div>
    @endif
    <!--end::Menu item-->
</div>
<!--end::Menu-->
@endif
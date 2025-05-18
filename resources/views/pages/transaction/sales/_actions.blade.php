<a href="#" class="btn btn-light btn-active-light-primary btn-flex btn-center btn-sm" data-kt-menu-trigger="click"
    data-kt-menu-placement="bottom-end">
    Actions
    <i class="ki-duotone ki-down fs-5 ms-1"></i>
</a>
<!--begin::Menu-->
<div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-4"
    data-kt-menu="true">

    <!--begin::Menu item-->
    <div class="menu-item px-3">
        <a href="#" class="menu-link px-3" onclick="Livewire.dispatch('showEditForm', [@js($transaction->id)])">
            Edit
        </a>
    </div>
    <!--end::Menu item-->

    @can('delete transaction')
    <!--begin::Menu item-->
    <div class="menu-item px-3">
        <a href="#" class="menu-link px-3" data-kt-transaction-id="{{ $transaction->id }}" data-kt-action="delete_row">
            Delete
        </a>
    </div>
    <!--end::Menu item-->
    @endcan
</div>
<!--end::Menu-->
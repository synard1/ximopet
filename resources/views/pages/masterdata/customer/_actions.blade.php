@if(auth()->user()->can('update customer master data') || auth()->user()->can('delete customer master data'))
<a href="#" class="btn btn-light btn-active-light-primary btn-flex btn-center btn-sm" data-kt-menu-trigger="click"
    data-kt-menu-placement="bottom-end">
    Actions
    <i class="ki-duotone ki-down fs-5 ms-1"></i>
</a>
<!--begin::Menu-->
<div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-4"
    data-kt-menu="true">
    {{--
    <!--begin::Menu item-->
    <div class="menu-item px-3">
        <a href="{{ route('master-data.customers.show', $customer) }}" class="menu-link px-3">
            View
        </a>
    </div>
    <!--end::Menu item--> --}}

    @if(auth()->user()->can('update customer master data'))
    <!--begin::Menu item-->
    <div class="menu-item px-3">
        {{-- <a href="#" class="menu-link px-3" wire:click="edit('{{ $customer->id }}')"> --}}
            {{-- <a href="#" class="menu-link px-3" data-kt-customer-id="{{ $customer->id }}" data-bs-toggle="modal"
                data-bs-target="#kt_modal_master_customer" data-kt-action="update_row"> --}}
                <a href="#" class="menu-link px-3" data-kt-customer-id="{{ $customer->id }}"
                    data-kt-action="update_row">
                    Edit
                </a>
                {{-- <button wire:click="edit('{{ $customer->id }}')" class="btn btn-sm btn-info">Edit</button> --}}

    </div>
    <!--end::Menu item-->
    @endif

    @if(auth()->user()->can('delete customer master data'))
    <!--begin::Menu item-->
    <div class="menu-item px-3">
        <a href="#" class="menu-link px-3" data-kt-customer-id="{{ $customer->id }}" data-kt-action="delete_row">
            {{-- <a href="#" class="menu-link px-3" wire:click="delete({{ $customer->id }})"> --}}
                Delete
            </a>
    </div>
    <!--end::Menu item-->
    @endif
</div>
<!--end::Menu-->
@endif
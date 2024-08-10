<!--begin::sidebar menu-->
<div class="menu-sidebar menu menu-fit menu-column menu-rounded menu-title-gray-700 menu-icon-gray-700 menu-arrow-gray-700 fw-semibold fs-6 align-items-stretch flex-grow-1" id="#kt_app_sidebar_menu" data-kt-menu="true" data-kt-menu-expand="true">
	<!--begin:Menu item-->
	<div class="menu-item py-1">
		<!--begin:Menu content-->
		<div class="menu-content">
			<div class="separator separator-dashed"></div>
		</div>
		<!--end:Menu content-->
	</div>
	<!--end:Menu item-->
	<!--begin:Menu item-->
	<div data-kt-menu-trigger="click" class="menu-item menu-accordion show">
		<!--begin:Menu link-->
		<span class="menu-link">
			<span class="menu-title">{{ trans('menu.create_header',[],'id') }}</span>
			<span class="menu-arrow"></span>
		</span>
		<!--end:Menu link-->
		<!--begin:Menu sub-->
		<div class="menu-sub menu-sub-accordion menu-state-gray-900 menu-fit open">
			<!--begin:Menu item-->
			<div class="menu-item menu-accordion menu-fit">
				<!--begin:Menu link-->
				<span class="menu-link">
					<span class="menu-icon">
						<i class="ki-outline ki-delivery-3 fs-4 text-success"></i>
					</span>
					<span class="menu-title">{{ trans('menu.create_supplier',[],'id') }}</span>
					<span class="menu-badge">
						{{-- <button class="btn btn-sm btn-icon btn-action" wire:click="openModal">
							<i class="ki-outline ki-plus fs-4"></i>
						</button> --}}
						{{-- <button class="btn btn-sm btn-icon btn-action" data-bs-toggle="modal" data-bs-target="#kt_modal_master_supplier"> --}}
						<button class="btn btn-sm btn-icon btn-action" data-kt-action="create_supplier">
							<i class="ki-outline ki-plus fs-4"></i>
						</button>
					</span>
				</span>
				<!--end:Menu link-->
			</div>
			<!--end:Menu item-->
			<!--begin:Menu item-->
			<div class="menu-item">
				<!--begin:Menu link-->
				<span class="menu-link">
					<span class="menu-icon">
						<i class="ki-outline ki-courier fs-4 text-danger"></i>
					</span>
					<span class="menu-title">{{ trans('menu.create_customer',[],'id') }}</span>
					<span class="menu-badge">
						<button class="btn btn-sm btn-icon btn-action" data-bs-toggle="modal" data-bs-target="#kt_modal_new_card">
							<i class="ki-outline ki-plus fs-4"></i>
						</button>
					</span>
				</span>
				<!--end:Menu link-->
			</div>
			<!--end:Menu item-->
			<!--begin:Menu item-->
			<div class="menu-item">
				<!--begin:Menu link-->
				<span class="menu-link">
					<span class="menu-icon">
						<i class="ki-outline ki-home fs-4 text-success"></i>
					</span>
					<span class="menu-title">{{ trans('menu.create_farm',[],'id') }}</span>
					<span class="menu-badge">
						<button class="btn btn-sm btn-icon btn-action" data-bs-toggle="modal" data-bs-target="#kt_modal_bidding">
							<i class="ki-outline ki-plus fs-4"></i>
						</button>
					</span>
				</span>
				<!--end:Menu link-->
			</div>
			<!--end:Menu item-->
			<!--begin:Menu item-->
			<div class="menu-item">
				<!--begin:Menu link-->
				<span class="menu-link">
					<span class="menu-icon">
						<i class="ki-outline ki-home-1 fs-4 text-info"></i>
					</span>
					<span class="menu-title">{{ trans('menu.create_cage',[],'id') }}</span>
					<span class="menu-badge">
						<button class="btn btn-sm btn-icon btn-action" data-bs-toggle="modal" data-bs-target="#kt_modal_invite_friends">
							<i class="ki-outline ki-plus fs-4"></i>
						</button>
					</span>
				</span>
				<!--end:Menu link-->
			</div>
			<!--end:Menu item-->
			<!--begin:Menu item-->
			<div class="menu-item">
				<!--begin:Menu link-->
				<span class="menu-link">
					<span class="menu-icon">
						<i class="ki-outline ki-cube-3 fs-4 text-warning"></i>
					</span>
					<span class="menu-title">{{ trans('menu.create_stock',[],'id') }}</span>
					<span class="menu-badge">
						<button class="btn btn-sm btn-icon btn-action" data-bs-toggle="modal" data-bs-target="#kt_modal_top_up_wallet">
							<i class="ki-outline ki-plus fs-4"></i>
						</button>
					</span>
				</span>
				<!--end:Menu link-->
			</div>
			<!--end:Menu item-->
		</div>
		<!--end:Menu sub-->
	</div>
	<!--end:Menu item-->
	<!--begin:Menu item-->
	<div class="menu-item py-1">
		<!--begin:Menu content-->
		<div class="menu-content">
			<div class="separator separator-dashed"></div>
		</div>
		<!--end:Menu content-->
	</div>
	<!--end:Menu item-->
	<!--begin:Menu item-->
	<div data-kt-menu-trigger="click" class="menu-item menu-accordion show">
		<!--begin:Menu link-->
		<span class="menu-link">
			<span class="menu-title">{{ trans('menu.create_transaction_header',[],'id') }}</span>
			<span class="menu-arrow"></span>
		</span>
		<!--end:Menu link-->
		<!--begin:Menu sub-->
		<div class="menu-sub menu-sub-accordion menu-state-gray-900 menu-fit open">
			<!--begin:Menu item-->
			<div class="menu-item menu-accordion menu-fit">
				<!--begin:Menu link-->
				<span class="menu-link">
					<span class="menu-icon">
						<i class="ki-outline ki-basket-ok fs-4 text-gray-700"></i>
					</span>
					<span class="menu-title">{{ trans('menu.create_purchasing',[],'id') }}</span>
					<span class="menu-badge">
						<button class="btn btn-sm btn-icon btn-action" data-bs-toggle="modal" data-bs-target="#kt_modal_new_target">
							<i class="ki-outline ki-plus fs-4"></i>
						</button>
					</span>
				</span>
				<!--end:Menu link-->
			</div>
			<!--end:Menu item-->
			<!--begin:Menu item-->
			<div class="menu-item menu-accordion menu-fit">
				<!--begin:Menu link-->
				<span class="menu-link">
					<span class="menu-icon">
						<i class="ki-outline ki-delivery-2 fs-4 text-gray-700"></i>
					</span>
					<span class="menu-title">{{ trans('menu.create_mutation_stock',[],'id') }}</span>
					<span class="menu-badge">
						<button class="btn btn-sm btn-icon btn-action" data-bs-toggle="modal" data-bs-target="#kt_modal_top_up_wallet">
							<i class="ki-outline ki-plus fs-4"></i>
						</button>
					</span>
				</span>
				<!--end:Menu link-->
			</div>
			<!--end:Menu item-->
			<!--begin:Menu item-->
			<div class="menu-item menu-accordion menu-fit">
				<!--begin:Menu link-->
				<span class="menu-link">
					<span class="menu-icon">
						<i class="ki-outline ki-notepad-edit fs-4 text-gray-700"></i>
					</span>
					<span class="menu-title">{{ trans('menu.create_mutation_livestock',[],'id') }}</span>
					<span class="menu-badge">
						<button class="btn btn-sm btn-icon btn-action" data-bs-toggle="modal" data-bs-target="#kt_modal_bidding">
							<i class="ki-outline ki-plus fs-4"></i>
						</button>
					</span>
				</span>
				<!--end:Menu link-->
			</div>
			<!--end:Menu item-->
		</div>
		<!--end:Menu sub-->
	</div>
	<!--end:Menu item-->
</div>
<!--end::sidebar menu-->

@push('scripts')
	<script>
		document.querySelectorAll('[data-kt-action="update_row"]').forEach(function (element) {
    element.addEventListener('click', function () {
        const supplierId = event.currentTarget.getAttribute('data-kt-supplier-id');
        console.log(supplierId);

        

        // showLoadingSpinner();
        Livewire.on('update_supplier', function (event) {
            // $('#kt_modal_master_supplier').modal('show');
            // Assuming event.detail.supplierId contains the actual ID:
            this.set('supplier_id', event.detail.supplierId); 
        });
        
        Livewire.dispatch('edit', [supplierId]);
        // this.set('supplier_id', event.detail.supplierId); 


        // const modal = new bootstrap.Modal(document.getElementById('kt_modal_1'));
        // modal.show();

        // toggleDiv('kt_modal_supplier_edit');
        
    });
});
	</script>
@endpush
<div>
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
						{{-- <livewire:master-data._create_supplier /> --}}
						{{-- <button class="btn btn-sm btn-icon btn-action" wire:click="openModal">
							<i class="ki-outline ki-plus fs-4"></i>
						</button> --}}
						<button class="btn btn-sm btn-icon btn-action" data-bs-toggle="modal" data-bs-target="#kt_modal_new_supplier">
						{{-- <button class="btn btn-sm btn-icon btn-action"> --}}
							<i class="ki-outline ki-plus fs-4"></i>
						</button>
						{{-- <a href="{{ route('supplier.create') }}"  class="btn btn-light-primary"><i class="ki-outline ki-plus fs-4"></i></a> --}}

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
						<button class="btn btn-sm btn-icon btn-action" data-bs-toggle="modal" data-bs-target="#kt_modal_new_customer">
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
						<button class="btn btn-sm btn-icon btn-action" data-bs-toggle="modal" data-bs-target="#kt_modal_new_farm">
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
						{{-- <button class="btn btn-sm btn-icon btn-action" data-bs-toggle="modal" data-bs-target="#kt_modal_new_kandang" data-kt-action="new_kandang" wire:click="openModal"> --}}
						<button class="btn btn-sm btn-icon btn-action" data-kt-action="new_kandang" wire:click="openModal">
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

@if($isOpen)
	{{-- <livewire:master-data.kandang-modal/> --}}
	@include('livewire.master-data.kandang-modal')
@endif

@push('styles')
	<style>
	/* Add this to your app.css or create a new stylesheet for this component */

.modal.d-block { 
    /* Reset the default modal styles that might be causing issues */
    position: fixed;  
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 1051; /* Ensure it's on top of other elements */
    display: flex; /* Use flexbox for centering */
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.modal.d-block .modal-dialog {
    /* Make the modal dialog expand to fill the container */
    max-width: none;
    width: 90%; /* Adjust this percentage as needed */
    height: 90%; /* Adjust this percentage as needed */
    margin: 0;
}

.modal.d-block .modal-content {
    height: 100%; /* Make the content fill the entire dialog */
}

.modal.d-block .modal-body {
    overflow-y: auto; /* Add scrollbar if content is too long */
}

/* Optional: Adjust the z-index of other elements if they overlap */
body > .header {
    z-index: 1049; /* Ensure the header is below the modal */
}
	</style>
@endpush

@push('scripts')
	<script>
		// Inside a <script> tag in your blade layout or component view
		document.addEventListener('DOMContentLoaded', function () {
			const modalDialog = document.querySelector('.modal-dialog'); // Or use your modal's specific class/ID
			function resizeModal() {
				modalDialog.style.maxHeight = window.innerHeight * 0.5 + 'px';
			}
			
			resizeModal(); // Initial resize
			window.addEventListener('resize', resizeModal); // Resize on window resize
		});

		document.querySelectorAll('[data-kt-action="new_kandang"]').forEach(function (element) {
		element.addEventListener('click', function () {
			console.log('create supplier click!');
			
			Livewire.dispatch('openModal');
			
		});
	});
	</script>
@endpush
</div>
